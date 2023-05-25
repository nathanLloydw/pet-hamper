<?php 

class WooSquareACHPayment_Gateway extends WC_Payment_Gateway { 
	
	protected $connect;
	protected $token;
	public $log;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id		            = 'square_ach_payment';
		$this->method_title 	    = __( 'Square ACH Payment', 'wpexpert-square' );
		$this->method_description   = __( 'Square ACH Payment works by adding payments button in an woocommerce checkout and then sending the details to Square for verification and processing.', 'wpexpert-square' );
		$this->has_fields 	        = true;
		$this->supports 	        = array(
			'products',
			'refunds',
		);

		// Load the form fields
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values
		$this->title           = $this->get_option( 'title' );
		$this->description     = $this->get_option( 'description' );
		$this->enabled         = $this->get_option( 'enabled' ) === 'yes' ? 'yes' : 'no';
		// $this->capture         = $this->get_option( 'capture' ) === 'yes' ? false : true;
		$this->logging         = $this->get_option( 'logging' ) === 'yes' ? true : false;
		$this->connect         = new WooSquare_Payments_Connect(); // decouple in future when v2 is ready
		$this->token           = get_option( 'woo_square_access_token'.get_transient('is_sandbox') );

		$this->connect->set_access_token( $this->token );
		

		// Hooks
		        
        $woocommerce_square_ach_payment_settings = get_option('woocommerce_square_ach_payment_settings');
        if('yes' == @$woocommerce_square_ach_payment_settings['enabled'] ) {
            add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts_ach_payment' ) );
        }
        
		
		add_action( 'admin_notices', array( $this, 'admin_notices_ach_payment' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Check if required fields are set
	 */
	public function admin_notices_ach_payment() {
		if ( ! $this->enabled ) {
			return;
		}

		// Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected
		if ( ! WC_SQUARE_ENABLE_STAGING && get_option( 'woocommerce_force_ssl_checkout' ) === 'no' && ! class_exists( 'WordPressHTTPS' ) ) {
			echo '<div class="error"><p>' . sprintf( esc_html__( 'Square is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout is not secured! Please enable SSL and ensure your server has a valid SSL certificate.', 'wpexpert-square' ), esc_url(admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ). '</p></div>';
		}
	}

	/**
	 * Check if this gateway is enabled
	 */
	public function is_available() {
		
		$is_available = true;
		
		if ( $this->enabled == 'yes' ) {
			if ( ! WC_SQUARE_ENABLE_STAGING && ! wc_checkout_is_https() ) {
				$is_available = false;
			}

			if ( ! WC_SQUARE_ENABLE_STAGING && empty( $this->token ) ) {
				$is_available = true;
			}
			

			if ( !get_option( 'woo_square_access_token_cauth'.get_transient('is_sandbox') )) {
				$is_available = false;
			}
			

			// Square only supports US, Canada and Australia for now.
			if ( ( 
				'US' !== WC()->countries->get_base_country()  ) || ( 
				'USD' !== get_woocommerce_currency() ) 
				) {
				$is_available = false;
			}
			
			
			// if enabled and sandbox credentials not setup.
			if(get_transient('is_sandbox')){
				if(
					empty(WOOSQU_PLUS_APPID)
					||
					empty(get_option('woo_square_access_token'.get_transient('is_sandbox')))
					||
					empty(get_option('woo_square_location_id'.get_transient('is_sandbox')))
				){
					$is_available = false;
				}
			}
						
			
		} else {
			$is_available = false;
		}
		
		return apply_filters( 'woocommerce_square_payment_ach_gateway_is_available', $is_available );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = apply_filters( 'woocommerce_square_ach_gateway_settings', array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'wpexpert-square' ),
				'label'       => __( 'Enable Square ACH Payment', 'wpexpert-square' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'wpexpert-square' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'wpexpert-square' ),
				'default'     => __( 'ACH Payment (Square)', 'wpexpert-square' )
			),
			'description' => array(
				'title'       => __( 'Description', 'wpexpert-square' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'wpexpert-square' ),
				'default'     => __( 'Pay with your Bank Account via Square.', 'wpexpert-square')
			),
			'logging' => array(
				'title'       => __( 'Logging', 'wpexpert-square' ),
				'label'       => __( 'Log debug messages', 'wpexpert-square' ),
				'type'        => 'checkbox',
				'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'wpexpert-square' ),
				'default'     => 'no'
			),
		) );
	}

	 
	/**
	 * Payment form on checkout page
	 */
	public function payment_fields() { ?>
		<fieldset class="wooSquare-ach-payment">
		
				<label for="ach-button"><?php esc_html_e( 'ACH Payment', 'woosquare' ); ?><span class="required">*</span></label>
				<div class = "ach-button-div"></div>
				 <input type="hidden" id="card_nonce" name="card_nonce" />
			<p>
				<div id="payment-status-container"></div>
			</p>
		</fieldset>
		<?php
	}
    
