<?php

namespace App\Services;

use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Avisos de la campana del portal del alumno:
 * exámenes sin terminar y simulacros disponibles que todavía no rindió.
 */
class AvisosAlumnoService
{
    /** @return list<array{texto: string, detalle: string, enlace: string, tipo: string}> */
    public function para(Usuario $usuario): array
    {
        $avisos = [];
        $ahora = now();
        $areaId = $usuario->area_postulacion_id ?? $usuario->carrera?->area_postulacion_id;

        $enCurso = DB::table('intentos as i')
            ->leftJoin('simulacros as s', 's.id', '=', 'i.simulacro_id')
            ->where('i.usuario_id', $usuario->id)->where('i.estado', 'en_curso')
            ->where(fn ($q) => $q->whereNull('i.fecha_limite')->orWhere('i.fecha_limite', '>', $ahora))
            ->orderByDesc('i.id')
            ->get(['i.id', 'i.fecha_limite', 's.nombre']);

        foreach ($enCurso as $intento) {
            $avisos[] = [
                'tipo' => 'en_curso',
                'texto' => 'Tienes un examen sin terminar',
                'detalle' => ($intento->nombre ?? 'Práctica')
                    .($intento->fecha_limite ? ' · vence '.Carbon::parse($intento->fecha_limite)->diffForHumans() : ''),
                'enlace' => route('examen', $intento->id),
            ];
        }

        $nuevos = DB::table('simulacros as s')
            ->where('s.activo', true)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('area_postulacion_simulacro as aps')
                ->whereColumn('aps.simulacro_id', 's.id')->where('aps.area_postulacion_id', $areaId))
            ->where(fn ($q) => $q->whereNull('s.carrera_id')
                ->orWhere('s.carrera_id', $usuario->carrera_id ?? 0))
            ->where('s.fecha_inicio', '<=', $ahora)->where('s.fecha_fin', '>', $ahora)
            ->whereNotExists(fn ($q) => $q->from('intentos as i')
                ->whereColumn('i.simulacro_id', 's.id')->where('i.usuario_id', $usuario->id))
            ->orderBy('s.fecha_fin')
            ->get(['s.id', 's.nombre', 's.fecha_fin']);

        foreach ($nuevos as $simulacro) {
            $avisos[] = [
                'tipo' => 'nuevo',
                'texto' => 'Nuevo simulacro disponible',
                'detalle' => $simulacro->nombre.' · cierra '.Carbon::parse($simulacro->fecha_fin)->diffForHumans(),
                'enlace' => route('alumno.intentos'),
            ];
        }

        return $avisos;
    }
}
