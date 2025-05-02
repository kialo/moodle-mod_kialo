# Plugin Development Guide

## Prerequisites

* Git
* PHP composer (https://getcomposer.org/)
  * on macOS you can install via `brew install composer`
* PHP 7.4 or PHP 8.2
  * If you installed composer `brew`, this should already be installed
* Docker (https://www.docker.com/)

## Development Setup

First, check out this repository and install the PHP dependencies.

```shell
git clone git@github.com:kialo/moodle-mod_kialo.git
cd moodle-mod_kialo
composer install
```

### IDE Setup

* https://docs.moodle.org/dev/Setting_up_PhpStorm
* https://docs.moodle.org/dev/Setting_up_VSCode
* https://docs.moodle.org/dev/Setting_up_ViM

Project files for IntelliJ IDEA / PhpStorm are already included in the `.idea` folder.
The IDEA project also includes a file watcher that automatically copies over the plugin files
into the mounted moodle plugin folder on every change.

## How to run Moodle

### First time setup

The following commands starts Moodle locally on port 8080 with MariaDB running on port 3366.
This is using non-default ports to avoid conflicts with already running services.
It also starts the hosted version of the Moodle app on port 8100.
It may take a few minutes for the `moodle` container to finish downloading and installing the app.

```shell
# Follow instructions in the .env file depending on your Kialo setup.
cp development/.env.example development/.env
composer docker:up
```

At this point Moodle should be running locally on port 8080.
(Note: this takes a while when running it for the first time.
The moodle logo needs to appear in the logs and after that the dependencies need to finish installing.
You can watch the logs via `cd development && docker compose logs -f`.)
You may access the site using any hostname that resolves to localhost, e.g. `http://localhost:8080`.
**If you are running Kialo in Docker, you must use a non-localhost hostname** so the Kialo backend can connect to the `moodle` container using the same name.
This can be the IP address of the `moodle` container or the Docker hostname of the `moodle` container (`moodle` by default).
You can add an entry to your `/etc/hosts` file so the custom hostname resolves correctly (see `.env.example`).

After you started Moodle for the first time, do the following to set some useful default settings:

* Import `development/config/kialo-admin-preset-universal.xml` via http://{MOODLE_HOST}/admin/tool/admin_presets/index.php?action=import. (Note: you need to login with "user/kialo1234".)
* Accept Kialo plugin ToS at http://{MOODLE_HOST}:8080/admin/settings.php?section=modsettingkialo.

The admin presets are important, as they adjust Moodle's curl blocklist and allowed ports. Without that,
testing Kialo locally won't work, as communication will be blocked by Moodle.
This also enables web services for mobile (required for the mobile app) and enables debug messages for developers.

By default there is only one user with the username "user" and password "kialo1234". This is the admin user.

If you'd like to set up the instance with some test users and classes, run the following command:
```shell
composer docker:populate-users
```
All users will have the password "kialo1234".

### Developing the plugin

The plugin folder is mounted in the Docker container, so your local changes should be reflected on refreshing the page.
If you do not immediately see changes, you may need to purge the cache as a Moodle admin user.
When logged in as the admin user, you can scroll to the bottom of any page and click on "Purge all caches" in the footer.

#### JavaScript
While developing, Moodle will only serve the minified files in the `amd/build` directory.
The Moodle container is set up to automatically build these files and source maps when you make a change in `amd/src`.
It's best to disable JS caching in the admin settings (http://{MOODLE_HOST}:8080/admin/settings.php?section=ajax).
This will significantly slow down page loads, so only use this when necessary.

The Moodle container will also handle linting for the JS source files, and will not build the minified files if the linting fails.
You can check the logs of the Moodle container to see the output of the linter.

If the CI job fails on the Grunt task due to a mismatch of the JS files, you may need to switch your local Moodle container branch to `main` to update the build tools.

### Moodle versions

The Docker setup is configured to run the latest moodle version (`main` branch) by default.
If you want to run a specific version, like one of the LTS versions, you can change the `MOODLE_VERSION` in the `.env` file.
See `.env.example` for details.

### Using the Moodle Mobile app

The Moodle mobile app is part of the docker compose setup and is available on port 8100.
To use the local Moodle instance with the mobile app, you need to change the app's configuration,
and enable web services following these instructions: https://docs.moodle.org/402/en/Mobile_web_services.
If you imported the admin preset from `/development/config/kialo-admin-preset.xml`, this should already be done.

### Backup & Restore - cron jobs

Course backups and restores require the cron job to be executed. At the moment, this process is only possible manually due to our current setup.
* Uncheck `Cron execution via command line only` under site security settings (`http://{MOODLE_HOST}/admin/settings.php?section=sitepolicies`)
* Open `http://{MOODLE_HOST}/admin/cron.php` - repeat this each time you run a backup & restore process.

### Use hosted Moodle instances

If you don't want to or can't run Moodle locally, you can also use Moodle's hosted versions:

* https://sandbox.moodledemo.net/ - this is a clean Moodle instance, reset once per hour
* https://latest.apps.moodledemo.net/ - the mobile app

## Moodle Activity Plugin Development

* https://docs.moodle.org/dev/Activity_modules
* https://moodledev.io/general/development/policies/codingstyle

### Dependency Management

We use PHP Composer to manage our dependencies.
To add a new dependency, run `composer require <package-name>`.

Whenever any dependency is changed (when `composer.lock` changes), you need to ensure to update `thirdpartylibs.xml` accordingly. Run the test `tests/thirdpartylibs_test.php` to check that
the thirdpartylibs.xml file is up to date.

If the test fails, run it with the env variable `UPDATE_THIRDPARTYLIBSXML=1` to automatically regenerate the file:

```shell
UPDATE_THIRDPARTYLIBSXML=1 ./vendor/bin/phpunit tests/thirdpartylibs_test.php
```

Dependencies for pipeline runs are handled in the composer-ci.json file. If you need dependencies locally that
result in pipeline failures, you may want to adjust this file to exclude certain dependencies that cause issues
or change other settings that are not relevant for local development.

## Linting

We use PHP CodeSniffer and php-cs-fixer to lint the code. To run the linter, execute `composer lint`.
To automatically fix linting issues and auto-format the code, run `composer fix`.

IntelliJ IDEA or PhpStorm have built-in support for PHP CodeSniffer and php-cs-fixer,
and the included project files already include the necessary configuration.
You can use "Inspect Code" to find linting issues.

If you use Visual Studio Code, there as an [extension](https://marketplace.visualstudio.com/items?itemName=obliviousharmony.vscode-php-codesniffer#:~:text=Integrates%20PHP_CodeSniffer%20into%20VS%20Code,utilizes%20VS%20Code's%20available%20features.) for that, too.

## Testing

Tests for the plugin are located in the `tests` folder. This needs to be executed in the docker compose context,
because they require access to the Moodle instance.

### How to run tests

To run all tests, follow these steps:

1. Start the docker compose setup: `composer docker:up`
2. Initialise the test environment: `development/tests-init.sh`
3. Run the tests:

   * To run all tests, execute `development/tests-run-all.sh`
   * To run a specific test file, use `tests-run.sh`, e.g.: `development/tests-run.sh tests/acceptance/kialo_test.php`
   * Alternatively, you can use `composer test` to run both init and all tests.

Each time you add new test files, you need to run `development/tests-init.sh` again.

## Creating Releases

### Writing Release Notes

See our [process document](https://docs.google.com/document/d/1iiZu7URlU0aeUQjig49_E6hHAXkJA-Yv0dee6Lo9Qts/edit?tab=t.0#heading=h.35vhdy4o8n97)
on how to write release notes.

### Automatic Release

We use GitHub Actions to automatically create a release whenever a new tag is pushed to the repository.
This requires an [access key](https://moodledev.io/general/community/plugincontribution/pluginsdirectory/api#access-token),
which is configured as a secret in this GitHub repo.

To create a new release, follow these steps:

1. Ensure the version in `version.php` has been incremented and `CHANGES.md` has been [updated accordingly](https://docs.google.com/document/d/1iiZu7URlU0aeUQjig49_E6hHAXkJA-Yv0dee6Lo9Qts/edit?tab=t.0#heading=h.35vhdy4o8n97).
2. Create a new tag, e.g. `git tag v1.2.3`.
   * For pre-releases that should not be pushed to the moodle plugin directory, use a tag name like `v1.2.3-beta1`.
        As long as the name contains the keyword `beta`, the release will not be pushed to the moodle plugin directory.
3. Push the commit and the tag to GitHub (`git push && git push --tags`).

To verify that the release was successful:

1. Wait for the release to be created on GitHub. This can take a few minutes.
   See https://github.com/kialo/moodle-mod_kialo/actions/workflows/moodle-ci.yml.
   Note that the release job in the CI workflow is only triggered for tags.
2. Check the release [on GitHub](https://github.com/kialo/moodle-mod_kialo/releases). Ensure the `mod_kialo.zip` file is attached.
3. Check the release on https://moodle.org/plugins/mod_kialo. Ensure the version number and changelog is correct.
   This only applies to non-beta releases.

### Manual Release

To release a new version, follow these steps:

1. Ensure the version in `version.php` has been incremented.
2. Run `composer bundle`. This will create the file `development/mod_kialo.zip`.
3. Create a new release on GitHub, and upload the zip file as an asset. The release should be tagged with the version number (e.g. v0.3.1).
4. Add the new version on https://moodle.org/plugins/mod_kialo and ensure the version number is correct.

    * Ensure to include useful release notes for end-users / Moodle admins.

## Related Docs

* https://registry.hub.docker.com/r/bitnami/moodle - setting up Moodle locally
* https://docs.moodle.org/dev/Automatic_class_loading
* https://docs.moodle.org/dev/Plugin_contribution_checklist

## Troubleshooting

### I see a Moodle login screen in the embed during the LTI flow
If you are using Chrome, this can occur because the site is running without HTTPS and the cookie cannot have the `SameSite=None` and `Secure` flags.
Chrome will block the cookie in the iframe in this case.
For local development, Firefox is preferred as it seems to allow the embedded iframe to access the Moodle session cookie even without HTTPS.
Safari does not block the cookie in the iframe and can also be used for local development.

### When running `composer docker:up` the moodle container exits shortly after startup with exit code 1

This can happen if you deleted your docker containers before for some reason and then tried running `composer docker:up` again.
Try resetting the docker images with `composer docker:reset`, delete the folder `development/moodle`, and then run `composer docker:up` again.

### The LTI flow fails when connecting linking or launching a discussion

This can occur if the Kialo backend cannot connect to the `moodle` container or vice versa.
Check that the Kialo backend and `moodle` containers can `ping` or `curl` the other.

#### Kialo running with Docker
If the Kialo backend container cannot reach the `moodle` container, check the following:
* The containers are on the same Docker network.
* The hostname used for Moodle is one of the network aliases of the `moodle` container. Run `docker inspect {moodle_container_name}` to check.

If the `moodle` container cannot reach the Kialo backend container, check the following:
* The containers are on the same Docker network.

#### Kialo running with Honcho
If the `moodle` container cannot reach the Kialo backend, check the following:
* On Linux, the firewall may be preventing the `moodle` container from connecting to Kialo running on localhost. Example steps with `ufw`:
   * Find the IP range for the docker network: `docker network inspect kialo_default`.
   * Find the corresponding network interface created by docker with this IP address: `ip a`. It may have a name like `br-{RANDOM HASH}`. Copy this name.
   * Add a new rule to allow traffic from the docker network to the host: `sudo ufw allow in on {NETWORK INTERFACE NAME} from {DOCKER NETWORK IP RANGE}`.
   * After you are done with moodle, clean up the firewall rule. Find the rule number with `sudo ufw status numbered` and delete it with `sudo ufw delete {RULE NUMBER}`.
