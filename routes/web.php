<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsureRegistrationByAuthorized;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PackageController;
use App\Http\Controllers\MoveController;
use App\Http\Controllers\PetController;
use App\Http\Controllers\LogController;

// Authentication routes
Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/register', 'register')->middleware(['auth', EnsureRegistrationByAuthorized::class]);
    Route::get('/logout', 'logout')->middleware('auth');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', function () {
        return 'Welcome to the dashboard!';
    })->name('dashboard');

    // User management routes (protected by auth middleware)
    Route::controller(UserController::class)->group(function () {
        Route::get('/users', 'index');
        Route::post('/user', 'store');
        Route::get('/user/{id}', 'show');
        Route::put('/user/{id}', 'update');
        Route::delete('/user/{id}', 'destroy');
    });

    // Invoice management routes
    Route::controller(InvoiceController::class)->group(function () {
        Route::get('/invoices', 'index');
        Route::get('/invoice/{id}', 'show');
        
        Route::middleware(EnsureRegistrationByAuthorized::class)->group(function () {
            Route::post('/invoice', 'store');
            Route::put('/invoice/{id}', 'update');
            Route::delete('/invoice/{id}', 'destroy');
        });
    });

    // Package management routes. Through these routes, residents can view their deliveries, 
    // and building managers and doormen can register, update, or delete a delivery.
    Route::controller(PackageController::class)->group(function () {
        Route::get('/packages', 'index');
        Route::get('/package/{id}', 'show');
        
        Route::middleware(EnsureRegistrationByAuthorized::class)->group(function () {
            Route::post('/package', 'store');
            Route::put('/package/{id}', 'update');
            Route::delete('/package/{id}', 'destroy');
        });
    });

    // Move management routes. Through these routes, residents can view their moves,
    // and building managers can register, update, or delete a move.
    Route::controller(MoveController::class)->group(function () {
        Route::get('/moves', 'index');
        Route::get('/moves/rejected', 'listRejectedMoves');
        Route::get('/move/{id}', 'show');
        Route::post('/move', 'store');
        Route::put('/move/{id}', 'update');
        Route::delete('/move/{id}', 'destroy');

        Route::middleware(EnsureRegistrationByAuthorized::class)->group(function () {
            Route::post('/move/{id}/decision', 'makeDecision');
            Route::get('/moves/pending', 'listPendingMoves');
        });
    });

    // Pet management routes. Through these routes, residents can register, update,
    // or delete their pets, and building managers can view all registered pets.
    Route::controller(PetController::class)->group(function () {
        Route::get('/pets', 'index');
        Route::get('/pet/{id}', 'show');
        Route::post('/pet', 'store');
        Route::put('/pet/{id}', 'update');
        Route::delete('/pet/{id}', 'destroy');
    });

    // Log management routes (issue #7). Apenas funcionários autorizados podem
    // consultar os logs críticos registrados localmente pela aplicação.
    Route::middleware(EnsureRegistrationByAuthorized::class)->group(function () {
        Route::get('/logs', [LogController::class, 'index']);
    });
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ping', function () {
    return 'pong';
});