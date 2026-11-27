# Ejecución local del asistente CNI (Ruta B: Python)

Esta aplicación conserva Laravel como puente autenticado. La ruta de integración es:

`Navegador → Laravel (sesión/CSRF) → FastAPI → Gemini + cliente MCP → servidor MCP → MariaDB/MySQL`

El navegador nunca llama directamente a Gemini, al servidor MCP ni a la base de datos. Laravel obtiene el rol y el ID del usuario autenticado y los envía al agente; el modelo no puede elegir la identidad que consulta.

## 1. Requisitos

- Proyecto clonado en la laptop.
- XAMPP con MariaDB/MySQL.
- PHP 8.2 o superior y Composer 2.
- Python 3.10 o superior.
- Una clave de Gemini AI Studio.
- Git.

En esta rama se usa la Ruta B del instructivo: `agente/agente.py` (FastAPI) y `agente/servidor_mcp.py` (herramientas MCP).

## 2. Preparar la aplicación y la base de datos

1. Inicia MySQL desde XAMPP. Apache puede utilizarse con un VirtualHost cuyo DocumentRoot apunte a la carpeta `public`; para la primera prueba local también puedes iniciar Laravel con `php artisan serve`.
2. En la raíz del proyecto instala dependencias PHP:

   ```bash
   composer install
   ```

3. Copia `.env.example` a `.env`, configura la conexión a la base local y genera la clave de Laravel:

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Usa una copia local de la base de datos de desarrollo. No conectes el agente a la base de producción ni uses datos personales reales.
5. Si creas la base desde cero, revisa las migraciones y sigue el procedimiento de instalación del README principal. Si importas `base-de-datos.sql`, no ejecutes `migrate:fresh` ni vuelvas a importar el SQL sobre una base con datos. La aplicación requiere el esquema actualizado, incluida la columna `usuarios.area_postulacion_id` y las tablas de resultados.
6. Crea un usuario MariaDB de solo lectura. Abre `agente/crear_usuario_solo_lectura.sql`, reemplaza el nombre de base `simulador_cni` por el de tu base local y ejecuta el archivo como administrador de MySQL. El usuario `agente` solo tendrá permisos SELECT sobre las tablas que usan las herramientas; en `usuarios` solo se conceden las columnas necesarias, no `password`.

## 3. Configurar el agente

Desde la raíz del repositorio:

```bash
cd agente
python3 -m venv .venv
```

Activa el entorno virtual:

**Linux / macOS**
```bash
source .venv/bin/activate
```

**Windows PowerShell**
```powershell
.venv\\Scripts\\Activate.ps1
```

Instala las dependencias fijadas por el proyecto:

```bash
python -m pip install --upgrade pip
python -m pip install -r requirements.txt
```

Copia y completa el archivo de entorno:

```bash
cp .env.example .env
```

En Windows puedes copiarlo desde el Explorador o usar `Copy-Item .env.example .env` en PowerShell.

Completa `agente/.env`:

```dotenv
DB_HOST=localhost
DB_PORT=3306
DB_USER=agente
DB_PASSWORD=TU_CLAVE_LOCAL
DB_NAME=simulador_cni
GEMINI_API_KEY=TU_CLAVE_DE_GEMINI
MODELO=EL_MODELO_FLASH_HABILITADO_EN_TU_AI_STUDIO
AGENTE_TOKEN=UN_SECRETO_LARGO_Y_ALEATORIO
```

- `DB_NAME` debe coincidir con el nombre de la base local.
- `AGENTE_TOKEN` es un secreto compartido entre Laravel y FastAPI.
- No copies la clave de Gemini ni el token al código, al chat o al repositorio.
- El servidor MCP utiliza el mismo intérprete Python del entorno virtual que ejecuta FastAPI. No necesitas configurar `PYTHON_EXECUTABLE` normalmente.

## 4. Configurar Laravel

En el `.env` de la raíz del proyecto agrega o ajusta:

```dotenv
ASISTENTE_ENABLED=true
ASISTENTE_URL=http://127.0.0.1:8001/chat
ASISTENTE_TOKEN=EL_MISMO_SECRETO_DE_AGENTE_TOKEN
```

