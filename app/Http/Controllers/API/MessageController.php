<?php

namespace App\Http\Controllers\API;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    // Send a message
    public function send(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:conversations,id',
            'content' => 'required|string',
        ]);

        // Get the currently authenticated user
        $sender_id = Auth::id();

        // Create the message
        $message = Message::create([
            'conversation_id' => $request->conversation_id,
            'sender_id' => $sender_id,
            'content' => $request->content,
        ]);

        // Broadcast the message to the appropriate channel
        broadcast(new MessageSent($message));

        // Return response
        return response()->json($message, 201);
    }

    // Mark message as read
    public function markAsRead($messageId)
    {
        $message = Message::findOrFail($messageId);
        $message->update(['read' => true]);

        return response()->json($message);
    }
}
