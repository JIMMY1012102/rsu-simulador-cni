@extends('layouts.app')
@section('content')
<section class="login card">
    <p class="eyebrow">RSU · CNI</p>
    <h1>Inicia sesión</h1>
    <p>Ingresa con la cuenta que te entregó el administrador.</p>
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <label for="usuario">Usuario</label>
        <input id="usuario" name="usuario" value="{{ old('usuario') }}" maxlength="50" autocomplete="username" required autofocus>
        <label for="password">Contraseña</label>
        <input id="password" type="password" name="password" maxlength="255" autocomplete="current-password" required>
        <button class="primary">Ingresar</button>
    </form>
</section>
@endsection
