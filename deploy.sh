#!/usr/bin/env bash
# deploy.sh — full rebuild and redeploy
# Rebuilds the app image, recreates containers, caches config.
# Run on the server: bash /home/alfre/healthcare-saas/deploy.sh
set -euo pipefail

APP_DIR=/home/alfre/healthcare-saas
COMPOSE="docker compose -f ${APP_DIR}/docker-compose.yml"
APP_CONTAINER=healthcare-saas-app
CADDY_CONTAINER=caddy

log() { echo "[$(date '+%H:%M:%S')] $*"; }

log "==> Building app image (no cache)..."
${COMPOSE} build app --no-cache

log "==> Recreating app, nginx, queue, scheduler containers..."
${COMPOSE} up -d --no-deps --force-recreate app nginx queue scheduler

log "==> Waiting for app container to be ready..."
sleep 3

log "==> Fixing bootstrap/cache and storage permissions..."
docker exec "${APP_CONTAINER}" bash -c "
    chown -R www-data:www-data bootstrap/cache storage && \
    chmod -R 775 bootstrap/cache storage
"

log "==> Caching config, routes, views..."
docker exec "${APP_CONTAINER}" php artisan optimize

log "==> Reloading Caddy..."
# Caddy resolves healthcare-saas-nginx by Docker DNS — no IP lookup needed.
docker exec "${CADDY_CONTAINER}" caddy reload \
    --config /etc/caddy/Caddyfile \
    --adapter caddyfile

log "==> Deploy complete."
