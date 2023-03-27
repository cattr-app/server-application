# syntax=docker/dockerfile:1-labs
FROM registry.git.amazingcat.net/cattr/core/wolfi-os-image/cattr-dev AS builder

ARG MODULES="cattr/gitlab_integration-module cattr/redmine_integration-module"
ARG APP_ENV=production
ENV APP_ENV $APP_ENV
ENV YARN_ENABLE_GLOBAL_CACHE=true

COPY --chown=root:root .root-fs/php /php

WORKDIR /app

COPY --chown=www:www . /app

USER www:www

RUN set -x && \
    composer require -n --no-ansi --no-install --no-update --no-audit $MODULES && \
    composer update -n --no-autoloader --no-install --no-ansi $MODULES && \
    composer install -n --no-dev --no-cache --no-ansi --no-autoloader --no-dev && \
    composer dump-autoload -n --optimize --apcu --classmap-authoritative

RUN set -x && \
    php artisan route:cache && \
    php artisan view:cache && \
    php artisan event:cache && \
    php artisan config:cache && \
    php artisan storage:link

RUN set -x && \
    yarn && \
    yarn prod && \
    rm -rf node_modules

FROM registry.git.amazingcat.net/cattr/core/wolfi-os-image/cattr AS runtime

ARG SENTRY_DSN
ARG APP_VERSION
ARG APP_ENV=production
ARG APP_KEY="base64:PU/8YRKoMdsPiuzqTpFDpFX1H8Af74nmCQNFwnHPFwY="
ENV IMAGE_VERSION=5.0.0
ENV DB_CONNECTION=mysql
ENV DB_HOST=db
ENV DB_USERNAME=root
ENV DB_PASSWORD=password
ENV LOG_CHANNEL=stderr
ENV APP_VERSION $APP_VERSION
ENV SENTRY_DSN $SENTRY_DSN
ENV APP_ENV $APP_ENV
ENV APP_KEY $APP_KEY

COPY --from=builder /app /app

COPY --chown=root:root .root-fs /

VOLUME /app/storage

#HEALTHCHECK --interval=5m --timeout=10s \
#  CMD wget --spider -q "http://127.0.0.1:8090/status"

EXPOSE 80
