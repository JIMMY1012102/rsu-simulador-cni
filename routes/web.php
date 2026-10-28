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
        Route::get('/temas/{tema}', [OrientacionController::class, 'tema'])->whereNumber('tema')->name('tema');
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

// Exportación de calificaciones limitada a la sección del docente autenticado.
Route::get('/docente/exportar', \App\Http\Controllers\Docente\ExportarCalificacionesController::class)
    ->middleware(['auth:web', EnsureActiveUser::class])
    ->name('docente.exportar');

// Vistas previas de diseño sin autenticación: exclusivas del entorno local.
if (app()->environment('local')) {
    Route::view('/docente-preview', 'livewire.docente.panel-docente');
    Route::redirect('/preview-docente', '/docente-preview');

    Route::get('/docente/calificaciones-preview', function () {
        return view('livewire.docente.calificaciones-salon', [
            'seccion' => null,
            'totalEstudiantes' => 0,
            'totalEvaluaciones' => 0,
            'promedioSeccion' => null,
            'alumnos' => collect(),
        ]);
    });

    // Vista previa SOLO local: no consulta MySQL ni requiere sesión de alumno.
    Route::get('/alumno/salud-preview', function () {
        $dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

        return view('alumno.panel', [
            'esVistaPrevia' => true,
            'usuario' => (object) ['nombres' => 'Estudiante', 'apellidos' => '(vista previa)'],
            'area' => (object) ['codigo' => 'A', 'nombre' => 'Ciencias de la Salud'],
            'carrera' => null,
            'carreras' => collect(),
            'simulacros' => collect(),
            'intentos' => collect(),
            'dashboard' => [
                'progreso' => 0,
                'resueltas' => 0,
                'correctas' => 0,
                'porcentajeAcierto' => 0,
                'simulacrosCompletados' => 0,
                'simulacrosDisponibles' => 0,
                'racha' => 0,
                'temas' => collect(),
                'semana' => collect($dias)->map(fn ($dia, $i) => [
                    'dia' => $dia,
                    'fecha' => now()->startOfWeek()->addDays($i)->toDateString(),
                    'actividad' => false,
                    'futuro' => false,
                ])->all(),
            ],
        ]);
    })->name('alumno.salud-preview');
}
