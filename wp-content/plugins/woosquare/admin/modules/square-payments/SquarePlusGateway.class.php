<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WooSquare_Plus_Gateway extends WC_Payment_Gateway {
	protected $connect;
	protected $token;
	public $log;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->id		            = 'square_plus';
		$this->method_title 	    = __( 'Square', 'woosquare' );
		$this->method_description   = __( 'Square works by adding payments fields in an iframe and then sending the details to Square for verification and processing.', 'woosquare' );
		$this->has_fields 	        = true;
		$this->supports = array(
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
		$this->capture         = $this->get_option( 'capture' ) === 'yes' ? true : false;
		$this->create_customer = $this->get_option( 'create_customer' ) === 'yes' ? true : false;
		$this->logging         = $this->get_option( 'logging' ) === 'yes' ? true : false;
		$this->connect         = new WooSquare_Payments_Connect(); // decouple in future when v2 is ready
		$this->token           = get_option( 'woo_square_access_token'.get_transient('is_sandbox') );

		

		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		if(get_transient('is_sandbox')){
			$this->description .= ' ' . __( 'STAGING MODE IS ENABLED". For testing purpose use card number 4111111111111111 with any CVC and valid expiration date.', 'woosquare' );
		}	
		
		$this->description  = trim( $this->description );
		$this->connect->set_access_token( $this->token );
		$sub = "";
		// Hooks
		// if cart having subscription type product disabled below script else work..
		if(in_array('wc-square-recurring-premium/wc-square-recuring.php', apply_filters('active_plugins', get_option('active_plugins'))) and is_checkout() ) {			
			$sub = false;
			foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
				$_product = wc_get_product( $cart_item['product_id'] );
				if( $_product->is_type( 'subscription' ) OR $_product->is_type( 'variable-subscription' ) ){
					$sub = true;
				}
			}
		}
		if(!@$sub){
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		}		
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		
		if (class_exists( 'WC_Pre_Orders_Order' )){
			
			add_action( 'wc_pre_orders_process_pre_order_completion_payment_' . $this->id, array( $this, 'process_pre_order_release_payment_woosquare' ) );
		}
		
		
	}

	/**
	 * get_icon function.
	 *
	 * @access public
	 * @return string
	 */
	public function get_icon() {
		$icon  = '<img src="' . WooSquare_PLUGIN_URL_PAYMENT . '/img/cc-icon/visa.svg'. '" alt="Visa" width="32" style="margin-left: 0.3em" />';
		$icon .= '<img src="' . WooSquare_PLUGIN_URL_PAYMENT . '/img/cc-icon/mastercard.svg'. '" alt="Mastercard" width="32" style="margin-left: 0.3em" />';
		$icon .= '<img src="' . WooSquare_PLUGIN_URL_PAYMENT . '/img/cc-icon/amex.svg'. '" alt="Amex" width="32" style="margin-left: 0.3em" />';

		$icon .= '<img src="' . WooSquare_PLUGIN_URL_PAYMENT . '/img/cc-icon/discover.svg'. '" alt="Discover" width="32" style="margin-left: 0.3em" />';
		
		return apply_filters( 'woocommerce_gateway_icon', $icon, $this->id );
	}

	/**
	 * Check if required fields are set
	 */
	public function admin_notices() {
		if ( $this->enabled != 'yes' ) {
			return;
		}

		// Show message if enabled and FORCE SSL is disabled and WordpressHTTPS plugin is not detected
		if ( ! WC_SQUARE_ENABLE_STAGING && ! class_exists( 'WordPressHTTPS' ) && ! is_ssl() ) {
			echo '<div class="error"><p>' . sprintf( esc_html__( 'Square is enabled, but the <a href="%s">force SSL option</a> is disabled; your checkout is not secured! Please enable SSL and ensure your server has a valid SSL certificate.', 'woosquare' ), esc_url(admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ) . '</p></div>';
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
				'US' !== WC()->countries->get_base_country() && 
				'CA' !== WC()->countries->get_base_country() && 
				'GB' !== WC()->countries->get_base_country() &&
				'ES' !== WC()->countries->get_base_country() &&
				'IE' !== WC()->countries->get_base_country() &&
				'JP' !== WC()->countries->get_base_country() &&
				'AU' !== WC()->countries->get_base_country() ) || ( 
				'USD' !== get_woocommerce_currency() && 
				'CAD' !== get_woocommerce_currency() && 
				'JPY' !== get_woocommerce_currency() && 
				'EUR' !== get_woocommerce_currency() && 
				'AUD' !== get_woocommerce_currency() && 
				'GBP' !== get_woocommerce_currency() ) 
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
			
		return apply_filters( 'woocommerce_square_payment_gateway_is_available', $is_available );
	}

	/**
	 * Initialize Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = apply_filters( 'woocommerce_square_gateway_settings', array(
			'enabled' => array(
				'title'       => __( 'Enable/Disable', 'woosquare' ),
				'label'       => __( 'Enable Square', 'woosquare' ),
				'type'        => 'checkbox',
				'description' => '',
				'default'     => 'no'
			),
			'title' => array(
				'title'       => __( 'Title', 'woosquare' ),
				'type'        => 'text',
				'description' => __( 'This controls the title which the user sees during checkout.', 'woosquare' ),
				'default'     => __( 'Credit card (Square)', 'woosquare' )
			),
			'description' => array(
				'title'       => __( 'Description', 'woosquare' ),
				'type'        => 'textarea',
				'description' => __( 'This controls the description which the user sees during checkout.', 'woosquare' ),
				'default'     => __( 'Pay with your credit card via Square.', 'woosquare')
			),
			'capture' => array(
				'title'       => __( 'Delay Capture', 'woosquare' ),
				'label'       => __( 'Enable Delay Capture', 'woosquare' ),
				'type'        => 'checkbox',
				'description' => __( 'When enabled, the request will only perform an Auth on the provided card. You can then later perform either a Capture or Void.', 'woosquare' ),
				'default'     => 'no'
			),
			'create_customer' => array(
				'title'       => __( 'Create Customer', 'woosquare' ),
				'label'       => __( 'Enable Create Customer', 'woosquare' ),
				'type'        => 'checkbox',
				'description' => __( 'When enabled, processing a payment will create a customer profile on Square.', 'woosquare' ),
				'default'     => 'no'
			),
			'logging' => array(
				'title'       => __( 'Logging', 'woosquare' ),
				'label'       => __( 'Log debug messages', 'woosquare' ),
				'type'        => 'checkbox',
				'description' => __( 'Save debug messages to the WooCommerce System Status log.', 'woosquare' ),
				'default'     => 'no'
			),
			'Send_customer_info' => array(
				'title'       => __( 'Send Customer Info', 'wpexpert-square' ),
				'label'       => __( 'Send First Name Last Name', 'wpexpert-square' ),
				'type'        => 'checkbox',
				'description' => __( 'Send First Name Last Name with order to square.', 'wpexpert-square' ),
				'default'     => 'no'
			),
			'enable_sandbox' => array( 
				'title'       => __( 'Enable/Disable', 'wpexpert-square' ),
				'label'       => __( 'Enable Sandbox', 'wpexpert-square' ),
				'type'        => 'checkbox',
				'description' => __( 'Test your transaction through sandbox mode.', 'wpexpert-square' ),
				'default'     => 'no'
			),
			'enable_avs_check' => array( 
				'title'       => __( 'Enable/Disable', 'wpexpert-square' ),
				'label'       => __( 'Enable AVS Zip code check', 'wpexpert-square' ),
				'type'        => 'checkbox',
				'description' => __( 'Enable avs zip code check.', 'wpexpert-square' ),
				'default'     => 'no'
			),
			/* 'api_details'           => array(
				'title'       => __( 'Sandbox API credentials', 'wpexpert-square' ),
				'type'        => 'title',
				/* translators: %s: URL 
				'description' => sprintf( __( '<div class="squ-sandbox-description" style="%s"><p>%s</p></div> <div class="squ-sandbox-description"><p>If you don\'t have an account, go to <a target="_blank" href="%s">https://squareup.com/signup</a> to create one. You need a Square account to register an application with Square. 
													Register your application with Square
												</p>
												<p>
													Then go to <a  target="_blank" href="%s">https://connect.squareup'.get_transient('is_sandbox').'.com/apps</a> and sign in to your Square account. Then <b>click New Application</b> and give the name for your application to Create App.

													The application dashboard displays your new app\'s sandbox credentials. Insert below these sandbox credentials.   
												</p></div>
												', 'wpexpert-square' ), '	padding: 3px 0px 3px 10px;
																			background-color: #0085ba;
																			color: white; 
																			font-size: medium;
																			font-weight: 400;
																			margin-bottom: 15px;'
																		,   'These settings are required only for sandbox!','https://squareup.com/signup',"https://connect.squareup".get_transient('is_sandbox').".com/apps" ),
			),
			'sandbox_application_id' => array(
				'title'       => __( 'Sandbox application id', 'wpexpert-square' ),
				'label'       => __( '', 'wpexpert-square' ),
				'type'        => 'textbox',
				'description' => __( 'Add Square Application ID settings to integrate with square payment with sandbox.', 'wpexpert-square' ),
				'default'     => ''
			),
			'sandbox_access_token' => array(
				'title'       => __( 'Sandbox access token', 'wpexpert-square' ),
				'label'       => __( '', 'wpexpert-square' ),
				'type'        => 'textbox',
				'description' => __( 'Add Square Access token settings to integrate with square payment with sandbox.', 'wpexpert-square' ),
				'default'     => ''
			),
			'sandbox_location_id' => array(
				'title'       => __( 'Sandbox location id', 'wpexpert-square' ),
				'label'       => __( 'Enable Sandbox', 'wpexpert-square' ),
				'type'        => 'textbox',
				'description' => __( 'Add Square Location ID settings to integrate with square payment with sandbox.', 'wpexpert-square' ),
				'default'     => ''
			), */
		) );
	}

	/**
	 * Payment form on checkout page
	 */
	public function payment_fields() { 
		
		?><fieldset class="wooSquare-checkout">
			<?php
				$allowed = array(
				    'a' => array(
				        'href' => array(),
				        'title' => array()
				    ),
				    'br' => array(),
				    'em' => array(),
				    'strong' => array(),
				    'span'	=> array(
				    	'class' => array(),
				    ),
				);
				if ( $this->description ) {
					echo apply_filters( 'woocommerce_square_description', wpautop( wp_kses( $this->description, $allowed ) ) );
				}
				$checkpaymentform = false;
				$value = $boolean = '';
				$checkpaymentform = apply_filters( 'checkpaymentform', $value, $boolean);
                
			
			?>

				<div  id="payment-form">
					<div id="card-container"></div>
				</div>
				<div id="payment-status-container"></div>
		<?php

			$subs = false;
			if(class_exists('WC_Subscriptions_Order')){
				if(WC_Subscriptions_Cart::cart_contains_subscription()){
					$subs = true;
				}
			}

			//checking is that order have pre order items
			if(class_exists( 'WC_Pre_Orders_Order' )){
				$cart_data = WC()->session->get('cart');
				$_wc_pre_orders_enabled = get_post_meta($cart_data[array_keys($cart_data)[0]]['product_id'],'_wc_pre_orders_enabled',true);
				$_wc_pre_orders_when_to_charge = get_post_meta($cart_data[array_keys($cart_data)[0]]['product_id'],'_wc_pre_orders_when_to_charge',true);
				?>
				<input type='hidden' name='is_preorder' class='is_preorder' value='1' /> 
				<?php
			}
			?>
		</fieldset>
			
		<?php
	}
	
	
	public function _get_cus($customer_id){
		if(!empty($customer_id) and !empty($this->token)){
			$square = new Square(get_option('woo_square_access_token'.get_transient('is_sandbox')), get_option('woo_square_location_id'.get_transient('is_sandbox')),WOOSQU_PLUS_APPID);
			$url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/customers/".$customer_id;
			$method = "GET";
            $headers = array(
				'authorization' => 'Bearer '.$this->token,
				'cache-control' => 'no-cache',
				'postman-token' => '51e3dc9d-a036-b635-9d1a-92fa490f2514'
			);
			$response = array();
			$args = array('');
			$response = $square->wp_remote_woosquare($url,$args,$method,$headers,$response);
			$objectCus=  json_decode($response['body'],  false);
           if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
             return  $objectCus;
          } else {
            return false;
        }

		}
	}

	public function create_cus_card($_square_customer_id,$card_details,$user_id,$token){

		$square = new Square(get_option('woo_square_access_token'.get_transient('is_sandbox')), get_option('woo_square_location_id'.get_transient('is_sandbox')),WOOSQU_PLUS_APPID);

		$url = "https://connect.".WC_SQUARE_STAGING_URL.".com/v2/customers/".$_square_customer_id."/cards";

		$method = "POST";

		$headers = array(
			'Authorization' => 'Bearer '.$token, // Use verbose mode in cURL to determine the format you want for this header
			'cache-control'  => 'no-cache',
			'Content-Type'  => 'application/json'
		);

		$response = array();
		$response = $square->wp_remote_woosquare($url,$card_details,$method,$headers,$response);

		$objectResponse =  json_decode($response['body'], true);
        if($response['response']['code'] == 200 and $response['response']['message'] == 'OK'){
			update_user_meta($user_id,'customers_card_create_response',$response);
			return $reason;
            
        } else {
			update_user_meta($user_id,'customers_card_create_err',$response);
			return null;
        }
		 
	}

	/**
	 * Get payment form input styles.
	 * This function is pass to the JS script in order to style the
	 * input fields within the iFrame.
	 *
	 * Possible styles are: mediaMinWidth, mediaMaxWidth, backgroundColor, boxShadow,
	 * color, fontFamily, fontSize, fontWeight, lineHeight and padding.
	 *
	 * @since 1.0.4
	 * @version 1.0.4
	 * @access public
	 * @return json $styles
	 */
	public function get_input_styles() {
		$styles = array(
			array(
				'fontSize'        => '1.2em',
				'padding'         => '.618em',
				'fontWeight'      => 400,
				'backgroundColor' => 'transparent',
				'lineHeight'      => 1.7
			),
			array(
				'mediaMaxWidth' => '1200px',
				'fontSize'      => '1em'
			)
		);

		return apply_filters( 'woocommerce_square_payment_input_styles', wp_json_encode( $styles ) );
	}

	/**
	 * payment_scripts function.
	 *
	 *
	 * @access public
	 */
	public function payment_scripts() {
		if ( ! is_checkout() ) {
			return;
		}

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '';
		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		$location = get_option('woo_square_location_id'.get_transient('is_sandbox'));
		global $woocommerce;
		// Will get you cart object
		$cart_total = $woocommerce->cart->get_totals();
		
		if(get_transient('is_sandbox')){
			$endpoint = 'sandbox.web';
		} else {
			$endpoint = 'web';
		}
		wp_enqueue_script('squareSDK', 'https://'.$endpoint.'.squarecdn.com/v1/square.js', array(), '');

		wp_register_script( 'woocommerce-square', WooSquare_PLUGIN_URL_PAYMENT . '/js/SquarePayments' . $suffix . '.js', array( 'jquery' ), '', true );
		wp_localize_script( 'woocommerce-square', 'square_params', array(
			'application_id'               => WOOSQU_PLUS_APPID,
			'environment'                  =>  'development' ,
			'locationId'                   =>  $location,
			'cart_total'                   =>  $cart_total['total'] ,
			'get_woocommerce_currency'	   =>  get_woocommerce_currency(),
			'placeholder_card_number'      => __( '•••• •••• •••• ••••', 'woosquare' ),
			'placeholder_card_expiration'  => __( 'MM / YY', 'woosquare' ),
			'placeholder_card_cvv'         => __( 'CVV', 'woosquare' ),
			'placeholder_card_postal_code' => __( 'Card Postal Code', 'woosquare' ),
			'payment_form_input_styles'    => esc_js( $this->get_input_styles() ),
			'custom_form_trigger_element'  => apply_filters( 'woocommerce_square_payment_form_trigger_element', esc_js( '' ) ),
			'enable_avs_check'  => $woocommerce_square_plus_settings['enable_avs_check'],
			'subscription'  => (class_exists('WC_Subscriptions_Order') ? WC_Subscriptions_Cart::cart_contains_subscription() : false) ,
		) );

		wp_enqueue_script( 'woocommerce-square' );
		
		wp_enqueue_style( 'woocommerce-square-styles', WooSquare_PLUGIN_URL_PAYMENT . '/css/SquareFrontendStyles.css' );

		return true;
	}

	/**
	 * Process the payment
	 */
	 
	  
    public function process_payment($order_id) {
			
		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		$location_id = get_option('woo_square_location_id'.get_transient('is_sandbox'));
		$endpoint = 'squareup'.get_transient('is_sandbox');
		
        $card_nonce = isset($_POST['square_nonce']) ? sanitize_text_field(wp_unslash($_POST['square_nonce'])) : '';
        $buyerVerification_token = isset($_POST['buyerVerification_token']) ? sanitize_text_field(wp_unslash($_POST['buyerVerification_token'])) : '';

		if(is_numeric(@$_POST['woocommerce_change_payment']) and isset($_POST['woocommerce_change_payment'])){
			$get_post = get_post(sanitize_text_field(wp_unslash($_POST['woocommerce_change_payment'])));
			$order = wc_get_order($get_post->post_parent);
			$order_id = $get_post->post_parent;
		} else {
			$order = wc_get_order($order_id);
		}

		if(@$woocommerce_square_plus_settings['Send_customer_info'] == 'yes'){
			$first_name = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_first_name : $order->get_billing_first_name();
			$last_name = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->billing_last_name : $order->get_billing_last_name();
			if(empty($first_name) and empty($last_name)){
				$first_name = $last_name = null;
			}
		} else {
			$first_name = $last_name = null;
		}


        $currency = $order->get_currency();

        //check if falid order manual pay
        $parent_order_id = null;
		$subscription = false;
		if(class_exists('WC_Subscriptions_Order')){
			if (wcs_order_contains_subscription($order_id, array('parent', 'renewal'))) {
				$subscriptions = wcs_get_subscriptions_for_order($order_id, array('order_type' => array('parent', 'renewal')));
				//get parent order
				foreach ($subscriptions as $subscription) {
					if ($subscription->get_parent_id()) {
						$parent_order = $subscription->get_parent();
						$parent_order_id = $parent_order->get_id();
					}
				}
				$subscription = true;
			}
		}
        
		
		
        try {
            //shipping address
			
			
			$shipping_address = array(
				'address_line_1' => $order->get_shipping_address_1() ? $order->get_shipping_address_1() : $order->get_billing_address_1(),
				'address_line_2' => $order->get_shipping_address_2() ? $order->get_shipping_address_2() : $order->get_billing_address_2(),
				'locality' => $order->get_shipping_city() ? $order->get_shipping_city() : $order->get_billing_city(),
				'administrative_district_level_1' => $order->get_shipping_state() ? $order->get_shipping_state() : $order->get_billing_state(),
				'postal_code' => $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode(),
				'country' => $order->get_shipping_country() ? $order->get_shipping_country() : $order->get_billing_country()
			);

			//billing address
			$billing_address = array(
				'address_line_1' => $order->get_billing_address_1(),
				'address_line_2' => $order->get_billing_address_2(),
				'locality' => $order->get_billing_city(),
				'administrative_district_level_1' => $order->get_billing_state(),
				'postal_code' => $order->get_billing_postcode(),
				'country' => $order->get_billing_country()
			);
            
            	if ( $this->create_customer ) {
				$this->maybe_create_customer( $order );
			}
			
			if(
				($subscription and empty($_POST['saved_cards']))
				OR 
				(@$_POST['square_plussq-card-saved'] == 'on')
				OR
				(is_numeric(@$_POST['woocommerce_change_payment']) and isset($_POST['woocommerce_change_payment']))
				OR
				(is_numeric(@$_POST['_wcf_flow_id']) and is_numeric($_POST['_wcf_checkout_id']) and empty($_POST['saved_cards']))
				OR 
				$this->maybe_process_pre_orders( $order_id )
			){
					
				if(
				is_numeric(@$_POST['wc-square-recurring-payment-token'])
					and
				isset($_POST['wc-square-recurring-payment-token'])){
					$token_id = wc_clean( $_POST['wc-square-recurring-payment-token'] );
					$WC_Payment_Tokens    = WC_Payment_Tokens::get( $token_id );
					$customerCardId = $WC_Payment_Tokens->get_token();

					if ($parent_order_id)
							update_post_meta($parent_order_id, '_woos_plus_customer_card_id', $customerCardId);
						else
							add_post_meta($order_id, '_woos_plus_customer_card_id', $customerCardId);

				} else if(empty($_POST['saved_cards'])){
					//create customer card
					
					$customerCard = $customer_api->createCustomerCard($square_customer_id, array(
						'card_nonce' => $card_nonce,
						'verification_token' => $buyerVerification_token,
						'billing_address' => $billing_address,
						'cardholder_name' => $order->get_billing_first_name() . ' ' . $order->get_billing_last_name()
					));
					
					//save customer card in order meta
					
					$customerCardData = json_decode($customerCard, true);
					
					
					
					$customerCardId = null;
					if (isset($customerCardData['card']['id'])) {
						$customerCardId = $customerCardData['card']['id'];



						if(isset($_POST['wc-'.$this->id.'-payment-token']) && $_POST['wc-'.$this->id.'-payment-token'] == 'new'){
							$WC_Payment_token = new WC_Payment_Token_CC();
							$WC_Payment_token->set_token( $customerCardId );
							$WC_Payment_token->set_gateway_id( $this->id ); // `$this->id` references the gateway ID set in `__construct`
							$WC_Payment_token->set_card_type( strtolower(isset( $_POST['woos_plus_1'] ) ? sanitize_text_field( wp_unslash( $_POST['woos_plus_1'] ) ): '' ));
							$WC_Payment_token->set_last4( isset( $_POST['woos_plus_2'] ) ? sanitize_text_field( wp_unslash( $_POST['woos_plus_2'] ) ): ''  );
							$WC_Payment_token->set_expiry_month( isset( $_POST['woos_plus_3'] ) ? sanitize_text_field( wp_unslash( $_POST['woos_plus_3'] ) ): ''  );
							$WC_Payment_token->set_expiry_year( isset( $_POST['woos_plus_4'] ) ? sanitize_text_field( wp_unslash( $_POST['woos_plus_4'] ) ): ''  );
							$WC_Payment_token->set_user_id( get_current_user_id() );
							$WC_Payment_token->save();
						}

						if ($parent_order_id)
							update_post_meta($parent_order_id, '_woos_plus_customer_card_id', $customerCardId);
						else
							add_post_meta($order_id, '_woos_plus_customer_card_id', $customerCardId);
					}

				} else if(!empty($_POST['saved_cards'])) {
					$saved_cards = ( isset( $_POST['saved_cards'] ) ? sanitize_text_field( wp_unslash( $_POST['saved_cards'] ) ): '' );
				    add_post_meta($order_id, '_woos_plus_customer_card_id', $saved_cards);
			    }
				
				
				// ToDo: `process_pre_order` saves the source to the order for a later payment.
				// This might not work well with PaymentIntents.
				if ( $this->maybe_process_pre_orders( $order_id ) ) {
					return $this->process_pre_order( $order_id );
				}

				
			}
			
			if(is_numeric(@$_POST['woocommerce_change_payment']) and isset($_POST['woocommerce_change_payment'])){
				$woocommerce_change_payment = (isset( $_POST['woocommerce_change_payment'] ) ? sanitize_text_field( wp_unslash( $_POST['woocommerce_change_payment'] ) ): '' );
				return array(
					'result' => 'success',
					'redirect' => site_url().'/my-account/view-subscription/'.$woocommerce_change_payment.'/'
				);
			}
		
			if($order->get_total() == 0){
			    $card_nonce = '';
		    }
			
            //charge customer
             if (
				$order->get_total() > 0
					&& 
				(isset($customerCardId)
					OR
				isset($card_nonce))
				) {

                $idempotencyKey = (string) $order_id.rand(10000,200000);
				
				
				
				
				// ToDo: `process_pre_order` saves the source to the order for a later payment.
				// This might not work well with PaymentIntents.
				if ( $this->maybe_process_pre_orders( $order_id ) ) {
					return $this->process_pre_order( $order_id );
				}

				
				if(function_exists('square_order_sync_add_on')){
					$amount = (int) round($this->format_amount( $order->get_total(), $currency ),1);
				} else {
					$amount = (int) $this->format_amount( $order->get_total(), $currency );
				}

				
				$fields = array();
				
				
				
				if(!empty($_POST['saved_cards']) and empty($_POST['square_plussq-card-saved'])){
					$fields['source_id'] = isset( $_POST['saved_cards'] ) ? sanitize_text_field( wp_unslash( $_POST['saved_cards'] ) ): '' ;
					add_post_meta($order_id, '_woos_plus_customer_card_id', sanitize_text_field( wp_unslash($_POST['saved_cards'])));
					update_post_meta($parent_order_id, '_woos_plus_customer_card_id', sanitize_text_field( wp_unslash($_POST['saved_cards'])));
					$user_id = get_current_user_id();
					$_square_customer_id = get_user_meta( $user_id, '_square_customer_id', true );
					$fields['customer_id'] = $_square_customer_id;
				} else {
					$fields['source_id'] =  $card_nonce;
				}
				
				
				
				
				
				$fields['autocomplete'] = $this->capture ? false : true;
				$fields['idempotency_key'] = $idempotencyKey;
				$fields['location_id'] = $location_id;
				$fields['amount_money'] = array(
											  "amount" => $amount,
											  "currency" => $currency
											);
				
				
				
				if(defined('SQUARE_VENDOR_COMISSION_INC_ITEMS') && array_key_exists(0, SQUARE_VENDOR_COMISSION_INC_ITEMS)){
					$commission = 0;
					$percentage = SQUARE_VENDOR_COMISSION;
					foreach ( WC()->cart->get_cart() as $cart_item ) {
						$product = $cart_item['data'];
						$quantity   = $cart_item['quantity'];
						if(in_array($product->get_id(), SQUARE_VENDOR_COMISSION_INC_ITEMS)){
							$product_price = $cart_item['data']->get_price() * $quantity;
							
							$percentage_fee = $product_price * $percentage;
							
							$percentage_fee = apply_filters('woosquare_product_comission_fee',$percentage_fee,$cart_item);
							$commission = $commission + $percentage_fee;
						}
						
					}
					if($commission > 0){
						$fields['app_fee_money'] = array(
							  "amount" => (int) $this->format_amount( $commission, $currency ),
							  "currency" => $currency
							);
					}
				}							
											
				if(
					$subscription 
					and 
					empty($_POST['saved_cards'])
					OR 
					@$_POST['square_plussq-card-saved'] == 'on'
					OR 
					(is_numeric(@$_POST['_wcf_flow_id']) and is_numeric($_POST['_wcf_checkout_id'])  and empty($_POST['saved_cards']))
					){
					$fields['source_id'] = $customerCardId;
					add_post_meta($order_id, '_woos_plus_customer_card_id', $customerCardId);
					update_post_meta($parent_order_id, '_woos_plus_customer_card_id', $customerCardId);
					$fields['customer_id'] = $square_customer_id;
				} 
				$fields['shipping_address'] = $shipping_address;
				$fields['billing_address'] = $billing_address;
				$fields['verification_token'] = $buyerVerification_token;
				$fields['reference_id'] = (string) $order->get_order_number();
				$fields['note'] = apply_filters( 'woosquare_payment_order_note', 'WooCommerce: Order #' . (string) $order->get_order_number().' '.$first_name.' '.$last_name, $order );
				
				//need to add order creation function and get the order id.
				// order sync must be used in live environment ..
				if(function_exists('square_order_sync_add_on')){
					$fields['order_id'] = square_order_sync_add_on($order,$location_id,$currency,$idempotencyKey,$this->token,$endpoint,$fields['customer_id']);
				}	
				
				
				$url = "https://connect.".$endpoint.".com/v2/payments";
				
				$headers = array(
					'Accept' => 'application/json',
					'Authorization' => 'Bearer '.$this->token,
					'Content-Type' => 'application/json',
					'Cache-Control' => 'no-cache'
				);
				$transactionData = json_decode(wp_remote_retrieve_body(wp_remote_post($url, array(
						'method' => 'POST',
						'headers' => $headers,
						'httpversion' => '1.0',
						'sslverify' => false,
						'body' => json_encode($fields)
						)
					)
				)
				);
                if (isset($transactionData->payment->id) and $transactionData->payment->card_details->status == 'CAPTURED' ) {
                    $transactionId = $transactionData->payment->id;
                    add_post_meta($order_id, 'woosquare_transaction_id', $transactionId);
                    add_post_meta($order_id, '_transaction_id', $transactionId);
                    add_post_meta($order_id, 'woosquare_transaction_location_id', $location_id);
					//if sandbox enable add sandbox prefix.
					$sandbox_prefix = get_transient('is_sandbox') ? 'through sandbox' : '';
					
					
					
                    // Mark as processing
                    $message = sprintf(__('Customer card successfully charged %s (Transaction ID: %s).', 'woosquare'), $sandbox_prefix,$transactionId);
                    //$order->update_status('processing', $message);
					$order->update_status(apply_filters('square_order_status_woo_to_square','processing'), $message); 
					
							
					// clear cart
					WC()->cart->empty_cart();

					// Return thank you page redirect
					return array(
						'result' => 'success',
						'redirect' => $this->get_return_url($order)
					);

					
                } elseif (isset($transactionData->payment->id) and $transactionData->payment->card_details->status == 'AUTHORIZED'){
                    // Store captured value
                    $transactionId = $transactionData->payment->id;
    				update_post_meta( $order->id, '_square_charge_captured', 'no' );
    				add_post_meta( $order->id, 'woosquare_transaction_id', $transactionId, true );
    				add_post_meta($order->id, '_transaction_id', $transactionId);
                    add_post_meta($order->id, 'woosquare_transaction_location_id', $location_id);
    
    				// Mark as on-hold
    				$authorized_message = sprintf( __( 'Square charge authorized (Authorized ID: %s). Process order to take payment, or cancel to remove the pre-authorization.', 'woosquare' ),$transactionData->payment->id );
    				$order->update_status( 'on-hold', $authorized_message );
    				$order->add_order_note( $authorized_message );
    				$this->log( "Success: $authorized_message" );
                    
                    
    				// Reduce stock levels
    				version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->reduce_order_stock() : wc_reduce_stock_levels( $order_id );
                
                    		
					// clear cart
					WC()->cart->empty_cart();

					// Return thank you page redirect
					return array(
						'result' => 'success',
						'redirect' => $this->get_return_url($order)
					);
                
                } else {
                    $message = '';
					if(!empty($transactionData->card_details->errors)){
						foreach ($transactionData->card_details->errors as $error) {
							$message .= $error->detail;
							if (isset($error->field))
								$message .= $error->field . ' - ' . $error->detail;
							
						}
					} else {
						foreach ($transactionData->errors as $error) {
								$message .= $error->code . ' - ' . $error->detail.' - '.$error->category;
							
						}
						$message .= '</br><a target="_blank" href="https://developer.squareup.com/docs/payments-api/error-codes#createpayment-errors"> ERROR CODE REFERENCES </a>';
					
					}
					wc_add_notice($message, 'error');
                    $message = sprintf(__('Square Payment Failed  %s .', 'woosquare'), $message);
                    
                    $order->update_status('failed', $message);
                }
            }else if (isset($customerCardId) && $order->get_total() == 0  && wcs_order_contains_subscription($order_id, array('parent', 'renewal'))) { 
				$message = sprintf(__('Not charged as cart total is 0.', 'woosquare'));
				//$order->update_status('processing', $message);
				$order->update_status(apply_filters('square_order_status_woo_to_square','processing'), $message); 
				return array(
					'result' => 'success',
					'redirect' => $this->get_return_url($order)
				);
			}


        } catch (Exception $ex) {
			$message = '';
            $errors = $ex->getResponseBody()->errors;
			
            foreach ($errors as $error) {
                $message = $error->detail;
                if (isset($error->field))
                    $message = $error->field . ' - ' . $error->detail;
                wc_add_notice($message, 'error');
            }

            $order->update_status('failed', $ex->getMessage());

            return;
        }
    }
	
	
	
	/**
	 * Checks if we need to process pre orders when
	 * pre orders is in the cart.
	 *
	 * @since 4.1.0
	 * @param int $order_id
	 * @return bool
	 */
	public function maybe_process_pre_orders( $order_id ) {
		return (
			class_exists( 'WC_Pre_Orders_Order' ) &&
			WC_Pre_Orders_Order::order_contains_pre_order( $order_id ) &&
			WC_Pre_Orders_Order::order_requires_payment_tokenization( $order_id ) &&
			! is_wc_endpoint_url( 'order-pay' )
		);
	}
	
	
	
	
	/**
	 * Process the pre-order when pay upon release is used.
	 * @param int $order_id
	 */
	public function process_pre_order( $order_id ) {
		$order = wc_get_order( $order_id );
		// Setup the response early to allow later modifications.
		
		// Remove cart.
		WC()->cart->empty_cart();
		// Is pre ordered!
		WC_Pre_Orders_Order::mark_order_as_pre_ordered( $order );
		
		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
		// Return thank you page redirect
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
			if ( get_post_meta( $order_id, '_cartflows_offer', true ) == 'yes' ) {
				$trans_id = get_post_meta( $order_id, '_transaction_id', true );
			} else {
				return false;
			}
		}

		if ( 'square_plus' === ( version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->payment_method : $order->get_payment_method() ) ) {
			try {
				$this->log( "Info: Begin refund for order {$order_id} for the amount of {$amount}" );
				$currency = version_compare( WC_VERSION, '3.0.0', '<' ) ? $order->get_order_currency() : $order->get_currency();
				
				$location = get_option('woo_square_location_id'.get_transient('is_sandbox'));
				$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
				
				$transaction_status = $this->connect->get_transaction_status( $trans_id );
				if ( 'CAPTURED' === $transaction_status ) {
					$amount = (int) $this->format_amount( $amount , $currency );
					$fields = array(
						"idempotency_key" => uniqid(),
						"payment_id" => $trans_id,
						"reason" => $reason,
						"amount_money" => array(
							  "amount" => $amount,
							  "currency" => $currency,
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
							$refund_message = sprintf( __( 'Refunded %s - Refund ID: %s - Reason: %s', 'woosquare' ), wc_price( $result->refund->amount_money->amount / 100 ), $result->refund->id, $reason );
						
							$order->add_order_note( $refund_message );
						
							$this->log( "Success: " . html_entity_decode( strip_tags( $refund_message ) ) );
							return true;
						}
					}
				}

			} catch ( Exception $e ) {
				$this->log( sprintf( __( 'Error: %s', 'woosquare' ), $e->getMessage() ) );
				return false;
			}
		}
	}
	
	
	
	/**
	 * Process a pre-order payment when the pre-order is released.
	 *
	 * @param WC_Order $order
	 * @param bool $retry
	 *
	 * @return void
	 */
	public function process_pre_order_release_payment_woosquare( $order, $retry = true ) {
		
		$pre_order = $order;
        $token = get_option( 'woo_square_access_token'.get_transient('is_sandbox') );
		$woocommerce_square_plus_settings = get_option('woocommerce_square_plus_settings');
		$location_id = get_option('woo_square_location_id'.get_transient('is_sandbox'));
		$endpoint = 'squareup'.get_transient('is_sandbox');
		
        try {
                //get parent order
                $parent_order_id = null;
                // $parent_order = null;
                if (true) {
                    //shipping address
                    $shipping_address = array(
                        'address_line_1' => $pre_order->get_shipping_address_1() ? $pre_order->get_shipping_address_1() : $pre_order->get_billing_address_1(),
                        'address_line_2' => $pre_order->get_shipping_address_2() ? $pre_order->get_shipping_address_2() : $pre_order->get_billing_address_2(),
                        'locality' => $pre_order->get_shipping_city() ? $pre_order->get_shipping_city() : $pre_order->get_billing_city(),
                        'administrative_district_level_1' => $pre_order->get_shipping_state() ? $pre_order->get_shipping_state() : $pre_order->get_billing_state(),
                        'postal_code' => $pre_order->get_shipping_postcode() ? $pre_order->get_shipping_postcode() : $pre_order->get_billing_postcode(),
                        'country' => $pre_order->get_shipping_country() ? $pre_order->get_shipping_country() : $pre_order->get_billing_country()
                    );

                    //billing address
                    $billing_address = array(
                        'address_line_1' => $pre_order->get_billing_address_1(),
                        'address_line_2' => $pre_order->get_billing_address_2(),
                        'locality' => $pre_order->get_billing_city(),
                        'administrative_district_level_1' => $pre_order->get_billing_state(),
                        'postal_code' => $pre_order->get_billing_postcode(),
                        'country' => $pre_order->get_billing_country() ? $pre_order->get_billing_country() : $pre_order->get_shipping_country()
                    );

                    $parent_order_id = $pre_order->get_id();
                    $currency = $pre_order->get_currency();
                    $customer_card_id = get_post_meta($parent_order_id, '_woos_plus_customer_card_id', true);
                    $square_customer_id = null;
                    $customer_id = $pre_order->get_customer_id();
                    
                    if(empty($square_customer_id)){
                        $square_customer_id = get_user_meta($customer_id, '_square_customer_id', true);
                    }
                    
                    if(empty($square_customer_id)){
                        $square_customer_id = get_post_meta($parent_order_id, '_square_customer_id', true);
                    }   
					
		
                    if ($square_customer_id && $customer_card_id) {
                        
                        $idempotencyKey = (string) $parent_order_id;

						
						$fields = array(
							"idempotency_key" => $idempotencyKey,
							"location_id" => $location_id,
							"amount_money" => array(
								  "amount" =>   (int) $this->format_amount( $pre_order->get_total(), $currency ),
								  "currency" => $currency
								),
							"source_id" => $customer_card_id,
							'customer_id' => $square_customer_id,
							'shipping_address' => $shipping_address,
							'billing_address' => $billing_address,
							'reference_id' => (string) $pre_order->get_order_number(),
                            'note' => 'Order #' . (string) $pre_order->get_order_number()
						);
						
						$url = "https://connect.".$endpoint.".com/v2/payments";
						
						$headers = array(
							'Accept' => 'application/json',
							'Authorization' => 'Bearer '.$token,
							'Content-Type' => 'application/json',
							'Cache-Control' => 'no-cache'
						);
						
						
						
						$transactionData = json_decode(wp_remote_retrieve_body(wp_remote_post($url, array(
								'method' => 'POST',
								'headers' => $headers,
								'httpversion' => '1.0',
								'sslverify' => false,
								'body' => json_encode($fields)
								)
							)
						)
						);
						
                        if (isset($transactionData->payment->id) and $transactionData->payment->card_details->status == 'CAPTURED') {
                            $transactionId = $transactionData->payment->id;
                            add_post_meta($pre_order, 'woosquare_transaction_id', $transactionId);
							add_post_meta($pre_order, '_transaction_id', $transactionId);
                            add_post_meta($parent_order_id, 'woosquare_transaction_location_id', $location_id);
							//if sandbox enable add sandbox prefix.
							$sandbox_prefix = $this->test_mode == 'yes' ? 'through sandbox' : '';
                            // Mark as processing
                            $message = sprintf(__('Customer card successfully charged %s (Transaction ID: %s) For pre-order.', 'wcsrs-payment'),$sandbox_prefix, $transactionId);
                            //$pre_order->update_status('processing', $message);
                            $pre_order->update_status(apply_filters('square_order_status_woo_to_square','processing'), $message);
							
							$order_stock_reduced = $order->get_meta( '_order_stock_reduced', true );

							if ( ! $order_stock_reduced ) {
								wc_reduce_stock_levels( $parent_order_id );
							}

							$order->set_transaction_id( $transactionId );
                        } else {
                            $pre_order->add_order_note( 'Errors: ' . json_encode($transactionData->errors) . ' </br><a target="_blank" href="https://developer.squareup.com/docs/payments-api/error-codes#createpayment-errors"> ERROR CODE REFERENCES </a>');
							$pre_order->update_status('failed');
						}
                    }
                }
            // }
        } catch (Exception $ex) {
            $pre_order->update_status('failed', $ex->getMessage());
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


include 'SquarePlusGoogGateway.class.php';
include 'SquarePlusACHGateway.class.php';
include 'SquarePlusAfterPayGateway.class.php';
include 'SquarePlusCashAppGateway.class.php';
include 'SquarePlusApplePayGateway.class.php';
