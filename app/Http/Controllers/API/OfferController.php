<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use App\Http\Utils\Message;
use App\Http\Utils\Status;
use App\Http\Utils\ApiResponse;
use Illuminate\Support\Facades\Validator;

class OfferController extends Controller
{
    use ApiResponse;
    public function index()
    {
        try {

            $offers = Offer::with(['item', 'offeredItem', 'user'])->get();

            return $this->successResponse(Status::OK, 'Offers retrieved successfully', compact('offers'));

        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try{
            $validation = Validator::make($request->all(),[
                'item_id' => 'required|exists:items,id',
                'offered_item_id' => 'required|exists:items,id|different:item_id',
                'message_text' => 'nullable|string|max:1000',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            $offer = Offer::create([
                'item_id' => $request->item_id,
                'offered_item_id' => $request->offered_item_id,
                'offered_by' => auth()->id(),
                'message_text' => $request->message_text,
                'status' => 'pending',
            ]);

            return $this->successResponse(Status::OK, 'Offer was created successfully', compact('offer'));
        }catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }


    /**
    * Display the specified resource.
    */
    public function show(string $id)
    {
        try {
            $offer = Offer::with(['item', 'offeredItem', 'user'])->find($id);

            if (!$offer) {
                return $this->errorResponse(Status::NOT_FOUND, 'Offer not found.');
            }

            return $this->successResponse(Status::OK, 'Offer retrieved successfully', compact('offer'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validation = Validator::make($request->all(), [
                'item_id' => 'sometimes|required|exists:items,id',
                'offered_item_id' => 'sometimes|required|exists:items,id|different:item_id',
                'message_text' => 'nullable|string|max:1000',
                'status' => 'nullable|in:pending,accepted,rejected',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            $offer = Offer::find($id);

            if (!$offer) {
                return $this->errorResponse(Status::NOT_FOUND, 'Offer not found.');
            }

            $offer->update([
                'item_id' => $request->item_id,
                'offered_item_id' => $request->offered_item_id,
                'message_text' => $request->message_text,
                'status' => $request->status,
            ]);

            return $this->successResponse(Status::OK, 'Offer updated successfully', compact('offer'));

        } catch (\Exception $e) {
            // Return error response if an exception occurs
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    /**
    * Remove the specified resource from storage.
    */
    public function destroy(string $id)
    {
        try {

            $offer = Offer::find($id);

            if (!$offer) {
                return $this->errorResponse(Status::NOT_FOUND, 'Offer not found.');
            }

            $offer->delete();

            return $this->successResponse(Status::OK, 'Offer deleted successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

}
