<?php

namespace Tests\Feature;

use App\Livewire\Examen;
use App\Models\Usuario;
use App\Services\IntentoService;
use Database\Seeders\ExamenDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ExamenPantallaTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $alumno;

    private int $simulacro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(Carbon::parse('2026-10-21 09:00:00'));
        $this->seed(ExamenDemoSeeder::class);
        $this->alumno = $this->crearAlumno('alumno');
        $this->simulacro = DB::table('simulacros')->where('nombre', ExamenDemoSeeder::SIMULACRO)->value('id');
    }

    private function crearAlumno(string $usuario): Usuario
    {
        return Usuario::create([
            'nombres' => 'Juan Carlos', 'apellidos' => 'Pérez', 'usuario' => $usuario,
            'password' => 'PruebaExamen123!', 'rol' => 'estudiante', 'activo' => true,
        ]);
    }

    private function iniciar(): int
    {
        return app(IntentoService::class)
            ->iniciar($this->alumno->id, ['tipo' => 'simulacro', 'simulacro_id' => $this->simulacro])->id;
    }

    private function preguntas(int $intento)
    {
        return app(IntentoService::class)->preguntas($intento)->values();
    }

    public function test_iniciar_crea_el_intento_y_al_repetir_lo_reanuda(): void
    {
        $this->actingAs($this->alumno, 'web');

        $primera = $this->post(route('examen.iniciar', $this->simulacro));
        $intento = DB::table('intentos')->value('id');
        $primera->assertRedirect(route('examen', $intento));

        $this->post(route('examen.iniciar', $this->simulacro))->assertRedirect(route('examen', $intento));
        $this->assertDatabaseCount('intentos', 1);
    }

    public function test_iniciar_un_simulacro_no_disponible_vuelve_con_error(): void
    {
        DB::table('simulacros')->where('id', $this->simulacro)->update(['activo' => false]);

        $this->actingAs($this->alumno, 'web')->from(route('panel'))
            ->post(route('examen.iniciar', $this->simulacro))
            ->assertRedirect(route('panel'))->assertSessionHasErrors('simulacro_id');
    }

    public function test_muestra_la_pregunta_con_su_area_sin_revelar_la_clave(): void
    {
        $intento = $this->iniciar();
        $primera = $this->preguntas($intento)->first();

        $this->actingAs($this->alumno, 'web')->get(route('examen', $intento))->assertOk()
            ->assertSee('Simulador de examen')
            ->assertSee($primera->enunciado)
            ->assertSee($primera->area)
            ->assertSee('0 de 40 respondidas')
            ->assertSee('JUAN')
            ->assertSee('js/examen.js')
            ->assertDontSee('La respuesta correcta es')
            ->assertDontSee('es_correcta');
    }

    public function test_solo_el_dueno_puede_abrir_su_examen(): void
    {
        $intento = $this->iniciar();

        $this->actingAs($this->crearAlumno('otro'), 'web')->get(route('examen', $intento))->assertNotFound();
    }

    public function test_un_invitado_va_al_login(): void
    {
        $this->get(route('examen', $this->iniciar()))->assertRedirect(route('login'));
    }

    public function test_un_intento_cerrado_redirige_al_panel_con_aviso(): void
    {
        $intento = $this->iniciar();
        app(IntentoService::class)->finalizar($intento, $this->alumno->id);

        $this->actingAs($this->alumno, 'web')->get(route('examen', $intento))
            ->assertRedirect(route('panel'))->assertSessionHasErrors('intento');
    }

    public function test_responder_guarda_y_navegar_cambia_de_pregunta(): void
    {
        $intento = $this->iniciar();
        $preguntas = $this->preguntas($intento);
        $alternativa = $preguntas[0]->alternativas[2]->id;

        Livewire::actingAs($this->alumno, 'web')->test(Examen::class, ['intento' => $intento])
            ->assertSet('actual', 0)
            ->call('responder', $alternativa)
            ->assertSee('Respuesta guardada')
            ->assertSee('1 de 40 respondidas')
            ->call('siguiente')
            ->assertSet('actual', 1)
            ->assertSee($preguntas[1]->enunciado)
            ->call('ir', 39)
            ->assertSee('Revisar y enviar')
            ->call('ir', 99)
            ->assertSet('actual', 39)
            ->call('anterior')
            ->assertSet('actual', 38);

        $this->assertSame($alternativa, (int) DB::table('respuestas_estudiante')
            ->where('intento_id', $intento)->where('pregunta_id', $preguntas[0]->id)->value('alternativa_id'));
    }

    public function test_al_reanudar_abre_la_primera_pregunta_sin_responder(): void
    {
        $intento = $this->iniciar();
        $preguntas = $this->preguntas($intento);
        foreach ([0, 1] as $i) {
            app(IntentoService::class)->guardarRespuesta($intento, $this->alumno->id, [
                'pregunta_id' => $preguntas[$i]->id, 'alternativa_id' => $preguntas[$i]->alternativas[0]->id,
            ]);
        }

        Livewire::actingAs($this->alumno, 'web')->test(Examen::class, ['intento' => $intento])
            ->assertSet('actual', 2)->assertSee('2 de 40 respondidas');
    }

    public function test_finalizar_envia_el_examen_y_vuelve_al_panel(): void
    {
        $intento = $this->iniciar();

        Livewire::actingAs($this->alumno, 'web')->test(Examen::class, ['intento' => $intento])
            ->call('finalizar')
            ->assertRedirect(route('panel'));

        $this->assertSame('finalizado', DB::table('intentos')->where('id', $intento)->value('estado'));
        $this->assertNotNull(DB::table('intentos')->where('id', $intento)->value('puntaje_total'));
    }

    public function test_si_el_tiempo_termino_no_guarda_y_envia_automaticamente(): void
    {
        $intento = $this->iniciar();
        $pregunta = $this->preguntas($intento)->first();
        $componente = Livewire::actingAs($this->alumno, 'web')->test(Examen::class, ['intento' => $intento]);

        $this->travel(61)->minutes();
        $componente->call('responder', $pregunta->alternativas[0]->id)->assertRedirect(route('panel'));

        $this->assertSame('enviado_auto', DB::table('intentos')->where('id', $intento)->value('estado'));
        $this->assertSame(0, DB::table('respuestas_estudiante')->where('intento_id', $intento)->whereNotNull('alternativa_id')->count());
    }

    public function test_el_temporizador_del_navegador_no_cierra_antes_de_tiempo(): void
    {
        $intento = $this->iniciar();

        Livewire::actingAs($this->alumno, 'web')->test(Examen::class, ['intento' => $intento])
            ->call('tiempoAgotado')->assertNoRedirect();

        $this->assertSame('en_curso', DB::table('intentos')->where('id', $intento)->value('estado'));
    }
}
