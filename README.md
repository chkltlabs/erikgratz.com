# Erik Gratz
_Occasional Dungeon Master, Full-Time Code Ninja_
***
Hi Ho, and Well Met, Adventurer! 
You have unearthed the ancient tome of code for my website, 
[ErikGratz.com](http://www.erikgratz.com).

It's a bit of a playground where I test out my ideas.

Bit of extra API here, a bit of experiments in websockets there, might even find a game or two if you snoop hard enough.

Either way, welcome, and I hope you find what you seek. 

_Oh, and try not to disturb the mimics._

|Twitter|Linkedin|Email|
|---|---|---|
|[chkltlabs](https://twitter.com/chkltlabs)|[Erik](https://www.linkedin.com/in/erik-gratz-126ba410b/)|[erikgratz110](mailto:erikgratz110@gmail.com)

## Docker Sail runtime

This project pulls the `laravel.test` image from GHCR by default instead of rebuilding Sail every time.

- Configure runtime source with `.env` values:
  - `SAIL_RUNTIME_IMAGE` (default: `ghcr.io/erikgratz/erikgratz.com/sail-8.4`)
  - `SAIL_RUNTIME_TAG` (default: `latest`)
- Standard flow:
  - `docker compose pull laravel.test`
  - `docker compose up -d`

If you are offline or debugging the runtime itself, create a local `docker-compose.override.yml` (already gitignored) to re-enable local Sail builds:

```yaml
services:
  laravel.test:
    build:
      context: ./vendor/laravel/sail/runtimes/8.4
      dockerfile: Dockerfile
    image: sail-8.4/app
```

### Rebuild or update the upstream Sail image

The GHCR image is rebuilt in CI when the Sail runtime content changes under `vendor/laravel/sail/runtimes/8.4`.

- To refresh from upstream Sail:
  - `composer update laravel/sail`
  - Commit the updated `composer.lock`
  - Push to `main`/`master` (or open a PR and merge)
- CI will:
  - Recompute the runtime fingerprint
  - Build and push `ghcr.io/chkltlabs/erikgratz.com/sail-8.4:sail-<fingerprint>`
  - Retag `:latest` on `main`/`master`

To manually rebuild/publish (outside CI), run from repo root after `composer install`:

```bash
RUNTIME_HASH=$(tar --sort=name --mtime='UTC 1970-01-01' --owner=0 --group=0 --numeric-owner -cf - -C vendor/laravel/sail/runtimes/8.4 . | shasum -a 256 | awk '{print substr($1,1,12)}')
IMAGE=ghcr.io/chkltlabs/erikgratz.com/sail-8.4

docker login ghcr.io
docker buildx build \
  --platform linux/amd64,linux/arm64 \
  --push \
  --provenance=false \
  -t "${IMAGE}:sail-${RUNTIME_HASH}" \
  --cache-from "type=registry,ref=${IMAGE}:buildcache" \
  --cache-to "type=registry,ref=${IMAGE}:buildcache,mode=max" \
  vendor/laravel/sail/runtimes/8.4
docker buildx imagetools create \
  --tag "${IMAGE}:latest" \
  "${IMAGE}:sail-${RUNTIME_HASH}"
```

Old fingerprint tags are pruned automatically by [`.github/workflows/ghcr-cleanup.yml`](.github/workflows/ghcr-cleanup.yml), which keeps the newest 3 `sail-*` tags and removes older ones on a weekly schedule (or manual dispatch).
