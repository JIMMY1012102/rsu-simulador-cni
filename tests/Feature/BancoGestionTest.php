<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BancoGestionTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    protected function setUp(): void
    {
        parent::setUp();
        config(['cni.administracion_academica_enabled' => true]);
        Storage::fake('public');
    }

    private function usuario(string $rol = 'admin'): Usuario
    {
        return Usuario::create([
            'usuario' => 'banco_'.$rol, 'nombres' => 'Usuario', 'apellidos' => 'CNI',
            'password' => 'ClaveCNI2026!', 'rol' => $rol, 'activo' => true,
        ]);
    }

    private function archivo(): UploadedFile
    {
        $ruta = $this->crearExcel();
        $bytes = file_get_contents($ruta);
        unlink($ruta);

        return UploadedFile::fake()->createWithContent('banco.xlsx', $bytes)
            ->mimeType('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_menu_muestra_solo_las_tres_secciones_y_la_entrada_redirige_a_subtemas(): void
    {
        $this->actingAs($this->usuario(), 'web')
            ->get(route('admin.inicio'))->assertRedirect(route('admin.subtemas.index'));
        foreach (['admin.subtemas.index', 'admin.preguntas.index', 'admin.importar.index'] as $ruta) {
            $this->get(route($ruta))->assertOk()
                ->assertSee('Subtemas')->assertSee('Preguntas')->assertSee('Importar Excel')
                ->assertDontSee('Resumen general')->assertDontSee('Nuevo simulacro')->assertDontSee('Catálogos');
        }
    }

    public function test_descarga_la_plantilla_vacia_compatible_con_el_importador(): void
    {
        $this->actingAs($this->usuario(), 'web');
        $this->get(route('admin.importar.index'))->assertOk()
            ->assertSee('Descargar plantilla Excel')
            ->assertSee(route('admin.importar.plantilla'), false);
        $respuesta = $this->get(route('admin.importar.plantilla'));
        $respuesta->assertOk()->assertDownload('Plantilla_Banco_Preguntas_CNI.xlsx');
        $ruta = $respuesta->baseResponse->getFile()->getPathname();
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($ruta));
        $this->assertStringContainsString('Banco de preguntas', $zip->getFromName('xl/workbook.xml'));
        $this->assertStringContainsString('Instrucciones', $zip->getFromName('xl/workbook.xml'));
        $zip->close();
        $this->assertSame([], app(\App\Services\LectorBancoExcel::class)->leer($ruta));
    }

    public function test_importa_desde_el_panel_sin_crear_simulacros_y_reimportar_no_duplica(): void
    {
        $this->actingAs($this->usuario(), 'web');
        foreach ([1, 2] as $vez) {
            $this->post(route('admin.importar.cargar'), ['archivo' => $this->archivo()])
                ->assertRedirect(route('admin.importar.index'))->assertSessionHas('importacion');
        }
        $this->assertDatabaseCount('preguntas', 2);
        $this->assertDatabaseCount('alternativas', 10);
        $this->assertDatabaseCount('simulacros', 0);
        Storage::disk('public')->assertExists('banco/AM_1.png');
        $this->get(route('admin.subtemas.index'))->assertOk()
            ->assertSee('Habilidad Matemática')->assertSee('Con preguntas');
    }

    public function test_no_reemplaza_preguntas_que_ya_se_usaron_en_un_examen(): void
    {
        $admin = $this->usuario();
        $this->actingAs($admin, 'web')->post(route('admin.importar.cargar'), ['archivo' => $this->archivo()])
            ->assertRedirect(route('admin.importar.index'));
        $p = DB::table('preguntas')->where('codigo', 'AM_1')->first();
        $simulacro = DB::table('simulacros')->insertGetId([
            'nombre' => 'Prueba historial', 'tiempo_limite' => 60, 'total_preguntas' => 1,
            'fecha_inicio' => now()->subDay(), 'fecha_fin' => now()->addDay(),
        ]);
        $intento = DB::table('intentos')->insertGetId([
            'usuario_id' => $admin->id, 'simulacro_id' => $simulacro, 'tipo' => 'simulacro',
            'estado' => 'en_curso', 'total_preguntas' => 1, 'fecha_inicio' => now(),
        ]);
        DB::table('respuestas_estudiante')->insert([
            'intento_id' => $intento, 'pregunta_id' => $p->id, 'orden' => 1,
        ]);
        $this->from(route('admin.importar.index'))
            ->post(route('admin.importar.cargar'), ['archivo' => $this->archivo()])
            ->assertRedirect(route('admin.importar.index'))->assertSessionHasErrors('archivo');
        $this->assertDatabaseHas('preguntas', ['id' => $p->id, 'imagen' => $p->imagen]);
        $this->assertDatabaseCount('respuestas_estudiante', 1);
    }

    public function test_rechaza_archivos_invalidos_y_el_acceso_de_docentes_y_estudiantes(): void
    {
        $this->actingAs($this->usuario(), 'web')
            ->from(route('admin.importar.index'))->post(route('admin.importar.cargar'), [
                'archivo' => UploadedFile::fake()->create('banco.txt', 1, 'text/plain'),
            ])->assertSessionHasErrors('archivo');
        foreach (['docente', 'estudiante'] as $rol) {
            $this->actingAs($this->usuario($rol), 'web');
            $this->get(route('admin.subtemas.index'))->assertForbidden();
            $this->get(route('admin.importar.index'))->assertForbidden();
            $this->get(route('admin.importar.plantilla'))->assertForbidden();
            $this->post(route('admin.importar.cargar'), ['archivo' => $this->archivo()])->assertForbidden();
        }
    }

    private function crearExcel(): string
    {
        $celda = fn (string $ref, string $texto) => '<c r="'.$ref.'" t="inlineStr"><is><t>'.htmlspecialchars($texto).'</t></is></c>';
        $fila = function (int $n, array $valores) use ($celda) {
            $xml = '';
            foreach ($valores as $col => $valor) {
                $xml .= $celda($col.$n, $valor);
            }

            return '<row r="'.$n.'">'.$xml.'</row>';
        };
        $encabezado = ['A' => 'Nº', 'B' => 'Código', 'C' => 'Curso', 'D' => 'Pregunta (imagen)', 'E' => 'A', 'F' => 'B', 'G' => 'C', 'H' => 'D', 'I' => 'E', 'J' => 'Respuesta correcta'];

        $hoja = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheetData>'
            .$fila(1, ['A' => 'Banco de preguntas por curso'])
            .$fila(4, $encabezado)
            .$fila(5, ['A' => 'RAZONAMIENTO MATEMÁTICO (RM)'])
            .$fila(6, ['A' => '1', 'B' => 'AM_1', 'C' => 'Razonamiento matemático', 'E' => '40', 'F' => '80', 'G' => '58', 'H' => '72', 'I' => '120', 'J' => 'B'])
            .$fila(7, ['A' => '2', 'B' => 'LE_1', 'C' => 'Lenguaje', 'E' => 'uno', 'F' => 'dos', 'G' => 'tres', 'H' => 'cuatro', 'I' => 'cinco'])
            .'</sheetData><drawing r:id="rId1"/></worksheet>';

        // Como en el Excel real, la imagen empieza en la fila anterior y termina en la de la pregunta.
        $dibujo = '<xdr:wsDr xmlns:xdr="http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<xdr:twoCellAnchor><xdr:from><xdr:col>3</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>4</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:from>'
            .'<xdr:to><xdr:col>3</xdr:col><xdr:colOff>0</xdr:colOff><xdr:row>5</xdr:row><xdr:rowOff>0</xdr:rowOff></xdr:to>'
            .'<xdr:pic><xdr:blipFill><a:blip r:embed="rId1"/></xdr:blipFill></xdr:pic><xdr:clientData/></xdr:twoCellAnchor></xdr:wsDr>';

        $rels = fn (string $id, string $destino) => '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="'.$id.'" Type="x" Target="'.$destino.'"/></Relationships>';

        $ruta = tempnam(sys_get_temp_dir(), 'banco').'.xlsx';
        $zip = new ZipArchive;
        $zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Banco de preguntas" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', $rels('rId1', 'worksheets/sheet1.xml'));
        $zip->addFromString('xl/worksheets/sheet1.xml', $hoja);
        $zip->addFromString('xl/worksheets/_rels/sheet1.xml.rels', $rels('rId1', '../drawings/drawing1.xml'));
        $zip->addFromString('xl/drawings/drawing1.xml', $dibujo);
        $zip->addFromString('xl/drawings/_rels/drawing1.xml.rels', $rels('rId1', '../media/image1.png'));
        $zip->addFromString('xl/media/image1.png', base64_decode(self::PNG));
        $zip->close();

        return $ruta;
    }
}
