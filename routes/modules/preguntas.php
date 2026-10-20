<?php

use App\Http\Controllers\Api\PreguntaController;
use App\Http\Middleware\EnsureActiveUser;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web', EnsureActiveUser::class])->group(function () {
    Route::get('/preguntas', [PreguntaController::class, 'index']);
    Route::get('/preguntas/{id}', [PreguntaController::class, 'show']);
    Route::post('/preguntas', [PreguntaController::class, 'store']);
    Route::put('/preguntas/{id}', [PreguntaController::class, 'update']);
    Route::delete('/preguntas/{id}', [PreguntaController::class, 'destroy']);
});
