#!/bin/sh
set -eu

# Cattr runs as UID/GID 10000 from the first instruction executed in the
# container. This script must therefore never require chown, setuid, mounts,
# capabilities, or writes to system-owned directories.

services=""
run_migrations=0
run_provision=0
role_all=0

# Optional Pebble layer that turns a crashed queue/reverb/scheduler into a
# container exit (see init/pebble/002-shutdown-policy.yaml). Kept outside the
# layers directory and installed per container start by apply_restart_policy.
restart_policy_layer=/opt/cattr/etc/pebble/002-shutdown-policy.yaml
pebble_layers_dir="${PEBBLE:-/opt/cattr/.pebble}/layers"

now() {
    date -u '+%Y-%m-%dT%H:%M:%SZ'
}

log() {
    printf '%s [cattr-entrypoint] %s\n' "$(now)" "$*" >&2
}

fail() {
    log "ERROR: $*"
    exit 1
}

if [ -z "${APP_KEY:-}" ]; then
    fail "APP_KEY must be set and non-empty"
fi

if [ -z "${REVERB_APP_SECRET:-}" ]; then
    fail "REVERB_APP_SECRET must be set and non-empty"
fi

run_step() {
    step="$1"
    shift

    started_at="$(date +%s)"
    log "START: ${step}"

    if "$@"; then
        finished_at="$(date +%s)"
        elapsed="$((finished_at - started_at))"
        log "DONE:  ${step} (${elapsed}s)"
        return 0
    else
        status="$?"
        finished_at="$(date +%s)"
        elapsed="$((finished_at - started_at))"
        log "FAIL:  ${step} (${elapsed}s, exit=${status})"
        return "$status"
    fi
}

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

prepare_directories() {
    mkdir -p \
        /opt/cattr/app/bootstrap/cache \
        /opt/cattr/app/storage/app/modules \
        /opt/cattr/app/storage/app/public \
        /opt/cattr/app/storage/app/screenshots \
        /opt/cattr/app/storage/framework/cache \
        /opt/cattr/app/storage/framework/sessions \
        /opt/cattr/app/storage/framework/views \
        /opt/cattr/app/storage/logs \
        /tmp/caddy/data \
        /tmp/caddy/config
}

cache_configuration() {
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

# CATTR_RESTART_POLICY:
#   restart  - Pebble restarts crashed services in place (001-cattr.yaml).
#   shutdown - a crashed queue/reverb/scheduler stops Pebble, so the container
#              exits and the orchestrator restarts it.
# Default: restart for the all-in-one `all` role, shutdown otherwise.
resolve_restart_policy() {
    if [ -z "${CATTR_RESTART_POLICY:-}" ]; then
        if [ "$role_all" -eq 1 ]; then
            CATTR_RESTART_POLICY=restart
        else
            CATTR_RESTART_POLICY=shutdown
        fi
    fi

    case "$CATTR_RESTART_POLICY" in
        restart|shutdown) ;;
        *)
            fail "invalid CATTR_RESTART_POLICY=${CATTR_RESTART_POLICY} (supported: restart, shutdown)"
            ;;
    esac
}

apply_restart_policy() {
    target="${pebble_layers_dir}/002-shutdown-policy.yaml"

    if [ "$CATTR_RESTART_POLICY" = "shutdown" ]; then
        # Skip the write when the layer is already in place, so a read-only
        # Pebble directory with a pre-installed layer keeps working.
        if ! cmp -s "$restart_policy_layer" "$target" 2>/dev/null; then
            cp "$restart_policy_layer" "$target"
        fi
    elif [ -e "$target" ]; then
        rm -f "$target"
    fi
}

log "Cattr container bootstrap starting"
log "Identity: uid=$(id -u) gid=$(id -g) user=$(id -un 2>/dev/null || printf unknown)"
log "Application root: /opt/cattr/app"
log "Environment: APP_ENV=${APP_ENV:-<unset>} DB_CONNECTION=${DB_CONNECTION:-<unset>} DB_HOST=${DB_HOST:-<unset>} DB_DATABASE=${DB_DATABASE:-<unset>}"

# Allow an explicit escape hatch for maintenance/debug commands without
# teaching the entrypoint about every possible executable.
if [ "${1:-}" = "exec" ]; then
    shift

    if [ "$#" -eq 0 ]; then
        log "ERROR: exec requires a command"
        exit 64
    fi

    log "Maintenance exec requested: $1"
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

log "Requested roles: $*"

for role in "$@"; do
    case "$role" in
        all)
            # Preserve the historical single-container behaviour.
            role_all=1
            run_migrations=1
            run_provision=1
            add_service caddy
            add_service reverb
            add_service queue
            add_service scheduler
            ;;
        web)
            # Caddy requires app in the Pebble plan. Reverb stays in the web
            # pod because the default Caddy config proxies WebSockets to
            # 127.0.0.1:8081.
            add_service caddy
            add_service reverb
            ;;
        app)
            add_service app
            ;;
        nginx)
            # Keep the historical role name as an alias for existing deploys.
            add_service caddy
            ;;
        caddy)
            add_service caddy
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
            log "ERROR: unknown Cattr role: ${role}"
            log "Supported roles: all web app caddy queue reverb scheduler migrate provision setup exec (nginx is a legacy alias for caddy)"
            exit 64
            ;;
    esac
done

resolve_restart_policy

log "Resolved services: ${services:-<none>}"
log "Bootstrap actions: config-cache=yes migrate=$([ "$run_migrations" -eq 1 ] && printf yes || printf no) provision=$([ "$run_provision" -eq 1 ] && printf yes || printf no) restart-policy=${CATTR_RESTART_POLICY}"

run_step "prepare writable runtime directories" prepare_directories
run_step "cache Laravel configuration" cache_configuration

if [ "$run_migrations" -eq 1 ]; then
    run_step "run database migrations and seed InitialSeeder" migrate_database
fi

if [ "$run_provision" -eq 1 ]; then
    run_step "provision Cattr administrator" provision_application
fi

if [ -z "$services" ]; then
    log "One-shot bootstrap completed successfully; no long-running services requested"
    exit 0
fi

run_step "apply restart policy ${CATTR_RESTART_POLICY}" apply_restart_policy

log "Starting Pebble as PID 1"
log "Pebble services requested: ${services}"
log "Restart policy: ${CATTR_RESTART_POLICY} ($([ "$CATTR_RESTART_POLICY" = "shutdown" ] && printf 'crashed queue/reverb/scheduler stop the container' || printf 'Pebble restarts crashed services in place'))"
log "Service output will be mirrored to container stdout/stderr"

# `pebble enter start` is designed for container entrypoints. exec replaces
# this shell so Pebble becomes PID 1 and remains the signal/reaping boundary.
# Service names come only from the controlled role table above.
# shellcheck disable=SC2086
exec /usr/bin/pebble enter --verbose start $services
