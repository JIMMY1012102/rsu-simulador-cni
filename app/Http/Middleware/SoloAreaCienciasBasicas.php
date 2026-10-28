<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Acceso al panel de Ciencias Básicas (área B). Sin área elegida o con otra área,
 * se devuelve al flujo del panel principal, que es quien guarda y valida el perfil.
 */
class SoloAreaCienciasBasicas
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user('web');
        abort_unless($usuario && $usuario->activo && $usuario->rol === 'estudiante', 403);

        // Área guardada en el perfil o, en cuentas antiguas, la de su carrera (igual que alumno.panel).
        $carrera = $usuario->carrera()->with('areaPostulacion')->first();
        $area = $usuario->areaPostulacion()->first() ?? $carrera?->areaPostulacion;

        if (! $area) {
            return redirect()->route('alumno.elegir-area');
        }
        $coherente = $area->activo && (! $carrera || ($carrera->activo && (int) $carrera->area_postulacion_id === (int) $area->id));
        if ($area->codigo !== 'B' || ! $coherente) {
            return redirect()->route('alumno.panel');
        }

        return $next($request);
    }
}
