<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Cloud\Dialogflow\V2\Client\SessionsClient;
use Google\Cloud\Dialogflow\V2\DetectIntentRequest;
use Google\Cloud\Dialogflow\V2\QueryInput;
use Google\Cloud\Dialogflow\V2\TextInput;
use Google\Cloud\Dialogflow\V2\EventInput;
use App\Models\ChatUser;
use App\Models\Review;
use App\Models\UnansweredQuestion;
use App\Models\Intent;

class ChatbotController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $sessionId = session()->getId();

        // Cek apakah user dengan session ini sudah ada
        $chatUser = ChatUser::updateOrCreate(
            ['session_id' => $sessionId],
            [
                'name' => $request->name,
                'email' => $request->email,
                'last_activity' => now()
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'user' => $chatUser
        ]);
    }

    public function checkUser()
    {
        $sessionId = session()->getId();
        $chatUser = ChatUser::where('session_id', $sessionId)->first();

        return response()->json([
            'registered' => $chatUser ? true : false,
            'user' => $chatUser
        ]);
    }
    public function send(Request $request)
    {
        try {
            $message = $request->message;
            $sessionId = session()->getId();

            // Update last activity
            ChatUser::where('session_id', $sessionId)->update([
                'last_activity' => now()
            ]);

            $sessionsClient = new SessionsClient([
                 'credentials' => storage_path('app/dialogflow/notarybot.json'),
            ]);

            $session = $sessionsClient->sessionName(
                env('DIALOGFLOW_PROJECT_ID'),
                $sessionId
            );

            // Create TextInput
            $textInput = new TextInput();
            $textInput->setText($message);
            $textInput->setLanguageCode('id');

            // Create QueryInput
            $queryInput = new QueryInput();
            $queryInput->setText($textInput);

            // Create DetectIntentRequest
            $detectIntentRequest = new DetectIntentRequest();
            $detectIntentRequest->setSession($session);
            $detectIntentRequest->setQueryInput($queryInput);

            // Send request to Dialogflow
            $response = $sessionsClient->detectIntent($detectIntentRequest);
            $queryResult = $response->getQueryResult();

            // Track intent usage
            $intentDisplayName = $queryResult->getIntent()->getDisplayName();
            if ($intentDisplayName) {
                Intent::where('display_name', $intentDisplayName)->increment('usage_count');
            }

            $sessionsClient->close();

            // Parse fulfillment messages
            $parsedPayload = [];
            foreach ($queryResult->getFulfillmentMessages() as $msg) {
                $parsedPayload[] = $this->parseMessage($msg);
            }

            return response()->json([
                'reply' => $queryResult->getFulfillmentText(),
                'payload' => $parsedPayload
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'reply' => 'Maaf, terjadi kesalahan. Silakan coba lagi.'
            ], 500);
        }
    }

    // untuk welcome event
    public function welcome()
    {
        try {
            $sessionId = session()->getId();

            $sessionsClient = new SessionsClient([
                'credentials' => storage_path('app/dialogflow/notarybot.json'),
            ]);

            $session = $sessionsClient->sessionName(
                env('DIALOGFLOW_PROJECT_ID'),
                $sessionId
            );

            // Create EventInput
            $eventInput = new EventInput();
            $eventInput->setName('WELCOME');
            $eventInput->setLanguageCode('id');

            // Create QueryInput
            $queryInput = new QueryInput();
            $queryInput->setEvent($eventInput);

            // Create DetectIntentRequest
            $detectIntentRequest = new DetectIntentRequest();
            $detectIntentRequest->setSession($session);
            $detectIntentRequest->setQueryInput($queryInput);

            // Send request to Dialogflow
            $response = $sessionsClient->detectIntent($detectIntentRequest);
            $queryResult = $response->getQueryResult();

            // Track intent usage for welcome event
            $intentDisplayName = $queryResult->getIntent()->getDisplayName();
            if ($intentDisplayName) {
                Intent::where('display_name', $intentDisplayName)->increment('usage_count');
            }

            $sessionsClient->close();

            // Parse fulfillment messages
            $parsedPayload = [];
            foreach ($queryResult->getFulfillmentMessages() as $msg) {
                $parsedPayload[] = $this->parseMessage($msg);
            }

            return response()->json($parsedPayload);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function parseMessage($message)
    {
        $parsed = [];

        // Parse text
        if ($message->getText()) {
            $textMessage = $message->getText();
            $parsed['type'] = 'text';
            
            // Get text array from Text message
            $texts = [];
            foreach ($textMessage->getText() as $text) {
                $texts[] = $text;
            }
            $parsed['text'] = $texts;
        }

        // Parse payload (for custom/rich content)
        if ($message->getPayload()) {
            $payload = $message->getPayload();
            $parsed['type'] = 'payload';
            
            // Convert protobuf Struct to array
            $payloadArray = json_decode($payload->serializeToJsonString(), true);
            $parsed['payload'] = $payloadArray;
        }

        return $parsed;
    }

    public function submitReview(Request $request)
    {
        $request->validate([
            'rating' => 'required|in:positive,negative',
            'comment' => 'nullable|string|max:500'
        ]);

        $sessionId = session()->getId();
        $chatUser = ChatUser::where('session_id', $sessionId)->first();

        if (!$chatUser) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        $review = Review::create([
            'chat_user_id' => $chatUser->id,
            'session_id' => $sessionId,
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih atas review Anda!',
            'review' => $review
        ]);
    }

    public function saveUnansweredQuestion(Request $request)
    {
        $request->validate([
            'question' => 'required|string',
            'bot_response' => 'nullable|string'
        ]);

        $sessionId = session()->getId();

        // Cari user berdasarkan session
        $chatUser = ChatUser::where('session_id', $sessionId)->first();

        $unansweredQuestion = UnansweredQuestion::create([
            'chat_user_id' => $chatUser ? $chatUser->id : null,
            'session_id' => $sessionId,
            'question' => $request->question,
            'bot_response' => $request->bot_response
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pertanyaan yang tidak terjawab telah disimpan',
            'unanswered_question' => $unansweredQuestion
        ]);
    }
}