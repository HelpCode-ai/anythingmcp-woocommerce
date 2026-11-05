#!/usr/bin/env bash
# Run WP-CLI against the dev site: dev/wp.sh plugin list
exec docker compose -f "$(dirname "$0")/docker-compose.yml" run --rm -T cli wp "$@" 2> >(grep -vE '^ ?Container ' >&2)
