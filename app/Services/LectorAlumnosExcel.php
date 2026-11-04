<?php

namespace App\Services;

use Illuminate\Support\Str;
use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class LectorAlumnosExcel
{
    private const NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    public const COLUMNAS = ['dni', 'apellidos', 'nombres', 'grado', 'seccion'];

    public function leer(string $ruta): array
    {
        $zip = new ZipArchive;
        if ($zip->open($ruta) !== true) {
            throw new RuntimeException('No se pudo abrir el Excel.');
        }
        try {
            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $total += $zip->statIndex($i)['size'];
                if ($total > 20 * 1024 * 1024 || $zip->numFiles > 1000) {
                    throw new RuntimeException('El contenido del Excel es demasiado grande.');
                }
            }
            $xml = function (string $ruta) use ($zip): SimpleXMLElement {
                $datos = $zip->getFromName($ruta);
                if ($datos === false || stripos($datos, '<!DOCTYPE') !== false || stripos($datos, '<!ENTITY') !== false) {
                    throw new RuntimeException('El Excel tiene una estructura inválida.');
                }
                return new SimpleXMLElement($datos, LIBXML_NONET);
            };
            $libro = $xml('xl/workbook.xml');
            $id = null;
            foreach ($libro->children(self::NS)->sheets->sheet as $hoja) {
                if ((string) $hoja->attributes()['name'] === 'Alumnos') {
                    $id = (string) $hoja->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships')['id'];
                }
            }
            $rutaHoja = null;
            foreach ($xml('xl/_rels/workbook.xml.rels')->children('http://schemas.openxmlformats.org/package/2006/relationships')->Relationship as $rel) {
                if ($id !== null && (string) $rel->attributes()['Id'] === $id && (string) $rel->attributes()['TargetMode'] !== 'External') {
                    $destino = (string) $rel->attributes()['Target'];
                    $rutaHoja = str_starts_with($destino, '/') ? ltrim($destino, '/') : 'xl/'.$destino;
                }
            }
            if ($rutaHoja === null || str_contains($rutaHoja, '..')) {
                throw new RuntimeException('Usa la hoja «Alumnos» de la plantilla.');
            }
            $texto = static function (SimpleXMLElement $nodo): string {
                $nodo->registerXPathNamespace('m', self::NS);
                return implode('', array_map('strval', $nodo->xpath('.//m:t') ?: []));
            };
            $strings = [];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                foreach ($xml('xl/sharedStrings.xml')->children(self::NS)->si as $si) {
                    $strings[] = $texto($si);
                }
            }
            $filas = [];
            $columnas = [];
            foreach ($xml($rutaHoja)->children(self::NS)->sheetData->row as $row) {
                $numero = (int) $row->attributes()['r'];
                $celdas = [];
                foreach ($row->children(self::NS)->c as $celda) {
                    if (!preg_match('/^([A-Z]+)[0-9]+$/', (string) $celda->attributes()['r'], $ref)) {
                        throw new RuntimeException('El Excel contiene referencias inválidas.');
                    }
                    $hijos = $celda->children(self::NS);
                    if (isset($hijos->f)) {
                        throw new RuntimeException("Fila {$numero}: usa valores de texto, sin fórmulas.");
                    }
                    $celdas[$ref[1]] = trim(match ((string) $celda->attributes()['t']) {
                        's' => $strings[(int) $hijos->v] ?? '',
                        'inlineStr' => $texto($hijos->is),
                        default => (string) $hijos->v,
                    });
                }
                if ($numero === 1) {
                    foreach ($celdas as $col => $valor) {
                        $nombre = Str::lower(Str::ascii($valor));
                        if (in_array($nombre, self::COLUMNAS, true)) {
                            if (in_array($nombre, $columnas, true)) {
                                throw new RuntimeException('Hay encabezados repetidos.');
                            }
                            $columnas[$col] = $nombre;
                        }
                    }
                    if (count($columnas) !== count(self::COLUMNAS)) {
                        throw new RuntimeException('Conserva los encabezados: DNI, Apellidos, Nombres, Grado y Sección.');
                    }
                    continue;
                }
                if (!array_filter($celdas, fn ($v) => $v !== '')) {
                    continue;
                }
                $fila = array_fill_keys(self::COLUMNAS, '');
                foreach ($columnas as $col => $nombre) {
                    $fila[$nombre] = $celdas[$col] ?? '';
                }
                $filas[] = ['fila' => $numero] + $fila;
                if (count($filas) > 1000) {
                    throw new RuntimeException('Importa como máximo 1000 alumnos por archivo.');
                }
            }
            if (!$filas) {
                throw new RuntimeException('Completa la plantilla con al menos un alumno.');
            }
            return $filas;
        } finally {
            $zip->close();
        }
    }

    public function plantilla(): string
    {
        $ruta = tempnam(sys_get_temp_dir(), 'cni_alumnos_');
        $zip = new ZipArchive;
        if ($zip->open($ruta, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo generar la plantilla.');
        }
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<workbook xmlns="'.self::NS.'" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Alumnos" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<styleSheet xmlns="'.self::NS.'"><fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="1"><xf numFmtId="49" applyNumberFormat="1"/></cellXfs></styleSheet>');
        $celdas = '';
        foreach (['DNI', 'Apellidos', 'Nombres', 'Grado', 'Sección'] as $i => $titulo) {
            $celdas .= '<c r="'.chr(65 + $i).'1" t="inlineStr"><is><t>'.$titulo.'</t></is></c>';
        }
        $zip->addFromString('xl/worksheets/sheet1.xml', '<worksheet xmlns="'.self::NS.'"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" state="frozen"/></sheetView></sheetViews><cols><col min="1" max="5" width="24" customWidth="1" style="0"/></cols><sheetData><row r="1">'.$celdas.'</row></sheetData><autoFilter ref="A1:E1"/></worksheet>');
        $zip->close();
        return $ruta;
    }
}
