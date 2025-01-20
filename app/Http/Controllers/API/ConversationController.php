<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Utils\ApiResponse;
use App\Http\Utils\Status;
use App\Http\Utils\Message;
use App\Models\Conversation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConversationController extends Controller
{

    /**
     * Display a listing of the resource.
     */
    use ApiResponse;

    public function index()
    {
        try{
        $conversations = Conversation::with('userOne', 'userTwo')->get();
        return $this->successResponse(Status::OK, 'Conversation retrieved successfully', compact('conversations'));

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
                'user_one_id' => 'required|exists:users,id',
                'user_two_id'=> 'required|exists:users,id'
            ]);

            if ($validation->fails()) {
                return $this->errorResponse(Status::INVALID_REQUEST, Message::VALIDATION_FAILURE, $validation->errors()->toArray());
            }

            $conversation = Conversation::create([
                'user_one_id' => $request->user_one_id,
                'user_two_id' => $request->user_two_id,
            ]);

            return $this->successResponse(Status::OK, 'Conversation was created successfully', ['conversation' => $conversation]
            );
       }catch(\Exception $e){
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
       }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try{
            $conversation = Conversation::with('userOne', 'userTwo')->find($id);
            return $this->successResponse(Status::OK, 'Conversation retrieved successfully', compact('conversation'));

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
            $conversation = Conversation::find($id);

            if (!$conversation) {
                return $this->errorResponse(Status::NOT_FOUND, 'No conversation found');
            }

            $conversation->delete();

            return $this->successResponse(Status::OK, 'Conversation deleted successfully');
        } catch (\Exception $e) {
            return $this->errorResponse(Status::INTERNAL_SERVER_ERROR, 'Something went wrong. Please try again.');
        }
    }
}
