# Run the tests
docker exec -i development-moodle-1 /bin/bash -c "cd /bitnami/moodle; vendor/bin/phpunit --testsuite mod_kialo_testsuite"
