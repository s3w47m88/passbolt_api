# Use PHP Apache base image
FROM php:8.2-apache

# Install required PHP extensions and tools
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libicu-dev \
    libldap2-dev \
    libgd-dev \
    libpng-dev \
    libjpeg-dev \
    libxml2-dev \
    libxslt1-dev \
    libzip-dev \
    unzip \
    git \
    gnupg \
    curl \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        intl \
        gd \
        opcache \
        xml \
        xsl \
        zip \
    && pecl install gnupg \
    && docker-php-ext-enable gnupg \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Clone Passbolt
RUN git clone https://github.com/passbolt/passbolt_api.git /var/www/passbolt --depth 1

# Set working directory
WORKDIR /var/www/passbolt

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Configure Apache
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/passbolt/webroot\n\
    <Directory /var/www/passbolt/webroot>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf \
    && a2enmod rewrite

# Set permissions
RUN chown -R www-data:www-data /var/www/passbolt

# Create startup script
RUN echo '#!/bin/bash\n\
echo "Starting Passbolt..."\n\
cd /var/www/passbolt\n\
\n\
# Run migrations\n\
su -s /bin/bash www-data -c "./bin/cake passbolt migrate --no-lock" || true\n\
\n\
# Install if needed\n\
su -s /bin/bash www-data -c "./bin/cake passbolt install --no-admin" || true\n\
\n\
# Start Apache\n\
apache2-foreground' > /start.sh && chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]