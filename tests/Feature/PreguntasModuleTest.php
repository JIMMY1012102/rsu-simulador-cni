<?php

namespace Tests\Feature;

use Tests\TestCase;

class PreguntasModuleTest extends TestCase
{
    public function test_el_archivo_de_rutas_existe(): void
    {
        $this->assertFileExists(
            base_path('routes/modules/preguntas.php')
        );
    }

    public function test_el_controlador_existe(): void
    {
        $this->assertTrue(
            class_exists(\App\Http\Controllers\Api\PreguntaController::class)
        );
    }

    public function test_el_servicio_existe(): void
    {
        $this->assertTrue(
            class_exists(\App\Services\PreguntaService::class)
        );
    }

    public function test_el_validador_existe(): void
    {
        $this->assertTrue(
            class_exists(
                \App\Http\Requests\Preguntas\GuardarPreguntaRequest::class
            )
        );
    }

    public function test_el_contrato_json_es_valido(): void
    {
        $ruta = base_path('contracts/preguntas.json');

        $this->assertFileExists($ruta);

        $contenido = file_get_contents($ruta);

        $this->assertIsArray(
            json_decode($contenido, true, 512, JSON_THROW_ON_ERROR)
        );
    }
}
