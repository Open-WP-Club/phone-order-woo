<?php
/**
 * Main Plugin Class
 *
 * @package OpenWPClub\PhoneOrder
 * @since 2.0.0
 */

declare(strict_types=1);

namespace OpenWPClub\PhoneOrder;

use OpenWPClub\PhoneOrder\Admin\Dashboard;
use OpenWPClub\PhoneOrder\Admin\Analytics;
use OpenWPClub\PhoneOrder\Frontend\FormRenderer;
use OpenWPClub\PhoneOrder\Frontend\AjaxHandler;
use OpenWPClub\PhoneOrder\Settings\Settings;
use OpenWPClub\PhoneOrder\Blocks\PhoneOrderBlock;
use OpenWPClub\PhoneOrder\API\AbilitiesAPI;

/**
 * Main Plugin Class - Singleton Pattern
 */
final class Plugin {
	/**
	 * Plugin instance
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Plugin version
	 *
	 * @var string
	 */
	public const VERSION = '2.0.0';

	/**
	 * Minimum PHP version
	 *
	 * @var string
	 */
	public const MIN_PHP_VERSION = '8.0';

	/**
	 * Minimum WordPress version
	 *
	 * @var string
	 */
	public const MIN_WP_VERSION = '6.9';

	/**
	 * Minimum WooCommerce version
	 *
	 * @var string
	 */
	public const MIN_WC_VERSION = '9.0';

	/**
	 * Plugin file path
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * Plugin directory path
	 *
	 * @var string
	 */
	private string $plugin_path;

	/**
	 * Plugin URL
	 *
	 * @var string
	 */
	private string $plugin_url;

	/**
	 * Get plugin instance
	 *
	 * @param string $plugin_file Main plugin file path.
	 * @return Plugin
	 */
	public static function get_instance( string $plugin_file ): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self( $plugin_file );
		}
		return self::$instance;
	}

	/**
	 * Constructor - Private to enforce singleton
	 *
	 * @param string $plugin_file Main plugin file path.
	 */
	private function __construct( string $plugin_file ) {
		$this->plugin_file = $plugin_file;
		$this->plugin_path = plugin_dir_path( $plugin_file );
		$this->plugin_url  = plugin_dir_url( $plugin_file );

		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'plugins_loaded', [ $this, 'init_plugin' ], 10 );
		add_action( 'before_woocommerce_init', [ $this, 'declare_hpos_compatibility' ] );
	}

	/**
	 * Initialize plugin
	 *
	 * @return void
	 */
	public function init_plugin(): void {
		// Check requirements
		if ( ! $this->check_requirements() ) {
			return;
		}

		// Load text domain
		load_plugin_textdomain(
			'woocommerce-phone-order',
			false,
			dirname( plugin_basename( $this->plugin_file ) ) . '/languages'
		);

		// Initialize components
		$this->init_components();

		// Hook for other plugins
		do_action( 'wc_phone_order_loaded' );
	}

	/**
	 * Check plugin requirements
	 *
	 * @return bool
	 */
	private function check_requirements(): bool {
		// Check WooCommerce
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', [ $this, 'woocommerce_missing_notice' ] );
			return false;
		}

		// Check WooCommerce version
		if ( version_compare( WC()->version, self::MIN_WC_VERSION, '<' ) ) {
			add_action( 'admin_notices', [ $this, 'woocommerce_version_notice' ] );
			return false;
		}

		return true;
	}

	/**
	 * Initialize plugin components
	 *
	 * @return void
	 */
	private function init_components(): void {
		// Settings
		Settings::get_instance();

		// Frontend components
		FormRenderer::get_instance();
		AjaxHandler::get_instance();

		// Gutenberg block
		PhoneOrderBlock::get_instance();

		// Admin components
		if ( is_admin() ) {
			Dashboard::get_instance();
			Analytics::get_instance();
		}

		// Abilities API
		AbilitiesAPI::get_instance();
	}

	/**
	 * Declare HPOS compatibility
	 *
	 * @return void
	 */
	public function declare_hpos_compatibility(): void {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				$this->plugin_file,
				true
			);
		}
	}

	/**
	 * WooCommerce missing notice
	 *
	 * @return void
	 */
	public function woocommerce_missing_notice(): void {
		$message = sprintf(
			/* translators: %s: WooCommerce link */
			__( 'Phone Order for WooCommerce requires WooCommerce to be installed and active. You can download %s here.', 'woocommerce-phone-order' ),
			'<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>'
		);
		printf( '<div class="error"><p>%s</p></div>', wp_kses_post( $message ) );
	}

	/**
	 * WooCommerce version notice
	 *
	 * @return void
	 */
	public function woocommerce_version_notice(): void {
		$message = sprintf(
			/* translators: %s: Required WooCommerce version */
			__( 'Phone Order for WooCommerce requires WooCommerce version %s or higher.', 'woocommerce-phone-order' ),
			self::MIN_WC_VERSION
		);
		printf( '<div class="error"><p>%s</p></div>', esc_html( $message ) );
	}

	/**
	 * Get plugin file path
	 *
	 * @return string
	 */
	public function get_plugin_file(): string {
		return $this->plugin_file;
	}

	/**
	 * Get plugin directory path
	 *
	 * @return string
	 */
	public function get_plugin_path(): string {
		return $this->plugin_path;
	}

	/**
	 * Get plugin URL
	 *
	 * @return string
	 */
	public function get_plugin_url(): string {
		return $this->plugin_url;
	}

	/**
	 * Prevent cloning
	 *
	 * @return void
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization
	 *
	 * @return void
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize singleton' );
	}
}
