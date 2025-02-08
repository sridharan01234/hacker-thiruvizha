<?php
namespace App\Http\Controllers;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Check if this is the first interaction
            if (!Session::has('chat_started')) {
                Session::put('chat_started', true);
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'choices' => [
                            [
                                'message' => [
                                    'content' => "Hello! I'm your Medical assistant. How can I help you today?"
                                ]
                            ]
                        ]
                    ]
                ]);
            }

            // Validate the incoming request
            $request->validate([
                'message' => 'required|string',
            ]);

            // Get the API endpoint from environment variable
            $apiEndpoint = env('LOCAL_AI_ENDPOINT', 'http://localhost:1234/v1/chat/completions');

            // Retry logic with exponential backoff
            $response = retry(3, function () use ($apiEndpoint, $request) {
                return Http::timeout(180) // Timeout set to 180 seconds
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->post($apiEndpoint, [
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $request->message,
                            ]
                        ],
                        'context' => [
                            'session_id' => Session::getId(),
                        ],
                        'temperature' => env('AI_TEMPERATURE', 0.5),
                        'max_tokens' => env('AI_MAX_TOKENS', 100),
                        'top_p' => env('AI_TOP_P', 1),
                        'frequency_penalty' => env('AI_FREQUENCY_PENALTY', 0),
                        'presence_penalty' => env('AI_PRESENCE_PENALTY', 0),
                        'stop' => env('AI_STOP', ['\n']), // Stop on newline character
                        'stream' => false,
                        'model' => env('AI_MODEL_NAME', 'default-model'),
                    ]);
            }, 500);

            // Check if the response was successful
            if ($response->successful()) {
                try {
                    return response()->json([
                        'status' => 'success',
                        'messages' => $response->json('choices.0.message') ?? null
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to process response data'
                    ], 500);
                }
            }            

            // Log the error response for debugging
            Log::error('AI Service Error', [
                'status_code' => $response->status(),
                'response' => $response->body(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get response from AI service: ' . $response->status()
            ], 500);

        } catch (RequestException $e) {
            // Catch timeout or disconnection errors specifically
            if ($e->getCode() == 28) { // HTTP_TIMEOUT_CODE (28) for timeouts in Guzzle
                Log::error('Request Timeout', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Request to the AI service timed out.'
                ], 504); // 504 is the standard Gateway Timeout error code
            }

            // Log any other exceptions
            Log::error('AI Service Exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing your request: ' . $e->getMessage()
            ], 500);
        }
    }
}
