# ---------------------------------------
# 1. Build JS assets
# ---------------------------------------
FROM node:18 AS frontend

WORKDIR /app

COPY package*.json ./


# ---------------------------------------
# 2. Build PHP/Laravel app
# ---------------------------------------
FROM php:8.2-fpm

# Install extensions
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring zip bcmath gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy app source code
COPY . .

# Copy built frontend from step 1
COPY --from=frontend /app/public ./public

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Set permissions
RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
