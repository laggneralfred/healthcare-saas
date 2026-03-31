#!/bin/bash
set -e

echo "--- Starting Demo Deployment ---"

# 1. Run migration
echo "1. Running migrations..."
docker compose exec app php artisan migrate --force

# 2. Seed demo data
echo "2. Seeding demo data..."
docker compose exec app php artisan db:seed --class=DemoSeeder --force

# 3. Verify demo user exists
echo "3. Verifying demo user exists in DB..."
docker exec healthcare-saas-postgres psql -U healthcare -d healthcare_saas \
   -c "SELECT email, practice_id FROM users WHERE email='demo@practiqapp.com';"

# 4. Add demo.practiqapp.com to Caddyfile and reload
echo "4. IMPORTANT: Manually ensure the Caddyfile contains the demo.practiqapp.com block."
echo "   Then run: docker restart caddy"

# 5. Test demo login
echo "5. Testing demo login endpoint..."
curl -I https://demo.practiqapp.com/demo-login

echo "--- Demo Deployment Complete ---"
