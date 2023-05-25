(function ( $ ) {
	'use strict';
	
	const appId = square_ach_params.application_id;
	const locationId = square_ach_params.lid;
	const redirectURI = square_ach_params.redirectURL;
	const transactionId = square_ach_params.transactionId;
	
	async function initializeACH(payments) {
		const ach = await payments.ach({ redirectURI, transactionId });
		// Note: ACH does not have an .attach(...) method
		// the ACH auth flow is triggered by .tokenize(...)
		jQuery('.ach-button-div').append('<button id="ach-button">Pay with Bank Account</button>');
		const achButton = document.getElementById('ach-button');
		// setTimeout(function(){ 	 
			
			// achButton.disabled = false;
			ach.addEventListener(`ontokenization`, function (event) {
				const { tokenResult, error } = event.detail;
				if (error) {
					achButton.disabled = false; // Add this line.
					// add code here to handle errors
				}
				else if (tokenResult.status === `OK`) {
					try {
						var $form = jQuery('form.woocommerce-checkout, form#order_review');
						if ( document.getElementsByClassName('woocommerce-error')){
							jQuery('#ach-button').prop('disabled', false);
						}
						document.getElementById('card_nonce').value = tokenResult.token;
						$form.submit();
						
					} catch (e) {
						achButton.disabled = false;
						// add code here to handle errors
					}
				}
			});

			function handlePaymentMethodSubmission(event, paymentMethod, options ) {
				event.preventDefault();

				try {
					// disable the submit button as we await tokenization and make a
					// payment request.
					if ( document.getElementsByClassName('woocommerce-error')){
						document.getElementById('card_nonce').value = '';
					}
					achButton.disabled = true;
					jQuery('.woocommerce-error').remove();
					const token = tokenize(paymentMethod, options);
					//const paymentResults = createPayment(token);
					//displayPaymentResults('SUCCESS');
					//console.debug('Payment Success', paymentResults);

				} catch (e) {

					displayPaymentResults('FAILURE');
					achButton.disabled = false;
					console.error(e.message);
				}
			}
			
			
			if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_ach_payment' ){
				achButton.addEventListener('click', async function (event) {
					// jQuery('.woocommerce-error').remove();
					const paymentForm = document.getElementsByClassName('woocommerce-checkout')[1];
					const achOptions = getACHOptions(paymentForm);
					await handlePaymentMethodSubmission(event, ach, achOptions);
				});
			}
			
		// }, 1000);
		
		return ach;
	}
	
	// Call this function to send a payment token, buyer name, and other details
    // to the project server code so that a payment can be created with 
    // Payments API
	
	/* async function createPayment(token) {
		if ( document.getElementsByClassName('woocommerce-error')){
			jQuery('#ach-button').prop('disabled', false);		
		} 
			console.log('NONCE: ' + token);
			document.getElementById('card_nonce').value = token;
		jQuery('form.woocommerce-checkout').submit();
		
    }
	 */
	// This function tokenizes a payment method. 
    // The ‘error’ thrown from this async function denotes a failed tokenization,
    // which is due to buyer error (such as an expired card). It is up to the
    // developer to handle the error and provide the buyer the chance to fix
    // their mistakes.
   async function tokenize(paymentMethod, options = {}) {
        const tokenResult = await paymentMethod.tokenize(options);
        
        if (tokenResult.status === 'OK') {
			return tokenResult.token;
		} else {
			jQuery('#ach-button').prop('disabled', false);
			let errorMessage = tokenResult.status;
			if (tokenResult.errors) {
				errorMessage += tokenResult.errors;
			}
			throw new Error(errorMessage);
		}
    }
	
	  function getBillingContact(form) {
        const formData = new FormData(form);
        // It is expected that the developer performs form field validation
        // which does not occur in this example.
        return {
          givenName: formData.get('billing_first_name'),
          familyName: formData.get('billing_last_name'),
        };
      }

	function getACHOptions(form) {
		const billingContact = getBillingContact(form);
		console.log(billingContact);
		const accountHolderName = `${billingContact.givenName} ${billingContact.familyName}`;
		
		return { accountHolderName };
    }
	
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
		
		let payments;
		try {
          payments = window.Square.payments(appId, locationId);
        } catch {
          const statusContainer = document.getElementById(
            'payment-status-container'
          );
          statusContainer.className = 'missing-credentials';
          statusContainer.style.visibility = 'visible';
          return;
        }
		let ach;
		jQuery( document.body ).on( 'updated_checkout', function() {
			if(jQuery('.ach-button-div').html().length > 1){
				jQuery('.ach-button-div').html('');
			}
			try {
				if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_ach_payment' ){
					ach = initializeACH(payments);
				}
			} catch (e) {
				console.error('Initializing ACH failed', e);
				return;
			}
		});
    });

}( jQuery ) );


