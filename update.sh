#!/usr/bin/env bash
set -euo pipefail

# update.sh
# Refresh-only script: permissions, cache, Caddy reload.
# DOES NOT deploy new code or rebuild containers.

APP_CONTAINER=healthcare-saas-app
CADDY_CONTAINER=caddy

log() { echo "[$(date '+%H:%M:%S')] $*"; }

require_cmd() {
    command -v "$1" >/dev/null 2>&1 || {
        log "ERROR: required command not found: $1"
        exit 1
    }
}

require_cmd docker

log "==> Refresh-only update starting (no code deploy)."

log "==> Fixing permissions..."
docker exec --user root "${APP_CONTAINER}" bash -lc '
    chown -R www-data:www-data bootstrap/cache storage &&
    chmod -R 775 bootstrap/cache storage
'

log "==> Clearing and rebuilding Laravel caches..."
docker exec "${APP_CONTAINER}" php artisan optimize:clear
docker exec "${APP_CONTAINER}" php artisan optimize

log "==> Reloading Caddy..."
docker exec "${CADDY_CONTAINER}" caddy reload \
    --config /etc/caddy/Caddyfile \
    --adapter caddyfile

log "==> Refresh-only update complete."
