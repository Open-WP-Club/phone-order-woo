# Phone Order for WooCommerce - Project Context

## Project Overview
This is a WordPress/WooCommerce plugin that enables quick order creation using just a customer's phone number.

**Plugin Name:** Phone Order for WooCommerce
**Plugin Slug:** `woo-phone-order`
**Version:** 1.0.0
**WooCommerce Compatibility:** 8.0+
**WordPress Compatibility:** 6.0+

## Claude Code Permissions

**Authorized Actions:**
- ✅ **Write/Edit any file** in the project (except `.git/` folder)
- ✅ **Run any command** in the project directory
- ✅ **Check the internet** (fetch documentation, search, etc.)
- ✅ **Full file system access** within project boundaries
- ✅ **Install dependencies** (npm, composer, etc.)
- ✅ **Git operations** (commit, push, pull, branch, etc.)
- ✅ **Testing and validation** (run tests, linters, validators)

**Restrictions:**
- ❌ **Cannot modify `.git/` folder** directly

**Guidelines:**
- Make changes confidently without asking for permission
- Use web search for latest WordPress/WooCommerce documentation
- Run tests and validation tools proactively
- Commit changes with descriptive messages when appropriate
- Push to remote when logical units of work are complete

## Architecture

### File Structure
```
woo-phone-order/
├── woo-phone-order.php          # Main plugin file (header, initialization)
├── includes/
│   ├── settings.php             # Settings management & defaults
│   ├── settings-page.php        # WooCommerce settings tab integration
│   ├── form-renderer.php        # Form display logic & shortcode
│   └── ajax-handler.php         # AJAX request handling & order creation
├── assets/
│   ├── css/wc-phone-order.css  # Styles (BEM methodology)
│   └── js/wc-phone-order.js    # JavaScript functionality
└── README.md                    # Plugin documentation
```

### Key Features
- **Phone-based customer lookup**: Searches existing customers by phone number
- **Guest customer creation**: Auto-creates accounts for new phone numbers
- **HPOS compatible**: Uses High Performance Order Storage
- **Stock validation**: Real-time inventory checking
- **Flexible placement**: Works on product pages + shortcode `[woo_phone_order]`

### Technical Details

#### Customer Matching Strategy
1. Search for existing customer by `billing_phone` meta field
2. If found: Use existing customer data
3. If not found: Create guest customer with phone as primary identifier
4. No custom database tables - uses WordPress core user meta

#### Order Creation Flow
```
User enters phone → AJAX validation → Customer lookup/creation
→ Stock check → Order creation → Success response
```

#### HPOS Compatibility
- Uses `wc_get_orders()` instead of direct database queries
- Compatible with both legacy and HPOS storage modes
- Follows WooCommerce CRUD patterns

## Development Guidelines

### Code Standards

#### WordPress/WooCommerce Standards
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Follow [WooCommerce Coding Standards](https://github.com/woocommerce/woocommerce/wiki/Coding-Guidelines)
- Use WordPress core functions wherever possible
- Avoid custom database tables

#### Security Requirements
**Always escape output:**
```php
esc_html()      // For HTML content
esc_attr()      // For HTML attributes
esc_url()       // For URLs
wp_kses_post()  // For post content with allowed HTML
```

**Always sanitize input:**
```php
sanitize_text_field()  // For text inputs
sanitize_email()       // For email addresses
wp_unslash()           // Remove magic quotes
absint()              // For positive integers
```

**AJAX Security:**
- Always use nonces: `wp_create_nonce()` and `check_ajax_referer()`
- Verify capabilities: `current_user_can()`
- Validate all input data

#### Naming Conventions
- **Functions**: `wc_phone_order_function_name()`
- **Classes**: `WC_Phone_Order_Class_Name`
- **Hooks**: `wc_phone_order_hook_name`
- **CSS classes**: `wc-phone-order__element` (BEM)
- **JS variables**: `wcPhoneOrder` (camelCase)

#### Debugging
Wrap all debug code in environment checks:
```php
if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
    error_log( 'Debug message' );
}
```

### WordPress.org Submission Requirements

⚠️ **Critical for WordPress.org approval:**

1. **Plugin Name**: MUST end with "for WooCommerce" (trademark compliance)
   - ✅ Correct: "Phone Order for WooCommerce"
   - ❌ Wrong: "WooCommerce Phone Order"

2. **Plugin Slug**: CANNOT contain "woo" or "woocommerce"
   - ✅ Correct: `phone-order-woocommerce`
   - ❌ Wrong: `woo-phone-order`

3. **Prefix Everything**: All functions, classes, hooks must have unique prefix
   - Prevents conflicts with other plugins

4. **Required Files**:
   - `readme.txt` with proper headers (WordPress standard)
   - `README.md` for GitHub documentation
   - `LICENSE` file (GPL v2 or compatible)

5. **No External Calls**: Cannot make unauthorized external API calls

6. **Proper Escaping**: All output must be escaped, all input sanitized

### Testing Checklist

#### Functional Testing
- [ ] Test form on single product pages
- [ ] Test shortcode `[woo_phone_order]` on pages/posts
- [ ] Test with existing customer phone number
- [ ] Test with new phone number (guest creation)
- [ ] Test with out-of-stock product
- [ ] Test with variable products
- [ ] Test AJAX error handling
- [ ] Test form validation (empty phone, invalid format)

#### Compatibility Testing
- [ ] Test with HPOS enabled
- [ ] Test with HPOS disabled (legacy)
- [ ] Test with popular themes (Storefront, Astra, etc.)
- [ ] Test with common plugins (caching, security)
- [ ] Test in different WordPress versions (6.0+)
- [ ] Test in different WooCommerce versions (8.0+)

#### Security Testing
- [ ] Verify nonce validation works
- [ ] Test SQL injection prevention
- [ ] Test XSS prevention (script injection)
- [ ] Test CSRF protection
- [ ] Verify capability checks

#### WordPress.org Validation
- [ ] Run [Plugin Check](https://wordpress.org/plugins/plugin-check/) plugin
- [ ] Validate `readme.txt` format
- [ ] Check for WordPress.org coding standards
- [ ] Verify GPL licensing
- [ ] Test on PHP 7.4+ and 8.0+

## Common Commands

### Development
```bash
# Watch for file changes during development
npm run watch  # (if build process exists)

# Run WordPress coding standards check
phpcs --standard=WordPress .

# Fix auto-fixable coding standards
phpcbf --standard=WordPress .
```

### Git Workflow
```bash
# Commit changes
git add .
git commit -m "feat: description of feature"
git push

# Create release
git tag v1.0.0
git push --tags
```

### Testing
```bash
# Run PHP unit tests (if configured)
vendor/bin/phpunit

# Validate plugin
wp plugin verify-checksums woo-phone-order
```

## Useful Resources

- [WordPress Plugin Developer Handbook](https://developer.wordpress.org/plugins/)
- [WooCommerce Documentation](https://woocommerce.com/documentation/)
- [WooCommerce CRUD Objects](https://github.com/woocommerce/woocommerce/wiki/CRUD-Objects-in-3.0)
- [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/)
- [Plugin Review Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/)

## Support & Contribution

When making changes:
1. Test thoroughly using the checklist above
2. Follow WordPress coding standards
3. Update documentation if adding features
4. Use descriptive commit messages
5. Push changes after each logical unit of work
