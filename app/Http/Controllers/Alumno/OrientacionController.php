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
        if ($usuario->area_postulacion_id !== null || $usuario->carrera_id !== null) {
            return redirect()->route('alumno.panel');
        }

        return view('alumno.elegir-area', [
            'areas' => AreaPostulacion::query()->where('activo', true)->orderBy('codigo')->get(),
        ]);
    }

    public function guardarArea(Request $request): RedirectResponse
    {
        $usuario = $this->estudiante($request);
        abort_if($usuario->area_postulacion_id !== null || $usuario->carrera_id !== null, 403,
            'Ya tienes un área de preparación asignada.');

        $datos = $request->validate([
            'area_id' => ['required', 'integer', Rule::exists('areas_postulacion', 'id')->where('activo', true)],
        ]);

        // La primera elección se guarda en MariaDB, no solo en sesión, para futuros ingresos.
        $guardado = Usuario::query()->whereKey($usuario->id)
            ->whereNull('area_postulacion_id')->whereNull('carrera_id')
            ->update(['area_postulacion_id' => (int) $datos['area_id']]);
        abort_unless($guardado === 1, 409, 'La selección ya se realizó desde otra sesión.');

        $usuario->refresh();

        return redirect()->route('alumno.panel')
            ->with('status', 'Área de preparación seleccionada.');
    }

    public function panel(Request $request, SimulacroService $servicio): View|RedirectResponse
    {
        $usuario = $this->estudiante($request);
        $carrera = $usuario->carrera()->with('areaPostulacion')->first();

        // Las cuentas antiguas con carrera y sin área explícita mantienen su acceso.
        $area = $usuario->areaPostulacion()->first() ?? $carrera?->areaPostulacion;

        if ($carrera && (! $carrera->activo || ! $carrera->areaPostulacion?->activo)) {
            abort(403, 'La carrera del estudiante está inactiva.');
        }
        if ($carrera && $area && (int) $carrera->area_postulacion_id !== (int) $area->id) {
            abort(403, 'La carrera no corresponde al área de preparación asignada.');
        }
        if ($area && ! $area->activo) {
            abort(403, 'El área de preparación está inactiva.');
        }
        if (! $area) {
            return redirect()->route('alumno.elegir-area');
        }

        // No hace falta elegir carrera: el área guardada determina qué examen se ofrece.
        $simulacros = $servicio->listar($usuario)->filter(function (array $s) use ($area, $carrera) {
            $habilitado = collect($s['areas_postulacion'])->contains(
                fn (array $asociada) => (int) $asociada['id'] === (int) $area->id
            );

            $carreraPermitida = $s['carrera_id'] === null
                || ($carrera !== null && (int) $carrera->id === (int) $s['carrera_id']);

            return $habilitado && $carreraPermitida && $s['activo'] && $s['fecha_inicio'] && $s['fecha_fin']
                && now()->gte($s['fecha_inicio']) && now()->lt($s['fecha_fin']);
        })->values();

        $intentos = DB::table('intentos')->where('usuario_id', $usuario->id)
            ->whereNotNull('puntaje_total')->orderByDesc('fecha_inicio')->limit(10)->get();

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

    // Carrera opcional: sirve para personalizar el perfil sin bloquear el examen por área.
    public function guardarCarrera(Request $request): RedirectResponse
    {
        $usuario = $this->estudiante($request);
        abort_if($usuario->carrera_id !== null, 403, 'La carrera ya fue seleccionada.');

        $datos = $request->validate([
            'carrera_id' => ['required', 'integer', Rule::exists('carreras', 'id')->where('activo', true)],
        ]);
        $carrera = Carrera::with('areaPostulacion')->findOrFail($datos['carrera_id']);

        $areaId = $usuario->area_postulacion_id;
        if (! $areaId || ! $carrera->areaPostulacion?->activo
            || (int) $carrera->area_postulacion_id !== (int) $areaId) {
            throw ValidationException::withMessages([
                'carrera_id' => 'Selecciona una carrera activa de tu área de preparación.',
            ]);
        }

        $guardado = Usuario::query()->whereKey($usuario->id)->whereNull('carrera_id')
            ->where('area_postulacion_id', $areaId)
            ->update(['carrera_id' => $carrera->id]);
        abort_unless($guardado === 1, 409, 'La carrera ya fue asignada.');

        $usuario->refresh();
        return redirect()->route('alumno.panel')->with('status', 'Carrera guardada correctamente.');
    }

    public function resultados(Request $request, int $intento, ResultadoService $resultados): View
    {
        $usuario = $this->estudiante($request);
        DB::table('intentos')->where('id', $intento)
            ->where('usuario_id', $usuario->id)->firstOrFail();

        return view('alumno.resultados', [
            'usuario' => $usuario,
            'resultado' => $resultados->mostrar($intento),
        ]);
    }
}
