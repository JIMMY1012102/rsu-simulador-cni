<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Páginas del menú del alumno: Mis intentos, Banco, Temas, Historial, Mi perfil y la campana. */
class PortalAlumnoTest extends TestCase
{
    use RefreshDatabase;

    private Usuario $alumno;

    protected function setUp(): void
    {
        parent::setUp();
        $this->alumno = Usuario::create([
            'nombres' => 'Ana', 'apellidos' => 'Quispe', 'usuario' => 'aquispe',
            'password' => 'PruebaPortal123', 'rol' => 'estudiante', 'activo' => true,
        ]);
    }

    public function test_todas_las_paginas_cargan_y_marcan_su_opcion_del_menu(): void
    {
        foreach (['alumno.intentos', 'alumno.preguntas', 'alumno.temas', 'alumno.historial', 'alumno.perfil'] as $ruta) {
            $this->actingAs($this->alumno, 'web')->get(route($ruta))
                ->assertOk()
                ->assertSee('href="'.route($ruta).'" class="portal-menu__enlace is-activo"', false);
        }
    }

    public function test_las_paginas_son_solo_para_alumnos(): void
    {
        $docente = Usuario::create([
            'nombres' => 'Luis', 'apellidos' => 'Rojas', 'usuario' => 'lrojas',
            'password' => 'PruebaPortal123', 'rol' => 'docente', 'activo' => true,
        ]);

        $this->actingAs($docente, 'web')->get(route('alumno.historial'))->assertForbidden();
        $this->actingAs($docente, 'web')->get(route('alumno.perfil'))->assertForbidden();
    }

    public function test_mis_intentos_muestra_simulacros_abiertos_y_la_campana_avisa(): void
    {
        $abierto = $this->simulacro('Simulacro de octubre', now()->subDay(), now()->addDay());
        $this->simulacro('Simulacro de noviembre', now()->addWeek(), now()->addWeeks(2));

        $this->actingAs($this->alumno, 'web')->get(route('alumno.intentos'))
            ->assertOk()
            ->assertSee('Simulacro de octubre')
            ->assertSee('action="'.route('examen.iniciar', $abierto).'"', false)
            ->assertSee('Iniciar simulacro')
            ->assertSee('Próximos simulacros')
            ->assertSee('Simulacro de noviembre')
            // Campana: simulacro nuevo que el alumno aún no rindió.
            ->assertSee('Nuevo simulacro disponible')
            ->assertSee('derecho-campana__punto', false);
    }

    public function test_un_examen_en_curso_se_ofrece_para_continuar(): void
    {
        $simulacro = $this->simulacro('Simulacro de octubre', now()->subDay(), now()->addDay());
        $intento = $this->intento($simulacro, 'en_curso', ['fecha_limite' => now()->addHour()]);

        $this->actingAs($this->alumno, 'web')->get(route('alumno.intentos'))
            ->assertSee('Continuar examen')
            ->assertSee('Tienes un examen sin terminar')
            ->assertSee(route('examen', $intento), false);
    }

    public function test_historial_lista_solo_examenes_terminados_propios_con_detalle_por_area(): void
    {
        $simulacro = $this->simulacro('Simulacro de octubre', now()->subDay(), now()->addDay());
        $terminado = $this->intento($simulacro, 'finalizado', [
            'correctas' => 7, 'incorrectas' => 2, 'en_blanco' => 1, 'doble_marca' => 0, 'puntaje_total' => 137.75,
        ]);
        $this->intento($simulacro, 'en_curso');
        $areaId = DB::table('areas')->insertGetId(['nombre' => 'Razonamiento verbal', 'bloque' => 'habilidad_verbal']);
        DB::table('resultados_area')->insert([
            'intento_id' => $terminado, 'area_id' => $areaId, 'total_preguntas' => 10, 'aciertos' => 7,
            'incorrectas' => 2, 'en_blanco' => 1, 'puntaje' => 137.75, 'porcentaje' => 70,
        ]);

        $this->actingAs($this->alumno, 'web')->get(route('alumno.historial'))
            ->assertOk()
            ->assertSee('137.750')
            ->assertSee(route('alumno.historial.detalle', $terminado), false);

        $this->get(route('alumno.historial.detalle', $terminado))
            ->assertOk()
            ->assertSee('Razonamiento verbal')
            ->assertSee('7/10 correctas');

        // El intento de otro alumno no se puede ver.
        $otro = Usuario::create([
            'nombres' => 'Rosa', 'apellidos' => 'Díaz', 'usuario' => 'rdiaz',
            'password' => 'PruebaPortal123', 'rol' => 'estudiante', 'activo' => true,
        ]);
        $this->actingAs($otro, 'web')->get(route('alumno.historial.detalle', $terminado))->assertNotFound();
    }

