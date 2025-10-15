<?php

use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::post('/user', [UsuarioController::class, 'store']);