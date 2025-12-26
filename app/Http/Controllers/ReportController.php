<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Intent;
use App\Models\ChatUser;
use App\Models\Review;
use App\Models\UnansweredQuestion;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Menampilkan halaman laporan intent
     */
    public function index()
    {
        // Ambil semua intent yang pernah digunakan, diurutkan berdasarkan usage_count
        $topIntents = Intent::where('usage_count', '>', 0)
            ->orderBy('usage_count', 'desc')
            ->get();
        
        // Statistik umum
        $totalUsers = ChatUser::count();
        $totalIntentUsage = Intent::sum('usage_count');
        $totalReviews = Review::count();
        $averageRating = Review::avg('rating');
        $totalUnansweredQuestions = UnansweredQuestion::count();
        
        // Ambil 10 intent teratas
        $topTenIntents = Intent::where('usage_count', '>', 0)
            ->orderBy('usage_count', 'desc')
            ->limit(10)
            ->get();
        
        // Data untuk chart (intent dengan usage > 0)
        $chartLabels = $topIntents->pluck('display_name')->toArray();
        $chartData = $topIntents->pluck('usage_count')->toArray();
        
        return view('reports.index', compact(
            'topIntents',
            'topTenIntents',
            'totalUsers',
            'totalIntentUsage',
            'totalReviews',
            'averageRating',
            'totalUnansweredQuestions',
            'chartLabels',
            'chartData'
        ));
    }
    
    /**
     * API untuk mendapatkan data intent dalam format JSON
     */
    public function getIntentData()
    {
        $intents = Intent::where('usage_count', '>', 0)
            ->orderBy('usage_count', 'desc')
            ->get(['display_name', 'usage_count', 'description']);
        
        return response()->json($intents);
    }
}
