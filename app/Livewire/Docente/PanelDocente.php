<?php

namespace App\Livewire\Docente;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Services\SimulacroService;
use Illuminate\Support\Facades\DB;

class PanelDocente extends Component
{
    public function render()
    {
        // Obtener el usuario autenticado
        $usuario = Auth::guard('web')->user();

        // Permitir acceso únicamente a docentes activos
        abort_unless(
            $usuario && $usuario->activo && $usuario->rol === 'docente',
            403
        );
        $simulacros = app(SimulacroService::class)->listar($usuario);
        $calificaciones = collect();

if ($usuario->seccion_id !== null) {
    $calificaciones = DB::table('intentos as i')
        ->join('usuarios as u', 'i.usuario_id', '=', 'u.id')
        ->join('simulacros as s', 'i.simulacro_id', '=', 's.id')
        ->where('u.rol', 'estudiante')
        ->where('u.seccion_id', $usuario->seccion_id)
        ->whereIn('i.estado', ['finalizado', 'enviado_auto'])
        ->whereNotNull('i.puntaje_total')
        ->select(
            'u.nombres',
            'u.apellidos',
            's.nombre as simulacro',
            'i.puntaje_total'
        )
        ->orderByDesc('i.id')
        ->get();
}

        // Mostrar la pantalla del docente
       return view('livewire.docente.panel-docente', [
    'simulacros' => $simulacros
])->layout('layouts.app');
    }
}
