# Run a specific test file (path relative to mod/kialo/tests)
# Example: ./tests-run.sh classes/mod_kialo_test.php
ENV_ARGS=()
if [ -n "$UPDATE_THIRDPARTYLIBSXML" ]; then
    ENV_ARGS=(-e "UPDATE_THIRDPARTYLIBSXML=$UPDATE_THIRDPARTYLIBSXML")
fi
docker exec -i "${ENV_ARGS[@]}" mod_kialo-moodle-1 /bin/bash -c "cd /var/www/html; vendor/bin/phpunit mod/kialo/tests/$1"
