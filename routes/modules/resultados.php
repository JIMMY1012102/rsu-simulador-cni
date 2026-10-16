<?php

use App\Http\Controllers\Api\ResultadoController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('resultados')->group(function () {
    Route::get('/', [ResultadoController::class, 'listar']);
    Route::post('/calificar', [ResultadoController::class, 'calificar']);
    Route::get('/{intentoId}', [ResultadoController::class, 'mostrar'])
        ->whereNumber('intentoId');
});
