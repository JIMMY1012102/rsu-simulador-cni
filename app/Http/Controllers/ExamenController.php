<?php

namespace App\Http\Controllers;

use App\Services\IntentoService;
use App\Services\Alumno\ExamenIndependienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExamenController extends Controller
{
    /** El área se resuelve en el servidor; el navegador no elige cuotas ni alumno. */
    public function independiente(Request $request, ExamenIndependienteService $examenes): RedirectResponse
    {
        abort_unless($request->user('web')?->rol === 'estudiante', 403);
        if ($request->user('web')->area_postulacion_id === null && $request->user('web')->carrera_id === null) {
            return redirect()->route('alumno.elegir-area');
        }
        $intento = $examenes->iniciar($request->user('web'));

        return redirect()->route('examen', $intento->id);
    }

    /** Reanuda el intento en curso del simulacro o inicia uno nuevo, y abre el examen. */
    public function iniciar(Request $request, int $simulacro, IntentoService $intentos): RedirectResponse
    {
        abort_unless($request->user('web')?->rol === 'estudiante' && ($request->user('web')?->area_postulacion_id !== null || $request->user('web')?->carrera_id !== null), 403);
        $usuarioId = (int) $request->user('web')->getAuthIdentifier();
        $intento = $intentos->enCurso($usuarioId, $simulacro)
            ?? $intentos->iniciar($usuarioId, ['tipo' => 'simulacro', 'simulacro_id' => $simulacro]);

        return redirect()->route('examen', $intento->id);
    }
}
