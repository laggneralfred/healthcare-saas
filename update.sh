#!/usr/bin/env bash
# update.sh — lightweight update, no image rebuild
# For config, view, or code changes that don't need a new image.
# Run on the server: bash /home/alfre/healthcare-saas/update.sh
set -euo pipefail

APP_DIR=/home/alfre/healthcare-saas
COMPOSE="docker compose -f ${APP_DIR}/docker-compose.yml"
APP_CONTAINER=healthcare-saas-app
CADDY_CONTAINER=caddy

log() { echo "[$(date '+%H:%M:%S')] $*"; }

log "==> Fixing bootstrap/cache and storage permissions..."
docker exec "${APP_CONTAINER}" bash -c "
    chown -R www-data:www-data bootstrap/cache storage && \
    chmod -R 775 bootstrap/cache storage
"

log "==> Clearing and rebuilding cache..."
docker exec "${APP_CONTAINER}" php artisan optimize:clear
docker exec "${APP_CONTAINER}" php artisan optimize

log "==> Reloading Caddy..."
docker exec "${CADDY_CONTAINER}" caddy reload \
    --config /etc/caddy/Caddyfile \
    --adapter caddyfile

log "==> Update complete."
