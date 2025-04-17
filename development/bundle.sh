# bundle kialo folder into a zip file (excluding development-related files)
# Usage: ./bundle.sh

# Only install production dependencies
cd .. # cd into root of repository
rm -rf vendor/
composer install --no-dev --no-scripts --prefer-dist --no-interaction

# extra files we want to include in the bundle (e.g. README.md, LICENSE, etc.)
cp -r vendor_extra/* ./vendor/

cd development

# Create a new ZIP file
rm -f mod_kialo.zip
rm -rf mod_kialo/vendor_extra

# The folder in the zip file needs to be called just "kialo"
mv mod_kialo kialo
zip -qr mod_kialo.zip kialo
mv kialo mod_kialo

# restore full dependencies (including dev dependencies)
cd ..
composer install

echo "-----------------------------"
echo "Bundle created: development/mod_kialo.zip"
