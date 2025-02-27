#!/bin/bash

# Install Moodle. See https://docs.moodle.org/404/en/Installing_Moodle#Command_line_installer
chown -R www-data /var/www/html
su - www-data -s /bin/bash -c "php /var/www/html/admin/cli/install.php --non-interactive --agree-license --allow-unstable --wwwroot=$MOODLE_HOST --dataroot=/var/www/moodledata --dbtype=mariadb --dbhost=$MOODLE_DATABASE_HOST --dbname=moodle --dbuser=moodle --dbport=$MOODLE_DATABASE_PORT_NUMBER --fullname=Moodle --shortname=moodle --adminuser=user --adminpass=$MOODLE_PASSWORD --adminemail=sre@kialo.com --supportemail=sre@kialo.com"

# Amend the config.php file to include our own config for development.
# The line should be added before the last line "require_once(__DIR__ . '/lib/setup.php');".
sed -i '/require_once/i\require_once(__DIR__ . "/config_kialo.php");' /var/www/html/config.php

# Make localhost.kialolabs.com point to the host machine IP address.
echo "$HOST_IP localhost.kialolabs.com" >> /etc/hosts

exec /usr/local/bin/moodle-docker-php-entrypoint "$@"
