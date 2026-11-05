<?php

namespace Tests\Feature;

use App\Livewire\Panel;
use App\Models\Usuario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class PanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_muestra_solo_intentos_del_usuario_actual(): void
    {
        $ana = $this->usuario('ana');
        $otro = $this->usuario('otro');
        foreach ([$ana, $otro] as $usuario) {
            DB::table('intentos')->insert([
                'usuario_id' => $usuario->id, 'tipo' => 'practica',
                'fecha_inicio' => now(), 'estado' => 'en_curso', 'total_preguntas' => 0,
            ]);
        }
        $propio = DB::table('intentos')->where('usuario_id', $ana->id)->value('id');
        $ajeno = DB::table('intentos')->where('usuario_id', $otro->id)->value('id');
        Livewire::actingAs($ana, 'web')->test(Panel::class)
            ->assertSee('Intento #'.$propio)->assertDontSee('Intento #'.$ajeno);
    }

    private function usuario(string $nombre): Usuario
    {
        return Usuario::create([
            'nombres' => $nombre, 'apellidos' => 'Prueba', 'usuario' => $nombre,
            'password' => 'secreto123', 'rol' => 'estudiante', 'activo' => true,
        ]);
    }
}
