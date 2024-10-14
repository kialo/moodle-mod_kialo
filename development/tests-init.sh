# needs to run once initially and again whenever new tests are added
docker exec -i mod_kialo-moodle-1 /bin/bash -c "cd /var/www/html; php admin/tool/phpunit/cli/init.php"
