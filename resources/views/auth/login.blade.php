@extends('layouts.app')
@section('content')
    <div class="login-page">
        <img
            src="{{ asset('images/Fondo_General.png') }}"
            alt=""
            class="login-background"
            aria-hidden="true"
        >

        <section class="login-presentation" aria-labelledby="login-presentation-title">
            <div class="login-presentation-copy">
                <h2 class="login-heading" id="login-presentation-title">Tu próximo paso empieza aquí.</h2>
            </div>

            <div class="login-mascot-scene">
                <img
                    src="{{ asset('images/Fondo_As.png') }}"
                    alt=""
                    class="login-mascot-seat"
                    aria-hidden="true"
                >
                <img
                    src="{{ asset('images/Fondo_Resplandor.png') }}"
                    alt=""
                    class="login-mascot-glow"
                    aria-hidden="true"
                >
                <img
                    src="{{ asset('images/gatito-cni.png') }}"
                    alt="Jesu, la mascota del Simulador CNI"
                    class="login-mascot"
                >
            </div>
        </section>

        <section class="login card" aria-labelledby="login-title">
            <img
                src="{{ asset('images/escudo-cni.png') }}"
                alt="Escudo del Colegio Nacional de Imperial"
                class="login-school-mark"
            >
            <p class="login-form-kicker">Colegio Nacional de Imperial</p>
            <h1 id="login-title">BIENVENIDO AL SIMULADOR CNI</h1>
            <p class="login-instructions">Ingresa con el usuario asignado por el administrador.</p>

            <form method="POST" action="{{ route('login.store') }}" aria-label="Inicia sesión">
                @csrf

                <label for="usuario">Usuario</label>
                <input
                    id="usuario"
                    name="usuario"
                    value="{{ old('usuario') }}"
                    maxlength="50"
                    autocomplete="username"
                    placeholder="Ingresa tu usuario"
                    required
                    autofocus
                >

                <label for="password">Contraseña</label>
                <input
                    id="password"
                    type="password"
                    name="password"
                    maxlength="255"
                    autocomplete="current-password"
                    placeholder="Ingresa tu contraseña"
                    required
                >

                <button class="primary" type="submit">Iniciar sesión</button>
            </form>
        </section>
    </div>

@endsection
