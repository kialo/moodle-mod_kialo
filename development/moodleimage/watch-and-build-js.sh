#!/bin/bash

# Initialize nvm
export NVM_DIR=$HOME/.nvm
. $NVM_DIR/nvm.sh

cd /var/www/html/mod/kialo

while inotifywait -r -e modify,create,delete,move /var/www/html/mod/kialo/amd/src; do
  npx grunt amd;
done
