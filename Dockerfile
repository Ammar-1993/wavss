FROM php:8.2-apache

# Install dependencies and PHP extensions required by the application
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libzip-dev \
    libpng-dev \
    && docker-php-ext-install mysqli zip gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Remove tests directory to prevent exposing dummy E2E targets to production
RUN rm -rf /var/www/html/tests

# Ensure directories (like logs, reports) exist and are writable by the web server
RUN mkdir -p /var/www/html/scanner/reports \
             /var/www/html/scanner/logs \
             /var/www/html/crawler/logs \
    && chown -R www-data:www-data /var/www/html

# Install Composer dependencies (no-dev for production)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader

HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/healthz.php || exit 1

EXPOSE 80
