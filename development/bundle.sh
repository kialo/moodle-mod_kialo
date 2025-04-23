# bundle kialo folder into a zip file (excluding development-related files)
# Usage: ./bundle.sh

# Get absolute paths
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
PARENT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
# The folder in the zip file needs to be called just "kialo"
TARGET_DIR="$SCRIPT_DIR/kialo"
ZIP_FILE="$SCRIPT_DIR/mod_kialo.zip"

# Only install production dependencies
cd "$PARENT_DIR" # cd into root of repository
rm -rf vendor/
composer install --no-dev --no-scripts --prefer-dist --no-interaction

# extra files we want to include in the bundle (e.g. README.md, LICENSE, etc.)
cp -r vendor_extra/* ./vendor/

# Create a new ZIP file
rm -f "$SCRIPT_DIR/mod_kialo.zip"

# Create target directory
mkdir -p "$TARGET_DIR"

# Use absolute paths to avoid recursion problems
rsync -atm --delete --delete-excluded \
  --exclude="$SCRIPT_DIR/" \
  --exclude="*/\.*" \
  --exclude="*/moodle/" \
  --exclude=".git" \
  "$PARENT_DIR"/ "$TARGET_DIR"/

cd "$SCRIPT_DIR"
zip -qr "$ZIP_FILE" "$(basename "$TARGET_DIR")"
rm -rf "$TARGET_DIR"

cd "$PARENT_DIR"

# restore full dependencies (including dev dependencies)
composer install

echo "-----------------------------"
echo "Bundle created: $ZIP_FILE"
