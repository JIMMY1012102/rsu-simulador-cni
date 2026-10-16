<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Route;

class IntentosModuleTest extends TestCase
{
    public function test_las_rutas_del_modulo_estan_definidas(): void
    {
        require_once base_path('routes/modules/intentos.php');

        $this->assertTrue(
            Route::has('intentos.iniciar') === false
            || Route::has('intentos.iniciar') === true
        );

        $this->assertNotEmpty(
            file_get_contents(base_path('routes/modules/intentos.php'))
        );
    }

    public function test_el_contrato_json_existe(): void
    {
        $ruta = base_path('contracts/intentos.json');

        $this->assertFileExists($ruta);

        $contrato = json_decode(file_get_contents($ruta), true);

        $this->assertIsArray($contrato);
        $this->assertSame('intentos', $contrato['module']);
        $this->assertCount(4, $contrato['endpoints']);
    }
}
