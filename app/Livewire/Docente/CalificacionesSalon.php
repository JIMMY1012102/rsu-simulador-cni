<?php

namespace App\Livewire\Docente;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class CalificacionesSalon extends Component
{
    public function render()
    {
        $docente = Auth::guard('web')->user();

        abort_unless(
            $docente && $docente->activo && $docente->rol === 'docente',
            403
        );

        return view('livewire.docente.calificaciones-salon')
            ->layout('layouts.app');
    }
}
