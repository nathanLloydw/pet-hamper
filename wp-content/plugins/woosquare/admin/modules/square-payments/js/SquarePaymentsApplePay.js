(function ( $ ) {
	'use strict';
const Apay_appId = squareapplepay_params.application_id;
const Apay_locationId = squareapplepay_params.lid;

function buildPaymentRequest(payments) {
	return payments.paymentRequest({
		countryCode:squareapplepay_params.country_code,
		currencyCode: squareapplepay_params.currency_code ,
		total: {
			amount: squareapplepay_params.order_total,
			label: 'Total',
		},
	});
}

async function tokenize(paymentMethod) {

	const tokenResult = await paymentMethod.tokenize();
	if (tokenResult.status === 'OK') {
		return tokenResult.token;
	} else {
		let errorMessage = tokenResult.status;
		if (tokenResult.errors) {
			errorMessage += tokenResult.errors;
		}
		throw new Error(errorMessage);
	}
}


async function initializeApplePay(payments) {
	
	
	const paymentRequest = buildPaymentRequest(payments)
	const applePay = await payments.applePay(paymentRequest);
	// Note: You do not need to `attach` applePay.
	
	setTimeout(function(){
		
		// jQuery('#rendering_applepay_gateway').hide();
		const cardButton = document.getElementById(
				'apple-pay-button'
		);
	
	 function handlePaymentMethodSubmission(event, paymentMethod) {
		
		//debugger;
		event.preventDefault();
		
		
		try {
			// disable the submit button as we await tokenization and make a
			// payment request.
			const token =  tokenize(paymentMethod);
			
			if(token.status !== undefined) {
			

				var $form = jQuery( 'form.woocommerce-checkout, form#order_review' );
				// inject nonce to a hidden field to be submitted
				/*$form.append( '<input type="hidden" class="errors" name="errors" value="' + errors + '" />' );
				 $form.append( '<input type="hidden" class="noncedatatype" name="noncedatatype" value="' + noncedatatype + '" />' );
				 $form.append( '<input type="hidden" class="cardData" name="cardData" value="' + cardData + '" />' );
				 */
				$form.append( '<input type="hidden" class="square-nonce" name="square_nonce" value="' + token + '" />' );
				$form.submit();
			} else{
				var html = '';
				html += '<ul class="woocommerce_error woocommerce-error">';
				$('#place_order').prop('disabled', false);
				html += '<li>' + token + '</li>';
				html += '</ul>';
				$( '.payment_method_square_plus fieldset' ).eq(0).prepend( html );
				var $form = jQuery( 'form.woocommerce-checkout, form#order_review' );
				$form.append( '<input type="hidden" class="square_submit_error" name="square_submit_error" value="' + html + '" />' );
			}
			console.debug('Payment Success', displayPaymentResults);
		} catch (e) {
			console.error(e.message);
		}
	}
	
	//Checkpoint 2
	if(applePay !== undefined) {
		const applePayButton = document.getElementById('apple-pay-button');
		
		applePayButton.addEventListener('click', async function (event) {
			
			 handlePaymentMethodSubmission(event, applePay);
		});
	}
	

	}, 2000);
	return applePay;
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

document.addEventListener('DOMContentLoaded', async function () {
	
	
	if (!window.Square) {
		throw new Error('Square.js failed to load properly');
	}
	const payments = window.Square.payments(Apay_appId, Apay_locationId);

	let applePay;
	
	jQuery( document.body ).on( 'updated_checkout', function() {
		
		try {
			if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_apple_pay' ){
				applePay =  initializeApplePay(payments);
			}
		} catch (e) {
			
			jQuery( "#browser_support_msg" ).text("Apple Pay is not available on this browser.");
			document.getElementById("apple-pay-button").style.display = "none";
			console.log('Initializing Apple Pay failed', e);
		}
	});
	
	
});
}( jQuery ) );

