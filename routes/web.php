<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\IndexController;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::any('/chat', [IndexController::class, 'index']);

require __DIR__.'/auth.php';
