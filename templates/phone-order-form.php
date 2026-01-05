<?php
/**
 * Phone Order Form Template
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/phone-order-form.php
 *
 * @package OpenWPClub\PhoneOrder
 * @version 2.0.0
 *
 * Available variables:
 * @var WC_Product $product                Product object
 * @var string     $form_title             Form title
 * @var string     $form_subtitle          Form subtitle
 * @var string     $form_description       Form description
 * @var string     $button_text            Button text
 * @var string     $form_class             Form CSS class
 * @var bool       $form_disabled          Whether form is disabled
 * @var bool       $is_in_stock            Whether product is in stock
 * @var string     $out_of_stock_behavior  Out of stock behavior setting
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="<?php echo esc_attr( $form_class ); ?>" data-wp-interactive="phone-order">
	<?php if ( $form_title ) : ?>
		<h3 class="woo-phone-order__title"><?php echo esc_html( $form_title ); ?></h3>
	<?php endif; ?>

	<?php if ( $form_subtitle ) : ?>
		<p class="woo-phone-order__subtitle"><?php echo esc_html( $form_subtitle ); ?></p>
	<?php endif; ?>

	<?php if ( $form_description ) : ?>
		<p class="woo-phone-order__description"><?php echo esc_html( $form_description ); ?></p>
	<?php endif; ?>

	<form
		id="woo-phone-order-form"
		class="woo-phone-order__form"
		data-product-id="<?php echo esc_attr( $product->get_id() ); ?>"
		<?php echo $form_disabled ? 'data-disabled="true"' : ''; ?>
		data-wp-context='{"isSubmitting": false, "message": "", "messageType": ""}'>

		<div class="woo-phone-order__input-group">
			<input
				type="tel"
				name="phone"
				class="woo-phone-order__phone-input"
				autocomplete="tel"
				required
				placeholder="<?php esc_attr_e( 'Your phone number', 'woocommerce-phone-order' ); ?>"
				<?php echo $form_disabled ? 'disabled' : ''; ?>
				data-wp-bind--disabled="context.isSubmitting"
				aria-label="<?php esc_attr_e( 'Phone number', 'woocommerce-phone-order' ); ?>">

			<button
				type="submit"
				class="woo-phone-order__submit-button"
				<?php echo $form_disabled ? 'disabled' : ''; ?>
				data-wp-bind--disabled="context.isSubmitting"
				data-wp-on--click="actions.submitForm">
				<span data-wp-show="!context.isSubmitting">
					<?php echo esc_html( $button_text ); ?>
				</span>
				<span data-wp-show="context.isSubmitting">
					<?php esc_html_e( 'Submitting...', 'woocommerce-phone-order' ); ?>
				</span>
			</button>
		</div>

		<?php if ( ! $is_in_stock && 'disabled' === $out_of_stock_behavior ) : ?>
			<p class="woo-phone-order__out-of-stock-notice">
				<?php esc_html_e( 'This product is currently out of stock', 'woocommerce-phone-order' ); ?>
			</p>
		<?php endif; ?>

		<div
			class="woo-phone-order__message"
			data-wp-show="context.message"
			data-wp-class--success="context.messageType === 'success'"
			data-wp-class--error="context.messageType === 'error'"
			role="alert"
			aria-live="polite">
			<span data-wp-text="context.message"></span>
		</div>
	</form>
</div>
