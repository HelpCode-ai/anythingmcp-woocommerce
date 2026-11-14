<?php
/**
 * Plugin Name:          AnythingMCP for WooCommerce
 * Plugin URI:           https://anythingmcp.com/marketplace/woocommerce
 * Description:          Connect your store to Claude, ChatGPT, Microsoft Copilot and Gemini through AnythingMCP. Creates a WooCommerce REST API key and opens the AnythingMCP setup with your store address filled in.
 * Version:              1.0.0
 * Requires at least:    6.5
 * Requires PHP:         7.4
 * Requires Plugins:     woocommerce
 * Author:               helpcode.ai GmbH
 * Author URI:           https://helpcode.ai
 * License:              GPL-2.0-or-later
 * License URI:          https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:          anythingmcp-for-woocommerce
 * Domain Path:          /languages
 * WC requires at least: 8.0
 * WC tested up to:      11.3
 *
 * @package AnythingMCP_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

define( 'AMCP_WC_VERSION', '1.0.0' );
define( 'AMCP_WC_FILE', __FILE__ );
define( 'AMCP_WC_DIR', plugin_dir_path( __FILE__ ) );
define( 'AMCP_WC_URL', plugin_dir_url( __FILE__ ) );

/*
 * AnythingMCP Cloud. Self-hosted AnythingMCP users point the plugin at their
 * own instance in the "Self-hosted AnythingMCP URL" setting.
 */
define( 'AMCP_WC_DEFAULT_ANYTHINGMCP_URL', 'https://cloud.anythingmcp.com' );

/* The AnythingMCP catalog connector this plugin sets up. */
define( 'AMCP_WC_CONNECTOR_SLUG', 'woocommerce' );

require_once AMCP_WC_DIR . 'includes/functions.php';
require_once AMCP_WC_DIR . 'includes/class-amcp-wc-api-keys.php';

/*
 * The plugin only adds an admin screen and touches no orders, carts or
 * checkout, so it works with HPOS and with the block-based cart and checkout.
 */
add_action( 'before_woocommerce_init', 'amcp_wc_declare_compatibility' );
add_filter( 'woocommerce_get_settings_pages', 'amcp_wc_add_settings_page' );
add_action( 'wp_ajax_amcp_wc_create_key', 'amcp_wc_ajax_create_key' );
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'amcp_wc_plugin_action_links' );
