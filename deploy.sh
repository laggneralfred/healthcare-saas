#!/usr/bin/env bash
# deploy.sh — full rebuild and redeploy
set -euo pipefail
APP_DIR=/opt/practiq
COMPOSE="docker compose -f ${APP_DIR}/docker-compose.yml"
APP_CONTAINER=healthcare-saas-app
CADDY_CONTAINER=caddy
log() { echo "[$(date '+%H:%M:%S')] $*"; }
log "==> Pulling latest code from GitHub..."
cd ${APP_DIR}
git fetch origin
git reset --hard origin/master
log "==> Building app image (no cache)..."
${COMPOSE} build app --no-cache
log "==> Recreating containers..."
${COMPOSE} up -d --no-deps --force-recreate app nginx queue scheduler
log "==> Waiting for app container..."
sleep 15
log "==> Fixing permissions..."
docker exec --user root "${APP_CONTAINER}" chown -R www-data:www-data bootstrap/cache storage
log "==> Caching config, routes, views..."
docker exec "${APP_CONTAINER}" php artisan optimize
log "==> Reloading Caddy..."
docker exec "${CADDY_CONTAINER}" caddy reload \
    --config /etc/caddy/Caddyfile \
    --adapter caddyfile
log "==> Deploy complete."
