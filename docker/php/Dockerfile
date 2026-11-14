FROM php:8.4-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    libssl-dev \
    pkg-config \
    zip \
    unzip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache

# Install MongoDB extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Configuration PHP de production : les erreurs sont journalisees, jamais affichees aux visiteurs
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# PHP performance config
COPY docker/php/php-dev.ini /usr/local/etc/php/conf.d/zz-custom.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Set environment to production BEFORE composer install
ENV APP_ENV=prod

# Copy project files
COPY . .

# Install PHP dependencies (production : no dev, uses lock file)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Warm up Symfony cache at build time (timeout 30s car la BDD n'est pas disponible au build)
RUN timeout 30 php bin/console cache:warmup --no-interaction || true

# Compile assets for production
RUN timeout 60 php bin/console asset-map:compile --no-interaction || true

# Set permissions
RUN chown -R www-data:www-data var/

EXPOSE 9000

COPY docker/php/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["php-fpm"]
