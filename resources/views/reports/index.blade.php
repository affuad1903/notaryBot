<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Intent - NotaryBot</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .stat-card {
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .stat-card.info {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: white;
        }
        
        .stat-icon {
            font-size: 3rem;
            opacity: 0.8;
        }
        
        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .table {
            margin-bottom: 0;
        }
        
        .table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .badge-usage {
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .progress-bar-custom {
            height: 8px;
            border-radius: 10px;
        }
        
        .page-header {
            margin-bottom: 30px;
            padding: 20px 0;
        }
        
        .page-header h1 {
            color: #333;
            font-weight: 700;
        }
        
        .intent-description {
            color: #6c757d;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark mb-4">
        <div class="container-fluid">
            <span class="navbar-brand mb-0 h1">
                <i class="fas fa-chart-line"></i> Dashboard Laporan NotaryBot
            </span>
            <a href="/chatbot" class="btn btn-light">
                <i class="fas fa-comments"></i> Kembali ke Chatbot
            </a>
        </div>
    </nav>

    <div class="container-fluid px-4">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-analytics"></i> Laporan Intent & Statistik</h1>
            <p class="text-muted">Analisis intent yang paling sering ditanyakan oleh pengguna</p>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card primary">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2">Total Penggunaan Intent</h6>
                            <h2 class="mb-0">{{ number_format($totalIntentUsage) }}</h2>
                            <small>Pertanyaan dijawab</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-comments"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card success">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2">Total Pengguna</h6>
                            <h2 class="mb-0">{{ number_format($totalUsers) }}</h2>
                            <small>Pengguna terdaftar</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card info">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2">Rating Rata-rata</h6>
                            <h2 class="mb-0">{{ number_format($averageRating ?? 0, 1) }}</h2>
                            <small>dari {{ $totalReviews }} ulasan</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card warning">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-2">Pertanyaan Tidak Terjawab</h6>
                            <h2 class="mb-0">{{ number_format($totalUnansweredQuestions) }}</h2>
                            <small>Perlu perhatian</small>
                        </div>
                        <div class="stat-icon">
                            <i class="fas fa-question-circle"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="chart-container">
                    <h5 class="mb-4"><i class="fas fa-chart-bar"></i> 10 Intent Paling Sering Ditanyakan</h5>
                    <canvas id="intentChart"></canvas>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="chart-container">
                    <h5 class="mb-4"><i class="fas fa-chart-pie"></i> Distribusi Intent</h5>
                    <canvas id="intentPieChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Intent Table -->
        <div class="row">
            <div class="col-12">
                <div class="table-container">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5><i class="fas fa-table"></i> Daftar Lengkap Intent</h5>
                        <div>
                            <input type="text" id="searchInput" class="form-control" placeholder="Cari intent..." style="width: 300px;">
                        </div>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="intentTable">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">#</th>
                                    <th style="width: 25%;">Nama Intent</th>
                                    <th style="width: 35%;">Deskripsi</th>
                                    <th style="width: 15%;">Jumlah Penggunaan</th>
                                    <th style="width: 20%;">Persentase</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topIntents as $index => $intent)
                                    <tr>
                                        <td><strong>{{ $index + 1 }}</strong></td>
                                        <td>
                                            <strong>{{ $intent->display_name }}</strong>
                                        </td>
                                        <td>
                                            <span class="intent-description">
                                                {{ $intent->description ?? 'Tidak ada deskripsi' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-usage bg-primary">
                                                <i class="fas fa-fire"></i> {{ number_format($intent->usage_count) }}
                                            </span>
                                        </td>
                                        <td>
                                            @php
                                                $percentage = $totalIntentUsage > 0 ? ($intent->usage_count / $totalIntentUsage) * 100 : 0;
                                            @endphp
                                            <div class="d-flex align-items-center">
                                                <div class="progress flex-grow-1 me-2" style="height: 8px;">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: {{ $percentage }}%; background: linear-gradient(90deg, #667eea, #764ba2);" 
                                                         aria-valuenow="{{ $percentage }}" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100">
                                                    </div>
                                                </div>
                                                <small class="text-muted">{{ number_format($percentage, 1) }}%</small>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4">
                                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                            <p class="text-muted">Belum ada data penggunaan intent</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chart Scripts -->
    <script>
        // Data dari Laravel
        const chartLabels = @json($topTenIntents->pluck('display_name'));
        const chartData = @json($topTenIntents->pluck('usage_count'));
        
        // Bar Chart
        const ctx = document.getElementById('intentChart').getContext('2d');
        const intentChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Jumlah Penggunaan',
                    data: chartData,
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: 'rgba(102, 126, 234, 1)',
                    borderWidth: 2,
                    borderRadius: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: {
                            size: 14
                        },
                        bodyFont: {
                            size: 13
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
        
        // Pie Chart
        const ctxPie = document.getElementById('intentPieChart').getContext('2d');
        const intentPieChart = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: chartLabels,
                datasets: [{
                    data: chartData,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4facfe',
                        '#00f2fe',
                        '#fa709a',
                        '#fee140',
                        '#30cfd0',
                        '#330867'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12
                    }
                }
            }
        });
        
        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const tableRows = document.querySelectorAll('#intentTable tbody tr');
            
            tableRows.forEach(row => {
                const intentName = row.cells[1]?.textContent.toLowerCase() || '';
                const description = row.cells[2]?.textContent.toLowerCase() || '';
                
                if (intentName.includes(searchValue) || description.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
