<?php
/**
 * Remove the plugin's own setting.
 *
 * WooCommerce REST API keys created with the plugin are not removed: they
 * belong to WooCommerce and may be in use. Revoke them under WooCommerce →
 * Settings → Advanced → REST API.
 *
 * @package AnythingMCP_For_WooCommerce
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $amcp_wc_site_id ) {
		switch_to_blog( $amcp_wc_site_id );
		delete_option( 'amcp_wc_anythingmcp_url' );
		restore_current_blog();
	}
} else {
	delete_option( 'amcp_wc_anythingmcp_url' );
}
