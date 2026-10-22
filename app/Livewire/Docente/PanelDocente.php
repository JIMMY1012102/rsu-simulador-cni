<?php

namespace App\Livewire\Docente;

use App\Services\Docente\PanelCalificacionesData;
use App\Services\Docente\ResultadosSeccionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class PanelDocente extends Component
{
    public function render()
    {
        $docente = Auth::guard('web')->user();

        abort_unless(
            $docente && $docente->activo && $docente->rol === 'docente',
            403
        );

        $resumen = app(ResultadosSeccionService::class)->resumen($docente);
        $calificaciones = app(PanelCalificacionesData::class)->obtener($docente);

        return view('livewire.docente.panel-docente', array_merge(
            $resumen,
            $calificaciones
        ))->layout('layouts.app');
    }
}
