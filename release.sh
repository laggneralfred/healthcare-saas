#!/usr/bin/env bash
set -euo pipefail

MSG=${1:-"update"}

log() { echo "[$(date '+%H:%M:%S')] $*"; }

log "==> Current branch:"
git branch --show-current

log "==> Git status:"
git status -sb

log "==> Adding changes..."
git add .

log "==> Committing..."
git commit -m "$MSG" || log "Nothing to commit"

log "==> Running tests..."
php artisan test || {
    log "ERROR: Tests failed. Aborting."
    exit 1
}

log "==> Pushing dev..."
git push origin dev

log "==> Switching to master..."
git checkout master

log "==> Merging dev -> master..."
git merge dev

log "==> Pushing master..."
git push origin master

log "==> Returning to dev..."
git checkout dev

log "==> Done. Deploy triggered via push to master."
