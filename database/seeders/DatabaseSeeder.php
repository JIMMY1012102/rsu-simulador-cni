<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SeccionSeeder::class,
            AreaPostulacionSeeder::class,
            CarreraSeeder::class,
            UsuarioSeeder::class,
        ]);

        // Los cuatro docentes ficticios existen solo para desarrollo y pruebas.
        if (app()->environment(['local', 'testing'])) {
            $this->call(DocentesSeccionDemoSeeder::class);
        }
    }
}

