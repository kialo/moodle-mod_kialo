# bundle kialo folder into a zip file (excluding development-related files)
# Usage: ./bundle.sh

# Only install production dependencies
cd .. # cd into root of repository
rm -rf vendor/
composer install --no-dev --no-scripts --prefer-dist --no-interaction

# Ensure that the version in moodle is up-to-date. This corresponds to our release version.
cd development
./sync.sh

# Create a new ZIP file
rm mod_kialo.zip
cd moodle/mod
zip -qr ../../mod_kialo.zip kialo

# restore full dependencies (including dev dependencies)
cd ../../..
composer install

echo "-----------------------------"
echo "Bundle created: mod_kialo.zip"
