<?php

use App\Helpers\CanRegister;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureRegistrationByAuthorized;

Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/register', 'register')->middleware('auth', EnsureRegistrationByAuthorized::class);
    Route::get('/logout', 'logout')->middleware('auth');
});

Route::middleware('auth')->group(function () {
    Route::controller(UserController::class)->group(function () {
        Route::get('/users', 'index');
        Route::post('/user', 'store')->middleware(CanRegister::class);
        Route::get('/user/{id}', 'show');
        Route::put('/user/{id}', 'update');
        Route::delete('/user/{id}', 'destroy');
    });
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ping', function () {
    return 'pong';
});