    public function test_banco_cuenta_preguntas_por_tema_y_nivel_sin_mostrar_respuestas(): void
    {
        $areaId = DB::table('areas')->insertGetId(['nombre' => 'Derecho', 'bloque' => 'conocimientos']);
        $temaId = DB::table('temas')->insertGetId(['area_id' => $areaId, 'nombre' => 'Derecho Civil']);
        foreach (['basico', 'basico', 'avanzado'] as $nivel) {
            DB::table('preguntas')->insert([
                'tema_id' => $temaId, 'enunciado' => 'Enunciado secreto', 'nivel' => $nivel, 'solucion' => 'Solución secreta',
            ]);
        }

        $this->actingAs($this->alumno, 'web')->get(route('alumno.preguntas'))
            ->assertOk()
            ->assertSee('3 preguntas disponibles')
            ->assertSeeInOrder(['Derecho Civil', '2', '0', '1', '3'])
            ->assertDontSee('Enunciado secreto')
            ->assertDontSee('Solución secreta');
    }

    public function test_temas_agrupa_por_area(): void
    {
        $areaId = DB::table('areas')->insertGetId(['nombre' => 'Derecho', 'bloque' => 'conocimientos']);
        DB::table('temas')->insert(['area_id' => $areaId, 'nombre' => 'Derecho Penal']);

        $this->actingAs($this->alumno, 'web')->get(route('alumno.temas'))
            ->assertOk()
            ->assertSeeInOrder(['Derecho', 'Derecho Penal', '0/0 preguntas']);
    }

    public function test_el_alumno_edita_su_nombre_y_se_ve_en_toda_la_pagina(): void
    {
        $this->actingAs($this->alumno, 'web')
            ->put(route('alumno.perfil.datos'), ['nombres' => 'Dafne', 'apellidos' => 'Chamorro'])
            ->assertRedirect(route('alumno.perfil'));

        $this->assertSame('Dafne', $this->alumno->fresh()->nombres);
        $this->get(route('alumno.inicio'))->assertSee('¡Hola, Dafne!')->assertSee('Dafne Chamorro');
    }

    public function test_los_datos_vacios_muestran_error_en_espanol(): void
    {
        $this->actingAs($this->alumno, 'web')->from(route('alumno.perfil'))
            ->put(route('alumno.perfil.datos'), ['nombres' => '', 'apellidos' => 'Quispe'])
            ->assertRedirect(route('alumno.perfil'))
            ->assertSessionHasErrorsIn('datos', ['nombres' => 'Este campo es obligatorio.']);
    }

    public function test_cambiar_contrasena_pide_la_actual(): void
    {
        $this->actingAs($this->alumno, 'web')->from(route('alumno.perfil'))
            ->put(route('alumno.perfil.password'), [
                'password_actual' => 'incorrecta', 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
            ])
            ->assertSessionHasErrorsIn('password', ['password_actual' => 'La contraseña actual no es correcta.']);

        $this->put(route('alumno.perfil.password'), [
            'password_actual' => 'PruebaPortal123', 'password' => 'NuevaClave2026', 'password_confirmation' => 'NuevaClave2026',
        ])->assertRedirect(route('alumno.perfil'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('NuevaClave2026', $this->alumno->fresh()->password));
    }

    private function simulacro(string $nombre, $inicio, $fin): int
    {
        return DB::table('simulacros')->insertGetId([
            'nombre' => $nombre, 'tiempo_limite' => 60, 'total_preguntas' => 10, 'cantidad_alternativas' => 5,
            'puntaje_correcta' => 20, 'puntaje_incorrecta' => -1.125, 'puntaje_blanco' => 0,
            'fecha_inicio' => $inicio, 'fecha_fin' => $fin, 'activo' => true,
        ]);
    }

    private function intento(int $simulacroId, string $estado, array $extra = []): int
    {
        return DB::table('intentos')->insertGetId($extra + [
            'usuario_id' => $this->alumno->id, 'simulacro_id' => $simulacroId, 'tipo' => 'simulacro',
            'fecha_inicio' => now(), 'estado' => $estado, 'total_preguntas' => 10,
        ]);
    }
}
