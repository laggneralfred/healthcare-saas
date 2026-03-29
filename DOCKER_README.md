# Docker Setup for Practiq

This directory contains a complete Docker setup for deploying Practiq to any environment (local development or DigitalOcean production).

## Files Overview

### Core Docker Files

- **`Dockerfile`** — Multi-stage build for PHP-FPM application
  - Installs PHP extensions: pgsql, redis, intl, mbstring, xml, zip, bcmath
  - Optimizes for production with opcache
  - Installs Node.js to build frontend assets (Vite)
  - ~500MB final image size

- **`docker-compose.yml`** — Production service orchestration
  - **postgres** — PostgreSQL 16 database
  - **redis** — Redis 7 for caching and sessions
  - **app** — PHP-FPM application container
  - **nginx** — Nginx reverse proxy (port 80/443)
  - **queue** — Laravel queue worker (email, jobs)
  - **scheduler** — Laravel task scheduler (cron jobs)

- **`docker-compose.local.yml`** — Development overrides
  - Enables debug mode
  - Adds Mailhog for email testing (port 8025)
  - Exposes ports for direct debugging
  - Usage: `docker-compose -f docker-compose.yml -f docker-compose.local.yml up`

### Nginx Configuration

- **`docker/nginx/conf.d/app.conf`** — Nginx web server config
  - HTTP/2 support
  - Gzip compression
  - Security headers (HSTS, X-Frame-Options, CSP)
  - SSL/TLS configuration (for production)
  - Static asset caching
  - Upstream PHP-FPM routing

### Environment Configuration

- **`.env.docker`** — Template for Docker deployment
  - Copy to `.env` and customize
  - Contains all required variables for production setup
  - Includes placeholders for Stripe, AWS S3, Postmark email

- **`.env.example`** — Standard Laravel local development template
- **`.env.production.example`** — Non-Docker production template

### Scripts & Documentation

- **`docker-start.sh`** — Quick-start script
  - Interactive setup for local or production
  - Builds images, starts containers, runs migrations
  - Usage: `./docker-start.sh`

