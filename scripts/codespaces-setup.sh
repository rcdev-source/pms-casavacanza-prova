#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_DIR="$PROJECT_ROOT/backend"
FRONTEND_DIR="$PROJECT_ROOT/frontend"
DATABASE_PATH="$BACKEND_DIR/database/database.sqlite"

echo "Preparazione PMS Casa Vacanze..."

composer install --working-dir="$BACKEND_DIR" --no-interaction --prefer-dist
npm install --prefix "$FRONTEND_DIR"

if [ ! -f "$BACKEND_DIR/.env" ]; then
    cp "$BACKEND_DIR/.env.codespaces.example" "$BACKEND_DIR/.env"
fi

touch "$DATABASE_PATH"
sed -i "s|^DB_DATABASE=.*|DB_DATABASE=$DATABASE_PATH|" "$BACKEND_DIR/.env"

cd "$BACKEND_DIR"
php artisan key:generate --force
php artisan migrate --seed --force
php artisan storage:link >/dev/null 2>&1 || true

echo "Preparazione completata."
