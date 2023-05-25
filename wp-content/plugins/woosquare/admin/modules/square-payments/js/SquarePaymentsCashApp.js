(function ( $ ) {
	'use strict';

	const cashapp_appId = square_cashapp_params.application_id;
	const cashapp_locationId = square_cashapp_params.lid;
	
	function buildPaymentRequest(payments) {

		var id_of_div = jQuery('div#order_review tr.order-total span.woocommerce-Price-amount bdi').html();
		var total = id_of_div.split("span")[2];
		var total = total.substring(1, total.length);
		var total_price = total.toString();
		const req = payments.paymentRequest({
			countryCode: square_cashapp_params.country_code,
			currencyCode: square_cashapp_params.currency_code,
			total: {
			amount: total_price,
			label: 'Total',
			},
		});
		return req;

	}

	async function tokenize(paymentMethod) {

		const tokenResult = await
		paymentMethod.tokenize();
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

	let cashAppPay;
	async function initializeCashApp(payments) {
		
		if(cashAppPay != undefined){
			cashAppPay.destroy();
		}

		const paymentRequest = buildPaymentRequest(payments);
		const buttonOptions = {
			shape: 'semiround',
			width: 'full',
		};
		cashAppPay = await payments.cashAppPay(paymentRequest,{
			redirectURL: square_cashapp_params.checkout_url,
		});
		
		setTimeout(function(){
			jQuery('#rendering_cashapp_gateway').hide();
			cashAppPay.attach('#cash-app-pay', buttonOptions);
			
			cashAppPay.addEventListener('ontokenization', function (event) {
				const { tokenResult, error } = event.detail;
				if (error) {
					// developer handles error
					//console.log('error' + error);
				}
				else if (tokenResult.status === 'OK') {
					// developer passes token to backend for use with CreatePayment
					var $form = jQuery('form.woocommerce-checkout, form#order_review');
					$form.append('<input type="hidden" class="square-nonce" name="square_nonce" value="' + tokenResult.token + '" />');
					$form.submit();
				}
			});
		}, 1000);
		// return cashAppPay;

	}
/* 
	function init_cashapp(cashAppPay,payments){
		
		try {
			cashAppPay = initializeCashApp(payments);
		} catch (e) {
			console.error('Initializing Cash App Pay failed', e);
		}

	}
 */
	document.addEventListener('DOMContentLoaded', async function () {

		if (!window.Square) {
			throw new Error('Square.js failed to load properly');
		}

		const payments = window.Square.payments(cashapp_appId, cashapp_locationId);
		
		jQuery( document.body ).on( 'updated_checkout', function() {
			try {
				if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_cash_app_pay' ){
					cashAppPay = initializeCashApp(payments);
				}
			} catch (e) {
				console.error('Initializing Cash App Pay failed', e);
			}
			if(jQuery('.woocommerce-checkout-payment .input-radio:checked').val() == 'square_cash_app_pay' ){
				if(jQuery('.woocommerce-error').length > 0 && jQuery('.square-nonce').val()){ 
					jQuery('#cash-app-pay').html('CashApp payment already generated');
					var $form = jQuery('form.woocommerce-checkout, form#order_review');
					// $form.append('<input type="hidden" class="square-nonce" name="square_nonce" value="' + tokenResult.token + '" />');
					$form.submit();
				} 
			}

			/* init_cashapp(cashAppPay,payments);
			jQuery('input[type=radio][name=payment_method]').change(function() {
				// jQuery('body').trigger('update_checkout');
				// console.log(jQuery('#cash-app-pay').html().length);
				// console.log('CASHAPP' +jQuery("input[name='payment_method'][value='square_cash_app_pay']").prop("checked"));
				if(jQuery("input[name='payment_method'][value='square_cash_app_pay']").prop("checked")){
					init_cashapp(cashAppPay,payments);
				}
			}); */
		});

	});

}( jQuery ) );