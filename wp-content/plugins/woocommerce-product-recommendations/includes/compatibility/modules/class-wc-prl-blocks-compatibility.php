<?php
/**
 * WC_PRL_Blocks_Compatibility class
 *
 * @package  WooCommerce Product Recommendations
 * @since    2.2.1
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks for Blocks compatibility.
 *
 * @class    WC_PRL_Blocks_Compatibility
 * @version  2.2.1
 */
class WC_PRL_Blocks_Compatibility {

	/**
	 * Initialize.
	 */
	public static function init() {

		// Drop support for Cart/Checkout locations when the block-based Checkout is in use.
		add_filter( 'woocommerce_prl_locations', array( __CLASS__, 'filter_locations' ) );
	}

	/**
	 * Filter out cart and checkout locations if block-based.
	 *
	 * @param  array  $locations  Available locations.
	 * @return array
	 */
	public static function filter_locations( $locations ) {

		if ( WC_PRL_Core_Compatibility::is_block_based_cart() ) {
			$location_key = array_search( 'WC_PRL_Location_Cart_Page', $locations, true );
			if ( false !== $location_key ) {
				unset( $locations[ $location_key ] );
			}
		}

		if ( WC_PRL_Core_Compatibility::is_block_based_checkout() ) {
			$location_key = array_search( 'WC_PRL_Location_Checkout', $locations, true );
			if ( false !== $location_key ) {
				unset( $locations[ $location_key ] );
			}
		}

		return $locations;
	}
}

WC_PRL_Blocks_Compatibility::init();
