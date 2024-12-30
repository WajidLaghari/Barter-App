<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Utils\Message;
use App\Http\Utils\Status;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use App\Http\Utils\ApiResponse;
use App\Models\User;

class UserController extends Controller
{
    use ApiResponse;

    public function register(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'username' => 'required|string|unique:users',
                'email' => 'required|string|email|unique:users',
                'password' => ['required', 'min:8'],
                'confirm_password' => ['required', 'same:password'],
                'first_name' => 'required|string',
                'last_name' => 'required|string',
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            $profilePicture = $request->file('profile_picture');
            $profilePicturePath = 'uploads/' . basename($profilePicture->move(public_path('uploads'), $profilePicture->hashName()));

            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'profile_picture' => $profilePicturePath,
                'status' => 1,
                'role' => 'user'
            ]);

            return $this->successResponse(Status::OK, 'user registration was successfully', compact('user'));
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->errorResponse(Status::INVALID_REQUEST, 'Email already exists');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function login(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'login' => 'required|string',
                'password' => 'required|string',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            $user = User::where(function ($query) use ($request) {
                if (filter_var($request->login, FILTER_VALIDATE_EMAIL)) {
                    $query->where('email', $request->login);
                } else {
                    $query->where('username', $request->login);
                }
            })->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->errorResponse(Status::UNAUTHORIZED, 'Invalid credentials');
            }

            // if (!$user->active) {
            //     return $this->errorResponse(Status::FORBIDDEN, 'Your account is not active. Please contact support.');
            // }
            if ($user->status !== 1) { // Check if the user exists
                return $this->errorResponse(Status::FORBIDDEN, 'User does not exist. Please contact support.');
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse(Status::OK, 'User logged in successfully', [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->errorResponse(Status::INVALID_REQUEST, 'Invalid request');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->tokens()->delete();

            return $this->successResponse(Status::OK, 'User logged out successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->errorResponse(Status::NOT_FOUND, 'User not found');
            }

            $validation = Validator::make($request->all(), [
                'username' => 'sometimes|required|string|unique:users,username,' . $id,
                'email' => 'sometimes|required|string|email|unique:users,email,' . $id,
                'first_name' => 'sometimes|required|string',
                'last_name' => 'sometimes|required|string',
                'profile_picture' => 'sometimes|required|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            if ($request->hasFile('profile_picture')) {
                if ($user->profile_picture) {
                    $oldImagePath = public_path($user->profile_picture);
                    if (File::exists($oldImagePath)) {
                        File::delete($oldImagePath);
                    }
                }

                $profilePicture = $request->file('profile_picture');
                $profilePicturePath = 'uploads/' . basename($profilePicture->move(public_path('uploads'), $profilePicture->hashName()));
                $user->profile_picture = $profilePicturePath;
            }

            $user->update($request->except(['password', 'profile_picture']));

            return $this->successResponse(Status::OK, 'User updated successfully', compact('user'));
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->errorResponse(Status::INVALID_REQUEST, 'Email or username already exists');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

}
