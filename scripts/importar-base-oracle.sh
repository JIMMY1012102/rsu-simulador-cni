#!/usr/bin/env bash
set -Eeuo pipefail

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd -P)"
XAMPP_DIR="${XAMPP_DIR:-/opt/lampp}"
MYSQL_BIN="$XAMPP_DIR/bin/mysql"
DUMP_ARG="${1:-}"

fail() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }
info() { printf '\n==> %s\n' "$*"; }

[[ -x "$MYSQL_BIN" ]] || fail "No encuentro el cliente MySQL de XAMPP: $MYSQL_BIN"
[[ -f "$PROJECT_DIR/.env" ]] || fail "No existe .env. Ejecuta primero preparar-xampp-antix.sh."
[[ -f "$PROJECT_DIR/vendor/autoload.php" ]] || fail "Faltan dependencias PHP. Ejecuta primero preparar-xampp-antix.sh."

cd "$PROJECT_DIR"

# Lee las variables con phpdotenv; nunca ejecuta .env como código shell.
mapfile -t ENV_B64 < <(php -r '
require "vendor/autoload.php";
$e = Dotenv\Dotenv::createImmutable(getcwd())->safeLoad();
foreach (["DB_HOST","DB_PORT","DB_DATABASE","DB_USERNAME","DB_PASSWORD"] as $k) {
    echo base64_encode((string)($e[$k] ?? "")), PHP_EOL;
}')
[[ "${#ENV_B64[@]}" -eq 5 ]] || fail "No pude leer la configuración DB_* de .env."
decode() { printf '%s' "$1" | base64 -d; }
DB_HOST="$(decode "${ENV_B64[0]}")"
DB_PORT="$(decode "${ENV_B64[1]}")"
DB_DATABASE="$(decode "${ENV_B64[2]}")"
DB_USERNAME="$(decode "${ENV_B64[3]}")"
DB_PASSWORD="$(decode "${ENV_B64[4]}")"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-rsu_cni_local}"
DB_USERNAME="${DB_USERNAME:-root}"

[[ "$DB_DATABASE" =~ ^[A-Za-z0-9_]+$ ]] || fail "DB_DATABASE solo puede contener letras, números y guion bajo."
[[ "$DB_HOST" =~ ^[A-Za-z0-9._:-]+$ ]] || fail "DB_HOST contiene caracteres no admitidos."
[[ "$DB_PORT" =~ ^[0-9]+$ ]] || fail "DB_PORT no es válido."

MYSQL=( "$MYSQL_BIN" --protocol=tcp --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" )
mysql_run() {
    if [[ -n "$DB_PASSWORD" ]]; then
        MYSQL_PWD="$DB_PASSWORD" "${MYSQL[@]}" "$@"
    else
        "${MYSQL[@]}" "$@"
    fi
}

# Si no se proporciona una ruta, busca respaldos en database/backups/, backups/ y raíz.
if [[ -n "$DUMP_ARG" ]]; then
    [[ -f "$DUMP_ARG" ]] || fail "No existe el archivo indicado: $DUMP_ARG"
    DUMP="$(realpath "$DUMP_ARG")"
else
    CANDIDATES=()
    for dir in "$PROJECT_DIR/database/backups" "$PROJECT_DIR/backups" "$PROJECT_DIR"; do
        [[ -d "$dir" ]] || continue
        while IFS= read -r -d '' file; do
            [[ "$(basename "$file")" == "base-de-datos.sql" ]] && continue
            CANDIDATES+=("$file")
        done < <(find "$dir" -maxdepth 1 -type f \( -iname '*.sql' -o -iname '*.sql.gz' \) -print0)
    done
    # Quita duplicados por si las carpetas coinciden.
    UNIQUE=()
    for file in "${CANDIDATES[@]}"; do
        seen=false
        for prior in "${UNIQUE[@]}"; do [[ "$prior" == "$file" ]] && seen=true; done
        $seen || UNIQUE+=("$file")
    done
    CANDIDATES=("${UNIQUE[@]}")
    if [[ "${#CANDIDATES[@]}" -eq 0 ]]; then
        info "No encontré un respaldo Oracle (.sql o .sql.gz)."
        echo "Colócalo en database/backups/ y vuelve a ejecutar:"
        echo "  bash scripts/importar-base-oracle.sh"
        exit 0
    elif [[ "${#CANDIDATES[@]}" -gt 1 ]]; then
        echo "Encontré varios respaldos. Indica cuál importar:"
        printf '  %s\n' "${CANDIDATES[@]}"
        echo "Ejemplo: bash scripts/importar-base-oracle.sh /ruta/al/respaldo.sql"
        exit 2
    fi
    DUMP="$(realpath "${CANDIDATES[0]}")"
fi

case "$DUMP" in
    *.sql) DUMP_KIND=plain ;;
    *.sql.gz) DUMP_KIND=gzip ;;
    *) fail "Formato no admitido. Usa un archivo .sql o .sql.gz." ;;
