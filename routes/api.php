<?php

use App\Http\Controllers\Api\ChatController;
use Illuminate\Support\Facades\Route;

// Route::middleware('auth')->group(function () {
    Route::prefix('chat')->group(function () {
        Route::post('', [ChatController::class, 'store'])->name('chat.store');

        Route::prefix('{conversation}')->group(function () {
            Route::post('message', [ChatController::class, 'storeMessage'])->name('chat-message.store');
        });
    });
    
    Route::get('/message/{message}/status', [ChatController::class, 'status'])->name('message.status');
// });
