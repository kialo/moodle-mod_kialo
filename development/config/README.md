# Moodle Config

These adjustments need to be made only once after the initial Moodle setup.

 * Copy `config.php` into `/development/moodle` to apply some default settings useful for development.
 * Import `kialo-admin-preset.xml` via http://localhost:8080/admin/tool/admin_presets/index.php?action=import.
This enables web services for mobile (required for the mobile app) and enables debug messages for developers.
 * See `../.env.example` on how to correctly configure the hostnames.
