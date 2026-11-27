<?php

use App\Http\Controllers\Api\SimulacroController;
use App\Http\Middleware\EnsureActiveUser;
use Illuminate\Support\Facades\Route;

/*
 * Rutas del módulo de simulacros.
 * Sin prefijo propio: el líder carga este archivo dentro del grupo /api en la integración.
 */
Route::middleware(['auth:web', EnsureActiveUser::class])->group(function () {
    Route::get('simulacros', [SimulacroController::class, 'index'])->name('simulacros.index');
    Route::get('simulacros/{id}', [SimulacroController::class, 'show'])->whereNumber('id')->name('simulacros.show');
    Route::post('simulacros', [SimulacroController::class, 'store'])->name('simulacros.store');
    Route::put('simulacros/{id}', [SimulacroController::class, 'update'])->whereNumber('id')->name('simulacros.update');
});
