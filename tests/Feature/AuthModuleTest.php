<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthModuleTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(array $cambios = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombres' => 'Ana',
            'apellidos' => 'Prueba',
            'usuario' => 'ana.prueba',
            'password' => 'secreto123',
            'rol' => 'estudiante',
            'activo' => true,
        ], $cambios));
    }

    public function test_login_correcto_devuelve_token_y_perfil_sin_contrasena(): void
    {
        $usuario = $this->crearUsuario();

        $respuesta = $this->postJson('/api/login', ['usuario' => 'ana.prueba', 'password' => 'secreto123']);

        $respuesta->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tipo_token', 'Bearer')
            ->assertJsonPath('data.usuario.id', $usuario->id)
            ->assertJsonPath('data.usuario.rol', 'estudiante')
            ->assertJsonMissingPath('data.usuario.password');

        $this->assertNotEmpty($respuesta->json('data.token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_contrasena_incorrecta_devuelve_401(): void
    {
        $this->crearUsuario();

        $this->postJson('/api/login', ['usuario' => 'ana.prueba', 'password' => 'otra'])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_usuario_inexistente_devuelve_el_mismo_error(): void
    {
        $this->postJson('/api/login', ['usuario' => 'nadie', 'password' => 'secreto123'])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Usuario o contraseña incorrectos.');
    }

    public function test_usuario_inactivo_no_puede_iniciar_sesion(): void
    {
        $this->crearUsuario(['activo' => false]);

        $this->postJson('/api/login', ['usuario' => 'ana.prueba', 'password' => 'secreto123'])
            ->assertForbidden()
            ->assertJsonPath('success', false);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_datos_incompletos_devuelven_error_de_validacion(): void
    {
        $this->postJson('/api/login', [])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonValidationErrors(['usuario', 'password']);
    }

    public function test_me_devuelve_el_usuario_del_token(): void
    {
        $usuario = $this->crearUsuario(['rol' => 'docente']);
        $token = $usuario->createToken('api')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')
            ->assertOk()
            ->assertJsonPath('data.id', $usuario->id)
            ->assertJsonPath('data.rol', 'docente')
            ->assertJsonMissingPath('data.password');
    }

    public function test_sin_token_no_hay_acceso(): void
    {
        $this->getJson('/api/me')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('data', null);

        $this->postJson('/api/logout')->assertUnauthorized();
    }

    public function test_logout_revoca_el_token(): void
    {
        $this->crearUsuario();
        $token = $this->postJson('/api/login', ['usuario' => 'ana.prueba', 'password' => 'secreto123'])
            ->json('data.token');

        $this->withToken($token)->postJson('/api/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Sesión cerrada.');

        $this->assertDatabaseCount('personal_access_tokens', 0);

        // El guard guarda el usuario en memoria entre peticiones del mismo test.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertUnauthorized();
    }
}
