# Ensure this script is executed in the same folder
cd "$(dirname "$0")"

# syncs the content of the development version of this plugin to the copy in the docker moodle installation (/moodle/mod/kialo)
rsync -atm --delete --delete-excluded --exclude='/development' --exclude={'/.[!.]*','/moodle'} .. ./mod_kialo
echo "Synced plugin."
