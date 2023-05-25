(function ( $ ) {
	'use strict';

	const appId = squaregpay_params.application_id;
	const locationId = squaregpay_params.lid; 
	
	function buildPaymentRequest(payments) {
		return payments.paymentRequest({
			countryCode: squaregpay_params.country_code,
			currencyCode: squaregpay_params.currency_code,
			total: {
				amount: squaregpay_params.order_total,
				label: 'Total',
			},
		});
	}
	
	let googlePay;
	async function initializeGooglePay(payments) {

		if(jQuery('#google-pay-button').html().length > 1){
			googlePay.destroy();
		}
		const paymentRequest = buildPaymentRequest(payments);
		googlePay = await payments.googlePay(paymentRequest);
		
		setTimeout(function(){ 	 
			jQuery('#rendering_googlepay_gateway').hide();    
			googlePay.attach('#google-pay-button');
			const googlePayButton = document.getElementById('google-pay-button');
			
			function handlePaymentMethodSubmission(event, paymentMethod, shouldVerify = false,payments) {
				event.preventDefault();
				try {
					// disable the submit button as we await tokenization and make a
					// payment request.
					// cardButton.disabled = true;
					jQuery('.woocommerce-error').remove();
					const token =  tokenize(paymentMethod,payments);
				} catch (e) {
					// cardButton.disabled = false;
					console.error(e.message);
				}
			}
			
			
			if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_google_pay' ){
			    
    			googlePayButton.addEventListener('click', async function (event) {
					handlePaymentMethodSubmission(event, googlePay);
				})
			}
			
		}, 2000);
		
	}
	
	// Call this function to send a payment token, buyer name, and other details
	// to the project server code so that a payment can be created with 
	// Payments API
	// This function tokenizes a payment method. 
	// The ‘error’ thrown from this async function denotes a failed tokenization,
	// which is due to buyer error (such as an expired card). It is up to the
	// developer to handle the error and provide the buyer the chance to fix
	// their mistakes.
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

		/*cardButton.disabled = true;

		 const paymentResults = await createPayment(token);
		 displayPaymentResults('SUCCESS');*/

		// console.debug('Payment Success', paymentResults);
	
		// return tokenResult.token;
		} else {
			let errorMessage = tokenResult.status;
			if (tokenResult.errors) {
				errorMessage += tokenResult.errors;
			}
			throw new Error(errorMessage);
		}
	}
	
	document.addEventListener('DOMContentLoaded', async function () {
		if (!window.Square) {
			throw new Error('Square.js failed to load properly');
		}
		
		const payments = window.Square.payments(appId, locationId);
		let googlePay;
		
		jQuery( document.body ).on( 'updated_checkout', function() {
			
			try {
				if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_google_pay' ){
					googlePay = initializeGooglePay(payments);
				}
			} catch (e) {
				console.error('Initializing Google Pay failed', e);
				return;
			}
		});
		

	});
	
}( jQuery ) );