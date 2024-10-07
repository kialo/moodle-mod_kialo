#!/bin/bash

# see https://docs.moodle.org/404/en/Installing_Moodle#Command_line_installer
chown -R www-data /var/www/html

# TODO: Make moodle default user "user" and not "admin"
su - www-data -s /bin/bash -c "php /var/www/html/admin/cli/install.php --non-interactive --agree-license --allow-unstable --wwwroot=$MOODLE_HOST --dataroot=/var/www/moodledata --dbtype=mariadb --dbhost=$MOODLE_DATABASE_HOST --dbname=moodle --dbuser=moodle --dbport=$MOODLE_DATABASE_PORT_NUMBER --fullname=Moodle --shortname=moodle --adminuser=user --adminpass=$MOODLE_PASSWORD --adminemail=sre@kialo.com --supportemail=sre@kialo.com"

exec /usr/local/bin/moodle-docker-php-entrypoint "$@"
