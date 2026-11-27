#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
XAMPP_DIR="${XAMPP_DIR:-/opt/lampp}"
HTTPD_CONF="$XAMPP_DIR/etc/httpd.conf"
ALIAS_CONF="$XAMPP_DIR/etc/extra/httpd-rsu-cni.conf"
ALIAS_PATH="/rsu-cni"
URL="http://localhost${ALIAS_PATH}"

fail() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }
info() { printf '\n==> %s\n' "$*"; }

[[ -x "$XAMPP_DIR/lampp" ]] || fail "No encuentro XAMPP en $XAMPP_DIR. Define XAMPP_DIR si está instalado en otra ruta."
[[ -f "$HTTPD_CONF" ]] || fail "No encuentro $HTTPD_CONF."
[[ -f "$PROJECT_DIR/artisan" ]] || fail "Ejecuta este script desde una copia válida del proyecto."

export PATH="$XAMPP_DIR/bin:$PATH"
command -v php >/dev/null 2>&1 || fail "No encuentro PHP CLI."
command -v composer >/dev/null 2>&1 || fail "No encuentro Composer 2."
command -v npm >/dev/null 2>&1 || fail "No encuentro npm."

PHP_VERSION="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
php -r 'exit(version_compare(PHP_VERSION, "8.2.0", ">=") ? 0 : 1);' || fail "Laravel 12 requiere PHP 8.2 o superior; PHP detectado: $PHP_VERSION."
for ext in mbstring openssl pdo_mysql tokenizer xml ctype json fileinfo curl; do
    php -m | grep -qi "^$ext$" || fail "Falta la extensión PHP: $ext. Habilítala en la configuración de PHP de XAMPP."
done

info "Instalando dependencias PHP"
cd "$PROJECT_DIR"
composer install

info "Preparando .env sin sobrescribir uno existente"
if [[ ! -f .env ]]; then
    cp .env.example .env
fi

info "Generando APP_KEY solo si todavía está vacía"
if ! grep -Eq '^APP_KEY=base64:.+' .env; then
    php artisan key:generate
fi

info "Instalando y compilando recursos del frontend"
npm install
npm run build

info "Creando enlace de almacenamiento si no existe"
if [[ ! -e public/storage ]]; then
    php artisan storage:link
fi

info "Configurando Apache para publicar únicamente public/"
ALIAS_CONTENT="Alias $ALIAS_PATH \"$PROJECT_DIR/public\"

<Directory \"$PROJECT_DIR/public\">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
"

if [[ $EUID -eq 0 ]]; then
    SUDO=()
else
    command -v sudo >/dev/null 2>&1 || fail "Se necesita sudo para configurar Apache."
    SUDO=(sudo)
fi

printf '%s\n' "$ALIAS_CONTENT" | "${SUDO[@]}" tee "$ALIAS_CONF" >/dev/null

INCLUDE_LINE="Include etc/extra/httpd-rsu-cni.conf"
if ! grep -Fq "$INCLUDE_LINE" "$HTTPD_CONF"; then
    "${SUDO[@]}" cp "$HTTPD_CONF" "$HTTPD_CONF.backup-rsu-cni"
    printf '\n# Alias local del proyecto RSU-CNI\n%s\n' "$INCLUDE_LINE" | "${SUDO[@]}" tee -a "$HTTPD_CONF" >/dev/null
fi

if ! grep -Eq '^[[:space:]]*LoadModule[[:space:]]+rewrite_module' "$HTTPD_CONF"; then
    fail "mod_rewrite no parece estar habilitado en $HTTPD_CONF. Habilita LoadModule rewrite_module y vuelve a ejecutar el script."
fi

info "Validando la configuración de Apache"
"$XAMPP_DIR/bin/httpd" -t -f "$HTTPD_CONF" || fail "Apache rechazó la configuración. Revisa $XAMPP_DIR/logs/error_log."

info "Limpiando cachés de Laravel"
php artisan optimize:clear

info "Reiniciando Apache"
"${SUDO[@]}" "$XAMPP_DIR/lampp" restartapache

cat <<EOF

Preparación terminada.
Proyecto: $PROJECT_DIR
URL: $URL

Siguiente paso:
1. Abre http://localhost/phpmyadmin
2. Crea una base de datos llamada rsu_cni_local.
3. Revisa .env (DB_DATABASE, DB_USERNAME y DB_PASSWORD).
4. Importa tu SQL de respaldo o, si la base está vacía y corresponde a este esquema, ejecuta: php artisan migrate
5. Abre $URL

El script no crea usuarios, no importa SQL y no ejecuta migraciones automáticamente.
EOF
