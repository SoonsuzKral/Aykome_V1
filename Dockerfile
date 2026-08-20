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
    libzip-dev \
    libaio1t64 \
    nginx \
    && (apt-get install -y libaio1 || true) \
    && MULARCH="$(gcc -print-multiarch)" \
    && [ -e "/usr/lib/$MULARCH/libaio.so.1t64" ] && ln -sf "/usr/lib/$MULARCH/libaio.so.1t64" "/usr/lib/$MULARCH/libaio.so.1" || true \
    && rm -rf /var/lib/apt/lists/*

# Download and install Oracle Instant Client (arch-aware: x64 / arm64)
RUN set -ex; \
    ARCH="$(uname -m)"; \
    if [ "$ARCH" = "aarch64" ] || [ "$ARCH" = "arm64" ]; then \
      IC_ARCH="linux.arm64"; IC_VERSION="19.32.0.0.0"; IC_DIR="1932000"; IC_SUFFIX="dbru"; \
    else \
      IC_ARCH="linux.x64"; IC_VERSION="21.14.0.0.0"; IC_DIR="2114000"; IC_SUFFIX="dbru"; \
    fi; \
    curl -sL "https://download.oracle.com/otn_software/linux/instantclient/${IC_DIR}/instantclient-basiclite-${IC_ARCH}-${IC_VERSION}${IC_SUFFIX}.zip" -o /tmp/instantclient.zip; \
    unzip -q /tmp/instantclient.zip -d /opt/oracle; \
    rm -f /tmp/instantclient.zip; \
    curl -sL "https://download.oracle.com/otn_software/linux/instantclient/${IC_DIR}/instantclient-sdk-${IC_ARCH}-${IC_VERSION}${IC_SUFFIX}.zip" -o /tmp/instantclient-sdk.zip; \
    unzip -qo /tmp/instantclient-sdk.zip -d /opt/oracle; \
    rm -f /tmp/instantclient-sdk.zip; \
    cd /opt/oracle; \
    mv "$(ls -d instantclient_*)" instantclient; \
    echo /opt/oracle/instantclient > /etc/ld.so.conf.d/oracle-instantclient.conf; \
    ldconfig

# Set Oracle environment
ENV ORACLE_HOME=/opt/oracle/instantclient
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient
ENV TNS_ADMIN=/opt/oracle/instantclient/network/admin
ENV PATH=$PATH:/opt/oracle/instantclient

# Create TNS admin directory
RUN mkdir -p $TNS_ADMIN

# Install PHP extensions (oci8 via PECL with instantclient)
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd sockets zip \
    && printf 'instantclient,/opt/oracle/instantclient\n' | pecl install oci8 \
    && docker-php-ext-enable oci8

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy application files
COPY . .

# Nginx configuration
COPY nginx.conf /etc/nginx/nginx.conf

# Install PHP dependencies
RUN composer install --no-dev --no-scripts --optimize-autoloader --no-interaction --ignore-platform-req=ext-oci8

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Expose port 80
EXPOSE 80

# Start PHP-FPM and Nginx
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
