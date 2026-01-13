<?php
/**
 * Phone Order Gutenberg Block
 *
 * @package OpenWPClub\PhoneOrder
 * @since 2.0.0
 */

declare(strict_types=1);

namespace OpenWPClub\PhoneOrder\Blocks;

use OpenWPClub\PhoneOrder\Plugin;
use OpenWPClub\PhoneOrder\Frontend\FormRenderer;

/**
 * Phone Order Block Class
 */
final class PhoneOrderBlock {
	/**
	 * Instance
	 *
	 * @var PhoneOrderBlock|null
	 */
	private static ?PhoneOrderBlock $instance = null;

	/**
	 * Block name
	 *
	 * @var string
	 */
	private const BLOCK_NAME = 'openwpclub/phone-order';

	/**
	 * Get instance
	 *
	 * @return PhoneOrderBlock
	 */
	public static function get_instance(): PhoneOrderBlock {
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
		add_action( 'init', [ $this, 'register_block' ] );
	}

	/**
	 * Register Gutenberg block
	 *
	 * @return void
	 */
	public function register_block(): void {
		// Register block from metadata
		register_block_type(
			Plugin::get_instance()->get_plugin_path() . 'build/phone-order-block',
			[
				'render_callback' => [ $this, 'render_block' ],
				'attributes'      => [
					'productId' => [
						'type'    => 'number',
						'default' => 0,
					],
					'showTitle' => [
						'type'    => 'boolean',
						'default' => true,
					],
					'showDescription' => [
						'type'    => 'boolean',
						'default' => true,
					],
					'customTitle' => [
						'type'    => 'string',
						'default' => '',
					],
					'customButtonText' => [
						'type'    => 'string',
						'default' => '',
					],
					'align' => [
						'type' => 'string',
					],
					'backgroundColor' => [
						'type' => 'string',
					],
					'textColor' => [
						'type' => 'string',
					],
					'className' => [
						'type' => 'string',
					],
				],
				'supports'        => [
					'align'             => true,
					'color'             => [
						'background' => true,
						'text'       => true,
					],
					'spacing'           => [
						'padding'  => true,
						'margin'   => true,
					],
					'typography'        => [
						'fontSize'   => true,
						'lineHeight' => true,
					],
					'interactivity'     => true, // WordPress 6.5+ Interactivity API
				],
			]
		);
	}

	/**
	 * Render block callback
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $content Block content.
	 * @return string
	 */
	public function render_block( array $attributes, string $content ): string {
		$product_id = absint( $attributes['productId'] ?? 0 );

		// If no product ID, try to get from current context
		if ( 0 === $product_id ) {
			global $product;
			if ( $product instanceof \WC_Product ) {
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
			return '<p>' . esc_html__( 'Please select a product for the phone order form.', 'woocommerce-phone-order' ) . '</p>';
		}

		// Get product
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_purchasable() ) {
			return '<p>' . esc_html__( 'Selected product is not available.', 'woocommerce-phone-order' ) . '</p>';
		}

		// Build wrapper classes
		$wrapper_classes = [ 'wp-block-openwpclub-phone-order' ];
		if ( ! empty( $attributes['className'] ) ) {
			$wrapper_classes[] = $attributes['className'];
		}
		if ( ! empty( $attributes['align'] ) ) {
			$wrapper_classes[] = 'align' . $attributes['align'];
		}

		// Build wrapper styles
		$wrapper_styles = [];
		if ( ! empty( $attributes['backgroundColor'] ) ) {
			$wrapper_styles[] = 'background-color: ' . esc_attr( $attributes['backgroundColor'] );
		}
		if ( ! empty( $attributes['textColor'] ) ) {
			$wrapper_styles[] = 'color: ' . esc_attr( $attributes['textColor'] );
		}

		// Start output buffering
		ob_start();

		printf(
			'<div class="%s" style="%s" data-wp-interactive="phone-order">',
			esc_attr( implode( ' ', $wrapper_classes ) ),
			esc_attr( implode( '; ', $wrapper_styles ) )
		);

		// Render form using FormRenderer
		FormRenderer::get_instance()->shortcode_handler( [
			'product_id' => $product_id,
		] );

		echo '</div>';

		return ob_get_clean();
	}
}
