<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AuditarAreasCni extends Command
{
    protected $signature = 'cni:auditar-areas';

    protected $description = 'Diagnóstico solo de lectura del catálogo A-E y compatibilidad de base de datos';

    public function handle(): int
    {
        $esperadas = [
            'A' => 'Ciencias de la Salud',
            'B' => 'Ciencias Básicas',
            'C' => 'Ingenierías',
            'D' => 'Ciencias Económicas y de la Gestión',
            'E' => 'Humanidades y Ciencias Jurídicas y Sociales',
        ];

        if (! Schema::hasTable('areas_postulacion') || ! Schema::hasTable('usuarios')) {
            $this->error('Faltan tablas: primero confirma que la base y las migraciones son correctas.');
            return self::FAILURE;
        }

        $mismatches = [];
        $registros = DB::table('areas_postulacion')->get(['id', 'codigo', 'nombre']);
        $filas = [];

        foreach ($registros as $registro) {
            $nombreEsperado = $esperadas[$registro->codigo] ?? null;
            $coincide = $nombreEsperado !== null && $registro->nombre === $nombreEsperado;
            $filas[] = [$registro->id, $registro->codigo, $registro->nombre, $coincide ? 'OK' : 'REVISAR'];
            if (! $coincide) {
                $mismatches[] = "Área {$registro->codigo}: catálogo incompatible; requiere plan de migración.";
            }
            unset($esperadas[$registro->codigo]);
        }

        foreach ($esperadas as $codigo => $nombre) {
            $mismatches[] = "Falta área {$codigo}: {$nombre}.";
        }

        if (! Schema::hasColumn('usuarios', 'area_postulacion_id')) {
            $mismatches[] = 'Falta usuarios.area_postulacion_id: aplicar la migración nueva en una copia de desarrollo.';
        }

        $this->table(['ID', 'Código', 'Nombre en BD', 'Estado'], $filas);
        $this->info('Usuarios: '.DB::table('usuarios')->count().'. Solo se consultaron totales y catálogo.');

        if ($mismatches) {
            foreach ($mismatches as $mensaje) {
                $this->warn($mensaje);
            }
            $this->error('Diagnóstico: pendiente. No se realizó ninguna escritura.');
            return self::FAILURE;
        }

        $this->info('Diagnóstico correcto: catálogo A-E y columna de selección presentes. No se realizó ninguna escritura.');
        return self::SUCCESS;
    }
}
