<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InicioAlumnoTest extends TestCase
{
    use RefreshDatabase;

    public function test_pide_iniciar_sesion(): void
    {
        $this->get(route('alumno.inicio'))->assertRedirect(route('login'));
    }

    public function test_solo_los_alumnos_pueden_entrar(): void
    {
        $docente = Usuario::create([
            'nombres' => 'Luis', 'apellidos' => 'Docente', 'usuario' => 'ldocente',
            'password' => 'PruebaInicio123!', 'rol' => 'docente', 'activo' => true,
        ]);

        $this->actingAs($docente, 'web')->get(route('alumno.inicio'))->assertForbidden();
    }

    public function test_el_login_del_alumno_lleva_a_su_inicio(): void
    {
        $this->alumno();

        $this->post(route('login.store'), ['usuario' => 'aquispe', 'password' => 'PruebaInicio123!'])
            ->assertRedirect(route('alumno.inicio'));
    }

    public function test_las_tarjetas_de_temas_tienen_flecha(): void
    {
        $areaId = DB::table('areas')->insertGetId(['nombre' => 'Derecho', 'bloque' => 'conocimientos']);
        DB::table('temas')->insert(['area_id' => $areaId, 'nombre' => 'Derecho Civil']);

        $this->actingAs($this->alumno(), 'web')
            ->get(route('alumno.inicio'))
            ->assertSee('class="inicio-tema__ir"', false)
            ->assertSee('aria-label="Practicar Derecho Civil"', false);
    }

    public function test_carga_con_ceros_si_el_alumno_no_tiene_datos(): void
    {
        $this->actingAs($this->alumno(), 'web')
            ->get(route('alumno.inicio'))
            ->assertOk()
            ->assertSee('¡Hola, Ana!')
            ->assertSee('>AQ<', false)                       // avatar con iniciales
            ->assertSee('aria-valuenow="0"', false)
            ->assertSeeText('0 días seguidos')
            ->assertSee('Todavía no hay temas registrados.')
            ->assertSee('class="portal-menu__enlace is-activo"', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('css/alumno.css');
    }

    public function test_calcula_progreso_y_temas_desde_la_base_de_datos(): void
    {
        $alumno = $this->alumno();
        $areaId = DB::table('areas')->insertGetId(['nombre' => 'Derecho', 'bloque' => 'conocimientos']);
        $civil = DB::table('temas')->insertGetId(['area_id' => $areaId, 'nombre' => 'Derecho Civil']);
        $penal = DB::table('temas')->insertGetId(['area_id' => $areaId, 'nombre' => 'Derecho Penal']);

        // Derecho Civil: 2 preguntas; Derecho Penal: 2 preguntas. Total del banco: 4.
        [$p1, $a1] = $this->pregunta($civil);
        [$p2, $a2] = $this->pregunta($civil);
        $this->pregunta($penal);
        $this->pregunta($penal);

        $intento = DB::table('intentos')->insertGetId([
            'usuario_id' => $alumno->id, 'tipo' => 'practica', 'fecha_inicio' => now(),
            'estado' => 'finalizado', 'total_preguntas' => 2,
        ]);
        // Responde hoy las dos preguntas de Civil: una correcta y una incorrecta.
        DB::table('respuestas_estudiante')->insert([
            ['intento_id' => $intento, 'pregunta_id' => $p1, 'orden' => 1, 'alternativa_id' => $a1,
                'estado_respuesta' => 'correcta', 'respondida_en' => now()],
            ['intento_id' => $intento, 'pregunta_id' => $p2, 'orden' => 2, 'alternativa_id' => $a2,
                'estado_respuesta' => 'incorrecta', 'respondida_en' => now()],
        ]);

        $this->actingAs($alumno, 'web')
            ->get(route('alumno.inicio'))
            ->assertOk()
            ->assertViewHas('progreso', fn (array $p) => $p['resueltas'] === 2 && $p['correctas'] === 1
                && $p['porcentaje'] === 50 && $p['acierto'] === 50)
            ->assertViewHas('racha', fn (array $r) => $r['dias'] === 1)
            ->assertSeeInOrder(['Derecho Civil', '2/2 preguntas', 'Derecho Penal', '0/2 preguntas']);
    }

    private function alumno(): Usuario
    {
        return Usuario::create([
            'nombres' => 'ana lucía', 'apellidos' => 'Quispe Rojas', 'usuario' => 'aquispe',
            'password' => 'PruebaInicio123!', 'rol' => 'estudiante', 'activo' => true,
        ]);
    }

    /** Crea una pregunta activa con una alternativa y devuelve [pregunta_id, alternativa_id]. */
    private function pregunta(int $temaId): array
    {
        $preguntaId = DB::table('preguntas')->insertGetId([
            'tema_id' => $temaId, 'enunciado' => '¿Pregunta?', 'nivel' => 'basico', 'solucion' => '-',
        ]);
        $alternativaId = DB::table('alternativas')->insertGetId([
            'pregunta_id' => $preguntaId, 'letra' => 'A', 'texto' => 'Opción', 'es_correcta' => true,
        ]);

        return [$preguntaId, $alternativaId];
    }
}
