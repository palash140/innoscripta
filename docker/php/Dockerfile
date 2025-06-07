FROM php:8.2-fpm

WORKDIR /var/www/html

# Install system dependencies including cron
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libzip-dev \
    cron \
    supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath zip opcache

RUN pecl install redis && docker-php-ext-enable redis

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Create user and group
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www

# Copy application files
COPY --chown=www:www . /var/www/html

# Create crontab file
COPY --chown=www:www docker/crontab /etc/cron.d/laravel-cron

# Give execution rights on the cron job and create log file
RUN chmod 0644 /etc/cron.d/laravel-cron && \
    crontab -u www /etc/cron.d/laravel-cron && \
    touch /var/log/cron.log && \
    chown www:www /var/log/cron.log

# Copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Switch to www user for Laravel operations
USER www

# Install Laravel dependencies
RUN composer install --no-dev --optimize-autoloader

# Switch back to root for supervisor
USER root

EXPOSE 9000

# Start supervisor which will manage both php-fpm and cron
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]