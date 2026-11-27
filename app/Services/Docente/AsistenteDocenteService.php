<?php
namespace App\Services\Docente;

use App\Models\Usuario;
use Illuminate\Support\Facades\Http;
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
    $modelo = config('services.gemini.model');
    $url = config('services.gemini.url');

    if (empty($apiKey) || empty($modelo) || empty($url)) {
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

    // Alinear el límite de PHP con el timeout HTTP de Laravel (90 s).
    set_time_limit(120);

    $maxOutputTokens = 2048;
    $endpoint = rtrim($url, '/') . '/models/' . $modelo . ':generateContent';

    // En los modelos con razonamiento, los tokens internos también consumen
    // maxOutputTokens. Reintentar una vez con más margen evita mostrar texto
    // truncado si Gemini vuelve a terminar con MAX_TOKENS.
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
                    'temperature' => 0.7,
                ],
            ]);

        if ($respuesta->failed()) {
            report(new RuntimeException(
                'Gemini generateContent respondió HTTP ' . $respuesta->status()
                . ': ' . mb_substr($respuesta->body(), 0, 1000)
            ));
            throw new RuntimeException(
                'Gemini no pudo procesar la consulta. Código HTTP: '
                . $respuesta->status()
            );
        }

        $finishReason = $respuesta->json('candidates.0.finishReason');
        if ($finishReason !== 'MAX_TOKENS' || $maxOutputTokens >= 4096) {
            break;
        }

        $maxOutputTokens = 4096;
    } while (true);

    if ($respuesta->json('promptFeedback.blockReason') !== null) {
        throw new RuntimeException(
            'Gemini bloqueó la consulta por sus filtros de seguridad.'
        );
    }

    $partes = $respuesta->json(
        'candidates.0.content.parts',
        []
    );

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
