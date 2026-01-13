<?php
/**
 * AJAX Handler for Phone Orders
 *
 * @package OpenWPClub\PhoneOrder
 * @since 2.0.0
 */

declare(strict_types=1);

namespace OpenWPClub\PhoneOrder\Frontend;

use OpenWPClub\PhoneOrder\Settings\Settings;
use WC_Product;
use WP_User;
use Exception;

/**
 * AJAX Handler Class
 */
final class AjaxHandler {
	/**
	 * Instance
	 *
	 * @var AjaxHandler|null
	 */
	private static ?AjaxHandler $instance = null;

	/**
	 * Settings instance
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Get instance
	 *
	 * @return AjaxHandler
	 */
	public static function get_instance(): AjaxHandler {
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
		add_action( 'wp_ajax_wc_phone_order_submit', array( $this, 'handle_submit' ) );
		add_action( 'wp_ajax_nopriv_wc_phone_order_submit', array( $this, 'handle_submit' ) );
	}

	/**
	 * Handle form submission.
	 *
	 * @throws Exception When order creation fails.
	 * @return void
	 */
	public function handle_submit(): void {
		try {
			// Verify nonce.
			check_ajax_referer( 'wc-phone-order-nonce', 'nonce' );

			// Get and validate inputs.
			$phone      = $this->get_phone_from_request();
			$product_id = $this->get_product_id_from_request();

			// Validate phone.
			if ( ! $this->validate_phone( $phone ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Please enter a valid phone number', 'woocommerce-phone-order' ),
					)
				);
			}

			// Get and validate product.
			$product = wc_get_product( $product_id );
			if ( ! $product instanceof WC_Product ) {
				wp_send_json_error(
					array(
						'message' => __( 'Invalid product', 'woocommerce-phone-order' ),
					)
				);
			}

			if ( ! $product->is_purchasable() ) {
				wp_send_json_error(
					array(
						'message' => __( 'This product cannot be purchased', 'woocommerce-phone-order' ),
					)
				);
			}

			// Check stock.
			if ( ! $product->is_in_stock() ) {
				wp_send_json_error(
					array(
						'message' => __( 'This product is currently out of stock', 'woocommerce-phone-order' ),
					)
				);
			}

			// Create or get customer.
			$customer_id = $this->get_or_create_customer( $phone );

			// Create order.
			$order = wc_create_order(
				array(
					'customer_id' => $customer_id,
					'created_via' => 'phone_order',
				)
			);

			if ( ! $order ) {
				throw new Exception( __( 'Failed to create order', 'woocommerce-phone-order' ) );
			}

			// Add product to order.
			$order->add_product( $product, 1 );

			// Set order details.
			$order->set_billing_phone( $phone );
			$order->set_status( 'processing' );
			$order->set_payment_method( 'phone_order' );
			$order->set_payment_method_title( __( 'Phone Order', 'woocommerce-phone-order' ) );
			$order->calculate_totals();
			$order->save();

			// Add order note.
			$order->add_order_note(
				__( 'Order placed via Phone Order form', 'woocommerce-phone-order' )
			);

			// Reduce stock.
			wc_maybe_reduce_stock_levels( $order->get_id() );

			// Track analytics.
			$this->track_order_analytics( $order->get_id(), $phone, $product_id );

			// Fire action hook.
			do_action( 'wc_phone_order_created', $order->get_id(), $phone, $product_id );

			wp_send_json_success(
				array(
					'message'  => __( 'Thank you! Your order has been placed. We\'ll contact you shortly to confirm.', 'woocommerce-phone-order' ),
					'order_id' => $order->get_id(),
				)
			);

		} catch ( Exception $e ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Intentional error logging for debugging.
			error_log( 'Phone Order Error: ' . $e->getMessage() );
			wp_send_json_error(
				array(
					'message' => __( 'An error occurred. Please try again or contact us directly.', 'woocommerce-phone-order' ),
				)
			);
		}
	}

	/**
	 * Get phone from request.
	 *
	 * @return string
	 */
	private function get_phone_from_request(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_submit().
		$phone = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
		return $phone;
	}

	/**
	 * Get product ID from request.
	 *
	 * @return int
	 */
	private function get_product_id_from_request(): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in handle_submit().
		return isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
	}

	/**
	 * Validate phone number
	 *
	 * @param string $phone Phone number.
	 * @return bool
	 */
	private function validate_phone( string $phone ): bool {
		if ( empty( $phone ) ) {
			return false;
		}

		$length = strlen( $phone );
		if ( $length < 5 || $length > 20 ) {
			return false;
		}

		// Allow digits, +, spaces, parentheses, and hyphens.
		return (bool) preg_match( '/^[0-9+\s()-]{5,20}$/', $phone );
	}

	/**
	 * Get or create customer by phone
	 *
	 * @param string $phone Phone number.
	 * @return int Customer ID.
	 * @throws Exception If customer creation fails.
	 */
	private function get_or_create_customer( string $phone ): int {
		// Try to find existing customer.
		$customer_id = $this->find_customer_by_phone( $phone );

		if ( $customer_id > 0 ) {
			return $customer_id;
		}

		// Create new guest customer.
		return $this->create_guest_customer( $phone );
	}

	/**
	 * Find customer by phone number
	 *
	 * @param string $phone Phone number.
	 * @return int Customer ID or 0 if not found.
	 */
	private function find_customer_by_phone( string $phone ): int {
		$cache_key = 'wc_phone_order_customer_' . md5( $phone );
		$user_id   = wp_cache_get( $cache_key, 'wc_phone_order' );

		if ( false !== $user_id ) {
			return absint( $user_id );
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom query with caching above.
		$user_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta}
				WHERE meta_key = 'billing_phone'
				AND meta_value = %s
				LIMIT 1",
				$phone
			)
		);

		$user_id = $user_id ? absint( $user_id ) : 0;
		wp_cache_set( $cache_key, $user_id, 'wc_phone_order', HOUR_IN_SECONDS );

		return $user_id;
	}

	/**
	 * Create guest customer
	 *
	 * @param string $phone Phone number.
	 * @return int Customer ID.
	 * @throws Exception If customer creation fails.
	 */
	private function create_guest_customer( string $phone ): int {
		$clean_phone = preg_replace( '/[^0-9]/', '', $phone );
		$timestamp   = time();

		// Generate unique username.
		$username = 'guest_' . $clean_phone . '_' . $timestamp;

		// Generate unique email.
		$email = $this->generate_unique_email( $clean_phone );

		// Create user.
		$user_id = wp_create_user( $username, wp_generate_password( 32 ), $email );

		if ( is_wp_error( $user_id ) ) {
			throw new Exception( esc_html( $user_id->get_error_message() ) );
		}

		// Set role.
		$user = new WP_User( $user_id );
		$user->set_role( 'customer' );

		// Set billing phone.
		update_user_meta( $user_id, 'billing_phone', $phone );

		return $user_id;
	}

	/**
	 * Generate unique email for guest customer
	 *
	 * @param string $clean_phone Cleaned phone number.
	 * @return string
	 */
	private function generate_unique_email( string $clean_phone ): string {
		$base_email = 'guest_' . $clean_phone . '@phone-order.local';

		if ( ! email_exists( $base_email ) ) {
			return $base_email;
		}

		// Add random suffix if email exists.
		do {
			$random_suffix = wp_generate_password( 6, false );
			$email         = 'guest_' . $clean_phone . '_' . $random_suffix . '@phone-order.local';
		} while ( email_exists( $email ) );

		return $email;
	}

	/**
	 * Track order analytics
	 *
	 * @param int    $order_id Order ID.
	 * @param string $phone Phone number.
	 * @param int    $product_id Product ID.
	 * @return void
	 */
	private function track_order_analytics( int $order_id, string $phone, int $product_id ): void {
		if ( 'yes' !== $this->settings->get( 'enable_analytics', 'yes' ) ) {
			return;
		}

		$analytics = array(
			'order_id'   => $order_id,
			'phone'      => $phone,
			'product_id' => $product_id,
			'created_at' => current_time( 'mysql' ),
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- User agent stored for analytics only.
			'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
			'ip_address' => $this->get_client_ip(),
		);

		// Store in custom table or post meta.
		add_post_meta( $order_id, '_phone_order_analytics', $analytics );

		// Fire analytics hook.
		do_action( 'wc_phone_order_analytics_tracked', $analytics );
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	private function get_client_ip(): string {
		$ip_keys = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);

		foreach ( $ip_keys as $key ) {
			if ( isset( $_SERVER[ $key ] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) );
				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		return '0.0.0.0';
	}
}
