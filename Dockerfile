FROM php:8.2-apache

# Install dependencies and PHP extensions required by the application
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install mysqli zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Ensure directories (like logs) are writable by the web server
RUN chown -R www-data:www-data /var/www/html

# Install Composer dependencies (no-dev for production)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader

EXPOSE 80
