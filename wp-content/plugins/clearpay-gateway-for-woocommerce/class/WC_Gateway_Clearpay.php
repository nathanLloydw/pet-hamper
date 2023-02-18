<?php
/**
 * This is the Clearpay - WooCommerce Payment Gateway Class.
 */
use Afterpay\SDK\HTTP;
use Afterpay\SDK\HTTP\Request\GetConfiguration;
use Afterpay\SDK\HTTP\Request\CreateCheckout;
use Afterpay\SDK\HTTP\Request\GetCheckout;
use Afterpay\SDK\HTTP\Request\ImmediatePaymentCapture;
use Afterpay\SDK\HTTP\Request\CreateRefund;
use Afterpay\SDK\HTTP\Request\UpdateShippingCourier;
use Afterpay\SDK\Helper\UrlHelper;

if (!class_exists('WC_Gateway_Clearpay')) {
	class WC_Gateway_Clearpay extends WC_Payment_Gateway
	{
		/**
		 * Private variables.
		 *
		 * @var		string	$include_path			Path to where this class's includes are located. Populated in the class constructor.
		 * @var		array	$environments			Keyed array containing the name and API/web URLs for each environment. Populated in the
		 *											class constructor by parsing the values in "environments.ini".
		 * @var		string	$token					The token to render on the preauth page.
		 * @var		array 	$assets					Static text content used for front end presentation based on currency/region.
		 * @var		array 	$express_base_error_config		Basic error config for express
		 */
		private $include_path, $environments, $token, $assets, $express_base_error_config;

		/**
		 * Protected static variables.
		 *
		 * @var		WC_Gateway_Clearpay	$instance		A static reference to a singleton instance of this class.
		 */
		protected static $instance = null;

		/**
		 * Public static variables.
		 *
		 * @var		WC_Logger		$log			An instance of the WC_Logger class.
		 */
		public static $log = false;

		/**
		 * Class constructor. Called when an object of this class is instantiated.
		 *
		 * @since	2.0.0
		 * @uses	plugin_basename()					Available as part of the WordPress core since 1.5.
		 * @uses	WC_Payment_Gateway::init_settings()	If the user has not yet saved their settings, it will extract the
		 *												default values from $this->form_fields defined in an ancestral class
		 *												and overridden below.
		 */
		public function __construct() {
			$this->include_path			= dirname( __FILE__ ) . '/WC_Gateway_Clearpay';
			$this->environments 		= include "{$this->include_path}/environments.php";

			$this->id					= 'clearpay';
			$this->has_fields        	= false;
			$this->description			= __( 'Credit cards accepted: Visa, Mastercard', 'woo_clearpay' );
			$this->method_title			= __( 'Clearpay', 'woo_clearpay' );
			$this->method_description	= __( 'Use Clearpay as a credit card processor for WooCommerce.', 'woo_clearpay' );
			//$this->icon; # Note: This URL is ignored; the WC_Gateway_Clearpay::filter_woocommerce_gateway_icon() method fires on the "woocommerce_gateway_icon" Filter hook and generates a complete HTML IMG tag.
			$this->supports				= array('products', 'refunds');
			$this->express_base_error_config = array(
				'log' => false,
				'redirect_url' => false
			);

			$this->init_form_fields();
			$this->init_settings();
			$this->init_user_agent();
			$this->init_merchant_account();
			$this->refresh_cached_configuration();
			$this->assets 				= include "{$this->include_path}/assets.php";

			if ( ! $this->is_valid_for_use() ) {
				$this->enabled = 'no';
			}
		}

		/**
		 * Initialise Gateway Settings Form Fields.
		 */
		public function init_form_fields() {
			$this->form_fields = include "{$this->include_path}/form_fields.php";
		}

		/**
		 * Initialise user agent header for API requests.
		 *
		 * @since	3.2.0
		 */
		private function init_user_agent() {
			global $wp_version;
			HTTP::addPlatformDetail('Clearpay Gateway for WooCommerce', Clearpay_Plugin::$version);
			HTTP::addPlatformDetail('WordPress', $wp_version);
			HTTP::addPlatformDetail('WooCommerce', WC()->version);
			HTTP::addPlatformDetail('ExpressCheckout', isset($this->settings['show-express-on-cart-page']) && $this->settings['show-express-on-cart-page']=='yes' ? '1' : '0');
			HTTP::addPlatformDetail('Multicurrency', isset($this->settings['enable-multicurrency']) && $this->settings['enable-multicurrency']=='yes' ? '1' : '0');
			HTTP::addPlatformDetail('WooCommerce Pre-Orders', defined('WC_PRE_ORDERS_VERSION') ? WC_PRE_ORDERS_VERSION : '0');
			try {
				HTTP::addStoreUrl(esc_url(home_url()));
			} catch (Exception $e) {
				self::log("User agent header: " . $e->getMessage());
			}
		}

		/**
		 * Configure merchant account for API requests.
		 * Triggered in constructor and after saving settings.
		 *
		 * @since	3.2.0
		 */
		public function init_merchant_account() {
			HTTP::setMerchantId($this->get_merchant_id());
			HTTP::setSecretKey($this->get_secret_key());
			HTTP::setCountryCode($this->get_country_code());
			HTTP::setApiEnvironment($this->get_api_env());
		}

		/**
		 * Generates 3 image sizes
		 *
		 * Example:
		 * when passed ("http://localhost:8080/", "folder/image", "png") will return:
		 * Obj(
		 * 		x1 -> "http://localhost:8080/folder/image.png"
		 * 		x2 -> "http://localhost:8080/folder/image@2x.png"
		 * 		x3 -> "http://localhost:8080/folder/image@3x.png"
		 * )
		 *
		 * @param string $base_url the protocol and domain where the file is located
		 * @param string $path the path to the file and it's sizes
		 * @param string $extension the file extension
		 *
		 * @since 3.5.2
		 */
		public function generate_source_sets($base_url, $path, $extension) {
			$withoutExtension = $base_url . $path;

			return (Object) array(
				"x1" => "$withoutExtension.$extension",
				"x2" => "$withoutExtension@2x.$extension",
				"x3" => "$withoutExtension@3x.$extension"
			);
		}

		/**
		 * Refresh cached configuration. This method updates the properties of the class instance.
		 * Called from the constructor and after settings are saved. As an extension of WC_Payment_Gateway,
		 * `$this->settings` is automatically refreshed when settings are saved, but our custom properties
		 * are not. So this method is attached to a WooCommerce hook to ensure properties are up to date
		 * when the cron jobs run.
		 *
		 * Note:	Hooked onto the "woocommerce_update_options_payment_gateways_clearpay" Action.
		 *
		 * @since	2.1.0
		 */
		public function refresh_cached_configuration() {
			if (array_key_exists('title', $this->settings)) {
				$this->title = $this->settings['title'];
			}
		}

		/**
		 * Logging method. Using this to log a string will store it in a file that is accessible
		 * from "WooCommerce > System Status > Logs" in the WordPress admin. No FTP access required.
		 *
		 * @param 	string	$message	The message to log.
		 * @uses	WC_Logger::add()
		 */
		public static function log($message) {
			if (empty(self::$log)) {
				self::$log = new WC_Logger;
			}
			if (is_array($message)) {
				/**
				 * @since 2.1.0
				 * Properly expand Arrays in logs.
				 */
				$message = print_r($message, true);
			} elseif(is_object($message)) {
				/**
				 * @since 2.1.0
				 * Properly expand Objects in logs.
				 *
				 * Only use the Output Buffer if it's not currently active,
				 * or if it's empty.
				 *
				 * Note:	If the Output Buffer is active but empty, we write to it,
				 * 			read from it, then discard the contents while leaving it active.
				 *
				 * Otherwise, if $message is an Object, it will be logged as, for example:
				 * (foo Object)
				 */
				$ob_get_length = ob_get_length();
				if (!$ob_get_length) {
					if ($ob_get_length === false) {
						ob_start();
					}
					var_dump($message);
					$message = ob_get_contents();
					if ($ob_get_length === false) {
						ob_end_clean();
					} else {
						ob_clean();
					}
				} else {
					$message = '(' . get_class($message) . ' Object)';
				}
			}
			self::$log->add( 'clearpay', $message );
		}

		/**
		 * Instantiate the class if no instance exists. Return the instance.
		 *
		 * @since	2.0.0
		 * @return	WC_Gateway_Clearpay
		 */
		public static function getInstance()
		{
			if (is_null(self::$instance)) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Is the gateway configured? This method returns true if any of the credentials fields are not empty.
		 *
		 * @since	2.0.0
		 * @return	bool
		 * @used-by	self::render_admin_notices()
		 */
		private function is_configured() {
			if (!empty($this->settings['prod-id']) ||
				!empty($this->settings['prod-secret-key']) ||
				!empty($this->settings['test-id']) ||
				!empty($this->settings['test-secret-key']))
			{
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Add the Clearpay gateway to WooCommerce.
		 *
		 * Note:	Hooked onto the "woocommerce_payment_gateways" Filter.
		 *
		 * @since	2.0.0
		 * @see		ClearpayPlugin::__construct()	For hook attachment.
		 * @param	array	$methods				Array of Payment Gateways.
		 * @return	array							Array of Payment Gateways, with Clearpay added.
		 **/
		public function add_clearpay_gateway($methods) {
			$methods[] = 'WC_Gateway_Clearpay';
			return $methods;
		}

		/**
		 * Check if the gateway is available for use.
		 *
		 * @return bool
		 */
		public function is_available() {
			$within_limits = true;
			if (WC()->cart) {
				$total = $this->get_order_total();
				$within_limits = $this->is_price_within_limits($total);
			}

			$products_supported = true; # Note: This may not be altered if no check is performed, such as when inside the admin.
			if (is_wc_endpoint_url('order-pay') && get_query_var( 'order-pay' ) > 0) {
				$order_id = absint(get_query_var('order-pay' ));
				$products_supported = $this->order_products_are_supported($order_id);
			} elseif (WC()->cart) {
				$products_supported = $this->cart_products_are_supported();
			}

			return
				$this->enabled === 'yes' &&
				$this->frontend_is_ready() &&
				$within_limits  !== false &&
				$products_supported !== false;
		}

		/**
		 * Check if the gateway is available for WC Blocks.
		 * Note:	Order amount is checked on the frontend instead.
		 *
		 * @return bool
		 */
		public function is_available_for_blocks() {
			$products_supported = true;
			if (WC()->cart) {
				$products_supported = $this->cart_products_are_supported();
			}
			return
				$this->enabled === 'yes' &&
				$this->frontend_is_ready() &&
				$products_supported !== false;
		}

		/**
		 * Display Clearpay Assets on Normal Products
		 * Note:	Hooked onto the "woocommerce_get_price_html" Filter.
		 *
		 * @since	2.0.0
		 * @see		Clearpay_Plugin::__construct()	For hook attachment.
		 * @param 	float $price
		 * @param 	WC_Product $product
		 * @uses	WC_Product::is_in_stock()
		 * @uses	self::render_placement()
		 * @return	string
		 */
		function filter_woocommerce_get_price_html($price, $product) {
			if (is_object($product)
				&& $product instanceof WC_Product_Variation
				&& $product->is_in_stock()
				&& isset($this->settings['show-info-on-product-variant'])
				&& $this->settings['show-info-on-product-variant'] == 'yes'
			) {
				ob_start();
				$this->render_placement('product-variant', $product);
				$clearpay_html = ob_get_clean();

				$price .= $clearpay_html;
			}
			return $price;
		}

		/**
		 * The WC_Payment_Gateway::$icon property only accepts a string for the image URL. Since we want
		 * to support high pixel density screens and specifically define the width and height attributes,
		 * this method attaches to a Filter hook so we can build our own HTML markup for the IMG tag.
		 *
		 * Note:	Hooked onto the "woocommerce_gateway_icon" Filter.
		 *
		 * @since	2.0.0
		 * @see		Clearpay_Plugin::__construct()	For hook attachment.
		 * @param	string 	$icon_html		Icon HTML
		 * @param	string 	$gateway_id		Payment Gateway ID
		 * @return	string
		 */
		public function filter_woocommerce_gateway_icon($icon_html, $gateway_id) {
			if ($gateway_id != 'clearpay') {
				return $icon_html;
			}

			$static_url = $this->get_static_url();
			$image_path = 'integration/checkout/logo-clearpay-colour-131x25';
			$logo = $this->generate_source_sets($static_url, $image_path, 'png');

			ob_start();

			?><img
				src="<?php echo esc_url($logo->x1); ?>"
				srcset="
					<?php echo esc_url($logo->x1); ?> 1x,
					<?php echo esc_url($logo->x2); ?> 2x,
					<?php echo esc_url($logo->x3); ?> 3x"
				width="131"
				height="25"
				alt="Clearpay" /><?php

			return ob_get_clean();
		}

		/**
		 * Render admin notices if applicable. This will print an error on every page of the admin if the cron failed to
		 * authenticate on its last attempt.
		 *
		 * Note:	Hooked onto the "admin_notices" Action.
		 * Note:	This runs BEFORE WooCommerce fires its "woocommerce_update_options_payment_gateways_<gateway_id>" actions.
		 *
		 * @since	2.0.0
		 * @uses	get_transient()			Available in WordPress core since 2.8.0
		 * @uses	delete_transient()		Available in WordPress core since 2.8.0
		 * @uses	admin_url()				Available in WordPress core since 2.6.0
		 * @uses	delete_option()
		 * @uses	self::is_configured()
		 */
		public function render_admin_notices() {
			/**
			 * Also change the activation message to include a link to the plugin settings.
			 *
			 * Note:	We didn't add the "is-dismissible" class here because we continually show another
			 *			message similar to this until the API credentials are entered.
			 *
			 * @see		./wp-admin/plugins.php	For the markup that this replaces.
			 * @uses	get_transient()			Available in WordPress core since 2.8.0
			 * @uses	delete_transient()		Available in WordPress core since 2.8.0
			 */
			if (function_exists('get_transient') && function_exists('delete_transient')) {
				if (get_transient( 'clearpay-admin-activation-notice' )) {
					?>
					<div class="updated notice">
						<p><?php _e( 'Plugin <strong>activated</strong>.' ) ?></p>
						<p><?php _e( 'Thank you for choosing Clearpay.', 'woo_clearpay' ); ?> <a href="<?php echo esc_url(admin_url( 'admin.php?page=wc-settings&tab=checkout&section=clearpay' )); ?>"><?php _e( 'Configure Settings.', 'woo_clearpay' ); ?></a></p>
						<p><?php _e( 'Don&rsquo;t have an Clearpay Merchant account yet?', 'woo_clearpay' ); ?> <a href="<?php echo esc_url($this->assets['retailer_url']); ?>" target="_blank"><?php _e( 'Apply online today!', 'woo_clearpay' ); ?></a></p>
					</div>
					<?php
					if (array_key_exists('activate', $_GET) && $_GET['activate'] == 'true') {
						unset($_GET['activate']); # Prevent the default "Plugin *activated*." notice.
					}
					delete_transient( 'clearpay-admin-activation-notice' );
					# No need to decide whether to render any API errors. We've only just activated the plugin.
					return;
				}
			}

			if (array_key_exists('woocommerce_clearpay_enabled', $_POST)) {
				# Since this runs before we handle the POST, we can clear any stored error here.
				delete_option( 'woocommerce_clearpay_api_error' );

				# If we're posting changes to the Clearpay settings, don't pull anything out of the database just yet.
				# This runs before the POST gets handled by WooCommerce, so we can wait until later.
				# If the updated settings fail, that will trigger its own error later.
				return;
			}

			$show_link = true;
			if (array_key_exists('page', $_GET) && array_key_exists('tab', $_GET) && array_key_exists('section', $_GET)) {
				if ($_GET['page'] == 'wc-settings' && $_GET['tab'] == 'checkout' && $_GET['section'] == 'clearpay') {
					# We're already on the Clearpay gateway's settings page. No need for the circular link.
					$show_link = false;
				}
			}

			$error = get_option( 'woocommerce_clearpay_api_error' );
			if (is_object($error) && $this->settings['enabled'] == 'yes') {
				?>
				<div class="error notice">
					<p>
						<strong><?php _e( "Clearpay API Error #{$error->code}:", 'woo_clearpay' ); ?></strong>
						<?php _e( $error->message, 'woo_clearpay' ); ?>
						<?php if (property_exists($error, 'id') && $error->id): ?>
							<em><?php _e( "(Error ID: {$error->id})", 'woo_clearpay' ); ?></em>
						<?php endif; ?>
						<?php if ($show_link): ?>
							<a href="<?php echo esc_url(admin_url( 'admin.php?page=wc-settings&tab=checkout&section=clearpay' )); ?>"><?php _e( 'Please check your Clearpay Merchant settings here.', 'woo_clearpay' ); ?></a>
						<?php endif; ?>
					</p>
				</div>
				<?php
				return;
			}

			# Also include a link to the plugin settings if they haven't been saved yet,
			# unless they have unchecked the Enabled checkbox in the settings.
			if (!$this->is_configured() && $this->settings['enabled'] == 'yes' && $show_link) {
				?>
				<div class="updated notice">
					<p><?php _e( 'Thank you for choosing Clearpay.', 'woo_clearpay' ); ?> <a href="<?php echo esc_url(admin_url( 'admin.php?page=wc-settings&tab=checkout&section=clearpay' )); ?>"><?php _e( 'Configure Settings.', 'woo_clearpay' ); ?></a></p>
					<p><?php _e( 'Don&rsquo;t have an Clearpay Merchant account yet?', 'woo_clearpay' ); ?> <a href="<?php echo esc_url($this->assets['retailer_url']); ?>" target="_blank"><?php _e( 'Apply online today!', 'woo_clearpay' ); ?></a></p>
				</div>
				<?php
				return;
			}
			if(isset($this->settings['clearpay-plugin-version']) && $this->settings['clearpay-plugin-version'] != Clearpay_Plugin::$version){
					?>
					<div class='updated notice'>
					<p>Clearpay Gateway for WooCommerce has updated from <?php echo esc_html($this->settings['clearpay-plugin-version']) ?> to <?php echo esc_html(Clearpay_Plugin::$version); ?>. Please review and re-save your settings <?php if ($show_link){ ?><a href="<?php echo esc_url(admin_url( 'admin.php?page=wc-settings&tab=checkout&section=clearpay' )); ?>"><?php _e( 'here', 'woo_clearpay' ); ?></a><?php } else { _e( 'below', 'woo_clearpay' );} ?>.</p>
					</div>
					<?php
			}
			else if(!isset($this->settings['clearpay-plugin-version'])){
				?>
				<div class='updated notice'><p>Clearpay Gateway for WooCommerce has updated to version <?php echo esc_html(Clearpay_Plugin::$version); ?>. Please review and re-save your settings <?php if ($show_link){ ?> <a href="<?php echo esc_url(admin_url( 'admin.php?page=wc-settings&tab=checkout&section=clearpay' )); ?>"><?php _e( 'here', 'woo_clearpay' ); ?></a><?php } else { _e( 'below', 'woo_clearpay' );} ?>.</p></div>
				<?php
			}
		}

		/**
		 * Admin Panel Options. Overrides the method defined in the parent class.
		 *
		 * @since	2.0.0
		 * @see		WC_Payment_Gateway::admin_options()			For the method that this overrides.
		 * @uses	WC_Settings_API::generate_settings_html()
		 */
		public function admin_options() {
			?>
			<h3><?php _e( 'Clearpay Gateway', 'woo_clearpay' ); ?></h3>

			<table class="form-table">
				<?php $this->generate_settings_html(); ?>
			</table>
			<?php
		}

		/**
		 * Get the current static URL based on our user settings. Defaults to the Sandbox URL.
		 *
		 * @since	2.1.7
		 * @return	string
		 */
		public function get_static_url() {
			$static_url = $this->environments[$this->settings['testmode']]['static_url'];

			if (empty($static_url)) {
				$static_url = $this->environments['sandbox']['static_url'];
			}

			return $static_url;
		}

		/**
		 * Get the Merchant ID from our user settings. Uses the Sandbox account for all environments except Production.
		 *
		 * @since	2.0.0
		 * @return	string
		 */
		public function get_merchant_id() {
			if ($this->settings['testmode'] == 'production') {
				return $this->settings['prod-id'];
			}
			return $this->settings['test-id'];
		}

		/**
		 * Get the Secret Key from our user settings. Uses the Sandbox account for all environments except Production.
		 *
		 * @since	2.0.0
		 * @return	string
		 */
		public function get_secret_key() {
			if ($this->settings['testmode'] == 'production') {
				return $this->settings['prod-secret-key'];
			}
			return $this->settings['test-secret-key'];
		}

		/**
		 * Get API environment based on our user settings.
		 *
		 * @since 2.2.0
		 * @return string
		 */
		public function get_api_env() {
			return $this->settings['testmode'];
		}

		/**
		 * Get locale based on trading country.
		 *
		 * @since 2.2.0
		 * @return string
		 */
		public function get_js_locale() {
			$locale_by_country = array(
				'FR' => 'fr_FR',
				'IT' => 'it_IT',
				'ES' => 'es_ES',
				'GB' => 'en_GB',
			);
			$country = $this->get_country_code();
			if ($country != 'GB' && substr(get_locale(), 0, 2) == 'en') {
				return 'en_' . $country; // Allow EU merchants to show placements in English
			}
			return $locale_by_country[$country];
		}

		/**
		 * Convert the global $post object to a WC_Product instance.
		 *
		 * @since	2.0.0
		 * @global	WP_Post	$post
		 * @uses	wc_get_product()	Available as part of the WooCommerce core plugin since 2.2.0.
		 *								Also see:	WC()->product_factory->get_product()
		 *								Also see:	WC_Product_Factory::get_product()
		 * @return	WC_Product|null|false		See: wc_get_product()
		 * @used-by self::render_placement()
		 */
		private function get_product_from_the_post() {
			global $post;

			$product = wc_get_product( $post->ID );

			return $product;
		}

		/**
		 * Is the given product supported by the Clearpay gateway?
		 *
		 * Note:	Some products may not be allowed to be purchased with Clearpay unless
		 *			combined with other products to lift the cart total above the merchant's
		 *			minimum. By default, this function will not check the merchant's
		 *			minimum. Set $alone to true to check if the product can be
		 *			purchased on its own.
		 *
		 * @since	2.0.0
		 * @param	WC_Product	$product									The product in question, in the form of a WC_Product object.
		 * @param	bool		$alone										Whether to view the product on its own.
		 *																	This affects whether the minimum setting is considered.
		 * @uses	WC_Product::get_type()									Possibly available as part of the WooCommerce core plugin since 2.6.0.
		 * @uses	WC_Product::get_price()									Possibly available as part of the WooCommerce core plugin since 2.6.0.
		 * @uses	apply_filters()											Available in WordPress core since 0.17.
		 * @return	bool													Whether or not the given product is eligible for Clearpay.
		 * @used-by self::render_placement()
		 */
		private function is_product_supported($product, $alone = false) {
			if (!isset($this->settings['enabled']) || $this->settings['enabled'] != 'yes') {
				return false;
			}

			if (!is_object($product)) {

				return false;
			}

			$product_type = $product->get_type();
			if (preg_match('/subscription/', $product_type)) {
				# Subscription products are not supported by Clearpay.
				return false;
			}

			if (class_exists('WC_Pre_Orders_Product')
				&& WC_Pre_Orders_Product::product_can_be_pre_ordered($product)
				&& WC_Pre_Orders_Product::product_is_charged_upon_release($product)
			) {
				return false;
			}

			if (!empty($this->settings['excluded-categories'])) {
				# Ineligible product categories
				if ($product instanceof WC_Product_Variation &&
					$parent_product = wc_get_product($product->get_parent_id())
				) {
					# Because categories are not inherited properly
					$cat_ids = $parent_product->get_category_ids();
				} else {
					$cat_ids = $product->get_category_ids();
				}

				$slugs = explode(',', $this->settings['excluded-categories']);
				foreach($slugs as $slug) {
					$cat = get_term_by('slug', trim($slug), 'product_cat');
					if ($cat && in_array($cat->term_id, $cat_ids)) {
						return false;
					}
				}
			}

			# Allow other plugins to exclude Clearpay from products that would otherwise be supported.
			return (bool)apply_filters( 'clearpay_is_product_supported', true, $product, $alone );
		}

		/**
		 * Is Price within the Clearpay Limit?
		 *
		 *
		 * @since	2.1.2
		 * @param	$amount													The price to be checked.
		 * @return	bool													Whether or not the given price is ithin the Clearpay Limits.
		 */
		private function is_price_within_limits($amount) {

			/* Check for API Failure */
			if (!$this->api_is_ok()) {
				return false;
			}

			if ($amount >= 0.04 && $amount >= floatval($this->getOrderLimitMin()) && $amount <= floatval($this->getOrderLimitMax())){
				return true;
			}
			else{
				return false;
			}
		}

		/**
		 * Check if this gateway is available in the user's country based on currency.
		 *
		 * @return bool
		 */
		public function is_valid_for_use() {
			return in_array(
				get_woocommerce_currency(),
				array( 'AUD', 'CAD', 'NZD', 'USD', 'GBP', 'EUR' ),
				true
			);
		}

		/**
		 * Print a paragraph of Clearpay info onto the individual product pages if enabled and the product is valid.
		 *
		 * Note:	Hooked onto the "woocommerce_single_product_summary" Action.
		 *
		 * @since	2.0.0
		 * @see		Clearpay_Plugin::__construct()							For hook attachment.
		 * @param	WC_Product|null		$product							The product for which to print instalment info.
		 * @uses	self::render_placement()
		 */
		public function print_info_for_product_detail_page($product = null) {
			if (isset($this->settings['show-info-on-product-pages'])
				&& $this->settings['show-info-on-product-pages'] == 'yes'
			) {
				$this->render_placement('product-pages', $product);
			}
		}

		/**
		 * Print a paragraph of Clearpay info onto each product item in the shop loop if enabled and the product is valid.
		 *
		 * Note:	Hooked onto the "woocommerce_after_shop_loop_item_title" Action.
		 *
		 * @since	2.0.0
		 * @see		Clearpay_Plugin::__construct()							For hook attachment.
		 * @param	WC_Product|null		$product							The product for which to print instalment info.
		 * @uses	self::render_placement()
		 */
		public function print_info_for_listed_products($product = null) {
			if (isset($this->settings['show-info-on-category-pages'])
				&& $this->settings['show-info-on-category-pages'] == 'yes'
			) {
				$this->render_placement('category-pages', $product);
			}
		}

		/**
		 * Checks that the currency values match
		 *
		 * @todo: update the name of this function to better match behaviour
		 *
		 * @used-by self::render_cart_page_elements()
		 * @used-by self::is_available()
		 *
		 * @return Boolean
		 */
		private function currency_is_supported() {
			if (empty($this->settings['settlement-currency'])) {
				return false;
			}

			$supported = [ $this->settings['settlement-currency'] ];

			if ( isset($this->settings['cbt-limits'])
				&& isset($this->settings['enable-multicurrency'])
				&& $this->settings['enable-multicurrency'] == 'yes'
			) {
				// Support multicurrency only when the feature has been enabled in merchant's country
				if ($this->feature_is_available('multicurrency')) {
					$limits = json_decode($this->settings['cbt-limits'], true);
					if (is_array($limits)) {
						$supported = array_merge($supported, array_keys($limits));
					}
				}
			}

			return in_array(get_woocommerce_currency(), $supported);
		}

		public function get_ei_configs() {
			$ei_configs_url = $this->get_static_url() . 'data/ei-configs.json';
			$result = '';

			$response = wp_remote_get($ei_configs_url);
			if (!is_wp_error($response)) {
			  $headers = wp_remote_retrieve_headers($response);
			  if (isset($headers['content-type']) && 'application/json' === $headers['content-type']) {
					$result = wp_remote_retrieve_body($response);
			  }
			}

			return $result;
		}

		/**
		 * Checks if a feature is available in the merchant's country
		 *
		 * @since 3.5.0
		 * @param String	$feature
		 * @return Boolean
		 */
		public function feature_is_available($feature) {
			$is_available = false;

			if (!empty($this->settings['ei-configs'])) {
				$ei_configs = json_decode($this->settings['ei-configs'], true);
				$country = strtolower($this->get_country_code());
				if (isset($ei_configs['feature'][$feature][$country])) {
					$schedule = $ei_configs['feature'][$feature][$country];
					$is_available = time() > strtotime($schedule);
				}
			}

			return $is_available;
		}

		/**
		 * Checks that the gateway is supported
		 *
		 * @used-by self::render_cart_page_elements()
		 * @used-by self::is_available()
		 *
		 * @return Boolean
		 */
		private function payment_is_enabled() {
			return array_key_exists('enabled', $this->settings) && $this->settings['enabled'] == 'yes';
		}

		private function cart_total_is_positive() {
			return WC()->cart->total > 0;
		}

		/**
		 * Checks that cart products are supported
		 *
		 * @used-by self::render_express_checkout_on_cart_page()
		 * @used-by self::is_available()
		 *
		 * @return Boolean
		 */
		private function cart_products_are_supported() {
			if (did_action('wp_loaded')) {
				foreach (WC()->cart->get_cart() as $cart_item) {
					$product = $cart_item['data'];
					if (!$this->is_product_supported($product)) {
						return false;
					}
				}
			}

			return true;
		}

		/**
		 * Checks that order products are supported
		 *
		 * @used-by self::is_available()
		 *
		 * @return Boolean
		 */
		private function order_products_are_supported($order_id) {
			$order = wc_get_order( $order_id );

			if ($order && count($order->get_items()) > 0) {
				foreach ( $order->get_items() as $item ) {
					$product = $item->get_product();
					if (!$this->is_product_supported($product)) {
						return false;
					}
				}
			}

			return true;
		}

		/**
		 * Checks that the API is still available by checking against settings
		 *
		 * @used-by self::render_cart_page_elements()
		 * @used-by self::is_available()
		 *
		 * @return Boolean
		 */
		private function api_is_ok() {
			return !($this->settings['pay-over-time-limit-min'] == 'N/A' && $this->settings['pay-over-time-limit-max'] == 'N/A'
			|| empty($this->settings['pay-over-time-limit-min']) && empty($this->settings['pay-over-time-limit-max']));
		}

		/**
		 * Calls functions that render Clearpay elements on Cart page
		 * 		- logo
		 * 		- payment schedule
		 * 		- express button.
		 *
		 * This is dependant on all of the following criteria being met:
		 * 		- The currency is supported
		 *		- The Clearpay Payment Gateway is enabled.
		 *		- The cart total is valid and within the merchant payment limits.
		 *		- All of the items in the cart are considered eligible to be purchased with Clearpay.
		 *
		 * Note:	Hooked onto the "woocommerce_cart_totals_after_order_total" Action.
		 *
		 * @since	3.1.0
		 * @see		Clearpay_Plugin::__construct()								For hook attachment.
		 * @uses	self::frontend_is_ready()
		 * @uses	self::cart_total_is_positive()
		 * @uses	self::render_schedule_on_cart_page()
		 */
		public function render_cart_page_elements() {
			if( $this->frontend_is_ready()
				&& $this->cart_total_is_positive()
			) {
				$this->render_schedule_on_cart_page();
				$this->render_express_checkout_on_cart_page();
			}
		}

		/**
		 * Render Clearpay elements (logo and payment schedule) on Cart page.
		 *
		 * This is dependant on the following criteria being met:
		 *		- The "Payment Info on Cart Page" box is ticked and there is a message to display.
		 *
		 * @since	2.0.0
		 * @uses	self::render_placement()
		 * @used-by	self::render_cart_page_elements()
		 */
		public function render_schedule_on_cart_page() {
			if (isset($this->settings['show-info-on-cart-page'])
				&& $this->settings['show-info-on-cart-page'] == 'yes'
			) {
				echo '<tr><td colspan="2">';
				$this->render_placement('cart-page');
				echo '</td></tr>';
			}
		}

		/**
		 * Check cart totals are within limits
		 *
		 * @used-by self::render_express_checkout_on_cart_page()
		 *
		 * @return Boolean
		 */
		public function cart_is_within_limits() {
			$total = WC()->cart->get_cart_contents_total() + WC()->cart->get_cart_contents_tax();

			return
				$total >= $this->getOrderLimitMin() &&
				$total <= $this->getOrderLimitMax();
		}

		/**
		 * Render the express checkout elements on Cart page.
		 *
		 * This is dependant on the following criteria being met:
		 *		- The "Show express on cart page" box is ticked.
		 *
		 * @since	3.1.0
		 * @used-by	self::render_cart_page_elements()
		 */
		public function render_express_checkout_on_cart_page() {
			if (
				!isset($this->settings['show-express-on-cart-page']) ||
				$this->settings['show-express-on-cart-page'] != 'yes' ||
				get_woocommerce_currency() != $this->settings['settlement-currency'] ||
				!$this->cart_is_within_limits() ||
				!$this->cart_products_are_supported() ||
				$this->cart_is_virtual()
			) {
				return;
			}

			wp_enqueue_style( 'clearpay_css' );
			wp_enqueue_script('clearpay_express');

			$button_html = str_replace('[THEME]', $this->settings['express-button-theme'], $this->assets['cart_page_express_button']);

			echo wp_kses($button_html, array(
				'tr' => true,
				'td' => array( 'colspan' => true, 'class' => true, ),
				'button' => array( 'id' => true, 'class' => true, 'type' => true, 'disabled' => true, ),
				'img' => array( 'src' => true, 'alt' => true, ),
			));
		}

		/**
		 * Get the country code
		 *
		 * @since 3.1.0
		 * @used-by Clearpay_Plugin::init_website_assets()
		 */
		public function get_country_code () {
			if (!isset($this->settings['trading-country']) || $this->settings['trading-country'] == 'auto') {
				$currency =	get_option('woocommerce_currency'); // Unfiltered base currency
				if ($currency === 'EUR') {
					$countryCode = substr(get_locale(), -2);
					if (!in_array($countryCode, ['FR', 'IT', 'ES'])) {
						// Fallback in case the site language is irrelevant
						$countryCode = 'FR';
					}
				} else {
					$mapping = array(
						'GBP' => 'GB',
					);
					$countryCode = array_key_exists($currency, $mapping) ? $mapping[$currency] : 'GB';
				}
			} else {
				$countryCode = $this->settings['trading-country'];
			}
			return $countryCode;
		}

		/**
		 * Display as a payment option on the checkout page.
		 *
		 * Note:	This overrides the method defined in the parent class.
		 *
		 * @since	2.0.0
		 * @see		WC_Payment_Gateway::payment_fields()						For the method that this overrides.
		 * @uses	get_woocommerce_currency()
		 */
		public function payment_fields() {
			$order_total = $this->get_order_total();
			$currency = get_woocommerce_currency();
			// If on 'Pay for order' page, use order currency instead
			if (is_wc_endpoint_url('order-pay') && get_query_var( 'order-pay' ) > 0) {
				$order_id = absint(get_query_var('order-pay'));
				$order = wc_get_order($order_id);
				$currency = $order->get_currency();
			}
			$locale = 'en-' . $this->get_country_code(); // Locales supported by the Checkout Widget are "en-AU", "en-NZ", "en-US", "en-CA" and "en-GB".

			include "{$this->include_path}/instalments.html.php";
		}

		/**
		 * This is called by the WooCommerce checkout via AJAX, if Clearpay was the selected payment method.
		 *
		 * Note:	This overrides the method defined in the parent class.
		 *
		 * @since	2.0.0
		 * @see		WC_Payment_Gateway::process_payment()	For the method we are overriding.
		 * @param	int	$order_id					The ID of the order.
		 * @return	array
		 */
		public function process_payment($order_id) {
			$order = wc_get_order( $order_id );
			$order_number = $order->get_order_number();
			self::log("Processing payment for WooCommerce Order #{$order_number}...");

			$currency = $order->get_currency();
			$result = [];

			if ($currency != get_woocommerce_currency() && !is_wc_endpoint_url('order-pay')) {
				// Intercept at checkout page
				self::log("Multicurrency has been enabled. However, orders are not created in consumer currency.");
				wc_add_notice( __( "Warning: The order was not created in your selected currency. If you choose to proceed with Clearpay, you might be charged a different amount in your selected currency than what you saw on the site.", 'woo_clearpay' ), 'notice' );
				$result = array(
					'result'   => 'success',
					'redirect' => $order->get_checkout_payment_url()
				);
			}
			else {
				try {
					$this->check_pre_order($order);
					$createCheckoutRequest = new CreateCheckout();
					$createCheckoutRequest
						->setAmount($order->get_total(), $currency)
							->setConsumer([
									'phoneNumber' => $order->get_billing_phone(),
									'givenNames' => $order->get_billing_first_name(),
									'surname' => $order->get_billing_last_name(),
									'email' => $order->get_billing_email()
							])
							->setBilling([
							'name' => $order->get_formatted_billing_full_name(),
							'line1' => $order->get_billing_address_1(),
									'line2' => $order->get_billing_address_2(),
									'area1' => $order->get_billing_city(),
									'region' => $order->get_billing_state() ?: 'N/A',
									'postcode' => $order->get_billing_postcode(),
									'countryCode' => $order->get_billing_country(),
									'phoneNumber' => $order->get_billing_phone()
							])
						->setMerchantReference($order_id)
							->setTaxAmount($order->get_total_tax(), $currency)
						->setMerchant([
									'redirectConfirmUrl' => WC()->api_request_url('WC_Gateway_Clearpay'),
									'redirectCancelUrl' => $order->get_checkout_payment_url()
							]);

					if ($order->needs_shipping_address()) {
						$createCheckoutRequest
							->setShipping([
								'name' => $order->get_formatted_shipping_full_name(),
										'line1' => $order->get_shipping_address_1(),
										'line2' => $order->get_shipping_address_2(),
										'area1' => $order->get_shipping_city(),
										'region' => $order->get_shipping_state() ?: 'N/A',
										'postcode' => $order->get_shipping_postcode(),
										'countryCode' => $order->get_shipping_country()
								])
							->setShippingAmount((float) $order->get_shipping_total(), $currency);
					}

					if ($items = $order->get_items()) {
						$itemsData = [];
						foreach ($items as $item) {
							if ($product = $item->get_product()) {
								$itemsData[] = [
									'name' => $product->get_name(),
									'sku' => $product->get_sku(),
									'quantity' => $item->get_quantity(),
									'price' => [ (float) $item->get_subtotal() / $item->get_quantity(), $currency ]
								];
							}
						}
						$createCheckoutRequest->setItems($itemsData);
					}

					if (method_exists($order, 'get_coupons')) {
						$coupons = $order->get_coupons();
					} else {
						$coupons = $order->get_items('coupon'); // fallback for pre WC 3.7
					}
					if ($coupons) {
						$discountsData = [];
						foreach ($coupons as $coupon) {
							$discountsData[] = [
								'displayName' => $coupon->get_name(),
								'amount' => [(float) $coupon->get_discount(), $currency]
							];
						}
						$createCheckoutRequest->setDiscounts($discountsData);
					}

					$successful = $createCheckoutRequest->send();
					$body = $createCheckoutRequest->getResponse()->getParsedBody();

					if ($successful) {
						$result = array(
							'result' => 'success',
							'redirect' => $body->redirectCheckoutUrl
						);
						$message = "Clearpay order token: {$body->token}";
					} else {
						wc_add_notice( __( "Sorry, there was a problem preparing your payment. (Error #{$body->httpStatusCode}: {$body->message})", 'woo_clearpay' ), 'error' );
						$message = "API Error #{$body->httpStatusCode} \"{$body->errorCode}\": {$body->message} (Error ID: {$body->errorId})";
					}
					self::log($message);
					$order->add_order_note( __( $message, 'woo_clearpay' ) );
				}
				catch (Exception $e) {
					self::log($e->getMessage());
					wc_add_notice( __( "Sorry, there was a problem preparing your payment.", 'woo_clearpay' ), 'error' );
				}
			}
			return $result;
		}

		/**
		 * This is triggered when customers confirm payment and return from the gateway
		 * Note:	Hooked onto the "woocommerce_api_wc_gateway_clearpay" action.
		 * @since	3.0.0
		 */
		public function capture_payment() {
			if (!empty($_GET) && !empty($_GET['orderToken']) &&
				isset($_GET['status']) && 'SUCCESS' === $_GET['status']
			) {
				$order = false;

				$clearpay_order = $this->get_checkout($_GET['orderToken']);
				if ($clearpay_order) {
					$order_id = $clearpay_order->merchantReference;
					$order = wc_get_order($order_id);
				}

				if (!$order || $order->is_paid() || $order->get_payment_method() != $this->id) {
					$exitMessage = sprintf('Could not get order details for token: %s', esc_html($_GET['orderToken']));
					wp_die( $exitMessage, 'Clearpay', array( 'response' => 500 ) );
				}

				$order_number = $order->get_order_number();
				if ($order_number != $order_id) {
					self::log("Updating merchantReference from {$order_id} to {$order_number} for token: " . $_GET['orderToken']);
				}

				$this->check_pre_order($order);
				$payment = $this->immediate_payment_capture($_GET['orderToken'], $order_number);

				if ($payment) {
					self::log("Payment {$payment->status} for WooCommerce Order #{$order_number} (Clearpay Order #{$payment->id}).");
					if ($payment->status == 'APPROVED') {
						$order->add_order_note(sprintf(__('Payment approved. Clearpay Order ID: %s.', 'woo_clearpay'), $payment->id));

						$order_currency = $order->get_currency();
						if ($order_currency != $this->settings['settlement-currency'] && isset($this->settings['cbt-limits'])) {
								$limits = json_decode($this->settings['cbt-limits'], true);
								if (is_array($limits) && isset($limits[$order_currency]['rate'])) {
									$order->add_order_note(sprintf(__('Approximate Clearpay exchange rate: %s.', 'woo_clearpay'), $limits[$order_currency]['rate']));
								}
						}

						$order->payment_complete($payment->id);
						update_post_meta($order_id, '_transaction_url', $payment->merchantPortalOrderUrl);
						if (wp_redirect( $this->get_return_url($order) )) {
							exit;
						}
					} else {
						$order->add_order_note(sprintf(__('Payment declined. Clearpay Order ID: %s.', 'woo_clearpay'), $payment->id));
						$order->update_status('failed');
						wc_add_notice(sprintf(__('Your payment was declined for Clearpay Order #%s. Please try again. For more information, please submit a request via <a href="%s" style="text-decoration: underline;">Clearpay Help Center.</a>', 'woo_clearpay'), $payment->id, $this->assets['help_center_url']), 'error');
						if (wp_redirect( $order->get_checkout_payment_url() )) {
							exit;
						}
					}
				} else {
					self::log("Updating status of WooCommerce Order #{$order_number} to \"Failed\", because payment failed.");
					$order->add_order_note(__('Clearpay payment failed.', 'woo_clearpay'));
					$order->update_status('failed');
					wc_add_notice( __( 'Payment failed. Please try again.', 'woo_clearpay' ), 'error' );
					if (wp_redirect( $order->get_checkout_payment_url() )) {
						exit;
					}
				}
			}
			wp_die( 'Invalid request to Clearpay callback', 'Clearpay', array( 'response' => 500 ) );
		}

		/**
		 * Get the merchant portal order URL.
		 *
		 * @param  WC_Order $order Order object.
		 * @return string
		 */
		public function get_transaction_url($order) {
			$url = get_post_meta($order->get_id(), '_transaction_url', true);
			if (empty($url)) {
				try {
					$url = UrlHelper::generateMerchantPortalOrderUrl($order->get_transaction_id(), $this->get_country_code(), $this->get_api_env());
				} catch (Exception $e) {
					self::log($e->getMessage());
				}
			}
			return $url;
		}

		/**
		 * Can the order be refunded?
		 *
		 * @since	1.0.0
		 * @param	WC_Order	$order
		 * @return	bool
		 */
		public function can_refund_order($order) {
			$has_api_creds = false;

			if ($this->settings['testmode'] == 'production') {
				$has_api_creds = $this->settings['prod-id'] && $this->settings['prod-secret-key'];
			} else {
				$has_api_creds = $this->settings['test-id'] && $this->settings['test-secret-key'];
			}

			return $order && $order->get_transaction_id() && $has_api_creds;
		}

		/**
		 * Process a refund if supported.
		 *
		 * Note:	This overrides the method defined in the parent class.
		 *
		 * @since	1.0.0
		 * @see		WC_Payment_Gateway::process_refund()		For the method that this overrides.
		 * @param	int			$order_id
		 * @param	float		$amount							Optional. The amount to refund. This cannot exceed the total.
		 * @param	string		$reason							Optional. The reason for the refund. Defaults to an empty string.
		 * @return	bool
		 */
		public function process_refund($order_id, $amount = null, $reason = '') {
			$order = wc_get_order( $order_id );

			if (!$this->can_refund_order($order)) {
				return new WP_Error( 'error', __( 'Refund failed.', 'woocommerce' ) );
			}

			$order_number = $order->get_order_number();
			self::log("Refunding WooCommerce Order #{$order_number} for \${$amount}...");

			try {
				$this->check_pre_order($order);
				$refundRequest = new CreateRefund([
					'amount' => [
	                    'amount' => $amount,
	                    'currency' => $order->get_currency()
	                ]
				]);
				$refundRequest->setOrderId($order->get_transaction_id());
				$successful = $refundRequest->send();
				$body = $refundRequest->getResponse()->getParsedBody();
				if (!$successful) {
					self::log("API ERROR #{$body->httpStatusCode} \"{$body->errorCode}\": {$body->message} (Error ID: {$body->errorId})");
				} else {
					self::log("Refund successful. Refund ID: {$body->refundId}.");
					$order->add_order_note( __( "Refund of \${$amount} sent to Clearpay. Reason: {$reason}", 'woo_clearpay' ) );
					return true;
				}
			}
			catch (Exception $e) {
				self::log($e->getMessage());
			}

			$order->add_order_note( __( "Failed to send refund of \${$amount} to Clearpay.", 'woo_clearpay' ) );
			return false;
		}

		/**
		 * Return the current settings for Clearpay Plugin
		 *
		 * @since	2.1.0
		 * @used-by	generate_category_hooks(), generate_product_hooks()
		 * @return 	array 	settings array values
		 */
		public function getSettings() {
			return $this->settings;
		}

		/**
		 * Returns Default Customisation Settings of Clearpay Plugin
		 *
		 * Note:	Hooked onto the "wp_ajax_clearpay_action" Action.
		 *
		 * @since	2.1.2
		 * @uses	get_form_fields()   returns $this->form_fields() array
		 * @return 	array               default clearpay customization settings
		 */
		public function reset_settings_api_form_fields() {
				$clearpay_default_settings = $this->get_form_fields();

				$settings_to_replace = array(
					'show-info-on-category-pages'           => $clearpay_default_settings['show-info-on-category-pages']['default'],
					'category-pages-placement-attributes'	=> $clearpay_default_settings['category-pages-placement-attributes']['default'],
					'category-pages-hook'                   => $clearpay_default_settings['category-pages-hook']['default'],
					'category-pages-priority'               => $clearpay_default_settings['category-pages-priority']['default'],
					'show-info-on-product-pages'            => $clearpay_default_settings['show-info-on-product-pages']['default'],
					'product-pages-placement-attributes'	=> $clearpay_default_settings['product-pages-placement-attributes']['default'],
					'product-pages-hook'                    => $clearpay_default_settings['product-pages-hook']['default'],
					'product-pages-priority'                => $clearpay_default_settings['product-pages-priority']['default'],
					'show-info-on-product-variant'          => $clearpay_default_settings['show-info-on-product-variant']['default'],
					'product-variant-placement-attributes'	=> $clearpay_default_settings['product-variant-placement-attributes']['default'],
					'show-outside-limit-on-product-page'    => $clearpay_default_settings['show-outside-limit-on-product-page']['default'],
					'show-info-on-cart-page'                => $clearpay_default_settings['show-info-on-cart-page']['default'],
					'cart-page-placement-attributes'		=> $clearpay_default_settings['cart-page-placement-attributes']['default'],
					'show-express-on-cart-page'             => $clearpay_default_settings['show-express-on-cart-page']['default'],
					'express-button-theme'                  => $clearpay_default_settings['express-button-theme']['default'],
				);

				wp_send_json($settings_to_replace);
		}

		/**
		 * Adds/Updates 'clearpay-plugin-version' in Clearpay settings
		 *
		 * Note:	Hooked onto the "woocommerce_update_options_payment_gateways_" Action.
		 *
		 * @since	2.1.2
		 * @uses	update_option()   updates option value
		 */
		public function process_admin_options() {
			parent::process_admin_options();

			$this->settings['clearpay-plugin-version'] = Clearpay_Plugin::$version;
			return update_option($this->get_option_key(), $this->settings, 'yes');
		}

		/**
		 * Provide a shortcode for rendering the standard Clearpay paragraph for theme builders.
		 *
		 * E.g.:
		 * 	- [clearpay_paragraph] OR [clearpay_paragraph type="product"] OR [clearpay_paragraph id="99"]
		 *
		 * @since	2.1.5
		 * @see		Clearpay_Plugin::__construct()		For shortcode definition.
		 * @param	array	$atts			            Array of shortcode attributes.
		 * @uses	shortcode_atts()
		 * @uses	self::render_placement()
		 * @return	string
		 */
		public function shortcode_clearpay_paragraph($atts) {
			$atts = shortcode_atts( array(
				'type' => 'product',
				'id'   => 0
			), $atts );

			if(array_key_exists('id',$atts) &&  $atts['id']!=0){
				$product = wc_get_product( $atts['id'] );
			}
			else{
				$product = $this->get_product_from_the_post();
			}

			ob_start();
			if($atts['type'] == "product" && $product instanceof WC_Product){
				$this->render_placement('product-pages', $product);
			}
			return ob_get_clean();
		}

		public function generate_express_token()
		{
			try {
				if (
					$_SERVER['REQUEST_METHOD'] != 'POST' ||
					!wp_verify_nonce($_POST['nonce'], "ec_start_nonce")
				) {
					wc_add_notice(__( 'Invalid request made', 'woo_clearpay' ), 'error' );
					throw new Exception('Invalid request', 2);
				}

				$currency = get_woocommerce_currency();
				$totals = WC()->cart->get_totals();

				$this->check_pre_order();
				$createCheckoutRequest = new CreateCheckout();
				$createCheckoutRequest
					->setMode('EXPRESS')
					->setAmount((float)$totals['cart_contents_total'], $currency)
					->setMerchant(['popupOriginUrl' => wc_get_cart_url()]);

				$chosen_shipping_methods = WC()->session->get( 'chosen_shipping_methods' );
				if (
					$chosen_shipping_methods &&
					count($chosen_shipping_methods) > 0 &&
					$chosen_shipping_method = $chosen_shipping_methods[0]
				) {
					$createCheckoutRequest->setShippingOptionIdentifier($chosen_shipping_method);
				}

				if ($items = WC()->cart->get_cart()) {
					$itemsData = [];
					foreach ($items as $item) {
						if ($item['variation_id']) {
							$product = wc_get_product($item['variation_id']);
						} else {
							$product = wc_get_product($item['product_id']);
						}
						$itemsData[] = [
							'name' => $product->get_name(),
							'sku' => $product->get_sku(),
							'quantity' => $item['quantity'],
							'price' => [ wc_get_price_excluding_tax($product), $currency ]
						];
					}
					$createCheckoutRequest->setItems($itemsData);
				}

				if ($coupons = WC()->cart->get_applied_coupons()) {
					$discountsData = [];
					foreach ($coupons as $coupon_code) {
						$discountsData[] = [
							'displayName' => $coupon_code,
							'amount' => [WC()->cart->get_coupon_discount_amount($coupon_code), $currency]
						];
					}
					$createCheckoutRequest->setDiscounts($discountsData);
				}

				$successful = $createCheckoutRequest->send();
				$body = $createCheckoutRequest->getResponse()->getParsedBody();

				if ($successful) {
					self::log("[EC] Clearpay order token: {$body->token}");
					$response = array(
						'success' => true,
						'token'  => $body->token
					);
				} else {
					wc_add_notice( __( "Something went wrong, please try again later.", 'woo_clearpay' ), 'error' );
					throw new Exception("API Error #{$body->httpStatusCode} \"{$body->errorCode}\": {$body->message} (Error ID: {$body->errorId})", 3);
				}
			}
			catch (Exception $e) {
				if (!$e->getCode()) {
					wc_add_notice( __( "Something went wrong, please try again later.", 'woo_clearpay' ), 'error' );
					$e = new Exception($e->getMessage(), 3);
				}
				$response = $this->express_error_handler($e);
			}

			wp_send_json($response);
		}

		public function fetch_express_shipping()
		{
			// Refer to WC_Shortcode_Cart::calculate_shipping()
			try {
				if (
					$_SERVER['REQUEST_METHOD'] != 'POST' ||
					!wp_verify_nonce($_POST['nonce'], "ec_change_nonce") ||
					!array_key_exists('address', $_POST)
				) {
					throw new Exception('Invalid request');
				}

				WC()->shipping()->reset_shipping();

				$address = wc_clean(wp_unslash($_POST['address']));
				$address['country'] = $address['countryCode'];
				$address['phone'] = $address['phoneNumber'];
				$address['city'] = $address['suburb'];

				if ( $address['postcode'] && ! WC_Validation::is_postcode( $address['postcode'], $address['country'] ) ) {
					throw new Exception( __( 'Please enter a valid postcode / ZIP.', 'woocommerce' ) );
				} elseif ( $address['postcode'] ) {
					$address['postcode'] = wc_format_postcode( $address['postcode'], $address['country'] );
				}

				$customer = WC()->customer;

				if ( $address['country'] ) {
					$names = $this->name_split($address['name']);

					/**
					 * will set the WC customers name to the Clearpay name if:
					 * 1. the user isn't logged in or
					 * 2. the user is logged in but has no first name stored
					 */
					if (!is_user_logged_in() || !$customer->get_billing_first_name()) {
						$customer->set_billing_first_name($names->first);
						$customer->set_billing_last_name($names->last);
					}

					/**
					 * will set the WC customer address details to the Clearpay address if:
					 * 1. the user isn't logged in or
					 * 2. the user is logged in but has no postcode stored
					 *
					 * This allows Express to retrieve relevant shipping methods for that address
					 * using the built in methods further down the page
					 */
					if (!is_user_logged_in() || !$customer->get_billing_postcode()) {
						$customer->set_billing_location( $address['country'], $address['state'], $address['postcode'], $address['city'] );
						$customer->set_billing_address_1($address['address1']);
						$customer->set_billing_address_2($address['address2']);
						$customer->set_billing_phone($address['phone']);
					}

					$customer->set_shipping_location( $address['country'], $address['state'], $address['postcode'], $address['city'] );
					$customer->set_shipping_first_name($names->first);
					$customer->set_shipping_last_name($names->last);
					$customer->set_shipping_address_1($address['address1']);
					$customer->set_shipping_address_2($address['address2']);
					if (method_exists($customer, 'set_shipping_phone')) {
						$customer->set_shipping_phone($address['phone']);
					}
				} else {
					$customer->set_billing_address_to_base();
					$customer->set_shipping_address_to_base();
				}

				$customer->set_calculated_shipping( true );
				$customer->save();

				//do_action( 'woocommerce_calculated_shipping' );

				WC()->cart->calculate_totals();

				// Refer to wc_cart_totals_shipping_html() at /wp-content/plugins/woocommerce/includes/wc-cart-functions.php
				$packages = WC()->shipping()->get_packages();
				$methods = $packages[0]['rates'];

				if (empty($methods)) {
					throw new Exception('Shipping is unavailable for this address.', 4);
				}

				$response = array();
				$currency = get_woocommerce_currency();
				$totals = WC()->cart->get_totals();
				$cart_total = $totals['cart_contents_tax'] + (float)$totals['cart_contents_total'];
				$maximum = floatval($this->getOrderLimitMax());

				foreach ($methods as $method) {
					$shipping_cost = (float)$method->get_cost() + $method->get_shipping_tax();
					$total = $cart_total + $shipping_cost;

					if ($total <= $maximum) {
						$response[] = array(
							'id' => $method->get_id(),
							'name' => $method->get_label(),
							'description' => $method->get_label(),
							'shippingAmount' => array(
								'amount' => number_format($shipping_cost, 2, '.', ''),
								'currency' => $currency
							),
							'orderAmount' => array(
								'amount' => number_format($total, 2, '.', ''),
								'currency' => $currency
							),
						);
					}
				}

				if (empty($response)) {
					throw new Exception('All shipping options exceed Clearpay order limit.', 4);
				}
			} catch ( Exception $e ) {
				if ( ! empty( $e ) ) {
					$shipping_error_response = array(
						'error' => true,
						'message' => $e->getMessage(),
					);

					$response = array_merge($this->express_error_handler($e), $shipping_error_response);
				} else {
					$response = array(
						'error' => true,
						'message' => 'Unknown error',
					);
				}
			}

			wp_send_json($response);
		}

		/**
		 * function to handle express errors
		 *
		 * Error notes:
		 * 	- If log is true, it will log the error message in the clearpay logs
		 *  - Code 1 will write to log and redirect to the pay for order page for the specific order (this requires the $order to be passed in as 2nd param)
		 * 	- Code 2 will not write to logs and will redirect to the cart page
		 * 	- Code 3 will write to logs and will redirect to the cart page
		 * 	- All other errors will not write to log or redirect anywhere
		 *
		 * @since 3.1.0
		 *
		 * @uses get_checkout_payment_url()
		 * @uses wc_get_cart_url()
		 * @uses get_checkout_payment_url()
		 * @uses self::log()
		 * @used-by self::create_order_and_capture_endpoint()
		 * @used-by self::generate_express_token()
		 *
		 * @return array
		 */
		private function express_error_handler($e, $order = null) {
			$response = array();

			switch ($e->getCode()) {
				case 1:
					$error_code_conf = array(
						'log'						=> true,
						'redirect_url' 	=> $order->get_checkout_payment_url()
					);
					break;
				case 2:
					$error_code_conf = array(
						'redirect_url' => wc_get_cart_url()
					);
					break;
				case 3:
					$error_code_conf = array(
						'log' => true,
						'redirect_url' => wc_get_cart_url()
					);
					break;
				case 4:
					$error_code_conf = array(
						'log' => true
					);
					break;
				default:
					$error_code_conf = array();
			}

			$err_conf = (object)array_merge($this->express_base_error_config, $error_code_conf);

			if ($err_conf->log) {
				self::log('[EC] ' . $e->getMessage());
			}

			if ($err_conf->redirect_url) {
				$response['redirectUrl'] = $err_conf->redirect_url;
			}

			return $response;
		}

		/**
		 * Endpoint for creating a WC order from a V1 Clearpay order and capturing.
		 *
		 * Notes:	Hooked onto the "wp_ajax_clearpay_express_complete" Action.
		 * 				Hooked onto the "wp_ajax_nopriv_clearpay_express_complete" Action
		 *
		 * @since	3.1.0
		 * @uses 	self::create_wc_order_from_clearpay_order
		 * @uses 	wp_send_json
		 * @uses  wp_die
		 * @uses 	self::create_wc_order_from_clearpay_order
		 * @uses 	self::capture_payment_express_checkout
		 * @return	void
		 */
		public function create_order_and_capture_endpoint() {
			try {
				if (
					$_SERVER['REQUEST_METHOD'] != 'POST' ||
					!wp_verify_nonce($_POST['nonce'], "ec_complete_nonce") ||
					!array_key_exists('token', $_POST)
				) {
					wc_add_notice(__( 'Invalid request made', 'woo_clearpay' ), 'error' );
					throw new Exception('Invalid request', 2);
				}

				$clearpay_order = $this->get_checkout($_POST['token']);
				if (!$clearpay_order) {
					wc_add_notice( __( 'Something went wrong. Please try again.', 'woo_clearpay' ), 'error' );
					throw new Exception("Couldn't get Clearpay Order. Token requested: {$_POST['token']}", 3);
				} else {
					$this->integrityCheck($clearpay_order);
				}

				/**
				 * this must run before creating the order as that function will use
				 * the order email address if the user is not logged in
				 */
				if(!is_user_logged_in()) {
					WC()->customer->set_billing_email($clearpay_order->consumer->email);
				}

				$order = $this->create_wc_order_from_cart();
				if (!$order) {
					wc_add_notice( __( 'Something went wrong. Please try again.', 'woo_clearpay' ), 'error' );
					throw new Exception("Couldn't create Woocommmerce order. Clearpay token: {$_POST['token']}", 3);
				}
				$order_number = $order->get_order_number();
				self::log("[EC] Processing payment for WooCommerce Order #{$order_number}...");
				$order->add_order_note( __( "Clearpay order token: {$_POST['token']}", 'woo_clearpay' ) );

				$this->capture_payment_express_checkout($order, $clearpay_order);

				$response = array(
					'redirectUrl' => $this->get_return_url($order)
				);
			} catch (Exception $e) {
				$response = $this->express_error_handler($e, $order);
			}

			wp_send_json($response);
			wp_die();
		}

		/**
		 * Function for creating a WC order from the current cart
		 *
		 * @since	3.1.0
		 * @used-by create_order_and_capture_endpoint
		 * @return	object
		 */
		private function create_wc_order_from_cart() {
			try {
				/**
				 * WC()->cart->calculate_totals() should be run before WC()->get_checkout()
				 *
				 * This function is currently called from create_order_and_capture_endpoint()
				 * which runs WC()->cart->calculate_totals() in integrityCheck()
				 */
				$checkout = WC()->checkout();

				$order_id = $checkout->create_order(array());
				$order = wc_get_order($order_id);

				$customer = WC()->customer;

				$order->set_address($customer->get_billing(), 'billing');
				/*
				 * Building the array manually instead of using the simpler Customer::get_shipping
				 * method as it is not reliable in legacy WC (v3.2.6)
				 */
				$order->set_address(array(
					'first_name' => $customer->get_shipping_first_name(),
					'last_name' => $customer->get_shipping_last_name(),
					'address_1' => $customer->get_shipping_address_1(),
					'address_2' => $customer->get_shipping_address_2(),
					'city' => $customer->get_shipping_city(),
					'state' => $customer->get_shipping_state(),
					'postcode' => $customer->get_shipping_postcode(),
					'country' => $customer->get_shipping_country()
				), 'shipping');
				if (method_exists($order, 'set_shipping_phone') &&
						method_exists($customer, 'get_shipping_phone')
				) {
					$order->set_shipping_phone($customer->get_shipping_phone());
				}

				$order->set_payment_method($this);
			} catch(Exception $e) {
				wc_add_notice( __( 'Your order couldn\'t be created. Please try again.', 'woo_clearpay' ), 'error' );
				throw new Exception("Woocommerce couldn't create the order: {$e->getMessage()}", 3);
			}

			$order->save();
			WC()->cart->empty_cart();

			return $order;
		}

		/**
		 * splits a full name into an object with first and last name
		 *
		 * @since 3.1.0
		 *
		 * @param string $name
		 * @used-by self::fetch_express_shipping()
		 *
		 * @return object
		 */
		private function name_split($name) {
			$full_name = explode(' ', $name);
			$last_name = array_pop($full_name);
			if (empty($full_name)) {
				$first_name = $last_name; // if $clearpay_order->shipping->name contains only one word
				$last_name = '';
			} else {
				$first_name = implode(' ', $full_name);
			}

			return (object)array(
				'first' => $first_name,
				'last' 	=> $last_name
			);
		}

		/**
		 * Checks that all items in cart are virtual
		 *
		 * @since 3.1.0
		 *
		 * @return boolean
		 */
		private function cart_is_virtual() {
			foreach ( WC()->cart->get_cart() as $cart_item ) {
				if (!$cart_item['data']->is_virtual()) {
					return false;
				}
			}

			return true;
		}
		/**
		 * Function for creating capturing.
		 *
		 * @since	3.1.0
		 * @param	object	$clearpay_order
		 * @param	object	$order
		 * @used-by create_order_and_capture_endpoint
		 */
		private function capture_payment_express_checkout($order, $clearpay_order) {
			$clearpay_token = $clearpay_order->token;
			$order_number = $order->get_order_number();
			$amount = [
				'amount' => $order->get_total(),
				'currency' => $order->get_currency()
			];

			$this->check_pre_order($order);
			$payment = $this->immediate_payment_capture($clearpay_token, $order_number, $amount);

			if ($payment) {
				if ($payment->status == 'APPROVED') {
					self::log("[EC] Payment {$payment->status} for WooCommerce Order #{$order_number} (Clearpay Order #{$payment->id}).");
					$order->add_order_note(sprintf(__('Payment approved. Clearpay Order ID: %s.', 'woo_clearpay'), $payment->id));

					$order_currency = $order->get_currency();
					if ($order_currency != $this->settings['settlement-currency'] && isset($this->settings['cbt-limits'])) {
							$limits = json_decode($this->settings['cbt-limits'], true);
							if (is_array($limits) && isset($limits[$order_currency]['rate'])) {
								$order->add_order_note(sprintf(__('Approximate Clearpay exchange rate: %s.', 'woo_clearpay'), $limits[$order_currency]['rate']));
							}
					}

					$order->payment_complete($payment->id);
					update_post_meta($order->get_id(), '_transaction_url', $payment->merchantPortalOrderUrl);
				} else {
					$order->update_status('failed', sprintf(__('Payment declined. Clearpay Order ID: %s.', 'woo_clearpay'), $payment->id));
					wc_add_notice(sprintf(__('Your payment was declined for Clearpay Order #%s. Please try again. For more information, please submit a request via <a href="%s" style="text-decoration: underline;">Clearpay Help Center.</a>', 'woo_clearpay'), $payment->id, $this->assets['help_center_url']), 'error');
					throw new Exception("Payment DECLINED for WooCommerce Order #{$order_number} (Clearpay Order #{$payment->id}).", 1);
				}
			} else {
				$order->update_status('failed', __('Clearpay payment failed.', 'woo_clearpay'));
				wc_add_notice(__('Something went wrong. Please try again.', 'woo_clearpay'), 'error');
				throw new Exception("Updating status of WooCommerce Order #{$order_number} to \"Failed\", because payment failed. Clearpay Token: {$clearpay_token}", 1);
			}
		}

		/**
		 * Function for handling express change shipping method event.
		 *
		 * @since	3.1.0
		 * @uses 	wp_send_json
		 * @uses  wp_die
		 * @uses  wp_verify_nonce
		 *
		 * @return	void
		 */
		public function express_update_wc_shipping() {
			try {
				if (
					$_SERVER['REQUEST_METHOD'] != 'POST' ||
					!wp_verify_nonce($_POST['nonce'], 'ec_change_shipping_nonce') ||
					!array_key_exists('shipping', $_POST)
				) {
					throw new Exception('Invalid request');
				}

				WC()->session->set( 'chosen_shipping_methods', array($_POST['shipping']));
				wp_send_json(array(
					'status' => 'SUCCESS'
				));
				wp_die();
			} catch (Exception $e) {
				wp_send_json(array(
					'status' => 'ERROR',
					'error' => $e->getMessage()
				));
				wp_die();
			}
		}

		/** Transaction Integrity Check
		 *  @since	3.2.0
		 *  @param	object	$clearpay_order
		 *  @used-by self::create_order_and_capture_endpoint
		 */
		private function integrityCheck($clearpay_order) {
			$currency = get_woocommerce_currency();
			$latest_cart = array(
				'items' => array(),
				'discounts' => array(),
				'amount' => array()
			);

			$items = WC()->cart->get_cart();
			foreach ($items as $item) {
				if ($item['variation_id']) {
					$product = wc_get_product($item['variation_id']);
				} else {
					$product = wc_get_product($item['product_id']);
				}
				$latest_cart['items'][] = array(
					'name' => $product->get_name(),
					'sku' => $product->get_sku(),
					'quantity' => $item['quantity'],
					'price' => array(
						'amount' => number_format(wc_get_price_excluding_tax($product), 2, '.', ''),
						'currency' => $currency
					)
				);
			}
			if (json_encode($latest_cart['items']) !== json_encode($clearpay_order->items)) {
				wc_add_notice( __( 'Cart items were changed unexpectedly. Please try again.', 'woo_clearpay' ), 'error' );
				throw new Exception("Cart items were changed unexpectedly.", 3);
			}

			$coupons = WC()->cart->get_applied_coupons();
			foreach ($coupons as $coupon_code) {
				$latest_cart['discounts'][] = array(
					'displayName' => $coupon_code,
					'amount' => array(
						'amount' => number_format(WC()->cart->get_coupon_discount_amount($coupon_code), 2, '.', ''),
						'currency' => $currency
					)
				);
			}
			if (json_encode($latest_cart['discounts']) !== json_encode($clearpay_order->discounts)) {
				wc_add_notice( __( 'Cart coupons were changed unexpectedly. Please try again.', 'woo_clearpay' ), 'error' );
				throw new Exception("Cart coupons were changed unexpectedly.", 3);
			}

			$chosen_shipping_methods = WC()->session->get('chosen_shipping_methods');

			if(!isset($clearpay_order->shippingOptionIdentifier)) {
				if (!$this->cart_is_virtual()) {
					wc_add_notice( __( 'Product types were changed unexpectedly. Please try again.', 'woo_clearpay' ), 'error' );
					throw new Exception("Product types were changed unexpectedly.", 3);
				}
			} else if (empty($chosen_shipping_methods) || $chosen_shipping_methods[0] !== $clearpay_order->shippingOptionIdentifier) {
				wc_add_notice( __( 'Shipping method was changed unexpectedly. Please try again.', 'woo_clearpay' ), 'error' );
				throw new Exception("Shipping method was changed unexpectedly.", 3);
			}

			WC()->cart->calculate_totals();
			$totals = WC()->cart->get_totals();
			$latest_cart['amount'] = array(
				'amount' => number_format((float)$totals['total'], 2, '.', ''),
				'currency' => $currency
			);
			if (json_encode($latest_cart['amount']) !== json_encode($clearpay_order->amount)) {
				wc_add_notice( __( 'Cart totals were changed unexpectedly. Please try again.', 'woo_clearpay' ), 'error' );
				throw new Exception("Cart totals were changed unexpectedly.", 3);
			}
		}

		/**
		 * Retrieve the merchant's applicable payment limits.
		 *
		 * @since	3.2.0
		 * @uses	WC_Admin_Settings::add_error()
		 * @return	object|false					A configuration or error object, or false on connection issues.
		 */
		public function get_configuration() {
			try {
				$getConfigurationRequest = new GetConfiguration();
				$successful = $getConfigurationRequest->send();
				$body = $getConfigurationRequest->getResponse()->getParsedBody();
				if (!$successful) {
					self::log("API Error #{$body->httpStatusCode} \"{$body->errorCode}\": {$body->message} (Error ID: {$body->errorId})");
					if (is_admin()) {
						if ($body->httpStatusCode == 401) {
							$message = 'Your Clearpay API credentials are incorrect.';
						} else {
							$message = 'The Clearpay Gateway for WooCommerce plugin cannot communicate with the Clearpay API.';
						}
						WC_Admin_Settings::add_error(__("Clearpay API Error #{$body->httpStatusCode}: {$message} (Error ID: {$body->errorId})", 'woo_clearpay'));
					}
				}
				return $body;
			}
			catch (Exception $e) {
				self::log($e->getMessage());
				if (is_admin()) {
					WC_Admin_Settings::add_error(__('The Clearpay Gateway for WooCommerce plugin cannot communicate with the Clearpay API.', 'woo_clearpay'));
				}
				return false;
			}
		}

		/**
		 * Retrieve an incomplete individual checkout by token.
		 *
		 * @since	3.2.0
		 * @param	string	$token
		 * @return	object|false					A checkout object, or false on errors.
		 */
		private function get_checkout($token) {
			try {
				$getCheckoutRequest = new GetCheckout();
				$getCheckoutRequest->setCheckoutToken($token);
				$successful = $getCheckoutRequest->send();
				$body = $getCheckoutRequest->getResponse()->getParsedBody();
				if ($successful) {
					return $body;
				} else {
					self::log("API Error #{$body->httpStatusCode} \"{$body->errorCode}\": {$body->message} (Error ID: {$body->errorId})");
				}
			}
			catch (Exception $e) {
				self::log($e->getMessage());
			}
			return false;
		}

		/**
		 * Perform a payment capture for the full value of the payment plan.
		 *
		 * @since	3.2.0
		 * @param	string	$token
		 * @param	string	$merchantReference
		 * @param	array	$amount
		 * @return	object|false					A payment object, or false on connection issues.
		 */
		private function immediate_payment_capture($token, $merchantReference, $amount = null) {
			try {
				$paymentCaptureRequest = new ImmediatePaymentCapture([
					'token' => $token,
					'merchantReference' => $merchantReference
				]);
				if ($amount) {
					$paymentCaptureRequest->setAmount($amount);
				}
				$successful = $paymentCaptureRequest->send();
				$body = $paymentCaptureRequest->getResponse()->getParsedBody();
				if ($successful) {
					return $body;
				} else {
					self::log("API Error #{$body->httpStatusCode} \"{$body->errorCode}\": {$body->message} (Error ID: {$body->errorId})");
				}
			}
			catch (Exception $e) {
				self::log($e->getMessage());
			}
			return false;
		}

		public function frontend_is_ready(){
			return $this->payment_is_enabled()
				&& $this->api_is_ok()
				&& $this->currency_is_supported();
		}

		public function filter_script_loader_tag($tag, $handle) {
			if ($handle == 'clearpay_js_lib') {
				$limit_min = $this->getOrderLimitMin();
				$limit_max = $this->getOrderLimitMax();
				if ($limit_min < 1) { $limit_min = 1; } // Interim solution for forced order minimum in JS Lib
				$extra = ' data-min="'.$limit_min.'" data-max="'.$limit_max.'" ';
				$tag = str_replace(' src', $extra . ' src', $tag);
			}
			return $tag;
		}

		private function render_placement($context, $product = null) {
			$valid_contexts = ['category-pages', 'product-pages', 'product-variant', 'cart-page'];

			if ($context == 'cart-page') {
				$price = WC()->cart->total;
			} else {
				if (is_null($product)) {
					$product = $this->get_product_from_the_post();
				}
				if (!($product instanceof WC_Product)) {
					return;
				}
				$price = wc_get_price_to_display($product);
				if (!$price && $product->is_type('variable')) {
					$min_price = $product->get_variation_price('min', true);

					if ($min_price && $min_price > 0) {
						$price = $min_price;
					}
				}
			}

			if (!in_array($context, $valid_contexts)
				|| !$this->frontend_is_ready()
				|| !$price
			) {
				return;
			}

			$attributes = [];
			$limit_min = $this->getOrderLimitMin();
			$limit_max = $this->getOrderLimitMax();
			$input = $this->settings[$context.'-placement-attributes'];

			if (preg_match_all('/data(-[a-z]+)+="[^"]+"/', $input, $raw_attributes)) {
				foreach ($raw_attributes[0] as $pair) {
					if (preg_match('/data(-[a-z]+)+(?==")/', $pair, $key)
						&& preg_match('/(?<==")[^"]+(?=")/', $pair, $value)
					) {
						$attributes[$key[0]] = $value[0];
					}
				}
			}
			$attributes['data-currency'] = get_woocommerce_currency();
			$attributes['data-locale'] = $this->get_js_locale();
			$attributes['data-amount'] = number_format($price, 2, '.', '');
			if ($context == 'category-pages') {
				// Because it is already wrapped in a hyperlink
				$attributes['data-modal-link-style'] = 'none';
			}

			$child_prices = array();
			if (!is_null($product) && $product->has_child()){
				$min_price = $max_price = null;
				if ($product->is_type('variable')) {
					$min_price = $product->get_variation_price('min', true);
					$max_price = $product->get_variation_price('max', true);
					$prices	= $product->get_variation_prices(true);
					foreach($prices['price'] as $val) {
						$child_prices[] = (float)$val;
					}
				}
				else {
					$children = array_filter(
						array_map('wc_get_product', $product->get_children()),
						'wc_products_array_filter_visible'
					);
					foreach ($children as $child) {
						if ('' !== $child->get_price()) {
							$child_prices[] = wc_get_price_to_display($child);
						}
					}
					if (!empty($child_prices)) {
						$min_price = min($child_prices);
						$max_price = max($child_prices);
					}
				}
				if ($min_price != $max_price) {
					unset($attributes['data-amount']);
					$attributes['data-amount-range'] = json_encode($child_prices);
				}
			}

			if (
				(
					isset($attributes['data-amount'])
					&& ($price < $limit_min || $price > $limit_max)
				)
				||
				(
					isset($attributes['data-amount-range'])
					&& empty($this->prices_within_limits($child_prices))
				)
			) {
				if (isset($this->settings['show-outside-limit-on-product-page'])
					&& $this->settings['show-outside-limit-on-product-page'] != 'yes'
					&& in_array($context, ['product-pages', 'product-variant'])
				) {
					return;
				}
			}

			if ($context == 'cart-page') {
				if (!$this->cart_products_are_supported()) {
					$attributes['data-cart-is-eligible'] = 'false';
				}
			} elseif (!$this->is_product_supported($product, true)) {
				$attributes['data-is-eligible'] = 'false';
			}

			if (get_woocommerce_currency() != $this->settings['settlement-currency']) {
				$attributes['data-cbt-enabled'] = 'true';
			}

			echo '<afterpay-placement';
			foreach ($attributes as $key => $value) {
				echo ' ' . esc_html($key) . '="' . esc_attr($value) . '"';
			}
			echo '></afterpay-placement>';
			wp_enqueue_script('clearpay_js_lib');
		}

		/**
		 * Checks if any prices in an array are within the limits
		 *
		 * @since 3.4.3
		 * @param Array	$prices
		 * @return Array
		 */
		private function prices_within_limits($prices) {
			return array_filter($prices, function($price) {
				return $price >= $this->getOrderLimitMin() && $price <= $this->getOrderLimitMax();
			});
		}

		/**
		 * Checks whether an order (if provided) or the cart contains a pre-order.
		 * Updates user agent accordingly.
		 *
		 * @since	3.4.0
		 * @param	WC_Order	$order
		 * @return	void
		 */
		private function check_pre_order($order = null) {
			if (!is_null($order)) {
				$isPreOrder = class_exists('WC_Pre_Orders_Order') && WC_Pre_Orders_Order::order_contains_pre_order($order);
			} else {
				$isPreOrder = class_exists('WC_Pre_Orders_Cart') && WC_Pre_Orders_Cart::cart_contains_pre_order();
			}
			HTTP::addPlatformDetail('PreOrder', $isPreOrder ? '1' : '0');
		}

		/**
		 * Collect shipping data when an order status is changed.
		 *
		 * Note: Hooked onto the "woocommerce_order_status_changed" Action.
		 *
		 * @since 3.4.0
		 * @param int    $order_id Order id.
		 * @param string $previous_status the old WooCommerce order status.
		 * @param string $next_status the new WooCommerce order status.
		 */
		public function collect_shipping_data($order_id, $previous_status, $next_status) {
			$order = wc_get_order($order_id);
			if ($order->get_payment_method() == $this->id
				&& $order->needs_shipping_address()
				&& ('completed' == $next_status || ('completed' == $previous_status && 'processing' == $next_status))
				&& $transaction_id = $order->get_transaction_id()
			) {
				$order_number = $order->get_order_number();
				self::log("Updating courier timestamp for WooCommerce Order #{$order_number} (Clearpay Order #{$transaction_id})...");
				try {
					$this->check_pre_order($order);
					$shippingRequest = new UpdateShippingCourier();
					if ('completed' == $next_status) {
						$shippingRequest->setShippedAt(date('c')); // ISO 8601 format
					} else {
						// Erase courier only when status changes from 'completed' back to 'processing'
						$shippingRequest->setRequestBody('{}');
					}
					$shippingRequest->setOrderId($transaction_id);
					$successful = $shippingRequest->send();
					$body = $shippingRequest->getResponse()->getParsedBody();
					if (!$successful) {
						self::log("API ERROR #{$body->httpStatusCode} \"{$body->errorCode}\": {$body->message} (Error ID: {$body->errorId})");
					} else {
						self::log("Update successful.");
					}
				}
				catch (Exception $e) {
					self::log($e->getMessage());
				}
			}
		}

		private function getOrderLimit($extremum) {
			$limit = 0;
			$currency = get_woocommerce_currency();
			if ($currency == $this->settings['settlement-currency']) {
				$limit = $this->settings['pay-over-time-limit-' . $extremum];
			} elseif (isset($this->settings['cbt-limits'])) {
				$limits = json_decode($this->settings['cbt-limits'], true);
				if (is_array($limits) && array_key_exists($currency, $limits)) {
					$limit = $limits[$currency][$extremum];
				}
			}
			return $limit;
		}

		public function getOrderLimitMin() {
			return $this->getOrderLimit('min');
		}

		public function getOrderLimitMax() {
			return $this->getOrderLimit('max');
		}
	}
}