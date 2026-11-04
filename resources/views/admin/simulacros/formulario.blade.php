@extends('admin.shell')
@section('admin-page', 'Configurar simulacro')
@section('admin-content')
<div class="cni-breadcrumb"><a href="{{ route('admin.simulacros.index') }}">Simulacros</a><span aria-hidden="true">/</span><span>Nuevo simulacro</span></div>
<section class="cni-page-title cni-page-title--compact">
    <div><span class="cni-eyebrow">NUEVA EVALUACIÓN</span><h1>Configurar simulacro</h1>
        <p>Personaliza el examen según el área o la carrera, sin duplicar el banco de preguntas.</p></div>
</section>
<div class="cni-editor-layout">
    <div class="cni-editor-main">
        <form method="POST" action="{{ route('admin.simulacros.store') }}" class="cni-form">@csrf
            <section class="cni-card cni-form-section">
                <div class="cni-form-section__heading"><span class="cni-step">01</span><div><h2>Datos del simulacro</h2><p>Identifica esta evaluación y quiénes pueden rendirla.</p></div></div>
                <label for="sim-nombre">Nombre del simulacro <span class="cni-required">*</span>
                    <input id="sim-nombre" name="nombre" required maxlength="150" value="{{ old('nombre') }}" placeholder="Ej. Simulacro Ingeniería de Sistemas">
                </label>
                <div class="cni-form-row">
                    <label for="sim-area">Área de postulación <span class="cni-required">*</span>
                        <select id="sim-area" name="area_postulacion_id" required><option value="">Selecciona un área</option>
                        @foreach($areas as $area)<option value="{{ $area->id }}" @selected(old('area_postulacion_id') == $area->id)>{{ $area->codigo }} · {{ $area->nombre }}</option>@endforeach
                        </select>
                    </label>
                    <label for="sim-carrera">Carrera <span class="cni-optional">Opcional</span>
                        <select id="sim-carrera" name="carrera_id"><option value="">Examen general del área</option>
                        @foreach($carreras as $c)<option value="{{ $c->id }}" @selected(old('carrera_id') == $c->id)>{{ $c->nombre }}</option>@endforeach
                        </select>
                    </label>
                </div>
                <p class="cni-field-help">Si no eliges una carrera, el simulacro estará disponible para los estudiantes de toda el área seleccionada.</p>
            </section>
            <section class="cni-card cni-form-section">
                <div class="cni-form-section__heading"><span class="cni-step">02</span><div><h2>Tiempo y disponibilidad</h2><p>Define cuánto dura y durante qué periodo estará disponible.</p></div></div>
                <label for="sim-duracion">Duración (minutos) <span class="cni-required">*</span>
                    <input id="sim-duracion" class="cni-input-short" type="number" name="tiempo_limite" min="1" max="65535" required value="{{ old('tiempo_limite', 120) }}">
                </label>
                <div class="cni-form-row">
                    <label for="sim-inicio">Inicio <span class="cni-required">*</span>
                        <input id="sim-inicio" type="datetime-local" name="fecha_inicio" required value="{{ old('fecha_inicio', now()->format('Y-m-d\TH:i')) }}">
                    </label>
                    <label for="sim-fin">Fin <span class="cni-required">*</span>
                        <input id="sim-fin" type="datetime-local" name="fecha_fin" required value="{{ old('fecha_fin', now()->addDays(30)->format('Y-m-d\TH:i')) }}">
                    </label>
                </div>
            </section>
            <section class="cni-card cni-form-section">
                <div class="cni-form-section__heading"><span class="cni-step">03</span><div><h2>Distribución de preguntas</h2><p>Establece cuántas preguntas se sortearán de cada asignatura.</p></div></div>
                <div class="cni-subjects-header"><span>Asignatura</span><span>Cupo</span></div>
                <div class="cni-subject-list">
                @forelse($asignaturas as $a)
                    <label class="cni-subject-row" for="cupo-{{ $a->id }}">
                        <span class="cni-subject-row__name"><strong>{{ $a->nombre }}</strong><small>{{ $banco[$a->id] ?? 0 }} preguntas válidas disponibles</small></span>
                        <input id="cupo-{{ $a->id }}" type="number" min="0" max="1000" name="distribucion[{{ $a->id }}]" required value="{{ old('distribucion.'.$a->id, 0) }}" aria-label="Número de preguntas de {{ $a->nombre }}">
                    </label>
                @empty
                    <div class="cni-empty cni-empty--small"><p>Primero registra asignaturas, temas y preguntas en el banco.</p><a href="{{ route('admin.inicio') }}#catalogos">Ir a catálogos →</a></div>
                @endforelse
                </div>
                <p class="cni-field-help">El cupo de cada asignatura no debe superar las preguntas válidas de su banco.</p>
                <div class="cni-form-actions"><a class="cni-button cni-button--secondary" href="{{ route('admin.simulacros.index') }}">Cancelar</a>
                    <button class="cni-button cni-button--primary" type="submit" @disabled($asignaturas->isEmpty())>Publicar simulacro <span aria-hidden="true">→</span></button>
                </div>
            </section>
        </form>
    </div>
    <aside class="cni-editor-aside" aria-label="Recomendaciones">
        <section class="cni-card cni-guidance"><div class="cni-guidance__top"><span aria-hidden="true">✦</span><strong>Cómo funciona</strong></div>
            <h3>Un banco, varios perfiles</h3><p>Las preguntas son compartidas. Cada simulacro define la cantidad que necesita de cada asignatura.</p>
            <div class="cni-profile-example"><span>Ejemplo de distribución</span>
                <div><small>Ingenierías · Matemática</small><span class="cni-progress-track"><span style="width:75%"></span></span></div>
                <div><small>Humanidades · Matemática</small><span class="cni-progress-track"><span style="width:25%"></span></span></div>
                <p class="cni-example-footnote">Ilustración de perfiles, no una configuración guardada.</p>
            </div>
        </section>
        <div class="cni-info-card"><span aria-hidden="true">ⓘ</span><p>El sistema verificará que haya suficientes preguntas válidas antes de publicar.</p></div>
    </aside>
</div>
@endsection
