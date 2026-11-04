@extends('layouts.app')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/login-cni-v2.css') }}?v=9">
@endpush

@section('content')
    <div class="login-page login-cni-v2">
        <img src="{{ asset('images/Fondo_General.png') }}"
             alt="" class="login-background" aria-hidden="true">

        <svg class="login-backdrop-art" viewBox="0 0 1600 900" preserveAspectRatio="none"
             xmlns="http://www.w3.org/2000/svg" fill="none" aria-hidden="true">
            <defs>
                <linearGradient id="cniRibbonTop" x1="0" y1="0" x2="1" y2="1">
                    <stop stop-color="#08a4ff" stop-opacity=".58"/>
                    <stop offset=".5" stop-color="#0650c4" stop-opacity=".74"/>
                    <stop offset="1" stop-color="#062552" stop-opacity=".18"/>
                </linearGradient>
                <linearGradient id="cniRibbonBottom" x1="0" y1="0" x2="0" y2="1">
                    <stop stop-color="#0b6de0" stop-opacity=".86"/>
                    <stop offset="1" stop-color="#031439" stop-opacity=".25"/>
                </linearGradient>
            </defs>
            <path d="M0 0H392L208 153 0 264Z" fill="url(#cniRibbonTop)" opacity=".42"/>
            <path d="M0 0H258L0 180Z" fill="#096fe0" opacity=".38"/>
            <path d="M1600 0H1290L1600 230Z" fill="#073d90" opacity=".5"/>
            <path d="M1600 0H1488L1600 125Z" fill="#1397ff" opacity=".42"/>
            <path d="M0 810Q160 792 335 821T870 828Q1190 794 1600 826V900H0Z" fill="url(#cniRibbonBottom)" opacity=".62"/>
            <path d="M0 846Q210 820 415 851T1040 852Q1310 822 1600 854" stroke="#fbd064" stroke-width="3.5" stroke-opacity=".95"/>
            <path d="M0 18Q170-16 296 0" stroke="#ffd270" stroke-width="2" stroke-opacity=".8"/>
            <path d="M1120 0Q1290 184 1458 0" stroke="#ffdc75" stroke-width="3" stroke-opacity=".82"/>
            <path d="M1599 440Q1490 590 1600 755" stroke="#0b7dff" stroke-width="3" stroke-opacity=".4"/>
            <path d="M1500 900Q1540 834 1600 804" stroke="#ffc658" stroke-width="2.5" stroke-opacity=".78"/>
        </svg>

        <section class="login-presentation" aria-labelledby="login-presentation-title">
            <div class="login-presentation-copy">
                <h2 class="login-heading" id="login-presentation-title">
                    Tu próximo paso
                    <span>empieza <em>aquí.</em></span>
                </h2>
            </div>

            <div class="login-academic-icons" aria-hidden="true">
                <svg class="login-academic-book" viewBox="0 0 136 94" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M68 25C48 10 27 11 6 22v50c24-12 46-10 62 4V25Zm0 0c17-15 40-17 62-4v51c-23-11-45-9-62 4V25Z"
                          stroke="#9bcbff" stroke-width="3" stroke-linejoin="round"/>
                    <path d="M15 31c18-7 34-4 44 2M77 32c17-11 30-12 44-5M15 44c18-7 33-5 44 2M77 45c18-10 31-9 44-4"
                          stroke="#9bcbff" stroke-width="2" stroke-opacity=".48"/>
                </svg>
                <svg class="login-academic-laurel" viewBox="0 0 150 136" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <g stroke="#9fc9ff" stroke-opacity=".58" stroke-width="2.5">
                        <path d="M57 18 75 8 96 18 75 29 57 18Zm6 7v15c8 8 19 8 25 0V25M96 18v24"/>
                        <path d="M75 8v-4"/>
                        <path d="M30 20c-33 35-13 82 32 99M120 20c33 35 13 82-32 99"/>
                        <path d="m31 24-15-4 9 19M19 51 6 48l9 18M19 80l-13 3 16 14M34 105l-7 13 19-5"/>
                        <path d="m119 24 15-4-9 19M131 51l13-3-9 18M131 80l13 3-16 14M116 105l7 13-19-5"/>
                    </g>
                </svg>
            </div>
            <div class="login-illustration" aria-hidden="true">
                <svg class="login-campus" viewBox="0 0 1000 540" fill="none"
                     xmlns="http://www.w3.org/2000/svg" focusable="false">
                    <defs>
                        <linearGradient id="cniFacade" x1="0" y1="0" x2="0" y2="1">
                            <stop stop-color="#75c1ff" stop-opacity=".84"/>
                            <stop offset=".43" stop-color="#1879cf" stop-opacity=".65"/>
                            <stop offset="1" stop-color="#093c86" stop-opacity=".9"/>
                        </linearGradient>
                        <linearGradient id="cniGlass" x1="0" y1="0" x2="0" y2="1">
                            <stop stop-color="#79c3ff" stop-opacity=".8"/>
                            <stop offset="1" stop-color="#0a3e8b" stop-opacity=".78"/>
                        </linearGradient>
                        <linearGradient id="cniBookBlue" x1="0" y1="0" x2="1" y2="1">
                            <stop stop-color="#1b84e7"/>
                            <stop offset="1" stop-color="#022b70"/>
                        </linearGradient>
                        <radialGradient id="cniLight">
                            <stop stop-color="#4db9ff" stop-opacity=".65"/>
                            <stop offset="1" stop-color="#0054bc" stop-opacity="0"/>
                        </radialGradient>
                    </defs>
                    <ellipse cx="520" cy="480" rx="485" ry="63" fill="url(#cniLight)"/>
                    <g class="login-campus-school" stroke="#76c2ff" stroke-width="2.2" stroke-linejoin="round">
                        <path d="M244 231 540 110 855 231v17H244Z" fill="#2179ce" fill-opacity=".82"/>
                        <path d="m525 128 15-32 16 32" fill="#4791d5"/>
                        <path d="M541 98V43" stroke="#b7e4ff" stroke-width="3"/>
                        <path d="M540 43c29-6 44 14 62 7v42c-19 5-37-8-62-6Z" fill="#4699e4" fill-opacity=".8" stroke="none"/>
                        <rect x="270" y="248" width="540" height="214" fill="url(#cniFacade)"/>
                        <rect x="276" y="260" width="528" height="23" fill="#143d7d" fill-opacity=".72" stroke="none"/>
                        <rect x="291" y="295" width="100" height="145" rx="47" fill="url(#cniGlass)"/>
                        <rect x="410" y="295" width="100" height="145" rx="47" fill="url(#cniGlass)"/>
                        <rect x="570" y="295" width="100" height="145" rx="47" fill="url(#cniGlass)"/>
                        <rect x="690" y="295" width="100" height="145" rx="47" fill="url(#cniGlass)"/>
                        <path d="M341 295v144M460 295v144M620 295v144M740 295v144" stroke-opacity=".48"/>
                        <path d="M287 367h515M271 460h539" stroke-opacity=".48"/>
                        <rect x="480" y="194" width="122" height="42" rx="4" fill="#14447f" stroke-opacity=".45"/>
                        <text x="541" y="223" font-family="Georgia,serif" text-anchor="middle" font-size="30" font-weight="bold" fill="#d2eeff" stroke="none">CNI</text>
                    </g>
                    <g class="login-campus-books" stroke-linejoin="round">
                        <ellipse cx="108" cy="515" rx="120" ry="12" fill="#001332" opacity=".55"/>
                        <path d="M12 425h200l-12 43H0l12-43Z" fill="url(#cniBookBlue)" stroke="#5eaff9" stroke-width="3"/>
                        <path d="M25 429h167l-6 32H18l7-32Z" fill="#eff5ff"/>
                        <path d="M5 474h204l-11 38H-7l12-38Z" fill="#063775" stroke="#6cb9ff" stroke-width="3"/>
                        <path d="M16 478h183l-7 27H9l7-27Z" fill="#e6f1ff"/>
                        <path d="M35 429 58 385l143 32-10 17" fill="#1050a4" stroke="#72beff" stroke-width="3"/>
                        <path d="m59 392 130 30-3 6-129-29" fill="#ebf6ff"/>
                        <path d="M26 425v36" stroke="#ffc65b" stroke-width="5"/>
                    </g>
                    <g class="login-campus-cap" stroke-linejoin="round">
                        <ellipse cx="876" cy="515" rx="117" ry="15" fill="#001431" opacity=".54"/>
                        <path d="m751 424 124-51 122 51-122 48-124-48Z" fill="#0b3d89" stroke="#92caff" stroke-width="3"/>
                        <path d="M817 450v35c35 24 80 25 116 0v-35" fill="#1351a5" stroke="#85bfff" stroke-width="3"/>
                        <path d="M876 421h91v66" stroke="#ffd56f" stroke-width="4"/>
                        <path d="m959 485 14 1 6 26-27-1Z" fill="#ffd66a"/>
                    </g>
                </svg>
                <div class="login-gold-stage"></div>
                <img src="{{ asset('images/gatito-cni.png') }}"
                     alt="" class="login-mascot">
            </div>
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
