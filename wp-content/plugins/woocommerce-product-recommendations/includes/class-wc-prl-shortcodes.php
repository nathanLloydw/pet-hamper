<?php
/**
 * WC_PRL_Shortcodes class
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.4.8
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * WooCommerce Product Recommendations Shortcodes class.
 *
 * @version 1.4.8
 */
class WC_PRL_Shortcodes {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
			'woocommerce_prl_recommendations' => array( __CLASS__, 'display_recommendations' )
		);

		foreach ( $shortcodes as $shortcode => $function ) {
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}
	}

	/**
	 * Display recommendations shortcode.
	 *
	 * @param  array  $args
	 * @return string
	 */
	public static function display_recommendations( $args ) {

		$args = wp_parse_args( $args, array(
			'id' => false
		) );

		if ( empty( $args[ 'id' ] ) ) {
			return;
		}

		// Fill-in mandatory keys.
		$args[ 'is_shortcode' ] = true;
		$hook                   = $args[ 'id' ];
		unset( $args[ 'id ' ] );

		ob_start();
		WC_PRL()->templates->process_hook( $hook, false, $args );
		return ob_get_clean();
	}
}
