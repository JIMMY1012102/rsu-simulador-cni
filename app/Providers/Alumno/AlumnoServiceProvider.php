<?php

namespace App\Providers\Alumno;

use Illuminate\Support\ServiceProvider;

class AlumnoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(base_path('routes/alumno.php'));
    }
}
