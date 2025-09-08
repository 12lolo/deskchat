<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MessageController;

Route::get('/health', fn() => response()->json(['ok'=>true, 'time'=>now()->toIso8601String()]));
Route::get('/messages', [MessageController::class, 'index']);
Route::post('/messages', [MessageController::class, 'store'])->middleware('throttle:chat');
