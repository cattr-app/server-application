#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." >/dev/null && pwd )"
cd ${DIR}

source deploy/00.0.functions.sh

###############
### Prepare ###
###############

echo-green "Set additional variables"

[ "${DEPLOY_SSH_PATH}" == "" ] && export DEPLOY_SSH_PATH="/opt/amazing-time"
[ "${DEPLOY_SSH_PORT}" == "" ] && export DEPLOY_SSH_PORT="22"
[ "${DEPLOY_STORAGE_PATH}" == "" ] && export DEPLOY_STORAGE_PATH="${DEPLOY_SSH_PATH}/storage"

################################
### Check required variables ###
################################

if [ "${DEPLOY_SSH_USER}" == "" ] ; then
    echo-red "SSH User doesn't passed" ; exit 0 ;
fi

if [ "${DEPLOY_SSH_IP}" == "" ] ; then
    echo-red "SSH Password doesn't passed"
    exit 0
fi

if [ "${DEPLOY_SSH_PASSWORD}" == "" ] ; then
    echo-red "SSH Key doesn't passed"
    exit 0
fi

####################
### Start script ###
####################

export LOCAL_SSH_KEY="./.id_rsa.at.private"
export BUILD_ID="$(date +"%F_%H-%M-%S")__${CI_COMMIT_REF_SLUG}"

echo-green "Create local ssh key file"

echo "${DEPLOY_SSH_PASSWORD}" > ${LOCAL_SSH_KEY}
chmod 600 ${LOCAL_SSH_KEY}

source deploy/00.1.functions-upload.sh

#####################################
### Upload build to remote server ###
#####################################

ssh-cp "Copy build package to remote" \
    "build-pack.tar.gz" \
    "${DEPLOY_SSH_PATH}" \
    "Can't upload archive to host" 1

ssh-exec "Extract archive" \
    "cd ${DEPLOY_SSH_PATH} ; tar -xf build-pack.tar.gz && rm build-pack.tar.gz" \
    "Can't extract archive" 2

ssh-exec "Move build to builds folder with version identification" \
    "mv ${DEPLOY_SSH_PATH}/.build-pack ${DEPLOY_SSH_PATH}/builds/${BUILD_ID}" \
    "Can't move build to builds folder" 3

##############################
### Version history update ###
##############################

export PREV_BUILD_ID=$(ssh-exec-silent "cat ${DEPLOY_SSH_PATH}/.build_id")
export PREV_BUILD_BACKUP_ID="backup_${PREV_BUILD_ID}"

ssh-exec "Store new build id" \
    "echo ${BUILD_ID} > ${DEPLOY_SSH_PATH}/.build_id" \
    "Can't store current build" 4

if [ "${PREV_BUILD_ID}" != "" ] ; then
    ssh-exec "Save previous build id to history file" \
        "cat ${DEPLOY_SSH_PATH}/.build_id >> ${DEPLOY_SSH_PATH}/.build_history" \
        "Can't save current build to history file" 5
fi

#################################
### Set new build as current ####
#################################

ssh-exec "Enable maintenance" \
    "${ARTISAN} down --message=\"Application Upgrade\"" \
    "Can't set maintenance mode" 6

ssh-exec "Create database backup" \
    "${ARTISAN} backup:mysql-dump ${PREV_BUILD_BACKUP_ID}" \
    "Can't create backup" 7

ssh-exec "Unlink current build" \
    "if [ -e ${DEPLOY_SSH_PATH}/current ]; then rm ${DEPLOY_SSH_PATH}/current ; fi" \
    "Can't unlink current build" 8

ssh-exec-restore "Set new build as current" \
    "ln -s ${DEPLOY_SSH_PATH}/builds/${BUILD_ID} ${DEPLOY_SSH_PATH}/current" \
    "Can't create link for new build" 9

ssh-exec-restore "Link storage to new build" \
    "ln -s ${DEPLOY_STORAGE_PATH} ${DEPLOY_SSH_PATH}/current/storage" \
    "Can't create storage link" 10

ssh-exec-restore "Finish configuration of new build" \
    "ln -s ${DEPLOY_SSH_PATH}/current/storage/app/uploads ${DEPLOY_SSH_PATH}/current/public/" \
    "Can't create link for public uploads folder" 11

ssh-exec-restore "Link .env file to new build" \
    "ln -s ${DEPLOY_SSH_PATH}/.env ${DEPLOY_SSH_PATH}/current/.env" \
    "Can't create link for .env file" 12

ssh-exec-restore "Run migrations" \
    "${ARTISAN} migrate --force" \
    "Database Migration failed" 13

##############################
### Archive previous build ###
##############################

if [ "${PREV_BUILD_ID}" != "" ] ; then
    ssh-exec "Archive previous build: ${PREV_BUILD_ID}" \
        "cd ${DEPLOY_SSH_PATH}/builds/ ; if [ -d ${PREV_BUILD_ID} ] ; then tar -czf ${PREV_BUILD_ID}.tar.gz ${PREV_BUILD_ID}/ && rm -rf ${PREV_BUILD_ID}/ ; fi" \
        "Can't create archive of previous version" 14
fi

##############
### Finish ###
##############

finish_script
