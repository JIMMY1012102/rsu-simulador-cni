@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login-cni-v2.css') }}?v=5">
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
            <!-- Ilustración académica original (SVG escalable): edificio, libros y birrete.
                 Se coloca detrás del gatito CNI, sin sustituir la mascota original. -->
            <div class="login-campus" aria-hidden="true">
                <svg viewBox="0 0 1000 565" xmlns="http://www.w3.org/2000/svg" fill="none" role="presentation" focusable="false">
                    <defs>
                        <linearGradient id="campusFacade" x1="0" y1="0" x2="0" y2="1">
                            <stop stop-color="#2582da" stop-opacity=".44"/>
                            <stop offset="1" stop-color="#092f76" stop-opacity=".72"/>
                        </linearGradient>
                        <linearGradient id="campusRoof" x1="0" y1="0" x2="1" y2="1">
                            <stop stop-color="#85c5ff" stop-opacity=".42"/>
                            <stop offset="1" stop-color="#2767b3" stop-opacity=".4"/>
                        </linearGradient>
                        <linearGradient id="campusBook" x1="0" y1="0" x2="1" y2="1">
                            <stop stop-color="#164d9a"/><stop offset="1" stop-color="#072455"/>
                        </linearGradient>
                        <linearGradient id="campusGround" x1="0" y1="0" x2="0" y2="1">
                            <stop stop-color="#45b3ff" stop-opacity=".24"/>
                            <stop offset="1" stop-color="#06245f" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <ellipse cx="505" cy="526" rx="485" ry="56" fill="url(#campusGround)"/>
                    <!-- Árboles tenues al fondo -->
                    <g opacity=".27" fill="#062f69">
                        <path d="M54 505v-183h11v183M89 504V294h9v210M137 503V333h10v170M902 508V286h13v222M944 510V322h10v188"/>
                        <ellipse cx="59" cy="329" rx="67" ry="73"/><ellipse cx="104" cy="306" rx="77" ry="91"/>
                        <ellipse cx="934" cy="299" rx="70" ry="83"/><ellipse cx="894" cy="331" rx="58" ry="68"/>
                    </g>
                    <!-- Colegio estilizado -->
                    <g transform="translate(310 78)" stroke="#74bdff" stroke-width="3.4" stroke-opacity=".46">
                        <path d="M38 182 300 81 564 182v23H38v-23Z" fill="url(#campusRoof)"/>
                        <path d="m272 96 28-53 28 53" fill="#0d4a94" opacity=".9"/>
                        <path d="M299 44V0" stroke="#a3cfff" stroke-opacity=".9"/>
                        <path d="M300 1c19 1 28 11 49 4v33c-17 3-30-7-49-2V1Z" fill="#7ac0ff" stroke="none" opacity=".65"/>
                        <path d="M64 205h471v258H64z" fill="url(#campusFacade)"/>
                        <path d="M25 235h53v229H25zM520 235h58v229h-58z" fill="#134d99" opacity=".6"/>
                        <rect x="114" y="223" width="369" height="26" fill="#0c498f" opacity=".7"/>
                        <path d="M110 274h377" stroke-opacity=".22"/>
                        <path d="M120 272h83v153h-83zM220 272h83v153h-83zM320 272h83v153h-83zM419 272h58v153h-58z" fill="#184a91" opacity=".6"/>
                        <path d="M144 412V305a17 17 0 0 1 34 0v107M244 412V305a17 17 0 0 1 34 0v107M343 412V305a17 17 0 0 1 34 0v107M431 412V305a16 16 0 0 1 32 0v107" stroke="#9bcfff" stroke-opacity=".47" stroke-width="5"/>
                        <path d="M240 463v-79a58 58 0 0 1 116 0v79" fill="#072f76" opacity=".7"/>
                        <path d="M297 327v137M244 381h110" stroke-opacity=".33"/>
                        <rect x="259" y="169" width="81" height="25" rx="7" fill="#163e7a" stroke-opacity=".5"/>
                        <text x="300" y="187" font-family="Georgia,serif" text-anchor="middle" font-size="18" font-weight="bold" fill="#b7d9ff" stroke="none" opacity=".85">CNI</text>
                        <path d="M29 464h547M49 480h502" stroke-opacity=".35"/>
                    </g>
                    <!-- Libros sobre la izquierda -->
                    <g transform="translate(18 468)" stroke-linejoin="round">
                        <path d="M3 39h158l-12 31H2L3 39Z" fill="url(#campusBook)" stroke="#4ea5ed" stroke-width="3"/>
                        <path d="M13 42h132l-7 22H11l2-22Z" fill="#e5eefc" stroke="#9fc3ed" stroke-width="2"/>
                        <path d="M5 74h159l-11 27H0L5 74Z" fill="#154b93" stroke="#447fca" stroke-width="3"/>
                        <path d="M13 77h137l-6 19H10l3-19Z" fill="#f4f7ff"/>
                        <path d="M13 38 28 4l123 20-8 29" fill="#0f4389" stroke="#4c9be4" stroke-width="3"/>
                        <path d="m30 10 115 18-4 15-114-17" fill="#dce9fc"/>
                        <path d="M141 24h8v28" stroke="#efbe50" stroke-width="4"/>
                    </g>
                    <!-- Birrete académico a la derecha -->
                    <g transform="translate(763 446)" stroke-linejoin="round">
                        <ellipse cx="101" cy="91" rx="112" ry="21" fill="#071e4b" opacity=".5"/>
                        <path d="M20 47 104 14l83 33-83 33L20 47Z" fill="#072c68" stroke="#619ee6" stroke-width="3"/>
                        <path d="M60 66v22c30 18 59 16 88 0V66" fill="#144e9a" stroke="#619ee6" stroke-width="3"/>
                        <path d="M103 50c22 1 47 2 76-3l1 47" stroke="#efbe50" stroke-width="4"/>
                        <path d="M173 93h14l4 23h-22l4-23Z" fill="#ffc956"/>
                    </g>
                </svg>
            </div>

            <div class="login-mascot-scene" aria-hidden="true">
                <img src="{{ asset('images/Fondo_As.png') }}" alt="" class="login-mascot-seat">
                <img src="{{ asset('images/Fondo_Resplandor.png') }}" alt="" class="login-mascot-glow">
                <img src="{{ asset('images/gatito-cni.png') }}" alt="" class="login-mascot">
            </div>
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