- **`DOCKER_DEPLOYMENT.md`** — Complete deployment guide
  - Step-by-step instructions for DigitalOcean
  - SSL certificate setup (Let's Encrypt)
  - Database backups and monitoring
  - Troubleshooting section

- **`opcache.ini`** — PHP opcache configuration
  - Production-optimized cache settings
  - Memory allocation: 256MB
  - Max files: 20,000

- **`.dockerignore`** — Files excluded from Docker build
  - Reduces build context size
  - Excludes: .git, node_modules, vendor, storage/logs, etc.

## Quick Start

### Local Development
```bash
./docker-start.sh
# Follow interactive prompts
# App: http://localhost:8000
# Mailhog: http://localhost:8025
```

### Production Deployment (DigitalOcean)
```bash
# 1. Copy .env.docker to .env
cp .env.docker .env
nano .env  # Configure values

# 2. Build and start
docker-compose build
docker-compose up -d

# 3. Initialize database
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan db:seed --class=DatabaseSeeder --force
```

## Service Details

### PostgreSQL
- **Container:** `healthcare-saas-postgres`
- **Port:** 5432 (exposed for local development)
- **Data:** Persisted in `postgres_data` volume
- **Health check:** pg_isready every 10 seconds

### Redis
- **Container:** `healthcare-saas-redis`
- **Port:** 6379 (exposed for local development)
- **Data:** Persisted in `redis_data` volume with AOF
- **Health check:** redis-cli ping every 10 seconds

### PHP-FPM Application
- **Container:** `healthcare-saas-app`
- **Port:** 9000 (internal only, accessed via Nginx)
- **Volumes:** Code mounted at `/app`, storage in persistent volumes
- **Health check:** curl to FPM status every 30 seconds
- **Queue worker & Scheduler:** Separate containers running artisan commands

### Nginx
- **Container:** `healthcare-saas-nginx`
- **Ports:** 80 (HTTP), 443 (HTTPS)
- **Config:** `docker/nginx/conf.d/app.conf`
- **SSL:** Commented out by default (uncomment for production)
- **Health check:** wget to `/health` endpoint every 10 seconds

## Environment Variables

**Database:**
```env
DB_CONNECTION=pgsql
DB_HOST=postgres
DB_PORT=5432
DB_DATABASE=healthcare_saas_prod
DB_USERNAME=app_user
DB_PASSWORD=secure_password
```

**Cache/Sessions/Queue:**
```env
CACHE_STORE=redis
CACHE_PREFIX=healthcare_saas_prod
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=redis
REDIS_PASSWORD=secure_password
```

**Email:**
```env
MAIL_MAILER=postmark          # postmark or log (development)
POSTMARK_API_KEY=your_key
MAIL_FROM_ADDRESS=noreply@your-domain.com
```

**Stripe (Production):**
```env
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

**File Storage:**
```env
FILESYSTEM_DISK=s3           # s3 or local
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_BUCKET=...
```

## Common Commands

### Service Management
```bash
# Start services
docker-compose up -d

# Stop services
docker-compose down

# View logs
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f queue

# Check service status
docker-compose ps
docker stats

# Restart a service
docker-compose restart app
```

### Database
```bash
# Run migrations
docker-compose exec app php artisan migrate --force

# Seed data
docker-compose exec app php artisan db:seed --class=DatabaseSeeder --force

# Create backup
docker-compose exec postgres pg_dump -U app_user healthcare_saas_prod > backup.sql

# Restore backup
cat backup.sql | docker-compose exec -T postgres psql -U app_user healthcare_saas_prod
```

### Application
```bash
# Run artisan commands
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan queue:work --once

# Tinker (Laravel REPL)
docker-compose exec app php artisan tinker

# Generate application key
docker-compose exec app php artisan key:generate
```

### Debugging
```bash
# View application logs
docker-compose exec app tail -f storage/logs/laravel.log

# Check PHP configuration
docker-compose exec app php -i

# Test database connection
docker-compose exec app php artisan tinker
# >>> DB::connection()->select('select 1');

# Test Redis connection
docker-compose exec app php artisan tinker
# >>> Redis::connection()->ping();
```

## Performance Tuning

### Increase Memory Limits
Edit `docker-compose.yml` and add limits:
```yaml
services:
  app:
    mem_limit: 2g
    memswap_limit: 2g
```

### Scale Queue Workers
```bash
docker-compose up -d --scale queue=3
```

### Enable Caching
Already configured in production setup. Verify with:
```bash
docker-compose exec app php artisan config:show | grep CACHE_STORE
```

## Security

### SSL/TLS Setup
1. Obtain certificate (Let's Encrypt):
   ```bash
   sudo certbot certonly --standalone -d your-domain.com
   ```

2. Update nginx config in `docker/nginx/conf.d/app.conf`:
   ```nginx
   ssl_certificate /etc/nginx/ssl/cert.pem;
   ssl_certificate_key /etc/nginx/ssl/key.pem;
   ```

3. Mount certificate in `docker-compose.yml`:
   ```yaml
   volumes:
     - /etc/letsencrypt/live/your-domain.com:/etc/nginx/ssl:ro
   ```

4. Uncomment HSTS and SSL configuration
5. Restart nginx: `docker-compose restart nginx`

### Environment Secrets
- Never commit `.env` to git
- Use strong passwords for DB_PASSWORD and REDIS_PASSWORD
- Rotate Stripe/AWS keys regularly
- Store secrets in `.env` (local) or use Docker Secrets (production)

## Troubleshooting

### Container won't start
```bash
docker-compose logs app
# Check for configuration errors in .env
# Verify database credentials
```

### Nginx 502 Bad Gateway
```bash
docker-compose logs nginx
docker-compose exec app php-fpm -t
# Verify app container is running and healthy
```

### Database connection errors
```bash
docker-compose exec postgres psql -U app_user
# Enter password from .env
# \l to list databases
# \c healthcare_saas_prod to connect
```

### Queue not processing jobs
```bash
docker-compose logs queue
# Verify QUEUE_CONNECTION=redis in .env
# Check Redis connection: docker-compose exec redis redis-cli ping
```

## Monitoring

### Health Checks
Each service has configured health checks that run automatically.
View status:
```bash
docker-compose ps
```

### Logs Aggregation (Production)
Consider integrating with:
- **Sentry** — Error tracking
- **ELK Stack** — Log aggregation
- **Datadog** — APM and monitoring
- **New Relic** — Performance monitoring

## Backup & Recovery

### Automated Backups (Production)
See `DOCKER_DEPLOYMENT.md` for backup script setup

### Manual Backup
```bash
# Database
docker-compose exec postgres pg_dump -U app_user healthcare_saas_prod | gzip > backup.sql.gz

# Uploaded files (if using S3, backup bucket)
aws s3 sync s3://your-bucket . --profile your-aws-profile
```

### Restore Database
```bash
gunzip < backup.sql.gz | docker-compose exec -T postgres psql -U app_user healthcare_saas_prod
```

## Production Checklist

- [x] Dockerfile multi-stage optimized
- [x] docker-compose.yml includes all services
- [x] .env.docker template created
- [x] DOCKER_DEPLOYMENT.md with full guide
- [ ] SSL certificate configured
- [ ] Environment variables customized
- [ ] Database backed up
- [ ] Email service (Postmark) configured
- [ ] Stripe webhooks registered
- [ ] S3 bucket configured
- [ ] Monitoring/alerting set up
- [ ] Log rotation configured
- [ ] Health checks verified

## Support

For issues:
1. Check `docker-compose logs` for error details
2. Review DOCKER_DEPLOYMENT.md for specific setup steps
3. See Troubleshooting section above
4. Check Laravel logs: `docker-compose exec app tail -f storage/logs/laravel.log`
