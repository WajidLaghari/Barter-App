<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Utils\Message;
use App\Http\Utils\Status;
use App\Http\Utils\ApiResponse;
use App\Http\Utils\Fcm;
use App\Models\Notification;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Exception;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    use ApiResponse;

    public function sendUserNotification(Request $request)
    {
        try {
            // Validate Request
            $validation = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'type'    => 'required|string|in:listing_approved,listing_rejected,barter_received,barter_confirmed',
                'content' => 'required|string'
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            // Get User and FCM Token
            $user = User::find($request->user_id);
            $fcmToken = $user->fcm_token;

            // Store Notification in Database
            $notification = Notification::create([
                'user_id' => $request->user_id,
                'type'    => $request->type,
                'content' => $request->content
            ]);

            // Send FCM Notification if Token Exists
            if ($fcmToken) {
                $factory = (new Factory)->withServiceAccount(config('services.fcm.credentials'));
                $messaging = $factory->createMessaging();

                $fcmNotification = FcmNotification::create('New Notification', $request->content);

                $message = CloudMessage::withTarget('token', $fcmToken)
                    ->withNotification($fcmNotification)
                    ->withData([
                        'type' => $request->type,
                        'content' => $request->content
                    ]);

                $fcmResponse = $messaging->send($message);
            } else {
                $fcmResponse = 'No FCM token found for user';
            }

            // Return Success Response
            return $this->successResponse(Status::OK, 'Notification sent successfully', [
                'notification' => $notification,
                'fcm_response' => $fcmResponse
            ]);
        } catch (Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function getUserNotifications()
    {
        try {
            $notifications = Notification::with('user')->orderBy('created_at', 'desc')->get();
            return $this->successResponse(Status::OK, 'Notifications retrieved successfully', compact('notifications'));
        } catch (Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }
}
