<?php

namespace App\Services\Alumno;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Historial de simulacros del estudiante autenticado.
 * Sin niveles inventados: el esquema actual no define umbrales de admisión.
 */
class HistorialAlumnoService
{
    public function obtener(Usuario $usuario): array
    {
        $filas = DB::table('intentos as i')
            ->leftJoin('simulacros as s', 's.id', '=', 'i.simulacro_id')
            ->where('i.usuario_id', $usuario->id)
            ->where('i.tipo', 'simulacro')
            ->orderBy('i.fecha_inicio')
            ->orderBy('i.id')
            ->get([
                'i.id', 'i.fecha_inicio', 'i.estado', 'i.puntaje_total',
                'i.total_preguntas', 's.nombre as nombre_simulacro',
                's.carrera_id as carrera_simulacro', 's.puntaje_correcta',
            ]);

        $registros = [];
        $calificados = [];
        $anterior = null;

        foreach ($filas as $fila) {
            $completado = in_array($fila->estado, ['finalizado', 'enviado_auto'], true)
                && $fila->puntaje_total !== null;
            $maximo = $fila->puntaje_correcta !== null
                ? (float) $fila->total_preguntas * (float) $fila->puntaje_correcta
                : null;
            $puntaje = $completado ? (float) $fila->puntaje_total : null;
            $porcentaje = $completado && $maximo > 0 ? $puntaje / $maximo * 100 : null;

            $evolucion = null;
            $unidadEvolucion = null;
            if ($porcentaje !== null && $anterior !== null) {
                if (abs($maximo - $anterior['maximo']) < 0.00001) {
                    $evolucion = round($puntaje - $anterior['puntaje'], 2);
                    $unidadEvolucion = 'pts';
                } else {
                    $evolucion = round($porcentaje - $anterior['porcentaje'], 1);
                    $unidadEvolucion = 'pp';
                }
            }

            $registro = [
                'id' => (int) $fila->id,
                'fecha' => Carbon::parse($fila->fecha_inicio)->format('d/m/Y'),
                'fecha_orden' => (string) $fila->fecha_inicio,
                'tipo' => $fila->carrera_simulacro === null ? 'Simulacro general' : 'Simulacro por carrera',
                'nombre' => $fila->nombre_simulacro ?? 'Simulacro',
                'estado' => $completado ? 'calificado' : $fila->estado,
                'puntaje' => $puntaje,
                'maximo' => $maximo,
                'porcentaje' => $porcentaje,
                'nivel' => $completado ? 'sin-nivel' : 'en-curso',
                'evolucion' => $evolucion,
                'unidad_evolucion' => $unidadEvolucion,
            ];
            $registros[] = $registro;

            if ($porcentaje !== null) {
                $calificados[] = $registro;
                $anterior = $registro;
            }
        }

        // Comparar simulacros con escalas diferentes por porcentaje del máximo.
        $mejor = null;
        foreach ($calificados as $registro) {
            if ($mejor === null || $registro['porcentaje'] > $mejor['porcentaje']) {
                $mejor = $registro;
            }
        }
        $ultimo = count($calificados) ? $calificados[array_key_last($calificados)] : null;

        $promedio = null;
        if ($calificados) {
            $maximos = array_column($calificados, 'maximo');
            $igualEscala = count(array_unique(array_map(
                fn ($maximo) => number_format($maximo, 3, '.', ''),
                $maximos
            ))) === 1;

            if ($igualEscala) {
                $promedio = [
                    'puntaje' => round(array_sum(array_column($calificados, 'puntaje')) / count($calificados), 2),
                    'maximo' => $maximos[0],
                    'porcentaje' => null,
                ];
            } else {
                $promedio = [
                    'puntaje' => null,
                    'maximo' => null,
                    'porcentaje' => round(array_sum(array_column($calificados, 'porcentaje')) / count($calificados), 1),
                ];
            }
        }

        return [
            'total' => count($registros),
            'calificados' => count($calificados),
            'mejor' => $mejor,
            'ultimo' => $ultimo,
            'promedio' => $promedio,
            'registros' => array_reverse($registros),
        ];
    }
}
