<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Utils\Message;
use App\Http\Utils\Status;
use App\Http\Utils\ApiResponse;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    use ApiResponse;

    public function showTransaction()
    {
        try {
            $transaction = Transaction::with(['offer', 'initiator', 'recipient'])->get();

            if (!$transaction) {
                return $this->errorResponse(Status::NOT_FOUND, 'Transaction not found.');
            }

            return $this->successResponse(Status::OK, 'Transaction retrieved successfully', compact('transaction'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function createTransaction(Request $request)
    {
        try {

            $validation = Validator::make($request->all(), [
                'offer_id' => 'required|exists:offers,id',
                'initiator_id' => 'required|exists:users,id',
                'recipient_id' => 'required|exists:users,id',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            $transaction = Transaction::create([
                'offer_id' => $request->offer_id,
                'initiator_id' => $request->initiator_id,
                'recipient_id' => $request->recipient_id,
                'status' => 'pending'
            ]);

            return $this->successResponse(Status::OK, 'Transaction created successfully', compact('transaction'));

        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {

            $validation = Validator::make($request->all(), [
                'status' => 'required|in:pending,completed,cancelled,disputed',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            $transaction = Transaction::find($id);

            if (!$transaction) {
                return $this->errorResponse(Status::NOT_FOUND, 'Transaction not found.');
            }

            $transaction->update([
                'status' => $request->status,
                'completed_at' => $request->status === 'completed' ? now() : $transaction->completed_at,
                'cancelled_at' => $request->status === 'cancelled' ? now() : $transaction->cancelled_at,
                'disputed_at' => $request->status === 'disputed' ? now() : $transaction->disputed_at,
            ]);

            return $this->successResponse(Status::OK, 'Transaction status updated successfully', compact('transaction'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function showSpecifiedTransaction($id)
    {
        try {
            $transaction = Transaction::with(['offer', 'initiator', 'recipient'])->find($id);

            if (!$transaction) {
                return $this->errorResponse(Status::NOT_FOUND, 'Transaction not found.');
            }

            return $this->successResponse(Status::OK, 'Transaction retrieved successfully', compact('transaction'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function deleteTransaction($id)
    {
        try {
            $transaction = Transaction::find($id);

            if (!$transaction) {
                return $this->errorResponse(Status::NOT_FOUND, 'Transaction not found.');
            }

            $transaction->delete();

            return $this->successResponse(Status::OK, 'Transaction deleted successfully', compact('transaction'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

}
