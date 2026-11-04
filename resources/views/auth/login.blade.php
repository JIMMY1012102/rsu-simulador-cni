@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login-cni-v2.css') }}?v=1">
@endpush

@section('content')
    <div class="login-page login-cni-v2">
        <img src="{{ asset('images/Fondo_General.png') }}"
             alt="" class="login-background" aria-hidden="true">

        <section class="login-presentation" aria-labelledby="login-presentation-title">
            <div class="login-presentation-copy">
                <p class="login-pretitle"><span></span> COLEGIO NACIONAL DE IMPERIAL</p>
                <h2 class="login-heading" id="login-presentation-title">
                    Tu próximo paso
                    <span>empieza <em>aquí.</em></span>
                </h2>
                <p class="login-presentation-subtitle">Prepárate, practica y alcanza tus metas con el Simulador CNI.</p>
            </div>

            <div class="login-mascot-scene" aria-hidden="true">
                <img src="{{ asset('images/Fondo_As.png') }}" alt="" class="login-mascot-seat">
                <img src="{{ asset('images/Fondo_Resplandor.png') }}" alt="" class="login-mascot-glow">
                <img src="{{ asset('images/gatito-cni.png') }}" alt="" class="login-mascot">
            </div>
            <p class="login-presentation-footer">Tu futuro comienza con una buena preparación.</p>
        </section>

        <section class="login card" aria-labelledby="login-title">
            <div class="login-school-identity">
                <img src="{{ asset('images/escudo-cni.png') }}"
                     alt="Escudo del Colegio Nacional de Imperial"
                     class="login-school-mark">
                <p class="login-form-kicker">COLEGIO NACIONAL DE IMPERIAL</p>
            </div>

            <div class="login-title-ornament" aria-hidden="true">
                <span></span>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="m2 9 10-5 10 5-10 5L2 9Z"/><path d="M6 11v5c3.5 3 8.5 3 12 0v-5"/><path d="M22 9v6"/></svg>
                <span></span>
            </div>

            <h1 id="login-title">BIENVENIDO AL <span>SIMULADOR CNI</span></h1>
            <p class="login-instructions">Ingresa con tus credenciales asignadas por el administrador.</p>

            <form method="POST" action="{{ route('login.store') }}" aria-label="Inicia sesión">
                @csrf

                <div class="login-form-field">
                    <label for="usuario">Usuario</label>
                    <div class="login-input-shell @error('usuario') is-invalid @enderror">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>
                        <input id="usuario" name="usuario" value="{{ old('usuario') }}"
                               maxlength="50" autocomplete="username" placeholder="Ingresa tu usuario"
                               aria-invalid="{{ $errors->has('usuario') ? 'true' : 'false' }}"
                               required autofocus>
                    </div>
                    @error('usuario') <p class="login-field-error">{{ $message }}</p> @enderror
                </div>

                <div class="login-form-field">
                    <label for="password">Contraseña</label>
                    <div class="login-input-shell @error('password') is-invalid @enderror">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 1 1 8 0v3"/></svg>
                        <input id="password" type="password" name="password"
                               maxlength="255" autocomplete="current-password" placeholder="Ingresa tu contraseña"
                               aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                               required>
                        <button type="button" class="login-password-toggle"
                                aria-controls="password" aria-pressed="false"
                                aria-label="Mostrar contraseña" title="Mostrar contraseña">
                            <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path d="M2.5 12S6 6 12 6s9.5 6 9.5 6-3.5 6-9.5 6S2.5 12 2.5 12Z"/>
                                <circle cx="12" cy="12" r="2.5"/>
                            </svg>
                        </button>
                    </div>
                    @error('password') <p class="login-field-error">{{ $message }}</p> @enderror
                </div>

                <button class="primary login-submit" type="submit">
                    <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M12 3h6a3 3 0 0 1 3 3v12a3 3 0 0 1-3 3h-6"/>
                    </svg>
                    Iniciar sesión
                </button>
            </form>
            <p class="login-security-note"><span aria-hidden="true">●</span> Acceso institucional seguro</p>
        </section>
    </div>

    <script>
        (function () {
            const toggle = document.querySelector('.login-cni-v2 .login-password-toggle');
            const password = document.getElementById('password');
            if (!toggle || !password) return;
            toggle.addEventListener('click', function () {
                const visible = password.type === 'password';
                password.type = visible ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', visible ? 'true' : 'false');
                toggle.setAttribute('aria-label', visible ? 'Ocultar contraseña' : 'Mostrar contraseña');
                toggle.title = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
            });
        })();
    </script>
@endsection
