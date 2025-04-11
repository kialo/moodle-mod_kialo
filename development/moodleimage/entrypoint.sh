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

echo "Waiting for MariaDB to be ready..."
max_attempts=30
attempts=0
while ! mysql -h "$MOODLE_DATABASE_HOST" -P "$MOODLE_DATABASE_PORT_NUMBER" -u "moodle" --password="$MOODLE_DATABASE_PASSWORD" -e "SELECT 1;" &> /dev/null; do
    attempts=$((attempts + 1))
    if [ $attempts -ge $max_attempts ]; then
        echo "Error: MariaDB did not become ready in time"
        exit 1
    fi
    echo "Waiting for MariaDB... ($attempts/$max_attempts)"
    sleep 2
done
echo "MariaDB is ready!"

# Ensure ownership of the mounted directory is the same as host for Linux
chown -R developer:developer /var/www/html/mod/kialo

# Install Moodle. See https://docs.moodle.org/404/en/Installing_Moodle#Command_line_installer
su - www-data -s /bin/bash -c "php /var/www/html/admin/cli/install.php --non-interactive --agree-license --allow-unstable --wwwroot=$MOODLE_HOST --dataroot=/var/www/moodledata --dbtype=mariadb --dbhost=$MOODLE_DATABASE_HOST --dbname=moodle --dbuser=moodle --dbport=$MOODLE_DATABASE_PORT_NUMBER --fullname=Moodle --shortname=moodle --adminuser=user --adminpass=$MOODLE_PASSWORD --adminemail=sre@kialo.com --supportemail=sre@kialo.com"

if [ ! -f /var/www/html/config.php ]; then
    echo "Error: config.php file is missing. Moodle installation may have failed."
    exit 1
fi
# Amend the config.php file to include our own config for development.
# The line should be added before the last line "require_once(__DIR__ . '/lib/setup.php');".
if ! grep -q "config_kialo.php" /var/www/html/config.php; then
    sed -i '/require_once/i\require_once(__DIR__ . "/config_kialo.php");' /var/www/html/config.php
    echo "Added config_kialo.php to config.php"
fi

# Replace the default Apache port 80 with the exposed port 8080. When running Moodle and
# Kialo in the same Docker network, it is necessary for the container port to be the same
# as the exposed port so the Kialo backend can connect to the Moodle container using the
# same address as in the browser.
sed -i "/^\s*Listen 80/c\Listen 8080" /etc/apache2/*.conf \
    && sed -i "/^\s*<VirtualHost \*:80>/c\<VirtualHost \*:8080>" /etc/apache2/sites-available/000-default.conf

# Check if nvm is already installed and skip installation if it exists
if [ ! -d "/home/developer/.nvm" ]; then
  echo "Setting up nvm and Node.js for developer user..."
  chown developer /var/www/html/npm-shrinkwrap.json
  su - developer -c "curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.2/install.sh | bash && \
                   export NVM_DIR=\$HOME/.nvm && \
                   [ -s \$NVM_DIR/nvm.sh ] && . \$NVM_DIR/nvm.sh && \
                   cd /var/www/html && \
                   nvm install && \
                   npm install"
else
  echo "nvm already installed, skipping setup..."
fi

echo "Starting JavaScript file watcher"
su - developer -c "/usr/local/bin/watch-and-build-js.sh &"

exec /usr/local/bin/moodle-docker-php-entrypoint "$@"
