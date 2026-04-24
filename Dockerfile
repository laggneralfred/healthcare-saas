# Multi-stage build for Laravel application
FROM php:8.4-fpm AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    git \
    zip \
    unzip \
    postgresql-client \
    libpq-dev \
    libicu-dev \
    libxml2-dev \
    libzip-dev \
    libonig-dev \
    autoconf \
    make \
    g++ \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql \
    && docker-php-ext-install \
    pdo \
    pdo_pgsql \
    pgsql \
    intl \
    mbstring \
    xml \
    zip \
    bcmath \
    opcache

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Configure PHP for production
RUN echo "upload_max_filesize = 50M" > /usr/local/etc/php/conf.d/upload.ini && \
    echo "post_max_size = 50M" >> /usr/local/etc/php/conf.d/upload.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/execution.ini

# Optimize opcache
COPY --chown=www-data:www-data opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application code
COPY --chown=www-data:www-data . .

# Create required directories before composer install
RUN mkdir -p bootstrap/cache storage/logs storage/framework/cache storage/framework/sessions storage/framework/views \
    && chmod -R 775 bootstrap/cache storage \
    && chown -R www-data:www-data bootstrap/cache storage

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-interaction

# Install Node.js and build frontend assets
FROM node:20-alpine AS frontend-builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

# Final production image
FROM base AS production

# Copy built frontend assets from builder
COPY --from=frontend-builder /app/public/build ./public/build

# Set environment to production
ENV APP_ENV=production

# Create non-root user
RUN usermod -u 1000 www-data && groupmod -g 1000 www-data

# Make test-time env/cache paths available to the runtime user.
RUN mkdir -p vendor/pestphp/pest-plugin-mutate/.temp vendor/pestphp/pest/.temp \
    && chown -R www-data:www-data vendor/pestphp/pest-plugin-mutate/.temp vendor/pestphp/pest/.temp \
    && touch .env \
    && chown www-data:www-data .env

# Set working directory
WORKDIR /app

# Expose FPM port (internal, used by Nginx)
EXPOSE 9000

# Run PHP-FPM as www-data user
USER www-data
CMD ["php-fpm"]
