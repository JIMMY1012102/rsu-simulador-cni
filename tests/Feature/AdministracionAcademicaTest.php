<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\AreaPostulacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdministracionAcademicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AreaPostulacionSeeder::class);
    }

    private function usuario(string $rol): Usuario
    {
        return Usuario::create([
            'usuario' => 'academico_'.$rol, 'nombres' => 'Usuario', 'apellidos' => 'CNI',
            'password' => 'ClaveCNI2026!', 'rol' => $rol, 'activo' => true,
        ]);
    }

    private function catalogo(): int
    {
        DB::table('areas')->updateOrInsert(
            ['nombre' => 'Matemática'],
            ['bloque' => 'habilidad_logico_matematica', 'activo' => true]
        );
        $id = DB::table('areas')->where('nombre', 'Matemática')->value('id');
        DB::table('temas')->updateOrInsert(
            ['area_id' => $id, 'nombre' => 'Álgebra'],
            ['activo' => true]
        );
        return DB::table('temas')->where('area_id', $id)->where('nombre', 'Álgebra')->value('id');
    }

    private function datosPregunta(array $extra = []): array
    {
        return array_merge([
            'tema_id' => $this->catalogo(),
            'enunciado' => 'Calcula 2 + 2',
            'nivel' => 'basico', 'solucion' => 'La suma es cuatro.',
            'alternativas' => ['A' => '1', 'B' => '2', 'C' => '4', 'D' => '5'],
            'correcta' => 'C',
        ], $extra);
    }

    public function test_admin_accede_desde_login_y_docente_y_alumno_no_pueden_administrar(): void
    {
        $admin = $this->usuario('admin');
        $this->post('/login', ['usuario' => $admin->usuario, 'password' => 'ClaveCNI2026!'])
            ->assertRedirect(route('admin.inicio'));
        $this->get(route('admin.inicio'))->assertRedirect(route('admin.subtemas.index'));
        $this->get(route('admin.subtemas.index'))->assertOk()->assertSee('Categorías');
        $this->get(route('admin.preguntas.index'))->assertOk()->assertDontSee('Nueva pregunta');
        $this->get(route('admin.simulacros.create'))->assertOk();

        foreach (['estudiante', 'docente'] as $rol) {
            $this->actingAs($this->usuario($rol), 'web');
            $this->get(route('admin.inicio'))->assertForbidden();
            $this->get(route('admin.preguntas.index'))->assertForbidden();
            $this->post(route('admin.importar.cargar'), [])->assertForbidden();
        }
        $this->post(route('admin.simulacros.store'), [])->assertForbidden();
    }


    public function test_con_panel_academico_apagado_el_admin_conserva_su_login_original(): void
    {
        config(['cni.administracion_academica_enabled' => false]);
        $admin = $this->usuario('admin');
        $this->post('/login', ['usuario' => $admin->usuario, 'password' => 'ClaveCNI2026!'])
            ->assertRedirect(route('panel'));
        $this->get(route('panel'))->assertOk();
        $this->get(route('admin.inicio'))->assertNotFound();
    }

    public function test_admin_crea_carrera_asignatura_y_tema_desde_la_interfaz(): void
    {
        $this->actingAs($this->usuario('admin'), 'web');
        $area = DB::table('areas_postulacion')->where('codigo', 'C')->value('id');
        $this->post(route('admin.carrera.guardar'), [
            'area_postulacion_id' => $area, 'nombre' => 'Ingeniería de Sistemas',
        ])->assertRedirect();
        $this->post(route('admin.asignatura.guardar'), [
            'nombre' => 'Historia', 'bloque' => 'conocimientos',
        ])->assertRedirect();
        $asignatura = DB::table('areas')->where('nombre', 'Historia')->value('id');
        $this->post(route('admin.tema.guardar'), [
            'area_id' => $asignatura, 'nombre' => 'Independencia',
        ])->assertRedirect();
        $this->assertDatabaseHas('carreras', ['area_postulacion_id' => $area, 'nombre' => 'Ingeniería de Sistemas']);
        $this->assertDatabaseHas('temas', ['area_id' => $asignatura, 'nombre' => 'Independencia']);
    }

    public function test_solo_excel_permite_cargar_o_modificar_el_banco(): void
    {
        $this->actingAs($this->usuario('admin'), 'web');
        $this->get('/administracion/preguntas/nueva')->assertNotFound();
        $this->get('/administracion/preguntas/1/editar')->assertNotFound();
        $this->post('/administracion/preguntas', $this->datosPregunta())->assertStatus(405);
        $this->put('/administracion/preguntas/1', $this->datosPregunta())->assertStatus(405);
        $this->post('/api/preguntas', $this->datosPregunta())->assertStatus(405);
        $this->put('/api/preguntas/1', $this->datosPregunta())->assertStatus(405);
        $this->assertDatabaseCount('preguntas', 0);
        $this->get(route('admin.importar.index'))->assertOk()->assertSee('Importar preguntas');
    }
}
