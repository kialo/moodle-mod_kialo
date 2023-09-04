# Plugin Development Guide

## Prerequisites

* Git
* PHP composer (https://getcomposer.org/)
  * on macOS you can install via `brew install composer`
* PHP 7.4 or higher
  * If you installed composer `brew`, this should already be installed
* Docker (https://www.docker.com/)

## Development Setup

First, check out this repository and install the PHP dependencies.

```shell
git clone git@github.com:kialo/moodle-mod_kialo.git
cd moodle-mod_kialo
composer install
```

## Run Moodle locally

This starts Moodle locally on port 8080 with MariaDB running on port 3366.
This is using non-default ports to avoid conflicts with already running services.
It also starts the hosted version of the Moodle app on port 8100.


```shell
cd development
cp .env.example .env # before starting compose, check instructions in this file
docker compose up
```

Afterward, see `/development/config/README.md` for steps to apply default settings that are useful for development.

The folder `moodle` is mounted locally in the `development` folder. To test changes to the plugin code,
you can use `development/sync.sh` to copy over the code into the `moodle/mod/kialo` folder.

## Using the Moodle Mobile app

The Moodle mobile app is part of the docker compose setup and is available on port 8100.
To use the local Moodle instance with the mobile app, you need to change the app's configuration,
and enable web services following these instructions: https://docs.moodle.org/402/en/Mobile_web_services.
If you imported the admin preset from `/development/config/kialo-admin-preset.xml`, this should already be done.

## Use hosted Moodle instances

If you don't want to or can't run Moodle locally, you can also use Moodle's hosted versions:

* https://sandbox.moodledemo.net/ - this is a clean Moodle instance, reset once per hour
* https://latest.apps.moodledemo.net/ - the mobile app

## IDE Setup

* https://docs.moodle.org/dev/Setting_up_PhpStorm
* https://docs.moodle.org/dev/Setting_up_VSCode
* https://docs.moodle.org/dev/Setting_up_ViM

Project files for IntelliJ IDEA / PhpStorm are already included in the `.idea` folder.
The IDEA project also includes a file watcher that automatically copies over the plugin files
into the mounted moodle plugin folder on every change.

## Moodle Activity Plugin Development

* https://docs.moodle.org/dev/Activity_modules
* https://moodledev.io/general/development/policies/codingstyle

### Dependency Management

We use PHP Composer to manage our dependencies.
To add a new dependency, run `composer require <package-name>`.

Whenever any dependency is changed (when `composer.lock` changes), you need to ensure to update `thirdpartylibs.xml` accordingly. Run the test `tests/thirdpartylibs_test.php` to check that 
the thirdpartylibs.xml file is up to date.

If the test fails, run it with the env variable `UPDATE_THIRDPARTYLIBS=1` to automatically regenerate the file:

```shell
UPDATE_THIRDPARTYLIBS=1 ./vendor/bin/phpunit tests/thirdpartylibs_test.php
```

## Testing

Tests for the plugin are located in the `tests` folder. This need to be executed in the docker compose context,
because they require access to the Moodle instance.

### How to run tests

To run all tests, follow these steps:

1. Start the docker compose setup: `cd development; docker compose up`
2. Initialise the test environment: `development/tests-init.sh`
3. Ensure the plugin files are synchronized with the Moodle instance: `cd development; sync.sh`
4. Run the tests:

   * To run all tests, execute `development/tests-run-all.sh`
   * To run a specific test file, use `tests-run.sh`, e.g.: `development/tests-run.sh tests/acceptance/kialo_test.php`
   * Alternatively, you can use `composer test` to run both init and all tests.

Each time you change the plugin code or a test, you need to run `cd development; sync.sh` again.
If you are using IntelliJ IDEA, the project files included in this project already include a file watcher that does that.

Each time you add new test files, you need to run `development/tests-init.sh` again.

## Linting

We use PHP CodeSniffer to lint the code. To run the linter, execute `composer lint`.

IntelliJ IDEA or PhpStorm have built-in support for PHP CodeSniffer,
and the included project files already include the necessary configuration.
You can use "Inspect Code" to find linting issues.

If you use Visual Studio Code, there as an [extension](https://marketplace.visualstudio.com/items?itemName=obliviousharmony.vscode-php-codesniffer#:~:text=Integrates%20PHP_CodeSniffer%20into%20VS%20Code,utilizes%20VS%20Code's%20available%20features.) for that, too.

## Creating Releases

To release a new version, follow these steps:

1. Ensure the version in `version.php` has been incremented.
2. Run `composer bundle`. This will create the file `development/mod_kialo.zip`.
3. Create a new release on GitHub, and upload the zip file as an asset. The release should be tagged with the version number (e.g. v0.3.1).

    * Check "Set as a pre-release" and add a tag "-alpha" to the tag while we haven't published the plugin yet.

## Related Docs

* https://registry.hub.docker.com/r/bitnami/moodle - setting up Moodle locally
* https://docs.moodle.org/dev/Automatic_class_loading
* https://docs.moodle.org/dev/Plugin_contribution_checklist

## Troubleshooting

### When running `docker compose up` the moodle container exits shortly after startup with exit code 1

This can happen if you deleted your docker containers before for some reason and then tried running `docker compose up` again.
Try deleting both the docker images, and the folder `development/moodle`, and then run `docker compose up` again.
