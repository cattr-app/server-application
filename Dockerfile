# syntax=docker/dockerfile:1-labs
FROM registry.git.amazingcat.net/cattr/core/wolfi-os-image/cattr-dev AS builder

ARG MODULES="cattr/gitlab_integration-module cattr/redmine_integration-module"

COPY php.ini /php/php.ini

WORKDIR /app

RUN echo '* * * * * php /app/artisan schedule:run' > /crontab

USER www:www

COPY --chown=www:www . /app

RUN set -x && \
    composer install -n --no-dev --no-cache --no-ansi -o && \
    composer require -n --no-ansi $MODULES && \
    composer dump-autoload -n --optimize && \
    php artisan storage:link && \
    yarn && \
    yarn prod

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
ENV APP_VERSION $APP_VERSION
ENV SENTRY_DSN $SENTRY_DSN
ENV APP_ENV $APP_ENV
ENV APP_KEY $APP_KEY

COPY --from=builder /app /app

COPY php.ini /php/php.ini

VOLUME /app/storage
VOLUME /app/bootstrap/cache

#HEALTHCHECK --interval=5m --timeout=10s \
#  CMD wget --spider -q "http://127.0.0.1:8090/status"

EXPOSE 8090
