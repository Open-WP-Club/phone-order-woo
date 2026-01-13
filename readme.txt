=== Phone Order for WooCommerce ===
Contributors: openwpclub
Tags: woocommerce, phone order, quick order, one-click order, simple checkout, gutenberg block
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern phone-based order creation with Gutenberg blocks and Interactivity API for WooCommerce stores.

== Description ==

Phone Order for WooCommerce is a modern WordPress 6.9+ plugin that allows customers to place quick orders using just their phone number. Built with the latest WordPress technologies including Gutenberg blocks and the Interactivity API for an enhanced user experience.

= Features =

* **Gutenberg Block** - Native block editor support with live preview
* **Interactivity API** - Modern, reactive user interface
* **Analytics Dashboard** - Track phone orders and conversion rates
* **Admin Dashboard** - Dedicated dashboard for phone order management
* Easy order placement with just a phone number
* Customizable form placement on product pages
* Shortcode support: `[woo_phone_order]` and `[woo_phone_order product_id="123"]`
* Configurable text for form title, subtitle, and description
* Out-of-stock product handling options
* Processing order status for easy order management
* Responsive design that works on all devices
* HPOS (High Performance Order Storage) compatible
* Customer matching by phone number
* Automatic guest customer creation

= How It Works =

1. Customer enters their phone number on a product page
2. Plugin checks for existing customer by phone number
3. Creates a guest account if needed
4. Automatically creates a WooCommerce order
5. Store owner contacts customer to complete the order

= Developer Features =

* Hook: `wc_phone_order_created` - Fired when a new phone order is created
* Full settings API integration
* Shortcode: `[woo_phone_order]` or `[woo_phone_order product_id="123"]`

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Ensure that WooCommerce is installed and activated
4. Go to WooCommerce > Settings > Phone Order to configure

== Frequently Asked Questions ==

= Can customers order multiple products or specify quantities? =

Currently, the plugin is designed for quick, single-product orders. For more complex orders, customers should use the regular WooCommerce checkout process.

= How are phone orders managed? =

Phone orders are created with a "Processing" status. You can manage these orders from the WooCommerce Orders page in your WordPress admin.

= Is this plugin compatible with my theme? =

The plugin should work with any WooCommerce-compatible theme. If you encounter any styling issues, you may need to add some custom CSS to your theme.

= Does this work with HPOS (High Performance Order Storage)? =

Yes! The plugin is fully compatible with WooCommerce's High Performance Order Storage (Custom Order Tables).

== Screenshots ==

1. Phone order form on product page
2. Settings page in WooCommerce
3. Order created in admin

== Changelog ==

= 2.0.0 =
* **NEW:** Gutenberg block for phone order form
* **NEW:** WordPress Interactivity API integration for reactive UI
* **NEW:** Analytics dashboard for tracking phone orders
* **NEW:** Admin dashboard for phone order management
* **NEW:** Abilities API for extended functionality
* **IMPROVED:** Modern codebase with PHP 8.0+ features
* **IMPROVED:** Block-based architecture
* **UPDATED:** Minimum requirements - WordPress 6.9+, PHP 8.0+, WooCommerce 9.0+
* Full backwards compatibility with shortcodes and legacy features

= 1.1.0 =
* Added HPOS (High Performance Order Storage) compatibility
* Added full settings page with customization options
* Added shortcode support: [woo_phone_order]
* Added customer matching by phone number
* Added automatic guest customer account creation
* Added stock validation before order creation
* Added configurable form placement options
* Added out-of-stock behavior settings
* Improved CSS with BEM methodology
* Improved JavaScript error handling
* Fixed deprecated wc_reduce_stock_levels function
* Updated compatibility: WooCommerce 8.0-9.5, WordPress 6.0+, PHP 7.4+

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
Major modernization update with Gutenberg blocks, Interactivity API, and analytics dashboard. Requires WordPress 6.9+, PHP 8.0+, and WooCommerce 9.0+.

= 1.1.0 =
Major update with HPOS compatibility, settings page, and customer management features.
