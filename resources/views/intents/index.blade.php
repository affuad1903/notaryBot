<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Intents - Notary Bot</title>
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
        }
        .badge-synced {
            background-color: #28a745;
        }
        .badge-not-synced {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-chat-dots"></i> Manajemen Intents Dialogflow</h1>
            <div>
                <form action="{{ route('intents.import') }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-info">
                        <i class="bi bi-download"></i> Import dari Dialogflow
                    </button>
                </form>
                <a href="{{ route('intents.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Tambah Intent Baru
                </a>
            </div>
        </div>

        @if(DB::table('intents')->count() == 0)
            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle"></i> <strong>Pertama kali menggunakan?</strong>
                <p class="mb-0">
                    1. Pastikan service account sudah memiliki permission (baca <strong>SETUP_DIALOGFLOW_PERMISSIONS.md</strong>)<br>
                    2. Klik <strong>"Import dari Dialogflow"</strong> untuk mengimport intents yang sudah ada<br>
                    3. Atau klik <strong>"Tambah Intent Baru"</strong> untuk membuat intent dari awal
                </p>
            </div>
        @endif

        @if(DB::table('intents')->count() > 0)
            <div class="alert alert-light border" role="alert">
                <small>
                    <i class="bi bi-arrow-repeat"></i> <strong>Tips:</strong> 
                    Klik "Import dari Dialogflow" untuk sinkronisasi penuh. 
                    Sistem akan otomatis menghapus intent yang sudah dihapus di Dialogflow.
                </small>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                @if(strpos(session('error'), 'Permission') !== false)
                    <hr>
                    <p class="mb-0 small">
                        <strong>Solusi:</strong> Berikan role <code>Dialogflow API Admin</code> ke service account di Google Cloud Console. 
                        <a href="https://console.cloud.google.com/iam-admin/iam" target="_blank" class="alert-link">
                            Buka IAM Console <i class="bi bi-box-arrow-up-right"></i>
                        </a>
                    </p>
                @endif
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>Nama Intent</th>
                        <th>Deskripsi</th>
                        <th>Training Phrases</th>
                        <th>Responses</th>
                        <th>Status Sync</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($intents as $intent)
                        <tr>
                            <td><strong>{{ $intent->display_name }}</strong></td>
                            <td>{{ Str::limit($intent->description ?? '-', 50) }}</td>
                            <td>
                                @if(count($intent->training_phrases ?? []) > 0)
                                    <span class="badge bg-info">
                                        {{ count($intent->training_phrases) }} phrases
                                    </span>
                                    <small class="d-block text-muted mt-1" title="{{ collect($intent->training_phrases)->take(3)->map(fn($p) => $p['parts'][0]['text'] ?? '')->implode(', ') }}">
                                        {{ Str::limit(collect($intent->training_phrases)->take(2)->map(fn($p) => $p['parts'][0]['text'] ?? '')->implode(', '), 40) }}
                                    </small>
                                @else
                                    <span class="badge bg-warning text-dark">
                                        <i class="bi bi-exclamation-triangle"></i> Tidak ada phrases
                                    </span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    {{ count($intent->responses['text']['text'] ?? []) }} responses
                                </span>
                            </td>
                            <td>
                                @if($intent->synced)
                                    <span class="badge badge-synced">
                                        <i class="bi bi-check-circle"></i> Synced
                                    </span>
                                    <small class="d-block text-muted">
                                        {{ $intent->last_synced_at?->diffForHumans() }}
                                    </small>
                                @else
                                    <span class="badge badge-not-synced">
                                        <i class="bi bi-x-circle"></i> Not Synced
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="{{ route('intents.edit', $intent) }}" class="btn btn-sm btn-warning">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    
                                    <form action="{{ route('intents.sync', $intent) }}" method="POST" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-info" title="Sync ke Dialogflow">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </button>
                                    </form>
                                    
                                    <form action="{{ route('intents.destroy', $intent) }}" method="POST" 
                                          onsubmit="return confirm('Yakin ingin menghapus intent ini?')" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                                <p class="mt-2">Belum ada intent. Silakan tambah intent baru atau import dari Dialogflow.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $intents->links() }}
        </div>

        <div class="mt-4">
            <a href="/chatbot" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali ke Chatbot
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
