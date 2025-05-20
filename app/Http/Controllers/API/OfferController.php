<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Offer;
use Illuminate\Http\Request;
use App\Http\Utils\Message;
use App\Http\Utils\Status;
use App\Http\Utils\ApiResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\Item;

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
        try {
            // Validate the request data
            $validation = Validator::make($request->all(), [
                'item_id' => 'required|exists:items,id',
                'offered_item_ids' => 'required|array|min:1',
                'offered_item_ids.*' => 'exists:items,id|different:item_id',
                'message_text' => 'nullable|string|max:1000',
            ]);
    
            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }
    
            // Get the item that is being offered on
            $item = Item::find($request->item_id);
    
            // Check if the item belongs to the authenticated user
            if ($item->user_id === auth()->id()) {
                return $this->errorResponse(Status::INVALID_REQUEST, 'You cannot offer on your own item.');
            }
    
            // Create the offer
            $offer = Offer::create([
                'item_id' => $request->item_id,
                'offered_by' => auth()->id(),
                'message_text' => $request->message_text,
                'status' => 'pending',
            ]);
    
            // Attach the offered items to the offer
            $offer->offeredItems()->attach($request->offered_item_ids);
    
            return $this->successResponse(Status::OK, 'Offer was created successfully', compact('offer'));
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
            $offer = Offer::with(['item', 'offeredItems', 'user'])->find($id);

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
                'offered_item_ids' => 'sometimes|required|array|min:1',
                'offered_item_ids.*' => 'exists:items,id|different:item_id',
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

            // Update the offer details
            $offer->update([
                'item_id' => $request->item_id,
                'message_text' => $request->message_text,
                'status' => $request->status,
            ]);

            // If offered_item_ids are passed, update the offered items
            if ($request->has('offered_item_ids')) {
                // Detach existing offered items
                $offer->offeredItems()->detach();

                // Attach the new offered items
                $offer->offeredItems()->attach($request->offered_item_ids);
            }

            return $this->successResponse(Status::OK, 'Offer updated successfully', compact('offer'));

        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

// public function respondToOffer(Request $request, string $id)
// {
//     try {
//         $validation = Validator::make($request->all(), [
//             'status' => 'required|in:accepted,declined',
//         ]);

//         if ($validation->fails()) {
//             return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
//         }

//         $offer = Offer::with('item')->find($id);

//         if (!$offer) {
//             return $this->errorResponse(Status::NOT_FOUND, 'Offer not found.');
//         }

//         // Ensure only the owner of the item can respond
//         if (auth()->id() !== $offer->item->user_id) {
//             return $this->errorResponse(Status::FORBIDDEN, 'You are not authorized to respond to this offer.');
//         }

//         $offer->status = $request->status;
//         $offer->save();

//         return $this->successResponse(Status::OK, 'Offer status updated successfully.', compact('offer'));

//     } catch (\Exception $e) {
//         return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
//     }
// }

public function respondToOffer(Request $request, string $id)
{
    try {
        $validation = Validator::make($request->all(), [
            'status' => 'required|in:accepted,declined',
        ]);

        if ($validation->fails()) {
            return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
        }

        // Load offer with related item and all offeredItems (bartered items)
        $offer = Offer::with(['item', 'offeredItems'])->find($id);

        if (!$offer) {
            return $this->errorResponse(Status::NOT_FOUND, 'Offer not found.');
        }

        // Only item owner can respond
        if (auth()->id() !== $offer->item->user_id) {
            return $this->errorResponse(Status::FORBIDDEN, 'You are not authorized to respond to this offer.');
        }

        $offer->status = $request->status;
        $offer->save();

        return $this->successResponse(Status::OK, 'Offer status updated successfully.', [
            'offer' => $offer,
            'item' => $offer->item,
            'offered_items' => $offer->offeredItems, // returns an array of barter items
        ]);

    } catch (\Exception $e) {
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

            // Detach related offered items before deleting the offer
            $offer->offeredItems()->detach();

            $offer->delete();

            return $this->successResponse(Status::OK, 'Offer deleted successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

}
