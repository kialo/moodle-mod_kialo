#!/bin/bash

# Initialize nvm
export NVM_DIR=$HOME/.nvm
. $NVM_DIR/nvm.sh

KIALO_DIR="/var/www/html/${MOODLE_PUBLIC_PREFIX}mod/kialo"

cd "$KIALO_DIR"

while inotifywait -r -e modify,create,delete,move "$KIALO_DIR/amd/src"; do
  npx grunt amd;
done
