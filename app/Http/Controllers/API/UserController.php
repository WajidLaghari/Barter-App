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
use App\Models\Item;
use App\Models\User;
use Str;

class UserController extends Controller
{
    use ApiResponse;

    public function showUsers()
    {
        try {
            $users = User::where('role', '!=', 'admin')->where('status', 0)->get();

            return $this->successResponse(Status::OK, 'Users retrieved successfully', compact('users'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function showSubAdmins()
    {
        try {
            $subAdmins = User::where('role', 'subAdmin')->where('status', 0)->get();

            return $this->successResponse(Status::OK, 'subAdmins retrieved successfully', compact('subAdmins'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function createSubAdmin(Request $request)
    {
        try {

            if (Auth::user()->role !== 'admin') {
                return $this->errorResponse(Status::FORBIDDEN, 'Only admins can create sub-admins.');
            }

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
                'status' => 0,
                'role' => 'subAdmin'
            ]);

            return $this->successResponse(Status::OK, 'user registration was successfully', compact('user'));
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->errorResponse(Status::INVALID_REQUEST, 'Email already exists');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

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

            $profilePicturePath = null;

            if ($request->hasFile('profile_picture')) {
                $profilePicture = $request->file('profile_picture');
                $uniqueFileName = time() . '_' . uniqid() . '.' . $profilePicture->getClientOriginalExtension();
                $profilePicturePath = $profilePicture->storeAs('uploads', $uniqueFileName, 'public');

                $sourcePath = storage_path('app/public/uploads/' . $uniqueFileName);
                $publicPath = public_path('storage/uploads/' . $uniqueFileName);

                if (!file_exists(dirname($publicPath))) {
                    mkdir(dirname($publicPath), 0755, true);
                }

                if (file_exists($sourcePath)) {
                    copy($sourcePath, $publicPath);
                }

                $profilePicturePath = asset('storage/uploads/' . $uniqueFileName);
            }


            $user = User::create([
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'profile_picture' => $profilePicturePath,
                'status' => 0,
                'role' => 'user'
            ]);

            return $this->successResponse(Status::OK, 'User registration was successful', compact('user'));
        } catch (\Illuminate\Database\QueryException $e) {
             return $this->errorResponse(Status::INVALID_REQUEST, $e->getMessage());
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    // public function login(Request $request)
    // {
    //     try {
    //         $validation = Validator::make($request->all(), [
    //             'login' => 'required|string',
    //             'password' => 'required|string',
    //         ]);

    //         if ($validation->fails()) {
    //             return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
    //         }

    //         $user = User::where(function ($query) use ($request) {
    //             if (filter_var($request->login, FILTER_VALIDATE_EMAIL)) {
    //                 $query->where('email', $request->login);
    //             } else {
    //                 $query->where('username', $request->login);
    //             }
    //         })->first();

    //         if (!$user || !Hash::check($request->password, $user->password)) {
    //             return $this->errorResponse(Status::UNAUTHORIZED, 'Invalid credentials');
    //         }

    //         if ($user->status !== 0) {
    //             return $this->errorResponse(Status::FORBIDDEN, 'Account does not exist. Please contact support.');
    //         }

    //         $token = $user->createToken('auth_token')->plainTextToken;

    //         return $this->successResponse(Status::OK, 'User logged in successfully', [
    //             'user' => $user,
    //             'access_token' => $token,
    //             'token_type' => 'Bearer',
    //         ]);
    //     } catch (\Illuminate\Database\QueryException $e) {
    //         return $this->errorResponse(Status::INVALID_REQUEST, 'Invalid request');
    //     } catch (\Exception $e) {
    //         return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
    //     }
    // }
    public function login(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'username' => 'required|string',
                'password' => 'required|string'
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            $user = User::where(function ($query) use ($request){
                if (filter_var($request->username, FILTER_VALIDATE_EMAIL)) {
                    $query->where('email', $request->username);
                } else {
                    $query->where('username', $request->username);
                }
            })->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return $this->errorResponse(Status::UNAUTHORIZED, 'Invalid credentials');
            }

            if ($user->status !== 0) {
                return $this->errorResponse(Status::FORBIDDEN, 'Account does not exist. Please contact support.');
            }

            // Generate New FCM Token
            $newFcmToken = Str::random(32);
            $user->fcm_token = $newFcmToken;
            $user->save();

            $token = $user->createToken('auth_token')->plainTextToken;

            return $this->successResponse(Status::OK, 'User logged in successfully', [
                'user' => $user,
                'fcm_token' => $newFcmToken,
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
                'profile_picture' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }


            if ($request->hasFile('profile_picture')) {

                if ($user->profile_picture) {

                    $oldImagePath = str_replace(asset('/'), '', $user->profile_picture);
                    if (file_exists(public_path($oldImagePath))) {
                        unlink(public_path($oldImagePath));
                    }
                }

                $profilePicture = $request->file('profile_picture');
                $profilePictureName = time() . '_' . $profilePicture->getClientOriginalName();
                $profilePicturePath = $profilePicture->storeAs('uploads', $profilePictureName, 'public');

                $sourcePath = storage_path('app/public/uploads/' . $profilePictureName);
                $publicPath = public_path('storage/uploads/' . $profilePictureName);

                if (!file_exists(dirname($publicPath))) {
                    mkdir(dirname($publicPath), 0755, true);
                }

                if (file_exists($sourcePath)) {
                    copy($sourcePath, $publicPath);
                }

                $user->profile_picture = asset('storage/uploads/' . $profilePictureName);
            }

            $user->update($request->except(['password', 'profile_picture']));

            return $this->successResponse(Status::OK, 'User updated successfully', compact('user'));
        } catch (\Illuminate\Database\QueryException $e) {
            return $this->errorResponse(Status::INVALID_REQUEST, 'Email or username already exists');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function show($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->errorResponse(Status::NOT_FOUND, 'User not found');
            }

            return $this->successResponse(Status::OK, 'User retrieved successfully', compact('user'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function myProfile()
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->errorResponse(Status::UNAUTHORIZED, 'User not authenticated');
            }

            return $this->successResponse(Status::OK, 'User profile retrieved successfully', compact('user'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function delete($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return $this->errorResponse(Status::NOT_FOUND, 'User not found');
            }

            $user->status = 1;
            $user->save();

            $user->delete();

            return $this->successResponse(Status::OK, 'User status updated to inactive');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function inactiveUsers()
    {
        try {
            $trashedUsers = User::onlyTrashed()->get();

            if ($trashedUsers->isEmpty()) {
                return $this->errorResponse(Status::NOT_FOUND, 'No User found');
            }

            return $this->successResponse(Status::OK, 'Inactive users retrieved successfully', compact('trashedUsers'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function restoreUser($id)
    {
        try {
            $user = User::withTrashed()->find($id);

            if (!$user) {
                return $this->errorResponse(Status::NOT_FOUND, 'User not found');
            }

            $user->restore();

            $user->status = 0;
            $user->save();

            return $this->successResponse(Status::OK, 'User restored and activated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function permanentDeleteUser($id)
    {
        try {
            $user = User::withTrashed()->find($id);

            if (!$user) {
                return $this->errorResponse(Status::NOT_FOUND, 'User not found');
            }

            if ($user->profile_picture && File::exists(public_path($user->profile_picture))) {
                File::delete(public_path($user->profile_picture));
            }

            $user->forceDelete();

            return $this->successResponse(Status::OK, 'User permanently deleted');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function updatePassword(Request $request)
    {
        try {
            $validation = Validator::make($request->all(), [
                'old_password' => 'required|string',
                'new_password' => 'required|string|min:8|different:old_password',
                'confirm_new_password' => 'required|string|same:new_password',
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            $user = Auth::user();

            if (!Hash::check($request->old_password, $user->password)) {
                return $this->errorResponse(Status::UNAUTHORIZED, 'Old password is incorrect');
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return $this->successResponse(Status::OK, 'Password updated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function showItems(Request $request)
    {
        try {

            if (!in_array(auth()->user()->role, ['admin', 'subAdmin'])) {
                return $this->errorResponse(Status::FORBIDDEN, 'Only admins or sub-admins can view pending items.');
            }

            $items = Item::with(['user', 'category'])->where('is_Approved', 'pending')->get();

            return $this->successResponse(Status::OK, 'Pending items retrieved successfully', compact('items'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function isApproved(Request $request, $itemId)
    {
        try {
            if (!in_array(auth()->user()->role, ['admin', 'subAdmin'])) {
                return $this->errorResponse(Status::FORBIDDEN, 'Only admins or sub admin can approved the item.');
            }

            $request->validate([
                'is_approved' => 'required|in:approved,rejected',
            ]);

            $item = Item::find($itemId);

            if (!$item) {
                return $this->errorResponse(Status::NOT_FOUND, 'item not found');
            }

            $item->is_Approved = $request->is_approved;
            $item->save();

            return $this->successResponse(Status::OK, 'Item has been ' . $request->is_Approved, compact('item'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function deleteSubAdmin($id)
    {
        try {
            $subAdmin = User::find($id);

            if (!$subAdmin) {
                return $this->errorResponse(Status::NOT_FOUND, 'SubAdmin not found');
            }

            $subAdmin->status = 1;
            $subAdmin->save();

            $subAdmin->delete();

            return $this->successResponse(Status::OK, 'SubAdmin status updated to inactive');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function inactiveSubAdmins()
    {
        try {
            $trashedSubAdmins = User::onlyTrashed()->get();

            if ($trashedSubAdmins->isEmpty()) {
                return $this->errorResponse(Status::NOT_FOUND, 'No SubAdmin found');
            }

            return $this->successResponse(Status::OK, 'Inactive SubAdmins retrieved successfully', compact('trashedSubAdmins'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function restoreSubAdmin($id)
    {
        try {
            $subAdmin = User::withTrashed()->find($id);

            if (!$subAdmin) {
                return $this->errorResponse(Status::NOT_FOUND, 'SubAdmin not found');
            }

            $subAdmin->restore();

            $subAdmin->status = 0;
            $subAdmin->save();

            return $this->successResponse(Status::OK, 'SubAdmin restored and activated successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function permanentDeleteSubAdmin($id)
    {
        try {
            $subAdmin = User::withTrashed()->find($id);

            if (!$subAdmin) {
                return $this->errorResponse(Status::NOT_FOUND, 'SubAdmin not found');
            }

            if ($subAdmin->profile_picture && File::exists(public_path($subAdmin->profile_picture))) {
                File::delete(public_path($subAdmin->profile_picture));
            }

            $subAdmin->forceDelete();

            return $this->successResponse(Status::OK, 'SubAdmin permanently deleted');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }

    public function getPendingVerifiedUsers()
    {
        try {
            $users = User::where('is_approved', 'pending')
                ->whereHas('verification', function ($query) {
                    $query->whereNotNull('profile_picture')
                        ->whereNotNull('cnic_front')
                        ->whereNotNull('cnic_back');
                })
                ->with(['verification' => function ($query) {
                    $query->select('user_id', 'profile_picture', 'cnic_front', 'cnic_back');
                }])
                ->get(['id', 'username', 'email', 'is_approved']);

            if ($users->isEmpty()) {
                return $this->errorResponse(Status::NOT_FOUND, 'No pending verified users found.');
            }

            return $this->successResponse(Status::OK, 'Pending verified users fetched successfully', compact('users'));
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }
}
