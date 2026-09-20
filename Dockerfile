# syntax=docker/dockerfile:1-labs
ARG BUILDER_BASE_IMAGE=ghcr.io/cattr-app/server-runtime
ARG BUILDER_BASE_IMAGE_TAG=builder-latest

ARG RUNTIME_BASE_IMAGE=ghcr.io/cattr-app/server-runtime
ARG RUNTIME_BASE_IMAGE_TAG=runtime-latest

FROM ${BUILDER_BASE_IMAGE}:${BUILDER_BASE_IMAGE_TAG} AS builder

ARG SENTRY_DSN
ARG APP_VERSION
ARG APP_ENV=production
ARG REVERB_SCHEME=http
ARG REVERB_PORT=8081

ENV IMAGE_VERSION=5.0.0 \
    APP_VERSION=${APP_VERSION} \
    SENTRY_DSN=${SENTRY_DSN} \
    APP_ENV=${APP_ENV} \
    REVERB_APP_KEY=cattr \
    REVERB_HOST=127.0.0.1 \
    REVERB_SCHEME=${REVERB_SCHEME} \
    REVERB_PORT=${REVERB_PORT}

# builder-latest is itself configured with run-as: 10000. Keep this explicit
# so a future base-image change cannot silently make application build steps root.
USER 10000:10000
WORKDIR /opt/cattr/app

COPY --chown=10000:10000 . /opt/cattr/app

RUN set -eux; \
    composer install \
        --no-interaction \
        --no-dev \
        --no-cache \
        --no-ansi \
        --no-autoloader; \
    composer dump-autoload \
        --no-interaction \
        --optimize \
        --apcu \
        --classmap-authoritative

RUN set -eux; \
    pnpm install --frozen-lockfile; \
    pnpm prod; \
    rm -rf node_modules

RUN set -eux; \
    php artisan storage:link; \
    rm -rf build configs init

FROM ${RUNTIME_BASE_IMAGE}:${RUNTIME_BASE_IMAGE_TAG} AS runtime

ARG SENTRY_DSN
ARG APP_VERSION
ARG APP_ENV=production
ARG APP_KEY="base64:PU/8YRKoMdsPiuzqTpFDpFX1H8Af74nmCQNFwnHPFwY="
ARG REVERB_APP_SECRET="secret"
ARG REVERB_SCHEME=http
ARG REVERB_PORT=8081

ENV IMAGE_VERSION=5.0.0 \
    APP_VERSION=${APP_VERSION} \
    SENTRY_DSN=${SENTRY_DSN} \
    APP_ENV=${APP_ENV} \
    APP_KEY=${APP_KEY} \
    REVERB_APP_KEY=cattr \
    REVERB_HOST=127.0.0.1 \
    REVERB_SCHEME=${REVERB_SCHEME} \
    REVERB_PORT=${REVERB_PORT} \
    DB_CONNECTION=mysql \
    DB_HOST=db \
    DB_USERNAME=root \
    DB_PASSWORD=password \
    LOG_CHANNEL=stderr \
    PEBBLE=/opt/cattr/.pebble

WORKDIR /opt/cattr/app

# Keep application code immutable to the runtime UID. Only Laravel's mutable
# directories are overlaid with UID/GID 10000 ownership below.
COPY --from=builder --chown=0:0 /opt/cattr/app/ /opt/cattr/app/
COPY --from=builder --chown=10000:10000 /opt/cattr/app/bootstrap/cache/ /opt/cattr/app/bootstrap/cache/
COPY --from=builder --chown=10000:10000 /opt/cattr/app/storage/ /opt/cattr/app/storage/

# Cattr-specific configuration lives under /opt/cattr/etc. Program-specific
# system configuration remains in the conventional /etc/php and /etc/nginx.
COPY --chown=0:0 --chmod=0644 configs/supercronic/crontab /opt/cattr/crontab
COPY --chown=0:0 --chmod=0644 configs/php/conf.d/99-cattr.ini /etc/php/conf.d/99-cattr.ini
COPY --chown=0:0 --chmod=0644 configs/nginx/nginx.conf /etc/nginx/nginx.conf
COPY --chown=0:0 --chmod=0644 configs/nginx/conf.d/app.conf /etc/nginx/conf.d/app.conf

# Pebble needs a writable per-user state directory; the base image creates it
# as UID/GID 10000. The layer itself is application data copied at image build.
COPY --chown=10000:10000 --chmod=0644 init/pebble/001-cattr.yaml /opt/cattr/.pebble/layers/001-cattr.yaml
COPY --chown=0:0 --chmod=0755 init/container-entrypoint.sh /opt/cattr/entrypoint

# Hard guarantee for the final image: PID 1 and every supervised process start
# as the unprivileged Cattr user. No runtime privilege drop is required.
USER 10000:10000

VOLUME ["/opt/cattr/app/storage"]

# 8080 nginx, 8081 Reverb, 8090 Octane. The latter two matter when a role is
# deployed without nginx in front of it.
EXPOSE 8080 8081 8090

STOPSIGNAL SIGTERM
ENTRYPOINT ["/opt/cattr/entrypoint"]
CMD ["all"]
