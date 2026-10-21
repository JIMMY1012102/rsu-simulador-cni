<?php

use App\Http\Controllers\ExamenController;
use App\Http\Controllers\SessionController;
use App\Http\Middleware\EnsureActiveUser;
use App\Livewire\Examen;
use App\Livewire\Panel;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('inicio');

Route::middleware('guest:web')->group(function () {
    Route::view('/login', 'auth.login')->name('login');
    Route::post('/login', [SessionController::class, 'store'])
        ->middleware('throttle:login')->name('login.store');
});

Route::post('/logout', [SessionController::class, 'destroy'])
    ->middleware('auth:web')->name('logout');

Route::get('/panel', Panel::class)
    ->middleware(['auth:web', EnsureActiveUser::class])->name('panel');
    Route::view('/examen-demo', 'examen-demo')
    ->middleware(['auth:web', EnsureActiveUser::class])
    ->name('examen.demo');

// Examen del alumno: iniciar o reanudar un simulacro y rendirlo.
Route::middleware(['auth:web', EnsureActiveUser::class])->group(function () {
    Route::post('/simulacros/{simulacro}/iniciar', [ExamenController::class, 'iniciar'])
        ->whereNumber('simulacro')->name('examen.iniciar');
    Route::get('/intentos/{intento}/examen', Examen::class)
        ->whereNumber('intento')->name('examen');
});

// Compatibilidad con los módulos existentes: JSON con sesión y CSRF.
// Se cargan dentro de web; no existe un segundo sistema de autenticación.
Route::prefix('api')->group(base_path('routes/api.php'));
