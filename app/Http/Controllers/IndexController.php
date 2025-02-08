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
            // Validate the incoming request
            $request->validate([
                'message' => 'required|string',
            ]);
    
            $userMessage = [
                'role' => 'user',
                'content' => $request->message
            ];
    
            $apiEndpoint = env('LOCAL_AI_ENDPOINT');
    
            // Retry logic with exponential backoff
            $response = retry(3, function () use ($apiEndpoint, $userMessage) {
                return Http::timeout(180)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->post($apiEndpoint, [
                        'messages' => [$userMessage], // Send as array of messages
                        'temperature' => env('AI_TEMPERATURE', 0.5),
                        'max_tokens' => env('AI_MAX_TOKENS', 100),
                        'top_p' => env('AI_TOP_P', 1),
                        'frequency_penalty' => env('AI_FREQUENCY_PENALTY', 0),
                        'presence_penalty' => env('AI_PRESENCE_PENALTY', 0),
                        'stop' => env('AI_STOP', ['\n']),
                        'stream' => false,
                        'model' => env('AI_MODEL_NAME', 'default-model'),
                    ]);
            }, 500);
    
            if ($response->successful()) {
                try {
                    $assistantMessage = $response->json('choices.0.message');
                    
                    if ($assistantMessage) {
                        return response()->json([
                            'status' => 'success',
                            'messages' => $assistantMessage
                        ]);
                    }

                    throw new \Exception('No message in response');
                    
                } catch (\Exception $e) {
                    Log::error('Response Processing Error', [
                        'message' => $e->getMessage(),
                        'response' => $response->json()
                    ]);
                    
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
            Log::error('Request Exception', [
                'message' => $e->getMessage(),
                'code' => $e->getCode()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'An error occurred while processing your request'
            ], 500);
        }
    }
}
