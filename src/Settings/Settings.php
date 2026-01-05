<?php
/**
 * Settings Management
 *
 * @package OpenWPClub\PhoneOrder
 * @since 2.0.0
 */

declare(strict_types=1);

namespace OpenWPClub\PhoneOrder\Settings;

/**
 * Settings Class
 */
final class Settings {
	/**
	 * Instance
	 *
	 * @var Settings|null
	 */
	private static ?Settings $instance = null;

	/**
	 * Option name
	 *
	 * @var string
	 */
	private const OPTION_NAME = 'wc_phone_order_settings';

	/**
	 * Default settings
	 *
	 * @var array<string, mixed>
	 */
	private array $defaults = [
		'enabled'                => 'yes',
		'display_position'       => 'after_summary',
		'form_title'             => 'Order by Phone',
		'form_subtitle'          => 'Quick order with just your phone number',
		'form_description'       => 'Enter your phone number and we\'ll call you to complete your order',
		'form_button_text'       => 'Order Now',
		'out_of_stock_behavior'  => 'hide',
		'enable_analytics'       => 'yes',
		'enable_abilities_api'   => 'yes',
	];

	/**
	 * Cached settings
	 *
	 * @var array<string, mixed>|null
	 */
	private ?array $settings = null;

	/**
	 * Get instance
	 *
	 * @return Settings
	 */
	public static function get_instance(): Settings {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		add_filter( 'woocommerce_get_sections_products', [ $this, 'add_settings_section' ] );
		add_filter( 'woocommerce_get_settings_products', [ $this, 'add_settings_fields' ], 10, 2 );
	}

	/**
	 * Add settings section to WooCommerce
	 *
	 * @param array<string, string> $sections Existing sections.
	 * @return array<string, string>
	 */
	public function add_settings_section( array $sections ): array {
		$sections['phone_order'] = __( 'Phone Order', 'woocommerce-phone-order' );
		return $sections;
	}

