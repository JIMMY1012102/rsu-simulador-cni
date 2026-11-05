<?php

namespace App\Livewire\Docente;

use App\Services\Docente\ResultadosSeccionService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CalificacionesSalon extends Component
{
    public function render()
    {
        $resultados = app(ResultadosSeccionService::class);
        $docente = Auth::guard('web')->user();

        abort_unless(
            $docente && $docente->activo && $docente->rol === 'docente',
            403
        );

        return view('livewire.docente.calificaciones-salon', array_merge(
            $resultados->resumen($docente),
            ['alumnos' => $resultados->alumnos($docente)]
        ))->layout('layouts.app');
    }
}
