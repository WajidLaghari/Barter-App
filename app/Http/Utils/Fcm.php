<?php

namespace App\Http\Utils;

use Google\Client as GoogleClient;
use GuzzleHttp\Client as GuzzleClient;
use Exception;

class Fcm
{
    public static function sendNotification($fcmToken, $title, $body)
    {
        $firebaseProjectId = env('FIREBASE_PROJECT_ID');
        $fcmUrl = "https://fcm.googleapis.com/v1/projects/$firebaseProjectId/messages:send";

        $client = new GoogleClient();
        $client->useApplicationDefaultCredentials();
        $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

        $accessToken = $client->fetchAccessTokenWithAssertion()['access_token'];

        $notification = [
            "message" => [
                "token" => $fcmToken,
                "notification" => [
                    "title" => $title,
                    "body" => $body
                ]
            ]
        ];

        $headers = [
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json'
        ];

        $http = new GuzzleClient();
        try {
            $response = $http->post($fcmUrl, [
                'headers' => $headers,
                'json' => $notification
            ]);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
