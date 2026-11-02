<div class="alumno-panel">
    <aside class="alumno-sidebar" aria-label="Portal del alumno">
        <div class="alumno-brand"><img class="alumno-seal" src="{{ asset('images/alumno/figma/EscudoInstitucionalCni.png') }}" alt="Escudo institucional CNI" width="106" height="92"><div><strong>CNI</strong><small>Portal del alumno</small></div></div>
        <details class="alumno-menu" open>
            <summary>Mi portal</summary>
            <nav aria-label="Navegación del alumno">
                <a href="{{ route('inicio') }}">@include('livewire.alumno.icon', ['name' => 'home']) <span>Inicio</span></a>
                <a href="#contenido-alumno" aria-current="page">@include('livewire.alumno.icon', ['name' => 'user']) <span>Mi perfil</span></a>
                @foreach (['cap' => 'Oferta formativa', 'book' => 'Recursos', 'help' => 'Ayuda y contacto'] as $icono => $seccion)
                    <button type="button" disabled title="Sección aún no disponible">@include('livewire.alumno.icon', ['name' => $icono]) <span>{{ $seccion }}</span></button>
                @endforeach
            </nav>
        </details>
        <div class="alumno-sidebar-bottom">
            <section class="alumno-support"><span class="alumno-support-icon">@include('livewire.alumno.icon', ['name' => 'chat'])</span><strong>Estamos para ayudarte</strong><p>Resuelve tus dudas sobre el portal o tus próximos pasos.</p><button disabled title="Contacto pendiente de integración">Contactar con CNI ↗</button></section>
            <div class="alumno-user"><span class="alumno-avatar"><img src="{{ asset('images/alumno/figma/AvatarIlustrativoDelAlumno.png') }}" alt="Avatar ilustrativo del alumno" width="34" height="34"></span><div><strong>{{ $usuario->nombres }}</strong><small>Alumno CNI</small></div></div>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="alumno-logout">@include('livewire.alumno.icon', ['name' => 'logout']) Cerrar sesión</button></form>
        </div>
    </aside>
    <div class="alumno-workspace">
        <header class="alumno-topbar"><span>Portal del alumno / Mi perfil</span><div><button disabled>@include('livewire.alumno.icon', ['name' => 'help-top']) Ayuda</button><strong>{{ $usuario->nombres }}</strong><span class="alumno-avatar"><img src="{{ asset('images/alumno/figma/AvatarIlustrativoDelAlumno1.png') }}" alt="Avatar ilustrativo del alumno" width="34" height="34"></span></div></header>
        <main id="contenido-alumno" class="alumno-content" tabindex="-1">
            <div class="alumno-heading"><div><h1>Mi perfil</h1><p>Tu información y el comienzo de tu experiencia en CNI.</p></div><button class="alumno-button alumno-outline" disabled title="Cambio de contraseña pendiente de integración">Cambiar contraseña @include('livewire.alumno.icon', ['name' => 'lock'])</button></div>
            <section class="alumno-hero" aria-labelledby="saludo-alumno">
                <img class="alumno-decor alumno-decor-one" src="{{ asset('images/alumno/figma/Ellipse.svg') }}" alt="" aria-hidden="true"><img class="alumno-decor alumno-decor-two" src="{{ asset('images/alumno/figma/Ellipse1.svg') }}" alt="" aria-hidden="true"><img class="alumno-decor alumno-decor-three" src="{{ asset('images/alumno/figma/Ellipse2.svg') }}" alt="" aria-hidden="true"><div class="alumno-hero-copy"><span class="alumno-status">Listo para empezar</span><h2 id="saludo-alumno">¡Hola, {{ $usuario->nombres }}! 👋</h2><p>Ya estás dentro de tu Portal del alumno. Aquí tienes tu información básica y el acceso rápido al simulador.</p><button class="alumno-button alumno-white" disabled title="La pantalla del simulador todavía no está integrada">@include('livewire.alumno.icon', ['name' => 'cap-button']) Iniciar simulador</button></div>
                
                <div class="alumno-hero-image"><img src="{{ asset('images/alumno/figma/Imagen1.png') }}" alt="Estudiantes y representantes del CNI en una ceremonia institucional" width="452" height="260"></div>
            </section>
            <section class="alumno-stats" aria-label="Resumen de tu cuenta">
                <article class="alumno-card"><span class="alumno-icon" aria-hidden="true">@include('livewire.alumno.icon', ['name' => 'check-stat'])</span><strong>{{ $pasosCompletados }} / 2</strong><p>Pasos completados</p></article>
                <article class="alumno-card"><span class="alumno-icon alumno-amber" aria-hidden="true">@include('livewire.alumno.icon', ['name' => 'cap-stat'])</span><strong aria-label="Dato no disponible">—</strong><p>Cursos inscritos · No disponible</p></article>
                <article class="alumno-card"><span class="alumno-icon alumno-green" aria-hidden="true">@include('livewire.alumno.icon', ['name' => 'user-stat'])</span><strong>{{ $usuario->activo ? 'Activa' : 'Inactiva' }}</strong><p>Estado de cuenta</p></article>
                <article class="alumno-card alumno-simulator"><span class="alumno-icon" aria-hidden="true">@include('livewire.alumno.icon', ['name' => 'simulator-stat'])</span><strong>Simulador</strong><p>{{ $simulacrosAbiertos > 0 ? 'Disponible ahora' : 'Sin simulacros abiertos' }}</p></article>
            </section>
            <section class="alumno-card alumno-personal" aria-labelledby="datos-alumno"><div class="alumno-section-heading"><h2 id="datos-alumno"><span class="alumno-icon" aria-hidden="true">@include('livewire.alumno.icon', ['name' => 'user-personal'])</span> Información personal</h2><button class="alumno-pill" disabled title="Edición de perfil pendiente de integración">@include('livewire.alumno.icon', ['name' => 'edit']) Editar datos</button></div><dl><div><dt>Nombre</dt><dd>{{ $usuario->nombres }}</dd></div><div><dt>Apellidos</dt><dd>{{ $usuario->apellidos }}</dd></div><div><dt>Correo electrónico</dt><dd class="alumno-muted">No disponible</dd></div><div><dt>Teléfono (opcional)</dt><dd class="alumno-muted">No disponible <button class="alumno-pill" disabled>+ Añadir</button></dd></div></dl></section>
            <section class="alumno-onboarding" aria-labelledby="pasos-alumno"><div class="alumno-section-heading"><h2 id="pasos-alumno">Comienza por aquí</h2><span class="alumno-pill">{{ $pasosCompletados }} de 2 pasos completados</span></div><div class="alumno-steps"><article class="alumno-card alumno-complete"><div class="alumno-section-heading"><h3><span class="alumno-icon alumno-green" aria-hidden="true">@include('livewire.alumno.icon', ['name' => 'check'])</span> Crea tu cuenta</h3><span class="alumno-badge">✓ Listo</span></div><p>Ya tienes tu espacio personal en el Portal del alumno.</p><strong class="alumno-success">● Completado</strong></article><article class="alumno-card"><div class="alumno-section-heading"><h3><span class="alumno-icon" aria-hidden="true">2</span> Iniciar simulador</h3><span class="alumno-pill">{{ $simuladorIniciado ? 'Listo' : 'Pendiente' }}</span></div><p>Practica con simulacros cronometrados de admisión y recibe tu puntaje y los temas que debes reforzar.</p><button class="alumno-button alumno-primary" disabled>Iniciar el simulador</button></article></div></section>
            <div class="alumno-legal"><span>CNI · Portal del alumno</span><div><button disabled>Privacidad</button><button disabled>Términos y condiciones</button></div></div>
        </main>
        <footer class="alumno-resources"><div class="alumno-section-heading"><h2>Recursos para guiarte</h2><span>Encuentra todo lo que necesitas</span></div><div class="alumno-resource-grid">@foreach (['Guía del portal' => 'Conoce las secciones y cómo utilizar tu cuenta.', 'Preguntas frecuentes' => 'Respuestas sobre el registro y la inscripción.', 'Ayuda y contacto' => 'Habla con CNI si necesitas orientación.'] as $titulo => $descripcion)<article><span class="alumno-icon" aria-hidden="true">@include('livewire.alumno.icon', ['name' => ['book-resource', 'help-resource', 'chat-resource'][$loop->index]])</span><span class="alumno-resource-arrow">@include('livewire.alumno.icon', ['name' => 'arrow'])</span><h3>{{ $titulo }}</h3><p>{{ $descripcion }}</p></article>@endforeach</div></footer>
    </div>
</div>


