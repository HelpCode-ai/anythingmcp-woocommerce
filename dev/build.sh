#!/usr/bin/env bash
# Build the distributable plugin: dist/anythingmcp-for-woocommerce/ and
# dist/anythingmcp-for-woocommerce-<version>.zip, with the files .distignore
# leaves in. Compiles the translations first when msgfmt is available.
set -euo pipefail
shopt -s nullglob
root="$(cd "$(dirname "$0")/.." && pwd)"
slug=anythingmcp-for-woocommerce
version=$(sed -n 's/^ \* Version: *//p' "$root/$slug.php")

if command -v msgfmt >/dev/null; then
  for po in "$root"/languages/*.po; do
    msgfmt --check -o "${po%.po}.mo" "$po"
  done
fi

mkdir -p "$root/dist/$slug"
rsync -a --delete --exclude-from="$root/.distignore" "$root/" "$root/dist/$slug/"
rm -f "$root/dist/$slug-$version.zip"
(cd "$root/dist" && zip -qrX "$slug-$version.zip" "$slug")
echo "dist/$slug-$version.zip"
