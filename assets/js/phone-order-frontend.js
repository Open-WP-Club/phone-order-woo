/**
 * Phone Order Frontend Script
 * Simple vanilla JS without Interactivity API
 */
(function() {
	'use strict';

	document.addEventListener('DOMContentLoaded', function() {
		initPhoneOrderForms();
	});

	function initPhoneOrderForms() {
		var forms = document.querySelectorAll('#woo-phone-order-form');

		forms.forEach(function(form) {
			var button = form.querySelector('.woo-phone-order__submit-button');
			var input = form.querySelector('.woo-phone-order__phone-input');
			var messageEl = form.querySelector('.woo-phone-order__message');
			var productId = form.dataset.productId;

			if (!button || !input) return;

			button.addEventListener('click', function(e) {
				e.preventDefault();
				e.stopPropagation();

				var phone = input.value.trim();

				// Validate - empty phone
				if (!phone) {
					showMessage(messageEl, wooPhoneOrderParams.i18n.emptyPhone, 'error');
					return;
				}

				// Disable button
				button.disabled = true;
				var originalText = button.textContent;
				button.textContent = wooPhoneOrderParams.i18n.submitting;

				// Send AJAX
				var formData = new FormData();
				formData.append('action', 'wc_phone_order_submit');
				formData.append('phone', phone);
				formData.append('product_id', productId);
				formData.append('nonce', wooPhoneOrderParams.nonce);

				fetch(wooPhoneOrderParams.ajaxUrl, {
					method: 'POST',
					body: formData
				})
				.then(function(response) {
					return response.json();
				})
				.then(function(data) {
					if (data.success) {
						showMessage(messageEl, data.data.message || wooPhoneOrderParams.i18n.success, 'success');
						input.value = '';
					} else {
						showMessage(messageEl, data.data.message || wooPhoneOrderParams.i18n.error, 'error');
					}
				})
				.catch(function(error) {
					console.error('Phone Order Error:', error);
					showMessage(messageEl, wooPhoneOrderParams.i18n.error, 'error');
				})
				.finally(function() {
					button.disabled = false;
					button.textContent = originalText;
				});
			});
		});
	}

	function showMessage(el, message, type) {
		if (!el) return;

		el.textContent = message;
		el.style.display = 'block';
		el.classList.remove('success', 'error');
		el.classList.add(type);
	}
})();
