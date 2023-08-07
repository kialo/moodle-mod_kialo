# Plugin Development Guide

## Prerequisites

* Git
* PHP composer (https://getcomposer.org/)
* Docker (https://www.docker.com/)

## Development Setup

First, check out this repository and install the PHP dependencies.

```shell
git clone git@github.com:kialo/moodle-plugin.git
cd moodle-plugin
composer install
```

## Run Moodle locally

This starts Moodle locally on port 8080 with MariaDB running on port 3366.
This is using non-default ports to avoid conflicts with already running services.
It also starts the hosted version of the Moodle app on port 8100.

It locally mounts moodle in the folder `moodle`. To test changes to the plugin code, 
you can use `development/sync.sh` to copy over the code into the `moodle/mod/kialo` folder.

```shell
cd development
docker compose up
```

Afterward, see `/development/config/README.md` for steps to apply default settings that are useful for development.

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

## TODOs

* go through the plugin checklist linked above
* the plugin content should be at the root of the repo, not its own folder
* the git repo should be called `moodle-mod_kialo`
