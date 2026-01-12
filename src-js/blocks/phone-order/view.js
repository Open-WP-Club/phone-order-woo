/**
 * Phone Order Block - Frontend Interactivity
 *
 * WordPress 6.5+ Interactivity API
 * Modern reactive frontend without jQuery
 *
 * @package OpenWPClub\PhoneOrder
 */

import { store, getContext } from '@wordpress/interactivity';

const { state } = store('phone-order', {
	state: {
		isSubmitting: false,
		message: '',
		messageType: '',
	},
	actions: {
		*submitForm(event) {
			event.preventDefault();
			event.stopPropagation(); // Prevent event from bubbling to parent forms

			const context = getContext();
			// Support both form and div containers
			const container = event.target.closest('#woo-phone-order-form') || event.target.closest('form');
			const phoneInput = container.querySelector('input[name="phone"]');
			const productId = container.dataset.productId;

			// Validate phone
			if (!phoneInput.value || phoneInput.value.trim() === '') {
				context.message = wooPhoneOrderParams.i18n.error || 'Please enter your phone number';
				context.messageType = 'error';
				return;
			}

			// Set submitting state
			context.isSubmitting = true;
			context.message = '';
			context.messageType = '';

			try {
				// Submit via AJAX
				const formData = new FormData();
				formData.append('action', 'wc_phone_order_submit');
				formData.append('phone', phoneInput.value.trim());
				formData.append('product_id', productId);
				formData.append('nonce', wooPhoneOrderParams.nonce);

				const response = yield fetch(wooPhoneOrderParams.ajaxUrl, {
					method: 'POST',
					body: formData,
				});

				const data = yield response.json();

				if (data.success) {
					context.message = data.data.message || wooPhoneOrderParams.i18n.success;
					context.messageType = 'success';
					phoneInput.value = ''; // Clear form
				} else {
					context.message = data.data?.message || wooPhoneOrderParams.i18n.error;
					context.messageType = 'error';
				}
			} catch (error) {
				context.message = wooPhoneOrderParams.i18n.error || 'An error occurred';
				context.messageType = 'error';
				console.error('Phone Order Error:', error);
			} finally {
				context.isSubmitting = false;
			}
		},
	},
});
