# Use the official PHP image
FROM php:8.2-apache

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

# Configure Apache for Drupal - create the configuration inline
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/web\n\
    ServerName localhost\n\
    \n\
    <Directory "/var/www/html/web">\n\
        AllowOverride All\n\
        Require all granted\n\
        Options -Indexes +FollowSymLinks\n\
        \n\
        # Security headers\n\
        Header always set X-Content-Type-Options nosniff\n\
        Header always set X-Frame-Options SAMEORIGIN\n\
        Header always set X-XSS-Protection "1; mode=block"\n\
    </Directory>\n\
    \n\
    # Deny access to sensitive Drupal files (but allow index.php and other entry points)\n\
    <FilesMatch "\\.(engine|inc|install|make|module|profile|po|sh|.*sql|theme|twig|tpl(\\.php)?|xtmpl|yml|yaml)$">\n\
        Require all denied\n\
    </FilesMatch>\n\
    \n\
    # Deny access to backup files\n\
    <FilesMatch "\\.php(~|\\.sw[op]|\\.bak|\\.orig|\\.save)$">\n\
        Require all denied\n\
    </FilesMatch>\n\
    \n\
    # Deny access to composer files\n\
    <FilesMatch "^composer\\.(json|lock)$">\n\
        Require all denied\n\
    </FilesMatch>\n\
    \n\
    # Deny access to hidden files\n\
    <FilesMatch "^\\.">\n\
        Require all denied\n\
    </FilesMatch>\n\
    \n\
    # Deny access to .git directories\n\
    <DirectoryMatch "/\\.git">\n\
        Require all denied\n\
    </DirectoryMatch>\n\
    \n\
    ErrorLog ${APACHE_LOG_DIR}/error.log\n\
    CustomLog ${APACHE_LOG_DIR}/access.log combined\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Set the working directory to web root
WORKDIR /var/www/html

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Create required directories for Drupal
RUN mkdir -p /var/www/html/web/sites/default/files \
    /var/www/html/web/sites/default/files/php \
    /var/www/html/web/sites/default/private \
    /tmp/drupal_cache

# Set proper permissions for cache directory
RUN chown -R www-data:www-data /tmp/drupal_cache && \
    chmod -R 775 /tmp/drupal_cache

# Create entrypoint script to fix permissions at runtime
RUN echo '#!/bin/bash\n\
echo "Starting Drupal container..."\n\
\n\
# Wait a moment for mounts to be ready\n\
sleep 2\n\
\n\
# Fix permissions on mounted volumes\n\
echo "Setting up permissions..."\n\
chown -R www-data:www-data /var/www/html/web/sites/default/files 2>/dev/null || true\n\
chmod -R 775 /var/www/html/web/sites/default/files 2>/dev/null || true\n\
\n\
# Create PHP storage directory if it does not exist\n\
mkdir -p /var/www/html/web/sites/default/files/php 2>/dev/null || true\n\
chown -R www-data:www-data /var/www/html/web/sites/default/files/php 2>/dev/null || true\n\
chmod -R 775 /var/www/html/web/sites/default/files/php 2>/dev/null || true\n\
\n\
echo "Starting Apache..."\n\
# Start Apache\n\
exec apache2-foreground\n\
' > /entrypoint.sh && chmod +x /entrypoint.sh

# Use custom entrypoint
ENTRYPOINT ["/entrypoint.sh"]

# Expose port 80
EXPOSE 80