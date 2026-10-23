@extends('admin.shell')
@section('admin-page', 'Resumen')
@section('admin-content')
<section class="cni-hero">
    <div class="cni-hero__copy">
        <span class="cni-hero__eyebrow"><span class="cni-hero__sparkle">✦</span> CENTRO DE CONTROL ACADÉMICO</span>
        <h1>Panel del administrador</h1>
        <p>Todo lo que necesitas para organizar tu banco de preguntas y preparar los próximos simulacros.</p>
        <div class="cni-hero__actions">
            <a class="cni-button cni-button--white" href="{{ route('admin.preguntas.create') }}"><span aria-hidden="true">＋</span> Crear pregunta</a>
            <a class="cni-button cni-button--outline-white" href="{{ route('admin.simulacros.create') }}">Nuevo simulacro <span aria-hidden="true">↗</span></a>
        </div>
    </div>
    <div class="cni-hero__art" aria-hidden="true"><span class="cni-hero__halo"></span><img src="{{ asset('images/gatito-cni.png') }}" alt=""></div>
</section>

<div class="cni-section-heading">
    <div><span class="cni-eyebrow">VISTA GENERAL</span><h2>Tu espacio de trabajo</h2></div>
    <span class="cni-muted">Información actual del sistema</span>
</div>
<section class="cni-stat-grid" aria-label="Indicadores académicos">
    <article class="cni-stat">
        <span class="cni-stat__icon cni-stat__icon--blue" aria-hidden="true">?</span>
        <span class="cni-stat__number">{{ number_format($totales['preguntas']) }}</span>
        <span class="cni-stat__label">Preguntas activas</span>
        <a href="{{ route('admin.preguntas.index') }}">Ver banco <span aria-hidden="true">↗</span></a>
    </article>
    <article class="cni-stat">
        <span class="cni-stat__icon cni-stat__icon--cyan" aria-hidden="true">≡</span>
        <span class="cni-stat__number">{{ number_format($totales['simulacros']) }}</span>
        <span class="cni-stat__label">Simulacros creados</span>
        <a href="{{ route('admin.simulacros.index') }}">Ver simulacros <span aria-hidden="true">↗</span></a>
    </article>
    <article class="cni-stat">
        <span class="cni-stat__icon cni-stat__icon--gold" aria-hidden="true">◇</span>
        <span class="cni-stat__number">{{ number_format($carreras->count()) }}</span>
        <span class="cni-stat__label">Carreras registradas</span>
        <a href="#catalogos">Administrar <span aria-hidden="true">↗</span></a>
    </article>
</section>

<div class="cni-section-heading cni-section-heading--spaced">
    <div><span class="cni-eyebrow">SIGUIENTE PASO</span><h2>Accesos rápidos</h2></div>
</div>
<div class="cni-shortcuts">
    <a class="cni-shortcut" href="{{ route('admin.preguntas.create') }}">
        <span class="cni-shortcut__icon cni-shortcut__icon--blue" aria-hidden="true">＋</span>
        <span><strong>Agregar preguntas</strong><small>Crea preguntas con respuestas e imágenes de apoyo.</small></span>
        <span class="cni-shortcut__arrow" aria-hidden="true">↗</span>
    </a>
    <a class="cni-shortcut" href="{{ route('admin.simulacros.create') }}">
        <span class="cni-shortcut__icon cni-shortcut__icon--cyan" aria-hidden="true">▤</span>
        <span><strong>Configurar simulacro</strong><small>Personaliza la distribución según el área o carrera.</small></span>
        <span class="cni-shortcut__arrow" aria-hidden="true">↗</span>
    </a>
</div>

