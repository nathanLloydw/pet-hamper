(function ( $ ) {
	'use strict';
	const appId = square_params.application_id;
	const locationId = square_params.locationId; 
	let card;
	async function initializeCard(payments) {
		
		if(jQuery('#card-container').html().length > 1){
			card.destroy();
		}
		card = await payments.card();
		 
		setTimeout(function(){ 	
			
			card.attach('#card-container');
			
			const cardButton = document.getElementById(
				'place_order'
			);
			
			function handlePaymentMethodSubmission(event, paymentMethod, shouldVerify = false,payments) {
				event.preventDefault();
				try {
					// disable the submit button as we await tokenization and make a
					// payment request.
					cardButton.disabled = true;
					jQuery('.woocommerce-error').remove();
					const token =  tokenize(paymentMethod,payments);
				} catch (e) {
					cardButton.disabled = false;
					console.error(e.message);
				}
			}
			cardButton.addEventListener('click', async function (event) {
			if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() =='square_plus'){
				 handlePaymentMethodSubmission(event, card, true,payments);
			}
			});
		}, 2000);
		return card;
    }


	// This function tokenizes a payment method. 
	// The â€˜errorâ€™ thrown from this async function denotes a failed tokenization,
	// which is due to buyer error (such as an expired card). It is up to the
	// developer to handle the error and provide the buyer the chance to fix
	// their mistakes.
	async function tokenize(paymentMethod,payments) {
		const tokenResult = await paymentMethod.tokenize();
		if (tokenResult.status === 'OK') {
		    if(jQuery( '#sq-card-saved' ).is(":checked")){
    			var intten = 'STORE';
    		} else if(square_params.subscription) {
    			var intten = 'STORE';
    		} else if(
    		jQuery( '._wcf_flow_id' ).val() != null ||  
    		jQuery( '._wcf_flow_id' ).val() != undefined || 
    		
    		jQuery( '._wcf_checkout_id' ).val() != null ||  
    		jQuery( '._wcf_checkout_id' ).val() != undefined 
    		) {
    			var intten = 'STORE';
    		} else if(jQuery( '.is_preorder' ).val()) {
    			var intten = 'STORE';
    		} else {
    			var intten = 'CHARGE';
    		}
    		const verificationDetails = {
    			intent: intten, 
    			amount: square_params.cart_total, 
    			currencyCode: square_params.get_woocommerce_currency, 
    			billingContact: {}
    		};
    		const verificationResults = await payments.verifyBuyer(
    			tokenResult.token,
    			verificationDetails
    
    		);
            if (verificationResults !== undefined && tokenResult.token !== undefined) {
					const pay_form = jQuery( 'form.woocommerce-checkout, form#order_review' );
					pay_form.append( '<input type="hidden" class="buyerVerification-token" name="buyerVerification_token" value="'+ verificationResults.token +'"  />' );
        			if ( document.getElementsByClassName('woocommerce-error')){
            			jQuery('#place_order').prop('disabled', false);		
            		} 
            		// inject nonce to a hidden field to be submitted
            		pay_form.append( '<input type="hidden" class="square-nonce" name="square_nonce" value="' + tokenResult.token + '" />' );
            		
            		    var pay_for_order = getUrlParameter('pay_for_order');
            			if(pay_for_order){
                		    jQuery('form#order_review').submit(); 
                	    }
            		
            		
            		jQuery('form.woocommerce-checkout').submit();
			} else {
				jQuery('#place_order').prop('disabled', false);
			}
		} else {
			let errorMessage = `Tokenization failed-status: ${tokenResult.status}`;
			if (tokenResult.errors) {
				errorMessage += ` and errors: ${JSON.stringify(
					tokenResult.errors
				)}`;
				jQuery('#place_order').prop('disabled', false);
			}	
			throw new Error(errorMessage);
		}
	}
	var getUrlParameter = function getUrlParameter(sParam) {
        var sPageURL = window.location.search.substring(1),
            sURLVariables = sPageURL.split('&'),
            sParameterName,
            i;
    
        for (i = 0; i < sURLVariables.length; i++) {
            sParameterName = sURLVariables[i].split('=');
    
            if (sParameterName[0] === sParam) {
                return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
            }
        }
        return false;
    };

	document.addEventListener('DOMContentLoaded', async function () {
		if (!window.Square) {
			throw new Error('Square.js failed to load properly');
		}
		const payments = window.Square.payments(appId, locationId);
		// let card;
        /*try {
			card = await initializeCard(payments);
		} catch (e) {
			console.error('Initializing Card failed', e);
			return;
		}*/
		
		var pay_for_order = getUrlParameter('pay_for_order');
		// console.log(pay_for_order);
		if(pay_for_order){
		    card =  initializeCard(payments);
	    }
		jQuery( document.body ).on( 'updated_checkout', function() {
			try {
			    if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_plus' ){
					card =  initializeCard(payments);
				}
				return card;
    		} catch (e) {
    			console.error('Initializing Card failed', e);
    			return;
    		}	
		});
		
		
		
	});
}( jQuery ) );

jQuery( window  ).on("load", function() {
    hideunhide();
});

jQuery( function($){
	$('form.checkout').on('change', '.woocommerce-checkout-payment input', function(){
		hideunhide();
	});
});
function hideunhide(){
	jQuery('input[type=radio][name=payment_method]').on('change', function(){
		console.log(jQuery('.woocommerce-checkout-payment .input-radio:checked').val());
		jQuery('body').trigger('update_checkout');
	})
	if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_plus' ){
		jQuery('#place_order').css('display', 'block');
	}else if( jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_google_pay' ){
		jQuery('#place_order').css('display', 'none');
	} else if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_apple_pay' ){
		jQuery('#place_order').css('display', 'none');
	}else if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_ach_payment' ){
		jQuery('#place_order').css('display', 'none');
	}else if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_after_pay' ){
		jQuery('#place_order').css('display', 'none');
	}else if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_cash_app_pay' ){
		jQuery('#place_order').css('display', 'none');
	} else {
		jQuery('#place_order').css('display', 'block');
	}
}