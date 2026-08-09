FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    openssl \
    autoconf \
    gcc \
    g++ \
    make \
    libnsl2 \
    && (apt-get install -y libaio1 || apt-get install -y libaio1t64) \
    && rm -rf /var/lib/apt/lists/*

# Download and install Oracle Instant Client 21c
RUN curl -sL https://download.oracle.com/otn_software/linux/instantclient/2114000/instantclient-basiclite-linux.x64-21.14.0.0.0dbru.zip -o /tmp/instantclient.zip \
    && unzip -q /tmp/instantclient.zip -d /opt/oracle \
    && rm /tmp/instantclient.zip \
    && ln -s /opt/oracle/instantclient_21_14 /opt/oracle/instantclient

# Set Oracle environment
ENV ORACLE_HOME=/opt/oracle/instantclient
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient
ENV TNS_ADMIN=/opt/oracle/instantclient/network/admin
ENV PATH=$PATH:/opt/oracle/instantclient

# Create TNS admin directory
RUN mkdir -p $TNS_ADMIN

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd sockets oci8

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=ext-oci8

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Expose port 80
EXPOSE 80

# Start PHP-FPM and Nginx
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
