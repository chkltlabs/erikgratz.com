# Docker runtime

The **active** app image is [`8.4/Dockerfile`](8.4/Dockerfile): Debian Bookworm + official `php:8.4-cli`, minimal extensions for this Laravel app (MySQL, Redis/Horizon, Filament-ish stack, PCOV for CI coverage).

Other `docker/8.x/` trees match historical Sail-published layouts and are **not** used by [`docker-compose.yml`](../docker-compose.yml) unless you change the compose `build.context`.

To tighten further, trim PHP extensions or Node in `8.4/Dockerfile` after checking `composer.json` and your queues/cache drivers.
