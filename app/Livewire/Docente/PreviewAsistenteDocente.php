<?php

namespace App\Livewire\Docente;

use App\Services\Docente\AsistenteDocenteService;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Component;
use Throwable;

class PreviewAsistenteDocente extends Component
{
    public string $pregunta = '';

    public ?string $respuesta = null;

    public ?string $errorAsistente = null;

    public array $mensajes = [];

    public bool $asistenteAbierto = true;

    private const CLAVE_CONVERSACION = 'asistente_docente.preview.historial';

    public function mount(): void
    {
        $this->mensajes = session()->get(self::CLAVE_CONVERSACION, []);
    }

    public function enviarPregunta(): void
    {
        $this->validate([
            'pregunta' => 'required|string|min:3|max:2000',
        ]);

        $clave = 'preview-asistente:' . request()->ip();

        if (RateLimiter::tooManyAttempts($clave, 5)) {
            $this->errorAsistente =
                'Has realizado varias consultas. Inténtalo en un minuto.';
            return;
        }

        RateLimiter::hit($clave, 60);

        $this->respuesta = null;
        $this->errorAsistente = null;

        try {
            $pregunta = trim($this->pregunta);
            $historial = session()->get(self::CLAVE_CONVERSACION, []);
            $this->respuesta = app(AsistenteDocenteService::class)
                ->consultar($pregunta, [
                    'datos_disponibles' => false,
                    'modo' => 'vista_publica_sin_datos_academicos',
                ], $historial);

            $historial[] = ['role' => 'user', 'text' => $pregunta];
            $historial[] = ['role' => 'model', 'text' => $this->respuesta];
            $historial = array_slice($historial, -12);
            session()->put(self::CLAVE_CONVERSACION, $historial);
            $this->mensajes = $historial;

            $this->pregunta = '';
        } catch (Throwable $e) {
            report($e);

            $this->errorAsistente =
                'No se pudo obtener una respuesta de Gemini. '
                . 'Verifica la configuración y vuelve a intentarlo.';
        }
    }

    public function abrirAsistente(): void
    {
        $this->asistenteAbierto = true;
    }

    public function cerrarAsistente(): void
    {
        $this->asistenteAbierto = false;
    }

    public function nuevaConversacion(): void
    {
        session()->forget(self::CLAVE_CONVERSACION);
        $this->mensajes = [];
        $this->pregunta = '';
        $this->respuesta = null;
        $this->errorAsistente = null;
        $this->asistenteAbierto = true;
    }

    public function usarSugerencia(string $sugerencia): void
    {
        $this->pregunta = mb_substr($sugerencia, 0, 2000);
    }

    public function render()
    {
        return view('livewire.docente.preview-asistente-docente');
    }
}
