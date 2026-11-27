<?php

namespace Tests\Feature;

use App\Services\Docente\AsistenteDocenteService;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class AsistenteDocenteFallbackTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.key' => 'test-key',
            'services.gemini.model' => 'gemini-3.8-flash',
            'services.gemini.url' => 'https://generativelanguage.googleapis.com/v1beta',
            'services.gemini.fallback_models' => 'gemini-3.5-flash-lite,gemini-4-hydrogen',
        ]);
    }

    public function test_prueba_el_siguiente_modelo_si_el_principal_devuelve_503(): void
    {
        Http::fakeSequence()
            ->push(['error' => ['message' => 'Service unavailable']], 503)
            ->push([
                'candidates' => [[
                    'content' => ['parts' => [['text' => 'Respuesta del respaldo']]],
                    'finishReason' => 'STOP',
                ]],
            ], 200);

        $respuesta = app(AsistenteDocenteService::class)->consultar(
            'Analiza el rendimiento del grupo.',
            ['datos_disponibles' => false]
        );

        $this->assertSame('Respuesta del respaldo', $respuesta);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => str_contains(
            $request->url(),
            '/models/gemini-3.8-flash:generateContent'
        ));
        Http::assertSent(fn ($request) => str_contains(
            $request->url(),
            '/models/gemini-3.5-flash-lite:generateContent'
        ));
    }

    public function test_no_intenta_respaldo_si_la_api_key_es_rechazada(): void
    {
        Http::fakeSequence()
            ->push(['error' => ['message' => 'Invalid API key']], 401);

        try {
            app(AsistenteDocenteService::class)->consultar(
                'Analiza el rendimiento del grupo.'
            );
            $this->fail('Se esperaba una excepción por credenciales inválidas.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('401', $e->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_agota_los_modelos_y_devuelve_el_ultimo_error_transitorio(): void
    {
        Http::fakeSequence()
            ->push(['error' => ['message' => 'Unavailable']], 503)
            ->push(['error' => ['message' => 'Unavailable']], 503)
            ->push(['error' => ['message' => 'Unavailable']], 503);

        try {
            app(AsistenteDocenteService::class)->consultar(
                'Analiza el rendimiento del grupo.'
            );
            $this->fail('Se esperaba una excepción después de agotar los modelos.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('503', $e->getMessage());
        }

        Http::assertSentCount(3);
    }
}
