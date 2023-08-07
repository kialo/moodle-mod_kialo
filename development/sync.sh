# syncs the content of the development version of this plugin to the copy in the docker moodle installation (/moodle/mod/kialo)
cd ..
rsync -a --delete --exclude development . ./development/moodle/mod/kialo
echo "Synced plugin."
