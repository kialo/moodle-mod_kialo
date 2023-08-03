# bundle kialo folder into a zip file (excluding development-related files)
# Usage: ./bundle.sh
cd ..
zip -r development/mod_kialo.zip . -x development\* -x .idea\*
