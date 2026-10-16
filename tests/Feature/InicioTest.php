<?php

namespace Tests\Feature;

use Tests\TestCase;

class InicioTest extends TestCase
{
    public function test_la_pagina_inicial_muestra_el_nombre_del_proyecto(): void
    {
        $this->get('/')->assertOk()->assertSee('Simulador CNI');
    }
}
