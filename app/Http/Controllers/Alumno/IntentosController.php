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

        $simulacros = DB::table('simulacros as s')
            ->where('s.activo', true)
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

        $proximos = DB::table('simulacros')
            ->where('activo', true)->where('fecha_inicio', '>', $ahora)
            ->orderBy('fecha_inicio')->limit(5)
            ->get(['id', 'nombre', 'total_preguntas', 'tiempo_limite', 'fecha_inicio']);

        return view('alumno.intentos', [
            'simulacros' => $simulacros,
            'proximos' => $proximos,
        ]);
    }
}
