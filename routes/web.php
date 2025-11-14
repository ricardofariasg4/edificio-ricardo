<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UsuarioController;
use App\Http\Middleware\GaranteCadastroPorAutorizado;

Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::get('/logout', 'logout')->middleware('auth');
});

Route::middleware(['auth', GaranteCadastroPorAutorizado::class])->group(function () {
    Route::controller(UsuarioController::class)->group(function () {
        Route::get('/users', 'index');
        Route::post('/user', 'store');
        Route::get('/user', 'show');
        Route::put('/user', 'update');
        Route::delete('/user', 'destroy');
    });
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ping', function () {
    return 'pong';
});