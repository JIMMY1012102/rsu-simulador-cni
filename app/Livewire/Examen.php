<?php

namespace App\Livewire;

use App\Services\IntentoService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Examen del alumno (Figma "CNI · Simulador de examen", nodo 214:3).
 * El servidor es la fuente de verdad del tiempo: IntentoService cierra el intento vencido.
 * "Marcar para revisar" vive solo en el navegador (public/js/examen.js), sin cambiar el esquema.
 */
class Examen extends Component
{
    #[Locked]
    public int $intentoId = 0;

    #[Locked]
    public bool $sinBanco = false;

    /** Índice (desde 0) de la pregunta visible. */
    public int $actual = 0;

    /** pregunta_id => alternativa_id guardada (null = en blanco). */
    #[Locked]
    public array $respuestas = [];

    public function mount(?int $intento = null): void
    {
        $usuario = Auth::guard('web')->user();
        abort_unless($usuario && $usuario->activo && $usuario->rol === 'estudiante', 403);
        if ($usuario->area_postulacion_id === null && $usuario->carrera_id === null) {
            throw new HttpResponseException(new RedirectResponse(route('alumno.elegir-area')));
        }
        if (request()->routeIs('examen.vacio')) {
            $carrera = $usuario->carrera;
            $area = $usuario->areaPostulacion ?? $carrera?->areaPostulacion;
            abort_unless($area?->activo, 403);
            abort_if($carrera && (! $carrera->activo
                || (int) $carrera->area_postulacion_id !== (int) $area->id), 403);
            $this->sinBanco = true;

            return;
        }
        abort_unless($intento !== null && $intento > 0, 404);
        $registro = $this->servicio()->obtenerPropio($intento, $this->usuarioId());
        if ($registro->estado !== 'en_curso') {
            // Livewire reemplaza redirect() durante mount; se responde con una redirección HTTP normal.
            $this->avisar($this->mensajeCierre($registro));
            throw new HttpResponseException(new RedirectResponse(route('alumno.resultados', ['intento' => $intento])));
        }
        $this->intentoId = $registro->id;
        $guardadas = $this->servicio()->respuestas($registro->id);
        $this->respuestas = $guardadas->mapWithKeys(fn ($r) => [
            $r->pregunta_id => $r->alternativa_id === null ? null : (int) $r->alternativa_id,
        ])->all();
        // Al reanudar, vuelve a la primera pregunta en blanco.
        $pendiente = $guardadas->search(fn ($r) => $r->alternativa_id === null);
        $this->actual = $pendiente === false ? 0 : $pendiente;
    }

    #[Computed]
    public function preguntas(): Collection
    {
        return $this->sinBanco ? collect() : $this->servicio()->preguntas($this->intentoId)->values();
    }

    public function responder(int $alternativaId): void
    {
        if ($this->sinBanco) {
            return;
        }
        $pregunta = $this->preguntas->get($this->actual);
        $guardado = $this->mientrasSigaEnCurso(fn () => $this->servicio()->guardarRespuesta(
            $this->intentoId, $this->usuarioId(),
            ['pregunta_id' => $pregunta->id, 'alternativa_id' => $alternativaId],
        ));
        if ($guardado) {
            $this->respuestas[$pregunta->id] = $alternativaId;
        }
    }

    public function ir(int $indice): void
    {
        if ($this->sinBanco) {
            return;
        }
        $this->actual = max(0, min($indice, $this->preguntas->count() - 1));
    }

    public function anterior(): void
    {
        $this->ir($this->actual - 1);
    }

    public function siguiente(): void
    {
        $this->ir($this->actual + 1);
    }

    public function finalizar(): void
    {
        if ($this->sinBanco) {
            return;
        }
        $intento = $this->servicio()->finalizar($this->intentoId, $this->usuarioId());
        $this->salir($this->mensajeCierre($intento));
    }

    /** Lo llama el temporizador del navegador al llegar a cero; el servidor decide si de verdad venció. */
    public function tiempoAgotado(): void
    {
        if ($this->sinBanco) {
            return;
        }
        $intento = $this->servicio()->obtenerPropio($this->intentoId, $this->usuarioId());
        if ($intento->estado !== 'en_curso') {
            $this->salir($this->mensajeCierre($intento));
        }
    }

