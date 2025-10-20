<?php

use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/user', [UsuarioController::class, 'store']);
    Route::get('/user', [UsuarioController::class, 'show']);
    Route::put('/user', [UsuarioController::class, 'update']);
});