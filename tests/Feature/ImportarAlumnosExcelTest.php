<?php

namespace Tests\Feature;

use App\Models\Seccion;
use App\Models\Usuario;
use App\Services\Docente\ResultadosSeccionService;
use App\Services\LectorAlumnosExcel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use ZipArchive;

class ImportarAlumnosExcelTest extends TestCase
{
    use RefreshDatabase;

    private array $archivos = [];
    private Seccion $seccion;
    private Usuario $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seccion = Seccion::create(['nombre' => 'A', 'grado' => '5to', 'activo' => true]);
        $this->admin = $this->cuenta('administrador', 'admin');
        $this->actingAs($this->admin, 'web');
    }

    protected function tearDown(): void
    {
        foreach ($this->archivos as $ruta) {
            @unlink($ruta);
        }
        parent::tearDown();
    }

    private function cuenta(string $usuario, string $rol): Usuario
    {
        return Usuario::create(['usuario' => $usuario, 'password' => 'ClaveCNI2026!', 'nombres' => 'Usuario', 'apellidos' => 'CNI', 'rol' => $rol, 'activo' => true, 'seccion_id' => $this->seccion->id]);
    }

    private function excel(array $filas = [], bool $compartidas = false): UploadedFile
    {
        $ruta = app(LectorAlumnosExcel::class)->plantilla();
        $this->archivos[] = $ruta;
        $zip = new ZipArchive;
        $zip->open($ruta);
        $xml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $contenido = '';
        $strings = '';
        $indice = 0;
        foreach ($filas as $n => $fila) {
            $numero = $n + 2;
            $contenido .= '<row r="'.$numero.'">';
            foreach ($fila as $i => $valor) {
                $valor = htmlspecialchars($valor, ENT_XML1, 'UTF-8');
                $ref = chr(65 + $i).$numero;
                if ($compartidas) {
                    $strings .= '<si><t>'.$valor.'</t></si>';
                    $contenido .= '<c r="'.$ref.'" t="s"><v>'.$indice++.'</v></c>';
                } else {
                    $contenido .= '<c r="'.$ref.'" t="inlineStr"><is><t>'.$valor.'</t></is></c>';
                }
            }
            $contenido .= '</row>';
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', str_replace('</sheetData>', $contenido.'</sheetData>', $xml));
        if ($compartidas) {
            $zip->addFromString('xl/sharedStrings.xml', '<sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.$strings.'</sst>');
        }
        $zip->close();
        return new UploadedFile($ruta, 'alumnos.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);
    }

    private function fila(string $nombres = 'José María', string $seccion = 'A'): array
    {
        return ['00123456', 'Peña Huamán', $nombres, '5to', $seccion];
    }

    private function validar(UploadedFile $archivo): string
    {
        return $this->postJson(route('admin.alumnos.validar'), ['archivo' => $archivo])->assertOk()->assertJsonPath('total', 1)->json('validacion');
    }

    public function test_importa_excel_real_y_conecta_login_seccion_y_lista_docente(): void
    {
        $archivo = $this->excel([$this->fila()], true);
        $token = $this->validar($archivo);
        $this->assertDatabaseMissing('usuarios', ['usuario' => 'Jose_Pena']);
        $this->post(route('admin.alumnos.cargar'), ['archivo' => $archivo, 'validacion' => $token])->assertRedirect(route('admin.alumnos.index'));
        $alumno = Usuario::where('usuario', 'Jose_Pena')->firstOrFail();
        $this->assertSame('José María', $alumno->nombres);
        $this->assertSame('Peña Huamán', $alumno->apellidos);
        $this->assertSame($this->seccion->id, $alumno->seccion_id);
        $this->assertSame('estudiante', $alumno->rol);
        $this->assertTrue($alumno->activo);
        $this->assertTrue(Hash::check('00123456', $alumno->password));
        $this->assertNotSame('00123456', $alumno->password);
        $docente = $this->cuenta('docente_a', 'docente');
        $listado = app(ResultadosSeccionService::class)->alumnos($docente);
        $this->assertTrue($listado->contains('estudiante_id', $alumno->id));
        $this->get(route('admin.alumnos.index'))->assertOk()->assertSee('José María')->assertDontSee('00123456');
        $this->post('/logout');
        $this->post('/login', ['usuario' => 'Jose_Pena', 'password' => '00123456'])->assertRedirect(route('alumno.elegir-area'));
        $this->assertAuthenticatedAs($alumno, 'web');
    }

    public function test_permisos_en_pantalla_plantilla_validacion_e_importacion(): void
    {
        $this->get(route('admin.alumnos.index'))->assertOk()->assertSee('Confirmar importación');
        $this->get(route('admin.alumnos.plantilla'))->assertOk()->assertDownload('Plantilla_Alumnos_CNI.xlsx');
        foreach (['docente', 'estudiante'] as $rol) {
            $this->actingAs($this->cuenta('cuenta_'.$rol, $rol), 'web');
            $this->get(route('admin.alumnos.index'))->assertForbidden();
            $this->get(route('admin.alumnos.plantilla'))->assertForbidden();
            $this->postJson(route('admin.alumnos.validar'))->assertForbidden();
            $this->postJson(route('admin.alumnos.cargar'))->assertForbidden();
        }
    }

    public function test_rechaza_duplicados_del_archivo_y_cuentas_existentes_sin_modificarlas(): void
    {
        $archivo = $this->excel([$this->fila(), $this->fila()]);
        $this->postJson(route('admin.alumnos.validar'), ['archivo' => $archivo])->assertUnprocessable()->assertJsonValidationErrors('archivo');
        $existente = $this->cuenta('Jose_Pena', 'estudiante');
        $existente->update(['nombres' => 'José María', 'apellidos' => 'Peña Huamán']);
        $archivo = $this->excel([$this->fila()]);
        $this->postJson(route('admin.alumnos.validar'), ['archivo' => $archivo])->assertUnprocessable();
        $this->assertSame('admin', $this->admin->fresh()->rol);
        $this->assertDatabaseCount('usuarios', 2);
    }

    public function test_rechaza_filas_incompletas_secciones_inexistentes_inactivas_y_plantilla_vacia(): void
    {
        foreach ([[], [$this->fila('nuevo', 'Z')], [['corta', '', 'Ana', '5to', 'A']]] as $filas) {
            $this->postJson(route('admin.alumnos.validar'), ['archivo' => $this->excel($filas)])->assertUnprocessable()->assertJsonValidationErrors('archivo');
        }
        $this->seccion->update(['activo' => false]);
        $this->postJson(route('admin.alumnos.validar'), ['archivo' => $this->excel([$this->fila()])])->assertUnprocessable();
        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_confirmacion_revalida_todo_y_no_deja_una_importacion_parcial(): void
    {
        $archivo = $this->excel([$this->fila('Ana'), $this->fila('Juan')]);
        $respuesta = $this->postJson(route('admin.alumnos.validar'), ['archivo' => $archivo])->assertOk();
        $this->cuenta('Juan_Pena', 'docente');
        $this->postJson(route('admin.alumnos.cargar'), ['archivo' => $archivo, 'validacion' => $respuesta->json('validacion')])->assertUnprocessable();
        $this->assertDatabaseMissing('usuarios', ['usuario' => 'Ana_Pena']);
        $this->assertDatabaseHas('usuarios', ['usuario' => 'Juan_Pena', 'rol' => 'docente']);
    }

    public function test_no_permite_importar_sin_validar_ni_cambiar_el_archivo_o_el_usuario(): void
    {
        $archivo = $this->excel([$this->fila()]);
        $this->postJson(route('admin.alumnos.cargar'), ['archivo' => $archivo])->assertUnprocessable();
        $token = $this->validar($archivo);
        $otro = $this->excel([$this->fila('otro')]);
        $this->postJson(route('admin.alumnos.cargar'), ['archivo' => $otro, 'validacion' => $token])->assertUnprocessable();
        $this->actingAs($this->cuenta('otro_admin', 'admin'), 'web');
        $this->postJson(route('admin.alumnos.cargar'), ['archivo' => $archivo, 'validacion' => $token])->assertUnprocessable();
        $this->assertDatabaseMissing('usuarios', ['usuario' => 'Jose_Pena']);
    }

    public function test_validacion_vencida_y_seccion_desactivada_despues_de_validar_no_importan(): void
    {
        $archivo = $this->excel([$this->fila()]);
        $token = $this->validar($archivo);
        $datos = json_decode(Crypt::decryptString($token), true);
        $datos['vence'] = now()->subMinute()->timestamp;
        $this->postJson(route('admin.alumnos.cargar'), ['archivo' => $archivo, 'validacion' => Crypt::encryptString(json_encode($datos))])->assertUnprocessable();
        $this->seccion->update(['activo' => false]);
        $this->postJson(route('admin.alumnos.cargar'), ['archivo' => $archivo, 'validacion' => $token])->assertUnprocessable();
        $this->assertDatabaseCount('usuarios', 1);
    }
    public function test_genera_nombre_apellido_y_sufijos_sin_modificar_cuentas_existentes(): void
    {
        $ocupado = $this->cuenta('JOSE_PENA', 'docente');
        $archivo = $this->excel([$this->fila(), $this->fila('José Luis')]);
        $respuesta = $this->postJson(route('admin.alumnos.validar'), ['archivo' => $archivo])
            ->assertOk()->assertJsonPath('usuarios.0.usuario', 'Jose_Pena_2')->assertJsonPath('usuarios.1.usuario', 'Jose_Pena_3');
        $this->post(route('admin.alumnos.cargar'), ['archivo' => $archivo, 'validacion' => $respuesta->json('validacion')])->assertRedirect(route('admin.alumnos.index'));
        $this->assertDatabaseHas('usuarios', ['usuario' => 'Jose_Pena_2', 'nombres' => 'José María']);
        $this->assertDatabaseHas('usuarios', ['usuario' => 'Jose_Pena_3', 'nombres' => 'José Luis']);
        $this->assertSame('docente', $ocupado->fresh()->rol);
        $this->postJson(route('admin.alumnos.validar'), ['archivo' => $archivo])->assertUnprocessable();
        $this->assertDatabaseCount('usuarios', 4);
    }

    public function test_la_plantilla_ya_no_pide_usuario_y_los_nombres_invalidos_no_crean_cuentas(): void
    {
        $archivo = $this->excel([$this->fila('12345')]);
        $filas = app(LectorAlumnosExcel::class)->leer($archivo->getRealPath());
        $this->assertArrayNotHasKey('usuario', $filas[0]);
        $this->postJson(route('admin.alumnos.validar'), ['archivo' => $archivo])->assertUnprocessable();
        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_dni_obligatorio_de_ocho_digitos_sin_letras_y_sin_notacion_cientifica(): void
    {
        foreach (['', '1234567', '123456789', '1234567A', '1.23E+07'] as $dni) {
            $fila = $this->fila();
            $fila[0] = $dni;
            $this->postJson(route('admin.alumnos.validar'), ['archivo' => $this->excel([$fila])])
                ->assertUnprocessable()->assertJsonValidationErrors('archivo');
        }
        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_dni_se_lee_como_texto_y_no_se_expone_en_la_vista_previa(): void
    {
        $archivo = $this->excel([$this->fila()]);
        $filas = app(LectorAlumnosExcel::class)->leer($archivo->getRealPath());
        $this->assertSame('00123456', $filas[0]['dni']);
        $this->assertArrayNotHasKey('contrasena', $filas[0]);
        $respuesta = $this->postJson(route('admin.alumnos.validar'), ['archivo' => $archivo])->assertOk();
        $this->assertArrayNotHasKey('dni', $respuesta->json('usuarios.0'));
        $this->assertStringNotContainsString('00123456', $respuesta->getContent());
    }

}
