# See https://moodledev.io/general/development/policies/php for the list of available PHP versions.
# Different versions of Moodle require different versions of PHP.
ARG PHP_VERSION="8.2"

FROM moodlehq/moodle-php-apache:${PHP_VERSION}

# Install dependencies
RUN apt-get update && apt-get install -y dnsutils inotify-tools curl

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
COPY watch-and-build-js.sh /usr/local/bin/watch-and-build-js.sh

# Create a non-root user with configurable UID/GID
ARG HOST_UID=1000
ARG HOST_GID=1000
RUN groupadd -g ${HOST_GID} developer && \
    useradd -u ${HOST_UID} -g ${HOST_GID} -m -s /bin/bash developer

# Switch to www-data user for the git clone operation
USER www-data

# See https://moodledev.io/general/releases for the list of available versions.
# See https://github.com/moodle/moodle/branches for the available branches.
# Branch names follow the pattern MOODLE_XYZ_STABLE, e.g. MOODLE_404_STABLE for Moodle 4.4.
# main is the default and most up-to-date branch, it includes upcoming versions before they are stable.
ARG MOODLE_BRANCH="main"
ENV MOODLE_BRANCH=${MOODLE_BRANCH}

RUN git clone git://git.moodle.org/moodle.git /var/www/html --depth=1 --branch ${MOODLE_BRANCH}

USER root
COPY config.php /var/www/html/config_kialo.php
RUN chown www-data /var/www/html/config_kialo.php

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
