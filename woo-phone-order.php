<?php
/**
 * Plugin Name: Phone Order for WooCommerce
 * Plugin URI:  https://openwpclub.com/plugins/phone-order-for-woocommerce/
 * Description: Fast order creation with just a phone number for WooCommerce. Modern WordPress 6.9+ compatible plugin with Gutenberg blocks and Interactivity API.
 * Author:      OpenWPClub.com
 * Author URI:  https://openwpclub.com/
 * Version:     2.0.0
 * Text Domain: woocommerce-phone-order
 * Domain Path: /languages/
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * WC requires at least: 9.0
 * WC tested up to: 9.5
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package OpenWPClub\PhoneOrder
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

// Autoloader
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

/**
 * Initialize the plugin
 *
 * @return void
 */
function wc_phone_order_init(): void {
	// Check PHP version
	if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
		add_action( 'admin_notices', function () {
			printf(
				'<div class="error"><p>%s</p></div>',
				esc_html__( 'Phone Order for WooCommerce requires PHP 8.0 or higher. Please update your PHP version.', 'woocommerce-phone-order' )
			);
		} );
		return;
	}

	// Check if composer autoloader exists
	if ( ! class_exists( 'OpenWPClub\PhoneOrder\Plugin' ) ) {
		add_action( 'admin_notices', function () {
			printf(
				'<div class="error"><p>%s</p></div>',
				esc_html__( 'Phone Order for WooCommerce: Composer dependencies are missing. Please run "composer install" in the plugin directory.', 'woocommerce-phone-order' )
			);
		} );
		return;
	}

	// Initialize plugin
	\OpenWPClub\PhoneOrder\Plugin::get_instance( __FILE__ );
}

// Hook initialization
add_action( 'plugins_loaded', 'wc_phone_order_init', 10 );

// Activation hook
register_activation_hook( __FILE__, function () {
	// Set default options
	if ( ! get_option( 'wc_phone_order_settings' ) ) {
		update_option( 'wc_phone_order_settings', [
			'enabled'                => 'yes',
			'display_position'       => 'after_summary',
			'form_title'             => __( 'Order by Phone', 'woocommerce-phone-order' ),
			'form_subtitle'          => __( 'Quick order with just your phone number', 'woocommerce-phone-order' ),
			'form_description'       => __( 'Enter your phone number and we\'ll call you to complete your order', 'woocommerce-phone-order' ),
			'form_button_text'       => __( 'Order Now', 'woocommerce-phone-order' ),
			'out_of_stock_behavior'  => 'hide',
			'enable_analytics'       => 'yes',
			'enable_abilities_api'   => 'yes',
		] );
	}

	// Flush rewrite rules
	flush_rewrite_rules();
} );

// Deactivation hook
register_deactivation_hook( __FILE__, function () {
	// Flush rewrite rules
	flush_rewrite_rules();
} );
