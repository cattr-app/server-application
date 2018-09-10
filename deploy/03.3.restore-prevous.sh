#!/bin/bash

echo-green "Restore previous build"

if [ "${PREV_BUILD_ID}" != "" ] ; then
    ssh-exec "Restore backup" \
        "${ARTISAN} backup:mysql-restore -y -f ${PREV_BUILD_BACKUP_ID}" \
        "Can't restore last backup" 101

    ssh-exec "Unlink current build" \
        "if [ -e ${PRODUCTION_SSH_PATH}/current ]; then rm ${PRODUCTION_SSH_PATH}/current ; fi" \
        "Can't unlink current build" 102

    ssh-exec "Set new build as current" \
        "ln -s ${PRODUCTION_SSH_PATH}/builds/${PREV_BUILD_ID} ${PRODUCTION_SSH_PATH}/current" \
        "Can't create link for previous build" 103
else
    echo-yellow "No previous version, skip"
fi
