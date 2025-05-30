<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Utils\ApiResponse;
use Illuminate\Support\Facades\Validator;
use App\Http\Utils\Status;
use App\Http\Utils\Message;
use App\Models\IsVerified;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserVerificationController extends Controller
{
    use ApiResponse;

    public function verifyProfile(Request $request)
    {

        $validation = Validator::make($request->all(), [
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'cnic_front' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'cnic_back' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validation->fails()) {
            return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
        }

        $user = auth()->user();

        if (!$user) {
            return $this->errorResponse(Status::INVALID_REQUEST, 'User not authenticated.');
        }

        $verificationRecord = IsVerified::where('user_id', $user->id)->first();

        if ($verificationRecord) {
            return $this->errorResponse(Status::INVALID_REQUEST, 'Verification documents already submitted.');
        }

        $profilePicture = $request->file('profile_picture')->store('uploads', 'public');
        $cnicFront = $request->file('cnic_front')->store('uploads', 'public');
        $cnicBack = $request->file('cnic_back')->store('uploads', 'public');

        IsVerified::create([
            'user_id' => $user->id,
            'profile_picture' => $profilePicture,
            'cnic_front' => $cnicFront,
            'cnic_back' => $cnicBack,
        ]);

        return $this->successResponse(Status::OK, 'Verification documents uploaded successfully. Please wait for admin approval.');
    }

    public function getVerificationDocuments(Request $request, $userId)
    {
        $authUser = auth()->user();

        if (!$authUser || $authUser->role !== 'admin') {
            return $this->errorResponse(Status::UNAUTHORIZED, 'Only admins can access this resource.');
        }

        $verification = IsVerified::where('user_id', $userId)->first();

        if (!$verification) {
            return $this->errorResponse(Status::NOT_FOUND, 'Verification documents not found.');
        }

        return $this->successResponse(Status::OK, 'Verification documents fetched successfully.', [
            'profile_picture' => asset('storage/' . $verification->profile_picture),
            'cnic_front' => asset('storage/' . $verification->cnic_front),
            'cnic_back' => asset('storage/' . $verification->cnic_back),
        ]);
    }

    public function handleVerification(Request $request, $id)
    {
        $verification = IsVerified::find($id);

        if (!$verification) {
            return $this->errorResponse(Status::INVALID_REQUEST, 'Verification record not found.');
        }

        $action = $request->input('action');

        if ($action == 'approve') {

            $user = User::find($verification->user_id);
            if ($user) {
                $user->update(['is_verified' => 1]);
            }

            $verification->update(['admin_comment' => $request->input('comment', 'Verified and approved')]);

            return $this->successResponse(Status::OK, 'User verification approved successfully.');
        } elseif ($action == 'disapprove') {

            $user = User::find($verification->user_id);
            if ($user) {
                $user->update(['is_verified' => false]);
            }

            $verification->update(['admin_comment' => $request->input('comment', 'Verification failed')]);

            return $this->successResponse(Status::OK, 'User verification disapproved successfully.');
        }

        return $this->errorResponse(Status::INVALID_REQUEST, 'Invalid action. Please specify either "approve" or "disapprove".');
    }
}
