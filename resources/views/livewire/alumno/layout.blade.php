<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Mi perfil · Portal del alumno CNI</title>
    <link rel="stylesheet" href="{{ asset('css/alumno/panel.css') }}">
    @livewireStyles
</head>
<body class="alumno-body">
    <a class="alumno-skip" href="#contenido-alumno">Saltar al contenido</a>
    {{ $slot }}
    @livewireScripts
    <script>
        // El menú solo se pliega en móvil; en escritorio permanece abierto.
        (() => {
            const menu = document.querySelector('.alumno-menu');
            if (!menu) return;
            const resumen = menu.querySelector('summary');
            const escritorio = matchMedia('(min-width: 721px)');
            const sincronizar = () => {
                if (escritorio.matches) menu.open = true;
                resumen.tabIndex = escritorio.matches ? -1 : 0;
            };
            resumen.addEventListener('click', (evento) => { if (escritorio.matches) evento.preventDefault(); });
            escritorio.addEventListener('change', sincronizar);
            sincronizar();
        })();
    </script>
</body>
</html>
