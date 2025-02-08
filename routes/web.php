<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\IndexController;

Route::get('/chat', [IndexController::class, 'index']);
Route::get('/chat/history', [IndexController::class, 'getHistory']);

require __DIR__.'/auth.php';
