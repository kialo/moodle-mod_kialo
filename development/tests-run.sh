# Run a specific test file (path relative to mod/kialo/tests)
# Example: ./tests-run.sh classes/mod_kialo_test.php
docker exec -i development-moodle-1 /bin/bash -c "cd /bitnami/moodle; vendor/bin/phpunit mod/kialo/tests/$1"
