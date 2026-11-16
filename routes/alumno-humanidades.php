<?php

use App\Http\Controllers\Alumno\BancoController;
use App\Http\Controllers\Alumno\HistorialController;
use App\Http\Controllers\Alumno\IntentosController;
use App\Http\Controllers\Alumno\PerfilController;
use App\Http\Controllers\Alumno\TemasController;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\FeatureAlumnoAreas;
use App\Http\Middleware\SoloAreaHumanidades;
use Illuminate\Support\Facades\Route;

// URL histórica de Humanidades: Inicio redirige al panel unificado.
// Las páginas secundarias de Dafne se conservan como módulos opcionales del área E.
Route::middleware(['auth:web', EnsureActiveUser::class, FeatureAlumnoAreas::class, SoloAreaHumanidades::class])
    ->prefix('alumno')->name('alumno.')->group(function () {
        Route::get('/inicio', fn () => redirect()->route('alumno.panel'))->name('inicio');
        Route::get('/intentos', IntentosController::class)->name('intentos');
        Route::get('/preguntas', BancoController::class)->name('preguntas');
        Route::get('/temas', TemasController::class)->name('temas');
        Route::get('/historial', [HistorialController::class, 'index'])->name('historial');
        Route::get('/historial/{intento}', [HistorialController::class, 'show'])
            ->whereNumber('intento')->name('historial.detalle');
        Route::get('/perfil', [PerfilController::class, 'show'])->name('perfil');
        Route::put('/perfil/datos', [PerfilController::class, 'actualizarDatos'])->name('perfil.datos');
        Route::put('/perfil/password', [PerfilController::class, 'actualizarPassword'])->name('perfil.password');
    });
