<?php

namespace App\Livewire\Alumno;

use App\Services\SimulacroService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class PanelAlumno extends Component
{
    public function render(SimulacroService $simulacros)
    {
        $usuario = Auth::guard('web')->user();
        abort_unless($usuario && $usuario->activo && $usuario->rol === 'estudiante', 403);

        $simuladorIniciado = DB::table('intentos')->where('usuario_id', $usuario->id)
            ->where('tipo', 'simulacro')->exists();

        // El servicio ya limita al estudiante a simulacros activos; aquí solo se mira la ventana de fechas.
        $ahora = now();
        $simulacrosAbiertos = $simulacros->listar($usuario)->filter(fn (array $s) => $s['fecha_inicio'] && $s['fecha_fin']
            && $ahora->gte(Carbon::parse($s['fecha_inicio'])) && $ahora->lt(Carbon::parse($s['fecha_fin'])))->count();

        return view('livewire.alumno.panel-alumno', [
            'usuario' => $usuario,
            'simuladorIniciado' => $simuladorIniciado,
            'pasosCompletados' => $simuladorIniciado ? 2 : 1,
            'simulacrosAbiertos' => $simulacrosAbiertos,
        ])->layout('livewire.alumno.layout');
    }
}
