<?php

use App\Http\Controllers\SessionController;
use App\Http\Controllers\Admin\BancoPreguntasController;
use App\Http\Controllers\Admin\CatalogoController;
use App\Http\Controllers\Admin\SimulacrosController;
use App\Http\Middleware\SoloAdministrador;
use App\Http\Controllers\ExamenController;
use App\Http\Controllers\Alumno\OrientacionController;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\RedirigirPanelEstudiante;
use App\Http\Middleware\FeatureAlumnoAreas;
use App\Livewire\Panel;
use App\Livewire\Examen;
use App\Livewire\Docente\PanelDocente;
use App\Livewire\Docente\CalificacionesSalon;
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
    ->middleware(['auth:web', EnsureActiveUser::class, RedirigirPanelEstudiante::class])->name('panel');

Route::middleware(['auth:web', EnsureActiveUser::class, FeatureAlumnoAreas::class])
    ->prefix('alumno')->name('alumno.')->group(function () {
        Route::get('/elegir-area', [OrientacionController::class, 'elegirArea'])->name('elegir-area');
        Route::post('/elegir-area', [OrientacionController::class, 'guardarArea'])->name('area.guardar');
        Route::get('/panel', [OrientacionController::class, 'panel'])->name('panel');
        Route::post('/carrera', [OrientacionController::class, 'guardarCarrera'])->name('carrera.guardar');
        Route::get('/resultados/{intento}', [OrientacionController::class, 'resultados'])
            ->whereNumber('intento')->name('resultados');
    });

// Herramientas internas de administración; nunca disponibles para estudiantes/docentes.
Route::middleware(['auth:web', EnsureActiveUser::class, SoloAdministrador::class])
    ->prefix('administracion')->name('admin.')->group(function () {
        Route::get('/', [CatalogoController::class, 'index'])->name('inicio');
        Route::post('/catalogo/asignaturas', [CatalogoController::class, 'asignatura'])->name('asignatura.guardar');
        Route::post('/catalogo/temas', [CatalogoController::class, 'tema'])->name('tema.guardar');
        Route::post('/catalogo/carreras', [CatalogoController::class, 'carrera'])->name('carrera.guardar');
        Route::get('/preguntas', [BancoPreguntasController::class, 'index'])->name('preguntas.index');
        Route::get('/preguntas/nueva', [BancoPreguntasController::class, 'create'])->name('preguntas.create');
        Route::post('/preguntas', [BancoPreguntasController::class, 'store'])->name('preguntas.store');
        Route::get('/preguntas/{pregunta}/editar', [BancoPreguntasController::class, 'edit'])->whereNumber('pregunta')->name('preguntas.edit');
        Route::put('/preguntas/{pregunta}', [BancoPreguntasController::class, 'update'])->whereNumber('pregunta')->name('preguntas.update');
        Route::delete('/preguntas/{pregunta}', [BancoPreguntasController::class, 'destroy'])->whereNumber('pregunta')->name('preguntas.destroy');
        Route::get('/simulacros', [SimulacrosController::class, 'index'])->name('simulacros.index');
        Route::get('/simulacros/nuevo', [SimulacrosController::class, 'create'])->name('simulacros.create');
        Route::post('/simulacros', [SimulacrosController::class, 'store'])->name('simulacros.store');
    });

// Pantalla de examen del alumno; reanuda intentos propios y valida tiempo en el servidor.
Route::middleware(['auth:web', EnsureActiveUser::class, FeatureAlumnoAreas::class])->group(function () {
    Route::post('/simulacros/{simulacro}/iniciar', [ExamenController::class, 'iniciar'])
        ->whereNumber('simulacro')->name('examen.iniciar');
    Route::get('/intentos/{intento}/examen', Examen::class)
        ->whereNumber('intento')->name('examen');
});

// Compatibilidad con los módulos existentes: JSON con sesión y CSRF.
Route::prefix('api')->group(base_path('routes/api.php'));

Route::get('/docente', PanelDocente::class)
    ->middleware(['auth:web', EnsureActiveUser::class])->name('docente.panel');

Route::get('/docente/calificaciones', CalificacionesSalon::class)
    ->middleware(['auth:web', EnsureActiveUser::class])->name('docente.calificaciones');
