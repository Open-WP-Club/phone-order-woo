<?php
/**
 * Analytics
 *
 * @package OpenWPClub\PhoneOrder
 * @since 2.0.0
 */

declare(strict_types=1);

namespace OpenWPClub\PhoneOrder\Admin;

/**
 * Analytics Class
 */
final class Analytics {
	/**
	 * Instance
	 *
	 * @var Analytics|null
	 */
	private static ?Analytics $instance = null;

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
		// Initialize analytics
	}

	/**
	 * Get dashboard statistics
	 *
	 * @return array<string, mixed>
	 */
	public function get_dashboard_stats(): array {
		return [
			'total_orders'   => $this->get_total_orders(),
			'today_orders'   => $this->get_today_orders(),
			'month_orders'   => $this->get_month_orders(),
			'total_revenue'  => $this->get_total_revenue(),
			'recent_orders'  => $this->get_recent_orders(),
			'top_products'   => $this->get_top_products(),
		];
	}

	/**
	 * Get total phone orders count
	 *
	 * @return int
	 */
	private function get_total_orders(): int {
		$orders = wc_get_orders( [
			'limit'        => -1,
			'created_via'  => 'phone_order',
			'return'       => 'ids',
		] );

		return count( $orders );
	}

	/**
	 * Get today's phone orders count
	 *
	 * @return int
	 */
	private function get_today_orders(): int {
		$orders = wc_get_orders( [
			'limit'        => -1,
			'created_via'  => 'phone_order',
			'date_created' => '>=' . strtotime( 'today' ),
			'return'       => 'ids',
		] );

		return count( $orders );
	}

	/**
	 * Get this month's phone orders count
	 *
	 * @return int
	 */
	private function get_month_orders(): int {
		$orders = wc_get_orders( [
			'limit'        => -1,
			'created_via'  => 'phone_order',
			'date_created' => '>=' . strtotime( 'first day of this month' ),
			'return'       => 'ids',
		] );

		return count( $orders );
	}

	/**
	 * Get total revenue from phone orders
	 *
	 * @return float
	 */
	private function get_total_revenue(): float {
		global $wpdb;

		$result = $wpdb->get_var(
			"SELECT SUM(om.meta_value)
			FROM {$wpdb->prefix}posts p
			INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
			INNER JOIN {$wpdb->prefix}postmeta om ON p.ID = om.post_id
			WHERE p.post_type = 'shop_order'
			AND pm.meta_key = '_created_via'
			AND pm.meta_value = 'phone_order'
			AND om.meta_key = '_order_total'
			AND p.post_status IN ('wc-completed', 'wc-processing')"
		);

		return $result ? (float) $result : 0.0;
	}

	/**
	 * Get recent phone orders
	 *
	 * @param int $limit Number of orders to retrieve.
	 * @return array<int, object>
	 */
	public function get_recent_orders( int $limit = 10 ): array {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.ID as order_id, p.post_date
				FROM {$wpdb->prefix}posts p
				INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
				WHERE p.post_type = 'shop_order'
				AND pm.meta_key = '_created_via'
				AND pm.meta_value = 'phone_order'
				ORDER BY p.post_date DESC
				LIMIT %d",
				$limit
			)
		);

		return $results ?: [];
	}

	/**
	 * Get top products from phone orders
	 *
	 * @param int $limit Number of products to retrieve.
	 * @return array<int, object>
	 */
	public function get_top_products( int $limit = 5 ): array {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					oim.meta_value as product_id,
					COUNT(DISTINCT oi.order_id) as order_count,
					SUM(om.meta_value) as revenue
				FROM {$wpdb->prefix}woocommerce_order_items oi
				INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
				INNER JOIN {$wpdb->prefix}posts p ON oi.order_id = p.ID
				INNER JOIN {$wpdb->prefix}postmeta pm ON p.ID = pm.post_id
				INNER JOIN {$wpdb->prefix}postmeta om ON p.ID = om.post_id
				WHERE oi.order_item_type = 'line_item'
				AND oim.meta_key = '_product_id'
				AND pm.meta_key = '_created_via'
				AND pm.meta_value = 'phone_order'
				AND om.meta_key = '_order_total'
				AND p.post_status IN ('wc-completed', 'wc-processing')
				GROUP BY product_id
				ORDER BY order_count DESC, revenue DESC
				LIMIT %d",
				$limit
			)
		);

		return $results ?: [];
	}

	/**
	 * Get phone orders by date range
	 *
	 * @param string $start_date Start date (Y-m-d format).
	 * @param string $end_date End date (Y-m-d format).
	 * @return array<int, object>
	 */
	public function get_orders_by_date_range( string $start_date, string $end_date ): array {
		$orders = wc_get_orders( [
			'limit'        => -1,
			'created_via'  => 'phone_order',
			'date_created' => $start_date . '...' . $end_date,
			'return'       => 'objects',
		] );

		return $orders;
	}

	/**
	 * Get conversion rate (phone orders / total orders)
	 *
	 * @return float Percentage
	 */
	public function get_conversion_rate(): float {
		$phone_orders = $this->get_total_orders();

		$total_orders = wc_get_orders( [
			'limit'  => -1,
			'return' => 'ids',
		] );

		$total = count( $total_orders );

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
	 * Export analytics data to CSV
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date End date.
	 * @return string CSV file path.
	 */
	public function export_to_csv( string $start_date, string $end_date ): string {
		$orders = $this->get_orders_by_date_range( $start_date, $end_date );

		$filename = 'phone-orders-' . $start_date . '-to-' . $end_date . '.csv';
		$filepath = wp_upload_dir()['basedir'] . '/' . $filename;

		$fp = fopen( $filepath, 'w' );

		// CSV Headers
		fputcsv( $fp, [
			'Order ID',
			'Date',
			'Phone',
			'Product',
			'Total',
			'Status',
		] );

		// CSV Data
		foreach ( $orders as $order ) {
			$items = $order->get_items();
			$product_names = [];
			foreach ( $items as $item ) {
				$product_names[] = $item->get_name();
			}

			fputcsv( $fp, [
				$order->get_id(),
				$order->get_date_created()->date( 'Y-m-d H:i:s' ),
				$order->get_billing_phone(),
				implode( ', ', $product_names ),
				$order->get_total(),
				$order->get_status(),
			] );
		}

		fclose( $fp );

		return $filepath;
	}
}