esac

info "Revisando el respaldo: $(basename "$DUMP")"
# Reconoce el nombre de esquema habitual en exportaciones de phpMyAdmin/MariaDB.
if [[ "$DUMP_KIND" == gzip ]]; then
    HEADER="$(gzip -dc "$DUMP" | head -c 65536 || true)"
else
    HEADER="$(head -c 65536 "$DUMP" || true)"
fi
SOURCE_DB="$(printf '%s\n' "$HEADER" | sed -nE \
    -e 's/^[[:space:]]*USE[[:space:]]+`?([A-Za-z0-9_]+)`?[[:space:]]*;.*/\1/ip' \
    -e 's/^[[:space:]]*CREATE[[:space:]]+DATABASE([[:space:]]+IF[[:space:]]+NOT[[:space:]]+EXISTS)?[[:space:]]+`?([A-Za-z0-9_]+)`?.*/\2/ip' \
    -e 's/^-- Current Database:[[:space:]]*`?([A-Za-z0-9_]+)`?.*/\1/ip' | head -n 1)"
if [[ -n "$SOURCE_DB" ]]; then
    [[ "$SOURCE_DB" =~ ^[A-Za-z0-9_]+$ ]] || fail "El respaldo declara un nombre de base no admitido."
    DB_DATABASE="$SOURCE_DB"
fi

info "Destino local: $DB_DATABASE"
info "Comprobando acceso a MariaDB"
mysql_run -Nse "SELECT VERSION();" >/dev/null || fail "No puedo conectar a MariaDB. Revisa XAMPP y DB_HOST/DB_PORT/DB_USERNAME/DB_PASSWORD en .env."

info "Creando la base si todavía no existe"
mysql_run -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" || fail "El usuario MySQL no puede crear la base. Revisa sus permisos."

TABLES="$(mysql_run -Nse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_DATABASE';")"
[[ "$TABLES" =~ ^[0-9]+$ ]] || fail "No pude verificar las tablas de destino."
if (( TABLES > 0 )); then
    fail "La base $DB_DATABASE ya contiene $TABLES tablas. No importé nada para evitar sobrescribir datos. Usa una base vacía o respalda y limpia la base manualmente antes de reintentar."
fi

info "Importando el respaldo de Oracle (puede tardar varios minutos)"
if [[ "$DUMP_KIND" == gzip ]]; then
    gzip -dc "$DUMP" | mysql_run "$DB_DATABASE" || fail "Falló la importación. Revisa el respaldo y el log de MariaDB."
else
    mysql_run "$DB_DATABASE" < "$DUMP" || fail "Falló la importación. Revisa el respaldo y el log de MariaDB."
fi

# Asegura que Laravel use el esquema identificado en el dump.
if grep -q '^DB_DATABASE=' .env; then
    sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE/" .env
else
    printf '\nDB_DATABASE=%s\n' "$DB_DATABASE" >> .env
fi

info "Verificando tablas importadas"
FINAL_TABLES="$(mysql_run -Nse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_DATABASE';")"
(( FINAL_TABLES > 0 )) || fail "La importación terminó pero no se encontraron tablas en $DB_DATABASE."
php artisan optimize:clear

printf '\nImportación completada. Base: %s | Tablas: %s\n' "$DB_DATABASE" "$FINAL_TABLES"
echo "Ahora puedes abrir http://localhost/rsu-cni"
