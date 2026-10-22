<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Datos de prueba para recorrer el examen del alumno.
 * No está en DatabaseSeeder; se ejecuta a mano:
 *   php artisan db:seed --class=ExamenDemoSeeder
 * Es idempotente: volver a ejecutarlo no duplica preguntas.
 */
class ExamenDemoSeeder extends Seeder
{
    public const SIMULACRO = 'Simulacro de admisión CNI (demo)';

    public function run(): void
    {
        DB::transaction(function () {
            $ahora = now();
            $creador = DB::table('usuarios')->where('rol', 'admin')->value('id');
            $cuotas = [];
            $contador = 0;

            foreach ($this->banco() as $areaNombre => $area) {
                DB::table('areas')->updateOrInsert(
                    ['nombre' => $areaNombre],
                    ['bloque' => $area['bloque'], 'activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora]
                );
                $areaId = DB::table('areas')->where('nombre', $areaNombre)->value('id');
                $cuotas[$areaId] = 0;

                foreach ($area['temas'] as $temaNombre => $preguntas) {
                    DB::table('temas')->updateOrInsert(
                        ['area_id' => $areaId, 'nombre' => $temaNombre],
                        ['activo' => true, 'created_at' => $ahora, 'updated_at' => $ahora]
                    );
                    $temaId = DB::table('temas')->where('area_id', $areaId)->where('nombre', $temaNombre)->value('id');

                    foreach ($preguntas as [$enunciado, $nivel, $opciones]) {
                        $cuotas[$areaId]++;
                        // La correcta va primera en el banco; se rota para que no siempre sea la A.
                        $giro = $contador++ % 4;
                        if (DB::table('preguntas')->where('tema_id', $temaId)->where('enunciado', $enunciado)->exists()) {
                            continue;
                        }
                        $preguntaId = DB::table('preguntas')->insertGetId([
                            'tema_id' => $temaId, 'enunciado' => $enunciado, 'imagen' => null,
                            'nivel' => $nivel, 'solucion' => 'La respuesta correcta es: '.$opciones[0].'.',
                            'activo' => true, 'creado_por' => $creador,
                            'created_at' => $ahora, 'updated_at' => $ahora,
                        ]);
                        $rotadas = array_merge(array_slice($opciones, $giro), array_slice($opciones, 0, $giro));
                        foreach ($rotadas as $i => $texto) {
                            DB::table('alternativas')->insert([
                                'pregunta_id' => $preguntaId, 'letra' => chr(65 + $i), 'texto' => $texto,
                                'es_correcta' => $texto === $opciones[0],
                                'created_at' => $ahora, 'updated_at' => $ahora,
                            ]);
                        }
                    }
                }
            }

            DB::table('simulacros')->updateOrInsert(
                ['nombre' => self::SIMULACRO],
                [
                    'tiempo_limite' => 60, 'total_preguntas' => array_sum($cuotas), 'cantidad_alternativas' => 4,
                    'fecha_inicio' => $ahora->copy()->subDay(), 'fecha_fin' => $ahora->copy()->addMonths(3),
                    'activo' => true, 'creado_por' => $creador,
                    'created_at' => $ahora, 'updated_at' => $ahora,
                ]
            );
            $simulacroId = DB::table('simulacros')->where('nombre', self::SIMULACRO)->value('id');

            DB::table('simulacro_area')->where('simulacro_id', $simulacroId)->delete();
            foreach ($cuotas as $areaId => $cantidad) {
                DB::table('simulacro_area')->insert([
                    'simulacro_id' => $simulacroId, 'area_id' => $areaId, 'nivel' => 'mixto',
                    'cantidad_preguntas' => $cantidad, 'created_at' => $ahora, 'updated_at' => $ahora,
                ]);
            }
        });
    }

    /** 40 preguntas: [enunciado, nivel, [correcta, distractor, distractor, distractor]]. */
    private function banco(): array
    {
        return [
            'Razonamiento Verbal' => ['bloque' => 'habilidad_verbal', 'temas' => [
                'Sinónimos' => [
                    ['Señala el sinónimo de EFÍMERO.', 'basico', ['Pasajero', 'Duradero', 'Eterno', 'Firme']],
                    ['Señala el sinónimo de OBSTINADO.', 'basico', ['Terco', 'Dócil', 'Flexible', 'Tímido']],
                    ['Señala el sinónimo de PERSPICAZ.', 'intermedio', ['Sagaz', 'Torpe', 'Lento', 'Distraído']],
                    ['Señala el sinónimo de ACERBO.', 'intermedio', ['Áspero', 'Dulce', 'Suave', 'Tierno']],
                    ['Señala el sinónimo de PRÍSTINO.', 'avanzado', ['Original', 'Moderno', 'Sucio', 'Reciente']],
                ],
                'Analogías' => [
                    ['PEZ : AGUA ::', 'basico', ['Ave : aire', 'Gato : ratón', 'Vaca : leche', 'Abeja : miel']],
                    ['MÉDICO : HOSPITAL ::', 'basico', ['Docente : colegio', 'Juez : ley', 'Pintor : cuadro', 'Chofer : auto']],
                    ['LIBRO : PÁGINA ::', 'intermedio', ['Edificio : piso', 'Lápiz : papel', 'Mesa : silla', 'Carta : sobre']],
                    ['FRÍO : CALOR ::', 'intermedio', ['Oscuridad : luz', 'Nieve : hielo', 'Lluvia : agua', 'Viento : aire']],
                    ['PINTOR : PINCEL ::', 'intermedio', ['Escultor : cincel', 'Cantante : escenario', 'Escritor : libro', 'Músico : partitura']],
                ],
            ]],
            'Razonamiento Matemático' => ['bloque' => 'habilidad_logico_matematica', 'temas' => [
                'Porcentajes' => [
                    ['En una clase de 40 estudiantes, el 60 % son mujeres. Si se incorporan 10 varones, ¿qué porcentaje del total representan ahora las mujeres?', 'intermedio', ['48 %', '40 %', '50 %', '60 %']],
                    ['¿Cuánto es el 25 % de 360?', 'basico', ['90', '80', '72', '100']],
                    ['Un artículo cuesta S/ 200 y se le aplica un descuento del 15 %. ¿Cuál es su precio final?', 'basico', ['S/ 170', 'S/ 185', 'S/ 175', 'S/ 160']],
                    ['Si un número aumenta en 20 % y luego disminuye en 20 %, ¿qué ocurre con el número original?', 'intermedio', ['Disminuye 4 %', 'No varía', 'Aumenta 4 %', 'Disminuye 2 %']],
                    ['¿Qué porcentaje de 80 es 20?', 'basico', ['25 %', '20 %', '40 %', '16 %']],
                ],
                'Series numéricas' => [
                    ['¿Qué número continúa? 2, 6, 12, 20, 30, …', 'basico', ['42', '40', '44', '36']],
                    ['¿Qué número continúa? 3, 5, 9, 17, 33, …', 'intermedio', ['65', '64', '66', '49']],
                    ['¿Qué número continúa? 1, 4, 9, 16, 25, …', 'basico', ['36', '30', '35', '49']],
                    ['¿Qué número continúa? 81, 27, 9, 3, …', 'basico', ['1', '0', '2', '1/9']],
                    ['¿Qué número continúa? 5, 8, 14, 23, 35, …', 'avanzado', ['50', '48', '47', '52']],
                ],
            ]],
            'Matemática' => ['bloque' => 'habilidad_logico_matematica', 'temas' => [
                'Aritmética' => [
                    ['¿Cuál es el mínimo común múltiplo de 12 y 18?', 'basico', ['36', '72', '6', '24']],
                    ['¿Cuál es el máximo común divisor de 48 y 60?', 'basico', ['12', '6', '24', '4']],
                    ['¿Cuánto es 3/4 + 2/3?', 'intermedio', ['17/12', '5/7', '6/12', '13/12']],
                ],
                'Álgebra' => [
                    ['Si 2x + 7 = 19, ¿cuál es el valor de x?', 'basico', ['6', '5', '7', '13']],
                    ['Si a + b = 7 y ab = 10, ¿cuánto vale a² + b²?', 'intermedio', ['29', '39', '49', '19']],
                    ['¿Cuáles son las raíces de x² − 5x + 6 = 0?', 'intermedio', ['2 y 3', '−2 y −3', '1 y 6', '−1 y 6']],
                ],
            ]],
            'Ciencias' => ['bloque' => 'conocimientos', 'temas' => [
                'Física' => [
                    ['¿Cuál es la unidad de fuerza en el Sistema Internacional?', 'basico', ['Newton', 'Joule', 'Pascal', 'Watt']],
                    ['Un auto viaja a 72 km/h. ¿Cuál es su rapidez en m/s?', 'intermedio', ['20 m/s', '25 m/s', '72 m/s', '7,2 m/s']],
                    ['¿Cuál es el valor aproximado de la aceleración de la gravedad en la Tierra?', 'basico', ['9,8 m/s²', '8,9 m/s²', '10,8 m/s²', '98 m/s²']],
                ],
                'Química' => [
                    ['¿Cuál es el símbolo químico del sodio?', 'basico', ['Na', 'So', 'S', 'N']],
                    ['¿Cuál es el número atómico del carbono?', 'basico', ['6', '12', '8', '14']],
                    ['Una solución con pH igual a 7 es:', 'basico', ['Neutra', 'Ácida', 'Básica', 'Muy ácida']],
                ],
                'Biología' => [
                    ['¿En qué organelo se realiza la respiración celular?', 'basico', ['Mitocondria', 'Ribosoma', 'Lisosoma', 'Aparato de Golgi']],
                    ['¿Qué molécula contiene la información genética?', 'basico', ['ADN', 'ATP', 'Glucosa', 'Lípido']],
                ],
            ]],
            'Humanidades' => ['bloque' => 'conocimientos', 'temas' => [
                'Historia del Perú' => [
                    ['¿En qué año se proclamó la independencia del Perú?', 'basico', ['1821', '1824', '1810', '1836']],
                    ['¿Quién proclamó la independencia del Perú?', 'basico', ['José de San Martín', 'Simón Bolívar', 'Túpac Amaru II', 'Ramón Castilla']],
                    ['¿Cuál fue la capital del Tahuantinsuyo?', 'basico', ['Cusco', 'Lima', 'Cajamarca', 'Quito']],
                ],
                'Geografía' => [
                    ['¿Cuál es el río más caudaloso del mundo, que nace en territorio peruano?', 'basico', ['Amazonas', 'Nilo', 'Rímac', 'Ucayali']],
                    ['¿Cuál es el lago navegable más alto del mundo?', 'basico', ['Titicaca', 'Junín', 'Parinacochas', 'Lagunillas']],
                    ['¿En qué cordillera se encuentra el nevado Huascarán?', 'intermedio', ['Cordillera Blanca', 'Cordillera Negra', 'Cordillera Huayhuash', 'Cordillera de Vilcanota']],
                ],
            ]],
        ];
    }
}
