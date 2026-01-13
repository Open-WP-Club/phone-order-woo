<?php
/**
 * Analytics
 *
 * @package OpenWPClub\PhoneOrder
 * @since 2.0.0
 */

declare(strict_types=1);

namespace OpenWPClub\PhoneOrder\Admin;

use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Analytics Class - HPOS Compatible
 */
final class Analytics {
	/**
	 * Instance
	 *
	 * @var Analytics|null
	 */
	private static ?Analytics $instance = null;

	/**
	 * Cache group
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'wc_phone_order_analytics';

	/**
	 * Cache expiration in seconds
	 *
	 * @var int
	 */
	private const CACHE_EXPIRATION = 300; // 5 minutes

	/**
	 * Get instance
	 *
	 * @return Analytics
	 */
	public static function get_instance(): Analytics {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'clear_cache' ) );
		add_action( 'wc_phone_order_created', array( $this, 'clear_cache' ) );
	}

	/**
	 * Clear analytics cache
	 *
	 * @return void
	 */
	public function clear_cache(): void {
		wp_cache_delete( 'dashboard_stats', self::CACHE_GROUP );
	}

	/**
	 * Check if HPOS is enabled
	 *
	 * @return bool
	 */
	private function is_hpos_enabled(): bool {
		return class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Get dashboard statistics
	 *
	 * @return array<string, mixed>
	 */
	public function get_dashboard_stats(): array {
		$cached = wp_cache_get( 'dashboard_stats', self::CACHE_GROUP );

		if ( false !== $cached ) {
			return $cached;
		}

		$stats = array(
			'total_orders'  => $this->get_total_orders(),
			'today_orders'  => $this->get_today_orders(),
			'month_orders'  => $this->get_month_orders(),
			'total_revenue' => $this->get_total_revenue(),
			'recent_orders' => $this->get_recent_orders(),
			'top_products'  => $this->get_top_products(),
		);

		wp_cache_set( 'dashboard_stats', $stats, self::CACHE_GROUP, self::CACHE_EXPIRATION );

		return $stats;
	}

	/**
	 * Get total phone orders count - uses paginate for efficiency
	 *
	 * @return int
	 */
	private function get_total_orders(): int {
		$result = wc_get_orders(
			array(
				'created_via' => 'phone_order',
				'paginate'    => true,
				'limit'       => 1,
			)
		);

		return $result->total;
	}

	/**
	 * Get today's phone orders count
	 *
	 * @return int
	 */
	private function get_today_orders(): int {
		$result = wc_get_orders(
			array(
				'created_via'  => 'phone_order',
				'date_created' => '>=' . gmdate( 'Y-m-d', strtotime( 'today' ) ),
				'paginate'     => true,
				'limit'        => 1,
			)
		);

		return $result->total;
	}

	/**
	 * Get this month's phone orders count
	 *
	 * @return int
	 */
	private function get_month_orders(): int {
		$result = wc_get_orders(
			array(
				'created_via'  => 'phone_order',
				'date_created' => '>=' . gmdate( 'Y-m-d', strtotime( 'first day of this month' ) ),
				'paginate'     => true,
				'limit'        => 1,
			)
		);

		return $result->total;
	}

	/**
	 * Get total revenue from phone orders - HPOS compatible
	 *
	 * @return float
	 */
	private function get_total_revenue(): float {
		$orders = wc_get_orders(
			array(
				'created_via' => 'phone_order',
				'status'      => array( 'wc-completed', 'wc-processing' ),
				'limit'       => -1,
				'return'      => 'objects',
			)
		);

		$total = 0.0;
		foreach ( $orders as $order ) {
			$total += (float) $order->get_total();
		}

		return $total;
	}

	/**
	 * Get recent phone orders - HPOS compatible
	 *
	 * @param int $limit Number of orders to retrieve.
	 * @return array<int, \WC_Order>
	 */
	public function get_recent_orders( int $limit = 10 ): array {
		return wc_get_orders(
			array(
				'created_via' => 'phone_order',
				'limit'       => $limit,
				'orderby'     => 'date',
				'order'       => 'DESC',
			)
		);
	}

	/**
	 * Get top products from phone orders - HPOS compatible
	 *
	 * @param int $limit Number of products to retrieve.
	 * @return array<int, array{product_id: int, order_count: int, revenue: float}>
	 */
	public function get_top_products( int $limit = 5 ): array {
		$orders = wc_get_orders(
			array(
				'created_via' => 'phone_order',
				'status'      => array( 'wc-completed', 'wc-processing' ),
				'limit'       => -1,
				'return'      => 'objects',
			)
		);

		$product_stats = array();

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();

				if ( ! isset( $product_stats[ $product_id ] ) ) {
					$product_stats[ $product_id ] = array(
						'product_id'  => $product_id,
						'order_count' => 0,
						'revenue'     => 0.0,
					);
				}

				++$product_stats[ $product_id ]['order_count'];
				$product_stats[ $product_id ]['revenue'] += (float) $item->get_total();
			}
		}

		// Sort by order count descending.
		usort(
			$product_stats,
			function ( $a, $b ) {
				return $b['order_count'] <=> $a['order_count'];
			}
		);

		return array_slice( $product_stats, 0, $limit );
	}

	/**
	 * Get phone orders by date range - HPOS compatible
	 *
	 * @param string $start_date Start date (Y-m-d format).
	 * @param string $end_date End date (Y-m-d format).
	 * @return array<int, \WC_Order>
	 */
	public function get_orders_by_date_range( string $start_date, string $end_date ): array {
		return wc_get_orders(
			array(
				'limit'        => -1,
				'created_via'  => 'phone_order',
				'date_created' => $start_date . '...' . $end_date,
				'return'       => 'objects',
			)
		);
	}

	/**
	 * Get conversion rate (phone orders / total orders) - optimized
	 *
	 * @return float Percentage
	 */
	public function get_conversion_rate(): float {
		$phone_orders = $this->get_total_orders();

		$result = wc_get_orders(
			array(
				'paginate' => true,
				'limit'    => 1,
			)
		);

		$total = $result->total;

		if ( 0 === $total ) {
			return 0.0;
		}

		return ( $phone_orders / $total ) * 100;
	}

	/**
	 * Get average order value for phone orders
	 *
	 * @return float
	 */
	public function get_average_order_value(): float {
		$total_orders = $this->get_total_orders();

		if ( 0 === $total_orders ) {
			return 0.0;
		}

		$total_revenue = $this->get_total_revenue();

		return $total_revenue / $total_orders;
	}

	/**
	 * Export analytics data to CSV - improved with proper file handling
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return string CSV file path or empty string on failure.
	 */
	public function export_to_csv( string $start_date, string $end_date ): string {
		$orders = $this->get_orders_by_date_range( $start_date, $end_date );

		$upload_dir = wp_upload_dir();
		$filename   = 'phone-orders-' . $start_date . '-to-' . $end_date . '.csv';
		$filepath   = $upload_dir['basedir'] . '/wc-phone-orders/' . $filename;

		// Create directory if it doesn't exist.
		wp_mkdir_p( dirname( $filepath ) );

		$fp = fopen( $filepath, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Writing CSV requires direct file handling.

		if ( false === $fp ) {
			return '';
		}

		// CSV Headers.
		fputcsv(
			$fp,
			array(
				'Order ID',
				'Date',
				'Phone',
				'Product',
				'Total',
				'Status',
			)
		);

		// CSV Data.
		foreach ( $orders as $order ) {
			$items         = $order->get_items();
			$product_names = array();

			foreach ( $items as $item ) {
				$product_names[] = $item->get_name();
			}

			$date_created = $order->get_date_created();

			fputcsv(
				$fp,
				array(
					$order->get_id(),
					$date_created ? $date_created->date( 'Y-m-d H:i:s' ) : '',
					$order->get_billing_phone(),
					implode( ', ', $product_names ),
					$order->get_total(),
					$order->get_status(),
				)
			);
		}

		fclose( $fp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Closing file handle opened above.

		return $filepath;
	}
}
