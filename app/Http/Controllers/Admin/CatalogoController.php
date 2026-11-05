<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogoController extends Controller
{
    public function index(): View
    {
        return view('admin.inicio', [
            'areasPostulacion' => DB::table('areas_postulacion')->where('activo', true)->orderBy('codigo')->get(),
            'carreras' => DB::table('carreras as c')->join('areas_postulacion as a', 'a.id', '=', 'c.area_postulacion_id')
                ->select('c.id', 'c.nombre', 'a.codigo')->orderBy('c.nombre')->get(),
            'asignaturas' => DB::table('areas')->where('activo', true)->orderBy('nombre')->get(),
            'temas' => DB::table('temas as t')->join('areas as a', 'a.id', '=', 't.area_id')
                ->select('t.nombre', 'a.nombre as asignatura')->orderBy('a.nombre')->orderBy('t.nombre')->get(),
            'totales' => [
                'preguntas' => DB::table('preguntas')->where('activo', true)->count(),
                'simulacros' => DB::table('simulacros')->count(),
            ],
        ]);
    }

    public function asignatura(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100', 'unique:areas,nombre'],
            'bloque' => ['required', Rule::in(['habilidad_verbal', 'habilidad_logico_matematica', 'conocimientos'])],
        ]);
        DB::table('areas')->insert($datos + ['activo' => true, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Asignatura creada.');
    }

    public function tema(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'area_id' => ['required', Rule::exists('areas', 'id')->where('activo', true)],
            'nombre' => ['required', 'string', 'max:150', Rule::unique('temas')->where('area_id', $request->input('area_id'))],
        ]);
        DB::table('temas')->insert($datos + ['activo' => true, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Tema creado.');
    }

    public function carrera(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'area_postulacion_id' => ['required', Rule::exists('areas_postulacion', 'id')->where('activo', true)],
            'nombre' => ['required', 'string', 'max:150', 'unique:carreras,nombre'],
        ]);
        DB::table('carreras')->insert($datos + ['activo' => true, 'created_at' => now(), 'updated_at' => now()]);

        return back()->with('status', 'Carrera registrada.');
    }
}