    /**
	 * get_country_code_scripts function.
	 *
	 *
	 * @access public
	 */
    
    /* public function get_country_codes( $currency_code ) {

		$currency_symbol = '';

		$currency_symbol = 'US';
	
		return $currency_symbol;
	
	} */
    

	/**
	 * payment_scripts function.
	 *
	 *
	 * @access public
	 */
	public function payment_scripts_ach_payment() {
		if ( ! is_checkout() ) {
			return;
		}
		$location = get_option('woo_square_location_id'.get_transient('is_sandbox'));
		
		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		
		
		global $woocommerce;
        $woocommerce_square_settings = get_option('woocommerce_square_settings'.get_transient('is_sandbox'));
        $currency_cod  = get_option('woocommerce_currency');
        $country_code = WC()->countries->get_base_country();
        //need to add condition square payment enable so disable below script.
		if(!empty(get_transient('is_sandbox'))){
			wp_enqueue_script( 'square-ach-js', 'https://sandbox.web.squarecdn.com/v1/square.js', array(), WooSquare_VERSION);
		} else {
			wp_enqueue_script( 'square-ach-js', 'https://web.squarecdn.com/v1/square.js', array(), WooSquare_VERSION);
		}

		wp_enqueue_script( 'woosquare-ach-payment', WooSquare_PLUGIN_URL_PAYMENT . '/js/SquarePaymentsACHPay.js', array(), WooSquare_VERSION);
		wp_localize_script( 'woosquare-ach-payment', 'square_ach_params', array(
			'application_id'               =>   WOOSQU_PLUS_APPID,
			'lid'						   =>   $location,
			'merchant_name'				   =>   "Square ACH Payment",
			'order_total'				   =>   $woocommerce->cart->total,
            'currency_code'                =>   $currency_cod, 
            'country_code'                 => 	$country_code,
			'redirectURL' 					=>	 wc_get_checkout_url(),
			'transactionId'					=>	 rand(),
		) );
		wp_enqueue_script( 'woosquare-ach-payment' );
		
		wp_enqueue_style( 'woocommerce-square-ach-styles', WooSquare_PLUGIN_URL_PAYMENT . '/css/SquareFrontendStyles_ach_payment.css' );

		return true;
	}
	
