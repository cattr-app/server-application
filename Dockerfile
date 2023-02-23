FROM composer:2.3.5 as composer

FROM php:8.1-alpine AS runtime

ARG SENTRY_DSN
ARG APP_VERSION
ARG APP_ENV=production
ARG MODULES="cattr/gitlab_integration-module cattr/redmine_integration-module"
ENV IMAGE_VERSION=4.1.0
ENV APP_VERSION $APP_VERSION
ENV SENTRY_DSN $SENTRY_DSN
ENV APP_ENV $APP_ENV

RUN set -x && \
    apk add --no-cache icu-libs zlib libpng libzip libjpeg libcurl bash && \
    apk add --no-cache --virtual .build-deps \
            autoconf \
            openssl \
            make \
            g++  \
            zlib-dev \
            libpng-dev \
            libzip-dev \
            libjpeg-turbo-dev  \
            icu-dev \
            curl-dev && \
    docker-php-ext-configure gd --with-jpeg && \
    CFLAGS="$CFLAGS -D_GNU_SOURCE" docker-php-ext-install -j$(nproc) \
        gd \
        zip \
        intl \
        pcntl \
        pdo_mysql && \
    printf "\n\n\nyes\nyes\nyes\n" | pecl install swoole-4.8.9 && \
    docker-php-ext-enable swoole && \
    wget -q "https://github.com/aptible/supercronic/releases/download/v0.1.12/supercronic-linux-amd64" \
         -O /usr/bin/supercronic && \
    chmod +x /usr/bin/supercronic && \
    docker-php-source delete && \
    apk del .build-deps && \
    rm -R /tmp/pear && \
    mkdir /app && \
    echo '* * * * * php /app/artisan schedule:run' > /app/crontab && \
    chown -R www-data:www-data /app

USER www-data:www-data

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

COPY php.ini /usr/local/etc/php/php.ini

WORKDIR /app

COPY --chown=www-data:www-data ./composer.* /app/

RUN composer require -n --no-install --no-ansi $MODULES && \
    composer install -n --no-dev --no-cache --no-ansi --no-autoloader

COPY --chown=www-data:www-data . /app

RUN set -x && \
    cp .env.docker storage/.env && \
    composer dump-autoload -n --optimize && \
    php artisan storage:link

VOLUME /app/storage
VOLUME /app/Modules

ENTRYPOINT ["/app/start"]

HEALTHCHECK --interval=5m --timeout=10s \
  CMD wget --spider -q "http://127.0.0.1:8090/status"

EXPOSE 8090
