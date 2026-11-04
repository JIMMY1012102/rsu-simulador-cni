<?php

namespace App\Http\Controllers;

use App\Services\IntentoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ExamenController extends Controller
{
    /** Reanuda el intento en curso del simulacro o inicia uno nuevo, y abre el examen. */
    public function iniciar(Request $request, int $simulacro, IntentoService $intentos): RedirectResponse
    {
        $usuarioId = (int) $request->user('web')->getAuthIdentifier();
        $intento = $intentos->enCurso($usuarioId, $simulacro)
            ?? $intentos->iniciar($usuarioId, ['tipo' => 'simulacro', 'simulacro_id' => $simulacro]);

        return redirect()->route('examen', $intento->id);
    }

    /** "Resultados de tu examen": totales y resultado por asignatura de un intento propio ya cerrado. */
    public function resultados(Request $request, int $intento): View|RedirectResponse
    {
        $usuario = $request->user('web');
        $registro = DB::table('intentos')->where('id', $intento)->where('usuario_id', $usuario->id)->first();
        abort_unless($registro, 404);
        if ($registro->estado === 'en_curso') {
            return redirect()->route('examen', $registro->id);
        }

        return view('examen.resultados', [
            'intento' => $registro,
            'usuario' => $usuario,
            'numeroIntento' => DB::table('intentos')->where('usuario_id', $registro->usuario_id)
                ->where('simulacro_id', $registro->simulacro_id)->where('id', '<=', $registro->id)->count(),
            'areas' => DB::table('resultados_area as ra')
                ->join('areas as a', 'a.id', '=', 'ra.area_id')
                ->where('ra.intento_id', $registro->id)
                ->orderBy('a.id')
                ->get(['a.nombre', 'ra.total_preguntas', 'ra.aciertos', 'ra.puntaje', 'ra.porcentaje']),
            'volver' => $usuario->rol === 'estudiante' ? route('alumno.inicio') : route('panel'),
        ]);
    }
}
