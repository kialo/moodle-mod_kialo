# Ensure files are up-to-date
./sync.sh

# Run the tests
docker exec -i mod_kialo-moodle-1 /bin/bash -c "cd /var/www/html; vendor/bin/phpunit --testsuite core_privacy_testsuite --filter kialo"
