<?php
/**
 * WooCommerce → Settings → AI Assistants.
 *
 * @package AnythingMCP_For_WooCommerce
 */

defined( 'ABSPATH' ) || exit;

/**
 * The "AI Assistants" settings tab.
 */
class AMCP_WC_Settings_Page extends WC_Settings_Page {

	/**
	 * Register the tab.
	 */
	public function __construct() {
		$this->id    = AMCP_WC_TAB;
		$this->label = __( 'AI Assistants', 'anythingmcp-for-woocommerce' );

		add_filter( 'woocommerce_admin_settings_sanitize_option_' . AMCP_WC_URL_OPTION, array( $this, 'sanitize_anythingmcp_url' ) );

		parent::__construct();
	}

	/**
	 * The one setting of the tab: the address of a self-hosted AnythingMCP.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		return array(
			array(
				'title' => __( 'Self-hosted AnythingMCP', 'anythingmcp-for-woocommerce' ),
				'type'  => 'title',
				'desc'  => __( 'AnythingMCP is open source. If you run your own instance, enter its address here. Leave it empty to use AnythingMCP Cloud.', 'anythingmcp-for-woocommerce' ),
				'id'    => 'amcp_wc_self_hosted',
			),
			array(
				'title'       => __( 'Self-hosted AnythingMCP URL', 'anythingmcp-for-woocommerce' ),
				'id'          => AMCP_WC_URL_OPTION,
				'type'        => 'url',
				'default'     => '',
				'placeholder' => AMCP_WC_DEFAULT_ANYTHINGMCP_URL,
				'desc'        => __( 'An https:// address, for example https://mcp.example.com.', 'anythingmcp-for-woocommerce' ),
				'css'         => 'min-width: 360px;',
				'autoload'    => false,
			),
			array(
				'type' => 'sectionend',
				'id'   => 'amcp_wc_self_hosted',
			),
		);
	}

	/**
	 * Accept only an https:// address for a self-hosted AnythingMCP.
	 *
	 * @param mixed $value Value from the form, already passed through wc_clean().
	 * @return string
	 */
	public function sanitize_anythingmcp_url( $value ) {
		$clean = amcp_wc_clean_anythingmcp_url( is_string( $value ) ? $value : '' );
		if ( null === $clean ) {
			WC_Admin_Settings::add_error( __( 'The self-hosted AnythingMCP URL must be an https:// address. The previous value was kept.', 'anythingmcp-for-woocommerce' ) );
			$previous = get_option( AMCP_WC_URL_OPTION, '' );
			return is_string( $previous ) ? $previous : '';
		}
		return $clean;
	}

	/**
	 * Show the tab: the connection panel, then the setting.
	 */
	public function output() {
		$this->enqueue_assets();
		$this->output_panel();
		parent::output();
	}

