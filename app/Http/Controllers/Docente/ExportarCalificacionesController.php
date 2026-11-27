<?php

namespace App\Http\Controllers\Docente;

use App\Http\Controllers\Controller;
use App\Services\Docente\PanelCalificacionesData;
use App\Services\Docente\ResultadosSeccionService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportarCalificacionesController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        $docente = $request->user('web');
        $service = app(ResultadosSeccionService::class);

        abort_unless($docente && $service->tieneSeccionActiva($docente), 403);

        ['alumnos' => $alumnos, 'areasDisponibles' => $areas] =
            app(PanelCalificacionesData::class)->obtener($docente);

        // CSV compatible con Excel en configuración regional española.
        // No es un archivo .xlsx ni se exportan alumnos de otras secciones.
        $nombreSeccion = preg_replace('/[^A-Za-z0-9_-]/', '', $docente->seccion->nombre);
        $nombre = 'calificaciones_5to_'.$nombreSeccion.'_'.date('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($alumnos, $areas) {
            echo "\xEF\xBB\xBF"; // UTF-8 BOM para Excel
            $fp = fopen('php://output', 'w');
            fputcsv($fp, array_merge(
                ['Estudiante', 'Último simulacro'],
                $areas->all(),
                ['Puntaje total', 'Estado', 'Fecha']
            ), ';', '"', '');

            foreach ($alumnos as $alumno) {
                $conNota = $alumno->puntaje_total !== null;
                $fila = [
                    $this->textoSeguro($alumno->apellidos.', '.$alumno->nombres),
                    $this->textoSeguro($alumno->simulacro ?? 'Sin simulacro calificado'),
                ];
                foreach ($areas as $area) {
                    $fila[] = isset($alumno->notas_area[$area])
                        ? number_format($alumno->notas_area[$area], 3, ',', '')
                        : '';
                }
                $fila[] = $conNota ? number_format((float) $alumno->puntaje_total, 3, ',', '') : '';
                $fila[] = $conNota ? 'Calificado' : 'Pendiente';
                $fila[] = $alumno->fecha_inicio
                    ? \Illuminate\Support\Carbon::parse($alumno->fecha_inicio)->format('d/m/Y')
                    : '';
                fputcsv($fp, $fila, ';', '"', '');
            }
            fclose($fp);
        }, $nombre, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'private, no-store',
        ]);
    }

    /** Evita que Excel interprete nombres de usuario como fórmulas. */
    private function textoSeguro(string $valor): string
    {
        return preg_match('/^[\s]*[=+\-@]/u', $valor) ? "'".$valor : $valor;
    }
}
