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
    public function store(Request $request)
    {
        try {

            $files = $request->file('images');
            if ($files && !is_array($files)) {
                $files = [$files];
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
                $imagePath = 'uploads/' . $image->hashName();
                $image->move(public_path('uploads'), $image->hashName());
                $uploadedImages[] = $imagePath;
            }

            $item = Item::create([
                'user_id' => $userId,
                'category_id' => $request->category_id,
                'title' => $request->title,
                'description' => $request->description,
                'location' => $request->location,
                'price_estimate' => $request->price_estimate,
                'images' => json_encode($uploadedImages),
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
            $item = Item::with('user', 'category')->find($id);

            if (!$item) {
                return $this->errorResponse(Status::NOT_FOUND, 'Item not found');
            }

            return $this->successResponse(Status::OK, 'Item retrieved successfully', compact('item'));
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
                    $imagePath = 'uploads/' . $image->hashName();
                    $image->move(public_path('uploads'), $image->hashName());
                    $uploadedImages[] = $imagePath;
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
