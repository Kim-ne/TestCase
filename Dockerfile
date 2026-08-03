# =================== COMPOSER ===================
FROM composer:2.8 AS composer-deps
WORKDIR /var/www/html

RUN apk add --no-cache \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip intl
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts --optimize-autoloader

# =================== PRODUCTION ===================
FROM php:8.3-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
    bash \
    curl \
    git \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    libxml2-dev \
    icu-dev \
    zip \
    libzip-dev \
    ca-certificates \
    && update-ca-certificates \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apk del --no-cache git \
    && echo "curl.cainfo = /etc/ssl/certs/ca-certificates.crt" > /usr/local/etc/php/conf.d/docker-php-curl-ca.ini \
    && echo "openssl.cafile = /etc/ssl/certs/ca-certificates.crt" >> /usr/local/etc/php/conf.d/docker-php-curl-ca.ini

COPY --from=composer-deps /usr/bin/composer /usr/bin/composer
COPY --from=composer-deps /var/www/html/vendor /var/www/html/vendor
COPY . /var/www/html

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

# ENTRYPOINT ["sh", "-c", "php artisan key:generate --force >/dev/null 2>&1 || true;\
#              php artisan config:cache --no-interaction;\
#              php artisan route:cache --no-interaction;\
#              php artisan view:cache --no-interaction;\
#               php-fpm"]

ENTRYPOINT ["sh", "-c", "php artisan key:generate --force >/dev/null 2>&1 || true;\
              php-fpm"]
