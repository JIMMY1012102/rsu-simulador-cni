<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;

/*
 * Rutas del módulo de autenticación (se cargan dentro del grupo /api).
 * El login limita a 10 intentos por minuto para frenar ataques de fuerza bruta.
 */
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('auth.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
});
