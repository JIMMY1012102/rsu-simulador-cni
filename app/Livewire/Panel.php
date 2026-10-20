<?php

namespace App\Livewire;

use App\Services\SimulacroService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Panel extends Component
{
    public function render()
    {
        $usuario = Auth::guard('web')->user();
        abort_unless($usuario && $usuario->activo, 403);

        return view('livewire.panel', [
            'simulacros' => app(SimulacroService::class)->listar($usuario),
            'intentos' => DB::table('intentos')->where('usuario_id', $usuario->id)
                ->orderByDesc('fecha_inicio')->limit(20)->get(),
        ])->layout('layouts.app');
    }
}
