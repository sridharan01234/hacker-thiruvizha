<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class IndexController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Validate the incoming request
            $request->validate([
                'message' => 'required|string',
            ]);

            // Get the API endpoint from environment variable
            $apiEndpoint = env('LOCAL_AI_ENDPOINT', 'http://localhost:8000/v1/chat/completions');

            // Make the API request
            $response = Http::post($apiEndpoint, [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $request->message
                    ]
                ],
                'model' => env('AI_MODEL_NAME', 'default-model'),
            ]);

            if ($response->successful()) {
                return response()->json([
                    'status' => 'success',
                    'data' => $response->json()
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get response from AI service'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
