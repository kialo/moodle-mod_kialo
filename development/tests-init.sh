# needs to run once initially and again whenever new tests are added
docker exec -i mod_kialo-moodle-1 php "/var/www/html/${MOODLE_PUBLIC_PREFIX-public/}admin/tool/phpunit/cli/init.php"
