FROM php:8.1-cli AS backend

COPY . /app

WORKDIR /app

RUN apt-get update && \
    apt-get install -y --no-install-recommends  \
        supervisor \
        libcurl4-openssl-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev  \
        libzip4  \
        libzip-dev  \
        procps  \
        git \
        zip && \
    apt-get clean && \
    rm -rf /etc/supervisor/

RUN printf "\n\n\nyes\nyes\nyes\n" | pecl install swoole-4.8.9 && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd zip mysqli pcntl pdo pdo_mysql && \
    docker-php-ext-enable swoole gd zip pdo pdo_mysql pcntl

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

COPY php.ini /usr/local/etc/php/php.ini

RUN cp .env.docker .env && \
    php -d memory_limit=8G /usr/local/bin/composer require -o $(cat .modules.production | tr '\012' ' ') && \
    php /app/artisan storage:link

RUN chown -R www-data:www-data /app

VOLUME /app/storage

ARG APP_VERSION
ARG APP_ENV=production
ENV IMAGE_VERSION=4.0.0
ENV APP_VERSION $APP_VERSION
ENV APP_ENV $APP_ENV

ENTRYPOINT /app/entrypoint.sh

EXPOSE 8090
