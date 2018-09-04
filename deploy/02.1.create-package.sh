#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." >/dev/null && pwd )"
cd ${DIR}

source deploy/00.functions.sh

([ -L public/uploads ] && unlink public/uploads) || ([ -d public/uploads ] && rm -r public/uploads)
([ -L storage ] && unlink storage) || ([ -d storage ] && rm -r storage)
[ -f build-pack.tar.gz ] && rm build-pack.tar.gz

mkdir .build-pack

rsync -av * .build-pack/ --exclude node_modules

tar -czf build-pack.tar.gz .build-pack
