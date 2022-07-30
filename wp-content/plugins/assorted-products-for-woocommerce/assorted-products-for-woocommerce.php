<?php
/**
* Plugin Name: Assorted Products For WooCommerce
* Plugin URI: https://www.codeincept.com/
* Description: Assorted Products For WooCommerce Plugin helps your customers to sort and make bundles.
* Version: 1.0.8
* Author: CodeIncept
* Author URI: https://www.codeincept.com/
* Developer: CodeIncept
* Developer URI: https://www.codeincept.com/
* Text Domain: wc-abp
*
* Woo: 4911617:164747fafbb4166d9553e56f6ebe6459
* WC requires at least: 3.5
* WC tested up to: 5.9.0
* 
* Copyright: © 2009-2015 WooCommerce.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
**/
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
} //!defined('ABSPATH')
if ( !defined('WC_ABP_DIR') ) {
	define('WC_ABP_DIR', plugin_dir_path(__FILE__));
}
if ( !defined('WC_ABP_URL') ) {
	define('WC_ABP_URL', plugin_dir_url(__FILE__));
}
if ( !class_exists('ABP_Assorted_Bundle_Products') ) {
	class ABP_Assorted_Bundle_Products {
		public function __construct() {
			/**
			 * Check if WooCommerce is installed and active.
			 **/
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			$active_plugins = get_option( 'active_plugins');
			if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', $active_plugins)) || is_plugin_active_for_network( 'woocommerce/woocommerce.php' ) ) {
				//register product type
				add_action( 'plugins_loaded', array($this, 'abp_register_assorted_product_product_type'));
				$this->abp_init();
				if ( in_array( 'woocommerce-subscriptions/woocommerce-subscriptions.php', apply_filters( 'active_plugins', $active_plugins)) || is_plugin_active_for_network( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
					require_once WC_ABP_DIR . '/includes/class-abp-subscription.php';
				}
			} else {
				add_action('admin_notices', array($this, 'abp_admin_notices'));
			}
		}
		public function abp_init() {
			/**        
			* Load language.     
			*/
			if ( function_exists( 'load_plugin_textdomain' ) ) {
				load_plugin_textdomain('wc-abp', false, dirname(plugin_basename(__FILE__)) . '/languages/');
			}
			require_once WC_ABP_DIR . '/includes/class-abp-controller.php';
			if ( is_admin() ) {
				require_once WC_ABP_DIR . '/includes/class-abp-admin-settings.php';
				require_once WC_ABP_DIR . '/includes/class-abp-settings.php';
			} else {
				require_once WC_ABP_DIR . '/includes/class-abp-frontend.php';
			}
			require_once WC_ABP_DIR . '/includes/class-abp-frontend-cart.php';
		}
		public function abp_register_assorted_product_product_type() {
			require WC_ABP_DIR . '/includes/class-abp-product-type.php';
		}
		public function abp_admin_notices() {
			global $pagenow;
			if ( 'plugins.php' === $pagenow ) {
				$class = 'notice notice-error is-dismissible';
				$message = esc_html__('WooCommerce Assorted Products needs WooCommerce to be installed and active.', 'wc-abp');
				printf('<div class="%1$s"><p>%2$s</p></div>', esc_html($class), esc_html($message));
			}
		}
	}
	new ABP_Assorted_Bundle_Products();
}
