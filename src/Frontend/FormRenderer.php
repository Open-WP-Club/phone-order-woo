<?php
/**
 * Form Renderer
 *
 * @package OpenWPClub\PhoneOrder
 * @since 2.0.0
 */

declare(strict_types=1);

namespace OpenWPClub\PhoneOrder\Frontend;

use OpenWPClub\PhoneOrder\Settings\Settings;
use OpenWPClub\PhoneOrder\Plugin;
use WC_Product;

/**
 * Form Renderer Class
 */
final class FormRenderer {
	/**
	 * Instance
	 *
	 * @var FormRenderer|null
	 */
	private static ?FormRenderer $instance = null;

	/**
	 * Settings instance
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Get instance
	 *
	 * @return FormRenderer
	 */
	public static function get_instance(): FormRenderer {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->settings = Settings::get_instance();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'init', [ $this, 'setup_display_hooks' ] );
		add_shortcode( 'woo_phone_order', [ $this, 'shortcode_handler' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
	}

	/**
	 * Setup display hooks based on settings
	 *
	 * @return void
	 */
	public function setup_display_hooks(): void {
		$display_position = $this->settings->get( 'display_position', 'after_summary' );

		switch ( $display_position ) {
			case 'after_summary':
				add_action( 'woocommerce_after_single_product_summary', [ $this, 'display_form' ], 11 );
				break;
			case 'after_add_to_cart':
				add_action( 'woocommerce_after_add_to_cart_button', [ $this, 'display_form' ] );
				break;
			case 'disabled':
			default:
				// Don't display automatically
				break;
		}
	}

	/**
	 * Display form on product pages
	 *
	 * @return void
	 */
	public function display_form(): void {
		global $product;

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		$this->render_form( $product->get_id() );
	}

	/**
	 * Shortcode handler
	 *
	 * @param array<string, mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode_handler( $atts ): string {
		$atts = shortcode_atts( [
			'product_id' => 0,
		], $atts, 'woo_phone_order' );

		$product_id = absint( $atts['product_id'] );

		// If no product ID, try global product
		if ( 0 === $product_id ) {
			global $product;
			if ( $product instanceof WC_Product ) {
				$product_id = $product->get_id();
			}
		}

		// If still no product, get latest
		if ( 0 === $product_id ) {
			$products = wc_get_products( [
				'limit'   => 1,
				'orderby' => 'date',
				'order'   => 'DESC',
			] );
			if ( ! empty( $products ) ) {
				$product_id = $products[0]->get_id();
			}
		}

		if ( 0 === $product_id ) {
			return '';
		}

		ob_start();
		$this->render_form( $product_id );
		return ob_get_clean();
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @return void
	 */
	public function enqueue_scripts(): void {
		global $post;

		// Check if we should load scripts
		$should_load = false;

		// Load on product pages
		if ( is_product() ) {
			$should_load = true;
		}

		// Load if shortcode is present
		if ( $post instanceof \WP_Post && has_shortcode( $post->post_content, 'woo_phone_order' ) ) {
			$should_load = true;
		}

		if ( ! $should_load ) {
			return;
		}

		$plugin_url = Plugin::get_instance()->get_plugin_url();

		// Enqueue styles
		wp_enqueue_style(
			'wc-phone-order',
			$plugin_url . 'assets/css/wc-phone-order.css',
			[],
			Plugin::VERSION
		);

		// Enqueue block styles
		wp_enqueue_style(
			'wc-phone-order-block',
			$plugin_url . 'build/phone-order-block/style-index.css',
			[],
			Plugin::VERSION
		);

		// Enqueue simple vanilla JS script (no Interactivity API)
		wp_enqueue_script(
			'wc-phone-order-frontend',
			$plugin_url . 'assets/js/phone-order-frontend.js',
			[],
			Plugin::VERSION,
			true
		);

		// Localize script with params
		wp_localize_script( 'wc-phone-order-frontend', 'wooPhoneOrderParams', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'wc-phone-order-nonce' ),
			'i18n'    => [
				'submitting' => __( 'Submitting...', 'woocommerce-phone-order' ),
				'success'    => __( 'Order placed successfully!', 'woocommerce-phone-order' ),
				'error'      => __( 'An error occurred', 'woocommerce-phone-order' ),
				'emptyPhone' => __( 'Please enter your phone number', 'woocommerce-phone-order' ),
			],
		] );
	}

	/**
	 * Render the phone order form
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	private function render_form( int $product_id ): void {
		$product = wc_get_product( $product_id );

		if ( ! $product instanceof WC_Product || ! $product->is_purchasable() ) {
			return;
		}

		// Get settings
		$form_title             = $this->settings->get( 'form_title', '' );
		$form_subtitle          = $this->settings->get( 'form_subtitle', '' );
		$form_description       = $this->settings->get( 'form_description', '' );
		$button_text            = $this->settings->get( 'form_button_text', __( 'Order Now', 'woocommerce-phone-order' ) );
		$out_of_stock_behavior  = $this->settings->get( 'out_of_stock_behavior', 'hide' );

		$is_in_stock = $product->is_in_stock();

		// Handle out of stock behavior
		if ( ! $is_in_stock && 'hide' === $out_of_stock_behavior ) {
			return;
		}

		$form_class    = 'woo-phone-order';
		$form_disabled = false;

		if ( ! $is_in_stock && 'disabled' === $out_of_stock_behavior ) {
			$form_class   .= ' woo-phone-order--disabled';
			$form_disabled = true;
		}

		// Render form template
		include Plugin::get_instance()->get_plugin_path() . 'templates/phone-order-form.php';
	}
}
