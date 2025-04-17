# Run the tests
docker exec -i mod_kialo-moodle-1 /bin/bash -c "cd /var/www/html; mod/kialo/vendor/bin/phpunit --testsuite core_privacy_testsuite --filter kialo"
