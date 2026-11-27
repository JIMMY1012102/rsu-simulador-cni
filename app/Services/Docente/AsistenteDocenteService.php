<?php

namespace App\Services\Docente;

use App\Models\Usuario;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use InvalidArgumentException;

class AsistenteDocenteService
{
    public function consultarParaDocente(
        string $pregunta,
        Usuario $docente,
        array $historial = []
    ): string {
        $pregunta = trim($pregunta);

        if ($pregunta === '' || strlen($pregunta) > 2000) {
            throw new InvalidArgumentException(
                'La consulta debe tener entre 1 y 2000 caracteres.'
            );
        }

        $resultados = app(ResultadosSeccionService::class);
        $contexto = ['datos_disponibles' => false];

        if ($resultados->tieneSeccionActiva($docente)) {
            $resumen = $resultados->resumen($docente);
            $datosPanel = app(PanelCalificacionesData::class)->obtener($docente);

            // Agrupar notas por área sin enviar datos personales.
            $areas = [];
            foreach ($datosPanel['alumnos'] as $alumno) {
                foreach ($alumno->notas_area ?? [] as $nombre => $nota) {
                    if (is_numeric($nota)) {
                        $areas[$nombre][] = (float) $nota;
                    }
                }
            }

            $resumenAreas = [];
            foreach ($areas as $nombre => $notas) {
                $resumenAreas[] = [
                    'area' => $nombre,
                    'promedio' => round(array_sum($notas) / count($notas), 2),
                    'estudiantes_con_resultado' => count($notas),
                ];
            }

            $contexto = [
                'datos_disponibles' => true,
                'total_estudiantes' => $resumen['totalEstudiantes'],
                'total_evaluaciones' => $resumen['totalEvaluaciones'],
                'total_simulacros' => $resumen['totalSimulacros'],
                'promedio_seccion' => $resumen['promedioSeccion'],
                'resultados_por_area' => $resumenAreas,
            ];
        }

        return $this->consultar($pregunta, $contexto, $historial);
    }

    public function consultar(
        string $pregunta,
        array $contexto = [],
        array $historial = []
    ): string {
        $apiKey = config('services.gemini.key');
        $modeloPrincipal = config('services.gemini.model');
        $url = config('services.gemini.url');

        if (empty($apiKey) || empty($modeloPrincipal) || empty($url)) {
            throw new RuntimeException(
                'La configuración de Gemini está incompleta.'
            );
        }

        $instrucciones = <<<'PROMPT'
Eres el asistente IA pedagógico de Refuerzo CNI y especialista en educación,
evaluación formativa y reforzamiento académico escolar. Ayudas a planificar
estrategias, analizar simulacros, detectar áreas que requieren apoyo, crear
actividades y ejercicios diferenciados y mejorar el rendimiento.

Reglas:
- Utiliza únicamente los datos proporcionados.
- Nunca inventes resultados, notas, porcentajes, tendencias ni estadísticas.
- Si faltan datos suficientes, dilo claramente y limita el análisis a
  recomendaciones pedagógicas generales.
- Propón acciones concretas y distingue datos observados de sugerencias.
- Utiliza lenguaje claro y profesional.
- No solicites datos personales de los estudiantes.
- Distingue los promedios de los resultados individuales.
PROMPT;

        $datos = json_encode(
            $contexto,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        $entrada = "Datos académicos disponibles para esta sección (pueden estar vacíos):\n"
            . ($datos ?: '{}')
            . "\n\nNueva consulta del docente:\n"
            . trim($pregunta);

        $contents = [];
        foreach (array_slice($historial, -12) as $turno) {
            if (! is_array($turno)
                || ! in_array($turno['role'] ?? null, ['user', 'model'], true)
                || ! is_string($turno['text'] ?? null)
                || trim($turno['text']) === '') {
                continue;
            }

            $contents[] = [
                'role' => $turno['role'],
                'parts' => [['text' => mb_substr($turno['text'], 0, 4000)]],
            ];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $entrada]]];

        // Mantener margen para el timeout HTTP de cada intento.
        set_time_limit(300);

        $modelosRespaldo = config('services.gemini.fallback_models', []);
        if (is_string($modelosRespaldo)) {
            $modelosRespaldo = explode(',', $modelosRespaldo);
        }

