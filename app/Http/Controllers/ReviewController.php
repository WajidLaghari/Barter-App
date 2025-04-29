<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Utils\Message;
use App\Http\Utils\Status;
use App\Http\Utils\ApiResponse;
use App\Models\Review;
use App\Models\Transaction;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use ApiResponse;
    public function index()
    {
        try {
            $userId = auth()->id();
            $reviews = Review::where('reviewer_id', $userId)
                ->orWhere('reviewee_id', $userId)
                ->with('transaction')
                ->get();

            return $this->successResponse(Status::OK, 'Reviews fetched successfully', compact('reviews'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validation
            $validator = Validator::make($request->all(), [
                'transaction_id' => 'required|exists:transactions,id',
                'rating' => 'required|integer|min:1|max:5',
                'comment' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validator->errors()->toArray());
            }

            $reviewerId = auth()->id();
            $transaction = Transaction::findOrFail($request->transaction_id);

            // Determine the reviewee
            if ($transaction->initiator_id == $reviewerId) {
                $revieweeId = $transaction->recipient_id;
            } elseif ($transaction->recipient_id == $reviewerId) {
                $revieweeId = $transaction->initiator_id;
            } else {
                return $this->errorResponse(Status::FORBIDDEN, 'You are not part of this transaction');
            }


            // Prevent duplicate reviews
            $alreadyReviewed = Review::where('transaction_id', $transaction->id)
                ->where('reviewer_id', $reviewerId)
                ->exists();

            if ($alreadyReviewed) {
                return $this->errorResponse(Status::CONFLICT, 'You have already reviewed this transaction');
            }

            // Save review
            $review = Review::create([
                'transaction_id' => $transaction->id,
                'reviewer_id' => $reviewerId,
                'reviewee_id' => $revieweeId,
                'rating' => $request->rating,
                'comment' => $request->comment
            ]);

            return $this->successResponse(Status::CREATED, 'Review submitted successfully', compact('review'));

        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $review = Review::with('transaction')->find($id);

            if (!$review) {
                return $this->errorResponse(Status::NOT_FOUND, 'Review not found');
            }

            $userId = auth()->id();

            if ($review->reviewer_id !== $userId && $review->reviewee_id !== $userId) {
                return $this->errorResponse(Status::FORBIDDEN, 'You are not authorized to view this review');
            }

            return $this->successResponse(Status::OK, 'Review fetched successfully', compact('review'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $review = Review::find($id);

            if (!$review) {
                return $this->errorResponse(Status::NOT_FOUND, 'Review not found');
            }

            $userId = auth()->id();

            // Only the reviewer can delete the review
            if ($review->reviewer_id !== $userId) {
                return $this->errorResponse(Status::FORBIDDEN, 'You are not authorized to delete this review');
            }

            $review->delete();

            return $this->successResponse(Status::OK, 'Review deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }
}
