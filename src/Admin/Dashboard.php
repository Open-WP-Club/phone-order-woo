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
		// Use priority 99 to ensure WooCommerce menu is registered first.
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ], 99 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

	}

	/**
	 * Add admin menu
	 *
	 * @return void
	 */
	public function add_admin_menu(): void {
		// Check if WooCommerce is active.
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		add_submenu_page(
			'woocommerce',
			__( 'Phone Orders', 'woocommerce-phone-order' ),
			__( 'Phone Orders', 'woocommerce-phone-order' ),
			'manage_options', // Use manage_options for compatibility - WooCommerce admins have this.
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
	 * Get current tab
	 *
	 * @return string
	 */
	private function get_current_tab(): string {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'dashboard';
	}

	/**
	 * Render dashboard page
	 *
	 * @return void
	 */
	public function render_dashboard(): void {
		// Check WooCommerce first.
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<div class="wrap"><h1>' . esc_html__( 'Phone Orders', 'woocommerce-phone-order' ) . '</h1>';
			echo '<p>' . esc_html__( 'WooCommerce is required for this plugin to work.', 'woocommerce-phone-order' ) . '</p></div>';
			return;
		}

		// Check permissions.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'woocommerce-phone-order' ) );
		}

		$current_tab = $this->get_current_tab();
		$base_url    = admin_url( 'admin.php?page=wc-phone-orders' );

		?>
		<div class="wrap wc-phone-order-dashboard">
			<h1><?php esc_html_e( 'Phone Orders', 'woocommerce-phone-order' ); ?></h1>

			<nav class="nav-tab-wrapper wc-phone-order-tabs">
				<a href="<?php echo esc_url( $base_url ); ?>"
				   class="nav-tab <?php echo 'dashboard' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Dashboard', 'woocommerce-phone-order' ); ?>
				</a>
				<a href="<?php echo esc_url( add_query_arg( 'tab', 'settings', $base_url ) ); ?>"
				   class="nav-tab <?php echo 'settings' === $current_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Settings', 'woocommerce-phone-order' ); ?>
				</a>
			</nav>

			<div class="wc-phone-order-tab-content">
				<?php
				if ( 'settings' === $current_tab ) {
					$this->render_settings_tab();
				} else {
					$this->render_dashboard_tab();
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render dashboard tab content
	 *
	 * @return void
	 */
	private function render_dashboard_tab(): void {
		$analytics = Analytics::get_instance();
		$stats     = $analytics->get_dashboard_stats();

		?>
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
		<?php
	}

	/**
	 * Render settings tab content
	 *
	 * @return void
	 */
	private function render_settings_tab(): void {
		// Handle form submission.
		if ( isset( $_POST['wc_phone_order_save_settings'] ) ) {
			$this->save_settings();
		}

		// Get current settings.
		$display_position      = get_option( 'wc_phone_order_display_position', 'after_summary' );
		$form_title            = get_option( 'wc_phone_order_form_title', 'Order by Phone' );
		$form_subtitle         = get_option( 'wc_phone_order_form_subtitle', 'Quick order with just your phone number' );
		$form_description      = get_option( 'wc_phone_order_form_description', "Enter your phone number and we'll call you to complete your order" );
		$form_button_text      = get_option( 'wc_phone_order_form_button_text', 'Order Now' );
		$out_of_stock_behavior = get_option( 'wc_phone_order_out_of_stock_behavior', 'hide' );
		$enable_analytics      = get_option( 'wc_phone_order_enable_analytics', 'yes' );

		?>
		<?php settings_errors( 'wc_phone_order_settings' ); ?>

		<div class="wc-phone-order-settings">
			<div class="wc-phone-order-settings-form">
				<h3><?php esc_html_e( 'Form Settings', 'woocommerce-phone-order' ); ?></h3>

				<form method="post" action="">
					<?php wp_nonce_field( 'wc_phone_order_settings', 'wc_phone_order_nonce' ); ?>

					<table class="form-table" role="presentation">
						<tbody>
							<tr>
								<th scope="row">
									<label for="display_position"><?php esc_html_e( 'Display Position', 'woocommerce-phone-order' ); ?></label>
								</th>
								<td>
									<select name="display_position" id="display_position">
										<option value="after_summary" <?php selected( $display_position, 'after_summary' ); ?>>
											<?php esc_html_e( 'After Product Summary', 'woocommerce-phone-order' ); ?>
										</option>
										<option value="after_add_to_cart" <?php selected( $display_position, 'after_add_to_cart' ); ?>>
											<?php esc_html_e( 'After Add to Cart Button', 'woocommerce-phone-order' ); ?>
										</option>
										<option value="disabled" <?php selected( $display_position, 'disabled' ); ?>>
											<?php esc_html_e( 'Disabled (Use Shortcode Only)', 'woocommerce-phone-order' ); ?>
										</option>
									</select>
									<p class="description"><?php esc_html_e( 'Where to display the phone order form on product pages', 'woocommerce-phone-order' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="form_title"><?php esc_html_e( 'Form Title', 'woocommerce-phone-order' ); ?></label>
								</th>
								<td>
									<input type="text" name="form_title" id="form_title" value="<?php echo esc_attr( $form_title ); ?>" class="regular-text">
									<p class="description"><?php esc_html_e( 'Main heading for the phone order form', 'woocommerce-phone-order' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="form_subtitle"><?php esc_html_e( 'Form Subtitle', 'woocommerce-phone-order' ); ?></label>
								</th>
								<td>
									<input type="text" name="form_subtitle" id="form_subtitle" value="<?php echo esc_attr( $form_subtitle ); ?>" class="regular-text">
									<p class="description"><?php esc_html_e( 'Subtitle text below the heading', 'woocommerce-phone-order' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="form_description"><?php esc_html_e( 'Form Description', 'woocommerce-phone-order' ); ?></label>
								</th>
								<td>
									<textarea name="form_description" id="form_description" rows="3" class="large-text"><?php echo esc_textarea( $form_description ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Description text explaining the process', 'woocommerce-phone-order' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="form_button_text"><?php esc_html_e( 'Button Text', 'woocommerce-phone-order' ); ?></label>
								</th>
								<td>
									<input type="text" name="form_button_text" id="form_button_text" value="<?php echo esc_attr( $form_button_text ); ?>" class="regular-text">
									<p class="description"><?php esc_html_e( 'Text on the submit button', 'woocommerce-phone-order' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row">
									<label for="out_of_stock_behavior"><?php esc_html_e( 'Out of Stock Behavior', 'woocommerce-phone-order' ); ?></label>
								</th>
								<td>
									<select name="out_of_stock_behavior" id="out_of_stock_behavior">
										<option value="hide" <?php selected( $out_of_stock_behavior, 'hide' ); ?>>
											<?php esc_html_e( 'Hide Form', 'woocommerce-phone-order' ); ?>
										</option>
										<option value="disabled" <?php selected( $out_of_stock_behavior, 'disabled' ); ?>>
											<?php esc_html_e( 'Show Disabled Form', 'woocommerce-phone-order' ); ?>
										</option>
										<option value="show" <?php selected( $out_of_stock_behavior, 'show' ); ?>>
											<?php esc_html_e( 'Show Active Form', 'woocommerce-phone-order' ); ?>
										</option>
									</select>
									<p class="description"><?php esc_html_e( 'How to handle the form when product is out of stock', 'woocommerce-phone-order' ); ?></p>
								</td>
							</tr>

							<tr>
								<th scope="row"><?php esc_html_e( 'Enable Analytics', 'woocommerce-phone-order' ); ?></th>
								<td>
									<label>
										<input type="checkbox" name="enable_analytics" value="yes" <?php checked( $enable_analytics, 'yes' ); ?>>
										<?php esc_html_e( 'Track phone order statistics in the admin dashboard', 'woocommerce-phone-order' ); ?>
									</label>
								</td>
							</tr>
						</tbody>
					</table>

					<p class="submit">
						<input type="submit" name="wc_phone_order_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Save Settings', 'woocommerce-phone-order' ); ?>">
					</p>
				</form>
			</div>

			<div class="wc-phone-order-settings-sidebar">
				<div class="wc-phone-order-shortcode-info">
					<h3><?php esc_html_e( 'Shortcode Usage', 'woocommerce-phone-order' ); ?></h3>
					<p><?php esc_html_e( 'Display the form anywhere:', 'woocommerce-phone-order' ); ?></p>
					<code>[woo_phone_order]</code>
					<p><?php esc_html_e( 'With specific product:', 'woocommerce-phone-order' ); ?></p>
					<code>[woo_phone_order product_id="123"]</code>
				</div>

				<div class="wc-phone-order-help-box">
					<h3><?php esc_html_e( 'Need Help?', 'woocommerce-phone-order' ); ?></h3>
					<p><?php esc_html_e( 'Orders are created directly when customers submit their phone number. No cart or checkout required.', 'woocommerce-phone-order' ); ?></p>
					<p><?php esc_html_e( 'View orders in WooCommerce > Orders with "phone_order" as the source.', 'woocommerce-phone-order' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save settings from the form
	 *
	 * @return void
	 */
	private function save_settings(): void {
		// Check permissions first.
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Verify nonce.
		$nonce = isset( $_POST['wc_phone_order_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['wc_phone_order_nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'wc_phone_order_settings' ) ) {
			return;
		}

		// Sanitize and save settings.
		$settings_to_save = [
			'wc_phone_order_display_position'      => isset( $_POST['display_position'] ) ? sanitize_key( wp_unslash( $_POST['display_position'] ) ) : 'after_summary',
			'wc_phone_order_form_title'            => isset( $_POST['form_title'] ) ? sanitize_text_field( wp_unslash( $_POST['form_title'] ) ) : '',
			'wc_phone_order_form_subtitle'         => isset( $_POST['form_subtitle'] ) ? sanitize_text_field( wp_unslash( $_POST['form_subtitle'] ) ) : '',
			'wc_phone_order_form_description'      => isset( $_POST['form_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['form_description'] ) ) : '',
			'wc_phone_order_form_button_text'      => isset( $_POST['form_button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['form_button_text'] ) ) : '',
			'wc_phone_order_out_of_stock_behavior' => isset( $_POST['out_of_stock_behavior'] ) ? sanitize_key( wp_unslash( $_POST['out_of_stock_behavior'] ) ) : 'hide',
			'wc_phone_order_enable_analytics'      => isset( $_POST['enable_analytics'] ) ? 'yes' : 'no',
		];

		foreach ( $settings_to_save as $key => $value ) {
			update_option( $key, $value );
		}

		// Show success message.
		add_settings_error(
			'wc_phone_order_settings',
			'settings_updated',
			__( 'Settings saved successfully.', 'woocommerce-phone-order' ),
			'success'
		);
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
