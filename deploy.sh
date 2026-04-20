#!/usr/bin/env bash
# deploy.sh — full rebuild and redeploy
set -euo pipefail

APP_DIR=/home/alfre/healthcare-saas
COMPOSE="docker compose -f ${APP_DIR}/docker-compose.yml"
APP_CONTAINER=healthcare-saas-app
CADDY_CONTAINER=caddy

log() { echo "[$(date '+%H:%M:%S')] $*"; }

wait_for_container() {
    local container=$1
    local max_wait=120
    local waited=0
    log "==> Waiting for ${container} to be healthy..."
    until [ "$(docker inspect --format='{{.State.Health.Status}}' ${container} 2>/dev/null)" = "healthy" ]; do
        if [ $waited -ge $max_wait ]; then
            log "ERROR: ${container} did not become healthy after ${max_wait}s"
            docker logs ${container} --tail=30
            exit 1
        fi
        sleep 3
        waited=$((waited + 3))
    done
    log "==> ${container} is healthy."
}

log "==> Pulling latest code from GitHub..."
cd ${APP_DIR}
git fetch origin
git reset --hard origin/master

log "==> Building app image (no cache)..."
${COMPOSE} build app --no-cache

log "==> Stopping and removing old app containers..."
docker stop healthcare-saas-app healthcare-saas-nginx healthcare-saas-queue healthcare-saas-scheduler 2>/dev/null || true
docker rm   healthcare-saas-app healthcare-saas-nginx healthcare-saas-queue healthcare-saas-scheduler 2>/dev/null || true

log "==> Recreating containers..."
${COMPOSE} up -d --no-deps --force-recreate app nginx queue scheduler

wait_for_container "${APP_CONTAINER}"

log "==> Fixing permissions..."
docker exec --user root "${APP_CONTAINER}" chown -R www-data:www-data bootstrap/cache storage

log "==> Caching config, routes, views..."
docker exec --user root "${APP_CONTAINER}" rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php
docker exec "${APP_CONTAINER}" php artisan package:discover --ansi
docker exec "${APP_CONTAINER}" php artisan migrate --force
docker exec "${APP_CONTAINER}" php artisan optimize

docker exec --user root healthcare-saas-queue rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php
docker exec --user root healthcare-saas-scheduler rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php

log "==> Reloading Caddy..."
docker exec "${CADDY_CONTAINER}" caddy reload \
    --config /etc/caddy/Caddyfile \
    --adapter caddyfile

log "==> Deploy complete."
