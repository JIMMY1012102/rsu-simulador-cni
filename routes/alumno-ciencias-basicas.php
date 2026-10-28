<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\FeatureAlumnoAreas;
use App\Http\Middleware\SoloAreaCienciasBasicas;
use App\Livewire\Alumno\CienciasBasicas\Inicio;
use Illuminate\Support\Facades\Route;

// Panel del área B (Ciencias Básicas, Figma 301:1600). Módulo aislado: alumno.panel sigue siendo el panel principal.
Route::get('/alumno/ciencias-basicas', Inicio::class)
    ->middleware(['auth:web', EnsureActiveUser::class, FeatureAlumnoAreas::class, SoloAreaCienciasBasicas::class])
    ->name('alumno.ciencias-basicas');
