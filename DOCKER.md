# Docker - Simulador RSU-CNI

## Requisitos

- Docker Desktop o Docker Engine
- Docker Compose
- Git

## Servicios

| Servicio | Tecnologia |
| --- | --- |
| app | Laravel 12 + PHP 8.3 |
| web | Nginx |
| db | MariaDB 11.4 |

## Instalacion local

### 1. Clonar el repositorio

git clone -b feature/dockerizacion https://github.com/JIMMY1012102/rsu-simulador-cni.git

cd rsu-simulador-cni

### 2. Configurar variables

Copiar .env.docker.example como .env.

En PowerShell:

Copy-Item .env.docker.example .env

Cambiar DB_PASSWORD y DB_ROOT_PASSWORD por contrasenas locales propias.

No subir .env a GitHub.

### 3. Construir los contenedores

docker compose build

### 4. Iniciar los servicios

docker compose up -d

### 5. Generar clave Laravel

docker compose exec app php artisan key:generate

### 6. Crear tablas

docker compose exec app php artisan migrate

### 7. Insertar datos de desarrollo

docker compose exec app php artisan db:seed

### 8. Crear enlace de almacenamiento

docker compose exec app php artisan storage:link

### 9. Abrir el simulador

http://localhost:8080

## Simulacros de demostracion

Solo para desarrollo local:

docker compose exec app php artisan db:seed --class=SimulacrosCincoAreasDemoSeeder

## Pruebas

docker compose exec app php artisan test

## Administracion

Ver contenedores:

docker compose ps

Ver registros:

docker compose logs --tail=50

Detener:

docker compose down

Iniciar nuevamente:

docker compose up -d

## Seguridad

- No subir .env ni credenciales reales.
- No publicar usuarios de demostracion en produccion.
- Configurar APP_DEBUG=false en produccion.
- No ejecutar migrate:fresh en bases con datos reales.
- No utilizar docker compose down -v si se desean conservar los datos.

## Notas

Los volumenes Docker conservan las dependencias y los datos de MariaDB.

La imagen utiliza Node.js para compilar los recursos de Vite durante la construccion.
