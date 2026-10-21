<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Livewire\Alumno\PanelAlumno;
use Illuminate\Support\Facades\Route;

Route::middleware(['web', 'auth:web', EnsureActiveUser::class])
    ->prefix('alumno')->name('alumno.')->group(function () {
        Route::get('/perfil', PanelAlumno::class)->name('perfil');
    });
