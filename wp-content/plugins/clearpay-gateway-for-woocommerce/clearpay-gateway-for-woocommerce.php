<?php
/**
 * Plugin Name: Clearpay Gateway for WooCommerce
 * Description: Provide Clearpay as a payment option for WooCommerce orders.
 * Author: Clearpay
 * Author URI: https://www.clearpay.co.uk/
 * Version: 3.5.3
 * Text Domain: woo_clearpay
 * Domain Path: /languages/
 * WC requires at least: 3.2.6
 * WC tested up to: 7.3.0
 *
 * Copyright: (c) 2021 Clearpay
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;

if (!defined('ABSPATH')) {
	exit;
}

define( 'WC_GATEWAY_CLEARPAY_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
define( 'WC_GATEWAY_CLEARPAY_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

if (!class_exists('Clearpay_Plugin')) {
	class Clearpay_Plugin
	{
		/**
		 * @var		Clearpay_Plugin		$instance	A static reference to an instance of this class.
		 */
		protected static $instance;

		/**
		 * @var		int					$version	A reference to the plugin version, which will match
		 *											the value in the comments above.
		 */
		public static $version = '3.5.3';

		/**
		 * Import required classes.
		 *
		 * @since	2.0.0
		 * @used-by	self::init()
		 * @used-by	self::deactivate_plugin()
		 */
		public static function load_classes()
		{
			require_once dirname(__FILE__) . '/vendor/autoload.php';
			require_once dirname(__FILE__) . '/class/Cron/Clearpay_Plugin_Cron.php';
			if (class_exists('WC_Payment_Gateway')) {
				require_once dirname(__FILE__) . '/class/WC_Gateway_Clearpay.php';
			}
		}

		/**
		 * Class constructor. Called when an object of this class is instantiated.
		 *
		 * @since	2.0.0
		 * @see		Clearpay_Plugin::init()						For where this class is instantiated.
		 * @see		WC_Settings_API::process_admin_options()
		 * @uses	self::generate_product_hooks()
		 * @uses	self::generate_category_hooks()
		 * @uses	WC_Gateway_Clearpay::getInstance()
		 */
		public function __construct()
		{
			$gateway = WC_Gateway_Clearpay::getInstance();

			/**
			 * Actions.
			 */
			add_action( 'admin_notices', array($gateway, 'render_admin_notices'), 10, 0 );
			add_action( 'admin_enqueue_scripts', array($this, 'init_admin_assets'), 10, 1 );
			add_action( 'clearpay_do_cron_jobs', array('Clearpay_Plugin_Cron', 'fire_jobs'), 10, 0 );
			add_action( "woocommerce_update_options_payment_gateways_{$gateway->id}", array($gateway, 'process_admin_options'), 10, 0 ); # process_admin_options() is defined in WC_Gateway_Clearpay's grandparent class: WC_Settings_API.
			add_action( "woocommerce_update_options_payment_gateways_{$gateway->id}", array($gateway, 'refresh_cached_configuration'), 11, 0 ); # Refresh cached configuration after our gateway settings are saved, but before the cron jobs run.
			add_action( "woocommerce_update_options_payment_gateways_{$gateway->id}", array('Clearpay_Plugin_Cron', 'fire_jobs'), 12, 0 ); # Manually fire the cron jobs after our gateway settings are saved, and after cached configuration is refreshed.
			add_action( 'woocommerce_cart_totals_after_order_total', array($gateway, 'render_cart_page_elements'), 10, 0 );
			add_action( 'woocommerce_order_status_changed', array($gateway, 'collect_shipping_data'), 10, 3 );
			add_action( 'wp_enqueue_scripts', array($this, 'init_website_assets'), 10, 0 );
			add_action( 'wp_ajax_clearpay_action', array($gateway, 'reset_settings_api_form_fields'),10,0 );
			add_action( 'wp_ajax_clearpay_express_start', array($gateway, 'generate_express_token'),10,0 );
			add_action( 'wp_ajax_nopriv_clearpay_express_start', array($gateway, 'generate_express_token'),10,0 );
			add_action( 'wp_ajax_clearpay_express_change', array($gateway, 'fetch_express_shipping'),10,0 );
			add_action( 'wp_ajax_nopriv_clearpay_express_change', array($gateway, 'fetch_express_shipping'),10,0 );
			add_action( 'wp_ajax_clearpay_express_shipping_change', array($gateway, 'express_update_wc_shipping'),10,0 );
			add_action( 'wp_ajax_nopriv_clearpay_express_shipping_change', array($gateway, 'express_update_wc_shipping'),10,0 );
			add_action( 'wp_ajax_clearpay_express_complete', array($gateway, 'create_order_and_capture_endpoint'));
			add_action( 'wp_ajax_nopriv_clearpay_express_complete', array($gateway, 'create_order_and_capture_endpoint') );
			add_action( 'woocommerce_api_wc_gateway_clearpay', array($gateway, 'capture_payment') );
			/**
			 * Filters.
			 */
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array($this, 'filter_action_links'), 10, 1 );
			add_filter( 'cron_schedules', array('Clearpay_Plugin_Cron', 'edit_cron_schedules'), 10, 1 );
			add_filter( 'woocommerce_payment_gateways', array($gateway, 'add_clearpay_gateway'), 10, 1 );
			add_filter( 'woocommerce_get_price_html', array($gateway, 'filter_woocommerce_get_price_html'), 10, 2 );
			add_filter( 'woocommerce_gateway_icon', array($gateway, 'filter_woocommerce_gateway_icon'), 10, 2 );
			add_filter('script_loader_tag', array($gateway, 'filter_script_loader_tag'), 10, 2);
			load_plugin_textdomain( 'woo_clearpay', false, plugin_basename( __DIR__ ) . '/languages' );
			/**
			 * Shortcodes.
			 */

			add_shortcode( 'clearpay_product_logo', array($this, 'shortcode_clearpay_product_logo') );
			add_shortcode( 'clearpay_paragraph', array($gateway, 'shortcode_clearpay_paragraph') );


			$this->generate_product_hooks($gateway);
			$this->generate_category_hooks($gateway);
		}

		/**
		 * Note: Perform dynamic Product Page Assets hooks processing
		 *
		 * @since	2.1.0
		 * @param	WC_Gateway_Clearpay		$gateway
		 * @uses	WC_Gateway_Clearpay::getSettings
		 * @return	bool
		 */
		public function generate_product_hooks($gateway)
		{
			$settings = $gateway->getSettings();
			if (!empty($settings['product-pages-hook'])) {
				$product_pages_hook = $settings['product-pages-hook'];

				if (!empty($settings['product-pages-priority'])) {
					$product_pages_priority = (int) $settings['product-pages-priority'];
				}
				else {
					$product_pages_priority = 10;
				}

				//add the adjusted Product Single Page action
				add_action( $product_pages_hook, array($gateway, 'print_info_for_product_detail_page'), $product_pages_priority, 0 );
			}

			return true;
		}

		/**
		 * Note: Perform dynamic Category Page Assets hooks processing
		 *
		 * @since	2.1.0
		 * @param	WC_Gateway_Clearpay		$gateway
		 * @uses	WC_Gateway_Clearpay::getSettings()
		 * @return	bool
		 */
		public function generate_category_hooks($gateway)
		{
			$settings = $gateway->getSettings();
			if (!empty($settings['category-pages-hook'])) {
				$category_pages_hook = $settings['category-pages-hook'];

				if (!empty($settings['category-pages-priority'])) {
					$category_pages_priority = (int) $settings['category-pages-priority'];
				}
				else {
					$category_pages_priority = 99;
				}

				//add the adjusted Product Single Page action
				add_action( $category_pages_hook, array($gateway, 'print_info_for_listed_products'), $category_pages_priority, 0 );
			}

			return true;
		}

		/**
		 * Note: Hooked onto the "plugin_action_links_woocommerce-gateway-clearpay/woocommerce-clearpay.php" Action.
		 *
		 * @since	2.0.0
		 * @see		self::__construct()		For hook attachment.
		 * @param	array	$links
		 * @return	array
		 */
		public function filter_action_links($links)
		{
			$additional_links = array(
				'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=clearpay' ) . '">' . __( 'Settings', 'woo_clearpay' ) . '</a>',
			);

			return array_merge($additional_links, $links);
		}

		/**
		 * Note: Hooked onto the "wp_enqueue_scripts" Action to avoid the Wordpress Notice warnings
		 *
		 * @since	2.0.0
		 * @see		self::__construct()		For hook attachment.
		 */
		public function init_website_assets()
		{
			$instance = WC_Gateway_Clearpay::getInstance();

			if ($instance->settings['enabled'] == 'yes') {
				$plugin_version = self::$version;

				/**
				 * Register CSS.
				 */
			 	wp_register_style( 'clearpay_css', plugins_url( 'css/clearpay.css', __FILE__ ), array(), $plugin_version );

				/**
				 * Register & Enqueue JS.
				 * Note: Admin assets are registered in self::init_admin_assets()
				 */
				if ( $instance->get_api_env() == 'production' ) {
					wp_register_script( 'clearpay_js_lib', 'https://js.afterpay.com/afterpay-1.x.js', array(), $plugin_version, true );
					wp_register_script( 'clearpay_express_lib', "https://portal.clearpay.co.uk/afterpay.js?merchant_key=woocommerce&plugin_version={$plugin_version}", array(), false, true );
				} else {
					wp_register_script( 'clearpay_js_lib', 'https://js.sandbox.afterpay.com/afterpay-1.x.js', array(), $plugin_version, true );
					wp_register_script( 'clearpay_express_lib', "https://portal.sandbox.clearpay.co.uk/afterpay.js?merchant_key=woocommerce&plugin_version={$plugin_version}", array(), false, true );
				}

				if (is_checkout()) {
					wp_enqueue_style( 'clearpay_css' );
					wp_enqueue_script('clearpay_express_lib');
					wp_enqueue_script(
						'clearpay_checkout_page',
						plugins_url('build/clearpay-page-checkout.js', __FILE__),
						['jquery', 'clearpay_express_lib'],
						$plugin_version,
						true
					);
				}

				if(is_cart()) {
					wp_register_script( 'clearpay_express', plugins_url( 'build/clearpay-express.js', __FILE__ ), array('jquery', 'clearpay_express_lib'), $plugin_version, true );
					wp_localize_script( 'clearpay_express', 'clearpay_express_js_config', array(
						'ajaxurl' => admin_url('admin-ajax.php'),
						'ec_start_nonce' => wp_create_nonce("ec_start_nonce"),
						'ec_change_nonce' => wp_create_nonce("ec_change_nonce"),
						'ec_change_shipping_nonce' => wp_create_nonce("ec_change_shipping_nonce"),
						'ec_complete_nonce' => wp_create_nonce("ec_complete_nonce"),
						'country_code' => $instance->get_country_code()
					) );
				}
			}
		}

		/**
		 * Note: Hooked onto the "admin_enqueue_scripts" Action.
		 *
		 * @since	2.0.0
		 * @see		self::__construct()		For hook attachment.
		 */
		public function init_admin_assets($hook)
		{
			if ( $hook == 'woocommerce_page_wc-settings' &&
				isset($_GET['section']) && $_GET['section'] == 'clearpay'
			) {
				wp_enqueue_script( 'clearpay_admin_js', plugins_url( 'build/clearpay-admin.js', __FILE__ ), array(), false, true );
				wp_localize_script( 'clearpay_admin_js', 'clearpay_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' )) );

				$instance = WC_Gateway_Clearpay::getInstance();
				$limit_min = $instance->getOrderLimitMin();
				$limit_max = $instance->getOrderLimitMax();
				if (!is_numeric($limit_min)) { $limit_min = 1; }
				if (!is_numeric($limit_max)) { $limit_max = 1000; }
				$multicurrency_is_available = $instance->feature_is_available('multicurrency');
				wp_localize_script( 'clearpay_admin_js', 'clearpay_config', array(
					'currency' => get_woocommerce_currency(),
					'locale' => $instance->get_js_locale(),
					'min' => $limit_min,
					'max' => $limit_max,
					'multicurrency_is_available' => $multicurrency_is_available,
				) );
			}
		}

		/**
		 * Provide a shortcode for rendering the standard Clearpay logo on individual product pages.
		 *
		 * E.g.:
		 * 	- [clearpay_product_logo] OR [clearpay_product_logo theme="colour"]
		 * 	- [clearpay_product_logo theme="black"]
		 * 	- [clearpay_product_logo theme="white"]
		 *
		 * @since	2.0.0
		 * @see		self::__construct()		For shortcode definition.
		 * @param	array	$atts			Array of shortcode attributes.
		 * @uses	shortcode_atts()
		 * @return	string
		 */
		public function shortcode_clearpay_product_logo($atts) {
			$atts = shortcode_atts( array(
				'theme' => 'colour'
			), $atts );

			if (!in_array($atts['theme'], array('colour', 'black', 'white'))) {
				$atts['theme'] = 'colour';
			}

			$gateway_instance = WC_Gateway_Clearpay::getInstance();
			$static_url = $gateway_instance->get_static_url();
			$image_path = "integration/product-page/logo-clearpay-{$atts['theme']}";
			$logo = $gateway_instance->generate_source_sets($static_url, $image_path, 'png');

			ob_start();

			?><img
				style="vertical-align:middle;"
				src="<?php echo esc_url($logo->x1); ?>"
				srcset="
					<?php echo esc_url($logo->x1); ?> 1x,
					<?php echo esc_url($logo->x2); ?> 2x,
					<?php echo esc_url($logo->x3); ?> 3x"
				width="110"
				height="21"
				alt="Clearpay" /><?php

			return ob_get_clean();
		}

		/**
		 * Initialise the class and return an instance.
		 *
		 * Note:	Hooked onto the "plugins_loaded" Action.
		 *
		 * @since	2.0.0
		 * @uses	self::load_classes()
		 * @return	Clearpay_Plugin
		 * @used-by	self::activate_plugin()
		 */
		public static function init()
		{
			self::load_classes();
			if (!class_exists('WC_Gateway_Clearpay')) {
				return false;
			}
			if (is_null(self::$instance)) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Callback for when this plugin is activated. Schedules the cron jobs.
		 *
		 * @since	2.0.0
		 * @uses	set_transient()							Available in WordPress core since 2.8.0
		 * @uses	self::init()
		 * @uses	Clearpay_Plugin_Cron::create_jobs()
		 */
		public static function activate_plugin()
		{
			if (!current_user_can( 'activate_plugins' )) {
				return;
			}

			if (function_exists('set_transient')) {
				set_transient( 'clearpay-admin-activation-notice', true, 300 );
			}

			self::init(); # Can't just use load_classes() here because the cron schedule is setup in the filter, which attaches inside the class constructor. Have to do a full init.
			Clearpay_Plugin_Cron::create_jobs();
		}

		/**
		 * Callback for when this plugin is deactivated. Deletes the scheduled cron jobs.
		 *
		 * @since	2.0.0
		 * @uses	self::load_classes()
		 * @uses	Clearpay_Plugin_Cron::delete_jobs()
		 */
		public static function deactivate_plugin()
		{
			if (!current_user_can( 'activate_plugins' )) {
				return;
			}

			self::load_classes();
			Clearpay_Plugin_Cron::delete_jobs();
		}

		/**
		 * Callback for when the plugin is uninstalled. Remove all of its data.
		 *
		 * Note:	This function is called when this plugin is uninstalled.
		 *
		 * @since	2.0.0
		 */
		public static function uninstall_plugin()
		{
			if (!current_user_can( 'activate_plugins' )) {
				return;
			}
		}

		public static function add_woocommerce_blocks_support(){
			if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
				require_once dirname(__FILE__) . '/class/WC_Gateway_Clearpay_Blocks_Support.php';
				add_action(
					'woocommerce_blocks_payment_method_type_registration',
					function( PaymentMethodRegistry $payment_method_registry ) {
						$payment_method_registry->register( new WC_Gateway_Clearpay_Blocks_Support );
					}
				);
			}
		}
	}

	register_activation_hook( __FILE__, array('Clearpay_Plugin', 'activate_plugin') );
	register_deactivation_hook( __FILE__, array('Clearpay_Plugin', 'deactivate_plugin') );
	register_uninstall_hook( __FILE__, array('Clearpay_Plugin', 'uninstall_plugin') );

	add_action( 'plugins_loaded', array('Clearpay_Plugin', 'init'), 10, 0 );
	add_action( 'woocommerce_blocks_loaded', array('Clearpay_Plugin', 'add_woocommerce_blocks_support') );
}
