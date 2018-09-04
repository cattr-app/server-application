#!/bin/bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." >/dev/null && pwd )"
cd ${DIR}

source deploy/00.functions.sh

[ "$PRODUCTION_SSH_PATH" == "" ] && export PRODUCTION_SSH_PATH="/opt/amazing-time"
[ "$PRODUCTION_SSH_PORT" == "" ] && export PRODUCTION_SSH_PORT="22"
[ "$PRODUCTION_STORAGE_PATH" == "" ] && export PRODUCTION_STORAGE_PATH="$PRODUCTION_SSH_PATH/storage"

export LOCAL_SSH_KEY="./.id_rsa.at.private"
export BUILD_ID="$(date +"%F_%H-%M-%S")__${CI_COMMIT_TAG}__${CI_COMMIT_REF_SLUG}"

echo "$PRODUCTION_SSH_PASSWORD" > ${LOCAL_SSH_KEY}
chmod 600 ${LOCAL_SSH_KEY}

# Shortcut for ssh command
function sshcmd() {
    echo-cyan "ssh -o StrictHostKeyChecking=no -i ${LOCAL_SSH_KEY} -p ${PRODUCTION_SSH_PORT} ${PRODUCTION_SSH_USER}@${PRODUCTION_SSH_IP} \"$*\""
    ssh -o StrictHostKeyChecking=no -i ${LOCAL_SSH_KEY} -p ${PRODUCTION_SSH_PORT} ${PRODUCTION_SSH_USER}@${PRODUCTION_SSH_IP} "$*"
}

# Copy build package
echo-cyan "scp -o StrictHostKeyChecking=no -i ${LOCAL_SSH_KEY} -P $PRODUCTION_SSH_PORT build-pack.tar.gz ${PRODUCTION_SSH_USER}@${PRODUCTION_SSH_IP}:${PRODUCTION_SSH_PATH}"
scp -o StrictHostKeyChecking=no -i ${LOCAL_SSH_KEY} -P $PRODUCTION_SSH_PORT build-pack.tar.gz ${PRODUCTION_SSH_USER}@${PRODUCTION_SSH_IP}:${PRODUCTION_SSH_PATH}
check-last-code "Can't upload archive to host" 1

sshcmd "cd $PRODUCTION_SSH_PATH ; tar -xf build-pack.tar.gz"
check-last-code "Can't extract archive" 2

sshcmd mv $PRODUCTION_SSH_PATH/.build-pack $PRODUCTION_SSH_PATH/builds/$BUILD_ID
check-last-code "Can't move build to builds folder" 3

# Set build to current
sshcmd "if [ -e ${PRODUCTION_SSH_PATH}/current ]; then rm ${PRODUCTION_SSH_PATH}/current ; fi"
check-last-code "Can't unlink current build" 4

sshcmd ln -s $PRODUCTION_SSH_PATH/builds/$BUILD_ID $PRODUCTION_SSH_PATH/current
check-last-code "Can't create link for new build" 5

# Link storage to new build
sshcmd ln -s $PRODUCTION_STORAGE_PATH $PRODUCTION_SSH_PATH/current/storage
check-last-code "Can't create storage link" 6

#Finish configuration of new build
sshcmd "ln -s $PRODUCTION_SSH_PATH/current/storage/app/uploads $PRODUCTION_SSH_PATH/current/public/"
check-last-code "Can't create link for public uploads folder" 7

# Run migrations
sshcmd php $PRODUCTION_SSH_PATH/current/artisan migrate
check-last-code "Database Migration failed" 8

# Archive previous build
PREV_BUILD_ID=$(sshcmd cat ${PRODUCTION_SSH_PATH}/.build_id)
if [ "$PREV_BUILD_ID" != "" ]; then
    sshcmd "cd $PRODUCTION_SSH_PATH/builds/ ; tar -czf ${PREV_BUILD_ID}.tar.gz $PREV_BUILD_ID/ && rm -rf $PREV_BUILD_ID/"
fi


# Do some history
sshcmd "cat $PRODUCTION_SSH_PATH/.build_id >> $PRODUCTION_SSH_PATH/.build_history"
sshcmd "echo $BUILD_ID > $PRODUCTION_SSH_PATH/.build_id"

rm ${LOCAL_SSH_KEY}
