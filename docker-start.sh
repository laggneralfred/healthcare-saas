#!/bin/bash
set -e

echo "🚀 Practiq Docker Quick Start"
echo "=================================="

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

echo "✓ Docker found"

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env from .env.docker..."
    cp .env.docker .env
    echo "⚠️  Please edit .env with your configuration before proceeding."
    echo "Run: nano .env"
    exit 1
fi

echo "✓ .env file found"

# Ask if we should use local or production setup
read -p "Are you setting up for local development? (y/n) " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    COMPOSE_ARGS="-f docker-compose.yml -f docker-compose.local.yml"
    echo "📦 Using local development configuration"
    echo "   - Debug mode enabled"
    echo "   - Mailhog for email testing at http://localhost:8025"
    echo "   - App at http://localhost:8000"
else
    COMPOSE_ARGS="-f docker-compose.yml"
    echo "📦 Using production configuration"
fi

# Build images
echo ""
echo "🔨 Building Docker images..."
docker-compose $COMPOSE_ARGS build

# Start containers
echo ""
echo "▶️  Starting containers..."
docker-compose $COMPOSE_ARGS up -d

# Wait for services to be healthy
echo ""
echo "⏳ Waiting for services to be healthy..."
sleep 10

# Run migrations
echo ""
echo "🗄️  Running database migrations..."
docker-compose $COMPOSE_ARGS exec -T app php artisan migrate --force

# Seed database
echo ""
echo "🌱 Seeding database..."
docker-compose $COMPOSE_ARGS exec -T app php artisan db:seed --class=DatabaseSeeder --force

# Generate cache
echo ""
echo "⚙️  Generating application cache..."
docker-compose $COMPOSE_ARGS exec -T app php artisan config:cache
docker-compose $COMPOSE_ARGS exec -T app php artisan route:cache
docker-compose $COMPOSE_ARGS exec -T app php artisan view:cache

# Display summary
echo ""
echo "=================================="
echo "✅ Practiq is ready!"
echo "=================================="
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "📱 Local Development URLs:"
    echo "   Web App:     http://localhost:8000"
    echo "   Mailhog:     http://localhost:8025"
    echo "   Database:    localhost:5432"
    echo "   Redis:       localhost:6379"
    echo ""
    echo "👤 Demo Login:"
    echo "   Email:    demo@example.com"
    echo "   Password: password"
    echo ""
    echo "📖 View logs:  docker-compose logs -f app"
    echo "🛑 Stop all:   docker-compose down"
else
    echo "📱 Production URLs:"
    echo "   Configure your domain in .env (APP_URL)"
    echo "   Configure SSL in docker/nginx/conf.d/app.conf"
    echo ""
    echo "📖 View logs:  docker-compose logs -f app"
    echo "🛑 Stop all:   docker-compose down"
fi

echo ""
echo "📚 Full guide: see DOCKER_DEPLOYMENT.md"
