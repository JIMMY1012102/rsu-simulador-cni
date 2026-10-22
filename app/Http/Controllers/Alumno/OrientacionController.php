<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\AreaPostulacion;
use App\Models\Carrera;
use App\Models\Usuario;
use App\Services\ResultadoService;
use App\Services\SimulacroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrientacionController extends Controller
{
    private function estudiante(Request $request): Usuario
    {
        $usuario = $request->user('web');
        abort_unless($usuario && $usuario->activo && $usuario->rol === 'estudiante', 403);
        return $usuario;
    }

    public function elegirArea(Request $request): View|RedirectResponse
    {
        $usuario = $this->estudiante($request);
        if ($usuario->carrera_id !== null) {
            return redirect()->route('alumno.panel');
        }
        return view('alumno.elegir-area', [
            'areas' => AreaPostulacion::query()->where('activo', true)->orderBy('codigo')->get(),
        ]);
    }

    public function guardarArea(Request $request): RedirectResponse
    {
        $usuario = $this->estudiante($request);
        abort_if($usuario->carrera_id !== null, 403, 'Ya seleccionaste una carrera.');
        $datos = $request->validate([
            'area_id' => ['required', 'integer', Rule::exists('areas_postulacion', 'id')->where('activo', true)],
        ]);
        $request->session()->put('alumno.area_id', (int) $datos['area_id']);
        return redirect()->route('alumno.panel');
    }

    public function panel(Request $request, SimulacroService $servicio): View|RedirectResponse
    {
        $usuario = $this->estudiante($request);
        $carrera = $usuario->carrera()->with('areaPostulacion')->first();
        $area = $carrera?->areaPostulacion;

        if ($carrera && (! $carrera->activo || ! $area?->activo)) {
            abort(403, 'Tu carrera o área está inactiva.');
        }
        if (! $area) {
            $area = AreaPostulacion::query()->where('activo', true)
                ->find($request->session()->get('alumno.area_id'));
        }
        if (! $area) {
            return redirect()->route('alumno.elegir-area');
        }

        // Un simulacro se ofrece solo si está asociado explícitamente al área del alumno.
        // El servidor también verifica esa asociación cuando se inicia el intento.
        $simulacros = $carrera ? $servicio->listar($usuario)->filter(function (array $s) use ($area) {
            $areaValida = collect($s['areas_postulacion'])->contains(
                fn (array $asociada) => (int) $asociada['id'] === (int) $area->id
            );
            return $areaValida && $s['activo'] && $s['fecha_inicio'] && $s['fecha_fin']
                && now()->betweenIncluded($s['fecha_inicio'], $s['fecha_fin'])
                && now()->lt($s['fecha_fin']);
        })->values() : collect();

        $intentos = $carrera ? DB::table('intentos')->where('usuario_id', $usuario->id)
            ->whereNotNull('puntaje_total')->orderByDesc('fecha_inicio')->limit(10)->get() : collect();

        return view('alumno.panel', [
            'usuario' => $usuario,
            'area' => $area,
            'carrera' => $carrera,
            'carreras' => $carrera ? collect() : Carrera::query()->where('activo', true)
                ->where('area_postulacion_id', $area->id)->orderBy('nombre')->get(),
            'simulacros' => $simulacros,
            'intentos' => $intentos,
        ]);
    }

    public function guardarCarrera(Request $request): RedirectResponse
    {
        $usuario = $this->estudiante($request);
        abort_if($usuario->carrera_id !== null, 403, 'La carrera ya fue seleccionada.');
        $datos = $request->validate([
            'carrera_id' => ['required', 'integer', Rule::exists('carreras', 'id')->where('activo', true)],
        ]);
        $carrera = Carrera::with('areaPostulacion')->findOrFail($datos['carrera_id']);
        $areaId = (int) $request->session()->get('alumno.area_id');
        if (! $areaId || ! $carrera->areaPostulacion?->activo
            || (int) $carrera->area_postulacion_id !== $areaId) {
            throw ValidationException::withMessages([
                'carrera_id' => 'Selecciona una carrera activa del área que elegiste.',
            ]);
        }
        $actualizadas = Usuario::query()->whereKey($usuario->id)->whereNull('carrera_id')
            ->update(['carrera_id' => $carrera->id]);
        abort_unless($actualizadas === 1, 409, 'La carrera ya fue asignada en otra sesión.');
        $request->session()->forget('alumno.area_id');
        return redirect()->route('alumno.panel')->with('status', 'Carrera guardada correctamente.');
    }

    public function resultados(Request $request, int $intento, ResultadoService $resultados): View
    {
        $usuario = $this->estudiante($request);
        // Un estudiante jamás puede abrir los resultados de otro.
        DB::table('intentos')->where('id', $intento)
            ->where('usuario_id', $usuario->id)->firstOrFail();

        return view('alumno.resultados', [
            'usuario' => $usuario,
            'resultado' => $resultados->mostrar($intento),
        ]);
    }
}
