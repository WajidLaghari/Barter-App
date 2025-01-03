<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Utils\Message;
use App\Http\Utils\Status;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use App\Http\Utils\ApiResponse;
use App\Models\Category;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use ApiResponse;

    public function index()
    {
        try {
            $categories = Category::all();

            if ($categories->isEmpty()) {
                return $this->errorResponse(Status::NOT_FOUND, 'No categories found');
            }

            return $this->successResponse(Status::OK, 'Categories retrieved successfully', compact('categories'));
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
            $validation = Validator::make($request->all(), [
                'name' => 'required|string|unique:categories,name',
                'description' => 'required|string',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            $category = Category::create([
                'name' => $request->name,
                'description' => $request->description,
            ]);

        return $this->successResponse(Status::OK, 'Category was added successfully', compact('category'));
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
            $category = Category::find($id);

            if (!$category) {
                return $this->errorResponse(Status::NOT_FOUND, 'No category found');
            }

            return $this->successResponse(Status::OK, 'Category retrieved successfully', ['category' => $category]);
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $validation = Validator::make($request->all(), [
                'name' => 'required|string|unique:categories,name,' . $id,
                'description' => 'required|string',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            $category = Category::find($id);

            if (!$category) {
                return $this->errorResponse(Status::NOT_FOUND, 'No category found');
            }

            $category->name = $request->name;
            $category->description = $request->description;
            $category->save();

            return $this->successResponse(Status::OK, 'Category updated successfully', ['category' => $category]);
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
            $category = Category::find($id);

            if (!$category) {
                return $this->errorResponse(Status::NOT_FOUND, 'No category found');
            }

            $category->delete();

            return $this->successResponse(Status::OK, 'Category deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

}
