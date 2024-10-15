# bundle kialo folder into a zip file (excluding development-related files)
# Usage: ./bundle.sh

# Only install production dependencies
cd .. # cd into root of repository
rm -rf vendor/
composer install --no-dev --no-scripts --prefer-dist --no-interaction

# extra files we want to include in the bundle (e.g. README.md, LICENSE, etc.)
cp -r vendor_extra/* ./vendor/

# Ensure that the version in moodle is up-to-date. This corresponds to our release version.
cd development
./sync.sh

# Create a new ZIP file
rm mod_kialo.zip
rm -rf mod_kialo/vendor_extra
zip -qr mod_kialo.zip mod_kialo

# restore full dependencies (including dev dependencies)
cd ..
composer install

echo "-----------------------------"
echo "Bundle created: development/mod_kialo.zip"
