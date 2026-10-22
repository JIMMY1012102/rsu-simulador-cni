<?php

namespace Tests\Feature;

use App\Http\Middleware\EnsureActiveUser;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AuthModuleTest extends TestCase
{
    use RefreshDatabase;

    private function crearUsuario(array $cambios = []): Usuario
    {
        return Usuario::create(array_merge([
            'nombres' => 'Ana', 'apellidos' => 'Prueba', 'usuario' => 'ana.prueba',
            'password' => 'secreto123', 'rol' => 'estudiante', 'activo' => true,
        ], $cambios));
    }

    public function test_login_web_crea_sesion_y_regenera_su_identificador(): void
    {
        $usuario = $this->crearUsuario();
        $this->get('/login')->assertOk()->assertSee('Inicia sesión');
        $anterior = session()->getId();

        $this->post('/login', ['usuario' => 'ana.prueba', 'password' => 'secreto123'])
            ->assertRedirect('/alumno/elegir-area');

        $this->assertAuthenticatedAs($usuario, 'web');
        $this->assertNotSame($anterior, session()->getId());
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->get('/alumno/elegir-area')->assertOk()->assertSee('¿En qué área deseas prepararte?');
    }

    public function test_login_json_usa_la_misma_sesion_sin_emitir_tokens(): void
    {
        $usuario = $this->crearUsuario();
        $this->postJson('/api/login', ['usuario' => 'ana.prueba', 'password' => 'secreto123'])
            ->assertOk()->assertJsonPath('data.usuario.id', $usuario->id)
            ->assertJsonMissingPath('data.token')->assertJsonMissingPath('data.usuario.password');

        $this->getJson('/api/me')->assertOk()->assertJsonPath('data.id', $usuario->id);
        $this->assertAuthenticatedAs($usuario, 'web');
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_credenciales_incorrectas_e_inactividad_no_crean_sesion(): void
    {
        $this->crearUsuario(['activo' => false]);
        foreach (['ana.prueba', 'nadie'] as $nombre) {
            $this->postJson('/api/login', ['usuario' => $nombre, 'password' => 'secreto123'])
                ->assertUnauthorized()
                ->assertJsonPath('message', 'Usuario o contraseña incorrectos, o cuenta desactivada.');
            $this->assertGuest('web');
        }
    }

    public function test_password_incorrecta_no_ingresa_y_formulario_muestra_error(): void
    {
        $this->crearUsuario();
        $this->from('/login')->post('/login', ['usuario' => 'ana.prueba', 'password' => 'otra'])
            ->assertRedirect('/login')->assertSessionHasErrors('usuario');
        $this->assertGuest('web');
        $this->assertArrayNotHasKey('password', session()->getOldInput());
    }

    public function test_datos_incompletos_se_validan_en_html_y_json(): void
    {
        $this->post('/login', [])->assertSessionHasErrors(['usuario', 'password']);
        $this->postJson('/api/login', [])->assertUnprocessable()
            ->assertJsonValidationErrors(['usuario', 'password']);
    }

    public function test_logout_invalida_sesion_y_regenera_csrf(): void
    {
        $this->crearUsuario();
        $this->post('/login', ['usuario' => 'ana.prueba', 'password' => 'secreto123']);
        session()->put('dato_temporal', 'prueba');
        $csrf = session()->token();
        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest('web');
        $this->assertFalse(session()->has('dato_temporal'));
        $this->assertNotSame($csrf, session()->token());
        $this->get('/panel')->assertRedirect('/login');
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_logout_json_cierra_la_misma_sesion(): void
    {
        $this->actingAs($this->crearUsuario(), 'web')->postJson('/api/logout')
            ->assertOk()->assertJsonPath('message', 'Sesión cerrada.');
        $this->assertGuest('web');
    }

    public function test_todos_los_modulos_requieren_sesion(): void
    {
        $this->get('/panel')->assertRedirect('/login');
        foreach (['me', 'preguntas', 'simulacros', 'intentos/1', 'resultados'] as $ruta) {
            $this->getJson('/api/'.$ruta)->assertUnauthorized();
        }
    }

    public function test_cuenta_desactivada_despues_del_login_pierde_acceso(): void
    {
        $usuario = $this->crearUsuario();
        $this->actingAs($usuario, 'web');
        $usuario->update(['activo' => false]);
        $this->get('/panel')->assertForbidden();
        $this->getJson('/api/preguntas')->assertForbidden();
        // Siempre puede salir, incluso si su cuenta acaba de ser desactivada.
        $this->post('/logout')->assertRedirect('/login');
    }

    public function test_login_comparte_limite_entre_html_y_json(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['usuario' => 'nadie', 'password' => 'error']);
            $this->postJson('/api/login', ['usuario' => 'nadie', 'password' => 'error'])->assertUnauthorized();
        }
        $this->postJson('/api/login', ['usuario' => 'nadie', 'password' => 'error'])->assertStatus(429);
    }

    public function test_login_real_da_acceso_a_los_modulos_con_la_misma_sesion(): void
    {
        $usuario = $this->crearUsuario();
        $otro = $this->crearUsuario(['usuario' => 'otro']);
        $intento = DB::table('intentos')->insertGetId([
            'usuario_id' => $otro->id, 'tipo' => 'practica',
            'fecha_inicio' => now(), 'estado' => 'en_curso', 'total_preguntas' => 0,
        ]);
        $this->post('/login', ['usuario' => 'ana.prueba', 'password' => 'secreto123']);
        foreach (['preguntas', 'simulacros', 'resultados'] as $modulo) {
            $this->getJson('/api/'.$modulo)->assertOk();
        }
        $this->getJson('/api/intentos/'.$intento)->assertNotFound();
        $this->getJson('/api/resultados/'.$intento)->assertForbidden();
        $this->postJson('/api/preguntas', [])->assertForbidden();
        $this->postJson('/api/simulacros', [])->assertForbidden();
        $this->assertAuthenticatedAs($usuario, 'web');
    }

    public function test_bearer_no_sustituye_una_sesion_web(): void
    {
        $this->withToken('token-antiguo')->getJson('/api/me')->assertUnauthorized();
    }

    public function test_endpoints_de_modulos_conservan_web_csrf_y_guard_unico(): void
    {
        foreach (['/api/preguntas', '/api/simulacros', '/api/intentos', '/api/resultados/calificar'] as $uri) {
            $ruta = Route::getRoutes()->match(Request::create($uri, 'POST'));
            $middleware = $ruta->gatherMiddleware();
            $this->assertContains('web', $middleware);
            $this->assertContains('auth:web', $middleware);
            $this->assertContains(EnsureActiveUser::class, $middleware);
            $this->assertNotContains('auth:sanctum', $middleware);
        }
    }

    public function test_csrf_rechaza_login_y_operaciones_sin_token(): void
    {
        // Laravel omite CSRF al ejecutar tests; simular entorno web para este caso.
        $this->app['env'] = 'local';
        $this->post('/login', ['usuario' => 'nadie', 'password' => 'error'])->assertStatus(419);
        $this->postJson('/api/intentos', ['tipo' => 'simulacro'])->assertStatus(419);
        $this->post('/logout')->assertStatus(419);
    }
}
