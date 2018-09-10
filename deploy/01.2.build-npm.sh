#!/usr/bin/env bash

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )/.." >/dev/null && pwd )"
cd ${DIR}

source deploy/00.0.functions.sh

npm install

check-last-code "Problem with npm decencies!" 1

[ -d public/js ] && rm -r public/js
[ -d apidoc ] && rm -r apidoc

([ -L public/apidoc ] && unlink public/apidoc) || ([ -d public/apidoc ] && rm -r public/apidoc)

npm run build-prod

check-last-code "Frontend build failed!" 2

npm run api

check-last-code "API Documentation build failed!" 3

([ -L public/apidoc ] && unlink public/apidoc) || ([ -d public/apidoc ] && rm -r public/apidoc)

mv apidoc public
