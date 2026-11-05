#!/usr/bin/env bash
# Start a throwaway WordPress + WooCommerce on https://localhost:8443 with the
# built plugin mounted read-only. Tear down with:
#   docker compose -f dev/docker-compose.yml down -v
set -euo pipefail
dev="$(cd "$(dirname "$0")" && pwd)"
"$dev/build.sh" >/dev/null

mkdir -p "$dev/certs"
if [ ! -f "$dev/certs/localhost.crt" ]; then
  openssl req -x509 -newkey rsa:2048 -nodes -days 30 -subj "/CN=localhost" \
    -addext "subjectAltName=DNS:localhost" \
    -keyout "$dev/certs/localhost.key" -out "$dev/certs/localhost.crt" 2>/dev/null
  chmod 644 "$dev/certs/localhost.key"
fi

compose=(docker compose -f "$dev/docker-compose.yml")
"${compose[@]}" up -d db wordpress
wp() { "${compose[@]}" run --rm -T cli wp "$@"; }

# The WordPress container copies core and writes wp-config.php on its first start.
for _ in $(seq 1 60); do
  curl -sk -o /dev/null https://localhost:8443/ && wp db query "SELECT 1" >/dev/null 2>&1 && break
  sleep 2
done

if ! wp core is-installed 2>/dev/null; then
  wp core install --url=https://localhost:8443 --title="AnythingMCP Test Shop" \
    --admin_user=admin --admin_password=admin --admin_email=admin@example.com --skip-email
fi
wp rewrite structure '/%postname%/' --hard >/dev/null
wp plugin is-installed woocommerce || wp plugin install woocommerce
wp plugin activate woocommerce
wp plugin is-installed plugin-check || wp plugin install plugin-check
wp plugin activate plugin-check
wp user get shopmanager >/dev/null 2>&1 || wp user create shopmanager shopmanager@example.com --role=shop_manager --user_pass=shopmanager
wp user get subscriber >/dev/null 2>&1 || wp user create subscriber subscriber@example.com --role=subscriber --user_pass=subscriber
wp option update woocommerce_coming_soon no >/dev/null
echo "WordPress: https://localhost:8443/wp-admin (admin / admin)"
