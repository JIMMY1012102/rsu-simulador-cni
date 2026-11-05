<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Services\ProgresoAlumnoService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Página de inicio del alumno: saludo, progreso, racha de estudio y temas.
 * Las cifras salen de ProgresoAlumnoService; los avisos de la campana, de AvisosAlumnoService.
 */
class InicioController extends Controller
{
    public function __invoke(ProgresoAlumnoService $progreso): View
    {
        /** @var Usuario $usuario */
        $usuario = auth('web')->user();

        return view('alumno.inicio', [
            'nombre' => Str::title(Str::before(trim($usuario->nombres).' ', ' ')),
            'carrera' => $usuario->carrera?->nombre,
            'progreso' => $progreso->progreso($usuario),
            'racha' => $progreso->racha($usuario),
            'temas' => $progreso->temas($usuario, config('alumno.temas_en_inicio')),
            'cita' => $this->citaDelDia(),
        ]);
    }

    /** Una cita distinta cada día, en orden. */
    private function citaDelDia(): ?array
    {
        $citas = config('alumno.citas', []);

        return $citas === [] ? null : $citas[Carbon::today()->dayOfYear % count($citas)];
    }
}
