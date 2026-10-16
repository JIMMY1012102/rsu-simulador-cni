
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PreguntaController;

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/preguntas', [PreguntaController::class, 'index']);
    Route::get('/preguntas/{id}', [PreguntaController::class, 'show']);
    Route::post('/preguntas', [PreguntaController::class, 'store']);
    Route::put('/preguntas/{id}', [PreguntaController::class, 'update']);
    Route::delete('/preguntas/{id}', [PreguntaController::class, 'destroy']);
});
