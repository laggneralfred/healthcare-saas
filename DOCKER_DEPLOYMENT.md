# Docker Deployment Guide — Practiq on DigitalOcean

This guide covers deploying Practiq to a DigitalOcean droplet using Docker and Docker Compose.

## Prerequisites

- DigitalOcean Droplet (Ubuntu 22.04 LTS recommended, 2GB+ RAM minimum)
- SSH access to droplet (137.184.33.220)
- Domain name configured (DNS pointing to droplet IP)
- SSL certificate (Let's Encrypt recommended)

## Step 1: Prepare DigitalOcean Droplet

### SSH into Droplet
```bash
ssh root@137.184.33.220
```

### Update system packages
```bash
apt update && apt upgrade -y
```

### Install Docker & Docker Compose
```bash
# Install Docker
curl -fsSL https://get.docker.com -o get-docker.sh
sudo sh get-docker.sh

# Add your user to docker group (run as non-root)
sudo usermod -aG docker $USER
newgrp docker

# Install Docker Compose
sudo curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose

# Verify installation
docker --version
docker-compose --version
```

### Create application directory
```bash
sudo mkdir -p /app/practiq
sudo chown $USER:$USER /app/practiq
cd /app/practiq
```

## Step 2: Deploy Application Code

### Clone repository (or copy files)
```bash
# Option A: Clone from Git
git clone https://your-repo-url.git .

# Option B: Upload files
# Use scp to copy files from your local machine
# scp -r /path/to/healthcare-saas/* user@137.184.33.220:/app/practiq/
```

### Verify Docker files are present
```bash
ls -la | grep -E "docker|Docker|env"
# Should show: Dockerfile, docker-compose.yml, .dockerignore, .env.docker
```

## Step 3: Configure Environment

### Copy and customize .env file
```bash
cp .env.docker .env
nano .env
```

**Update these critical values:**

```env
# Application
APP_KEY=base64:$(php -r 'echo base64_encode(random_bytes(32));')
APP_URL=https://your-domain.com

# Database Credentials (change these!)
DB_PASSWORD=SECURE_RANDOM_PASSWORD

# Redis Password
REDIS_PASSWORD=ANOTHER_SECURE_PASSWORD

# Mail (Postmark)
POSTMARK_API_KEY=your_postmark_api_key

# Stripe Keys (Live)
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_SOLO_PRICE=price_...
STRIPE_CLINIC_PRICE=price_...
STRIPE_ENTERPRISE_PRICE=price_...

# AWS S3 (or use local storage)
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_BUCKET=...

# Domain settings
SESSION_DOMAIN=.your-domain.com
MAIL_FROM_ADDRESS=noreply@your-domain.com
```

### Generate APP_KEY (if not done above)
```bash
docker run --rm \
  -v /app/practiq:/app \
  -w /app \
  php:8.3-cli \
  php -r 'echo "base64:" . base64_encode(random_bytes(32)) . PHP_EOL;'

# Copy the output to APP_KEY in .env
```

## Step 4: Configure SSL Certificate

### Using Let's Encrypt with Certbot
```bash
# Install Certbot
sudo apt install certbot -y

# Generate certificate
sudo certbot certonly --standalone -d your-domain.com -d www.your-domain.com

# Certificates saved to:
# /etc/letsencrypt/live/your-domain.com/
```

### Configure Nginx to use certificate
```bash
# Edit Nginx config
nano docker/nginx/conf.d/app.conf

# Uncomment and update these lines:
# ssl_certificate /etc/nginx/ssl/cert.pem;
# ssl_certificate_key /etc/nginx/ssl/key.pem;

# Mount certificates into Nginx container (see Step 5)
```

## Step 5: Start Docker Services

### Update docker-compose.yml for SSL (optional)
```bash
# In docker-compose.yml, update nginx volumes to mount certificates:
volumes:
  - .:/app
  - ./docker/nginx/conf.d:/etc/nginx/conf.d:ro
  - /etc/letsencrypt/live/your-domain.com:/etc/nginx/ssl:ro
  - app_storage:/app/storage
```

### Build and start containers
```bash
cd /app/practiq

# Build images
docker-compose build

# Start services
docker-compose up -d

# Verify all services are running
docker-compose ps
```

Expected output:
```
NAME                    STATUS
healthcare-saas-postgres   Up
healthcare-saas-redis      Up
healthcare-saas-app        Up
healthcare-saas-nginx      Up
healthcare-saas-queue      Up
healthcare-saas-scheduler  Up
```

## Step 6: Initialize Database

### Run migrations
```bash
docker-compose exec app php artisan migrate --force
```

### Seed subscription plans
```bash
docker-compose exec app php artisan db:seed --class=DatabaseSeeder --force
```

### (Optional) Seed demo data
```bash
docker-compose exec app php artisan db:seed --class=DemoSeeder --force
```

### Generate cache
```bash
docker-compose exec app php artisan config:cache
docker-compose exec app php artisan route:cache
docker-compose exec app php artisan view:cache
```

## Step 7: Configure SSL Certificate Auto-Renewal

### Create renewal script
```bash
sudo nano /usr/local/bin/renewal-docker-hook.sh
```

Add:
```bash
#!/bin/bash
cd /app/practiq
docker-compose exec -T app php artisan cache:clear
docker-compose restart nginx
```

Save and make executable:
```bash
sudo chmod +x /usr/local/bin/renewal-docker-hook.sh
```

### Add to certbot renewal hooks
```bash
sudo nano /etc/letsencrypt/renewal/your-domain.com.conf
```

Add:
```
post_renewal_hook = /usr/local/bin/renewal-docker-hook.sh
```

## Step 8: Verify Deployment

### Check app health
```bash
curl http://localhost/health
# Should return: healthy

curl https://your-domain.com/health
# Should return: healthy
```

### View logs
```bash
# All services
docker-compose logs -f

# Specific service
docker-compose logs -f app
docker-compose logs -f nginx
docker-compose logs -f queue

# From inside container
docker-compose exec app tail -f storage/logs/laravel.log
```

### Test email queue
```bash
# Send test email (creates a test user and triggers welcome email)
docker-compose exec app php artisan tinker
# Inside tinker:
# >>> \App\Models\User::factory()->create(['email' => 'test@example.com']);
# >>> Mail::send(new \App\Mail\TrialWelcomeMail(...));
```

## Step 9: Setup Monitoring & Backups

### Database backups
```bash
# Create backup script
sudo nano /usr/local/bin/backup-db.sh
```

Add:
```bash
#!/bin/bash
BACKUP_DIR="/backups/database"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR
cd /app/practiq
docker-compose exec -T postgres pg_dump -U app_user healthcare_saas_prod | gzip > $BACKUP_DIR/healthcare_saas_${DATE}.sql.gz
# Keep only last 7 days
find $BACKUP_DIR -type f -mtime +7 -delete
```

Make executable and add to crontab:
```bash
sudo chmod +x /usr/local/bin/backup-db.sh
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-db.sh
```

### Monitor services with health checks
```bash
docker-compose ps --format "table {{.Names}}\t{{.Status}}"
```

## Step 10: Configure Stripe Webhooks

### Register webhook endpoint
1. Go to Stripe Dashboard → Webhooks
2. Add endpoint: `https://your-domain.com/stripe/webhook`
3. Select events:
   - `customer.subscription.created`
   - `customer.subscription.updated`
   - `customer.subscription.deleted`
   - `invoice.payment_succeeded`
   - `invoice.payment_failed`
4. Copy signing secret to `STRIPE_WEBHOOK_SECRET` in `.env`
5. Restart app: `docker-compose restart app`

## Troubleshooting

### Check service health
```bash
docker-compose ps
docker-compose logs app
docker-compose logs nginx
```

### App container not starting
```bash
# View full error
docker-compose logs app --tail=100

# Check PHP syntax
docker-compose exec app php -l

# Check database connection
docker-compose exec app php artisan tinker
# >>> \DB::connection()->getPdo();
```

### Nginx 502 Bad Gateway
```bash
# Verify app container is running
docker-compose ps app

# Check app logs
docker-compose logs app

# Restart services
docker-compose restart app nginx
```

### Database connection errors
```bash
# Verify database is running
docker-compose exec postgres pg_isready

# Check credentials in .env
grep DB_ .env

# Test connection
docker-compose exec app php artisan tinker
# >>> \DB::connection()->select('select 1');
```

### Redis connection errors
```bash
# Verify Redis is running
docker-compose exec redis redis-cli ping

# Check Redis password
grep REDIS_PASSWORD .env

# Test connection
docker-compose exec app php artisan tinker
# >>> \Redis::connection()->ping();
```

## Maintenance

### Update application code
```bash
cd /app/practiq
git pull origin main  # or your deployment branch
docker-compose build
docker-compose up -d
docker-compose exec app php artisan migrate --force
```

### View queue jobs
```bash
docker-compose exec app php artisan queue:failed
docker-compose exec app php artisan queue:retry all
```

### Clear cache
```bash
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan view:clear
docker-compose exec app php artisan route:clear
```

### Scale services (if needed)
```bash
# Run multiple queue workers
docker-compose up -d --scale queue=3
```

## Performance Optimization

### Enable HTTP/2
Already configured in nginx conf.d/app.conf

### Enable Gzip compression
Already configured in nginx conf.d/app.conf

### Monitor resources
```bash
docker stats
```

### Increase PHP limits (if needed)
Edit opcache.ini and rebuild:
```bash
docker-compose build --no-cache app
docker-compose up -d
```

## Security Checklist

- [x] SSL/TLS certificate installed
- [ ] HSTS enabled (uncomment in nginx config)
- [ ] Security headers configured
- [ ] Database password changed
- [ ] Redis password changed
- [ ] APP_KEY generated
- [ ] APP_DEBUG=false
- [ ] Postmark API key configured
- [ ] Stripe webhook secret configured
- [ ] S3 bucket configured with proper permissions
- [ ] Regular database backups configured
- [ ] Monitor logs for suspicious activity

## Support

For issues or questions:
1. Check `docker-compose logs` for error details
2. Review DEPLOYMENT.md for general deployment guidance
3. Check Laravel logs at `/app/practiq/storage/logs/laravel.log`
