<?php

namespace Tests\Feature;

use App\Models\Usuario;
use Database\Seeders\AreaPostulacionSeeder;
use Database\Seeders\CarreraSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Una sola ruta y una sola plantilla Blade, con cinco identidades visuales.
 */
class PanelesPorAreaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['cni.alumno_areas_enabled' => true]);
        $this->seed(AreaPostulacionSeeder::class);
        $this->seed(CarreraSeeder::class);
    }

    private function estudiante(string $codigo, bool $conArea = true): Usuario
    {
        return Usuario::create([
            'nombres' => 'Alumno',
            'apellidos' => 'Area '.$codigo,
            'usuario' => uniqid('alumno_'.$codigo.'_'),
            'password' => 'ClaveCNI2026!',
            'rol' => 'estudiante',
            'activo' => true,
            'area_postulacion_id' => $conArea
                ? DB::table('areas_postulacion')->where('codigo', $codigo)->value('id')
                : null,
        ]);
    }

    public function test_cinco_areas_usan_misma_ruta_y_plantilla_pero_identidad_diferente(): void
    {
        $temas = config('panel_alumno.areas');
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], array_keys($temas));

        foreach ($temas as $codigo => $tema) {
            $this->actingAs($this->estudiante($codigo), 'web');
            $this->get(route('alumno.panel'))
                ->assertOk()
                ->assertSee('panel-alumno.css')
                ->assertSee('data-area="'.$codigo.'"', false)
                ->assertSee($tema['descripcion'])
                ->assertSee($tema['lema'])
                ->assertSee('Iniciar Simulador CNI')
                ->assertSee('Mascota del área de')
                ->assertSee('href="#perfil"', false)
                ->assertSee('href="#progreso"', false)
                ->assertSee('href="#historial"', false)
                ->assertSee('Misión Beca CNI')
                ->assertSee(route('alumno.becas'))
                ->assertSee('aria-controls="cni-alumno-sidebar"', false)
                ->assertSee('data-sidebar-toggle', false);
        }
    }

    public function test_eleccion_persiste_y_llega_a_la_misma_pagina_para_cada_area(): void
    {
        foreach (['A', 'B', 'C', 'D', 'E'] as $codigo) {
            $usuario = $this->estudiante($codigo, false);
            $idArea = DB::table('areas_postulacion')->where('codigo', $codigo)->value('id');

            $this->actingAs($usuario, 'web')
                ->post(route('alumno.area.guardar'), ['area_id' => $idArea])
                ->assertRedirect(route('alumno.panel'));

            $this->assertDatabaseHas('usuarios', [
                'id' => $usuario->id,
                'area_postulacion_id' => $idArea,
                'carrera_id' => null,
            ]);

            $this->get(route('alumno.panel'))->assertOk()
                ->assertSee('data-area="'.$codigo.'"', false);
        }
    }

    public function test_una_cuenta_sin_area_ve_el_selector_en_vez_del_panel(): void
    {
        $this->actingAs($this->estudiante('A', false), 'web');
        $this->get(route('alumno.panel'))
            ->assertRedirect(route('alumno.elegir-area'));
    }

    public function test_las_urls_anteriores_de_diego_y_dafne_redirigen_al_panel_unificado(): void
    {
        $this->actingAs($this->estudiante('B'), 'web');
        $this->get(route('alumno.ciencias-basicas'))->assertRedirect(route('alumno.panel'));

        $this->actingAs($this->estudiante('E'), 'web');
        $this->get(route('alumno.inicio'))->assertRedirect(route('alumno.panel'));
    }

    public function test_los_temas_de_otras_areas_no_se_abren_mediante_url_directa(): void
    {
        $this->actingAs($this->estudiante('E'), 'web');
        $this->get(route('alumno.tema', ['tema' => 9999]))->assertNotFound();
    }

    public function test_menu_lateral_tiene_cinco_opciones_y_un_boton_para_contraerlo(): void
    {
        $this->actingAs($this->estudiante('C'), 'web');
        $html = $this->get(route('alumno.panel'))->assertOk()->getContent();

        $this->assertSame(1, preg_match(
            '/<nav class="cni-alumno-menu"[^>]*>(.*?)<\\/nav>/s',
            $html,
            $navegacion
        ));
        $this->assertSame(5, preg_match_all('/<a\\b/', $navegacion[1]));
        $this->assertStringContainsString('Mi perfil', $navegacion[1]);
        $this->assertStringContainsString('Mi progreso', $navegacion[1]);
        $this->assertStringContainsString('Historial', $navegacion[1]);
        $this->assertStringContainsString('Academias virtuales', $navegacion[1]);
        $this->assertStringContainsString('aria-expanded="true"', $html);
        $this->assertStringContainsString('data-sidebar-backdrop', $html);
        $this->assertStringContainsString('id="progreso"', $html);
        $this->assertStringContainsString('id="historial"', $html);
        $this->assertStringContainsString('id="perfil"', $html);
    }


    public function test_academias_virtuales_son_comunes_a_las_cinco_areas(): void
    {
        $academias = [
            'Grupo Ciencias San Marcos' => 'https://www.youtube.com/@GrupoCienciasSanMarcos',
            'Academia Vonex' => 'https://www.youtube.com/@AcademiaVonex/videos',
            'Alberto Cruz' => 'https://www.youtube.com/@AlbertoCruz11111',
            'David ERRE' => 'https://www.youtube.com/@DAVIDERRE',
            'Física PRE' => 'https://www.youtube.com/@F%C3%ADsicaPRE',
            'Jonathan Cachimbo' => 'https://www.youtube.com/@jonathancachimbo',
            'Juan Meneses' => 'https://www.youtube.com/@juanmenesesrm',
        ];

        foreach (['A', 'B', 'C', 'D', 'E'] as $codigo) {
            $this->actingAs($this->estudiante($codigo), 'web');
            $html = $this->get(route('alumno.panel'))->assertOk()->getContent();

            $this->assertStringContainsString('href="#academias"', $html);
            $this->assertStringContainsString('id="academias" data-panel-view="academias"', $html);
            $this->assertMatchesRegularExpression('/id="academias"[^>]*hidden>/', $html);

            foreach ($academias as $nombre => $url) {
                $this->assertStringContainsString($nombre, $html);
                $this->assertStringContainsString($url, $html);
            }

            $this->assertSame(7, preg_match_all(
                '/<a class="cni-alumno-academia-card"[^>]*target="_blank" rel="noopener noreferrer"/',
                $html
            ));
            $this->assertStringContainsString("const destinos = ['#perfil', '#progreso', '#historial', '#academias'];", $html);
            $this->assertStringContainsString("'#academias': ['Academias virtuales', '▶']", $html);
        }
    }

    public function test_mi_perfil_abre_de_entrada_con_solo_portada_y_dos_tarjetas(): void
    {
        $this->actingAs($this->estudiante('B'), 'web');
        $html = $this->get(route('alumno.panel'))->assertOk()->getContent();

        $this->assertStringContainsString('id="perfil" data-panel-view="perfil"', $html);
        $this->assertSame(1, preg_match_all('/class="cni-alumno-banner"/', $html));
        $this->assertSame(1, preg_match_all('/class="cni-alumno-progress-card"/', $html));
        $this->assertSame(1, preg_match_all('/class="cni-alumno-streak-card"/', $html));
        $this->assertStringContainsString('href="#perfil" class="is-active"', $html);
        $this->assertStringNotContainsString('cni-alumno-topics', $html);
        $this->assertStringContainsString('id="progreso" data-panel-view="progreso"', $html);
        $this->assertStringContainsString('id="intentos" data-panel-view="intentos"', $html);
        $this->assertStringContainsString('id="historial" data-panel-view="historial"', $html);
        $this->assertMatchesRegularExpression('/id="progreso"[^>]*hidden>/', $html);
        $this->assertMatchesRegularExpression('/id="intentos"[^>]*hidden>/', $html);
        $this->assertMatchesRegularExpression('/id="historial"[^>]*hidden>/', $html);
        $this->assertStringContainsString('const mostrarSeccion =', $html);
    }

    public function test_la_pantalla_de_intentos_conserva_examenes_y_eleccion_de_carrera(): void
    {
        $this->actingAs($this->estudiante('E'), 'web');
        $html = $this->get(route('alumno.panel'))->assertOk()->getContent();

        $this->assertStringContainsString('Mis intentos · Iniciar Simulador CNI', $html);
        $this->assertStringContainsString(route('alumno.carrera.guardar'), $html);
        $this->assertStringContainsString('Carrera de interés', $html);
        $this->assertStringContainsString('Mi perfil', $html);
    }

    public function test_menu_usa_mi_perfil_mi_progreso_historial_para_cada_area(): void
    {
        foreach (['A', 'B', 'C', 'D', 'E'] as $codigo) {
            $this->actingAs($this->estudiante($codigo), 'web');
            $html = $this->get(route('alumno.panel'))->assertOk()->getContent();

            $this->assertMatchesRegularExpression('/href="#perfil"[^>]*>.*?Mi perfil/s', $html);
            $this->assertMatchesRegularExpression('/href="#progreso"[^>]*>.*?Mi progreso/s', $html);
            $this->assertMatchesRegularExpression('/href="#historial"[^>]*>.*?Historial/s', $html);
            $this->assertStringContainsString('data-panel-view="progreso"', $html);
            $this->assertStringContainsString('Aún no hay intentos calificados', $html);
            $this->assertStringContainsString('data-go-panel="#intentos"', $html);
            $this->assertStringNotContainsString('href="#intentos"', $html);
        }
    }

    public function test_mision_beca_es_comun_a_las_cinco_areas(): void
    {
        foreach (['A', 'B', 'C', 'D', 'E'] as $codigo) {
            $usuario = $this->estudiante($codigo);
            $this->actingAs($usuario, 'web')->get(route('alumno.becas'))
                ->assertOk()->assertViewIs('alumno.becas')
                ->assertSee('BECA 18')->assertSee('BECAS BCP')
                ->assertSee('Volver al panel')
                ->assertSee(route('examen.independiente'));
        }
    }

    public function test_mision_beca_requiere_sesion_y_rol_estudiante(): void
    {
        $this->get(route('alumno.becas'))->assertRedirect(route('login'));
        $usuario = $this->estudiante('A');
        $usuario->update(['rol' => 'docente']);
        $this->actingAs($usuario, 'web')->get(route('alumno.becas'))->assertForbidden();
    }

    public function test_docente_no_puede_abrir_el_panel_de_alumno(): void
    {
        $docente = Usuario::create([
            'nombres' => 'Docente', 'apellidos' => 'CNI',
            'usuario' => 'docente_unificado',
            'password' => 'ClaveCNI2026!',
            'rol' => 'docente', 'activo' => true,
        ]);

        $this->actingAs($docente, 'web')
            ->get(route('alumno.panel'))->assertForbidden();
    }

    public function test_cada_academia_muestra_vista_previa_y_no_renderiza_iframes_al_cargar(): void
    {
        $ids = [
            'Grupo Ciencias San Marcos' => 'UC2t2Wzp4cnLGW31aBWSb9MA',
            'Academia Vonex' => 'UCcRvqu5Z-8xcYT8rZ05KEXg',
            'Alberto Cruz' => 'UCfXlr6-4saqmE8U9mtw3WPQ',
            'David ERRE' => 'UCLvutyuve6_JeMk2fL2QmIw',
            'Física PRE' => 'UCc4RghPUscsFM5X6exrTZ5w',
            'Jonathan Cachimbo' => 'UCjEwR5G0LtxCSh__51k0Iog',
            'Juan Meneses' => 'UC9OAoS3avLDNj9vB4T-9xuw',
        ];

        $this->actingAs($this->estudiante('A'), 'web');
        $html = $this->get(route('alumno.panel'))->assertOk()->getContent();
        preg_match('/<section class="cni-alumno-vista cni-alumno-vista-academias" id="academias"[^>]*>(.*?)<\\/section>/s', $html, $seccion);
        $this->assertNotEmpty($seccion[1] ?? null);
        $this->assertStringNotContainsString('<iframe', $seccion[1]);

        foreach ($ids as $id) {
            $this->assertStringContainsString(
                'youtube-nocookie.com/embed/videoseries?list=UU'.substr($id, 2),
                $seccion[1]
            );
        }
        $this->assertSame(7, preg_match_all('/<button type="button" class="cni-alumno-academia-preview"/', $seccion[1]));
        $this->assertSame(7, substr_count($seccion[1], 'data-academia-preview'));
        $this->assertSame(7, substr_count($seccion[1], 'data-academia-close'));
        $this->assertSame(7, preg_match_all('/youtube-nocookie\\.com\\/embed\\/videoseries\\?list=UU/', $seccion[1]));
        $this->assertStringContainsString('MutationObserver', $html);
    }

    public function test_academias_sin_id_o_con_id_invalido_no_muestran_vista_previa(): void
    {
        config(['panel_alumno.academias' => [
            ['nombre' => 'Academia sin ID', 'url' => 'https://www.youtube.com/@sinid'],
            ['nombre' => 'Academia ID inválido', 'url' => 'https://www.youtube.com/@invalido', 'canal_id' => 'abc'],
        ]]);

        $this->actingAs($this->estudiante('A'), 'web');
        $html = $this->get(route('alumno.panel'))->assertOk()->getContent();
        preg_match('/<section class="cni-alumno-vista cni-alumno-vista-academias" id="academias"[^>]*>(.*?)<\\/section>/s', $html, $seccion);
        $this->assertNotEmpty($seccion[1] ?? null);
        $this->assertStringContainsString('Academia sin ID', $seccion[1]);
        $this->assertStringContainsString('Academia ID inválido', $seccion[1]);
        $this->assertStringNotContainsString('data-academia-preview', $seccion[1]);
        $this->assertSame(0, substr_count($seccion[1], 'data-academia-close'));
        $this->assertStringNotContainsString('youtube-nocookie.com/embed/videoseries', $seccion[1]);
    }


    public function test_ultimo_video_de_academia_devuelve_json_y_usa_cache(): void
    {
        Cache::flush();
        Http::preventStrayRequests();
        $canal = config('panel_alumno.academias.0.canal_id');
        $feed = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<feed xmlns="http://www.w3.org/2005/Atom" xmlns:yt="http://www.youtube.com/xml/schemas/2015">'
            .'<entry><yt:videoId>AbCdEfGhI12</yt:videoId><title>Primer video</title></entry>'
            .'<entry><yt:videoId>ZyXwVuTsR98</yt:videoId><title>Segundo video</title></entry>'
            .'</feed>';
        Http::fake(['https://www.youtube.com/feeds/videos.xml*' => Http::response($feed, 200)]);

        $this->actingAs($this->estudiante('A'), 'web');
        $url = route('alumno.academias.ultimo-video', ['canal' => $canal]);
        $this->getJson($url)->assertOk()->assertExactJson([
            'video_id' => 'AbCdEfGhI12',
            'titulo' => 'Primer video',
        ]);
        $this->getJson($url)->assertOk()->assertExactJson([
            'video_id' => 'AbCdEfGhI12',
            'titulo' => 'Primer video',
        ]);
        Http::assertSentCount(1);
    }

    public function test_ultimo_video_rechaza_canales_que_no_estan_en_la_configuracion(): void
    {
        Cache::flush();
        Http::preventStrayRequests();
        Http::fake();
        $this->actingAs($this->estudiante('A'), 'web');
        $this->getJson(route('alumno.academias.ultimo-video', ['canal' => 'UC0000000000000000000000']))
            ->assertNotFound()->assertExactJson([]);
        Http::assertNothingSent();
    }

    public function test_ultimo_video_responde_404_si_youtube_falla(): void
    {
        Cache::flush();
        Http::preventStrayRequests();
        Http::fake(['https://www.youtube.com/feeds/videos.xml*' => Http::response('', 500)]);
        $this->actingAs($this->estudiante('A'), 'web');
        $canal = config('panel_alumno.academias.0.canal_id');
        $this->getJson(route('alumno.academias.ultimo-video', ['canal' => $canal]))
            ->assertNotFound()->assertExactJson([]);
        $this->get(route('alumno.panel'))->assertOk();
    }

    public function test_ultimo_video_requiere_sesion_y_rol_estudiante(): void
    {
        Cache::flush();
        Http::preventStrayRequests();
        $canal = config('panel_alumno.academias.0.canal_id');
        $url = route('alumno.academias.ultimo-video', ['canal' => $canal]);
        $this->get($url)->assertRedirect(route('login'));

        $docente = $this->estudiante('A');
        $docente->update(['rol' => 'docente']);
        $this->actingAs($docente, 'web')->get($url)->assertForbidden();
    }

}
