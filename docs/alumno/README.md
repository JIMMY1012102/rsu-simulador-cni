# Panel del alumno — entrega del frontend

Rama: `feature/panel-alumno`.

## Abrir el módulo

Con Laravel funcionando (`php artisan serve`), abrir **http://127.0.0.1:8000/alumno/perfil** e iniciar sesión con una cuenta de estudiante activa. Al solicitar primero esa URL, el login existente devuelve al perfil después de autenticar. El login directo conserva su destino original `/panel`.

La ruta propia `alumno.perfil` usa los middleware `web`, `auth:web` y `EnsureActiveUser`. El componente verifica además el rol estudiante en cada renderizado Livewire. El cierre de sesión utiliza el POST y CSRF existentes.

## Archivos

- `app/Livewire/Alumno/PanelAlumno.php`: datos y autorización del módulo.
- `resources/views/livewire/alumno/`: vista, layout e iconos SVG propios.
- `public/css/alumno/panel.css`: estilos aislados por prefijo; no requiere npm ni Vite.
- `routes/alumno.php` y `app/Providers/Alumno/AlumnoServiceProvider.php`: ruta independiente.
- `bootstrap/providers.php`: única línea de registro del proveedor del alumno; no sustituye proveedores existentes.
- `tests/Feature/PanelAlumnoTest.php`: diez pruebas del módulo.

Los cuatro archivos protegidos no se modificaron: `routes/web.php`, `app/Livewire/Panel.php`, `resources/views/livewire/panel.blade.php` y `resources/views/layouts/app.blade.php`. `/panel` conserva su componente y comportamiento.

## Datos y dependencias del equipo

| Elemento | Estado |
| --- | --- |
| Nombres, apellidos y cuenta activa | Datos reales de la sesión web |
| Progreso | Primer paso: cuenta creada. Segundo: existe un intento propio de tipo simulacro; no cuentan prácticas ni intentos ajenos |
| Tarjeta «Simulador» | `SimulacroService::listar()` (solo activos para el estudiante) filtrado por ventana de fechas: «Disponible ahora» o «Sin simulacros abiertos». No valida la carrera; eso lo hace `IntentoService::iniciar()` al empezar |
| Correo, teléfono y cursos inscritos | El esquema actual no los contiene; se indica «No disponible» |
| Editar datos, cambiar contraseña e iniciar simulador | Controles del diseño deshabilitados hasta disponer de las pantallas correspondientes |
| Oferta formativa, ayuda, recursos y enlaces legales | Presentación según referencia; sin destinos inventados |
| Escudo, fotografía institucional, avatar e iconos | Originales descargados del marco Figma 32:618 y servidos desde public/images/alumno/figma |

No se agregaron pantallas, endpoints de escritura, migraciones, paquetes ni datos a la base de trabajo. Se incorporaron los recursos originales del marco «CNI · Bienvenida y Mi perfil» (32:618), con colores, tamaños y posiciones adaptados a Blade/CSS. Cualquier estado o formulario adicional requerirá su referencia de diseño. Las referencias móviles no se proporcionaron: la adaptación conserva el contenido y apila los bloques.

## Validación realizada

- Suite completa: **69 pruebas aprobadas, 505 aserciones**.
- Vistas Blade compiladas con `php artisan view:cache`.
- Formato PHP aplicado solo a los archivos del módulo con Pint.
- Ruta real: login requerido, acceso permitido a estudiante activo, rechazo de inactivos y docentes, regreso desde login y cierre de sesión verificados por pruebas HTTP.
- Aislamiento de intentos y escape HTML verificados.
- Revisión en navegador de `/alumno/perfil` con MariaDB local y la cuenta `estudiante_demo`: datos reales, sin errores de consola, fuentes e imágenes cargadas.
- Anchos de 320, 390, 768 y 1024 px sin desbordamiento horizontal. Comparación visual con el marco de Figma 32:618.
- Menú lateral: siempre visible en escritorio; plegable en móvil (≤ 720 px).
- En navegador, `/alumno/perfil` sin sesión redirige a `/login` como corresponde.

## Entrega

Frontend revisado y accesible mediante ruta propia. Las funciones deshabilitadas descritas arriba son pendientes explícitos de integración, no funciones implementadas. Las imágenes y los iconos ya están integrados.

## Recursos de Figma incorporados

Fuente: [SIMULADOR-CNI — Mi perfil](https://www.figma.com/design/ulS1Jfn9xGBXz8ikbNxBZt/SIMULADOR-CNI?node-id=32-618).

Los archivos locales conservan los nombres descriptivos del contexto exportado. No se utilizan capturas de la pantalla como imágenes del frontend ni enlaces temporales de Figma en tiempo de ejecución. Los iconos SVG se sirven como archivos originales, conservando sus dimensiones intrínsecas; sus variantes de navegación, estadísticas y recursos se usan en los lugares correspondientes.

La fuente Inter (pesos 400–800) se sirve localmente desde `public/fonts/alumno/`, junto a su licencia OFL. Las imágenes de avatar son las ilustraciones del diseño, no fotografías de cuentas reales.

Los campos que no existen en el backend siguen indicando «No disponible».

## Revisión de base de datos y funciones

Consulta [REVISION.md](REVISION.md) para la auditoría del esquema y la integración API–panel.

