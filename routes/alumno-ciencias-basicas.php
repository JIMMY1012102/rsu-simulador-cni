<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\FeatureAlumnoAreas;
use App\Http\Middleware\SoloAreaCienciasBasicas;
use Illuminate\Support\Facades\Route;

// Compatibilidad: la antigua URL de Diego redirige a la única plantilla del alumno.
Route::get('/alumno/ciencias-basicas', fn () => redirect()->route('alumno.panel'))
    ->middleware(['auth:web', EnsureActiveUser::class, FeatureAlumnoAreas::class, SoloAreaCienciasBasicas::class])
    ->name('alumno.ciencias-basicas');
