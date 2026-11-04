{{--
    "Mi perfil": datos del alumno, edición de nombre y apellidos, y cambio de contraseña.
    Datos: App\Http\Controllers\Alumno\PerfilController.
--}}
@extends('layouts.alumno-derecho', ['variante' => 'derecho', 'title' => 'Mi perfil'])

@section('content')
<div class="pagina">
    <header class="pagina-encabezado">
        <h1>Mi perfil</h1>
        <p>Revisa tus datos. Puedes cambiar tu nombre, tus apellidos y tu contraseña.</p>
    </header>

    <div class="perfil">
        <section class="inicio-tarjeta" aria-labelledby="titulo-datos">
            <h2 id="titulo-datos">Información personal</h2>

            <dl class="perfil-datos">
                <div><dt>Usuario de acceso</dt><dd>{{ $usuario->usuario }}</dd></div>
                <div><dt>Carrera</dt><dd>{{ $usuario->carrera?->nombre ?? 'Sin asignar' }}</dd></div>
                <div><dt>Sección</dt><dd>{{ $usuario->seccion ? $usuario->seccion->grado.' '.$usuario->seccion->nombre : 'Sin asignar' }}</dd></div>
            </dl>

            <form class="formulario" method="POST" action="{{ route('alumno.perfil.datos') }}">
                @csrf
                @method('PUT')
                <div class="formulario__fila">
                    <label class="campo">
                        <span>Nombres</span>
                        <input type="text" name="nombres" value="{{ old('nombres', $usuario->nombres) }}" maxlength="100" required autocomplete="given-name">
                        @error('nombres', 'datos') <small class="campo__error">{{ $message }}</small> @enderror
                    </label>
                    <label class="campo">
                        <span>Apellidos</span>
                        <input type="text" name="apellidos" value="{{ old('apellidos', $usuario->apellidos) }}" maxlength="100" required autocomplete="family-name">
                        @error('apellidos', 'datos') <small class="campo__error">{{ $message }}</small> @enderror
                    </label>
                </div>
                <button type="submit" class="boton">Guardar datos</button>
            </form>
        </section>

        <section class="inicio-tarjeta" aria-labelledby="titulo-password">
            <h2 id="titulo-password">Cambiar contraseña</h2>
            <form class="formulario" method="POST" action="{{ route('alumno.perfil.password') }}">
                @csrf
                @method('PUT')
                <label class="campo">
                    <span>Contraseña actual</span>
                    <input type="password" name="password_actual" required autocomplete="current-password">
                    @error('password_actual', 'password') <small class="campo__error">{{ $message }}</small> @enderror
                </label>
                <label class="campo">
                    <span>Nueva contraseña</span>
                    <input type="password" name="password" required minlength="8" autocomplete="new-password">
                    <small class="campo__ayuda">Mínimo 8 caracteres, con letras y números.</small>
                    @error('password', 'password') <small class="campo__error">{{ $message }}</small> @enderror
                </label>
                <label class="campo">
                    <span>Repite la nueva contraseña</span>
                    <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
                </label>
                <button type="submit" class="boton"><x-alumno.icono nombre="candado" :tamano="16" /> Cambiar contraseña</button>
            </form>
        </section>
    </div>
</div>
@endsection
