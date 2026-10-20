@extends('layouts.app')
@section('content')
<section class="card">
    <p class="eyebrow">RSU · UNIVERSIDAD NACIONAL DE CAÑETE</p>
    <h1>Simulador CNI</h1>
    <p>Practica y consulta tus avances de preparación académica.</p>
    @auth('web')
        <a href="{{ route('panel') }}">Ir a mi panel →</a>
    @else
        <a href="{{ route('login') }}">Iniciar sesión →</a>
    @endauth
</section>
@endsection
