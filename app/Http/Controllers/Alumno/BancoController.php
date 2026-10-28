<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * "Banco de preguntas": cuántas preguntas hay por área, tema y nivel.
 * No muestra enunciados ni respuestas para no adelantar el examen.
 * La práctica por tema queda pendiente: IntentoService todavía no sortea preguntas para prácticas.
 */
class BancoController extends Controller
{
    private const NIVELES = ['basico' => 'Básico', 'intermedio' => 'Intermedio', 'avanzado' => 'Avanzado'];

    public function __invoke(): View
    {
        $usuario = auth('web')->user();
        $areaId = $usuario->area_postulacion_id ?? $usuario->carrera?->area_postulacion_id;

        $filas = DB::table('preguntas as p')
            ->join('temas as t', 't.id', '=', 'p.tema_id')
            ->join('areas as a', 'a.id', '=', 't.area_id')
            ->where('p.activo', true)->where('t.activo', true)->where('a.activo', true)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('simulacro_area as sa')
                ->join('area_postulacion_simulacro as aps', 'aps.simulacro_id', '=', 'sa.simulacro_id')
                ->whereColumn('sa.area_id', 'a.id')->where('aps.area_postulacion_id', $areaId))
            ->groupBy('a.id', 'a.nombre', 't.id', 't.nombre', 'p.nivel')
            ->orderBy('a.id')->orderBy('t.id')
            ->get(['a.id as area_id', 'a.nombre as area', 't.id as tema_id', 't.nombre as tema', 'p.nivel',
                DB::raw('count(*) as cantidad')]);

        // area => [nombre, total, temas => [tema_id => [nombre, total, niveles => [nivel => cantidad]]]]
        $areas = $filas->groupBy('area_id')->map(fn ($deArea) => [
            'nombre' => $deArea->first()->area,
            'total' => (int) $deArea->sum('cantidad'),
            'temas' => $deArea->groupBy('tema_id')->map(fn ($deTema) => [
                'nombre' => $deTema->first()->tema,
                'total' => (int) $deTema->sum('cantidad'),
                'niveles' => collect(self::NIVELES)->map(fn ($etiqueta, $nivel) => (int) $deTema
                    ->firstWhere('nivel', $nivel)?->cantidad),
            ])->values(),
        ])->values();

        return view('alumno.banco', [
            'areas' => $areas,
            'niveles' => self::NIVELES,
            'total' => (int) $filas->sum('cantidad'),
        ]);
    }
}
