# syncs the content of the development version of this plugin to the copy in the docker moodle installation (/moodle/mod/kialo)
rsync -atm --delete --delete-excluded --exclude={'/development','/.[!.]*'} .. ./moodle/mod/kialo
echo "Synced plugin."

cp config/config.php moodle/config.php
echo "Synced config.php"
