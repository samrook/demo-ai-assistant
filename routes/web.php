<?php

use App\Http\Controllers\ChatController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route::middleware('auth')->group(function () {
    Route::get('chat/{chat}', [ChatController::class, 'view'])->name('chat.show');
// });
