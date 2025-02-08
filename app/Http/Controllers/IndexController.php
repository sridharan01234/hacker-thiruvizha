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
            // Initialize message history in session if it doesn't exist
            if (!Session::has('message_history')) {
                Session::put('message_history', []);
            }
    
            // Check if this is the first interaction
            if (!Session::has('chat_started')) {
                Session::put('chat_started', true);
                $initialMessage = [
                    'role' => 'assistant',
                    'content' => "Hello! I'm your Medical assistant. How can I help you today?"
                ];
                
                // Store initial message in history
                Session::put('message_history', [$initialMessage]);
    
                return response()->json([
                    'status' => 'success',
                    'data' => [
                        'choices' => [
                            [
                                'message' => [
                                    'content' => $initialMessage['content']
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
    
            // Get current message history
            $messageHistory = Session::get('message_history', []);
            
            // Add user's new message to history
            $userMessage = [
                'role' => 'user',
                'content' => $request->message
            ];
            $messageHistory[] = $userMessage;
    
            // Keep only last 4 messages
            $messageHistory = array_slice($messageHistory, -4);
    
            // Get the API endpoint from environment variable
            $apiEndpoint = env('LOCAL_AI_ENDPOINT', 'http://localhost:1234/v1/chat/completions');
    
            // Retry logic with exponential backoff
            $response = retry(3, function () use ($apiEndpoint, $messageHistory) {
                return Http::timeout(180)
                    ->withHeaders([
                        'Accept' => 'application/json',
                        'Content-Type' => 'application/json',
                    ])
                    ->post($apiEndpoint, [
                        'messages' => $messageHistory,
                        'context' => [
                            'session_id' => Session::getId(),
                        ],
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
                        // Add assistant's response to history
                        $messageHistory[] = [
                            'role' => 'assistant',
                            'content' => $assistantMessage['content']
                        ];
                        
                        // Keep only last 4 messages
                        $messageHistory = array_slice($messageHistory, -4);
                        
                        // Update session with new history
                        Session::put('message_history', $messageHistory);
    
                        return response()->json([
                            'status' => 'success',
                            'messages' => $assistantMessage
                        ]);
                    }
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
