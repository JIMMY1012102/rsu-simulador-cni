<?php

namespace App\Services\Alumno;

use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Indicadores reales del alumno autenticado. No introduce notas, temas ni
 * rachas de demostración; la información proviene de intentos y del banco.
 */
class DashboardEstudianteService
{
    public function obtener(Usuario $usuario, Collection $simulacros): array
    {
        $simulacroIds = $simulacros->pluck('id')->map(fn ($id) => (int) $id)->all();

        $conteos = DB::table('respuestas_estudiante as r')
            ->join('intentos as i', 'i.id', '=', 'r.intento_id')
            ->where('i.usuario_id', $usuario->id)
            ->whereNotNull('i.puntaje_total')
            ->whereNotNull('r.estado_respuesta')
            ->selectRaw('COUNT(*) as resueltas')
            ->selectRaw("COALESCE(SUM(CASE WHEN r.estado_respuesta = 'correcta' THEN 1 ELSE 0 END), 0) as correctas")
            ->first();

        $resueltas = (int) ($conteos->resueltas ?? 0);
        $correctas = (int) ($conteos->correctas ?? 0);
        $porcentajeAcierto = $resueltas > 0 ? (int) round(100 * $correctas / $resueltas) : 0;

        $completados = count($simulacroIds)
            ? DB::table('intentos')->where('usuario_id', $usuario->id)
                ->where('tipo', 'simulacro')->whereNotNull('puntaje_total')
                ->whereIn('simulacro_id', $simulacroIds)
                ->distinct()->count('simulacro_id')
            : 0;
        $progreso = count($simulacroIds)
            ? (int) min(100, round(100 * $completados / count($simulacroIds)))
            : 0;

        $fechas = DB::table('intentos')
            ->where('usuario_id', $usuario->id)
            ->whereNotNull('puntaje_total')->whereNotNull('fecha_fin')
            ->pluck('fecha_fin')
            ->map(fn ($valor) => Carbon::parse($valor)->toDateString())
            ->unique()->flip();

        $hoy = Carbon::today();
        $cursor = $hoy->copy();
        if (! $fechas->has($cursor->toDateString())) {
            $cursor->subDay();
        }
        $racha = 0;
        while ($racha < 365 && $fechas->has($cursor->toDateString())) {
            $racha++;
            $cursor->subDay();
        }

        $lunes = $hoy->copy()->startOfWeek();
        $dias = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];
        $semana = [];
        for ($n = 0; $n < 7; $n++) {
            $fecha = $lunes->copy()->addDays($n)->toDateString();
            $semana[] = [
                'dia' => $dias[$n],
                'fecha' => $fecha,
                'actividad' => $fechas->has($fecha),
                'futuro' => $lunes->copy()->addDays($n)->isAfter($hoy),
            ];
        }

        // Solo temas incluidos en asignaturas de simulacros publicados para su área.
        $asignaturaIds = count($simulacroIds)
            ? DB::table('simulacro_area')->whereIn('simulacro_id', $simulacroIds)
                ->distinct()->pluck('area_id')->all()
            : [];

        $temas = collect();
        if ($asignaturaIds) {
            $catalogo = DB::table('temas as t')
                ->join('areas as a', 'a.id', '=', 't.area_id')
                ->whereIn('t.area_id', $asignaturaIds)
                ->where('t.activo', true)->where('a.activo', true)
                ->select('t.id', 't.nombre', 'a.nombre as asignatura')
                ->orderBy('a.nombre')->orderBy('t.nombre')->limit(60)->get();

            $temaIds = $catalogo->pluck('id')->all();
            if ($temaIds) {
                $totales = DB::table('preguntas')->where('activo', true)
                    ->whereIn('tema_id', $temaIds)
                    ->select('tema_id')->selectRaw('COUNT(*) as total')
                    ->groupBy('tema_id')->pluck('total', 'tema_id');

                $respondidas = DB::table('respuestas_estudiante as r')
                    ->join('intentos as i', 'i.id', '=', 'r.intento_id')
                    ->join('preguntas as p', 'p.id', '=', 'r.pregunta_id')
                    ->where('i.usuario_id', $usuario->id)
                    ->whereNotNull('i.puntaje_total')
                    ->whereIn('p.tema_id', $temaIds)
                    ->select('p.tema_id')
                    ->selectRaw('COUNT(DISTINCT r.pregunta_id) as realizadas')
                    ->groupBy('p.tema_id')->pluck('realizadas', 'p.tema_id');

                $temas = $catalogo->map(function ($tema) use ($totales, $respondidas) {
                    $total = (int) ($totales[$tema->id] ?? 0);
                    $realizadas = min($total, (int) ($respondidas[$tema->id] ?? 0));
                    return (object) [
                        'id' => (int) $tema->id,
                        'nombre' => $tema->nombre,
                        'asignatura' => $tema->asignatura,
                        'realizadas' => $realizadas,
                        'total' => $total,
                        'avance' => $total ? (int) round(100 * $realizadas / $total) : 0,
                    ];
                })->filter(fn ($tema) => $tema->total > 0)->values();
            }
        }

        return [
            'resueltas' => $resueltas,
            'correctas' => $correctas,
            'porcentajeAcierto' => $porcentajeAcierto,
            'simulacrosCompletados' => $completados,
            'simulacrosDisponibles' => count($simulacroIds),
            'progreso' => $progreso,
            'racha' => $racha,
            'semana' => $semana,
            'temas' => $temas,
        ];
    }
}
