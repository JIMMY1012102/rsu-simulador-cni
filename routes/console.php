<?php

use App\Services\IntentoService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Validation\ValidationException;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Cierre síncrono: utiliza la misma finalización y calificación que el envío manual.
Artisan::command('intentos:cerrar-vencidos', function (IntentoService $servicio) {
    $fallos = 0;
    $cerrados = 0;
    DB::table('intentos')->where('estado', 'en_curso')
        ->whereNotNull('fecha_limite')->where('fecha_limite', '<=', now())
        ->select(['id', 'usuario_id'])->chunkById(100, function ($intentos) use ($servicio, &$fallos, &$cerrados) {
            foreach ($intentos as $intento) {
                try {
                    $servicio->finalizar((int) $intento->id, (int) $intento->usuario_id);
                    $cerrados++;
                } catch (ValidationException $e) {
                    $this->error('No se pudo cerrar el intento '.$intento->id.': '.$e->getMessage());
                    $fallos++;
                }
            }
        });
    $this->info("Intentos cerrados: {$cerrados}. Fallos: {$fallos}.");

    return $fallos > 0 ? 1 : 0;
})->purpose('Finaliza y califica los intentos cuyo tiempo terminó');

Schedule::command('intentos:cerrar-vencidos')->everyMinute()->withoutOverlapping();
