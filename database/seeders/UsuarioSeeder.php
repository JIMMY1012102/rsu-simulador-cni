<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuarioSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [
                'nombres' => 'Administrador',
                'apellidos' => 'Prueba',
                'usuario' => 'admin_demo',
                'rol' => 'admin',
                'seccion' => null,
            ],
            [
                'nombres' => 'Docente',
                'apellidos' => 'Prueba',
                'usuario' => 'docente_demo',
                'rol' => 'docente',
                'seccion' => null,
            ],
            [
                'nombres' => 'Estudiante',
                'apellidos' => 'Prueba',
                'usuario' => 'estudiante_demo',
                'rol' => 'estudiante',
                'seccion' => 'A',
            ],
        ];

        foreach ($usuarios as $usuario) {
            $seccionId = null;

            if ($usuario['seccion'] !== null) {
                $seccionId = DB::table('secciones')
                    ->where('nombre', $usuario['seccion'])
                    ->where('grado', '5to')
                    ->value('id');

                if ($seccionId === null) {
                    throw new \RuntimeException(
                        'No existe la sección del estudiante.'
                    );
                }
            }

            DB::table('usuarios')->updateOrInsert(
                ['usuario' => $usuario['usuario']],
                [
                    'nombres' => $usuario['nombres'],
                    'apellidos' => $usuario['apellidos'],
                    'password' => Hash::make('DemoCNI2026!'),
                    'rol' => $usuario['rol'],
                    'seccion_id' => $seccionId,
                    'carrera_id' => null,
                    'activo' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
