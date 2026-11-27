<?php

namespace App\Services\Docente;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Datos de solo lectura de la sección del docente autenticado.
 * Reutiliza el aislamiento por sección de ResultadosSeccionService.
 */
class PanelCalificacionesData
{
    public function obtener(Usuario $docente): array
    {
        $service = app(ResultadosSeccionService::class);
        $alumnos = $service->alumnos($docente);

        if (! $service->tieneSeccionActiva($docente) || $alumnos->isEmpty()) {
            return ['alumnos' => $alumnos, 'areasDisponibles' => collect()];
        }

        $idsAlumnos = $alumnos->pluck('estudiante_id')->all();

        // Igual criterio que alumnos(): último intento calificado por estudiante.
        $intentos = DB::table('intentos')
            ->whereIn('usuario_id', $idsAlumnos)
            ->where('tipo', 'simulacro')
            ->whereIn('estado', ['finalizado', 'enviado_auto'])
            ->whereNotNull('puntaje_total')
            ->groupBy('usuario_id')
            ->select('usuario_id')
            ->selectRaw('MAX(id) AS intento_id')
            ->get()
            ->keyBy('usuario_id');

        $idsIntentos = $intentos->pluck('intento_id')->all();
        $porIntento = collect();
        $areasDisponibles = collect();

        if ($idsIntentos) {
            $puntajes = DB::table('resultados_area AS ra')
                ->join('areas AS a', 'a.id', '=', 'ra.area_id')
                ->whereIn('ra.intento_id', $idsIntentos)
                ->select('ra.intento_id', 'a.nombre AS area_nombre', 'ra.puntaje')
                ->orderBy('a.nombre')
                ->get();

            $areasDisponibles = $puntajes->pluck('area_nombre')->unique()->values();
            $porIntento = $puntajes->groupBy('intento_id');
        }

        foreach ($alumnos as $alumno) {
            $ultimo = $intentos->get($alumno->estudiante_id);
            $alumno->notas_area = $ultimo
                ? $porIntento->get($ultimo->intento_id, collect())
                    ->mapWithKeys(fn ($p) => [$p->area_nombre => (float) $p->puntaje])
                    ->all()
                : [];
        }

        return [
            'alumnos' => $alumnos,
            'areasDisponibles' => $areasDisponibles,
        ];
    }
}
