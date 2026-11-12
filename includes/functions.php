<?php
/**
 * Hooks and helpers.
 *
 * @package AnythingMCP_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/** Option that holds the address of a self-hosted AnythingMCP. Empty means AnythingMCP Cloud. */
const AMCP_WC_URL_OPTION = 'amcp_wc_anythingmcp_url';

/** The WooCommerce settings tab of this plugin. */
const AMCP_WC_TAB = 'anythingmcp';

/**
 * Declare compatibility with WooCommerce features.
 */
function amcp_wc_declare_compatibility() {
	if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', AMCP_WC_FILE, true );
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', AMCP_WC_FILE, true );
	}
}

/**
 * Add the "AI Assistants" tab to WooCommerce → Settings.
 *
 * @param WC_Settings_Page[] $pages Settings pages.
 * @return WC_Settings_Page[]
 */
function amcp_wc_add_settings_page( $pages ) {
	require_once AMCP_WC_DIR . 'includes/class-amcp-wc-settings-page.php';
	$pages[] = new AMCP_WC_Settings_Page();
	return $pages;
}

/**
 * Link to the settings tab from the Plugins screen.
 *
 * @param string[] $links Action links.
 * @return string[]
 */
function amcp_wc_plugin_action_links( $links ) {
	if ( current_user_can( 'manage_woocommerce' ) ) {
		array_unshift(
			$links,
			sprintf(
				'<a href="%s">%s</a>',
				esc_url( amcp_wc_settings_url() ),
				esc_html__( 'Settings', 'anythingmcp-for-woocommerce' )
			)
		);
	}
	return $links;
}

/**
 * Address of the plugin's settings tab.
 *
 * @return string
 */
function amcp_wc_settings_url() {
	return admin_url( 'admin.php?page=wc-settings&tab=' . AMCP_WC_TAB );
}

/**
 * Address of WooCommerce's own list of REST API keys, where keys are revoked.
 *
 * @return string
 */
function amcp_wc_keys_url() {
	return admin_url( 'admin.php?page=wc-settings&tab=advanced&section=keys' );
}

/**
 * The store address AnythingMCP needs: the site root, without a trailing slash.
 *
 * @return string
 */
function amcp_wc_store_url() {
	return untrailingslashit( home_url() );
}

/**
 * The AnythingMCP instance to open: the self-hosted address, or AnythingMCP Cloud.
 *
 * @return string
 */
function amcp_wc_anythingmcp_url() {
	$url = get_option( AMCP_WC_URL_OPTION, '' );
	if ( ! is_string( $url ) || '' === $url ) {
		$url = AMCP_WC_DEFAULT_ANYTHINGMCP_URL;
	}
	return untrailingslashit( $url );
}

/**
 * Link to the AnythingMCP setup of the WooCommerce connector.
 *
 * Only the store address goes into the link. The consumer key and secret are
 * never part of it: the user pastes them on the AnythingMCP page, which reads
 * no credential from a link.
 *
 * @return string
 */
function amcp_wc_setup_url() {
	return amcp_wc_anythingmcp_url()
		. '/connectors/setup/' . AMCP_WC_CONNECTOR_SLUG
		. '?prefill.WOOCOMMERCE_URL=' . rawurlencode( amcp_wc_store_url() );
}

/**
 * Check an address for a self-hosted AnythingMCP.
 *
 * @param string $value Address as entered.
 * @return string|null The clean address without a trailing slash, '' for none, or null when it is not valid.
 */
function amcp_wc_clean_anythingmcp_url( $value ) {
	$value = trim( (string) $value );
	if ( '' === $value ) {
		return '';
	}
	$url   = esc_url_raw( $value, array( 'https' ) );
	$parts = wp_parse_url( $url );
	if (
		'' === $url
		|| ! is_array( $parts )
		|| empty( $parts['scheme'] ) || 'https' !== strtolower( $parts['scheme'] )
		|| empty( $parts['host'] )
		|| isset( $parts['user'] ) || isset( $parts['pass'] )
		|| isset( $parts['query'] ) || isset( $parts['fragment'] )
	) {
		return null;
	}
	return untrailingslashit( $url );
}

/**
 * AJAX: create a WooCommerce REST API key for the current user.
 *
 * Returns the consumer key and secret once, for the page to show. Neither is
 * stored in plain text nor logged.
 */
function amcp_wc_ajax_create_key() {
	// The response carries the secret: never answer a GET, whose URL may end up in logs.
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
		wp_send_json_error( array( 'message' => __( 'Use a POST request.', 'anythingmcp-for-woocommerce' ) ), 405 );
	}
	check_ajax_referer( 'amcp_wc_create_key', 'nonce' );

	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_send_json_error(
			array( 'message' => __( 'You are not allowed to create API keys.', 'anythingmcp-for-woocommerce' ) ),
			403
		);
	}

	$permissions = isset( $_POST['permissions'] ) ? sanitize_key( wp_unslash( $_POST['permissions'] ) ) : 'read';
	if ( ! in_array( $permissions, AMCP_WC_API_Keys::PERMISSIONS, true ) ) {
		$permissions = 'read';
	}

	$key = AMCP_WC_API_Keys::create( get_current_user_id(), $permissions );
	if ( is_wp_error( $key ) ) {
		wp_send_json_error( array( 'message' => $key->get_error_message() ), 500 );
	}

	wp_send_json_success(
		array(
			'consumer_key'    => $key['consumer_key'],
			'consumer_secret' => $key['consumer_secret'],
			'description'     => $key['description'],
			'permissions'     => AMCP_WC_API_Keys::permission_label( $permissions ),
		)
	);
}
