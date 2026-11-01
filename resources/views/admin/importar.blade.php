@extends('admin.shell')
@section('admin-page', 'Importar Excel')
@section('admin-content')
<section class="cni-page-title cni-page-title--compact">
    <div><h1>Importar Excel</h1><p>Carga las preguntas y sus imágenes al banco del CNI.</p></div>
    <a href="{{ route('admin.importar.plantilla') }}" class="cni-button cni-template-download">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3v12m-5-5 5 5 5-5M4 16v4a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-4"/></svg>
        <span>Descargar plantilla Excel<small>.xlsx · Lista para completar</small></span>
    </a>
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
</section>
@if(session('importacion'))
<section class="cni-card cni-form-section" aria-labelledby="resultado-importacion">
    <h2 id="resultado-importacion">Resultado de la importación</h2>
    <pre class="cni-bank-resultado">{{ session('importacion') }}</pre>
</section>
@endif
@endsection
