<?php
/**
 * WC_PRL_Flatsome_Compatibility class
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.4.11
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks for Flatsome theme compatibility.
 *
 * @class    WC_PRL_Flatsome_Compatibility
 * @version  3.0.2
 */
class WC_PRL_Flatsome_Compatibility {

	/**
	 * Initialize.
	 */
	public static function init() {
		add_action( 'after_setup_theme', array( __CLASS__, 'add_hooks' ) );
	}

	/**
	 * Add hooks if the active parent theme is Flatsome.
	 */
	public static function add_hooks() {
		add_filter( 'woocommerce_cross_sells_columns', array( __CLASS__, 'reset_cart_product_columns' ) );
	}

	/**
	 * Reset the cart columns number to fetch from PRL settings.
	 *
	 * @param  int  $columns
	 * @return int
	 */
	public static function reset_cart_product_columns( $columns ) {
		$active_deployment = WC_PRL()->templates->get_current_deployment();
		if ( ! is_null( $active_deployment ) ) {
			$columns = $active_deployment->get_columns();
		}

		return $columns;
	}
}

WC_PRL_Flatsome_Compatibility::init();
