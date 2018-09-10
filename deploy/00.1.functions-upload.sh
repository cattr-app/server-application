#!/bin/bash

export ARTISAN="php ${PRODUCTION_SSH_PATH}/current/artisan"

# Finish script
function finish_script() {
    echo-blue "Finish script..."

    ssh-exec "Disable Maintenance" \
        "${ARTISAN} up" \
        "Can't disable maintenance" 253

    echo-green  "Remove local ssh key"
    rm ${LOCAL_SSH_KEY}
    check-last-code "Can't remove ssh key file" 254
}

# Helper function for command running
# Usage:
# cmd-exec "<message>" "<command>" "<error message>" [ <error_code>|skip [ "<fail_callback>" ] ]
function cmd-exec() {
    # Print command message
    echo-green "${1}"
    # Print command
    echo-cyan "${2}"
    # Execute command
    ${2}
    # Check if command successful
    check-last-code "${3}" "${4}" "${5}"
}

# Shortcut for ssh command
# Usage:
# ssh-exec "<message>" "<remote_command>" "<error message>" [ <error_code>|skip [ "<fail_callback>" ] ]
function ssh-exec() {
    CMD="cmd-exec \"$1\" \"ssh -o StrictHostKeyChecking=no -i ${LOCAL_SSH_KEY} -p ${PRODUCTION_SSH_PORT} ${PRODUCTION_SSH_USER}@${PRODUCTION_SSH_IP} bash -c \\\"${2}\\\"\" \"${3}\" \"${4}\""

    if [ "$5" != "" ] ; then
        ${CMD} "${5}"
    else
        ${CMD} "finish_script"
    fi
}

# Shortcut for ssh command with "restore previous build" fallback command
# Usage:
# ssh-exec "<message>" "<remote_command>" "<error message>" [ <error_code>|skip ]
function ssh-exec-restore() {
    ssh-exec "${1}" "${2}" "${3}" "${4}" "restore-previous-build"
}

# Shortcut for ssh command with command output only
# Usage:
# ssh-exec-silent "<remote_command>"
function ssh-exec-silent() {
    ssh -o StrictHostKeyChecking=no -i ${LOCAL_SSH_KEY} -p ${PRODUCTION_SSH_PORT} ${PRODUCTION_SSH_USER}@${PRODUCTION_SSH_IP} "echo \$(bash -c \"${1}\")"
}

# Shortcut for scp command
# Usage:
# ssh-cp <message> <local_file> <remote_path> <error message>" [ <error_code>|skip ]
function ssh-cp() {
    cmd-exec "$1" "scp -o StrictHostKeyChecking=no -i ${LOCAL_SSH_KEY} -P ${PRODUCTION_SSH_PORT} ${2} ${PRODUCTION_SSH_USER}@${PRODUCTION_SSH_IP}:${3}" "${4}" "${5}" "finish_script"
}

# Restore previous build
function restore-previous-build() {
    source deploy/03.3.restore-prevous.sh

    finish_script
}