    public function render()
    {
        if ($this->sinBanco) {
            return $this->pantallaSinBanco();
        }
        $intento = $this->servicio()->obtenerPropio($this->intentoId, $this->usuarioId());
        if ($intento->estado !== 'en_curso') {
            $this->salir($this->mensajeCierre($intento));
        }
        $preguntas = $this->preguntas;
        $this->actual = max(0, min($this->actual, $preguntas->count() - 1));
        $simulacro = $intento->simulacro_id ? DB::table('simulacros')->find($intento->simulacro_id) : null;

        return view('livewire.examen', [
            'intento' => $intento,
            'simulacro' => $simulacro,
            'numeroIntento' => DB::table('intentos')->where('usuario_id', $intento->usuario_id)
                ->where('simulacro_id', $intento->simulacro_id)->where('id', '<=', $intento->id)->count(),
            'preguntas' => $preguntas,
            'pregunta' => $preguntas->get($this->actual),
            'total' => $preguntas->count(),
            'respondidas' => count(array_filter($this->respuestas, fn ($id) => $id !== null)),
            'segundosRestantes' => $intento->fecha_limite
                ? (int) max(0, now()->diffInSeconds(Carbon::parse($intento->fecha_limite), false)) : null,
            // Fijo durante todo el intento: el x-data de la vista no debe cambiar entre renders.
            'limiteMs' => $intento->fecha_limite ? Carbon::parse($intento->fecha_limite)->getTimestampMs() : null,
            'ahoraMs' => now()->getTimestampMs(),
        ])->layout('layouts.examen-simple', ['migas' => 'Examen en curso'])->title('Simulador de examen');
    }

    /** La pantalla vacía no crea intentos, respuestas ni resultados. */
    private function pantallaSinBanco()
    {
        $usuario = Auth::guard('web')->user();
        abort_unless($usuario && $usuario->activo && $usuario->rol === 'estudiante', 403);
        $area = $usuario->areaPostulacion ?? $usuario->carrera?->areaPostulacion;

        return view('livewire.examen', [
            'intento' => null,
            'simulacro' => (object) ['nombre' => 'Examen · '.($area?->nombre ?? ''), 'tiempo_limite' => 180],
            'numeroIntento' => null,
            'preguntas' => collect(), 'pregunta' => null,
            'total' => 0, 'respondidas' => 0, 'segundosRestantes' => null,
            'limiteMs' => null, 'ahoraMs' => now()->getTimestampMs(),
        ])->layout('layouts.examen-simple', ['migas' => 'Examen'])->title('Simulador de examen');
    }

    /** Ejecuta una escritura; si el intento ya se cerró (por tiempo u otra pestaña), vuelve al panel. */
    private function mientrasSigaEnCurso(callable $accion): bool
    {
        try {
            $accion();

            return true;
        } catch (ValidationException $e) {
            if (! array_key_exists('intento', $e->errors())) {
                throw $e;
            }
            $intento = DB::table('intentos')->where('id', $this->intentoId)->first();
            $this->salir($this->mensajeCierre($intento));

            return false;
        }
    }

    private function salir(string $mensaje): void
    {
        $this->avisar($mensaje);
        $this->redirectRoute('alumno.resultados', ['intento' => $this->intentoId]);
        $this->skipRender();
    }

    /** El panel muestra los avisos desde el bag de errores del layout común. */
    private function avisar(string $mensaje): void
    {
        session()->flash('errors', (new ViewErrorBag)->put('default', new MessageBag(['intento' => $mensaje])));
    }

    private function mensajeCierre(object $intento): string
    {
        $respondidas = DB::table('respuestas_estudiante')->where('intento_id', $intento->id)
            ->whereNotNull('alternativa_id')->count();
        $inicio = $intento->estado === 'enviado_auto'
            ? 'El tiempo terminó y tu examen se envió automáticamente.'
            : 'Tu examen fue enviado.';

        return "{$inicio} Respondiste {$respondidas} de {$intento->total_preguntas} preguntas; "
            .'revisa tu puntaje en «Mis intentos y resultados».';
    }

    private function servicio(): IntentoService
    {
        return app(IntentoService::class);
    }

    private function usuarioId(): int
    {
        return (int) Auth::guard('web')->id();
    }
}
