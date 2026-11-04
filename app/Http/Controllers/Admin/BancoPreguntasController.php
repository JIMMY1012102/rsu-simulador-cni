<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PreguntaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class BancoPreguntasController extends Controller
{
    public function __construct(private readonly PreguntaService $preguntas) {}

    public function index(Request $request): View
    {
        $filtros = $request->validate([
            'tema_id' => ['nullable', 'integer', 'exists:temas,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'estado' => ['nullable', Rule::in(['activo', 'inactivo'])],
            'buscar' => ['nullable', 'string', 'max:200'],
        ]);
        $consulta = DB::table('preguntas as p')->join('temas as t', 't.id', '=', 'p.tema_id')
            ->join('areas as a', 'a.id', '=', 't.area_id')
            ->select('p.id', 'p.codigo', 'p.enunciado', 'p.imagen', 'p.activo', 'p.created_at',
                't.nombre as tema', 'a.nombre as asignatura');
        if (! empty($filtros['tema_id'])) {
            $consulta->where('t.id', $filtros['tema_id']);
        }
        if (! empty($filtros['area_id'])) {
            $consulta->where('a.id', $filtros['area_id']);
        }
        if (! empty($filtros['estado'])) {
            $consulta->where('p.activo', $filtros['estado'] === 'activo');
        }
        $buscar = trim($filtros['buscar'] ?? '');
        if ($buscar !== '') {
            $consulta->where(function ($query) use ($buscar) {
                $query->where('p.enunciado', 'like', '%'.$buscar.'%')
                    ->orWhere('p.codigo', 'like', '%'.$buscar.'%');
                if (ctype_digit($buscar)) {
                    $query->orWhere('p.id', $buscar);
                }
            });
        }

        return view('admin.preguntas.index', [
            'preguntas' => $consulta->orderByDesc('p.id')->paginate(15)->withQueryString(),
            'temas' => DB::table('temas')->orderBy('nombre')->get(['id', 'nombre']),
            'areas' => DB::table('areas')->orderBy('nombre')->get(['id', 'nombre']),
            'totalPreguntas' => DB::table('preguntas')->count(),
            'puedeVaciarBanco' => $request->user('web')?->usuario === 'Super_Admin',
        ]);
    }

    public function show(int $pregunta): View
    {
        $registro = DB::table('preguntas as p')->join('temas as t', 't.id', '=', 'p.tema_id')
            ->join('areas as a', 'a.id', '=', 't.area_id')
            ->select('p.*', 't.nombre as tema', 'a.nombre as asignatura')
            ->where('p.id', $pregunta)->first();
        abort_unless($registro, 404);

        return view('admin.preguntas.ver', [
            'pregunta' => $registro,
            'alternativas' => DB::table('alternativas')->where('pregunta_id', $pregunta)->orderBy('letra')->get(),
        ]);
    }

    private function opciones(): array
    {
        return [
            'temas' => DB::table('temas as t')->join('areas as a', 'a.id', '=', 't.area_id')
                ->where('t.activo', true)->where('a.activo', true)
                ->select('t.id', 't.nombre', 'a.nombre as asignatura')
                ->orderBy('a.nombre')->orderBy('t.nombre')->get(),
        ];
    }

    public function create(): View
    {
        return view('admin.preguntas.formulario', $this->opciones() + [
            'pregunta' => null, 'alternativas' => collect(),
        ]);
    }

    public function edit(int $pregunta): View
    {
        $registro = DB::table('preguntas')->where('activo', true)->find($pregunta);
        abort_unless($registro, 404);

        return view('admin.preguntas.formulario', $this->opciones() + [
            'pregunta' => $registro,
            'alternativas' => DB::table('alternativas')->where('pregunta_id', $pregunta)->pluck('texto', 'letra'),
            'correcta' => DB::table('alternativas')->where('pregunta_id', $pregunta)->where('es_correcta', true)->value('letra'),
        ]);
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'tema_id' => ['required', Rule::exists('temas', 'id')->where('activo', true)],
            'enunciado' => ['required', 'string', 'max:15000'],
            'nivel' => ['required', Rule::in(['basico', 'intermedio', 'avanzado'])],
            'solucion' => ['required', 'string', 'max:15000'],
            'alternativas' => ['required', 'array', 'size:4'],
            'alternativas.A' => ['required', 'string', 'max:500'],
            'alternativas.B' => ['required', 'string', 'max:500'],
            'alternativas.C' => ['required', 'string', 'max:500'],
            'alternativas.D' => ['required', 'string', 'max:500'],
            'correcta' => ['required', Rule::in(['A', 'B', 'C', 'D'])],
            'imagen_archivo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
    }

    private function datosPregunta(array $validado, ?string $imagen): array
    {
        return [
            'tema_id' => $validado['tema_id'],
            'enunciado' => $validado['enunciado'],
            'nivel' => $validado['nivel'],
            'solucion' => $validado['solucion'],
            'imagen' => $imagen,
            'alternativas' => collect(['A', 'B', 'C', 'D'])->map(fn ($letra) => [
                'letra' => $letra,
                'texto' => $validado['alternativas'][$letra],
                'es_correcta' => $validado['correcta'] === $letra,
            ])->all(),
        ];
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $this->validar($request);
        $ruta = $request->file('imagen_archivo')?->store('preguntas', 'public');

        try {
            $id = $this->preguntas->guardar(
                $this->datosPregunta($datos, $ruta ? 'storage/'.$ruta : null),
                (int) $request->user('web')->id
            );
        } catch (Throwable $error) {
            if ($ruta) {
                Storage::disk('public')->delete($ruta);
            }
            throw $error;
        }

        return redirect()->route('admin.preguntas.edit', $id)
            ->with('status', 'Pregunta guardada en el banco.');
    }

    public function update(Request $request, int $pregunta): RedirectResponse
    {
        $actual = DB::table('preguntas')->where('activo', true)->find($pregunta);
        abort_unless($actual, 404);
        $datos = $this->validar($request);
        $ruta = $request->file('imagen_archivo')?->store('preguntas', 'public');
        $imagen = $ruta ? 'storage/'.$ruta : $actual->imagen;

        try {
            $this->preguntas->actualizar($pregunta, $this->datosPregunta($datos, $imagen));
        } catch (Throwable $error) {
            if ($ruta) {
                Storage::disk('public')->delete($ruta);
            }
            throw $error;
        }
        // No borrar el archivo anterior: podría estar referenciado por otro contenido.
        return redirect()->route('admin.preguntas.edit', $pregunta)
            ->with('status', 'Pregunta actualizada.');
    }

    public function destroy(int $pregunta): RedirectResponse
    {
        $this->preguntas->eliminar($pregunta);

        return redirect()->route('admin.preguntas.index')
            ->with('status', 'Pregunta desactivada sin borrar su historial.');
    }
}
