<?php

namespace Tests\Unit;

use App\Services\ResultadoService;
use PHPUnit\Framework\TestCase;

class ResultadoServiceTest extends TestCase
{
    private ResultadoService $servicio;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicio = new ResultadoService();
    }

    public function test_calcula_puntaje_correcto(): void
    {
        $simulacro = (object) [
            'puntaje_correcta' => 20,
            'puntaje_incorrecta' => -1.125,
            'puntaje_blanco' => 0,
        ];

        $resultado = $this->servicio->calcularPuntaje('correcta', $simulacro);

        $this->assertSame(20.0, $resultado);
    }

    public function test_calcula_puntaje_incorrecto(): void
    {
        $simulacro = (object) [
            'puntaje_correcta' => 20,
            'puntaje_incorrecta' => -1.125,
            'puntaje_blanco' => 0,
        ];

        $resultado = $this->servicio->calcularPuntaje('incorrecta', $simulacro);

        $this->assertSame(-1.125, $resultado);
    }

    public function test_calcula_puntaje_en_blanco(): void
    {
        $simulacro = (object) [
            'puntaje_correcta' => 20,
            'puntaje_incorrecta' => -1.125,
            'puntaje_blanco' => 0,
        ];

        $resultado = $this->servicio->calcularPuntaje('en_blanco', $simulacro);

        $this->assertSame(0.0, $resultado);
    }

    public function test_calcula_puntaje_doble_marca(): void
    {
        $simulacro = (object) [
            'puntaje_correcta' => 20,
            'puntaje_incorrecta' => -1.125,
            'puntaje_blanco' => 0,
        ];

        $resultado = $this->servicio->calcularPuntaje('doble_marca', $simulacro);

        $this->assertSame(0.0, $resultado);
    }
}
