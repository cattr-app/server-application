#!/bin/sh
set -eu

# Cattr runs as UID/GID 10000 from the first instruction executed in the
# container. This script must therefore never require chown, setuid, mounts,
# capabilities, or writes to system-owned directories.

services=""
run_migrations=0
run_provision=0

add_service() {
    service="$1"

    case " ${services} " in
        *" ${service} "*) ;;
        *)
            if [ -n "${services}" ]; then
                services="${services} ${service}"
            else
                services="${service}"
            fi
            ;;
    esac
}

prepare_runtime() {
    mkdir -p \
        /opt/cattr/app/bootstrap/cache \
        /opt/cattr/app/storage/app/modules \
        /opt/cattr/app/storage/app/public \
        /opt/cattr/app/storage/app/screenshots \
        /opt/cattr/app/storage/framework/cache \
        /opt/cattr/app/storage/framework/sessions \
        /opt/cattr/app/storage/framework/views \
        /opt/cattr/app/storage/logs \
        /tmp/nginx/client_body \
        /tmp/nginx/fastcgi \
        /tmp/nginx/proxy \
        /tmp/nginx/scgi \
        /tmp/nginx/uwsgi

    php /opt/cattr/app/artisan config:cache --no-interaction
}

migrate_database() {
    php /opt/cattr/app/artisan migrate \
        --force \
        --seed \
        --seeder=InitialSeeder \
        --no-interaction
}

provision_application() {
    php /opt/cattr/app/artisan cattr:make:admin --no-interaction
}

# Allow an explicit escape hatch for maintenance/debug commands without
# teaching the entrypoint about every possible executable.
if [ "${1:-}" = "exec" ]; then
    shift

    if [ "$#" -eq 0 ]; then
        echo "cattr-entrypoint: exec requires a command" >&2
        exit 64
    fi

    exec "$@"
fi

# CLI arguments are preferred for Kubernetes `args:`. CATTR_ROLES is useful
# for Docker/Compose when no arguments are supplied.
if [ "$#" -eq 0 ]; then
    # Word splitting is intentional: CATTR_ROLES is a whitespace-separated
    # list from a trusted deployment configuration.
    # shellcheck disable=SC2086
    set -- ${CATTR_ROLES:-all}
fi

for role in "$@"; do
    case "$role" in
        all)
            # Preserve the historical single-container behaviour.
            run_migrations=1
            run_provision=1
            add_service nginx
            add_service reverb
            add_service queue
            add_service scheduler
            ;;
        web)
            # nginx requires app in the Pebble plan. Reverb stays in the web
            # pod because the default nginx config proxies WebSockets to
            # 127.0.0.1:8081.
            add_service nginx
            add_service reverb
            ;;
        app)
            add_service app
            ;;
        nginx)
            add_service nginx
            ;;
        queue)
            add_service queue
            ;;
        reverb)
            add_service reverb
            ;;
        scheduler)
            add_service scheduler
            ;;
        migrate)
            run_migrations=1
            ;;
        provision)
            run_provision=1
            ;;
        setup)
            run_migrations=1
            run_provision=1
            ;;
        *)
            echo "Unknown Cattr role: ${role}" >&2
            echo "Supported roles: all web app nginx queue reverb scheduler migrate provision setup exec" >&2
            exit 64
            ;;
    esac
done

prepare_runtime

if [ "$run_migrations" -eq 1 ]; then
    migrate_database
fi

if [ "$run_provision" -eq 1 ]; then
    provision_application
fi

if [ -z "$services" ]; then
    # One-shot invocation such as migrate, provision, or setup.
    exit 0
fi

# `pebble enter start` is designed for container entrypoints. exec replaces
# this shell so Pebble becomes PID 1 and remains the signal/reaping boundary.
# Service names come only from the controlled role table above.
# shellcheck disable=SC2086
exec /usr/bin/pebble enter --verbose start $services
