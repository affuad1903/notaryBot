<?php

namespace App\Http\Controllers;

use App\Models\Intent;
use App\Services\DialogflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IntentController extends Controller
{
    protected $dialogflowService;

    public function __construct(DialogflowService $dialogflowService)
    {
        $this->dialogflowService = $dialogflowService;
    }

    /**
     * Menampilkan daftar semua intents
     */
    public function index()
    {
        $intents = Intent::orderBy('display_name')->paginate(20);
        return view('intents.index', compact('intents'));
    }

    /**
     * Menampilkan form untuk membuat intent baru
     */
    public function create()
    {
        return view('intents.create');
    }

    /**
     * Menyimpan intent baru ke database dan Dialogflow
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:255|unique:intents,display_name',
            'description' => 'nullable|string',
            'priority' => 'nullable|integer',
            'training_phrases' => 'nullable|array',
            'training_phrases.*' => 'nullable|string',
            'events' => 'nullable|array',
            'events.*' => 'nullable|string',
            'responses' => 'required|array|min:1',
            'responses.*' => 'required|string',
            'input_contexts' => 'nullable|array',
            'input_contexts.*' => 'nullable|string',
            'output_contexts' => 'nullable|array',
            'output_contexts.*.name' => 'required|string',
            'output_contexts.*.lifespan' => 'nullable|integer|min:1|max:50',
            'webhook_enabled' => 'boolean',
            'action' => 'nullable|string|max:255',
        ]);

        // Validate: must have either training_phrases OR events
        if (empty($validated['training_phrases']) && empty($validated['events'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Intent harus memiliki minimal 1 training phrase ATAU 1 event.');
        }

        try {
            // Format training phrases untuk Dialogflow
            $trainingPhrases = [];
            if (!empty($validated['training_phrases'])) {
                $trainingPhrases = array_map(function($phrase) {
                    return ['parts' => [['text' => $phrase]]];
                }, array_filter($validated['training_phrases']));
            }

            // Format responses untuk Dialogflow
            $responses = [
                'text' => [
                    'text' => $validated['responses']
                ]
            ];

            // Simpan ke database
            $intent = Intent::create([
                'display_name' => $validated['display_name'],
                'description' => $validated['description'] ?? null,
                'priority' => $validated['priority'] ?? 500000,
                'training_phrases' => $trainingPhrases,
                'events' => $validated['events'] ?? [],
                'responses' => $responses,
                'input_contexts' => $validated['input_contexts'] ?? [],
                'output_contexts' => $validated['output_contexts'] ?? [],
                'webhook_enabled' => $validated['webhook_enabled'] ?? false,
                'action' => $validated['action'] ?? null,
                'synced' => false,
            ]);

            // Sync ke Dialogflow
            $dialogflowId = $this->dialogflowService->createIntent($intent);
            
            if ($dialogflowId) {
                $intent->update([
                    'dialogflow_id' => $dialogflowId,
                    'synced' => true,
                    'last_synced_at' => now(),
                ]);
            }

            return redirect()->route('intents.index')
                ->with('success', 'Intent berhasil dibuat dan disinkronkan dengan Dialogflow!');
        } catch (\Exception $e) {
            Log::error('Error creating intent: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal membuat intent: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan form untuk edit intent
     */
    public function edit(Intent $intent)
    {
        return view('intents.edit', compact('intent'));
    }

    /**
     * Update intent di database dan Dialogflow
     */
    public function update(Request $request, Intent $intent)
    {
        $validated = $request->validate([
            'display_name' => 'required|string|max:255|unique:intents,display_name,' . $intent->id,
            'description' => 'nullable|string',
            'priority' => 'nullable|integer',
            'training_phrases' => 'nullable|array',
            'training_phrases.*' => 'nullable|string',
            'events' => 'nullable|array',
            'events.*' => 'nullable|string',
            'responses' => 'required|array|min:1',
            'responses.*' => 'required|string',
            'input_contexts' => 'nullable|array',
            'input_contexts.*' => 'nullable|string',
            'output_contexts' => 'nullable|array',
            'output_contexts.*.name' => 'required|string',
            'output_contexts.*.lifespan' => 'nullable|integer|min:1|max:50',
            'webhook_enabled' => 'boolean',
            'action' => 'nullable|string|max:255',
        ]);

        // Validate: must have either training_phrases OR events
        if (empty($validated['training_phrases']) && empty($validated['events'])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Intent harus memiliki minimal 1 training phrase ATAU 1 event.');
        }

        try {
            // Format training phrases
            $trainingPhrases = [];
            if (!empty($validated['training_phrases'])) {
                $trainingPhrases = array_map(function($phrase) {
                    return ['parts' => [['text' => $phrase]]];
                }, array_filter($validated['training_phrases']));
            }

            // Format responses
            $responses = [
                'text' => [
                    'text' => $validated['responses']
                ]
            ];

            // Update database
            $intent->update([
                'display_name' => $validated['display_name'],
                'description' => $validated['description'] ?? null,
                'priority' => $validated['priority'] ?? 500000,
                'training_phrases' => $trainingPhrases,
                'events' => $validated['events'] ?? [],
                'responses' => $responses,
                'input_contexts' => $validated['input_contexts'] ?? [],
                'output_contexts' => $validated['output_contexts'] ?? [],
                'webhook_enabled' => $validated['webhook_enabled'] ?? false,
                'action' => $validated['action'] ?? null,
                'synced' => false,
            ]);

            // Sync ke Dialogflow
            $success = $this->dialogflowService->updateIntent($intent);
            
            if ($success) {
                $intent->update([
                    'synced' => true,
                    'last_synced_at' => now(),
                ]);
            }

            return redirect()->route('intents.index')
                ->with('success', 'Intent berhasil diupdate dan disinkronkan!');
        } catch (\Exception $e) {
            Log::error('Error updating intent: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal mengupdate intent: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus intent dari database dan Dialogflow
     */
    public function destroy(Intent $intent)
    {
        try {
            $displayName = $intent->display_name;
            $wasDeleted = false;
            
            // Hapus dari Dialogflow jika sudah sync
            if ($intent->dialogflow_id) {
                try {
                    $this->dialogflowService->deleteIntent($intent->dialogflow_id);
                    $wasDeleted = true;
                } catch (\Exception $e) {
                    // Jika error NOT_FOUND, berarti sudah dihapus di Dialogflow
                    if (strpos($e->getMessage(), 'NOT_FOUND') !== false) {
                        Log::warning("Intent already deleted in Dialogflow: {$displayName}");
                    } else {
                        throw $e; // Re-throw jika error lain
                    }
                }
            }

            // Hapus dari database
            $intent->delete();

            $message = $wasDeleted 
                ? 'Intent berhasil dihapus dari database dan Dialogflow!' 
                : 'Intent berhasil dihapus dari database!';

            return redirect()->route('intents.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error deleting intent: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menghapus intent: ' . $e->getMessage());
        }
    }

    /**
     * Sync ulang intent ke Dialogflow
     */
    public function sync(Intent $intent)
    {
        try {
            if ($intent->dialogflow_id) {
                // Update existing intent
                $success = $this->dialogflowService->updateIntent($intent);
            } else {
                // Create new intent
                $dialogflowId = $this->dialogflowService->createIntent($intent);
                $success = (bool) $dialogflowId;
                
                if ($dialogflowId) {
                    $intent->update(['dialogflow_id' => $dialogflowId]);
                }
            }

            if ($success) {
                $intent->update([
                    'synced' => true,
                    'last_synced_at' => now(),
                ]);
                return redirect()->back()->with('success', 'Intent berhasil disinkronkan!');
            } else {
                return redirect()->back()->with('error', 'Gagal sinkronisasi intent!');
            }
        } catch (\Exception $e) {
            Log::error('Error syncing intent: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal sinkronisasi: ' . $e->getMessage());
        }
    }

    /**
     * Import semua intents dari Dialogflow
     */
    public function import()
    {
        try {
            $result = $this->dialogflowService->importAllIntents();
            $imported = $result['imported'];
            $deleted = $result['deleted'];
            
            $message = "Berhasil mengimport {$imported} intents dari Dialogflow!";
            if ($deleted > 0) {
                $message .= " {$deleted} intent yang sudah dihapus di Dialogflow telah dibersihkan.";
            }
            
            return redirect()->route('intents.index')
                ->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error importing intents: ' . $e->getMessage());
            
            // Check if it's permission error
            if (strpos($e->getMessage(), 'PERMISSION_DENIED') !== false) {
                return redirect()->back()
                    ->with('error', 'Permission Denied! Service account belum memiliki akses Dialogflow API. Silakan baca dokumentasi setup: SETUP_DIALOGFLOW_PERMISSIONS.md');
            }
            
            return redirect()->back()
                ->with('error', 'Gagal mengimport intents: ' . $e->getMessage());
        }
    }
}
