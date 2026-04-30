#!/usr/bin/env bash
set -euo pipefail

export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
export DB_HOST="${DB_HOST:-127.0.0.1}"
export DB_PORT="${DB_PORT:-5433}"
export DB_DATABASE="${DB_DATABASE:-healthcare_saas_test}"
export DB_USERNAME="${DB_USERNAME:-healthcare}"
export DB_PASSWORD="${DB_PASSWORD:-secret}"

echo "== Practiq feature suite =="
echo "DB: ${DB_CONNECTION}://${DB_HOST}:${DB_PORT}/${DB_DATABASE}"
echo "Mode: sequential php artisan test tests/Feature"
echo

php artisan config:clear --ansi
php artisan test tests/Feature
