FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpng-dev libjpeg62-turbo-dev libfreetype6-dev libwebp-dev \
        libzip-dev libicu-dev libxml2-dev libcurl4-openssl-dev \
        default-mysql-client unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" gd mysqli pdo_mysql zip intl opcache soap exif

RUN a2enmod rewrite headers

# OpenCart 3 lives in public_html/
ENV APACHE_DOCUMENT_ROOT=/home/forge/ewmarket.sa/public_html
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && printf '<Directory %s>\n  AllowOverride All\n  Require all granted\n</Directory>\n' "$APACHE_DOCUMENT_ROOT" \
       > /etc/apache2/conf-available/opencart.conf \
    && a2enconf opencart

COPY php.ini /usr/local/etc/php/conf.d/zz-opencart.ini

WORKDIR /home/forge/ewmarket.sa
