<?php

use App\Http\Controllers\Api\IntentoController;
use App\Http\Middleware\EnsureActiveUser;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web', EnsureActiveUser::class])->prefix('intentos')->group(function () {
    Route::post('/', [IntentoController::class, 'iniciar']);
    Route::get('/{id}', [IntentoController::class, 'mostrar'])
        ->whereNumber('id');
    Route::post('/{id}/respuestas', [IntentoController::class, 'guardarRespuesta'])
        ->whereNumber('id');
    Route::post('/{id}/finalizar', [IntentoController::class, 'finalizar'])
        ->whereNumber('id');
});
