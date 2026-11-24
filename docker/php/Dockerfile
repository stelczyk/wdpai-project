FROM php:8.3-fpm-alpine

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

RUN apk add --no-cache \
        zlib \
        libzip \
        libpng \
        libjpeg-turbo \
        postgresql-libs \
    && apk add --no-cache --virtual .build-deps \
        zlib-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        postgresql-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        opcache \
        zip \
        gd \
        bcmath \
        pgsql \
        pdo_pgsql \
    && apk del .build-deps

# Błędy tylko do logu kontenera (docker compose logs php), nigdy na stronę – stack trace zdradza ścieżki i SQL.
# Limity uploadu = client_max_body_size 8m w nginx; zdjęcie aktywności i tak ma limit w PhotoUploadService.
RUN { \
      echo 'display_errors = Off'; \
      echo 'display_startup_errors = Off'; \
      echo 'log_errors = On'; \
      echo 'expose_php = Off'; \
      echo 'memory_limit = 256M'; \
      echo 'upload_max_filesize = 8M'; \
      echo 'post_max_size = 8M'; \
      echo 'max_file_uploads = 5'; \
    } > /usr/local/etc/php/conf.d/dystans.ini

COPY . .
