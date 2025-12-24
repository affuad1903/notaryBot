<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Intent - Notary Bot</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            max-width: 900px;
        }
        .phrase-item, .response-item {
            margin-bottom: 10px;
        }
        .btn-remove {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4"><i class="bi bi-pencil"></i> Edit Intent: {{ $intent->display_name }}</h1>

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('intents.update', $intent) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="display_name" class="form-label">Nama Intent <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="display_name" name="display_name" 
                       value="{{ old('display_name', $intent->display_name) }}" required 
                       placeholder="Contoh: greeting.hello">
                <small class="text-muted">Nama unik untuk intent ini</small>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Deskripsi</label>
                <textarea class="form-control" id="description" name="description" 
                          rows="2" placeholder="Deskripsi singkat tentang intent ini">{{ old('description', $intent->description) }}</textarea>
            </div>

            <div class="mb-3">
                <label for="priority" class="form-label">Priority</label>
                <input type="number" class="form-control" id="priority" name="priority" 
                       value="{{ old('priority', $intent->priority) }}" placeholder="500000">
                <small class="text-muted">Default: 500000</small>
            </div>

            <div class="mb-4">
                <label class="form-label">Training Phrases</label>
                <small class="text-muted d-block mb-2">
                    <i class="bi bi-info-circle"></i> Kosongkan jika menggunakan Event
                </small>
                <div id="training-phrases-container">
                    @php
                        $trainingPhrases = old('training_phrases');
                        if (!$trainingPhrases) {
                            $trainingPhrases = array_map(function($phrase) {
                                return $phrase['parts'][0]['text'] ?? '';
                            }, $intent->training_phrases ?? []);
                        }
                    @endphp
                    
                    @if(empty($trainingPhrases))
                        <div class="phrase-item input-group">
                            <input type="text" class="form-control" name="training_phrases[]" 
                                   placeholder="Contoh: Halo">
                            <button type="button" class="btn btn-remove remove-phrase">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    @else
                        @foreach($trainingPhrases as $phrase)
                            <div class="phrase-item input-group">
                                <input type="text" class="form-control" name="training_phrases[]" 
                                       value="{{ $phrase }}" placeholder="Contoh: Halo">
                                <button type="button" class="btn btn-remove remove-phrase">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                        @endforeach
                    @endif
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-phrase">
                    <i class="bi bi-plus"></i> Tambah Phrase
                </button>
                <small class="d-block text-muted mt-1">Minimal 1 training phrase atau 1 event diperlukan</small>
            </div>

            <div class="mb-4">
                <label class="form-label">Events</label>
                <small class="text-muted d-block mb-2">
                    <i class="bi bi-info-circle"></i> Event names untuk trigger intent (contoh: WELCOME, CUSTOM_EVENT)
                </small>
                <div id="events-container">
                    @php
                        $events = old('events', $intent->events ?? []);
                    @endphp
                    
                    @foreach($events as $event)
                        <div class="event-item input-group mb-2">
                            <input type="text" class="form-control" name="events[]" 
                                   value="{{ $event }}" placeholder="Contoh: WELCOME">
                            <button type="button" class="btn btn-remove remove-event">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="add-event">
                    <i class="bi bi-plus"></i> Tambah Event
                </button>
            </div>

            <div class="mb-4">
                <label class="form-label">Input Contexts</label>
                <small class="text-muted d-block mb-2">
                    Context yang harus aktif agar intent ini bisa dipicu
                </small>
                <div id="input-contexts-container">
                    @php
                        $inputContexts = old('input_contexts', $intent->input_contexts ?? []);
                    @endphp
                    
                    @foreach($inputContexts as $context)
                        <div class="input-context-item input-group mb-2">
                            <input type="text" class="form-control" name="input_contexts[]" 
                                   value="{{ $context }}" placeholder="Contoh: awaiting-name">
                            <button type="button" class="btn btn-remove remove-input-context">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="add-input-context">
                    <i class="bi bi-plus"></i> Tambah Input Context
                </button>
            </div>

            <div class="mb-4">
                <label class="form-label">Output Contexts</label>
                <small class="text-muted d-block mb-2">
                    Context yang akan diset setelah intent ini dipicu
                </small>
                <div id="output-contexts-container">
                    @php
                        $outputContexts = old('output_contexts', $intent->output_contexts ?? []);
                    @endphp
                    
                    @foreach($outputContexts as $index => $context)
                        <div class="output-context-item mb-2">
                            <div class="row">
                                <div class="col-8">
                                    <input type="text" class="form-control" name="output_contexts[{{ $index }}][name]" 
                                           value="{{ $context['name'] ?? '' }}" placeholder="Context name" required>
                                </div>
                                <div class="col-3">
                                    <input type="number" class="form-control" name="output_contexts[{{ $index }}][lifespan]" 
                                           value="{{ $context['lifespan'] ?? 5 }}" placeholder="Lifespan" min="1" max="50">
                                </div>
                                <div class="col-1">
                                    <button type="button" class="btn btn-remove remove-output-context">
                                        <i class="bi bi-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="add-output-context">
                    <i class="bi bi-plus"></i> Tambah Output Context
                </button>
                <small class="d-block text-muted mt-1">Lifespan: jumlah turn context tetap aktif (default: 5)</small>
            </div>

            <div class="mb-4">
                <label class="form-label">Responses <span class="text-danger">*</span></label>
                <div id="responses-container">
                    @php
                        $responses = old('responses', $intent->responses['text']['text'] ?? []);
                    @endphp
                    
                    @foreach($responses as $response)
                        <div class="response-item input-group">
                            <textarea class="form-control" name="responses[]" rows="2" 
                                      placeholder="Contoh: Halo! Ada yang bisa saya bantu?" required>{{ $response }}</textarea>
                            <button type="button" class="btn btn-remove remove-response">
                                <i class="bi bi-x"></i>
                            </button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-response">
                    <i class="bi bi-plus"></i> Tambah Response
                </button>
                <small class="d-block text-muted mt-1">Minimal 1 response diperlukan</small>
            </div>

            <div class="mb-3">
                <label for="action" class="form-label">Action Name</label>
                <input type="text" class="form-control" id="action" name="action" 
                       value="{{ old('action', $intent->action) }}" placeholder="Contoh: greeting.action">
                <small class="text-muted">Optional: nama action untuk webhook</small>
            </div>

            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="webhook_enabled" 
                       name="webhook_enabled" value="1" 
                       {{ old('webhook_enabled', $intent->webhook_enabled) ? 'checked' : '' }}>
                <label class="form-check-label" for="webhook_enabled">
                    Aktifkan Webhook
                </label>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save"></i> Update Intent
                </button>
                <a href="{{ route('intents.index') }}" class="btn btn-secondary">
                    <i class="bi bi-x-circle"></i> Batal
                </a>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add Training Phrase
        document.getElementById('add-phrase').addEventListener('click', function() {
            const container = document.getElementById('training-phrases-container');
            const newPhrase = document.createElement('div');
            newPhrase.className = 'phrase-item input-group';
            newPhrase.innerHTML = `
                <input type="text" class="form-control" name="training_phrases[]" 
                       placeholder="Contoh: Halo">
                <button type="button" class="btn btn-remove remove-phrase">
                    <i class="bi bi-x"></i>
                </button>
            `;
            container.appendChild(newPhrase);
        });

        // Add Event
        document.getElementById('add-event').addEventListener('click', function() {
            const container = document.getElementById('events-container');
            const newEvent = document.createElement('div');
            newEvent.className = 'event-item input-group mb-2';
            newEvent.innerHTML = `
                <input type="text" class="form-control" name="events[]" 
                       placeholder="Contoh: WELCOME">
                <button type="button" class="btn btn-remove remove-event">
                    <i class="bi bi-x"></i>
                </button>
            `;
            container.appendChild(newEvent);
        });

        // Add Input Context
        document.getElementById('add-input-context').addEventListener('click', function() {
            const container = document.getElementById('input-contexts-container');
            const newContext = document.createElement('div');
            newContext.className = 'input-context-item input-group mb-2';
            newContext.innerHTML = `
                <input type="text" class="form-control" name="input_contexts[]" 
                       placeholder="Contoh: awaiting-name">
                <button type="button" class="btn btn-remove remove-input-context">
                    <i class="bi bi-x"></i>
                </button>
            `;
            container.appendChild(newContext);
        });

        // Add Output Context
        let outputContextIndex = {{ count($outputContexts ?? []) }};
        document.getElementById('add-output-context').addEventListener('click', function() {
            const container = document.getElementById('output-contexts-container');
            const newContext = document.createElement('div');
            newContext.className = 'output-context-item mb-2';
            newContext.innerHTML = `
                <div class="row">
                    <div class="col-8">
                        <input type="text" class="form-control" name="output_contexts[${outputContextIndex}][name]" 
                               placeholder="Context name" required>
                    </div>
                    <div class="col-3">
                        <input type="number" class="form-control" name="output_contexts[${outputContextIndex}][lifespan]" 
                               value="5" placeholder="Lifespan" min="1" max="50">
                    </div>
                    <div class="col-1">
                        <button type="button" class="btn btn-remove remove-output-context">
                            <i class="bi bi-x"></i>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(newContext);
            outputContextIndex++;
        });

        // Add Response
        document.getElementById('add-response').addEventListener('click', function() {
            const container = document.getElementById('responses-container');
            const newResponse = document.createElement('div');
            newResponse.className = 'response-item input-group';
            newResponse.innerHTML = `
                <textarea class="form-control" name="responses[]" rows="2" 
                          placeholder="Contoh: Halo! Ada yang bisa saya bantu?" required></textarea>
                <button type="button" class="btn btn-remove remove-response">
                    <i class="bi bi-x"></i>
                </button>
            `;
            container.appendChild(newResponse);
        });

        // Remove Training Phrase
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-phrase')) {
                e.target.closest('.phrase-item').remove();
            }
        });

        // Remove Event
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-event')) {
                e.target.closest('.event-item').remove();
            }
        });

        // Remove Input Context
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-input-context')) {
                e.target.closest('.input-context-item').remove();
            }
        });

        // Remove Output Context
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-output-context')) {
                e.target.closest('.output-context-item').remove();
            }
        });

        // Remove Response
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-response')) {
                const container = document.getElementById('responses-container');
                if (container.children.length > 1) {
                    e.target.closest('.response-item').remove();
                } else {
                    alert('Minimal 1 response diperlukan');
                }
            }
        });
    </script>
</body>
</html>
