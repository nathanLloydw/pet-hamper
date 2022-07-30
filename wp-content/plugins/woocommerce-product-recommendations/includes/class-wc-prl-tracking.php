<?php
/**
 * WC_PRL_Tracking class
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracking class.
 *
 * @class    WC_PRL_Tracking
 * @version  2.0.0
 */
class WC_PRL_Tracking {

	/**
	 * Init.
	 */
	public static function init() {
		add_filter( 'woocommerce_add_cart_item', array( __CLASS__, 'add_long_term_conversion' ) );
		add_filter( 'post_class', array( __CLASS__, 'add_taxonomy_classes' ) );
	}

	/**
	 * Handles the long-term conversion after inserting into cart from a deployment.
	 *
	 * @param  array  $cart_item_data
	 * @return array
	 */
	public static function add_long_term_conversion( $cart_item_data ) {

		if ( ! wc_prl_tracking_enabled() ) {
			return $cart_item_data;
		}

		// Get product id.
		$product_id          = $cart_item_data[ 'product_id' ];

		// Search in local cookie.
		$cookie              = 'wc_prl_deployments_clicked';
		$product_ids_clicked = isset( $_COOKIE[ $cookie ] ) && ! empty( $_COOKIE[ $cookie ] ) ? explode( ',', sanitize_text_field( $_COOKIE[ $cookie ] ) ) : array();

		if ( empty( $product_ids_clicked ) ) {
			return $cart_item_data;
		}

		foreach ( $product_ids_clicked as $event ) {
			$attrs = explode( '_', $event );

			if ( ! isset( $attrs[ 1 ] ) || absint( $attrs[ 1 ] ) !== $product_id ) {
				continue;
			}

			$cart_item_data[ '_prl_conversion' ]      = absint( $attrs[ 0 ] ); // Deployment ID.
			$cart_item_data[ '_prl_conversion_time' ] = time();

			if ( isset( $attrs[ 2 ] ) ) {
				$cart_item_data[ '_prl_conversion_source_hash' ] = $attrs[ 2 ];
			}

			break;
		}

		return $cart_item_data;
	}

	/**
	 * Add taxonomy specific classes on the product dom container.
	 *
	 * @since  1.2.0
	 *
	 * @param  array  $classes
	 * @return void
	 */
	public static function add_taxonomy_classes( $classes ) {

		if ( is_admin() ) {
			return $classes;
		}

		global $product;

		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return $classes;
		}

		$categories = $product->get_category_ids();
		if ( ! empty( $categories ) ) {
			$classes[] = 'wc-prl-cat-' . implode( '-', $categories );
		}

		$tags = $product->get_tag_ids();
		if ( ! empty( $tags ) ) {
			$classes[] = 'wc-prl-tag-' . implode( '-', $tags );
		}

		return $classes;
	}

	/*
	|--------------------------------------------------------------------------
	| Deprecated methods.
	|--------------------------------------------------------------------------
	*/

	public static function track_clicks() {
		_deprecated_function( __METHOD__ . '()', '2.0.0' );
	}

	public static function maybe_add_click_event( $track_param ) {
		_deprecated_function( __METHOD__ . '()', '2.0.0' );
	}

	public static function update_clicks_cookie() {
		_deprecated_function( __METHOD__ . '()', '2.0.0' );
	}

	public static function track_product_views( $product_id ) {
		_deprecated_function( __METHOD__ . '()', '1.2.0' );
	}

	public static function update_recently_viewed_cookie( $product_id ) {
		_deprecated_function( __METHOD__ . '()', '1.2.0' );
	}
}

WC_PRL_Tracking::init();