El valor de `ASISTENTE_TOKEN` debe ser exactamente igual a `AGENTE_TOKEN` en `agente/.env`.

Después, desde la raíz:

```bash
php artisan config:clear
```

No publiques ninguno de los dos archivos `.env`; ya están excluidos por Git.

## 5. Encender los servicios

Mantén abiertas dos terminales.

**Terminal 1 — agente Python**

Desde la carpeta `agente`, con el entorno virtual activado:

```bash
python -m uvicorn agente:app --host 127.0.0.1 --port 8001
```

No agregues `--reload`: el agente inicia el servidor MCP como subproceso y el modo reload puede interferir con ese proceso en Windows.

Comprueba que FastAPI está disponible en:

- Documentación: http://127.0.0.1:8001/docs
- Endpoint: http://127.0.0.1:8001/chat

**Terminal 2 — Laravel**

Desde la raíz del proyecto:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Abre http://127.0.0.1:8000/login e inicia sesión con una cuenta local de demostración con rol `estudiante` o `admin`.

Si se utiliza Apache de XAMPP en lugar de `artisan serve`, configura el VirtualHost para que el DocumentRoot sea la carpeta `public` del proyecto y conserva la misma configuración de Laravel.

## 6. Comprobar las herramientas MCP

El servidor MCP se inicia automáticamente cuando el agente recibe una consulta. El agente registra herramientas diferentes según el rol:

- Estudiante: resultados propios, rendimiento por asignatura, progreso por temas, simulacros de su área y banco académico.
- Admin: resumen general, estado del banco, simulacros recientes, rendimiento por sección y preguntas más falladas.

Todas las herramientas son de solo lectura. Las consultas usan parámetros enlazados, columnas explícitas y límites de filas. La herramienta de banco no devuelve solución ni clave correcta salvo que se solicite una explicación.

Prueba desde el chat integrado preguntas cuyas respuestas puedas verificar en phpMyAdmin. Por ejemplo:

- «¿Cómo voy en mis resultados?»
- «¿En qué asignatura tengo menor rendimiento?»
- «¿Qué temas debo reforzar?»

La respuesta debe corresponder a los registros del usuario autenticado. Prueba también una pregunta fuera del alcance y una pregunta de seguimiento.

## 7. Pruebas automatizadas y entrega

Desde la raíz del proyecto:

```bash
php artisan test --filter=AsistenteChatTest
```

Luego ejecuta la suite completa según el README principal. Completa `docs/asistente-ia/casos-de-prueba.md` con respuesta esperada, respuesta obtenida y resultado de cada caso. La respuesta esperada se consulta directamente en MariaDB/phpMyAdmin, nunca preguntándole al agente.

Antes de entregar:

- [ ] El chat aparece para los roles autorizados incluso si el agente está apagado; en ese caso informa claramente que está desactivado.
- [ ] Hay al menos dos herramientas MCP funcionales y se puede mostrar cuál se utilizó.
- [ ] Tres preguntas distintas se responden con datos reales.
- [ ] Una pregunta de seguimiento conserva el contexto de esa sesión.
- [ ] Las consultas no cruzan datos entre estudiantes.
- [ ] Los 10 casos de prueba están registrados.
- [ ] Solo se usan datos simulados.
- [ ] No hay claves en GitHub.
- [ ] La demostración de tres minutos está ensayada.

## Solución rápida de errores

- **No conecta a MySQL:** verifica que XAMPP tenga MySQL encendido, que `DB_PORT` sea correcto y que el usuario `agente` tenga SELECT en todas las tablas consultadas.
- **401 en /chat:** compara `AGENTE_TOKEN` con `ASISTENTE_TOKEN`; deben coincidir.
- **503 desde Laravel:** confirma que FastAPI esté encendido en el puerto 8001 y que `ASISTENTE_URL` sea correcto.
- **No encuentra el módulo MCP:** activa el entorno virtual antes de iniciar Uvicorn e instala `requirements.txt`.
- **Gemini devuelve 404 o modelo no encontrado:** configura `MODELO` con el identificador Flash disponible en tu AI Studio.
- **Gemini devuelve 429:** espera a que se restablezca la cuota o utiliza el modelo de respaldo configurado; no crees claves adicionales en el mismo proyecto para intentar evadir el límite.
