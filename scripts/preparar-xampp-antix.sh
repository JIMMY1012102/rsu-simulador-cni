#!/usr/bin/env bash
set -Eeuo pipefail
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
XAMPP_DIR="${XAMPP_DIR:-/opt/lampp}"
HTTPD_CONF="$XAMPP_DIR/etc/httpd.conf"
ALIAS_CONF="$XAMPP_DIR/etc/extra/httpd-rsu-cni.conf"
URL="http://localhost/rsu-cni"
SQL_ARG="${1:-}"
fail(){ echo "ERROR: $*" >&2; exit 1; }
info(){ echo; echo "==> $*"; }
[[ -x "$XAMPP_DIR/lampp" && -f "$HTTPD_CONF" ]] || fail "No encuentro XAMPP en $XAMPP_DIR."
[[ -f "$PROJECT_DIR/artisan" ]] || fail "No parece un proyecto Laravel."
[[ -f "$PROJECT_DIR/.env" ]] || cp "$PROJECT_DIR/.env.example" "$PROJECT_DIR/.env"
export PATH="$XAMPP_DIR/bin:$PATH"
for c in php composer npm mysql; do command -v "$c" >/dev/null || fail "Falta $c."; done
php -r 'exit(version_compare(PHP_VERSION,"8.2.0",">=")?0:1);' || fail "Se requiere PHP 8.2+."
for e in mbstring openssl pdo_mysql tokenizer xml ctype json fileinfo curl; do php -m | grep -qi "^$e$" || fail "Falta extensión PHP: $e"; done
cd "$PROJECT_DIR"
info "Instalando dependencias PHP"
composer install --no-interaction
if ! grep -Eq '^APP_KEY=base64:.+' .env; then php artisan key:generate --force; fi
info "Compilando frontend"
npm install
npm run build
readarray -t DBVARS < <(php -r 'require "vendor/autoload.php"; Dotenv\Dotenv::createImmutable(getcwd())->safeLoad(); foreach(["DB_HOST","DB_PORT","DB_DATABASE","DB_USERNAME","DB_PASSWORD"] as $k) echo $k."=".base64_encode($_ENV[$k]??"")."\n";')
declare -A DB
for item in "${DBVARS[@]}"; do k="${item%%=*}"; DB["$k"]="$(printf %s "${item#*=}"|base64 -d)"; done
DB_HOST="${DB[DB_HOST]:-127.0.0.1}"; DB_PORT="${DB[DB_PORT]:-3306}"
DB_NAME="${DB[DB_DATABASE]:-rsu_cni_local}"; DB_USER="${DB[DB_USERNAME]:-root}"; DB_PASS="${DB[DB_PASSWORD]:-}"
[[ "$DB_NAME" =~ ^[A-Za-z0-9_]+$ ]] || fail "Nombre DB inválido."
info "Iniciando MariaDB de XAMPP"
"$XAMPP_DIR/lampp" startmysql || true
CNF="$(mktemp)"; chmod 600 "$CNF"
trap 'rm -f "$CNF"' EXIT
{ printf '[client]\nhost=%s\nport=%s\nuser=%s\n' "$DB_HOST" "$DB_PORT" "$DB_USER"; [[ -z "$DB_PASS" ]] || printf 'password=%s\n' "$DB_PASS"; } > "$CNF"
MYSQL=(mysql --defaults-extra-file="$CNF")
"${MYSQL[@]}" -e "CREATE DATABASE IF NOT EXISTS \`$DB_NAME\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
SQL_FILE=""
if [[ -n "$SQL_ARG" ]]; then SQL_FILE="$(realpath "$SQL_ARG" 2>/dev/null || true)"; [[ -f "$SQL_FILE" ]] || fail "No encuentro $SQL_ARG"; else
  found=()
  for f in "$PROJECT_DIR"/respaldo-oracle.sql "$PROJECT_DIR"/respaldo-oracle.sql.gz "$PROJECT_DIR"/database/backups/*.sql "$PROJECT_DIR"/database/backups/*.sql.gz "$HOME"/Descargas/*.sql "$HOME"/Descargas/*.sql.gz "$HOME"/Downloads/*.sql "$HOME"/Downloads/*.sql.gz; do [[ -f "$f" ]] && found+=("$f"); done
  if (( ${#found[@]} == 1 )); then SQL_FILE="${found[0]}"; elif (( ${#found[@]} > 1 )); then printf 'Hay varios respaldos; indica uno como argumento:\n'; printf ' %s\n' "${found[@]}"; exit 2; fi
fi
if [[ -n "$SQL_FILE" ]]; then
  info "Verificando que la base destino esté vacía"
  N="$("${MYSQL[@]}" -Nse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME';")"
  [[ "$N" == 0 ]] || fail "La base $DB_NAME ya contiene tablas. No se tocó. Usa una base nueva/vacía y cambia DB_DATABASE en .env."
  info "Importando respaldo Oracle: $SQL_FILE"
  if [[ "$SQL_FILE" == *.gz ]]; then gzip -dc "$SQL_FILE" | sed -E '/^[[:space:]]*(CREATE DATABASE|USE )[[:space:]]/Id' | "${MYSQL[@]}" "$DB_NAME"
  else sed -E '/^[[:space:]]*(CREATE DATABASE|USE )[[:space:]]/Id' "$SQL_FILE" | "${MYSQL[@]}" "$DB_NAME"; fi
  info "Aplicando migraciones pendientes"
  php artisan migrate --force
else
  info "No se encontró SQL. No se importó nada."
  echo "Copia el respaldo a database/backups/ o ejecuta:"
  echo "bash scripts/preparar-xampp-antix.sh /ruta/al/respaldo-oracle.sql"
fi
if [[ ! -e public/storage ]]; then php artisan storage:link || true; fi
info "Configurando Apache"
ALIAS_CONTENT="Alias /rsu-cni \"$PROJECT_DIR/public\"

<Directory \"$PROJECT_DIR/public\">
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
"
if [[ $EUID -eq 0 ]]; then SUDO=(); else SUDO=(sudo); fi
printf '%s\n' "$ALIAS_CONTENT" | "${SUDO[@]}" tee "$ALIAS_CONF" >/dev/null
INCLUDE="Include etc/extra/httpd-rsu-cni.conf"
if ! grep -Fq "$INCLUDE" "$HTTPD_CONF"; then "${SUDO[@]}" cp "$HTTPD_CONF" "$HTTPD_CONF.backup-rsu-cni"; printf '\n# RSU-CNI local\n%s\n' "$INCLUDE" | "${SUDO[@]}" tee -a "$HTTPD_CONF" >/dev/null; fi
grep -Eq '^[[:space:]]*LoadModule[[:space:]]+rewrite_module' "$HTTPD_CONF" || fail "Activa mod_rewrite en httpd.conf."
"$XAMPP_DIR/bin/httpd" -t -f "$HTTPD_CONF" || fail "Configuración Apache inválida."
php artisan optimize:clear
"${SUDO[@]}" "$XAMPP_DIR/lampp" restartapache
echo
echo "INSTALACIÓN LISTA: $URL"
echo "Base configurada: $DB_NAME"
