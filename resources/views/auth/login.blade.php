@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login-cni-v2.css') }}?v=7">
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


            <div class="login-illustration" aria-hidden="true">
                <!-- Escenografía académica vectorial: edificio, libros y birrete. -->
                <svg class="login-campus" viewBox="0 0 1000 540" fill="none"
                     xmlns="http://www.w3.org/2000/svg" focusable="false">
                    <defs>
                        <linearGradient id="cniFacade" x1="0" y1="0" x2="0" y2="1">
                            <stop stop-color="#4ab3ff" stop-opacity=".48"/>
                            <stop offset="1" stop-color="#124f99" stop-opacity=".65"/>
                        </linearGradient>
                        <linearGradient id="cniGlass" x1="0" y1="0" x2="0" y2="1">
                            <stop stop-color="#9ed8ff" stop-opacity=".5"/>
                            <stop offset="1" stop-color="#105196" stop-opacity=".55"/>
                        </linearGradient>
                        <linearGradient id="cniBookBlue" x1="0" y1="0" x2="1" y2="1">
                            <stop stop-color="#2685e3"/>
                            <stop offset="1" stop-color="#052a68"/>
                        </linearGradient>
                        <radialGradient id="cniLight">
                            <stop stop-color="#3ab6ff" stop-opacity=".4"/>
                            <stop offset="1" stop-color="#0054bc" stop-opacity="0"/>
                        </radialGradient>
                    </defs>
                    <ellipse cx="500" cy="470" rx="485" ry="75" fill="url(#cniLight)"/>
                    <!-- Silueta del Colegio Nacional de Imperial -->
                    <g class="login-campus-school" stroke="#89c9ff" stroke-width="3.5" stroke-linejoin="round">
                        <path d="M265 226 560 112 850 226v20H265v-20Z"
                              fill="#235d9e" fill-opacity=".66"/>
                        <path d="m540 132 20-40 20 40" fill="#2769aa"/>
                        <path d="M560 93V53" stroke="#addaff"/>
                        <path d="M560 55q26-3 47 7v35q-20-11-47-5V55Z"
                              fill="#5eaeff" fill-opacity=".55" stroke-width="1"/>
                        <rect x="298" y="247" width="520" height="208"
                              fill="url(#cniFacade)"/>
                        <path d="M298 277h520M298 410h520" stroke-opacity=".34"/>
                        <rect x="318" y="259" width="480" height="28" fill="#12467f" fill-opacity=".44" stroke="none"/>
                        <rect x="360" y="294" width="86" height="134" rx="45"
                              fill="url(#cniGlass)" stroke-opacity=".55"/>
                        <rect x="468" y="294" width="86" height="134" rx="45"
                              fill="url(#cniGlass)" stroke-opacity=".55"/>
                        <rect x="575" y="294" width="86" height="134" rx="45"
                              fill="url(#cniGlass)" stroke-opacity=".55"/>
                        <rect x="684" y="294" width="86" height="134" rx="45"
                              fill="url(#cniGlass)" stroke-opacity=".55"/>
                        <path d="M403 295v131M511 295v131M618 295v131M726 295v131"
                              stroke-opacity=".4" stroke-width="2"/>
                        <path d="M352 365h102M460 365h102M568 365h102M676 365h102"
                              stroke-opacity=".35" stroke-width="2"/>
                        <rect x="495" y="196" width="130" height="43" rx="6"
                              fill="#0c346b" fill-opacity=".57" stroke-opacity=".65"/>
                        <text x="560" y="227" font-size="31" font-family="Georgia,serif"
                              font-weight="bold" fill="#a8dbff" text-anchor="middle"
                              stroke="none">CNI</text>
                        <path d="M281 455h560M256 469h610" stroke-opacity=".4"/>
                    </g>
                    <!-- Libros apilados: dentro del área visible, no cortados -->
                    <g class="login-campus-books" stroke-linejoin="round">
                        <ellipse cx="121" cy="505" rx="123" ry="19" fill="#001538" opacity=".45"/>
                        <path d="M25 426h192l-10 44H17l8-44Z" fill="url(#cniBookBlue)" stroke="#5caefb" stroke-width="3"/>
                        <path d="M33 432h172l-7 30H26l7-30Z" fill="#f3f7ff"/>
                        <path d="M14 472h208l-10 38H4l10-38Z" fill="#0a387e" stroke="#509be8" stroke-width="3"/>
                        <path d="M24 479h186l-7 23H17l7-23Z" fill="#e0f1ff"/>
                        <path d="M29 429h8v31" stroke="#edbf59" stroke-width="4"/>
                        <path d="M38 426 56 388l154 31-5 16" fill="#124988" stroke="#63abff" stroke-width="3"/>
                        <path d="m57 395 144 28-3 8-144-29" fill="#e5f3ff"/>
                    </g>
                    <!-- Birrete junto al escenario, sin superponerse a la tarjeta -->
                    <g class="login-campus-cap" stroke-linejoin="round">
                        <ellipse cx="871" cy="499" rx="112" ry="19" fill="#001638" opacity=".42"/>
                        <path d="m762 429 110-48 111 48-111 44-110-44Z"
                              fill="#093974" stroke="#8cc6ff" stroke-width="3"/>
                        <path d="M817 452v34q56 32 112 0v-34" fill="#1254a6"
                              stroke="#7cb5f9" stroke-width="3"/>
                        <path d="M870 429h83v57" stroke="#f9ce64" stroke-width="4"/>
                        <path d="m946 486 14 1 7 30-25-1 4-30Z" fill="#ffd464"/>
                    </g>
                </svg>
                <div class="login-gold-stage"></div>
                <img src="{{ asset('images/gatito-cni.png') }}"
                     alt="" class="login-mascot">
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
