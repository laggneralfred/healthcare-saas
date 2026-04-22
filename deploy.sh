#!/usr/bin/env bash
set -euo pipefail

APP_DIR=/opt/practiq
COMPOSE="docker compose -f ${APP_DIR}/docker-compose.yml"
APP_CONTAINER=healthcare-saas-app
QUEUE_CONTAINER=healthcare-saas-queue
SCHEDULER_CONTAINER=healthcare-saas-scheduler
NGINX_CONTAINER=healthcare-saas-nginx
CADDY_CONTAINER=caddy
TARGET_BRANCH=master
MAX_WAIT=120

log() { echo "[$(date '+%H:%M:%S')] $*"; }

require_cmd() {
    command -v "$1" >/dev/null 2>&1 || {
        log "ERROR: required command not found: $1"
        exit 1
    }
}

wait_for_container() {
    local container=$1
    local waited=0

    log "==> Waiting for ${container} to be healthy..."
    until [ "$(docker inspect --format='{{.State.Health.Status}}' "${container}" 2>/dev/null || true)" = "healthy" ]; do
        if [ "$waited" -ge "$MAX_WAIT" ]; then
            log "ERROR: ${container} did not become healthy after ${MAX_WAIT}s"
            docker logs "${container}" --tail=50 || true
            exit 1
        fi
        sleep 3
        waited=$((waited + 3))
    done
    log "==> ${container} is healthy."
}

run_in_app() {
    docker exec "${APP_CONTAINER}" "$@"
}

run_in_app_root() {
    docker exec --user root "${APP_CONTAINER}" "$@"
}

clear_bootstrap_cache() {
    local container=$1
    docker exec --user root "${container}" rm -f \
        bootstrap/cache/packages.php \
        bootstrap/cache/services.php \
        bootstrap/cache/config.php || true
}

require_cmd git
require_cmd docker

log "==> Starting deploy from origin/${TARGET_BRANCH}"

if [ ! -d "${APP_DIR}/.git" ]; then
    log "ERROR: ${APP_DIR} is not a git repository"
    exit 1
fi

log "==> Fetching latest code..."
git -C "${APP_DIR}" fetch origin

log "==> Resetting working tree to origin/${TARGET_BRANCH}..."
git -C "${APP_DIR}" reset --hard "origin/${TARGET_BRANCH}"

DEPLOY_COMMIT="$(git -C "${APP_DIR}" rev-parse --short HEAD)"
DEPLOY_MESSAGE="$(git -C "${APP_DIR}" log -1 --pretty=%s)"
log "==> Deploying commit: ${DEPLOY_COMMIT}"
log "==> Commit message: ${DEPLOY_MESSAGE}"

log "==> Building app image (no cache)..."
${COMPOSE} build app --no-cache

log "==> Building queue and scheduler images..."
${COMPOSE} build queue scheduler

log "==> Stopping old containers..."
docker stop "${APP_CONTAINER}" "${NGINX_CONTAINER}" "${QUEUE_CONTAINER}" "${SCHEDULER_CONTAINER}" 2>/dev/null || true

log "==> Removing old containers..."
docker rm "${APP_CONTAINER}" "${NGINX_CONTAINER}" "${QUEUE_CONTAINER}" "${SCHEDULER_CONTAINER}" 2>/dev/null || true

log "==> Recreating containers..."
${COMPOSE} up -d --no-deps --force-recreate app nginx queue scheduler

wait_for_container "${APP_CONTAINER}"

log "==> Fixing permissions..."
run_in_app_root chown -R www-data:www-data bootstrap/cache storage
run_in_app_root chmod -R 775 bootstrap/cache storage

log "==> Clearing stale bootstrap cache files..."
clear_bootstrap_cache "${APP_CONTAINER}"
clear_bootstrap_cache "${QUEUE_CONTAINER}"
clear_bootstrap_cache "${SCHEDULER_CONTAINER}"

log "==> Running Laravel maintenance commands..."
run_in_app php artisan package:discover --ansi
run_in_app php artisan migrate --force
run_in_app php artisan optimize:clear
run_in_app php artisan optimize

log "==> Reloading Caddy..."
docker exec "${CADDY_CONTAINER}" caddy reload --config /etc/caddy/Caddyfile --adapter caddyfile

log "==> Deploy complete."
