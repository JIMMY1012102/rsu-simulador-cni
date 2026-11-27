<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AsistenteChatTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(string $rol): Usuario
    {
        return Usuario::create([
            'nombres' => 'Usuario', 'apellidos' => 'Prueba',
            'usuario' => $rol.'_'.uniqid(), 'password' => 'password',
            'rol' => $rol, 'activo' => true,
        ]);
    }

    public function test_sin_sesion_no_puede_entrar(): void
    {
        $this->postJson('/asistente/chat', ['mensaje' => 'Hola'])->assertUnauthorized();
    }

    public function test_estudiante_envia_rol_e_id_de_sesion(): void
    {
        config(['asistente.enabled' => true, 'asistente.url' => 'http://agente.test/chat', 'asistente.token' => 'secreto']);
        Http::fake(['agente.test/*' => Http::response(['respuesta' => 'Resultados'], 200)]);
        $usuario = $this->usuario('estudiante');
        $this->actingAs($usuario, 'web')->postJson('/asistente/chat', ['mensaje' => 'Mis resultados'])->assertOk();
        Http::assertSent(fn ($r) => $r['rol'] === 'estudiante' && (int) $r['usuario_id'] === $usuario->id && is_string($r['sesion']) && $r['sesion'] !== '' && $r->hasHeader('X-Agente-Token', 'secreto'));
    }

    public function test_admin_envia_rol_e_id_correctos(): void
    {
        config(['asistente.enabled' => true, 'asistente.url' => 'http://agente.test/chat', 'asistente.token' => 'secreto']);
        Http::fake(['agente.test/*' => Http::response(['respuesta' => 'Resumen'], 200)]);
        $usuario = $this->usuario('admin');
        $this->actingAs($usuario, 'web')->postJson('/asistente/chat', ['mensaje' => 'Resumen'])->assertOk();
        Http::assertSent(fn ($r) => $r['rol'] === 'admin' && (int) $r['usuario_id'] === $usuario->id);
    }

    public function test_docente_recibe_403(): void
    {
        $this->actingAs($this->usuario('docente'), 'web')->postJson('/asistente/chat', ['mensaje' => 'Hola'])->assertForbidden();
    }

    public function test_mensaje_vacio_o_mayor_a_500_caracteres_se_rechaza(): void
    {
        $usuario = $this->usuario('estudiante');
        $this->actingAs($usuario, 'web')->postJson('/asistente/chat', ['mensaje' => ''])->assertUnprocessable();
        $this->actingAs($usuario, 'web')->postJson('/asistente/chat', ['mensaje' => str_repeat('x', 501)])->assertUnprocessable();
    }

    public function test_agente_caido_devuelve_mensaje_amable(): void
    {
        config(['asistente.enabled' => true, 'asistente.url' => 'http://agente.test/chat', 'asistente.token' => 'secreto']);
        Http::fake(['agente.test/*' => Http::response([], 500)]);
        $this->actingAs($this->usuario('estudiante'), 'web')->postJson('/asistente/chat', ['mensaje' => 'Hola'])
            ->assertStatus(503)->assertJson(['respuesta' => 'El asistente no está disponible en este momento. Intente de nuevo en un minuto.']);
    }

    public function test_agente_desactivado_devuelve_mensaje_claro(): void
    {
        config(['asistente.enabled' => false]);

        $this->actingAs($this->usuario('estudiante'), 'web')
            ->postJson('/asistente/chat', ['mensaje' => 'Hola'])
            ->assertStatus(503)
            ->assertJson(['respuesta' => 'El asistente está desactivado en este momento. Inténtelo nuevamente más tarde.']);
    }

    public function test_chat_sigue_visible_cuando_el_agente_esta_desactivado(): void
    {
        config(['asistente.enabled' => false]);

        $this->actingAs($this->usuario('estudiante'), 'web')
            ->get('/panel')
            ->assertSee('Servicio desactivado')
            ->assertSee('El asistente está desactivado en este momento.');
    }
}
