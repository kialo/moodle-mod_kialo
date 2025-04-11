#!/bin/bash

is_kialo_reachable() {
    curl -s --connect-timeout 5 "$TARGET_KIALO_URL" > /dev/null
}

if ! is_kialo_reachable; then
    KIALO_IP="${KIALO_IP:-$(dig +short A host.docker.internal | head -n 1)}"
    if [[ -n "$KIALO_IP" ]]; then
        echo "Adding $KIALO_IP as the IP for local Kialo"
        echo "$KIALO_IP localhost.kialolabs.com" >> /etc/hosts
    else
        echo "No IP found for local Kialo. If you are using Linux, make sure to set a KIALO_IP in the .env file. Exiting"
        exit 1
    fi

    is_kialo_reachable || {
        echo "Could not connect to local Kialo. Make sure that Kialo is running or consult the README for troubleshooting. Exiting"
        exit 1
    }
fi

echo "Moodle can reach local Kialo, continuing with installation"

# Install Moodle. See https://docs.moodle.org/404/en/Installing_Moodle#Command_line_installer
chown -R www-data /var/www/html
chown -R developer:developer /var/www/html/mod/kialo
su - www-data -s /bin/bash -c "php /var/www/html/admin/cli/install.php --non-interactive --agree-license --allow-unstable --wwwroot=$MOODLE_HOST --dataroot=/var/www/moodledata --dbtype=mariadb --dbhost=$MOODLE_DATABASE_HOST --dbname=moodle --dbuser=moodle --dbport=$MOODLE_DATABASE_PORT_NUMBER --fullname=Moodle --shortname=moodle --adminuser=user --adminpass=$MOODLE_PASSWORD --adminemail=sre@kialo.com --supportemail=sre@kialo.com"

# Amend the config.php file to include our own config for development.
# The line should be added before the last line "require_once(__DIR__ . '/lib/setup.php');".
sed -i '/require_once/i\require_once(__DIR__ . "/config_kialo.php");' /var/www/html/config.php

# Replace the default Apache port 80 with the exposed port 8080. When running Moodle and
# Kialo in the same Docker network, it is necessary for the container port to be the same
# as the exposed port so the Kialo backend can connect to the Moodle container using the
# same address as in the browser.
sed -i "/^\s*Listen 80/c\Listen 8080" /etc/apache2/*.conf \
    && sed -i "/^\s*<VirtualHost \*:80>/c\<VirtualHost \*:8080>" /etc/apache2/sites-available/000-default.conf

exec /usr/local/bin/moodle-docker-php-entrypoint "$@"
