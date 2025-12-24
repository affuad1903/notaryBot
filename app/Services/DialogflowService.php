<?php

namespace App\Services;

use App\Models\Intent;
use Google\Cloud\Dialogflow\V2\Client\IntentsClient;
use Google\Cloud\Dialogflow\V2\Intent as DialogflowIntent;
use Google\Cloud\Dialogflow\V2\Intent\TrainingPhrase;
use Google\Cloud\Dialogflow\V2\Intent\TrainingPhrase\Part;
use Google\Cloud\Dialogflow\V2\Intent\Message;
use Google\Cloud\Dialogflow\V2\Intent\Message\Text;
use Google\Cloud\Dialogflow\V2\Context;
use Google\Cloud\Dialogflow\V2\CreateIntentRequest;
use Google\Cloud\Dialogflow\V2\UpdateIntentRequest;
use Google\Cloud\Dialogflow\V2\DeleteIntentRequest;
use Google\Cloud\Dialogflow\V2\GetIntentRequest;
use Google\Cloud\Dialogflow\V2\ListIntentsRequest;
use Google\Cloud\Dialogflow\V2\IntentView;
use Illuminate\Support\Facades\Log;

class DialogflowService
{
    protected $intentsClient;
    protected $projectId;
    protected $parentPath;

    public function __construct()
    {
        try {
            // Set path ke service account key
            $keyPath = storage_path('app/dialogflow/notarybot.json');
            
            if (!file_exists($keyPath)) {
                throw new \Exception('Dialogflow service account key tidak ditemukan di: ' . $keyPath);
            }

            putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $keyPath);
            
            $this->intentsClient = new IntentsClient();
            
            // Ambil project ID dari service account
            $keyData = json_decode(file_get_contents($keyPath), true);
            $this->projectId = $keyData['project_id'] ?? null;
            
            if (!$this->projectId) {
                throw new \Exception('Project ID tidak ditemukan dalam service account key');
            }
            
            $this->parentPath = $this->intentsClient->projectAgentName($this->projectId);
            
            Log::info('DialogflowService initialized successfully', [
                'project_id' => $this->projectId
            ]);
        } catch (\Exception $e) {
            Log::error('DialogflowService initialization error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Membuat intent baru di Dialogflow
     */
    public function createIntent(Intent $intent)
    {
        try {
            $dialogflowIntent = new DialogflowIntent();
            $dialogflowIntent->setDisplayName($intent->display_name);
            $dialogflowIntent->setPriority($intent->priority);

            // Set training phrases (optional jika ada events)
            if (!empty($intent->training_phrases)) {
                $trainingPhrases = [];
                foreach ($intent->training_phrases as $phrase) {
                    $part = new Part();
                    $part->setText($phrase['parts'][0]['text']);
                    
                    $trainingPhrase = new TrainingPhrase();
                    $trainingPhrase->setParts([$part]);
                    $trainingPhrases[] = $trainingPhrase;
                }
                $dialogflowIntent->setTrainingPhrases($trainingPhrases);
            }

            // Set events
            if (!empty($intent->events)) {
                $dialogflowIntent->setEvents(array_values(array_filter($intent->events)));
            }

            // Set input contexts
            if (!empty($intent->input_contexts)) {
                $inputContextNames = [];
                foreach (array_filter($intent->input_contexts) as $contextName) {
                    $inputContextNames[] = $this->intentsClient->contextName($this->projectId, '-', $contextName);
                }
                $dialogflowIntent->setInputContextNames($inputContextNames);
            }

            // Set output contexts
            if (!empty($intent->output_contexts)) {
                $outputContexts = [];
                foreach ($intent->output_contexts as $ctx) {
                    if (!empty($ctx['name'])) {
                        $context = new Context();
                        $contextPath = $this->intentsClient->contextName($this->projectId, '-', $ctx['name']);
                        $context->setName($contextPath);
                        $context->setLifespanCount($ctx['lifespan'] ?? 5);
                        $outputContexts[] = $context;
                    }
                }
                $dialogflowIntent->setOutputContexts($outputContexts);
            }

            // Set responses
            $textResponses = $intent->responses['text']['text'] ?? [];
            $text = new Text();
            $text->setText($textResponses);
            
            $message = new Message();
            $message->setText($text);
            $dialogflowIntent->setMessages([$message]);

            // Set webhook jika enabled
            if ($intent->webhook_enabled && $intent->action) {
                $dialogflowIntent->setAction($intent->action);
                $dialogflowIntent->setWebhookState(DialogflowIntent\WebhookState::WEBHOOK_STATE_ENABLED);
            }

            // Buat intent di Dialogflow
            $request = new CreateIntentRequest();
            $request->setParent($this->parentPath);
            $request->setIntent($dialogflowIntent);
            $response = $this->intentsClient->createIntent($request);
            
            // Extract ID dari path
            $intentPath = $response->getName();
            $intentId = basename($intentPath);
            
            Log::info('Intent created in Dialogflow', ['intent_id' => $intentId, 'name' => $intent->display_name]);
            
            return $intentId;
        } catch (\Exception $e) {
            Log::error('Error creating intent in Dialogflow: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update intent di Dialogflow
     */
    public function updateIntent(Intent $intent)
    {
        try {
            if (!$intent->dialogflow_id) {
                throw new \Exception('Intent tidak memiliki dialogflow_id');
            }

            $intentPath = $this->intentsClient->intentName($this->projectId, $intent->dialogflow_id);
            
            // Get existing intent
            $getRequest = new GetIntentRequest();
            $getRequest->setName($intentPath);
            $dialogflowIntent = $this->intentsClient->getIntent($getRequest);
            
            // Update fields
            $dialogflowIntent->setDisplayName($intent->display_name);
            $dialogflowIntent->setPriority($intent->priority);

            // Update training phrases (optional jika ada events)
            if (!empty($intent->training_phrases)) {
                $trainingPhrases = [];
                foreach ($intent->training_phrases as $phrase) {
                    $part = new Part();
                    $part->setText($phrase['parts'][0]['text']);
                    
                    $trainingPhrase = new TrainingPhrase();
                    $trainingPhrase->setParts([$part]);
                    $trainingPhrases[] = $trainingPhrase;
                }
                $dialogflowIntent->setTrainingPhrases($trainingPhrases);
            } else {
                $dialogflowIntent->setTrainingPhrases([]);
            }

            // Update events
            if (!empty($intent->events)) {
                $dialogflowIntent->setEvents(array_values(array_filter($intent->events)));
            } else {
                $dialogflowIntent->setEvents([]);
            }

            // Update input contexts
            if (!empty($intent->input_contexts)) {
                $inputContextNames = [];
                foreach (array_filter($intent->input_contexts) as $contextName) {
                    $inputContextNames[] = $this->intentsClient->contextName($this->projectId, '-', $contextName);
                }
                $dialogflowIntent->setInputContextNames($inputContextNames);
            } else {
                $dialogflowIntent->setInputContextNames([]);
            }

            // Update output contexts
            if (!empty($intent->output_contexts)) {
                $outputContexts = [];
                foreach ($intent->output_contexts as $ctx) {
                    if (!empty($ctx['name'])) {
                        $context = new Context();
                        $contextPath = $this->intentsClient->contextName($this->projectId, '-', $ctx['name']);
                        $context->setName($contextPath);
                        $context->setLifespanCount($ctx['lifespan'] ?? 5);
                        $outputContexts[] = $context;
                    }
                }
                $dialogflowIntent->setOutputContexts($outputContexts);
            } else {
                $dialogflowIntent->setOutputContexts([]);
            }

            // Update responses
            $textResponses = $intent->responses['text']['text'] ?? [];
            $text = new Text();
            $text->setText($textResponses);
            
            $message = new Message();
            $message->setText($text);
            $dialogflowIntent->setMessages([$message]);

            // Update webhook
            if ($intent->webhook_enabled && $intent->action) {
                $dialogflowIntent->setAction($intent->action);
                $dialogflowIntent->setWebhookState(DialogflowIntent\WebhookState::WEBHOOK_STATE_ENABLED);
            } else {
                $dialogflowIntent->setWebhookState(DialogflowIntent\WebhookState::WEBHOOK_STATE_UNSPECIFIED);
            }

            // Update intent
            $updateRequest = new UpdateIntentRequest();
            $updateRequest->setIntent($dialogflowIntent);
            $this->intentsClient->updateIntent($updateRequest);
            
            Log::info('Intent updated in Dialogflow', ['intent_id' => $intent->dialogflow_id, 'name' => $intent->display_name]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error updating intent in Dialogflow: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Hapus intent dari Dialogflow
     */
    public function deleteIntent($dialogflowId)
    {
        try {
            $intentPath = $this->intentsClient->intentName($this->projectId, $dialogflowId);
            $deleteRequest = new DeleteIntentRequest();
            $deleteRequest->setName($intentPath);
            $this->intentsClient->deleteIntent($deleteRequest);
            
            Log::info('Intent deleted from Dialogflow', ['intent_id' => $dialogflowId]);
            
            return true;
        } catch (\Exception $e) {
            // Check if error is NOT_FOUND - intent already deleted in Dialogflow
            if (strpos($e->getMessage(), 'NOT_FOUND') !== false || 
                strpos($e->getMessage(), 'NotFoundException') !== false) {
                Log::warning('Intent not found in Dialogflow (already deleted)', ['intent_id' => $dialogflowId]);
                return true; // Consider it successful since intent doesn't exist anyway
            }
            
            Log::error('Error deleting intent from Dialogflow: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Import semua intents dari Dialogflow ke database
     */
    public function importAllIntents()
    {
        try {
            $listRequest = new ListIntentsRequest();
            $listRequest->setParent($this->parentPath);
            // Set IntentView to FULL untuk mendapatkan semua detail termasuk training phrases
            $listRequest->setIntentView(IntentView::INTENT_VIEW_FULL);
            $intents = $this->intentsClient->listIntents($listRequest);
            $count = 0;

            foreach ($intents as $dialogflowIntent) {
                // Parse training phrases
                $trainingPhrases = [];
                $phrasesIterator = $dialogflowIntent->getTrainingPhrases();
                
                if ($phrasesIterator) {
                    foreach ($phrasesIterator as $phrase) {
                        $parts = [];
                        $partsIterator = $phrase->getParts();
                        
                        if ($partsIterator) {
                            foreach ($partsIterator as $part) {
                                $text = $part->getText();
                                if ($text) {
                                    $parts[] = ['text' => $text];
                                }
                            }
                        }
                        
                        if (!empty($parts)) {
                            $trainingPhrases[] = ['parts' => $parts];
                        }
                    }
                }

                // Parse responses
                $responseTexts = [];
                $messagesIterator = $dialogflowIntent->getMessages();
                
                if ($messagesIterator) {
                    foreach ($messagesIterator as $message) {
                        if ($message->getText()) {
                            $textIterator = $message->getText()->getText();
                            if ($textIterator) {
                                foreach ($textIterator as $text) {
                                    if ($text) {
                                        $responseTexts[] = $text;
                                    }
                                }
                            }
                            break;
                        }
                    }
                }

                // Parse events
                $events = [];
                $eventsIterator = $dialogflowIntent->getEvents();
                if ($eventsIterator) {
                    foreach ($eventsIterator as $event) {
                        if ($event) {
                            $events[] = $event;
                        }
                    }
                }

                // Parse input contexts
                $inputContexts = [];
                $inputContextsIterator = $dialogflowIntent->getInputContextNames();
                if ($inputContextsIterator) {
                    foreach ($inputContextsIterator as $contextName) {
                        // Extract context name from path (projects/x/agent/sessions/-/contexts/NAME)
                        $inputContexts[] = basename($contextName);
                    }
                }

                // Parse output contexts
                $outputContexts = [];
                $outputContextsIterator = $dialogflowIntent->getOutputContexts();
                if ($outputContextsIterator) {
                    foreach ($outputContextsIterator as $context) {
                        $outputContexts[] = [
                            'name' => basename($context->getName()),
                            'lifespan' => $context->getLifespanCount()
                        ];
                    }
                }

                // Extract intent ID
                $intentPath = $dialogflowIntent->getName();
                $intentId = basename($intentPath);

                // Log untuk debugging
                Log::info("Importing intent: {$dialogflowIntent->getDisplayName()}", [
                    'training_phrases_count' => count($trainingPhrases),
                    'events_count' => count($events),
                    'responses_count' => count($responseTexts),
                ]);

                // Simpan atau update di database
                Intent::updateOrCreate(
                    ['dialogflow_id' => $intentId],
                    [
                        'display_name' => $dialogflowIntent->getDisplayName(),
                        'priority' => $dialogflowIntent->getPriority(),
                        'is_fallback' => $dialogflowIntent->getIsFallback(),
                        'training_phrases' => $trainingPhrases,
                        'events' => $events,
                        'responses' => ['text' => ['text' => $responseTexts]],
                        'input_contexts' => $inputContexts,
                        'output_contexts' => $outputContexts,
                        'action' => $dialogflowIntent->getAction(),
                        'webhook_enabled' => $dialogflowIntent->getWebhookState() == DialogflowIntent\WebhookState::WEBHOOK_STATE_ENABLED,
                        'synced' => true,
                        'last_synced_at' => now(),
                    ]
                );

                $count++;
            }

            Log::info("Imported {$count} intents from Dialogflow");
            
            // Sync deletion: hapus intent di database yang tidak ada di Dialogflow
            $dialogflowIds = [];
            foreach ($intents as $dialogflowIntent) {
                $intentPath = $dialogflowIntent->getName();
                $dialogflowIds[] = basename($intentPath);
            }
            
            $deleted = Intent::whereNotNull('dialogflow_id')
                ->whereNotIn('dialogflow_id', $dialogflowIds)
                ->count();
            
            if ($deleted > 0) {
                Intent::whereNotNull('dialogflow_id')
                    ->whereNotIn('dialogflow_id', $dialogflowIds)
                    ->delete();
                Log::info("Deleted {$deleted} intents that no longer exist in Dialogflow");
            }
            
            return [
                'imported' => $count,
                'deleted' => $deleted
            ];
        } catch (\Exception $e) {
            Log::error('Error importing intents from Dialogflow: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Destructor untuk cleanup client
     */
    public function __destruct()
    {
        if ($this->intentsClient) {
            $this->intentsClient->close();
        }
    }
}
