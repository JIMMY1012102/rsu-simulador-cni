<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\Usuario;
use App\Models\Carrera;
use App\Services\Alumno\HistorialEstudianteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortalEstudianteController extends Controller
{
    private function datos(Request $request): array
    {
        /** @var Usuario|null $usuario */
        $usuario = $request->user('web');
        abort_unless($usuario && $usuario->activo && $usuario->rol === 'estudiante', 403);
        $carrera = $usuario->carrera()->with('areaPostulacion')->first();
        $area = $usuario->areaPostulacion()->first() ?? $carrera?->areaPostulacion;
        if (! $area) {
            return ['usuario' => $usuario, 'area' => null, 'carrera' => null];
        }
        abort_unless($area->activo, 403);
        abort_if($carrera && (! $carrera->activo || ! $carrera->areaPostulacion?->activo
            || (int) $carrera->area_postulacion_id !== (int) $area->id), 403);

        return compact('usuario', 'area', 'carrera');
    }

    public function perfil(Request $request): View|RedirectResponse
    {
        $datos = $this->datos($request);
        if (! $datos['area']) {
            return redirect()->route('alumno.elegir-area');
        }
        return view('alumno.perfil', $datos + [
            'paginaActiva' => 'perfil',
            'tituloPagina' => 'Mi perfil',
            'esVistaPrevia' => false,
            'carreras' => $datos['carrera'] ? collect() : Carrera::query()
                ->where('activo', true)->where('area_postulacion_id', $datos['area']->id)
                ->orderBy('nombre')->get(),
        ]);
    }

    public function progreso(Request $request, HistorialEstudianteService $servicio): View|RedirectResponse
    {
        $datos = $this->datos($request);
        if (! $datos['area']) {
            return redirect()->route('alumno.elegir-area');
        }
        return view('alumno.progreso', $datos + [
            'paginaActiva' => 'progreso',
            'tituloPagina' => 'Mi progreso',
            'esVistaPrevia' => false,
            'resumen' => $servicio->resumen($datos['usuario']),
        ]);
    }

    public function historial(Request $request, HistorialEstudianteService $servicio): View|RedirectResponse
    {
        $datos = $this->datos($request);
        if (! $datos['area']) {
            return redirect()->route('alumno.elegir-area');
        }
        return view('alumno.historial', $datos + [
            'paginaActiva' => 'historial',
            'tituloPagina' => 'Historial de intentos',
            'esVistaPrevia' => false,
        ] + $servicio->historial($datos['usuario'], $request));
    }
}
