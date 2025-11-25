# PHP 8.2 FPM

FROM php:8.2-fpm

# Install system dependencies and PHP extensions

RUN apt-get update && apt-get install -y 
git curl zip unzip libpng-dev libonig-dev libxml2-dev libzip-dev nodejs npm 
&& docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd 
&& apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory

WORKDIR /var/www/html

# Copy all files

COPY . .

# Install PHP dependencies

RUN composer install --no-dev --optimize-autoloader

# Install JS dependencies & build assets

RUN npm install
RUN npm run build

# Set Laravel permissions

RUN chown -R www-data:www-data storage bootstrap/cache public/build

# Expose port for PHP-FPM

EXPOSE 8080

# Start Laravel server (for development only; for production use PHP-FPM + Nginx)

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8080"]
