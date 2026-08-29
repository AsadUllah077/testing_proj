<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send FCM notification using HTTP v1 API
     *
     * @param  string  $fcm_token  The device token
     * @param  array|null  $data1  Notification data containing title, body, route, etc.
     * @return \Illuminate\Http\JsonResponse
     */
    public static function sendNotification($fcm_token, $data1 = null)
    {
        if (empty(trim((string) $fcm_token))) {
            Log::warning('FCM sendNotification skipped: empty token.');

            return null;
        }

        try {
            // 1. Path to your Firebase Service Account JSON file
            $credentialsPath = public_path('firebase/service-account.json');

            if (! file_exists($credentialsPath)) {
                Log::error('FCM Service Account file not found at: '.$credentialsPath);

                return response()->json(['success' => false, 'message' => 'Service account file missing'], 500);
            }

            // 2. Get OAuth2 Access Token
            $client = new GoogleClient;
            $client->setAuthConfig($credentialsPath);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');
            $client->fetchAccessTokenWithAssertion();

            $tokenResponse = $client->getAccessToken();
            if (! isset($tokenResponse['access_token'])) {
                Log::error('FCM: Failed to fetch access token', ['response' => $tokenResponse]);

                return response()->json(['success' => false, 'message' => 'Auth failed'], 500);
            }
            $accessToken = $tokenResponse['access_token'];

            // 3. Prepare the Message Payload
            // Get Project ID from the service account file
            $json = json_decode(file_get_contents($credentialsPath), true);
            $projectId = $json['project_id'] ?? 'erpv4notification';

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $payload = [
                'message' => [
                    'token' => $fcm_token,
                    'notification' => [
                        'title' => $data1['title'] ?? 'Notification',
                        'body' => $data1['body'] ?? '',
                    ],
                    'data' => [
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ],
            ];

            // 4. Send the Request using Laravel's Http Client
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if (! $response->successful()) {
                Log::error('FCM v1 Send Error: '.$response->body());

                // If token is unregistered/invalid, clear it from the database
                $errorCode = $response->json('error.details.0.errorCode') ?? '';
                if (in_array($errorCode, ['UNREGISTERED', 'INVALID_ARGUMENT'])) {
                    \App\Models\Employee::where('fcm_token', $fcm_token)
                        ->update(['fcm_token' => null]);
                    Log::info('FCM: Cleared stale token for errorCode: '.$errorCode);
                }
            }

            return response()->json($response->json());

        } catch (\Exception $e) {
            Log::error('FCM Service Error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
