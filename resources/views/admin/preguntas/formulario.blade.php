@extends('admin.shell')
@section('admin-page', 'Editor de preguntas')
@section('admin-content')
<div class="cni-breadcrumb"><a href="{{ route('admin.preguntas.index') }}">Banco de preguntas</a><span aria-hidden="true">/</span><span>{{ $pregunta ? 'Editar pregunta' : 'Nueva pregunta' }}</span></div>
<section class="cni-page-title cni-page-title--compact">
    <div><span class="cni-eyebrow">EDITOR DE CONTENIDO</span><h1>{{ $pregunta ? 'Editar pregunta #'.$pregunta->id : 'Crear una pregunta' }}</h1>
        <p>Completa el enunciado, las alternativas y la solución para agregarla al banco.</p></div>
</section>
<div class="cni-editor-layout">
    <div class="cni-editor-main">
        <form id="form-pregunta" method="POST" enctype="multipart/form-data" action="{{ $pregunta ? route('admin.preguntas.update', $pregunta->id) : route('admin.preguntas.store') }}" class="cni-form">
            @csrf @if($pregunta)@method('PUT')@endif
            <section class="cni-card cni-form-section">
                <div class="cni-form-section__heading"><span class="cni-step">01</span><div><h2>Información de la pregunta</h2><p>Define dónde pertenece y cuál es su contenido.</p></div></div>
                <div class="cni-form-row">
                    <label for="pregunta-tema">Asignatura y tema <span class="cni-required">*</span>
                        <select id="pregunta-tema" name="tema_id" required>
                            <option value="">Seleccionar tema</option>
                            @foreach($temas as $t)<option value="{{ $t->id }}" @selected(old('tema_id', $pregunta->tema_id ?? '') == $t->id)>{{ $t->asignatura }} · {{ $t->nombre }}</option>@endforeach
                        </select>
                    </label>
                    <label for="pregunta-nivel">Dificultad <span class="cni-required">*</span>
                        <select id="pregunta-nivel" name="nivel" required>
                        @foreach(['basico'=>'Básico','intermedio'=>'Intermedio','avanzado'=>'Avanzado'] as $value=>$label)
                            <option value="{{ $value }}" @selected(old('nivel', $pregunta->nivel ?? 'basico') === $value)>{{ $label }}</option>
                        @endforeach
                        </select>
                    </label>
                </div>
                <label for="pregunta-enunciado">Enunciado <span class="cni-required">*</span>
                    <textarea id="pregunta-enunciado" name="enunciado" required rows="5" maxlength="15000" placeholder="Escribe aquí la pregunta que verá el estudiante...">{{ old('enunciado', $pregunta->enunciado ?? '') }}</textarea>
                </label>
                <label for="pregunta-imagen">Imagen de apoyo <span class="cni-optional">Opcional</span>
                    <span class="cni-upload"><span class="cni-upload__symbol" aria-hidden="true">⇧</span><span><strong>Adjuntar una imagen</strong><small>JPG, PNG o WebP · Máximo 2 MB</small></span><input id="pregunta-imagen" type="file" name="imagen_archivo" accept="image/jpeg,image/png,image/webp"></span>
                </label>
                @if($pregunta?->imagen)<div class="cni-image-preview"><span>Imagen actual:</span><img src="{{ asset($pregunta->imagen) }}" alt="Imagen actual de la pregunta"></div>@endif
            </section>
            <section class="cni-card cni-form-section">
                <div class="cni-form-section__heading"><span class="cni-step">02</span><div><h2>Alternativas de respuesta</h2><p>Escribe cuatro opciones y marca la correcta.</p></div></div>
                <div class="cni-alternatives">
                @foreach(['A','B','C','D'] as $letra)
                    <label class="cni-alternative" for="alternativa-{{ $letra }}"><span class="cni-alternative__letter">{{ $letra }}</span><span class="cni-alternative__content">Opción {{ $letra }}
                        <input id="alternativa-{{ $letra }}" name="alternativas[{{ $letra }}]" required maxlength="500" placeholder="Escribe la respuesta {{ $letra }}..." value="{{ old('alternativas.'.$letra, $alternativas[$letra] ?? '') }}">
                    </span></label>
                @endforeach
                </div>
                <label for="pregunta-correcta" class="cni-correct-label">Respuesta correcta <span class="cni-required">*</span>
                    <select id="pregunta-correcta" name="correcta" required>
                        @foreach(['A','B','C','D'] as $letra)<option value="{{ $letra }}" @selected(old('correcta', $correcta ?? 'A') === $letra)>Alternativa {{ $letra }}</option>@endforeach
                    </select>
                </label>
            </section>
            <section class="cni-card cni-form-section">
                <div class="cni-form-section__heading"><span class="cni-step">03</span><div><h2>Solución y revisión</h2><p>Agrega una explicación que ayude a comprender la respuesta.</p></div></div>
                <label for="pregunta-solucion">Explicación de la respuesta <span class="cni-required">*</span>
                    <textarea id="pregunta-solucion" name="solucion" required rows="4" maxlength="15000" placeholder="Explica el procedimiento o por qué esa alternativa es correcta...">{{ old('solucion', $pregunta->solucion ?? '') }}</textarea>
                </label>
                <div class="cni-form-actions"><a class="cni-button cni-button--secondary" href="{{ route('admin.preguntas.index') }}">Cancelar</a>
                    <button class="cni-button cni-button--primary" type="submit">{{ $pregunta ? 'Guardar cambios' : 'Registrar pregunta' }} <span aria-hidden="true">→</span></button>
                </div>
            </section>
        </form>
    </div>
    <aside class="cni-editor-aside" aria-label="Ayuda para editar preguntas">
        <section class="cni-card cni-guidance">
            <div class="cni-guidance__top"><span aria-hidden="true">✦</span><strong>Guía rápida</strong></div>
            <h3>Antes de guardar</h3>
            <ul class="cni-checklist">
                <li>Elige la asignatura y el tema correctos.</li>
                <li>Escribe un enunciado claro y sin ambigüedades.</li>
                <li>Incluye cuatro alternativas diferentes.</li>
                <li>Selecciona una sola respuesta correcta.</li>
                <li>Agrega una explicación útil.</li>
            </ul>
        </section>
        <div class="cni-info-card"><span aria-hidden="true">ⓘ</span><p>Las preguntas usadas en un examen no pueden editarse; crea otra versión cuando corresponda.</p></div>
    </aside>
</div>
@endsection
