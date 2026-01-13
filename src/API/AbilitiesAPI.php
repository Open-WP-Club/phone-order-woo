<?php
/**
 * Abilities API Integration
 *
 * WordPress 6.9+ Abilities API allows plugins to expose their capabilities
 * to AI agents and automation tools in a standardized format.
 *
 * @package OpenWPClub\PhoneOrder
 * @since 2.0.0
 */

declare(strict_types=1);

namespace OpenWPClub\PhoneOrder\API;

use OpenWPClub\PhoneOrder\Settings\Settings;

/**
 * Abilities API Class
 */
final class AbilitiesAPI {
	/**
	 * Instance
	 *
	 * @var AbilitiesAPI|null
	 */
	private static ?AbilitiesAPI $instance = null;

	/**
	 * Settings instance
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Get instance
	 *
	 * @return AbilitiesAPI
	 */
	public static function get_instance(): AbilitiesAPI {
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
		// WordPress 6.9+ Abilities API filter.
		add_filter( 'wp_abilities_registry', array( $this, 'register_abilities' ) );
	}

	/**
	 * Register plugin abilities with WordPress Abilities API
	 *
	 * @param array<string, mixed> $abilities Existing abilities.
	 * @return array<string, mixed>
	 */
	public function register_abilities( array $abilities ): array {
		// Check if Abilities API is enabled.
		if ( 'yes' !== $this->settings->get( 'enable_abilities_api', 'yes' ) ) {
			return $abilities;
		}

		// Register Phone Order abilities.
		$abilities['openwpclub/phone-order'] = array(
			'name'         => 'Phone Order for WooCommerce',
			'version'      => '2.0.0',
			'description'  => 'Enables quick order creation using just a customer phone number',
			'capabilities' => array(
				array(
					'id'          => 'create_phone_order',
					'name'        => 'Create Phone Order',
					'description' => 'Create a WooCommerce order using a phone number and product ID',
					'type'        => 'action',
					'parameters'  => array(
						array(
							'name'        => 'phone',
							'type'        => 'string',
							'required'    => true,
							'description' => 'Customer phone number (5-20 characters, digits, +, spaces, parentheses, hyphens)',
							'validation'  => array(
								'pattern' => '^[0-9+\s()-]{5,20}$',
							),
						),
						array(
							'name'        => 'product_id',
							'type'        => 'integer',
							'required'    => true,
							'description' => 'WooCommerce product ID to order',
						),
						array(
							'name'        => 'quantity',
							'type'        => 'integer',
							'required'    => false,
							'default'     => 1,
							'description' => 'Quantity to order (default: 1)',
						),
					),
					'returns'     => array(
						'type'        => 'object',
						'description' => 'Order creation result',
						'properties'  => array(
							'success'  => array(
								'type'        => 'boolean',
								'description' => 'Whether order was created successfully',
							),
							'order_id' => array(
								'type'        => 'integer',
								'description' => 'Created order ID',
							),
							'message'  => array(
								'type'        => 'string',
								'description' => 'Result message',
							),
						),
					),
					'endpoint'    => rest_url( 'phone-order/v1/create' ),
					'method'      => 'POST',
				),
				array(
					'id'          => 'check_product_availability',
					'name'        => 'Check Product Availability',
					'description' => 'Check if a product is available for phone order',
					'type'        => 'query',
					'parameters'  => array(
						array(
							'name'        => 'product_id',
							'type'        => 'integer',
							'required'    => true,
							'description' => 'WooCommerce product ID to check',
						),
					),
					'returns'     => array(
						'type'        => 'object',
						'description' => 'Product availability information',
						'properties'  => array(
							'available'    => array(
								'type'        => 'boolean',
								'description' => 'Whether product is available',
							),
							'in_stock'     => array(
								'type'        => 'boolean',
								'description' => 'Whether product is in stock',
							),
							'purchasable'  => array(
								'type'        => 'boolean',
								'description' => 'Whether product is purchasable',
							),
							'product_name' => array(
								'type'        => 'string',
								'description' => 'Product name',
							),
							'price'        => array(
								'type'        => 'string',
								'description' => 'Product price',
							),
						),
					),
					'endpoint'    => rest_url( 'phone-order/v1/check-availability' ),
					'method'      => 'GET',
				),
				array(
					'id'          => 'get_phone_order_stats',
					'name'        => 'Get Phone Order Statistics',
					'description' => 'Retrieve statistics about phone orders',
					'type'        => 'query',
					'parameters'  => array(),
					'returns'     => array(
						'type'        => 'object',
						'description' => 'Phone order statistics',
						'properties'  => array(
							'total_orders'  => array(
								'type'        => 'integer',
								'description' => 'Total number of phone orders',
							),
							'today_orders'  => array(
								'type'        => 'integer',
								'description' => 'Phone orders today',
							),
							'month_orders'  => array(
								'type'        => 'integer',
								'description' => 'Phone orders this month',
							),
							'total_revenue' => array(
								'type'        => 'number',
								'description' => 'Total revenue from phone orders',
							),
						),
					),
					'endpoint'    => rest_url( 'phone-order/v1/stats' ),
					'method'      => 'GET',
					'permission'  => 'manage_woocommerce',
				),
			),
			'examples'     => array(
				array(
					'title'       => 'Create a phone order for product ID 123',
					'description' => 'Example of creating a phone order',
					'capability'  => 'create_phone_order',
					'request'     => array(
						'phone'      => '+1234567890',
						'product_id' => 123,
						'quantity'   => 1,
					),
				),
				array(
					'title'       => 'Check if product is available',
					'description' => 'Example of checking product availability',
					'capability'  => 'check_product_availability',
					'request'     => array(
						'product_id' => 123,
					),
				),
			),
		);

		return $abilities;
	}

	/**
	 * Get plugin abilities schema
	 *
	 * This can be accessed via REST API for external tools
	 *
	 * @return array<string, mixed>
	 */
	public function get_abilities_schema(): array {
		$abilities = $this->register_abilities( array() );
		return $abilities['openwpclub/phone-order'] ?? array();
	}
}
