# Use PHP 8.2 FPM

FROM php:8.2-fpm

# Install system dependencies

RUN apt-get update && apt-get install -y 
git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev 
&& docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd 
&& apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory

WORKDIR /var/www/html

# Copy all project files (including prebuilt public/build)

COPY . .

# Install PHP dependencies

RUN composer install --no-dev --optimize-autoloader

# Set Laravel permissions

RUN chown -R www-data:www-data storage bootstrap/cache public/build

# Expose port for PHP-FPM

EXPOSE 9000

# Start PHP-FPM

CMD ["php-fpm"]
