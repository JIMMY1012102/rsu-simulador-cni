<?php

use App\Http\Controllers\Alumno\BancoController;
use App\Http\Controllers\Alumno\HistorialController;
use App\Http\Controllers\Alumno\InicioController;
use App\Http\Controllers\Alumno\IntentosController;
use App\Http\Controllers\Alumno\PerfilController;
use App\Http\Controllers\Alumno\TemasController;
use App\Http\Controllers\ExamenController;
use App\Http\Controllers\SessionController;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureEstudiante;
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

// Examen del alumno: iniciar o reanudar un simulacro y rendirlo.
Route::middleware(['auth:web', EnsureActiveUser::class])->group(function () {
    Route::post('/simulacros/{simulacro}/iniciar', [ExamenController::class, 'iniciar'])
        ->whereNumber('simulacro')->name('examen.iniciar');
    Route::get('/intentos/{intento}/examen', Examen::class)
        ->whereNumber('intento')->name('examen');
});

// Portal del alumno (diseño "Simulador CNI · Derecho"). Solo para estudiantes.
// Los nombres de ruta coinciden con el menú de config/alumno.php.
Route::middleware(['auth:web', EnsureActiveUser::class, EnsureEstudiante::class])
    ->prefix('alumno')->name('alumno.')->group(function () {
        Route::get('/inicio', InicioController::class)->name('inicio');
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

// Compatibilidad con los módulos existentes: JSON con sesión y CSRF.
// Se cargan dentro de web; no existe un segundo sistema de autenticación.
Route::prefix('api')->group(base_path('routes/api.php'));
