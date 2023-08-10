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

## Used hosted Moodle instances

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

## Testing

Tests for the plugin are located in the `tests` folder. This need to be executed in the docker compose context,
because they require access to the Moodle instance.

### How to run tests

To run all tests, follow these steps:

1. Start the docker compose setup: `cd development; docker compose up`
2. Initialise the test environment: `development/tests-init.sh`
3. Ensure the plugin files are synchronized with the Moodle instance: `development/sync.sh`
4. Run the tests:

   * To run all tests, execute `development/tests-run-all.sh`
   * To run a specific test file, use `tests-run.sh`, e.g.: `development/tests-run.sh tests/acceptance/kialo_test.php`

Each time you change the plugin code or a test, you need to run `development/sync.sh` again.
If you are using IntelliJ IDEA, the project files included in this project already include a file watcher that does that.

Each time you add new test files, you need to run `development/tests-init.sh` again.

## Linting

We use PHP CodeSniffer to lint the code. To run the linter, execute `composer lint`.

IntelliJ IDEA or PhpStorm have built-in support for PHP CodeSniffer, 
and the included project files already include the necessary configuration.
You can use "Inspect Code" to find linting issues.

If you use Visual Studio Code, there as an [extension](https://marketplace.visualstudio.com/items?itemName=obliviousharmony.vscode-php-codesniffer#:~:text=Integrates%20PHP_CodeSniffer%20into%20VS%20Code,utilizes%20VS%20Code's%20available%20features.) for that, too.

# Random Notes (temporary, should be removed/updated before release)

## Moodle 3 / 4 compatibility

* A single branch can be used to support both Moodle 3x and 4x activity plugins by including icon.svg for Moodle 3x and monologo.svg for Moodle 4x.
* in `kialo_supports` when defining a MOD_PURPOSE_, `if (defined('FEATURE_MOD_PURPOSE') && $feature === FEATURE_MOD_PURPOSE) {
  return MOD_PURPOSE_CONTENT` to ensure Moodle 3 compatibility.

## Global Moodle vars

* `$CFG`: This global variable contains configuration values of the Moodle setup, such as the root directory, data directory, database details, and other config values.
* ```$SESSION`: Moodle's wrapper round PHP's `$_SESSION`.
* `$USER`: Holds the user table record for the current user. This will be the 'guest' user record for people who are not logged in.
* `$SITE`: Frontpage course record. This is the course record with id=1.
* `$COURSE`: This global variable holds the current course details. An alias for `$PAGE->course`.
* `$PAGE`: This is a central store of information about the current page we are generating in response to the user's request.
* `$OUTPUT`: `$OUTPUT `is an instance of core_renderer or one of its subclasses. It is used to generate HTML for output.
* `$DB`: This holds the database connection details. It is used for all access to the database.

## Docs

* https://registry.hub.docker.com/r/bitnami/moodle - setting up Moodle locally
* https://docs.moodle.org/dev/Automatic_class_loading
* https://docs.moodle.org/dev/Plugin_contribution_checklist

## Troubleshooting

### When running `docker compose up` the moodle container exits shortly after startup with exit code 1

This can happen if you deleted your docker containers before for some reason and then tried running `docker compose up` again.
Try deleting both the docker images, and the folder `development/moodle`, and then run `docker compose up` again.

## TODOs

* go through the plugin checklist linked above
* the plugin content should be at the root of the repo, not its own folder
* the git repo should be called `moodle-mod_kialo`
* configure CI to run the tests for different moodle versions, at least 3.9 (current LTS), 4.0 (next LTS), and latest moodle (4.2)