        $modelos = array_values(array_unique(array_filter(array_map(
            static fn ($nombre) => trim((string) $nombre),
            array_merge([$modeloPrincipal], (array) $modelosRespaldo)
        ))));
        $modelos = array_values(array_filter(
            $modelos,
            static fn ($nombre) => $nombre !== ''
        ));

        $respuesta = null;
        $ultimoError = null;
        $maxOutputTokens = 2048;

        foreach ($modelos as $indice => $modelo) {
            $maxOutputTokens = 2048;
            $endpoint = rtrim($url, '/') . '/models/' . $modelo . ':generateContent';

            try {
                // Un MAX_TOKENS puede deberse al presupuesto de razonamiento.
                // Se conserva el reintento de 2048 a 4096 en el mismo modelo.
                do {
                    $respuesta = Http::withHeaders(['x-goog-api-key' => $apiKey])
                        ->acceptJson()
                        ->timeout(90)
                        ->connectTimeout(10)
                        ->post($endpoint, [
                            'systemInstruction' => ['parts' => [['text' => $instrucciones]]],
                            'contents' => $contents,
                            'generationConfig' => [
                                'maxOutputTokens' => $maxOutputTokens,
                            ],
                        ]);

                    $finishReason = $respuesta->json('candidates.0.finishReason');
                    if ($finishReason !== 'MAX_TOKENS' || $maxOutputTokens >= 4096) {
                        break;
                    }

                    $maxOutputTokens = 4096;
                } while (true);
            } catch (ConnectionException $e) {
                $ultimoError = new RuntimeException(
                    'No se pudo establecer conexión con Gemini. Último modelo: ' . $modelo,
                    0,
                    $e
                );

                Log::warning('Davis: error de conexión con Gemini; se evaluará el respaldo.', [
                    'model' => $modelo,
                    'has_next_model' => isset($modelos[$indice + 1]),
                ]);

                if (isset($modelos[$indice + 1])) {
                    continue;
                }

                throw $ultimoError;
            }

            if ($respuesta->failed()) {
                $status = $respuesta->status();
                $mensajeError = (string) $respuesta->json('error.message', '');
                $mensajeNormalizado = mb_strtolower($mensajeError);

                $errorTransitorio = in_array($status, [408, 429, 500, 502, 503, 504], true);
                $modeloNoEncontrado = $status === 404
                    && (
                        str_contains($mensajeNormalizado, 'model')
                        || str_contains($mensajeNormalizado, 'not found')
                    );

                $ultimoError = new RuntimeException(
                    'Gemini no pudo procesar la consulta. Código HTTP: ' . $status
                );

                if (($errorTransitorio || $modeloNoEncontrado)
                    && isset($modelos[$indice + 1])) {
                    Log::warning('Davis: modelo Gemini no disponible; se probará el siguiente.', [
                        'model' => $modelo,
                        'http_status' => $status,
                        'next_model' => $modelos[$indice + 1],
                    ]);
                    continue;
                }

                report(new RuntimeException(
                    'Gemini generateContent falló. Modelo: ' . $modelo
                    . ', HTTP: ' . $status
                    . ', detalle: ' . mb_substr($mensajeError, 0, 500)
                ));

                throw $ultimoError;
            }

            Log::info('Davis: respuesta generada por Gemini.', [
                'model' => $modelo,
                'used_fallback' => $indice > 0,
            ]);
            $ultimoError = null;
            break;
        }

        if ($respuesta === null || $ultimoError !== null) {
            throw $ultimoError ?? new RuntimeException(
                'No hay modelos Gemini configurados para responder.'
            );
        }

        if ($respuesta->json('promptFeedback.blockReason') !== null) {
            throw new RuntimeException(
                'Gemini bloqueó la consulta por sus filtros de seguridad.'
            );
        }

        $partes = $respuesta->json('candidates.0.content.parts', []);
        $contenido = '';

        foreach ($partes as $parte) {
            if (isset($parte['text'])) {
                $contenido .= $parte['text'];
            }
        }

        if (trim($contenido) === '') {
            throw new RuntimeException(
                'Gemini no generó una respuesta de texto.'
            );
        }

        if ($respuesta->json('candidates.0.finishReason') === 'MAX_TOKENS') {
            throw new RuntimeException(
                'Gemini no pudo completar la respuesta dentro del límite de tokens. '
                . 'Vuelve a formular la consulta de manera más breve.'
            );
        }

        return trim($contenido);
    }
}
