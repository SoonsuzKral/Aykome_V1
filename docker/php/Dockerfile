FROM php:8.2-fpm-bookworm

RUN apt-get update && apt-get install -y \
    libaio1 unzip curl libpq-dev libpng-dev libzip-dev \
    && docker-php-ext-install pdo_pgsql gd zip opcache \
    && rm -rf /var/lib/apt/lists/*

ENV IC_VERSION=19.23.0.0.0
ENV IC_DIR=1923000

RUN curl -sL -o /tmp/basic.zip \
    "https://download.oracle.com/otn_software/linux/instantclient/${IC_DIR}/instantclient-basic-linux.x64-${IC_VERSION}dbru.zip" && \
    curl -sL -o /tmp/sdk.zip \
    "https://download.oracle.com/otn_software/linux/instantclient/${IC_DIR}/instantclient-sdk-linux.x64-${IC_VERSION}dbru.zip" && \
    mkdir -p /opt/oracle && \
    unzip -q /tmp/basic.zip -d /opt/oracle && \
    unzip -qo /tmp/sdk.zip -d /opt/oracle && \
    cd /opt/oracle && \
    mv $(ls -d instantclient_*) instantclient && \
    rm -f /tmp/basic.zip /tmp/sdk.zip && \
    echo /opt/oracle/instantclient > /etc/ld.so.conf.d/oracle-instantclient.conf && \
    ldconfig

ENV ORACLE_HOME=/opt/oracle/instantclient
ENV LD_LIBRARY_PATH=/opt/oracle/instantclient
ENV TNS_ADMIN=/opt/oracle/instantclient/network/admin

RUN docker-php-ext-configure oci8 --with-oci8=instantclient,/opt/oracle/instantclient && \
    docker-php-ext-install oci8 pdo_mysql exif pcntl

RUN pecl install redis && docker-php-ext-enable redis

# OpCache config
RUN docker-php-ext-enable opcache
RUN { \
    echo 'opcache.memory_consumption=128'; \
    echo 'opcache.interned_strings_buffer=8'; \
    echo 'opcache.max_accelerated_files=10000'; \
    echo 'opcache.revalidate_freq=2'; \
    echo 'opcache.fast_shutdown=1'; \
    echo 'opcache.enable_cli=1'; \
} > /usr/local/etc/php/conf.d/opcache.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app
