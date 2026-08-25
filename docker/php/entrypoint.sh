#!/bin/bash
set -e

# api and worker share the same bind-mounted project directory and both boot
# from this entrypoint. Cross-container file locking (flock on a shared bind
# mount) turned out not to reliably serialize them on Docker Desktop, so
# instead only api runs first-boot setup (composer install, .env writes,
# migrations); worker just waits for api's "done" marker rather than racing
# on the same work itself.
READY_MARKER=/var/www/html/storage/framework/cache/data/.setup-complete

if [ "$CONTAINER_ROLE" = "worker" ]; then
    echo "Waiting for the api container to finish first-boot setup..."
    until [ -f "$READY_MARKER" ]; do
        sleep 1
    done
else
    rm -f "$READY_MARKER"
    /usr/local/bin/setup.sh
    touch "$READY_MARKER"
fi

# Run php-fpm in the background; nginx (the CMD) becomes the container's foreground process.
php-fpm -D

exec "$@"
