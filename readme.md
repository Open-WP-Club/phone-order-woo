# Phone Order for WooCommerce

Modern phone-based order creation with Gutenberg blocks and WordPress Interactivity API for WooCommerce stores. Built with cutting-edge WordPress 6.9+ technologies for a superior user experience.

## Features

### New in 2.0

- **Gutenberg Block** - Native block editor support with live preview and customization
- **Interactivity API** - Modern, reactive user interface powered by WordPress Interactivity API
- **Analytics Dashboard** - Track phone orders, conversion rates, and performance metrics
- **Admin Dashboard** - Dedicated interface for managing phone orders
- **Abilities API** - Extended functionality and integration points

### Core Features

- **Quick Orders** - Customers order with just a phone number
- **Smart Customer Matching** - Automatically matches existing customers by phone
- **Guest Customer Support** - Creates guest accounts for new phone numbers
- **Flexible Display** - Show form after product summary, after add-to-cart, use Gutenberg block, or shortcode
- **Stock Management** - Respects product stock status with configurable behavior
- **Full Customization** - Configure all form text via settings page
- **HPOS Compatible** - Works with WooCommerce High Performance Order Storage

## Requirements

- WordPress 6.9 or higher
- WooCommerce 9.0 or higher
- PHP 8.0 or higher

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate through the 'Plugins' menu in WordPress
3. Ensure WooCommerce is installed and activated
4. Configure settings at WooCommerce > Settings > Phone Order

## Configuration

Navigate to **WooCommerce > Settings > Phone Order** to configure:

### Form Content
- **Form Title** - Main heading (default: "Quick Purchase")
- **Form Subtitle** - Tagline/subtitle
- **Form Description** - Explanatory text
- **Button Text** - Submit button label (default: "Order Now")

### Display Options
- **After product summary** - Shows below product details
- **After add to cart button** - Shows near purchase button
- **Disabled** - Use shortcode only

### Stock Behavior
- **Hide form** - Don't show for out-of-stock products
- **Show form** - Display but reject orders
- **Show disabled** - Display with disabled state and notice

## Usage

### Gutenberg Block

Add the "Phone Order" block to any post or page using the block editor. Configure options directly in the editor:
- Product selection
- Show/hide title and description
- Custom title and button text
- Color and spacing customization

### Automatic Display

Configure display position in settings. Form appears automatically on product pages.

### Shortcode

```
[woo_phone_order]
```

With specific product:
```
[woo_phone_order product_id="123"]
```

If no product ID specified, uses current product or latest product.

## How It Works

1. Customer enters phone number on product page (via block, shortcode, or automatic display)
2. Interactive form validates input in real-time (powered by Interactivity API)
3. Plugin checks for existing customer with that phone
4. Creates guest account if customer is new
5. Creates WooCommerce order with "Processing" status
6. Analytics tracks conversion and displays on dashboard
7. Store owner contacts customer to complete order

Orders use WooCommerce's standard `billing_phone` field for compatibility.

## Dashboard & Analytics

Version 2.0 introduces a comprehensive analytics and management system:

- **Phone Order Dashboard** - Centralized view of all phone orders
- **Conversion Tracking** - Monitor success rates and trends
- **Performance Metrics** - Track order volume and customer engagement
- Access via WooCommerce > Phone Orders in admin menu

## Developer Hooks

### Actions

**`wc_phone_order_created`**
Fires when order is created via phone order.

```php
do_action('wc_phone_order_created', $order_id, $phone, $product_id);
```

### Filters

**`wc_phone_order_settings`**
Modify settings page fields.

```php
add_filter('wc_phone_order_settings', function($settings) {
    // Modify $settings array
    return $settings;
});
```

## Frequently Asked Questions

**What's new in version 2.0?**
Version 2.0 is a complete modernization with Gutenberg blocks, WordPress Interactivity API for reactive interfaces, analytics dashboard, and requires WordPress 6.9+ with PHP 8.0+.

**Can customers order multiple products?**
No, this is designed for quick single-product orders. Customers should use regular checkout for complex orders.

**How are orders managed?**
Orders appear in WooCommerce Orders with "Processing" status. Filter by payment method "Phone Order". Use the new Phone Orders dashboard for dedicated management.

**Does it work with HPOS?**
Yes! Fully compatible with WooCommerce High Performance Order Storage (Custom Order Tables).

**Can I use the old shortcode method?**
Yes! All legacy features (shortcodes, automatic display) remain fully supported alongside the new Gutenberg block.

**Can I style the form?**
Yes! Add custom CSS targeting `.woo-phone-order` classes. Uses BEM methodology for easy customization. The Gutenberg block also supports WordPress block styling options.

**What happens to customer data?**
Uses WooCommerce standard fields. Existing customers are matched by phone. New customers get guest accounts.

**What is the Interactivity API?**
WordPress Interactivity API is a modern framework for building reactive, dynamic interfaces. It provides real-time validation and smooth user interactions without page reloads.

## Changelog

### Version 2.0.0 (Latest)

**New Features:**
- Gutenberg block for phone order form
- WordPress Interactivity API integration
- Analytics dashboard
- Admin dashboard for phone order management
- Abilities API for extensibility

**Improvements:**
- Modern PHP 8.0+ codebase
- Block-based architecture
- Enhanced performance and UX

**Requirements Update:**
- WordPress 6.9+
- PHP 8.0+
- WooCommerce 9.0+

### Version 1.1.0

- HPOS compatibility
- Settings page
- Customer matching
- Stock validation

### Version 1.0.0

- Initial release

## License

GPL v2 or later

## Credits

Developed and maintained by [OpenWPClub.com](https://openwpclub.com)

Version 2.0 - Modern WordPress architecture with Gutenberg blocks and Interactivity API
