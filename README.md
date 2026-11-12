# AnythingMCP for WooCommerce

Use a WooCommerce store in Claude, ChatGPT, Copilot and Gemini. This WordPress plugin adds an
**AI Assistants** tab to *WooCommerce → Settings* that connects the store to
[AnythingMCP](https://anythingmcp.com) in three steps:

1. **Create API key** creates a WooCommerce REST API key (Read by default, or Read/Write) for the
   current user, stored the same way as WooCommerce's own *Add key*, and shows key and secret once.
2. **Open AnythingMCP** opens `<AnythingMCP>/connectors/setup/woocommerce?prefill.WOOCOMMERCE_URL=<store>`.
   Only the store address is in the link; the user pastes key and secret on the AnythingMCP page.
3. AnythingMCP checks the key against the store, then shows how to add it to the assistant.

The plugin makes no remote requests and stores one option (`amcp_wc_anythingmcp_url`, the address of
a self-hosted AnythingMCP; empty means `https://cloud.anythingmcp.com`).

The WordPress.org description is in [readme.txt](readme.txt).

## Development

Requires Docker. The test site runs WordPress, WooCommerce and Plugin Check on
`https://localhost:8443` (self-signed certificate, `admin` / `admin`), with the built plugin mounted
read-only.

```bash
dev/setup.sh              # build the plugin and start the site
dev/functional-test.sh    # API keys, REST access, permissions, settings, uninstall
dev/wp.sh plugin check anythingmcp-for-woocommerce --require=./wp-content/plugins/plugin-check/cli.php
docker compose -f dev/docker-compose.yml down -v   # stop and delete everything
```

`dev/build.sh` writes `dist/anythingmcp-for-woocommerce/` and
`dist/anythingmcp-for-woocommerce-<version>.zip` with the files `.distignore` leaves in. Run it again
after every change; the site uses `dist/`.

Coding standards: `phpcs` with WordPress Coding Standards 3 and PHPCompatibilityWP
(`phpcs.xml.dist`).

### Translations

`languages/anythingmcp-for-woocommerce.pot` comes from
`wp i18n make-pot . languages/anythingmcp-for-woocommerce.pot --exclude=dev,dist,.github,.wordpress-org,vendor`.
Translations are managed on translate.wordpress.org; the plugin ships no `.po`/`.mo` files and
WordPress loads the language packs itself. `dev/translations/` keeps the German, Italian, Spanish
and French `.po` files written before the first release, to import there once the plugin is live.

## Release

Set the version in `anythingmcp-for-woocommerce.php` (header and `AMCP_WC_VERSION`) and
`Stable tag` in `readme.txt`, add a changelog entry, then push a tag with the same version
(`1.0.1`). `.github/workflows/deploy.yml` publishes it to WordPress.org SVN with
`10up/action-wordpress-plugin-deploy`; `.wordpress-org/` holds the icons, banners and screenshots.
The repository needs the secrets `SVN_USERNAME` and `SVN_PASSWORD`.

## License

GPL-2.0-or-later. Made by helpcode.ai GmbH, Freiburg, Germany. Support: support@anythingmcp.com
