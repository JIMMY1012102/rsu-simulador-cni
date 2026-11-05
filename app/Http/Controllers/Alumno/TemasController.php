<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Services\ProgresoAlumnoService;
use Illuminate\View\View;

/** "Temas de estudio": todos los temas activos, agrupados por área, con el avance del alumno. */
class TemasController extends Controller
{
    public function __invoke(ProgresoAlumnoService $progreso): View
    {
        return view('alumno.temas', [
            'areas' => $progreso->temas(auth('web')->user())->groupBy('area'),
        ]);
    }
}
