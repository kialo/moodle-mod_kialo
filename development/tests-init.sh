# needs to run once initially and again whenever new tests are added
docker exec -it development-moodle-1 /bin/bash -c "cd /bitnami/moodle; php admin/tool/phpunit/cli/init.php"
