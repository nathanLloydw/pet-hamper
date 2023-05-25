(function ( $ ) {
	'use strict';

	const afterpay_appId = square_afterpay_params.application_id;
	const afterpay_locationId = square_afterpay_params.lid;
	
	function buildPaymentRequest(payments) {
		
		var id_of_div = jQuery('div#order_review tr.order-total span.woocommerce-Price-amount bdi').html();
		var total = id_of_div.split("span")[2];
		var total = total.substring(1, total.length);
		var total_price = total.toString();
		//console.log(total_price);
		const req = payments.paymentRequest({
			countryCode: square_afterpay_params.country_code,
			currencyCode: square_afterpay_params.currency_code,
			total: {
			amount: total_price,
			label: 'Total',
			},
			requestShippingContact: true,
		});

		// Note how afterpay has its own listeners
		req.addEventListener('afterpay_shippingaddresschanged', function (_address) {
			return {
				shippingOptions: [{
					amount: '0.00',
					id: 'shipping-option-1',
					label: 'Flat rate',
					taxLineItems: [],
					total: {
						amount: total_price,
						label: 'total',
					}
				}]
			};
		});
		req.addEventListener('afterpay_shippingoptionchanged', function (_option) {
			// This event listener is for information purposes only.
			// Changes here (or values returned) will not affect the Afterpay/Clearpay PaymentRequest.
		});

		return req;
	}

	let afterpay;

	async function initializeAfterpay(payments) {
		const paymentRequest = buildPaymentRequest(payments);
		if(jQuery('#afterpay-button').html().length > 1){
			afterpay.destroy();
		}
		afterpay = await payments.afterpayClearpay(paymentRequest);

		setTimeout(function(){ 	 
			afterpay.attach('#afterpay-button');
			jQuery('#rendering_afterpay_gateway').hide();
			const afterpayButton = document.getElementById('afterpay-button');
			
			async function handlePaymentMethodSubmission(event, paymentMethod) {
				event.preventDefault();
				try {
					// disable the submit button as we await tokenization and make a
					// payment request.
					// cardButton.disabled = true;
					jQuery('.woocommerce-error').remove();
					const token =  tokenize(paymentMethod);
				} catch (e) {
					// cardButton.disabled = false;
					console.error(e.message);
				}
			}
			
			if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_after_pay' ){
    			afterpayButton.addEventListener('click', async function (event) {
					await handlePaymentMethodSubmission(event, afterpay);
				});
			}
			
		}, 1000);
		// return afterpay;
	}
	
	async function tokenize(paymentMethod) {

		const tokenResult = await
		paymentMethod.tokenize();
		if (tokenResult.status === 'OK') {
			
			var $form = jQuery('form.woocommerce-checkout, form#order_review');
			// inject nonce to a hidden field to be submitted
			/*$form.append( '<input type="hidden" class="errors" name="errors" value="' + errors + '" />' );
			 $form.append( '<input type="hidden" class="noncedatatype" name="noncedatatype" value="' + noncedatatype + '" />' );
			 $form.append( '<input type="hidden" class="cardData" name="cardData" value="' + cardData + '" />' );
			 */
			$form.append('<input type="hidden" class="square-nonce" name="square_nonce" value="' + tokenResult.token + '" />');


			$form.submit();
			// console.debug('Payment Success', displayPaymentResults);
		} else {
			let errorMessage = tokenResult.status;
			if (tokenResult.errors) {
				errorMessage += tokenResult.errors;
			}
			throw new Error(errorMessage);
		}
	}

// Helper method for displaying the Payment Status on the screen.
// status is either SUCCESS or FAILURE;
	function displayPaymentResults(status) {
		const statusContainer = document.getElementById(
				'payment-status-container'
		);
		if (status === 'SUCCESS') {
			statusContainer.classList.remove('is-failure');
			statusContainer.classList.add('is-success');
		} else {
			statusContainer.classList.remove('is-success');
			statusContainer.classList.add('is-failure');
		}

		statusContainer.style.visibility = 'visible';
	}
	function init_afterpay(afterpay,payments){
		try {
			afterpay = initializeAfterpay(payments);
			// return afterpay;
		} catch (e) {
			console.error('Initializing After Pay failed', e);
			return;
		}
	}
	document.addEventListener('DOMContentLoaded', async function () {
		if (!window.Square) {
			throw new Error('Square.js failed to load properly');
		}
		const payments = window.Square.payments(afterpay_appId, afterpay_locationId);

		// let afterpay;
		
		jQuery( document.body ).on( 'updated_checkout', function() {
			try {
				if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_after_pay' ){
					afterpay = initializeAfterpay(payments);
				}
				// return afterpay;
			} catch (e) {
				console.error('Initializing After Pay failed', e);
				return;
			}
			/* init_afterpay(afterpay,payments);
			jQuery('input[type=radio][name=payment_method]').change(function() {
				// console.log(jQuery("input[name='payment_method'][value='square_after_pay']").prop("checked"));
				if(jQuery("input[name='payment_method'][value='square_after_pay']").prop("checked")){
					init_afterpay(afterpay,payments);
				}
			});	 */
		});
		
			

	});


}( jQuery ) );
