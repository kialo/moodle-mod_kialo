# See https://moodledev.io/general/development/policies/php for the list of available PHP versions.
# Different versions of Moodle require different versions of PHP.
ARG PHP_VERSION="8.2"

FROM moodlehq/moodle-php-apache:${PHP_VERSION}

# See https://moodledev.io/general/releases for the list of available versions.
# See https://github.com/moodle/moodle/branches for the available branches.
# Branch names follow the pattern MOODLE_XYZ_STABLE, e.g. MOODLE_404_STABLE for Moodle 4.4.
# main is the default and most up-to-date branch, it includes upcoming versions before they are stable.
ARG MOODLE_BRANCH="main"
ENV MOODLE_BRANCH=${MOODLE_BRANCH}

RUN git clone git://git.moodle.org/moodle.git /var/www/html --depth=1 --branch ${MOODLE_BRANCH}

# Install dnsutils
RUN apt-get update && apt-get install -y dnsutils

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
COPY config.php /var/www/html/config_kialo.php

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
