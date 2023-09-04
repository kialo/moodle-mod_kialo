# bundle kialo folder into a zip file (excluding development-related files)
# Usage: ./bundle.sh

# Only install production dependencies
cd .. # cd into root of repository
cp vendor/oat-sa/lib-lti1p3-core/readme_moodle.txt ./readme_moodle.txt
cp vendor/readme_moodle.txt ./vendor_readme_moodle.txt
rm -rf vendor/
composer install --no-dev --no-scripts --prefer-dist --no-interaction
mv ./readme_moodle.txt vendor/oat-sa/lib-lti1p3-core/readme_moodle.txt
mv ./vendor_readme_moodle.txt vendor/readme_moodle.txt

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
echo "Bundle created: development/mod_kialo.zip"
