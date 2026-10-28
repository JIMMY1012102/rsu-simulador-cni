<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use App\Models\AreaPostulacion;
use App\Models\Carrera;
use App\Models\Usuario;
use App\Services\ResultadoService;
use App\Services\SimulacroService;
use App\Services\Alumno\DashboardEstudianteService;
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

    public function panel(Request $request, SimulacroService $servicio, DashboardEstudianteService $dashboard): View|RedirectResponse
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

        // KISS: el mismo panel funciona para A-E. Solo cambian identidad y datos del área.
        // No se reasigna carrera ni se cambia la selección persistida en MariaDB.
        abort_unless(config('panel_alumno.areas.'.$area->codigo), 404);

        // No hace falta elegir carrera: el área guardada determina qué examen se ofrece.
        $simulacros = $this->simulacrosDelArea($usuario, $area, $carrera, $servicio);

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
            'dashboard' => $dashboard->obtener($usuario, $simulacros),
            'esVistaPrevia' => false,
        ]);
    }


    /**
     * Misma autorización de áreas y carreras usada en el panel y en el detalle
     * del tema. Nunca exponemos contenidos de otras áreas al navegar por URL.
     */
    private function simulacrosDelArea(
        Usuario $usuario,
        AreaPostulacion $area,
        ?Carrera $carrera,
        SimulacroService $servicio
    ): \Illuminate\Support\Collection {
        return $servicio->listar($usuario)->filter(function (array $simulacro) use ($area, $carrera) {
            $incluyeArea = collect($simulacro['areas_postulacion'])->contains(
                fn (array $asociada) => (int) $asociada['id'] === (int) $area->id
            );
            $permiteCarrera = $simulacro['carrera_id'] === null
                || ($carrera && (int) $carrera->id === (int) $simulacro['carrera_id']);

            return $incluyeArea && $permiteCarrera && $simulacro['activo']
                && $simulacro['fecha_inicio'] && $simulacro['fecha_fin']
                && now()->gte($simulacro['fecha_inicio']) && now()->lt($simulacro['fecha_fin']);
        })->values();
    }

    /**
     * Detalle de estudio de un tema real y permitido para la sección académica
     * que eligió el alumno. Solo muestra agregados, nunca respuestas correctas.
     */
    public function tema(
        Request $request,
        int $tema,
        SimulacroService $servicio,
        DashboardEstudianteService $dashboard
    ): View|RedirectResponse {
        $usuario = $this->estudiante($request);
        $carrera = $usuario->carrera()->with('areaPostulacion')->first();
        $area = $usuario->areaPostulacion()->first() ?? $carrera?->areaPostulacion;

        if (! $area) {
            return redirect()->route('alumno.elegir-area');
        }
        abort_unless($area->activo && config('panel_alumno.areas.'.$area->codigo), 404);
        abort_if($carrera && (! $carrera->activo || ! $carrera->areaPostulacion?->activo
            || (int) $carrera->area_postulacion_id !== (int) $area->id), 403);

        $simulacros = $this->simulacrosDelArea($usuario, $area, $carrera, $servicio);
        $resumen = $dashboard->obtener($usuario, $simulacros);
        $temaActual = $resumen['temas']->firstWhere('id', $tema);
        abort_unless($temaActual, 404);

        return view('alumno.tema', [
            'usuario' => $usuario,
            'area' => $area,
            'tema' => $temaActual,
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
