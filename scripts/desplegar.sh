#!/usr/bin/env bash
# Actualiza Aldea Tecnológica en el servidor de producción con lo último de la rama main.
# Uso (en el servidor, dentro de la carpeta del proyecto):  bash scripts/desplegar.sh
# Nunca borra datos: respalda la base antes de migrar y solo agrega cambios.
set -euo pipefail

cd "$(dirname "$0")/.."

RESPALDOS="${RESPALDOS:-$HOME/respaldos}"
mkdir -p "$RESPALDOS"

echo "==> Respaldo de la base de datos"
DB=$(grep -E '^DB_DATABASE=' .env | cut -d= -f2- | tr -d '"')
# Usuario y contraseña de MySQL salen de ~/.my.cnf (ver docs/DESPLIEGUE.md)
mysqldump --single-transaction "$DB" | gzip > "$RESPALDOS/antes-de-desplegar-$(date +%F-%H%M).sql.gz"

echo "==> Modo mantenimiento"
php artisan down --retry=30 || true

# Si algo falla, el sitio vuelve a estar en línea
trap 'php artisan up' EXIT

echo "==> Código"
git pull --ff-only origin main

echo "==> Dependencias de PHP"
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Migraciones"
php artisan migrate --force

echo "==> Estilos y scripts"
npm ci --no-audit --no-fund
npm run build

echo "==> Caché de configuración, rutas y vistas"
php artisan optimize

echo "==> Listo"
