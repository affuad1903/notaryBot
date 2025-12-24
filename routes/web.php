<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\IntentController;

Route::get('/chatbot', function () {
    return view('chatbot');
});

Route::post('/chatbot/register', [ChatbotController::class, 'register']);
Route::get('/chatbot/check-user', [ChatbotController::class, 'checkUser']);
Route::post('/chatbot/send', [ChatbotController::class, 'send']);
Route::get('/chatbot/welcome', [ChatbotController::class, 'welcome']);
Route::post('/chatbot/review', [ChatbotController::class, 'submitReview']);
Route::post('/chatbot/unanswered-question', [ChatbotController::class, 'saveUnansweredQuestion']);

// Routes untuk manajemen intents
Route::prefix('intents')->name('intents.')->group(function () {
    Route::get('/', [IntentController::class, 'index'])->name('index');
    Route::get('/create', [IntentController::class, 'create'])->name('create');
    Route::post('/', [IntentController::class, 'store'])->name('store');
    Route::get('/{intent}/edit', [IntentController::class, 'edit'])->name('edit');
    Route::put('/{intent}', [IntentController::class, 'update'])->name('update');
    Route::delete('/{intent}', [IntentController::class, 'destroy'])->name('destroy');
    Route::post('/{intent}/sync', [IntentController::class, 'sync'])->name('sync');
    Route::post('/import', [IntentController::class, 'import'])->name('import');
});
