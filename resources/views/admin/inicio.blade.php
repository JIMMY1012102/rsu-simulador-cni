@extends('layouts.app')
@push('styles')<link rel="stylesheet" href="{{ asset('css/admin.css') }}">@endpush
@section('content')
<div class="admin">
    <div class="admin-head">
        <div><p class="admin-kicker">ADMINISTRACIÓN ACADÉMICA</p><h1>Panel del administrador</h1>
        <p>Configura las carreras, el banco de preguntas y los simulacros del CNI.</p></div>
        <nav class="admin-actions" aria-label="Módulos administrativos">
            <a class="admin-btn" href="{{ route('admin.preguntas.index') }}">Banco de preguntas</a>
            <a class="admin-btn" href="{{ route('admin.simulacros.index') }}">Simulacros</a>
        </nav>
    </div>
    @if(session('status'))<p class="admin-success" role="status">{{ session('status') }}</p>@endif
    <div class="admin-stats">
        <div><strong>{{ $totales['preguntas'] }}</strong><span>Preguntas activas</span></div>
        <div><strong>{{ $totales['simulacros'] }}</strong><span>Simulacros creados</span></div>
        <div><strong>{{ $carreras->count() }}</strong><span>Carreras registradas</span></div>
    </div>

    <div class="admin-grid">
        <section class="admin-card"><h2>Registrar asignatura</h2>
            <form action="{{ route('admin.asignatura.guardar') }}" method="POST">@csrf
                <label>Nombre de la asignatura<input name="nombre" required maxlength="100" value="{{ old('nombre') }}" placeholder="Ej. Matemática"></label>
                <label>Bloque<select name="bloque" required>
                    <option value="conocimientos">Conocimientos</option>
                    <option value="habilidad_verbal">Habilidad verbal</option>
                    <option value="habilidad_logico_matematica">Habilidad lógico-matemática</option>
                </select></label>
                <button class="admin-btn" type="submit">Guardar asignatura</button>
            </form>
            <ul class="admin-list">@forelse($asignaturas as $a)<li>{{ $a->nombre }}</li>@empty<li>Registra primero una asignatura.</li>@endforelse</ul>
        </section>

        <section class="admin-card"><h2>Registrar tema</h2>
            <form action="{{ route('admin.tema.guardar') }}" method="POST">@csrf
                <label>Asignatura<select name="area_id" required>
                    <option value="">Selecciona...</option>
                    @foreach($asignaturas as $a)<option value="{{ $a->id }}">{{ $a->nombre }}</option>@endforeach
                </select></label>
                <label>Nombre del tema<input name="nombre" required maxlength="150" placeholder="Ej. Álgebra"></label>
                <button class="admin-btn" type="submit" @disabled($asignaturas->isEmpty())>Guardar tema</button>
            </form>
            <ul class="admin-list">@foreach($temas as $t)<li>{{ $t->asignatura }} — {{ $t->nombre }}</li>@endforeach</ul>
        </section>

        <section class="admin-card"><h2>Registrar carrera</h2>
            <form action="{{ route('admin.carrera.guardar') }}" method="POST">@csrf
                <label>Área de postulación<select name="area_postulacion_id" required>
                    <option value="">Selecciona...</option>
                    @foreach($areasPostulacion as $a)<option value="{{ $a->id }}">{{ $a->codigo }} · {{ $a->nombre }}</option>@endforeach
                </select></label>
                <label>Carrera<input name="nombre" required maxlength="150" placeholder="Ej. Ingeniería de Sistemas"></label>
                <button class="admin-btn" type="submit">Guardar carrera</button>
            </form>
            <ul class="admin-list">@foreach($carreras as $c)<li>{{ $c->codigo }} — {{ $c->nombre }}</li>@endforeach</ul>
        </section>
    </div>
</div>
@endsection
