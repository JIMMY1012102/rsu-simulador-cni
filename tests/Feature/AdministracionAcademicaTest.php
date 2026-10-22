<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\AreaPostulacionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
        $this->get(route('admin.inicio'))->assertOk()->assertSee('Panel del administrador');
        $this->get(route('admin.preguntas.create'))->assertOk();
        $this->get(route('admin.simulacros.create'))->assertOk();

        foreach (['estudiante', 'docente'] as $rol) {
            $this->actingAs($this->usuario($rol), 'web');
            $this->get(route('admin.inicio'))->assertForbidden();
            $this->get(route('admin.preguntas.index'))->assertForbidden();
            $this->post(route('admin.preguntas.store'), $this->datosPregunta())->assertForbidden();
        }
        $this->post(route('admin.simulacros.store'), [])->assertForbidden();
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

    public function test_imagen_valida_se_guarda_y_su_ruta_se_vincula_a_la_pregunta(): void
    {
        Storage::fake('public');
        $this->actingAs($this->usuario('admin'), 'web');
        $datos = $this->datosPregunta([
            'imagen_archivo' => UploadedFile::fake()->image('figura.png', 300, 200),
        ]);
        $this->post(route('admin.preguntas.store'), $datos)->assertRedirect();
        $pregunta = DB::table('preguntas')->first();
        $this->assertStringStartsWith('storage/preguntas/', $pregunta->imagen);
        Storage::disk('public')->assertExists(substr($pregunta->imagen, strlen('storage/')));
        $this->assertDatabaseHas('alternativas', ['pregunta_id' => $pregunta->id, 'letra' => 'C', 'es_correcta' => true]);
        $this->assertSame(4, DB::table('alternativas')->where('pregunta_id', $pregunta->id)->count());
        $this->get(route('admin.preguntas.edit', $pregunta->id))->assertOk()->assertSee('Imagen actual');
    }

    public function test_archivos_peligrosos_y_alternativas_incompletas_se_rechazan(): void
    {
        Storage::fake('public');
        $this->actingAs($this->usuario('admin'), 'web');
        $this->post(route('admin.preguntas.store'), $this->datosPregunta([
            'imagen_archivo' => UploadedFile::fake()->create('ejecutable.php', 2, 'application/x-php'),
        ]))->assertSessionHasErrors('imagen_archivo');
        $this->post(route('admin.preguntas.store'), $this->datosPregunta([
            'alternativas' => ['A' => '1', 'B' => '2'],
        ]))->assertSessionHasErrors('alternativas');
        $this->assertDatabaseCount('preguntas', 0);
    }

    public function test_no_se_puede_editar_una_pregunta_ya_utilizada(): void
    {
        $this->actingAs($admin = $this->usuario('admin'), 'web');
        $this->post(route('admin.preguntas.store'), $this->datosPregunta());
        $id = DB::table('preguntas')->value('id');
        // La regla de inmutabilidad se prueba al usar una respuesta vinculada.
        $simulacro = DB::table('simulacros')->insertGetId([
            'nombre' => 'Simulacro de prueba', 'tiempo_limite' => 60, 'total_preguntas' => 1,
            'fecha_inicio' => now()->subDay(), 'fecha_fin' => now()->addDay(),
            'activo' => true, 'creado_por' => $admin->id,
        ]);
        $intento = DB::table('intentos')->insertGetId([
            'usuario_id' => $admin->id, 'simulacro_id' => $simulacro,
            'tipo' => 'simulacro', 'fecha_inicio' => now(), 'estado' => 'en_curso',
            'total_preguntas' => 1,
        ]);
        DB::table('respuestas_estudiante')->insert([
            'intento_id' => $intento, 'pregunta_id' => $id, 'orden' => 1,
        ]);
        $this->post(route('admin.preguntas.update', $id), $this->datosPregunta([
            'enunciado' => 'No se debe modificar',
            '_method' => 'PUT',
        ]))->assertSessionHasErrors('pregunta');
        $this->assertDatabaseHas('preguntas', ['id' => $id, 'enunciado' => 'Calcula 2 + 2']);
    }
}
