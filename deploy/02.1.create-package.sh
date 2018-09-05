#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." >/dev/null && pwd )"
cd ${DIR}

source deploy/00.functions.sh

[ -f build-pack.tar.gz ] && rm build-pack.tar.gz

mkdir .build-pack

rsync -av * .build-pack/ \
    --exclude="node_modules" \
    --exclude="deploy" \
    --exclude="storage" \
    --exclude="public/uploads" \
    --exclude="build-pack.tar.gz" \
    --exclude=".build-pack"

tar -czf build-pack.tar.gz .build-pack

rm -r .build-pack
