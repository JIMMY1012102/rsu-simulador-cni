<?php

namespace App\Http\Controllers\Alumno;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * "Mis intentos": simulacros abiertos para rendir (iniciar o continuar) y exámenes sin terminar.
 * El examen se inicia con la ruta examen.iniciar (ExamenController), que reanuda si ya hay uno en curso.
 */
class IntentosController extends Controller
{
    public function __invoke(): View
    {
        $usuario = auth('web')->user();
        $ahora = now();
        $areaId = $usuario->area_postulacion_id ?? $usuario->carrera?->area_postulacion_id;

        $simulacros = DB::table('simulacros as s')
            ->where('s.activo', true)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('area_postulacion_simulacro as aps')
                ->whereColumn('aps.simulacro_id', 's.id')->where('aps.area_postulacion_id', $areaId))
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('simulacros', 'carrera_id'),
                fn ($q) => $q->where(fn ($carrera) => $carrera->whereNull('s.carrera_id')
                    ->orWhere('s.carrera_id', $usuario->carrera_id ?? 0)))
            ->where('s.fecha_inicio', '<=', $ahora)->where('s.fecha_fin', '>', $ahora)
            ->orderBy('s.fecha_fin')
            ->select('s.id', 's.nombre', 's.total_preguntas', 's.tiempo_limite', 's.fecha_fin')
            ->selectSub(fn ($q) => $q->from('intentos as i')
                ->whereColumn('i.simulacro_id', 's.id')->where('i.usuario_id', $usuario->id)
                ->selectRaw('count(*)'), 'intentos')
            // Un intento en curso cuyo tiempo no venció: el botón dice "Continuar".
            ->selectSub(fn ($q) => $q->from('intentos as i')
                ->whereColumn('i.simulacro_id', 's.id')->where('i.usuario_id', $usuario->id)
                ->where('i.estado', 'en_curso')
                ->where(fn ($q) => $q->whereNull('i.fecha_limite')->orWhere('i.fecha_limite', '>', $ahora))
                ->selectRaw('count(*)'), 'en_curso')
            ->get();

        $proximos = DB::table('simulacros as s')
            ->where('s.activo', true)->where('s.fecha_inicio', '>', $ahora)
            ->whereExists(fn ($q) => $q->selectRaw('1')->from('area_postulacion_simulacro as aps')
                ->whereColumn('aps.simulacro_id', 's.id')->where('aps.area_postulacion_id', $areaId))
            ->when(\Illuminate\Support\Facades\Schema::hasColumn('simulacros', 'carrera_id'),
                fn ($q) => $q->where(fn ($carrera) => $carrera->whereNull('s.carrera_id')
                    ->orWhere('s.carrera_id', $usuario->carrera_id ?? 0)))
            ->orderBy('s.fecha_inicio')->limit(5)
            ->get(['s.id', 's.nombre', 's.total_preguntas', 's.tiempo_limite', 's.fecha_inicio']);

        return view('alumno.intentos', [
            'simulacros' => $simulacros,
            'proximos' => $proximos,
        ]);
    }
}
