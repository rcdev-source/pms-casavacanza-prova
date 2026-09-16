#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKEND_DIR="$PROJECT_ROOT/backend"
FRONTEND_DIR="$PROJECT_ROOT/frontend"

if [ -n "${CODESPACE_NAME:-}" ] && [ -n "${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN:-}" ]; then
    FRONTEND_ORIGIN="https://${CODESPACE_NAME}-5173.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"
    API_ORIGIN="https://${CODESPACE_NAME}-8000.${GITHUB_CODESPACES_PORT_FORWARDING_DOMAIN}"
else
    FRONTEND_ORIGIN="http://localhost:5173"
    API_ORIGIN="http://localhost:8000"
fi

sed -i "s|^APP_URL=.*|APP_URL=$API_ORIGIN|" "$BACKEND_DIR/.env"
sed -i "s|^FRONTEND_URL=.*|FRONTEND_URL=$FRONTEND_ORIGIN|" "$BACKEND_DIR/.env"

stop_process() {
    local pid_file="$1"
    if [ -f "$pid_file" ]; then
        local process_id
        process_id="$(cat "$pid_file")"
        if kill -0 "$process_id" 2>/dev/null; then
            kill "$process_id"
        fi
        rm -f "$pid_file"
    fi
}

stop_process /tmp/pms-backend.pid
stop_process /tmp/pms-frontend.pid

cd "$BACKEND_DIR"
php artisan config:clear >/dev/null
nohup php artisan serve --host=0.0.0.0 --port=8000 >/tmp/pms-backend.log 2>&1 &
echo $! >/tmp/pms-backend.pid

cd "$FRONTEND_DIR"
nohup env VITE_API_URL="$API_ORIGIN/api/v1" npm run dev -- --host 0.0.0.0 >/tmp/pms-frontend.log 2>&1 &
echo $! >/tmp/pms-frontend.pid

for _ in $(seq 1 30); do
    if curl --silent --fail http://127.0.0.1:8000/up >/dev/null && curl --silent --fail http://127.0.0.1:5173 >/dev/null; then
        echo "PMS avviato: $FRONTEND_ORIGIN"
        exit 0
    fi
    sleep 1
done

echo "Avvio non completato. Controlla /tmp/pms-backend.log e /tmp/pms-frontend.log." >&2
exit 1
