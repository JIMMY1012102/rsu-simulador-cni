@extends('admin.shell')
@section('admin-page', 'Preguntas')
@section('admin-content')
<section class="cni-page-title">
    <div><h1>Pregunta {{ $pregunta->codigo ?: '#'.$pregunta->id }}</h1><p>{{ $pregunta->asignatura }} · {{ $pregunta->tema }} · {{ $pregunta->activo ? 'Activa' : 'Inactiva' }}</p></div>
    <a class="cni-button cni-button--secondary" href="{{ route('admin.preguntas.index') }}">Volver a preguntas</a>
</section>
<section class="cni-card cni-form-section cni-question-detail">
    <h2>Enunciado</h2>
    @if($pregunta->enunciado)<p>{{ $pregunta->enunciado }}</p>@endif
    @if($pregunta->imagen)<img src="{{ asset($pregunta->imagen) }}" alt="Enunciado de la pregunta {{ $pregunta->codigo ?: $pregunta->id }}">@endif
    <h2>Alternativas</h2>
    <ul>
        @foreach($alternativas as $alternativa)
        <li><strong>{{ $alternativa->letra }}.</strong> {{ $alternativa->texto }}
            @if($alternativa->imagen)<img src="{{ asset($alternativa->imagen) }}" alt="Alternativa {{ $alternativa->letra }}">@endif
            @if($alternativa->es_correcta)<span class="cni-question-status is-active">Correcta</span>@endif
        </li>
        @endforeach
    </ul>
    @if($pregunta->solucion)<h2>Solución</h2><p>{{ $pregunta->solucion }}</p>@endif
</section>
@endsection