	/**
	 * Add settings fields
	 *
	 * @param array<int, array<string, mixed>> $settings Existing settings.
	 * @param string                           $current_section Current section.
	 * @return array<int, array<string, mixed>>
	 */
	public function add_settings_fields( array $settings, string $current_section ): array {
		if ( 'phone_order' !== $current_section ) {
			return $settings;
		}

		$phone_order_settings = [
			[
				'title' => __( 'Phone Order Settings', 'woocommerce-phone-order' ),
				'type'  => 'title',
				'desc'  => __( 'Configure how the phone order form appears and behaves on your store.', 'woocommerce-phone-order' ),
				'id'    => 'wc_phone_order_settings_title',
			],
			[
				'title'   => __( 'Enable Phone Orders', 'woocommerce-phone-order' ),
				'desc'    => __( 'Enable phone order functionality', 'woocommerce-phone-order' ),
				'id'      => 'wc_phone_order_enabled',
				'default' => 'yes',
				'type'    => 'checkbox',
			],
			[
				'title'   => __( 'Display Position', 'woocommerce-phone-order' ),
				'desc'    => __( 'Where to display the phone order form on product pages', 'woocommerce-phone-order' ),
				'id'      => 'wc_phone_order_display_position',
				'default' => 'after_summary',
				'type'    => 'select',
				'options' => [
					'after_summary'      => __( 'After Product Summary', 'woocommerce-phone-order' ),
					'after_add_to_cart'  => __( 'After Add to Cart Button', 'woocommerce-phone-order' ),
					'disabled'           => __( 'Disabled (Use Shortcode Only)', 'woocommerce-phone-order' ),
				],
			],
			[
				'title'   => __( 'Form Title', 'woocommerce-phone-order' ),
				'desc'    => __( 'Main heading for the phone order form', 'woocommerce-phone-order' ),
				'id'      => 'wc_phone_order_form_title',
				'default' => 'Order by Phone',
				'type'    => 'text',
			],
			[
				'title'   => __( 'Form Subtitle', 'woocommerce-phone-order' ),
				'desc'    => __( 'Subtitle text below the heading', 'woocommerce-phone-order' ),
				'id'      => 'wc_phone_order_form_subtitle',
				'default' => 'Quick order with just your phone number',
				'type'    => 'text',
			],
			[
				'title'   => __( 'Form Description', 'woocommerce-phone-order' ),
				'desc'    => __( 'Description text explaining the process', 'woocommerce-phone-order' ),
				'id'      => 'wc_phone_order_form_description',
				'default' => 'Enter your phone number and we\'ll call you to complete your order',
				'type'    => 'textarea',
			],
			[
				'title'   => __( 'Button Text', 'woocommerce-phone-order' ),
				'desc'    => __( 'Text on the submit button', 'woocommerce-phone-order' ),
				'id'      => 'wc_phone_order_form_button_text',
				'default' => 'Order Now',
				'type'    => 'text',
			],
			[
				'title'   => __( 'Out of Stock Behavior', 'woocommerce-phone-order' ),
				'desc'    => __( 'How to handle the form when product is out of stock', 'woocommerce-phone-order' ),
				'id'      => 'wc_phone_order_out_of_stock_behavior',
				'default' => 'hide',
				'type'    => 'select',
				'options' => [
					'hide'     => __( 'Hide Form', 'woocommerce-phone-order' ),
					'disabled' => __( 'Show Disabled Form', 'woocommerce-phone-order' ),
					'show'     => __( 'Show Active Form', 'woocommerce-phone-order' ),
				],
			],
			[
				'title'   => __( 'Enable Analytics', 'woocommerce-phone-order' ),
				'desc'    => __( 'Track phone order statistics in the admin dashboard', 'woocommerce-phone-order' ),
				'id'      => 'wc_phone_order_enable_analytics',
				'default' => 'yes',
				'type'    => 'checkbox',
			],
			[
				'title'   => __( 'Enable Abilities API', 'woocommerce-phone-order' ),
				'desc'    => __( 'Expose plugin capabilities to AI agents and automation tools (WordPress 6.9+)', 'woocommerce-phone-order' ),
				'id'      => 'wc_phone_order_enable_abilities_api',
				'default' => 'yes',
				'type'    => 'checkbox',
			],
			[
				'type' => 'sectionend',
				'id'   => 'wc_phone_order_settings_end',
			],
		];

		return $phone_order_settings;
	}

	/**
	 * Get all settings
	 *
	 * @return array<string, mixed>
	 */
	public function get_all(): array {
		if ( null === $this->settings ) {
			$saved_settings   = get_option( self::OPTION_NAME, [] );
			$this->settings = wp_parse_args( $saved_settings, $this->defaults );
		}
		return $this->settings;
	}

	/**
	 * Get a specific setting
	 *
	 * @param string $key Setting key.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	public function get( string $key, $default = null ) {
		// Try WooCommerce settings first (for backward compatibility)
		$wc_option = get_option( 'wc_phone_order_' . $key );
		if ( false !== $wc_option ) {
			return $wc_option;
		}

		// Fallback to combined settings
		$settings = $this->get_all();
		return $settings[ $key ] ?? $default ?? $this->defaults[ $key ] ?? null;
	}

	/**
	 * Update a setting
	 *
	 * @param string $key Setting key.
	 * @param mixed  $value Setting value.
	 * @return bool
	 */
	public function set( string $key, $value ): bool {
		$settings = $this->get_all();
		$settings[ $key ] = $value;
		$this->settings = $settings;
		return update_option( self::OPTION_NAME, $settings );
	}

	/**
	 * Update multiple settings
	 *
	 * @param array<string, mixed> $settings Settings to update.
	 * @return bool
	 */
	public function set_multiple( array $settings ): bool {
		$current_settings = $this->get_all();
		$this->settings = array_merge( $current_settings, $settings );
		return update_option( self::OPTION_NAME, $this->settings );
	}
}
