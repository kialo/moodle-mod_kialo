# Run a specific test file (path relative to mod/kialo/tests)
# Example: ./tests-run.sh classes/mod_kialo_test.php
docker exec -i mod_kialo-moodle-1 /bin/bash -c "cd /var/www/html; mod/kialo/vendor/bin/phpunit mod/kialo/tests/$1"
