<?php

use App\Http\Controllers\ChatController;
use App\Models\AiConversation;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::prefix('chat')->group(function () {
        Route::post('', [ChatController::class, 'store'])->name('chat.store');

        Route::prefix('{conversation}')->group(function () {
            Route::get('', function (AiConversation $conversation) {
                return Inertia::render('Chat/Show', [
                    'conversation' => $conversation->load('messages'),
                ]);
            })->name('chat.show');

            Route::post('message', [ChatController::class, 'storeMessage'])->name('chat-message.store');
        });
    });
    
    Route::get('/message/{message}/status', [ChatController::class, 'status'])->name('message.status');
});
