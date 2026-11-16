<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * El portal de Dafne corresponde al área E (Humanidades).
 * Nunca altera el área o carrera elegida por un estudiante.
 */
class SoloAreaHumanidades
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user('web');
        abort_unless($usuario && $usuario->activo && $usuario->rol === 'estudiante', 403);

        $carrera = $usuario->carrera()->with('areaPostulacion')->first();
        $area = $usuario->areaPostulacion()->first() ?? $carrera?->areaPostulacion;
        if (! $area) {
            return redirect()->route('alumno.elegir-area');
        }
        if (! $area->activo || ($carrera && (! $carrera->activo ||
            (int) $carrera->area_postulacion_id !== (int) $area->id))) {
            abort(403, 'Área o carrera de postulación no válida.');
        }
        if ($area->codigo !== 'E') {
            return redirect()->route('alumno.panel');
        }

        return $next($request);
    }
}
