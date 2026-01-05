<?php
/**
 * Admin Dashboard
 *
 * @package OpenWPClub\PhoneOrder
 * @since 2.0.0
 */

declare(strict_types=1);

namespace OpenWPClub\PhoneOrder\Admin;

use OpenWPClub\PhoneOrder\Settings\Settings;
use OpenWPClub\PhoneOrder\Plugin;

/**
 * Dashboard Class
 */
final class Dashboard {
	/**
	 * Instance
	 *
	 * @var Dashboard|null
	 */
	private static ?Dashboard $instance = null;

	/**
	 * Settings instance
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Get instance
	 *
	 * @return Dashboard
	 */
	public static function get_instance(): Dashboard {
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
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
	}

	/**
	 * Add admin menu
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		add_submenu_page(
			'woocommerce',
			__( 'Phone Orders', 'woocommerce-phone-order' ),
			__( 'Phone Orders', 'woocommerce-phone-order' ),
			'manage_woocommerce',
			'wc-phone-orders',
			[ $this, 'render_dashboard' ]
		);
	}

	/**
	 * Enqueue admin scripts
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_scripts( string $hook ): void {
		if ( 'woocommerce_page_wc-phone-orders' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wc-phone-order-admin',
			Plugin::get_instance()->get_plugin_url() . 'assets/css/admin.css',
			[],
			Plugin::VERSION
		);

		wp_enqueue_script(
			'wc-phone-order-admin',
			Plugin::get_instance()->get_plugin_url() . 'assets/js/admin.js',
			[ 'jquery', 'wp-api' ],
			Plugin::VERSION,
			true
		);
	}

	/**
	 * Render dashboard page
	 *
	 * @return void
	 */
	public function render_dashboard(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'woocommerce-phone-order' ) );
		}

		$analytics = Analytics::get_instance();
		$stats     = $analytics->get_dashboard_stats();

		?>
		<div class="wrap wc-phone-order-dashboard">
			<h1><?php esc_html_e( 'Phone Orders Dashboard', 'woocommerce-phone-order' ); ?></h1>

			<div class="wc-phone-order-stats-grid">
				<div class="wc-phone-order-stat-card">
					<h3><?php esc_html_e( 'Total Orders', 'woocommerce-phone-order' ); ?></h3>
					<p class="stat-number"><?php echo esc_html( number_format_i18n( $stats['total_orders'] ) ); ?></p>
				</div>

				<div class="wc-phone-order-stat-card">
					<h3><?php esc_html_e( 'Orders Today', 'woocommerce-phone-order' ); ?></h3>
					<p class="stat-number"><?php echo esc_html( number_format_i18n( $stats['today_orders'] ) ); ?></p>
				</div>

				<div class="wc-phone-order-stat-card">
					<h3><?php esc_html_e( 'This Month', 'woocommerce-phone-order' ); ?></h3>
					<p class="stat-number"><?php echo esc_html( number_format_i18n( $stats['month_orders'] ) ); ?></p>
				</div>

				<div class="wc-phone-order-stat-card">
					<h3><?php esc_html_e( 'Total Revenue', 'woocommerce-phone-order' ); ?></h3>
					<p class="stat-number"><?php echo wp_kses_post( wc_price( $stats['total_revenue'] ) ); ?></p>
				</div>
			</div>

			<div class="wc-phone-order-recent-orders">
				<h2><?php esc_html_e( 'Recent Phone Orders', 'woocommerce-phone-order' ); ?></h2>
				<?php $this->render_orders_table( $stats['recent_orders'] ); ?>
			</div>

			<div class="wc-phone-order-top-products">
				<h2><?php esc_html_e( 'Top Products', 'woocommerce-phone-order' ); ?></h2>
				<?php $this->render_top_products( $stats['top_products'] ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render orders table
	 *
	 * @param array<int, object> $orders Recent orders.
	 * @return void
	 */
	private function render_orders_table( array $orders ): void {
		if ( empty( $orders ) ) {
			echo '<p>' . esc_html__( 'No phone orders yet.', 'woocommerce-phone-order' ) . '</p>';
			return;
		}

		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Order', 'woocommerce-phone-order' ); ?></th>
					<th><?php esc_html_e( 'Phone', 'woocommerce-phone-order' ); ?></th>
					<th><?php esc_html_e( 'Product', 'woocommerce-phone-order' ); ?></th>
					<th><?php esc_html_e( 'Total', 'woocommerce-phone-order' ); ?></th>
					<th><?php esc_html_e( 'Status', 'woocommerce-phone-order' ); ?></th>
					<th><?php esc_html_e( 'Date', 'woocommerce-phone-order' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $orders as $order_data ) : ?>
					<?php
					$order = wc_get_order( $order_data->order_id );
					if ( ! $order ) {
						continue;
					}
					?>
					<tr>
						<td>
							<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $order->get_id() . '&action=edit' ) ); ?>">
								#<?php echo esc_html( $order->get_id() ); ?>
							</a>
						</td>
						<td><?php echo esc_html( $order->get_billing_phone() ); ?></td>
						<td>
							<?php
							$items = $order->get_items();
							if ( ! empty( $items ) ) {
								$item = reset( $items );
								echo esc_html( $item->get_name() );
							}
							?>
						</td>
						<td><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></td>
						<td><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></td>
						<td><?php echo esc_html( $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Render top products
	 *
	 * @param array<int, object> $products Top products.
	 * @return void
	 */
	private function render_top_products( array $products ): void {
		if ( empty( $products ) ) {
			echo '<p>' . esc_html__( 'No product data available.', 'woocommerce-phone-order' ) . '</p>';
			return;
		}

		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Product', 'woocommerce-phone-order' ); ?></th>
					<th><?php esc_html_e( 'Orders', 'woocommerce-phone-order' ); ?></th>
					<th><?php esc_html_e( 'Revenue', 'woocommerce-phone-order' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $products as $product_data ) : ?>
					<?php
					$product = wc_get_product( $product_data->product_id );
					if ( ! $product ) {
						continue;
					}
					?>
					<tr>
						<td>
							<a href="<?php echo esc_url( admin_url( 'post.php?post=' . $product->get_id() . '&action=edit' ) ); ?>">
								<?php echo esc_html( $product->get_name() ); ?>
							</a>
						</td>
						<td><?php echo esc_html( number_format_i18n( $product_data->order_count ) ); ?></td>
						<td><?php echo wp_kses_post( wc_price( $product_data->revenue ) ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}
