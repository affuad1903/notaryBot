<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatbotController;

Route::get('/chatbot', function () {
    return view('chatbot');
});

Route::post('/chatbot/register', [ChatbotController::class, 'register']);
Route::get('/chatbot/check-user', [ChatbotController::class, 'checkUser']);
Route::post('/chatbot/send', [ChatbotController::class, 'send']);
Route::get('/chatbot/welcome', [ChatbotController::class, 'welcome']);
Route::post('/chatbot/review', [ChatbotController::class, 'submitReview']);
Route::post('/chatbot/unanswered-question', [ChatbotController::class, 'saveUnansweredQuestion']);