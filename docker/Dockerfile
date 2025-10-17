# Use the official PHP image
FROM php:8.2-apache AS dev

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libgd-dev \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Drupal
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        opcache

# Enable Apache modules
RUN a2enmod rewrite headers

# Set recommended PHP.ini settings for Drupal
RUN { \
        echo 'memory_limit=512M'; \
        echo 'upload_max_filesize=64M'; \
        echo 'post_max_size=64M'; \
        echo 'max_execution_time=300'; \
        echo 'max_input_vars=3000'; \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.interned_strings_buffer=8'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.revalidate_freq=2'; \
        echo 'opcache.fast_shutdown=1'; \
    } > /usr/local/etc/php/conf.d/drupal-recommended.ini

# Configure Apache for Drupal - copy configuration file
COPY docker/apache-drupal.conf /etc/apache2/sites-available/000-default.conf

# Set the working directory to web root
WORKDIR /var/www/html

# Copy composer files and install dependencies
COPY composer.json composer.lock ./
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create required directories for Drupal
RUN mkdir -p /var/www/html/web/sites/default/files \
    /var/www/html/web/sites/default/files/php \
    /var/www/html/web/sites/default/private \
    /tmp/drupal_cache

# Install composer dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html /tmp/drupal_cache && \
    chmod -R 775 /var/www/html/web/sites/default/files \
    /var/www/html/web/sites/default/private \
    /tmp/drupal_cache

# Copy and set up entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Use custom entrypoint
ENTRYPOINT ["/entrypoint.sh"]

# Expose port 80
EXPOSE 80

FROM dev AS prod

# Use production Apache configuration
COPY docker/apache-drupal-prod.conf /etc/apache2/sites-available/000-default.conf

# Enable required Apache modules for production
RUN a2enmod expires deflate headers

COPY web /var/www/html/web
COPY docker/prod.settings.php /var/www/html/web/sites/default/settings.php

# Copy and set up entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh


FROM prod AS stage

# Override the robots.txt for staging environment
COPY docker/staging.robot.txt /var/www/html/web/robots.txt
# Use staging .htaccess to prevent indexing
COPY docker/htaccess.staging /var/www/html/web/.htaccess