	/**
	 * Process the payment
	 */
	public function process_payment( $order_id, $retry = true ) {
		
		$order    = wc_get_order( $order_id );
		$nonce    = isset( $_POST['card_nonce'] ) ? wc_clean( $_POST['card_nonce'] ) : '';
		$errors	  = (isset( $_POST['errors'] ) ? sanitize_text_field( wp_unslash( $_POST['errors'] ) ): '');
		$noncedatatype	  = (isset( $_POST['noncedatatype'] ) ? sanitize_text_field( wp_unslash( $_POST['noncedatatype'] ) ): '');
		$cardData	  = (isset( $_POST['cardData'] ) ? sanitize_text_field( wp_unslash( $_POST['cardData'] ) ): '');
		update_post_meta( $order->id, '_POST_requuest'.rand(1,1000), sanitize_text_field($_POST));
		update_post_meta( $order->id, 'errors_ach', $errors ); 
		update_post_meta( $order->id, 'errors_noncedatatype', $noncedatatype ); 
		update_post_meta( $order->id, 'errors_cardData', $cardData );
		$currency = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->get_order_currency() : $order->get_currency();
		$this->log( "Info: Begin processing payment for order {$order_id} for the amount of {$order->get_total()}" );
		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');				
		if($woocommerce_square_plus_settings['Send_customer_info'] == 'yes'){
			$first_name = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_first_name : $order->get_billing_first_name();
			$last_name = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_last_name : $order->get_billing_last_name();
			if(empty($first_name) and empty($last_name)){
				$first_name = $last_name = null;
			}
		} else {
			$first_name = $last_name = null;
		}
		try {
			
			
			
			if(function_exists('square_order_sync_add_on')){
				$amount = (int) round($this->format_amount( $order->get_total(), $currency ),1);
			} else {
				$amount = (int) $this->format_amount( $order->get_total(), $currency );
			}

			
			$idempotency_key = uniqid();
			$data = array(
				'idempotency_key' => $idempotency_key,
				'amount_money'    => array(
					'amount'   => $amount,
					'currency' => $currency,
				),
				'reference_id'        => (string) $order->get_order_number(),
				// 'autocomplete'       => $this->capture,
				'source_id'          => $nonce,
				'buyer_email_address' => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_email : $order->get_billing_email(),
				'billing_address'     => array(
					'address_line_1'                  => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_address_1 : $order->get_billing_address_1(),
					'address_line_2'                  => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_address_2 : $order->get_billing_address_2(),
					'locality'                        => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_city : $order->get_billing_city(),
					'administrative_district_level_1' => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_state : $order->get_billing_state(),
					'postal_code'                     => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_postcode : $order->get_billing_postcode(),
					'country'                         => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_country : $order->get_billing_country(),
				),
				'note' => apply_filters( 'woosquare_payment_order_note', 'WooCommerce: Order #' . (string) $order->get_order_number().' '.$first_name.' '.$last_name, $order ),
			);
			
			if ( $order->needs_shipping_address() ) {
				$data['shipping_address'] = array(
					'address_line_1'                  => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_address_1 : $order->get_shipping_address_1(),
					'address_line_2'                  => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_address_2 : $order->get_shipping_address_2(),
					'locality'                        => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_city : $order->get_shipping_city(),
					'administrative_district_level_1' => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_state : $order->get_shipping_state(),
					'postal_code'                     => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_postcode : $order->get_shipping_postcode(),
					'country'                         => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->shipping_country : $order->get_shipping_country(),
				);
			}
			
			
			$msg = '';
			$endpoint = 'squareup';
			$location_id = get_option('woo_square_location_id'.get_transient('is_sandbox'));
			if(!empty(get_transient('is_sandbox'))){
				$msg = ' via Sandbox ';
				$endpoint = 'squareupsandbox';
			}
			
			if(get_option('woo_square_customer_sync_square_order_sync') == "1"){
			$api_config = '';
	    	$api_client = '';
			$api_config = new \SquareConnect\Configuration();
			$api_config->setHost("https://connect.squareup".get_transient('is_sandbox').".com");
			$api_config->setAccessToken($this->token);
			$api_client = new \SquareConnect\ApiClient($api_config);
			
			    //create customer
				$square_customer_id = null;
				$customer_api = new \SquareConnect\Api\CustomersApi($api_client);
				//check if customer exist  
				$customer_id = $order->get_customer_id();
				
				if ($customer_id) {
					$square_customer_id = get_user_meta($customer_id, '_square_customer_id', true);
				} else {
					if ($parent_order_id)
						$square_customer_id = get_post_meta($parent_order_id, '_square_customer_id', true);
					else
						$square_customer_id = get_post_meta($order_id, '_square_customer_id', true);
				}
			
				//check if there is customer id and not exist in square account
				if ($square_customer_id) {
					try {
						$customer = $customer_api->retrieveCustomer( $square_customer_id );
						
					} catch (Exception $ex) {
						//customer not exist
						$square_customer_id = null;
					}
				}

				if (!$square_customer_id) {
					
					$body = new \SquareConnect\Model\CreateCustomerRequest();
					
					$body->setGivenName($order->get_shipping_first_name() ? $order->get_shipping_first_name() : $order->get_billing_first_name());
					$body->setFamilyName($order->get_shipping_last_name() ? $order->get_shipping_last_name() : $order->get_billing_last_name());
					$body->setEmailAddress($order->get_billing_email());
					$body->setAddress($shipping_address);
					$body->setPhoneNumber($order->get_billing_phone());
					$body->setReferenceId($customer_id ? (string) $customer_id : __('Guest', 'woosquare'));
					$square_customer = $customer_api->createCustomer($body);
					
					
					
					$square_customer = json_decode($square_customer, true);
					
					if (isset($square_customer['customer']['id'])) {
						$square_customer_id = $square_customer['customer']['id'];
						if ($customer_id) {
							update_user_meta($customer_id, '_square_customer_id', $square_customer_id);
						} else {
							if ($parent_order_id)
								update_post_meta($parent_order_id, '_square_customer_id', $square_customer_id);
							else
								update_post_meta($order_id, '_square_customer_id', $square_customer_id);
						}
					}
				}
			} else {
				$square_customer_id = null;    
			}	
			
			if(function_exists('square_order_sync_add_on')){
				$data['order_id'] = square_order_sync_add_on($order,$location_id,$currency,$idempotency_key,$this->token,$endpoint,$square_customer_id);
			}	
			
			update_post_meta( $order->id, 'request_Data'.rand(1,1000), $data);

        	$url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/payments";
			$headers = array(
				'Square-Version'  => '2021-03-17',
				'Accept' => 'application/json',
				'Authorization' => 'Bearer '.$this->token,
				'Content-Type' => 'application/json',
				'Cache-Control' => 'no-cache'
			);
		
			$result = json_decode(wp_remote_retrieve_body(wp_remote_post($url, array(
					'method' => 'POST',
					'headers' => $headers,
					'httpversion' => '1.0',
					'sslverify' => false,
					'body' => json_encode($data)
					)
				    )
		        )
			);
			
			update_post_meta( $order->id, 'woosquare_request_results_ach_'.rand(1,1000), $result);
			
			if ( is_wp_error( $result ) ) {
				wc_add_notice( __( 'Error: Unable to complete your transaction with square due to some issue. For now you can try some other payment method or try again later.', 'wpexpert-square' ), 'error' );

				throw new Exception( $result->get_error_message() );
			}

			if ( ! empty( $result->errors ) ) {
				if ( 'INVALID_REQUEST_ERROR' === $result->errors[0]->category ) {
					wc_add_notice( __( 'Error: Unable to complete your transaction with square due to some issue. For now you can try some other payment method or try again later.', 'wpexpert-square' ), 'error' );
				}
				
				if ( 'PAYMENT_METHOD_ERROR' === $result->errors[0]->category || 'VALIDATION_ERROR' === $result->errors[0]->category ) {
					// format errors for display
					$error_html = __( 'Payment Error: ', 'wpexpert-square' );
					$error_html .= '<br />';
					$error_html .= '<ul>';

					foreach( $result->errors as $error ) {
						$error_html .= '<li>' . $error->detail . '</li>';
					}

					$error_html .= '</ul>';

					wc_add_notice( $error_html, 'error' );
				}
				
				$errors = print_r( $result->errors, true );

				throw new Exception( $errors );
			}

			if ( empty( $result ) ) {
				wc_add_notice( __( 'Error: Unable to complete your transaction with square due to some issue. For now you can try some other payment method or try again later.', 'wpexpert-square' ), 'error' );

				throw new Exception( 'Unknown Error' );
			}

			if (isset($result->payment->id) and !empty(get_transient('is_sandbox')) and $result->payment->status == 'PENDING'){
				
				// Payment complete
				add_post_meta( $order->id, 'woosquare_transaction_id', $result->payment->id, true );
				
				// Mark as on-hold
				$authorized_message = sprintf( __( 'ACH Payment '.$msg.' (Payment ID: %s). will be charged after 1 minute.', 'wpexpert-square' ), $result->payment->id );
				$order->add_order_note( $authorized_message );
				$this->log( "Success: $authorized_message" );
				
				// Reduce stock levels
				version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->reduce_order_stock() : wc_reduce_stock_levels( $order->id );
				
			}else{
				add_post_meta( $order->id, 'woosquare_transaction_id', $result->payment->id, true );
				
				// Mark as on-hold
				$authorized_message = sprintf( __( 'ACH Payment '.$msg.' (Payment ID: %s). will be charged with in three to five days.', 'wpexpert-square' ), $result->payment->id );
				$order->add_order_note( $authorized_message );
				$this->log( "Success: $authorized_message" );
				
				// Reduce stock levels
				version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->reduce_order_stock() : wc_reduce_stock_levels( $order->id );
				
			}

			


			if (isset($result->payment->id) and $result->payment->card_details->status == 'CAPTURED' ) {

				// Store captured value
				update_post_meta( $order->id, '_square_charge_captured', 'yes' );

				// Payment complete
				$order->payment_complete( $result->payment->id );
				add_post_meta( $order->id, 'woosquare_transaction_id', $result->payment->id, true );
				

				
				
				$complete_message = sprintf( __( 'Square charge complete '.$msg.' (Charge ID: %s)', 'wpexpert-square' ), $result->payment->id );
				$order->add_order_note( $complete_message );
				$this->log( "Success: $complete_message" );				
			



			}

			// we got this far which means the payment went through
			if ( $this->create_customer ) {
				$this->maybe_create_customer( $order );
			}
			
			// Remove cart
			WC()->cart->empty_cart();

			// Return thank you page redirect
			return array(
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order )
			);
		} catch ( Exception $e ) {
			$this->log( sprintf( __( 'Error: %s', 'wpexpert-square' ), $e->getMessage() ) );

			$order->update_status( 'failed', $e->getMessage() );

			return;
		}
	}
    
    /**
	 * Tries to create the customer on Square
	 *
	 * @param object $order
	 */
	public function maybe_create_customer( $order ) {


		$user               = get_current_user_id();
		$square_customer_id = get_user_meta( $user, '_square_customer_id', true );

		$create_customer = true;

		$customer = array(
			'given_name'    => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_first_name : $order->get_billing_first_name(),
			'family_name'   => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_last_name : $order->get_billing_last_name(),
			'email_address' => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_email : $order->get_billing_email(),
			'address'       => array(
				'address_line_1'                  => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_address_1 : $order->get_billing_address_1(),
				'address_line_2'                  => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_address_2 : $order->get_billing_address_2(),
				'locality'                        => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_city : $order->get_billing_city(),
				'administrative_district_level_1' => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_state : $order->get_billing_state(),
				'postal_code'                     => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_postcode : $order->get_billing_postcode(),
				'country'                         => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_country : $order->get_billing_country(),
			),
			'phone_number' => (string) version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_phone : $order->get_billing_phone(),
			'reference_id' => ! empty( $user ) ? (string) $user : __( 'Guest', 'woosquare' ),
		);

		// to prevent creating duplicate customer
		// check to make sure this customer does not exist on Square
		if ( ! empty( $square_customer_id ) ) {
			$square_customer = $this->connect->get_customer( $square_customer_id );
				    
	 
			if ( empty( $square_customer->errors ) ) {
				// customer already exist on Square
				$create_customer = false;
			}
		}
		
		if ( $create_customer ) {
			$result = $this->connect->create_customer( $customer );
	

			// we don't want to halt any processes here just log it
			if ( is_wp_error( $result ) ) {
				$this->log( sprintf( __( 'Error creating customer: %s', 'woosquare' ), $result->get_error_message() ) );
				$order->add_order_note( sprintf( __( 'Error creating customer: %s', 'woosquare' ), $result->get_error_message() ) );
			}

			// we don't want to halt any processes here just log it
			if ( ! empty( $result->errors ) ) {
				$this->log( sprintf( __( 'Error creating customer: %s', 'woosquare' ), print_r( $result->errors, true ) ) );
				$order->add_order_note( sprintf( __( 'Error creating customer: %s', 'woosquare' ), print_r( $result->errors, true ) ) );
			}

			// if no errors save Square customer ID to user meta
			if ( ! is_wp_error( $result ) && empty( $result->errors ) && ! empty( $user ) ) {
				update_user_meta( $user, '_square_customer_id', $result->customer->id );
				$order->add_order_note( sprintf( __( 'Customer created on Square: %s', 'woosquare' ), $result->customer->id ) );
			}
		}
	}

	/**
	 * Process amount to be passed to Square.
	 * @return float
	 */
	public function format_amount( $total, $currency = '' ) {
		if ( ! $currency ) {
			$currency = get_woocommerce_currency();
		}

		switch ( strtoupper( $currency ) ) {
			// Zero decimal currencies
			case 'BIF' :
			case 'CLP' :
			case 'DJF' :
			case 'GNF' :
			case 'JPY' :
			case 'KMF' :
			case 'KRW' :
			case 'MGA' :
			case 'PYG' :
			case 'RWF' :
			case 'VND' :
			case 'VUV' :
			case 'XAF' :
			case 'XOF' :
			case 'XPF' :
				$total = absint( $total );
				break;
			default :
				$total = round( $total, 2 ) * 100; // In cents
				break;
		}

		return $total;
	}

	/**
	 * Refund a charge
	 * @param  int $order_id
	 * @param  float $amount
	 * @return bool
	 */
	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		$order = wc_get_order( $order_id );
		
		$trans_id = get_post_meta( $order_id, 'woosquare_transaction_id', true );
		if ( ! $order || ! $trans_id ) {
			return false;
		}

		if ( 'square_ach_payment' === ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->payment_method : $order->get_payment_method() ) ) {
			try {
				$this->log( "Info: Begin refund for order {$order_id} for the amount of {$amount}" );

				
				$captured = get_post_meta( $order_id, '_square_charge_captured', true );
				
				$transaction_status = $order->data['status'];
				
				if ( 'processing' === $transaction_status || 'completed' === $transaction_status  ) {
					
                    $currency = $order->get_order_currency();
                        $fields = array(
						"idempotency_key" => uniqid(),
						"payment_id" => $trans_id,
						"reason" => $reason,
						"amount_money" => array(
							 'amount'   =>  (int) $this->format_amount( $amount , $currency ),
							  "currency" => version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->get_order_currency() : $order->get_currency(),
							),
					);
					
					$url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/refunds";
					$headers = array(
						'Accept' => 'application/json',
						'Authorization' => 'Bearer '.$this->token,
						'Content-Type' => 'application/json',
						'Cache-Control' => 'no-cache'
					);
					
					$result = json_decode(wp_remote_retrieve_body(wp_remote_post($url, array(
							'method' => 'POST',
							'headers' => $headers,
							'httpversion' => '1.0',
							'sslverify' => false,
							'body' => json_encode($fields)
							)
						)
					)
					);
                    
					if ( is_wp_error( $result ) ) {
						throw new Exception( $result->get_error_message() );

					} elseif ( ! empty( $result->errors ) ) {
						throw new Exception( "Error: " . print_r( $result->errors, true ) );
						
					} else {
						if ( 'APPROVED' === $result->refund->status || 'PENDING' === $result->refund->status ) {
							$refund_message = sprintf( __( 'Refunded %s - Refund ID: %s - Reason: %s', 'wpexpert-square' ), wc_price( $result->refund->amount_money->amount / 100 ), $result->refund->id, $reason );
						
							$order->add_order_note( $refund_message );
						
							$this->log( "Success: " . html_entity_decode( strip_tags( $refund_message ) ) );
						
							return true;
						}
					}
				}

			} catch ( Exception $e ) {
				$this->log( sprintf( __( 'Error: %s', 'wpexpert-square' ), $e->getMessage() ) );

				return false;
			}
		}
	}

	/**
	 * Logs
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @param string $message
	 */
	public function log( $message ) {
		if ( $this->logging ) {
			WooSquare_Payment_Logger::log( $message );
		}
	}
	
}

