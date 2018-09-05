#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." >/dev/null && pwd )"
cd ${DIR}

source deploy/00.functions.sh

echo-green "Set additional variables"

[ "${PRODUCTION_SSH_PATH}" == "" ] && export PRODUCTION_SSH_PATH="/opt/amazing-time"
[ "${PRODUCTION_SSH_PORT}" == "" ] && export PRODUCTION_SSH_PORT="22"
[ "${PRODUCTION_STORAGE_PATH}" == "" ] && export PRODUCTION_STORAGE_PATH="${PRODUCTION_SSH_PATH}/storage"


export LOCAL_SSH_KEY="./.id_rsa.at.private"
export BUILD_ID="$(date +"%F_%H-%M-%S")__${CI_COMMIT_REF_SLUG}"

echo-green "Create local ssh key file"

echo "${PRODUCTION_SSH_PASSWORD}" > ${LOCAL_SSH_KEY}
chmod 600 ${LOCAL_SSH_KEY}

# Finish script
function finish_script() {
    echo-blue "Finish script..."
    echo-green  "Remove local ssh key"
    rm ${LOCAL_SSH_KEY}
    check-last-code "Can't remove ssh key file" 254
}

# Helper function for command running
function cmd-exec() {
    # Print command message
    echo-green "${1}"
    # Print command
    echo-cyan "${2}"
    # Execute command
    ${2}
    # Check if command successful
    check-last-code "${3}" "${4}" "finish_script"
}

function ssh-exec-silent() {
    ssh -o StrictHostKeyChecking=no -i ${LOCAL_SSH_KEY} -p ${PRODUCTION_SSH_PORT} ${PRODUCTION_SSH_USER}@${PRODUCTION_SSH_IP} "echo \$(bash -c \"${1}\")"
}

# Shortcut for ssh command
function ssh-exec() {
    cmd-exec "$1" "ssh -o StrictHostKeyChecking=no -i ${LOCAL_SSH_KEY} -p ${PRODUCTION_SSH_PORT} ${PRODUCTION_SSH_USER}@${PRODUCTION_SSH_IP} bash -c \"${2}\"" "${3}" "${4}"
}

# Shortcut for scp command
function ssh-cp() {
    cmd-exec "$1" "scp -o StrictHostKeyChecking=no -i ${LOCAL_SSH_KEY} -P ${PRODUCTION_SSH_PORT} ${2} ${PRODUCTION_SSH_USER}@${PRODUCTION_SSH_IP}:${3}" "${4}" "${5}"
}

ssh-cp "Copy build package to remote" \
    "build-pack.tar.gz" \
    "${PRODUCTION_SSH_PATH}" \
    "Can't upload archive to host" 1

ssh-exec "Extract archive" \
    "cd ${PRODUCTION_SSH_PATH} ; tar -xf build-pack.tar.gz && rm build-pack.tar.gz" \
    "Can't extract archive" 2

ssh-exec "Move build to builds folder with version identification" \
    "mv ${PRODUCTION_SSH_PATH}/.build-pack ${PRODUCTION_SSH_PATH}/builds/${BUILD_ID}" \
    "Can't move build to builds folder" 3

ssh-exec "Unlink current build" \
    "if [ -e ${PRODUCTION_SSH_PATH}/current ]; then rm ${PRODUCTION_SSH_PATH}/current ; fi" \
    "Can't unlink current build" 4

ssh-exec "Set new build as current" \
    "ln -s ${PRODUCTION_SSH_PATH}/builds/${BUILD_ID} ${PRODUCTION_SSH_PATH}/current" \
    "Can't create link for new build" 5

ssh-exec "Link storage to new build" \
    "ln -s ${PRODUCTION_STORAGE_PATH} ${PRODUCTION_SSH_PATH}/current/storage" \
    "Can't create storage link" 6

ssh-exec "Finish configuration of new build" \
    "ln -s ${PRODUCTION_SSH_PATH}/current/storage/app/uploads ${PRODUCTION_SSH_PATH}/current/public/" \
    "Can't create link for public uploads folder" 7

ssh-exec "Link .env file to new build" \
    "ln -s ${PRODUCTION_SSH_PATH}/.env ${PRODUCTION_SSH_PATH}/current/.env" \
    "Can't create link for .env file" 8

ssh-exec "Run migrations" \
    "php ${PRODUCTION_SSH_PATH}/current/artisan migrate --force" \
    "Database Migration failed" 9

PREV_BUILD_ID=$(ssh-exec-silent "cat ${PRODUCTION_SSH_PATH}/.build_id")
if [ "${PREV_BUILD_ID}" != "" ] ; then
    ssh-exec "Archive previous build: ${PREV_BUILD_ID}" \
        "cd ${PRODUCTION_SSH_PATH}/builds/ ; if [ -d ${PREV_BUILD_ID} ] ; then tar -czf ${PREV_BUILD_ID}.tar.gz ${PREV_BUILD_ID}/ && rm -rf ${PREV_BUILD_ID}/ ; fi" \
        "Can't create archive of previous version" 10

    ssh-exec "Save previous build id to history file" \
        "cat ${PRODUCTION_SSH_PATH}/.build_id >> ${PRODUCTION_SSH_PATH}/.build_history" \
        "Can't save current build to history file" 11
fi

ssh-exec "Store new build id" \
    "echo ${BUILD_ID} > ${PRODUCTION_SSH_PATH}/.build_id" \
    "Can't store current build" 12

finish_script
