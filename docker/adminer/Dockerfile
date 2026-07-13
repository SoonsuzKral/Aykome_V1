FROM php:8.2-apache-bookworm

RUN apt-get update && apt-get install -y \
    libaio1 unzip curl \
    && rm -rf /var/lib/apt/lists/*

ENV IC_VERSION=19.23.0.0.0
ENV IC_DIR=1923000

RUN curl -sL -o /tmp/basic.zip \
    "https://download.oracle.com/otn_software/linux/instantclient/${IC_DIR}/instantclient-basic-linux.arm64-${IC_VERSION}dbru.zip" && \
    curl -sL -o /tmp/sdk.zip \
    "https://download.oracle.com/otn_software/linux/instantclient/${IC_DIR}/instantclient-sdk-linux.arm64-${IC_VERSION}dbru.zip" && \
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
    docker-php-ext-install oci8 pdo pdo_mysql

RUN curl -sL -o /var/www/html/adminer.php "https://github.com/vrana/adminer/releases/download/v4.8.1/adminer-4.8.1.php"

COPY index.php /var/www/html/index.php

RUN chmod 755 /var/www/html/*.php && \
    sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf && \
    sed -i 's/:80>/:8080>/' /etc/apache2/sites-available/000-default.conf

EXPOSE 8080

CMD ["apache2-foreground"]
