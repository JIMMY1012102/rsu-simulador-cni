<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SimulacroService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SimulacrosController extends Controller
{
    public function __construct(private readonly SimulacroService $simulacros) {}

    public function index(): View
    {
        return view('admin.simulacros.index', [
            'simulacros' => DB::table('simulacros as s')
                ->leftJoin('carreras as c', 'c.id', '=', 's.carrera_id')
                ->select('s.*', 'c.nombre as carrera')->orderByDesc('s.id')->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('admin.simulacros.formulario', [
            'areas' => DB::table('areas_postulacion')->where('activo', true)->orderBy('codigo')->get(),
            'carreras' => DB::table('carreras')->where('activo', true)->orderBy('nombre')->get(),
            'asignaturas' => DB::table('areas')->where('activo', true)->orderBy('nombre')->get(),
            'banco' => DB::table('preguntas as p')->join('temas as t', 't.id', '=', 'p.tema_id')
                ->join('areas as a', 'a.id', '=', 't.area_id')
                ->join('alternativas as alt', 'alt.pregunta_id', '=', 'p.id')
                ->where('p.activo', true)->where('t.activo', true)->where('a.activo', true)
                ->groupBy('p.id', 't.area_id')
                ->havingRaw('COUNT(alt.id) = 4')
                ->havingRaw('SUM(CASE WHEN alt.es_correcta = 1 THEN 1 ELSE 0 END) = 1')
                ->select('t.area_id', 'p.id')->get()->countBy('area_id'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'area_postulacion_id' => ['required', Rule::exists('areas_postulacion', 'id')->where('activo', true)],
            'carrera_id' => ['nullable', Rule::exists('carreras', 'id')->where('activo', true)],
            'tiempo_limite' => ['required', 'integer', 'min:1', 'max:65535'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after:fecha_inicio'],
            'distribucion' => ['required', 'array', 'min:1'],
            'distribucion.*' => ['required', 'integer', 'min:0', 'max:1000'],
        ]);

        $asignaturas = DB::table('areas')->where('activo', true)->pluck('id')->map(fn ($id) => (string) $id)->all();
        $cuotas = [];
        foreach ($datos['distribucion'] as $id => $cantidad) {
            if (! in_array((string) $id, $asignaturas, true)) {
                throw ValidationException::withMessages(['distribucion' => 'Asignatura inválida.']);
            }
            if ((int) $cantidad > 0) {
                $cuotas[] = ['area_id' => (int) $id, 'nivel' => 'mixto', 'cantidad_preguntas' => (int) $cantidad];
            }
        }
        if (! $cuotas) {
            throw ValidationException::withMessages(['distribucion' => 'Configura al menos una pregunta.']);
        }

        if ($datos['carrera_id'] ?? null) {
            $pertenece = DB::table('carreras')->where('id', $datos['carrera_id'])
                ->where('area_postulacion_id', $datos['area_postulacion_id'])->exists();
            if (! $pertenece) {
                throw ValidationException::withMessages(['carrera_id' => 'La carrera no pertenece al área.']);
            }
        }

        // Impedir publicar un examen imposible de rendir por falta de preguntas válidas.
        foreach ($cuotas as $cuota) {
            $cantidadDisponible = DB::table('preguntas as p')
                ->join('temas as t', 't.id', '=', 'p.tema_id')
                ->join('areas as a', 'a.id', '=', 't.area_id')
                ->where('t.area_id', $cuota['area_id'])
                ->where('p.activo', true)->where('t.activo', true)->where('a.activo', true)
                ->whereRaw('(SELECT COUNT(*) FROM alternativas WHERE pregunta_id = p.id) = 4')
                ->whereRaw('(SELECT COUNT(*) FROM alternativas WHERE pregunta_id = p.id AND es_correcta = 1) = 1')
                ->count();
            if ($cantidadDisponible < $cuota['cantidad_preguntas']) {
                throw ValidationException::withMessages([
                    'distribucion' => 'No hay suficientes preguntas válidas en una asignatura: máximo disponible '.$cantidadDisponible.'.',
                ]);
            }
        }

        $this->simulacros->crear([
            'nombre' => $datos['nombre'],
            'tiempo_limite' => $datos['tiempo_limite'],
            'total_preguntas' => array_sum(array_column($cuotas, 'cantidad_preguntas')),
            'cantidad_alternativas' => 4,
            'fecha_inicio' => $datos['fecha_inicio'],
            'fecha_fin' => $datos['fecha_fin'],
            'activo' => true,
            'areas' => $cuotas,
            'areas_postulacion' => [(int) $datos['area_postulacion_id']],
            'carrera_id' => $datos['carrera_id'] ?? null,
        ], $request->user('web'));

        return redirect()->route('admin.simulacros.index')->with('status', 'Simulacro publicado correctamente.');
    }
}
