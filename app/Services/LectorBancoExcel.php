<?php

namespace App\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Lee el Excel del banco de preguntas (.xlsx) sin librerías externas:
 * un .xlsx es un ZIP con XML. Devuelve cada pregunta con su imagen,
 * sus alternativas (texto o imagen) y la letra correcta si existe.
 *
 * Formato esperado (hoja "Banco de preguntas"): una fila de encabezados con
 * Código | Curso | Pregunta (imagen) | A | B | C | D | E | Respuesta correcta.
 * La imagen de la pregunta va en la columna "Pregunta (imagen)".
 */
class LectorBancoExcel
{
    private const NS_MAIN = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    private const NS_REL = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
    private const NS_PKG = 'http://schemas.openxmlformats.org/package/2006/relationships';
    private const NS_XDR = 'http://schemas.openxmlformats.org/drawingml/2006/spreadsheetDrawing';
    private const NS_A = 'http://schemas.openxmlformats.org/drawingml/2006/main';

    private const LETRAS = ['A', 'B', 'C', 'D', 'E'];

    private ZipArchive $zip;

    /**
     * @return list<array{fila:int, codigo:string, curso:string, respuesta:?string,
     *   imagen:?array{datos:string, extension:string},
     *   alternativas:array<string, array{texto:string, imagen:?array{datos:string, extension:string}}>}>
     */
    public function leer(string $ruta, string $hoja = 'Banco de preguntas'): array
    {
        $this->zip = new ZipArchive;
        if ($this->zip->open($ruta) !== true) {
            throw new RuntimeException("No se pudo abrir el Excel: {$ruta}");
        }

        try {
            $rutaHoja = $this->rutaDeHoja($hoja);
            $filas = $this->celdas($rutaHoja);
            [$filaEncabezado, $columnas] = $this->encabezados($filas);
            $imagenes = $this->imagenes($rutaHoja);

            $preguntas = [];
            foreach ($filas as $numero => $celdas) {
                $codigo = trim((string) ($celdas[$columnas['codigo']] ?? ''));
                if ($numero <= $filaEncabezado || $codigo === '') {
                    continue;  // títulos de bloque y filas vacías
                }
                $respuesta = strtoupper(trim((string) ($celdas[$columnas['respuesta']] ?? '')));
                $alternativas = [];
                foreach (self::LETRAS as $letra) {
                    $columna = $columnas[$letra];
                    $alternativas[$letra] = [
                        'texto' => trim((string) ($celdas[$columna] ?? '')),
                        'imagen' => $imagenes[$numero][$columna] ?? null,
                    ];
                }
                $preguntas[] = [
                    'fila' => $numero,
                    'codigo' => $codigo,
                    'curso' => trim((string) ($celdas[$columnas['curso']] ?? '')),
                    'respuesta' => in_array($respuesta, self::LETRAS, true) ? $respuesta : null,
                    'imagen' => $imagenes[$numero][$columnas['imagen']] ?? null,
                    'alternativas' => $alternativas,
                ];
            }

            return $preguntas;
        } finally {
            $this->zip->close();
        }
    }

    private function rutaDeHoja(string $nombre): string
    {
        $libro = $this->xml('xl/workbook.xml');
        $rels = $this->relaciones('xl/workbook.xml');
        foreach ($libro->children(self::NS_MAIN)->sheets->sheet as $hoja) {
            if ((string) $hoja->attributes()['name'] === $nombre) {
                $id = (string) $hoja->attributes(self::NS_REL)['id'];

                return $rels[$id] ?? throw new RuntimeException("La hoja «{$nombre}» no tiene archivo.");
            }
        }
        throw new RuntimeException("El Excel no tiene la hoja «{$nombre}».");
    }

    /** @return array<int, array<string, string>> fila => [columna => valor] */
    private function celdas(string $rutaHoja): array
    {
        $compartidas = [];
        if ($this->zip->locateName('xl/sharedStrings.xml') !== false) {
            foreach ($this->xml('xl/sharedStrings.xml')->children(self::NS_MAIN)->si as $si) {
                $compartidas[] = $this->textoDe($si);
            }
        }

        $filas = [];
        foreach ($this->xml($rutaHoja)->children(self::NS_MAIN)->sheetData->row as $row) {
            foreach ($row->children(self::NS_MAIN)->c as $c) {
                [$columna, $fila] = $this->referencia((string) $c->attributes()['r']);
                $tipo = (string) $c->attributes()['t'];
                $hijos = $c->children(self::NS_MAIN);
                $valor = match ($tipo) {
                    's' => $compartidas[(int) $hijos->v] ?? '',
                    'inlineStr' => $this->textoDe($hijos->is),
                    default => (string) $hijos->v,
                };
                if ($valor !== '') {
                    $filas[$fila][$columna] = $valor;
                }
            }
        }
        ksort($filas);

        return $filas;
    }

