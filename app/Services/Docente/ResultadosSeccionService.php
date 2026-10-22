<?php

namespace App\Services\Docente;

use App\Models\Usuario;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Consultas de solo lectura para la sección del docente autenticado.
 * La sección se obtiene siempre del usuario, nunca de un parámetro de la URL.
 */
class ResultadosSeccionService
{
    public function resumen(Usuario $docente): array
    {
        $docente->loadMissing('seccion');
        $seccion = $docente->seccion;

        $vacio = [
            'docente' => $docente,
            'seccion' => $seccion,
            'totalEstudiantes' => 0,
            'totalEvaluaciones' => 0,
            'totalSimulacros' => 0,
            'promedioSeccion' => null,
        ];

        if (! $this->tieneSeccionActiva($docente)) {
            return $vacio;
        }

        $totalEstudiantes = DB::table('usuarios')
            ->where('rol', 'estudiante')
            ->where('activo', true)
            ->where('seccion_id', $docente->seccion_id)
            ->count();

        $estadisticas = $this->calificados($docente)
            ->selectRaw('COUNT(*) as total_evaluaciones')
            ->selectRaw('COUNT(DISTINCT i.simulacro_id) as total_simulacros')
            ->selectRaw('AVG(i.puntaje_total) as promedio')
            ->first();

        return [
            'docente' => $docente,
            'seccion' => $seccion,
            'totalEstudiantes' => $totalEstudiantes,
            'totalEvaluaciones' => (int) $estadisticas->total_evaluaciones,
            'totalSimulacros' => (int) $estadisticas->total_simulacros,
            'promedioSeccion' => $estadisticas->promedio === null
                ? null
                : round((float) $estadisticas->promedio, 2),
        ];
    }

    public function resultados(Usuario $docente, int $limite = 50): Collection
    {
        if (! $this->tieneSeccionActiva($docente)) {
            return collect();
        }

        return $this->calificados($docente)
            ->select(
                'i.id as intento_id',
                'u.id as estudiante_id',
                'u.nombres',
                'u.apellidos',
                's.nombre as simulacro',
                'i.simulacro_id',
                'i.puntaje_total',
                'i.fecha_inicio',
                'i.estado'
            )
            ->orderByDesc('i.id')
            ->limit(max(1, min($limite, 200)))
            ->get();
    }

    private function tieneSeccionActiva(Usuario $docente): bool
    {
        $docente->loadMissing('seccion');

        return $docente->rol === 'docente'
            && (bool) $docente->activo
            && $docente->seccion_id !== null
            && $docente->seccion !== null
            && (bool) $docente->seccion->activo;
    }

    private function calificados(Usuario $docente): Builder
    {
        return DB::table('intentos as i')
            ->join('usuarios as u', 'i.usuario_id', '=', 'u.id')
            ->join('simulacros as s', 'i.simulacro_id', '=', 's.id')
            ->where('u.rol', 'estudiante')
            ->where('u.activo', true)
            ->where('u.seccion_id', $docente->seccion_id)
            ->where('i.tipo', 'simulacro')
            ->whereIn('i.estado', ['finalizado', 'enviado_auto'])
            ->whereNotNull('i.puntaje_total');
    }
}
