<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UsuarioController;

Route::middleware('auth')->group(function () {
    Route::post('/user', [UsuarioController::class, 'store']);
    Route::get('/user', [UsuarioController::class, 'show']);
    Route::put('/user', [UsuarioController::class, 'update']);
});