<?php

namespace App\Livewire\Docente;

use App\Services\Docente\AsistenteDocenteService;
use App\Services\Docente\PanelCalificacionesData;
use App\Services\Docente\ResultadosSeccionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Throwable;

class PanelDocente extends Component
{
    public string $pregunta = '';

    public ?string $respuesta = null;

    public ?string $errorAsistente = null;

    public array $mensajes = [];

    public bool $asistenteAbierto = false;

    public function enviarPregunta(): void
    {
        $this->validate([
            'pregunta' => 'required|string|min:3|max:2000',
        ]);

        $docente = Auth::guard('web')->user();

        abort_unless(
            $docente && $docente->activo && $docente->rol === 'docente',
            403
        );

        $clave = 'asistente-docente:' . $docente->id;

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            $this->errorAsistente =
                'Has realizado varias consultas. Inténtalo nuevamente en un minuto.';
            return;
        }

        RateLimiter::hit($clave, 60);

        $this->errorAsistente = null;
        $this->respuesta = null;

        try {
            $claveConversacion = 'asistente_docente.historial.' . $docente->id;
            $historial = session()->get($claveConversacion, []);
            $pregunta = trim($this->pregunta);
            $this->respuesta = app(AsistenteDocenteService::class)
                ->consultarParaDocente($pregunta, $docente, $historial);

            $historial[] = ['role' => 'user', 'text' => $pregunta];
            $historial[] = ['role' => 'model', 'text' => $this->respuesta];
            $historial = array_slice($historial, -12);
            session()->put($claveConversacion, $historial);
            $this->mensajes = $historial;

            $this->pregunta = '';
        } catch (Throwable $e) {
            report($e);

            $this->errorAsistente = match (true) {
                str_contains($e->getMessage(), 'configuración de Gemini') =>
                    'El asistente no está configurado correctamente. Contacta al administrador.',
                str_contains($e->getMessage(), 'Código HTTP: 429') =>
                    'Gemini recibió demasiadas consultas. Espera un momento e inténtalo de nuevo.',
                str_contains($e->getMessage(), 'Código HTTP: 400'),
                str_contains($e->getMessage(), 'Código HTTP: 403') =>
                    'Gemini rechazó la consulta. Verifica la configuración de acceso y vuelve a intentarlo.',
                $e instanceof \Illuminate\Http\Client\ConnectionException =>
                    'No se pudo conectar con Gemini. Revisa tu conexión e inténtalo nuevamente.',
                default =>
                    'Gemini no pudo responder en este momento. Inténtalo nuevamente en unos instantes.',
            };
        }
    }

    public function nuevaConversacion(): void
    {
        $docente = Auth::guard('web')->user();
        abort_unless($docente && $docente->rol === 'docente' && $docente->activo, 403);

        session()->forget('asistente_docente.historial.' . $docente->id);
        $this->mensajes = [];
        $this->pregunta = '';
        $this->respuesta = null;
        $this->errorAsistente = null;
    }

    public function abrirAsistente(): void
    {
        $docente = Auth::guard('web')->user();
        abort_unless($docente && $docente->rol === 'docente' && $docente->activo, 403);

        $this->asistenteAbierto = true;
    }

    public function cerrarAsistente(): void
    {
        $this->asistenteAbierto = false;
    }

    public function usarSugerencia(string $sugerencia): void
    {
        $this->pregunta = mb_substr($sugerencia, 0, 2000);
    }

    public function render()
    {
        $docente = Auth::guard('web')->user();

        abort_unless(
            $docente && $docente->activo && $docente->rol === 'docente',
            403
        );

        $this->mensajes = session()->get(
            'asistente_docente.historial.' . $docente->id,
            []
        );

        $resumen = app(ResultadosSeccionService::class)->resumen($docente);
        $calificaciones = app(PanelCalificacionesData::class)->obtener($docente);
        $preguntasSugeridas = $this->preguntasSugeridas($calificaciones['alumnos'] ?? []);

        return view('livewire.docente.panel-docente', array_merge(
            $resumen,
            $calificaciones,
            ['preguntasSugeridas' => $preguntasSugeridas]
        ))->layout('layouts.app');
    }

    /** @return list<string> */
    private function preguntasSugeridas(iterable $alumnos): array
    {
        $puntajesPorArea = [];

        foreach ($alumnos as $alumno) {
            foreach ($alumno->notas_area ?? [] as $area => $puntaje) {
                if (is_numeric($puntaje)) {
                    $puntajesPorArea[$area][] = (float) $puntaje;
                }
            }
        }

        if ($puntajesPorArea === []) {
            return [
                '¿Qué actividad breve ayuda a reforzar comprensión lectora?',
                'Propón una actividad de aprendizaje activo para mi sección.',
                '¿Cómo puedo analizar un simulacro y planificar el siguiente refuerzo?',
            ];
        }

        $promedios = [];
        foreach ($puntajesPorArea as $area => $puntajes) {
            $promedios[$area] = array_sum($puntajes) / count($puntajes);
        }
        asort($promedios, SORT_NUMERIC);

        $areaPrioritaria = (string) array_key_first($promedios);

        return [
            "¿Qué actividad breve puedo usar para reforzar {$areaPrioritaria} según los resultados del grupo?",
            "Crea ejercicios graduales para practicar {$areaPrioritaria}.",
            "¿Cómo puedo comprobar si el grupo mejora en {$areaPrioritaria}?",
        ];
    }
}
