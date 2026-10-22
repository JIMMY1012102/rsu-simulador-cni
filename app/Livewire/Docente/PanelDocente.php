<?php

namespace App\Livewire\Docente;

use App\Services\Docente\ResultadosSeccionService;
use App\Services\SimulacroService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PanelDocente extends Component
{
    public function render()
    {
        $resultados = app(ResultadosSeccionService::class);
        $docente = Auth::guard('web')->user();

        abort_unless(
            $docente && $docente->activo && $docente->rol === 'docente',
            403
        );

        return view('livewire.docente.panel-docente', array_merge(
            $resultados->resumen($docente),
            [
                'calificaciones' => $resultados->resultados($docente, 5),
                'simulacros' => app(SimulacroService::class)->listar($docente),
            ]
        ))->layout('layouts.app');
    }
}
