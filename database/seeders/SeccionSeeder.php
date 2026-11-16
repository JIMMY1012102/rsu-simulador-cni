<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SeccionSeeder extends Seeder
{
    public function run(): void
    {
        $secciones = ['A', 'B', 'C', 'D'];

        foreach ($secciones as $seccion) {
            DB::table('secciones')->updateOrInsert(
                [
                    'nombre' => $seccion,
                    'grado' => '5to',
                ],
                [
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