<section id="catalogos" class="cni-catalog-section" aria-labelledby="catalogos-titulo">
    <div class="cni-section-heading cni-section-heading--spaced">
        <div><span class="cni-eyebrow">CONFIGURACIÓN</span><h2 id="catalogos-titulo">Catálogos académicos</h2><p>Crea primero una asignatura, luego sus temas y finalmente registra las preguntas.</p></div>
    </div>
    <div class="cni-catalog-grid">
        <section class="cni-card cni-catalog-card">
            <div class="cni-card__head"><span class="cni-card__number">01</span><span><h3>Asignaturas</h3><p>Organiza las materias del examen.</p></span></div>
            <form action="{{ route('admin.asignatura.guardar') }}" method="POST" class="cni-form">@csrf
                <label for="asignatura-nombre">Nombre de la asignatura<input id="asignatura-nombre" name="nombre" required maxlength="100" value="{{ old('nombre') }}" placeholder="Ej. Matemática"></label>
                <label for="asignatura-bloque">Bloque académico<select id="asignatura-bloque" name="bloque" required>
                    <option value="conocimientos">Conocimientos</option><option value="habilidad_verbal">Habilidad verbal</option><option value="habilidad_logico_matematica">Habilidad lógico-matemática</option>
                </select></label>
                <button class="cni-button cni-button--primary cni-button--full" type="submit">Guardar asignatura <span aria-hidden="true">→</span></button>
            </form>
            <div class="cni-catalog-preview"><strong>Registradas <span class="cni-count">{{ $asignaturas->count() }}</span></strong>
                <ul class="cni-chip-list">@forelse($asignaturas as $a)<li>{{ $a->nombre }}</li>@empty<li class="cni-chip-list__empty">Sin asignaturas todavía</li>@endforelse</ul>
            </div>
        </section>
        <section class="cni-card cni-catalog-card">
            <div class="cni-card__head"><span class="cni-card__number">02</span><span><h3>Temas</h3><p>Divide cada materia en contenidos.</p></span></div>
            <form action="{{ route('admin.tema.guardar') }}" method="POST" class="cni-form">@csrf
                <label for="tema-asignatura">Asignatura<select id="tema-asignatura" name="area_id" required><option value="">Selecciona una asignatura</option>
                    @foreach($asignaturas as $a)<option value="{{ $a->id }}" @selected(old('area_id') == $a->id)>{{ $a->nombre }}</option>@endforeach
                </select></label>
                <label for="tema-nombre">Nombre del tema<input id="tema-nombre" name="nombre" required maxlength="150" value="{{ old('nombre') }}" placeholder="Ej. Álgebra"></label>
                <button class="cni-button cni-button--primary cni-button--full" type="submit" @disabled($asignaturas->isEmpty())>Guardar tema <span aria-hidden="true">→</span></button>
            </form>
            <div class="cni-catalog-preview"><strong>Registrados <span class="cni-count">{{ $temas->count() }}</span></strong>
                <ul class="cni-chip-list">@forelse($temas as $t)<li title="{{ $t->asignatura }}">{{ $t->nombre }}</li>@empty<li class="cni-chip-list__empty">Sin temas todavía</li>@endforelse</ul>
            </div>
        </section>
        <section class="cni-card cni-catalog-card">
            <div class="cni-card__head"><span class="cni-card__number">03</span><span><h3>Carreras</h3><p>Vincula cada carrera con su área A–E.</p></span></div>
            <form action="{{ route('admin.carrera.guardar') }}" method="POST" class="cni-form">@csrf
                <label for="carrera-area">Área de postulación<select id="carrera-area" name="area_postulacion_id" required><option value="">Selecciona un área</option>
                    @foreach($areasPostulacion as $a)<option value="{{ $a->id }}" @selected(old('area_postulacion_id') == $a->id)>{{ $a->codigo }} · {{ $a->nombre }}</option>@endforeach
                </select></label>
                <label for="carrera-nombre">Nombre de la carrera<input id="carrera-nombre" name="nombre" required maxlength="150" value="{{ old('nombre') }}" placeholder="Ej. Ingeniería de Sistemas"></label>
                <button class="cni-button cni-button--primary cni-button--full" type="submit">Guardar carrera <span aria-hidden="true">→</span></button>
            </form>
            <div class="cni-catalog-preview"><strong>Registradas <span class="cni-count">{{ $carreras->count() }}</span></strong>
                <ul class="cni-chip-list">@forelse($carreras as $c)<li title="{{ $c->nombre }}">{{ $c->codigo }} · {{ $c->nombre }}</li>@empty<li class="cni-chip-list__empty">Sin carreras todavía</li>@endforelse</ul>
            </div>
        </section>
    </div>
</section>
@endsection
