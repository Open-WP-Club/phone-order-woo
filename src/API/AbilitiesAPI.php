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
		// WordPress 6.9+ Abilities API filter
		add_filter( 'wp_abilities_registry', [ $this, 'register_abilities' ] );
	}

	/**
	 * Register plugin abilities with WordPress Abilities API
	 *
	 * @param array<string, mixed> $abilities Existing abilities.
	 * @return array<string, mixed>
	 */
	public function register_abilities( array $abilities ): array {
		// Check if Abilities API is enabled
		if ( 'yes' !== $this->settings->get( 'enable_abilities_api', 'yes' ) ) {
			return $abilities;
		}

		// Register Phone Order abilities
		$abilities['openwpclub/phone-order'] = [
			'name'        => 'Phone Order for WooCommerce',
			'version'     => '2.0.0',
			'description' => 'Enables quick order creation using just a customer phone number',
			'capabilities' => [
				[
					'id'          => 'create_phone_order',
					'name'        => 'Create Phone Order',
					'description' => 'Create a WooCommerce order using a phone number and product ID',
					'type'        => 'action',
					'parameters'  => [
						[
							'name'        => 'phone',
							'type'        => 'string',
							'required'    => true,
							'description' => 'Customer phone number (5-20 characters, digits, +, spaces, parentheses, hyphens)',
							'validation'  => [
								'pattern' => '^[0-9+\s()-]{5,20}$',
							],
						],
						[
							'name'        => 'product_id',
							'type'        => 'integer',
							'required'    => true,
							'description' => 'WooCommerce product ID to order',
						],
						[
							'name'        => 'quantity',
							'type'        => 'integer',
							'required'    => false,
							'default'     => 1,
							'description' => 'Quantity to order (default: 1)',
						],
					],
					'returns'     => [
						'type'        => 'object',
						'description' => 'Order creation result',
						'properties'  => [
							'success'  => [
								'type'        => 'boolean',
								'description' => 'Whether order was created successfully',
							],
							'order_id' => [
								'type'        => 'integer',
								'description' => 'Created order ID',
							],
							'message'  => [
								'type'        => 'string',
								'description' => 'Result message',
							],
						],
					],
					'endpoint'    => rest_url( 'phone-order/v1/create' ),
					'method'      => 'POST',
				],
				[
					'id'          => 'check_product_availability',
					'name'        => 'Check Product Availability',
					'description' => 'Check if a product is available for phone order',
					'type'        => 'query',
					'parameters'  => [
						[
							'name'        => 'product_id',
							'type'        => 'integer',
							'required'    => true,
							'description' => 'WooCommerce product ID to check',
						],
					],
					'returns'     => [
						'type'        => 'object',
						'description' => 'Product availability information',
						'properties'  => [
							'available'    => [
								'type'        => 'boolean',
								'description' => 'Whether product is available',
							],
							'in_stock'     => [
								'type'        => 'boolean',
								'description' => 'Whether product is in stock',
							],
							'purchasable'  => [
								'type'        => 'boolean',
								'description' => 'Whether product is purchasable',
							],
							'product_name' => [
								'type'        => 'string',
								'description' => 'Product name',
							],
							'price'        => [
								'type'        => 'string',
								'description' => 'Product price',
							],
						],
					],
					'endpoint'    => rest_url( 'phone-order/v1/check-availability' ),
					'method'      => 'GET',
				],
				[
					'id'          => 'get_phone_order_stats',
					'name'        => 'Get Phone Order Statistics',
					'description' => 'Retrieve statistics about phone orders',
					'type'        => 'query',
					'parameters'  => [],
					'returns'     => [
						'type'        => 'object',
						'description' => 'Phone order statistics',
						'properties'  => [
							'total_orders'  => [
								'type'        => 'integer',
								'description' => 'Total number of phone orders',
							],
							'today_orders'  => [
								'type'        => 'integer',
								'description' => 'Phone orders today',
							],
							'month_orders'  => [
								'type'        => 'integer',
								'description' => 'Phone orders this month',
							],
							'total_revenue' => [
								'type'        => 'number',
								'description' => 'Total revenue from phone orders',
							],
						],
					],
					'endpoint'    => rest_url( 'phone-order/v1/stats' ),
					'method'      => 'GET',
					'permission'  => 'manage_woocommerce',
				],
			],
			'examples'    => [
				[
					'title'       => 'Create a phone order for product ID 123',
					'description' => 'Example of creating a phone order',
					'capability'  => 'create_phone_order',
					'request'     => [
						'phone'      => '+1234567890',
						'product_id' => 123,
						'quantity'   => 1,
					],
				],
				[
					'title'       => 'Check if product is available',
					'description' => 'Example of checking product availability',
					'capability'  => 'check_product_availability',
					'request'     => [
						'product_id' => 123,
					],
				],
			],
		];

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
		$abilities = $this->register_abilities( [] );
		return $abilities['openwpclub/phone-order'] ?? [];
	}
}