	/**
	 * Load the tab's script and styles. Both ship with the plugin.
	 */
	private function enqueue_assets() {
		wp_enqueue_style( 'amcp-wc-admin', AMCP_WC_URL . 'assets/css/admin.css', array(), AMCP_WC_VERSION );
		wp_enqueue_script( 'amcp-wc-admin', AMCP_WC_URL . 'assets/js/admin.js', array(), AMCP_WC_VERSION, true );
		wp_localize_script(
			'amcp-wc-admin',
			'amcpWcAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'amcp_wc_create_key' ),
				'i18n'    => array(
					'copied'     => __( 'Copied', 'anythingmcp-for-woocommerce' ),
					'copyFailed' => __( 'Select the text and copy it by hand.', 'anythingmcp-for-woocommerce' ),
					'error'      => __( 'The API key could not be created. Reload the page and try again.', 'anythingmcp-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * The connection panel: what this does, the store details, the two steps.
	 */
	private function output_panel() {
		$store_url  = amcp_wc_store_url();
		$is_https   = 0 === strpos( $store_url, 'https://' );
		$plain_urls = '' === (string) get_option( 'permalink_structure' );
		$wc_version = defined( 'WC_VERSION' ) ? WC_VERSION : '';
		?>
		<div class="amcp-wc">
			<h2><?php esc_html_e( 'Use your store in Claude, ChatGPT, Copilot and Gemini', 'anythingmcp-for-woocommerce' ); ?></h2>
			<p class="amcp-wc-lead">
				<?php esc_html_e( 'AnythingMCP connects your store to AI assistants such as Claude, ChatGPT, Microsoft Copilot and Gemini. Ask about orders, products, stock and customers in plain language.', 'anythingmcp-for-woocommerce' ); ?>
			</p>
			<ul class="amcp-wc-points">
				<li><?php esc_html_e( 'Your data stays behind your own WooCommerce API key. You can revoke it at any time.', 'anythingmcp-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'Read-only by default: the assistant can look things up but not change them.', 'anythingmcp-for-woocommerce' ); ?></li>
				<li><?php esc_html_e( 'This plugin sends nothing by itself. Your browser opens AnythingMCP only when you click the button below.', 'anythingmcp-for-woocommerce' ); ?></li>
			</ul>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Store address', 'anythingmcp-for-woocommerce' ); ?></th>
					<td>
						<code id="amcp-wc-store-url"><?php echo esc_html( $store_url ); ?></code>
						<?php if ( ! $is_https ) : ?>
							<p class="amcp-wc-warning"><?php esc_html_e( 'AnythingMCP connects only to stores on https://. Set up HTTPS for this site first.', 'anythingmcp-for-woocommerce' ); ?></p>
						<?php endif; ?>
						<?php if ( $plain_urls ) : ?>
							<p class="amcp-wc-warning">
								<?php esc_html_e( 'The REST API needs pretty permalinks. Choose any structure other than "Plain" under Settings → Permalinks.', 'anythingmcp-for-woocommerce' ); ?>
							</p>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'WooCommerce version', 'anythingmcp-for-woocommerce' ); ?></th>
					<td><?php echo esc_html( $wc_version ); ?></td>
				</tr>
			</table>

			<h3><?php esc_html_e( '1. Create an API key', 'anythingmcp-for-woocommerce' ); ?></h3>
			<p>
				<?php esc_html_e( 'The key belongs to your user and can do what your user can do, limited by the permission you choose.', 'anythingmcp-for-woocommerce' ); ?>
			</p>
			<div id="amcp-wc-create">
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="amcp-wc-permissions"><?php esc_html_e( 'Permissions', 'anythingmcp-for-woocommerce' ); ?></label></th>
						<td>
							<select id="amcp-wc-permissions">
								<option value="read" selected="selected"><?php echo esc_html( AMCP_WC_API_Keys::permission_label( 'read' ) ); ?></option>
								<option value="read_write"><?php echo esc_html( AMCP_WC_API_Keys::permission_label( 'read_write' ) ); ?></option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Read: the assistant can look up orders, products, customers and reports. Read/Write: it can also change prices, stock and orders, and create products and coupons.', 'anythingmcp-for-woocommerce' ); ?>
							</p>
						</td>
					</tr>
				</table>
				<p>
					<button type="button" class="button button-secondary" id="amcp-wc-create-key">
						<?php esc_html_e( 'Create API key', 'anythingmcp-for-woocommerce' ); ?>
					</button>
					<span class="spinner" id="amcp-wc-spinner"></span>
				</p>
				<div class="notice notice-error inline" id="amcp-wc-error" hidden><p></p></div>
			</div>

			<div id="amcp-wc-key" hidden>
				<div class="notice notice-success inline">
					<p>
						<strong><?php esc_html_e( 'API key generated successfully.', 'anythingmcp-for-woocommerce' ); ?></strong>
						<?php esc_html_e( 'Make sure to copy your new keys now as the secret key will be hidden once you leave this page.', 'anythingmcp-for-woocommerce' ); ?>
					</p>
				</div>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Description', 'anythingmcp-for-woocommerce' ); ?></th>
						<td><span id="amcp-wc-key-description"></span> (<span id="amcp-wc-key-permissions"></span>)</td>
					</tr>
					<tr>
						<th scope="row"><label for="amcp-wc-consumer-key"><?php esc_html_e( 'Consumer key', 'anythingmcp-for-woocommerce' ); ?></label></th>
						<td>
							<input type="text" id="amcp-wc-consumer-key" class="regular-text code" readonly="readonly" autocomplete="off" spellcheck="false" />
							<button type="button" class="button amcp-wc-copy" data-target="amcp-wc-consumer-key"><?php esc_html_e( 'Copy', 'anythingmcp-for-woocommerce' ); ?></button>
							<span class="amcp-wc-copied" aria-live="polite"></span>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="amcp-wc-consumer-secret"><?php esc_html_e( 'Consumer secret', 'anythingmcp-for-woocommerce' ); ?></label></th>
						<td>
							<input type="text" id="amcp-wc-consumer-secret" class="regular-text code" readonly="readonly" autocomplete="off" spellcheck="false" />
							<button type="button" class="button amcp-wc-copy" data-target="amcp-wc-consumer-secret"><?php esc_html_e( 'Copy', 'anythingmcp-for-woocommerce' ); ?></button>
							<span class="amcp-wc-copied" aria-live="polite"></span>
						</td>
					</tr>
				</table>
			</div>

			<h3><?php esc_html_e( '2. Open AnythingMCP', 'anythingmcp-for-woocommerce' ); ?></h3>
			<p>
				<?php esc_html_e( 'Your store address is already filled in. Paste the consumer key and secret there and click "Check and install". AnythingMCP then shows how to add your store to Claude, ChatGPT, Copilot or Gemini.', 'anythingmcp-for-woocommerce' ); ?>
			</p>
			<p>
				<a class="button button-primary" id="amcp-wc-open" href="<?php echo esc_url( amcp_wc_setup_url() ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Open AnythingMCP', 'anythingmcp-for-woocommerce' ); ?>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'anythingmcp-for-woocommerce' ); ?></span>
				</a>
			</p>
			<p class="amcp-wc-links">
				<a href="https://anythingmcp.com/marketplace/woocommerce" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Setup guide', 'anythingmcp-for-woocommerce' ); ?></a>
				<span aria-hidden="true">|</span>
				<a href="<?php echo esc_url( amcp_wc_keys_url() ); ?>"><?php esc_html_e( 'Manage or revoke API keys (Advanced → REST API)', 'anythingmcp-for-woocommerce' ); ?></a>
			</p>
		</div>
		<?php
	}
}
