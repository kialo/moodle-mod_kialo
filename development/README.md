# Development Setup

## Run Moodle locally

This starts Moodle locally on port 88 with MariaDB running on port 3366.
This is using non-default ports to avoid conflicts with already running services.

It locally mounts moodle in the folder `moodle`. You can copy the plugin into the `moodle/mod` folder,
to test changes.

```shell
docker compose up
```