    /** @return array{0:int, 1:array<string, string>} fila del encabezado y columna de cada campo */
    private function encabezados(array $filas): array
    {
        $buscados = ['codigo' => 'código', 'curso' => 'curso', 'imagen' => 'pregunta', 'respuesta' => 'respuesta'];
        foreach ($filas as $numero => $celdas) {
            $columnas = [];
            foreach ($celdas as $columna => $valor) {
                $texto = mb_strtolower(trim($valor));
                foreach ($buscados as $clave => $prefijo) {
                    if (! isset($columnas[$clave]) && str_starts_with($texto, $prefijo)) {
                        $columnas[$clave] = $columna;
                    }
                }
                if (in_array(strtoupper(trim($valor)), self::LETRAS, true) && strlen(trim($valor)) === 1) {
                    $columnas[strtoupper(trim($valor))] = $columna;
                }
            }
            if (count($columnas) === count($buscados) + count(self::LETRAS)) {
                return [$numero, $columnas];
            }
        }
        throw new RuntimeException('No se encontró la fila de encabezados (Código, Curso, Pregunta, A–E, Respuesta correcta).');
    }

    /** @return array<int, array<string, array{datos:string, extension:string}>> fila => [columna => imagen] */
    private function imagenes(string $rutaHoja): array
    {
        $resultado = [];
        foreach ($this->relaciones($rutaHoja) as $rutaDibujo) {
            if (! str_contains($rutaDibujo, '/drawings/')) {
                continue;
            }
            $medios = $this->relaciones($rutaDibujo);
            $dibujo = $this->xml($rutaDibujo)->children(self::NS_XDR);
            $anclas = array_merge(
                iterator_to_array($dibujo->twoCellAnchor, false),
                iterator_to_array($dibujo->oneCellAnchor, false),
            );
            foreach ($anclas as $ancla) {
                $blip = $ancla->pic?->blipFill?->children(self::NS_A)->blip;
                $id = $blip ? (string) $blip->attributes(self::NS_REL)['embed'] : '';
                if ($id === '' || ! isset($medios[$id])) {
                    continue;
                }
                // Excel ancla la imagen por su esquina; la fila de destino ("to") es la de la pregunta.
                $marco = isset($ancla->to) ? $ancla->to : $ancla->from;
                $fila = (int) $marco->row + 1;
                $columna = $this->letraColumna((int) $ancla->from->col);
                $resultado[$fila][$columna] = [
                    'datos' => $this->zip->getFromName($medios[$id]),
                    'extension' => strtolower(pathinfo($medios[$id], PATHINFO_EXTENSION)) ?: 'png',
                ];
            }
        }

        return $resultado;
    }

    /** @return array<string, string> id de relación => ruta dentro del ZIP */
    private function relaciones(string $ruta): array
    {
        $rutaRels = dirname($ruta).'/_rels/'.basename($ruta).'.rels';
        if ($this->zip->locateName($rutaRels) === false) {
            return [];
        }
        $rels = [];
        foreach ($this->xml($rutaRels)->children(self::NS_PKG)->Relationship as $rel) {
            $rels[(string) $rel->attributes()['Id']] = $this->resolver(dirname($ruta), (string) $rel->attributes()['Target']);
        }

        return $rels;
    }

    private function resolver(string $base, string $destino): string
    {
        $partes = [];
        $ruta = str_starts_with($destino, '/') ? ltrim($destino, '/') : $base.'/'.$destino;
        foreach (explode('/', $ruta) as $parte) {
            if ($parte === '..') {
                array_pop($partes);
            } elseif ($parte !== '.' && $parte !== '') {
                $partes[] = $parte;
            }
        }

        return implode('/', $partes);
    }

    private function xml(string $ruta): SimpleXMLElement
    {
        $contenido = $this->zip->getFromName($ruta);
        if ($contenido === false) {
            throw new RuntimeException("Falta {$ruta} dentro del Excel.");
        }

        return new SimpleXMLElement($contenido, LIBXML_NONET);
    }

    private function textoDe(?SimpleXMLElement $nodo): string
    {
        if ($nodo === null) {
            return '';
        }
        $nodo->registerXPathNamespace('m', self::NS_MAIN);

        return implode('', array_map('strval', $nodo->xpath('.//m:t') ?: []));
    }

    /** "AB12" => ["AB", 12] */
    private function referencia(string $celda): array
    {
        preg_match('/^([A-Z]+)(\d+)$/', $celda, $m);

        return [$m[1], (int) $m[2]];
    }

    private function letraColumna(int $indice): string
    {
        $letra = '';
        for ($n = $indice + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $letra = chr(65 + ($n - 1) % 26).$letra;
        }

        return $letra;
    }
}
