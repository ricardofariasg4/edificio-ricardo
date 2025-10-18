<?php

use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::post('/register', [AuthController::class, 'register']);
// Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user', [UsuarioController::class, 'store']);
    Route::get('/user', [UsuarioController::class, 'show']);
    // Route::put('/user', [UsuarioController::class, 'update']);
    Route::post('/logout', [UsuarioController::class, 'logout']);
});