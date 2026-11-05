<?php
/**
 * WooCommerce REST API keys.
 *
 * @package AnythingMCP_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates WooCommerce REST API keys the way WooCommerce itself does.
 */
class AMCP_WC_API_Keys {

	/** Permission levels a key can have, as WooCommerce names them. */
	const PERMISSIONS = array( 'read', 'read_write' );

	/**
	 * Human-readable name of a permission level, as WooCommerce shows it.
	 *
	 * @param string $permissions Permission level.
	 * @return string
	 */
	public static function permission_label( $permissions ) {
		return 'read_write' === $permissions
			? __( 'Read/Write', 'anythingmcp-for-woocommerce' )
			: __( 'Read', 'anythingmcp-for-woocommerce' );
	}

	/**
	 * Create a REST API key for a user.
	 *
	 * Same storage as WooCommerce's "Add key" (WC_AJAX::update_api_key) and its
	 * app authentication (WC_Auth::create_keys): a random ck_ key and cs_
	 * secret; the table keeps a hash of the key, the secret and the last seven
	 * characters of the key. The plain key is returned once and kept nowhere.
	 *
	 * @param int    $user_id     Owner of the key. WooCommerce checks this user's capabilities on every request.
	 * @param string $permissions 'read' or 'read_write'.
	 * @return array|WP_Error Array with consumer_key, consumer_secret, key_id and description.
	 */
	public static function create( $user_id, $permissions ) {
		global $wpdb;

		if ( ! in_array( $permissions, self::PERMISSIONS, true ) ) {
			return new WP_Error( 'amcp_wc_invalid_permissions', __( 'Unknown permission level.', 'anythingmcp-for-woocommerce' ) );
		}
		if ( ! function_exists( 'wc_rand_hash' ) || ! function_exists( 'wc_api_hash' ) ) {
			return new WP_Error( 'amcp_wc_no_woocommerce', __( 'WooCommerce is not active.', 'anythingmcp-for-woocommerce' ) );
		}

		$consumer_key    = 'ck_' . wc_rand_hash();
		$consumer_secret = 'cs_' . wc_rand_hash();
		// Same name in every language: it labels the key in WooCommerce's list of REST API keys.
		$description = 'AnythingMCP (' . wp_date( 'Y-m-d H:i' ) . ')';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- WooCommerce has no API to create a key; it writes this table directly in the same way.
		$inserted = $wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => (int) $user_id,
				'description'     => $description,
				'permissions'     => $permissions,
				'consumer_key'    => wc_api_hash( $consumer_key ),
				'consumer_secret' => $consumer_secret,
				'truncated_key'   => substr( $consumer_key, -7 ),
			),
			array( '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( ! $inserted ) {
			return new WP_Error( 'amcp_wc_insert_failed', __( 'The API key could not be saved.', 'anythingmcp-for-woocommerce' ) );
		}

		return array(
			'key_id'          => (int) $wpdb->insert_id,
			'consumer_key'    => $consumer_key,
			'consumer_secret' => $consumer_secret,
			'description'     => $description,
		);
	}
}
