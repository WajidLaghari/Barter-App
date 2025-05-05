<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Validated;
use App\Http\Utils\Message;
use App\Http\Utils\Status;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use App\Http\Utils\ApiResponse;
use App\Models\Item;
use App\Models\Offer;

class ItemController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
    */
    public function index()
    {
        try {
            $items = Item::with('user','category')->get();
            return $this->successResponse(Status::OK, 'Items retrieved successfully', compact('items'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    /**
    * Store a newly created resource in storage.
    */
    // public function store(Request $request)
    // {
    //     try {

    //         $files = $request->file('images');
    //         if ($files && !is_array($files)) {
    //             $files = [$files];
    //         }

    //         $requestData = $request->all();
    //         $requestData['images'] = $files;

    //         $validation = Validator::make($requestData, [
    //             'category_id' => 'required|exists:categories,id',
    //             'title' => 'required|string|max:255',
    //             'description' => 'nullable|string',
    //             'location' => 'required|string|max:255',
    //             'price_estimate' => 'required|numeric',
    //             'images' => 'required|array',
    //             'images.*' => 'file|image|mimes:jpeg,png,jpg|max:2048',
    //         ]);

    //         if ($validation->fails()) {
    //             return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
    //         }

    //         $userId = auth()->id();

    //         $uploadedImages = [];
    //         foreach ($files as $image) {
    //             $imagePath = $image->store('uploads', 'public');
    //             $uploadedImages[] = asset('storage/' . $imagePath);
    //         }

    //         $item = Item::create([
    //             'user_id' => $userId,
    //             'category_id' => $request->category_id,
    //             'title' => $request->title,
    //             'description' => $request->description,
    //             'location' => $request->location,
    //             'price_estimate' => $request->price_estimate,
    //             'images' => json_encode($uploadedImages),
    //             'status' => 'stock',
    //             'is_Approved' => 'pending'
    //         ]);

    //         return $this->successResponse(Status::OK, 'Item created successfully', compact('item'));
    //     } catch (\Illuminate\Database\QueryException $e) {
    //         return $this->errorResponse(Status::INVALID_REQUEST, 'Database error occurred: ' . $e->getMessage());
    //     } catch (\Exception $e) {
    //         return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
    //     }
    // }
    public function store(Request $request)
    {
        try {
            $files = $request->file('images');
            if ($files && !is_array($files)) {
                $files = [$files]; // Ensure it's an array
            }

            $requestData = $request->all();
            $requestData['images'] = $files;

            $validation = Validator::make($requestData, [
                'category_id' => 'required|exists:categories,id',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'location' => 'required|string|max:255',
                'price_estimate' => 'required|numeric',
                'images' => 'required|array',
                'images.*' => 'file|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            $userId = auth()->id();

            $uploadedImages = [];
            foreach ($files as $image) {
                $imagePath = $image->store('uploads', 'public');
                $uploadedImages[] = asset('storage/' . $imagePath);
            }

            $item = Item::create([
                'user_id' => $userId,
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'location' => $request->location,
                'price_estimate' => $request->price_estimate,
                'images' => json_encode($uploadedImages), // ✅ correct
                'status' => 'stock',
                'is_Approved' => 'pending'
            ]);

            return $this->successResponse(Status::OK, 'Item created successfully', compact('item'));
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->errorResponse(Status::INVALID_REQUEST, 'Database error occurred: ' . $e->getMessage());
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
            $userId = Auth::id(); // current logged-in user ka ID
            $item = Item::with('user', 'category')
                        ->where('user_id', $userId)
                        ->find($id);

            if (!$item) {
                return $this->errorResponse(Status::NOT_FOUND, 'Item not found or not authorized');
            }

            return $this->successResponse(Status::OK, 'Item retrieved successfully', compact('item'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function showAllForUser()
    {
        try {
            $userId = Auth::id();
            $items = Item::with('user', 'category')
                        ->where('user_id', $userId)
                        ->get();

            if ($items->isEmpty()) {
                return $this->errorResponse(Status::NOT_FOUND, 'No items found for this user');
            }

            return $this->successResponse(Status::OK, 'Items retrieved successfully', compact('items'));
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
            $item = Item::find($id);

            if (!$item) {
                return $this->errorResponse(Status::NOT_FOUND, 'Item not found');
            }

            $validation = Validator::make($request->all(), [
                'category_id' => 'sometimes|exists:categories,id',
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|nullable|string',
                'location' => 'sometimes|string|max:255',
                'price_estimate' => 'sometimes|numeric',
                'images' => 'sometimes|array',
                'images.*' => 'file|image|mimes:jpeg,png,jpg|max:2048',
                'status' => 'sometimes|in:stock,out of stock,sold'
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            /*Handle new images if provided*/
            $files = $request->file('images');
            $uploadedImages = [];

            if ($files && is_array($files)) {
                /*Remove old images*/
                if ($item->images) {
                    $oldImages = json_decode($item->images, true);
                    foreach ($oldImages as $oldImage) {
                        if (File::exists(public_path($oldImage))) {
                            File::delete(public_path($oldImage));
                        }
                    }
                }

                /*Upload new images*/
                foreach ($files as $image) {
                    $imagePath = $image->store('uploads', 'public');
                    $uploadedImages[] = asset('storage/' . $imagePath);
                }
            } else {
                /*Retain old images if no new images are uploaded*/
                $uploadedImages = $item->images ? json_decode($item->images, true) : [];
            }

            $item->update([
                'category_id' => $request->get('category_id', $item->category_id),
                'title' => $request->get('title', $item->title),
                'description' => $request->get('description', $item->description),
                'location' => $request->get('location', $item->location),
                'price_estimate' => $request->get('price_estimate', $item->price_estimate),
                'images' => json_encode($uploadedImages),
                'status' => $request->get('status', $item->status),
                'available_until' => $request->get('available_until', $item->available_until),
            ]);

            return $this->successResponse(Status::OK, 'Item updated successfully', compact('item'));
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->errorResponse(Status::INVALID_REQUEST, 'Database error occurred: ' . $e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    // public function itemsWithOffers()
    // {
    //     try {
    //         $items = Item::where('user_id', auth()->id())
    //             ->whereHas('itemsWithOffer')
    //             ->withCount('itemsWithOffer')
    //             ->get();

    //         if ($items->isEmpty()) {
    //             return $this->errorResponse(Status::NOT_FOUND, 'No items with offers found.');
    //         }

    //         $itemsData = $items->map(function ($item) {
    //             return [
    //                 'item_id' => $item->id,
    //                 'title' => $item->title,
    //                 'offer_count' => $item->items_with_offer_count, // Offer count ka data
    //             ];
    //         });

    //         return $this->successResponse(Status::OK, 'Items with offers retrieved successfully.', [
    //             'items' => $itemsData,
    //         ]);
    //     } catch (\Exception $e) {
    //         return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
    //     }
    // }

    // public function itemWithOffersDetails()
    // {
    //     try {
    //         $items = Item::where('user_id', auth()->id()) // Jo user logged in hai, uske items
    //             ->whereHas('offersAsItem') // Jisme offers hon
    //             ->with(['user', 'category', 'offersAsItem.user']) // Offers ke saath user details bhi load ho
    //             ->get();

    //         if ($items->isEmpty()) {
    //             return $this->errorResponse(Status::NOT_FOUND, 'No items with offers found.');
    //         }

    //         // Items aur unke offers ka data prepare kar rahe hain
    //         $itemsData = $items->map(function ($item) {
    //             $offers = $item->offersAsItem->map(function ($offer) {
    //                 return [
    //                     'user_id' => $offer->user->id,
    //                     'name' => $offer->user->name,
    //                     'email' => $offer->user->email,
    //                     'profile_picture' => $offer->user->profile_picture,
    //                 ];
    //             });

    //             return [
    //                 'item_id' => $item->id,
    //                 'title' => $item->title,
    //                 'description' => $item->description,
    //                 'image' => $item->image,
    //                 'offers' => $offers,
    //             ];
    //         });

    //         return $this->successResponse(Status::OK, 'Items with offers retrieved successfully.', [
    //             'items' => $itemsData,
    //         ]);
    //     } catch (\Exception $e) {
    //         return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
    //     }
    // public function itemWithOffersDetails()
    // {
    //     try {
    //         // Fetch items with offers
    //         $items = Item::where('user_id', auth()->id()) // Get items for the logged-in user
    //             ->whereHas('offersAsItem') // Items that have offers
    //             ->with(['user', 'category', 'offersAsItem.user']) // Eager load related data
    //             ->get();

    //         if ($items->isEmpty()) {
    //             return $this->errorResponse(Status::NOT_FOUND, 'No items with offers found.');
    //         }

    //         // Map the items and prepare data including the offer count
    //         $itemsData = $items->map(function ($item) {

    //             // Prepare offer data
    //             $offers = $item->offersAsItem->map(function ($offer) {
    //                 return [
    //                     'user_id' => $offer->user->id,
    //                     'username' => $offer->user ? $offer->user->username : null, // Change name to username
    //                     'email' => $offer->user ? $offer->user->email : null,
    //                     'profile_picture' => $offer->user ? $offer->user->profile_picture : null,
    //                 ];
    //             });

    //             return [
    //                 'item_id' => $item->id,
    //                 'title' => $item->title,
    //                 'description' => $item->description,
    //                 'image' => $item->images[0] ?? null,  // Safe access to images
    //                 'offer_count' => $item->offersAsItem->count(),
    //                 'offers' => $offers,
    //             ];
    //         });

    //         return $this->successResponse(Status::OK, 'Items with offers retrieved successfully.', [
    //             'items' => $itemsData,
    //         ]);
    //     } catch (\Exception $e) {
    //         return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
    //     }
    // }

    public function itemWithOffersDetails()
    {
        try {
            // Fetch items with offers
            $items = Item::where('user_id', auth()->id()) // Get items for the logged-in user
                ->whereHas('offersAsItem') // Items that have offers
                ->with(['user', 'category', 'offersAsItem.user']) // Eager load related data
                ->get();

            if ($items->isEmpty()) {
                return $this->errorResponse(Status::NOT_FOUND, 'No items with offers found.');
            }

            // Map the items and prepare data including the offer count
            $itemsData = $items->map(function ($item) {
                // Handle images safely whether stored as JSON or array
                $images = is_array($item->images)
                    ? $item->images
                    : json_decode($item->images, true);

                if (!is_array($images)) {
                    $images = []; // fallback
                }

                // Prepare offer data
                $offers = $item->offersAsItem->map(function ($offer) {
                    return [
                        'user_id' => $offer->user->id,
                        'username' => $offer->user ? $offer->user->username : null,
                        'email' => $offer->user ? $offer->user->email : null,
                        'profile_picture' => $offer->user ? $offer->user->profile_picture : null,
                    ];
                });

                return [
                    'item_id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'images' => $images,
                    'offer_count' => $item->offersAsItem->count(),
                    'offers' => $offers,
                ];
            });

            return $this->successResponse(Status::OK, 'Items with offers retrieved successfully.', [
                'items' => $itemsData,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function itemsUserOfferedOn()
    {
        try {
            // Get offers made by the logged-in user (offered_by)
            $offers = Offer::where('offered_by', auth()->id())
                ->with(['item.user', 'item.category']) // Load related item, item owner, and category
                ->get();

            if ($offers->isEmpty()) {
                return $this->errorResponse(Status::NOT_FOUND, 'You have not made any offers.');
            }

            // Prepare the response data
            $offeredItems = $offers->map(function ($offer) {
                $item = $offer->item;

                // Decode images safely
                $images = is_array($item->images)
                    ? $item->images
                    : json_decode($item->images, true);

                if (!is_array($images)) {
                    $images = [];
                }

                return [
                    'item_id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'images' => $images,
                    'owner_id' => $item->user->id ?? null,
                    'owner_name' => $item->user->username ?? null,
                    'category' => $item->category->name ?? null,
                    'your_offer_id' => $offer->id,
                ];
            });

            return $this->successResponse(Status::OK, 'Items you have offered on retrieved successfully.', [
                'items' => $offeredItems,
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }


    public function viewOfferDetail($id)
    {
        try {
            $offer = Offer::with(['user', 'item.user', 'offeredItems'])
                ->find($id);

            if (!$offer) {
                return $this->errorResponse(Status::NOT_FOUND, 'Offer not found.');
            }

            $data = [
                'offer_id' => $offer->id,
                'message' => $offer->message_text,
                'status' => $offer->status,
                'offered_by' => [
                    'user_id' => $offer->user->id,
                    'name' => $offer->user->name,
                    'email' => $offer->user->email,
                    'profile_picture' => $offer->user->profile_picture,
                ],
                'offered_on_item' => [
                    'item_id' => $offer->item->id,
                    'title' => $offer->item->title,
                    'description' => $offer->item->description,
                    'images' => $offer->item->images,
                    'owner_name' => $offer->item->user->name,
                ],
                'barter_items' => $offer->offeredItems->map(function ($item) {
                    return [
                        'item_id' => $item->id,
                        'title' => $item->title,
                        'description' => $item->description,
                        'images' => $item->images,
                    ];
                }),
            ];

            return $this->successResponse(Status::OK, 'Offer details fetched successfully.', $data);
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong.');
        }
    }

    /**
    * Remove the specified resource from storage.
    */
    public function destroy(string $id)
    {
        try {
            $item = Item::find($id);

            if (!$item) {
                return $this->errorResponse(Status::NOT_FOUND, 'Item not found');
            }

            $item->update(['status' => 'out of stock']);

            $item->delete();

            return $this->successResponse(Status::OK, 'Item deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

}
