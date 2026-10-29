<?php

namespace Tests\Feature;

use App\Console\Commands\ImportarBancoExcel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class ImportarBancoExcelTest extends TestCase
{
    use RefreshDatabase;

    /** PNG de 1×1 píxel. */
    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';

    private string $archivo;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->archivo = $this->crearExcel();
    }

    protected function tearDown(): void
    {
        @unlink($this->archivo);
        parent::tearDown();
    }

    public function test_importa_preguntas_con_imagen_alternativas_y_respuesta(): void
    {
        $this->artisan('banco:importar', ['archivo' => $this->archivo])
            ->expectsOutputToContain('Importadas 2 preguntas (1 con respuesta, 1 pendientes de respuesta).')
            ->assertSuccessful();

        $pregunta = DB::table('preguntas')->where('codigo', 'AM_1')->first();
        $this->assertSame('storage/banco/AM_1.png', $pregunta->imagen);
        Storage::disk('public')->assertExists('banco/AM_1.png');

        $alternativas = DB::table('alternativas')->where('pregunta_id', $pregunta->id)->orderBy('letra')->get();
        $this->assertSame(['40', '80', '58', '72', '120'], $alternativas->pluck('texto')->all());
        $this->assertSame(['B'], $alternativas->where('es_correcta', true)->pluck('letra')->values()->all());

        $tema = DB::table('temas')->where('id', $pregunta->tema_id)->first();
        $this->assertSame('Razonamiento matemático', $tema->nombre);

        $sinRespuesta = DB::table('preguntas')->where('codigo', 'LE_1')->value('id');
        $this->assertSame(0, DB::table('alternativas')->where('pregunta_id', $sinRespuesta)->where('es_correcta', true)->count());
    }

    public function test_volver_a_importar_no_duplica(): void
    {
        $this->artisan('banco:importar', ['archivo' => $this->archivo])->assertSuccessful();
        $this->artisan('banco:importar', ['archivo' => $this->archivo])->assertSuccessful();

        $this->assertSame(2, DB::table('preguntas')->whereNotNull('codigo')->count());
        $this->assertSame(10, DB::table('alternativas')->count());
    }

    public function test_el_simulacro_solo_usa_preguntas_con_respuesta(): void
    {
        DB::table('areas_postulacion')->insert(['codigo' => 'D', 'nombre' => 'Ciencias Económicas', 'activo' => true]);

        $this->artisan('banco:importar', ['archivo' => $this->archivo, '--simulacro' => true])->assertSuccessful();

        $simulacro = DB::table('simulacros')->where('nombre', ImportarBancoExcel::SIMULACRO)->first();
        $this->assertSame(1, (int) $simulacro->total_preguntas);
        $this->assertSame(5, (int) $simulacro->cantidad_alternativas);
        $this->assertSame(1, (int) DB::table('simulacro_area')->where('simulacro_id', $simulacro->id)->sum('cantidad_preguntas'));
        $this->assertSame(1, DB::table('area_postulacion_simulacro')->where('simulacro_id', $simulacro->id)->count());
    }

    public function test_archivo_inexistente_falla_con_mensaje(): void
    {
        $this->artisan('banco:importar', ['archivo' => 'no-existe.xlsx'])
            ->expectsOutputToContain('No existe el archivo')
            ->assertFailed();
    }

    /** Excel mínimo con el mismo formato del banco: título, encabezados, 2 preguntas y 1 imagen anclada. */
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
