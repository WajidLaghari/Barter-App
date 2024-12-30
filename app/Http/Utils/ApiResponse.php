<?php

namespace App\Http\Utils;

use App\Http\Utils\Status;

trait ApiResponse
{
    public function successResponse(Status $code, Message|string $message, array $data = null)
    {
        $responseData = [
            'status' => $code->name,
            'messages' =>  is_string($message) ? $message : $message->value,
        ];

        if ($data && is_array($data)) {
            foreach ($data as $key => $value) {
                $responseData[$key] = $value;
            };
        }

        return response()->json($responseData, $code->value);
    }

    public function errorResponse(Status $code, Message|string $message, array|string $data = null)
    {
        $responseData = [
            'status' => $code->name,
            'messages' => is_string($message) ? $message : $message->value,
        ];

        if ($data && is_array($data)) {
            $responseData['errors'] = array_map(fn($message) => $message[0], $data);
        }

        if ($data && is_string($data)) {
            $responseData['error'] = $data;
        }

        return response()->json($responseData, $code->value);
    }
}
