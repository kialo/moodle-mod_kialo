# bundle kialo folder into a zip file (excluding development-related files)
# Usage: ./bundle.sh

# Ensure that the version in moodle is up-to-date. This corresponds to our release version.
./sync.sh

cd moodle/mod
rm ../../mod_kialo.zip
zip -qr ../../mod_kialo.zip kialo
echo "Bundle created: mod_kialo.zip"
