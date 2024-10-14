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

Before you run Moodle copy `.env.example` to `.env` and adjust the values to your needs. 
See `.env.example` for instructions.

The following command starts Moodle locally on port 8080 with MariaDB running on port 3366.
This is using non-default ports to avoid conflicts with already running services.
It also starts the hosted version of the Moodle app on port 8100.

```shell
cd development
cp .env.example .env # before starting compose, check instructions in this file
docker compose up
```

After you started Moodle for the first time, do the following to set some useful default settings:

* Copy `development/config.php` into `development/moodle` to apply some default settings useful for development.
* Import `development/config/kialo-admin-preset.xml` via http://localhost:8080/admin/tool/admin_presets/index.php?action=import.

The admin presets are important, as they adjust Moodle's curl blocklist and allowed ports. Without that,
testing Kialo locally won't work, as communication will be blocked by Moodle.
This also enables web services for mobile (required for the mobile app) and enables debug messages for developers.

By default there is only one user with the username "user" and password "kialo1234". This is the admin user.

The folder `moodle` is mounted locally in the `development` folder. To test changes to the plugin code,
you can use `development/sync.sh` to copy over the code into the `moodle/mod/kialo` folder.A

### Moodle versions

The Docker setup is configured to run the latest moodle version (`main` branch) by default.
If you want to run a specific version, like one of the LTS versions, you can change the `MOODLE_VERSION` in the `.env` file.
See `.env.example` for details.

### Using the Moodle Mobile app

The Moodle mobile app is part of the docker compose setup and is available on port 8100.
To use the local Moodle instance with the mobile app, you need to change the app's configuration,
and enable web services following these instructions: https://docs.moodle.org/402/en/Mobile_web_services.
If you imported the admin preset from `/development/config/kialo-admin-preset.xml`, this should already be done.

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

If the test fails, run it with the env variable `UPDATE_THIRDPARTYLIBS=1` to automatically regenerate the file:

```shell
UPDATE_THIRDPARTYLIBS=1 ./vendor/bin/phpunit tests/thirdpartylibs_test.php
```

## Linting

We use PHP CodeSniffer and php-cs-fixer to lint the code. To run the linter, execute `composer lint`.
To automatically fix linting issues and auto-format the code, run `composer fix`.

IntelliJ IDEA or PhpStorm have built-in support for PHP CodeSniffer and php-cs-fixer,
and the included project files already include the necessary configuration.
You can use "Inspect Code" to find linting issues.

If you use Visual Studio Code, there as an [extension](https://marketplace.visualstudio.com/items?itemName=obliviousharmony.vscode-php-codesniffer#:~:text=Integrates%20PHP_CodeSniffer%20into%20VS%20Code,utilizes%20VS%20Code's%20available%20features.) for that, too.

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

## Creating Releases

### Automatic Release

We use GitHub Actions to automatically create a release whenever a new tag is pushed to the repository.
This requires an [access key](https://moodledev.io/general/community/plugincontribution/pluginsdirectory/api#access-token),
which is configured as a secret in this GitHub repo.

To create a new release, follow these steps:

1. Ensure the version in `version.php` has been incremented and `CHANGES.md` has been updated accordingly. 
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

### When running `docker compose up` the moodle container exits shortly after startup with exit code 1

This can happen if you deleted your docker containers before for some reason and then tried running `docker compose up` again.
Try deleting both the docker images, and the folder `development/moodle`, and then run `docker compose up` again.
