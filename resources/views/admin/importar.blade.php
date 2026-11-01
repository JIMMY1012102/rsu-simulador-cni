@extends('admin.shell')
@section('admin-page', 'Importar Excel')
@section('admin-content')
<section class="cni-page-title cni-page-title--compact">
    <div><h1>Importar Excel</h1><p>Carga las preguntas y sus imágenes al banco del CNI.</p></div>
</section>
<section class="cni-card cni-form-section cni-bank-importar" aria-labelledby="importar-titulo">
    <h2 id="importar-titulo">Cargar banco de preguntas</h2>
    <p>Selecciona el archivo .xlsx con la hoja <strong>Banco de preguntas</strong>.</p>
    <form method="POST" action="{{ route('admin.importar.cargar') }}" enctype="multipart/form-data" class="cni-form">
        @csrf
        <label for="banco-excel">Archivo Excel
            <input id="banco-excel" type="file" name="archivo" accept=".xlsx" required aria-describedby="excel-formato">
        </label>
        <p id="excel-formato">Columnas: Código, Curso, Pregunta (imagen), A, B, C, D, E y Respuesta correcta.</p>
        <button type="submit" class="cni-button cni-button--primary">Importar preguntas</button>
    </form>
    <details class="cni-bank-formato">
        <summary>Ver formato y códigos de curso</summary>
        <p>Usa códigos únicos como AM_1 o LE_12. Coloca la imagen del enunciado en «Pregunta (imagen)» y marca la clave con una letra de A a E. Las alternativas pueden contener texto o imagen.</p>
        <p>Las filas sin clave o con contenido incompleto se señalarán en el resultado de la importación.</p>
        <div class="cni-table-scroll"><table class="cni-table"><thead><tr><th>Código</th><th>Curso</th></tr></thead><tbody>
            @foreach(\App\Support\PerfilExamenCni::CURSOS as $codigo => [$curso, $bloque])
                <tr><td>{{ $codigo }}_…</td><td>{{ $curso }}</td></tr>
            @endforeach
        </tbody></table></div>
    </details>
</section>
@if(session('importacion'))
<section class="cni-card cni-form-section" aria-labelledby="resultado-importacion">
    <h2 id="resultado-importacion">Resultado de la importación</h2>
    <pre class="cni-bank-resultado">{{ session('importacion') }}</pre>
</section>
@endif
@endsection
