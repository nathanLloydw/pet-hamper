<?php
/**
 * WC_PRL_Ajax class
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front-end AJAX filters.
 *
 * @class    WC_PRL_Ajax
 * @version  2.4.0
 */
class WC_PRL_Ajax {

	/**
	 * Current URL that is retrieving deployments.
	 *
	 * @var string
	 */
	private static $current_url;

	/**
	 * Hook in.
	 */
	public static function init() {
		// Send HTML chunks to the JS template when using AJAX rendering.
		add_action( 'wc_ajax_woocommerce_prl_print_location', array( __CLASS__ , 'prl_print_location' ) );
	}

	/**
	 * Render deployments to bypass HTML cache.
	 *
	 * @since 1.1.0
	 *
	 * @return void
	 */
	public static function prl_print_location() {

		// Validation.
		if ( empty( $_POST[ 'locations' ] ) ) {

			wp_send_json( array(
				'result'  => 'failure',
				'message' => __( 'Locations not found', 'woocommerce-product-recommendations' )
			) );
		}

		// Filter current request url.
		self::$current_url = empty( $_POST[ 'current_url' ] ) ? null : esc_url( sanitize_text_field( $_POST[ 'current_url' ] ), null, 'edit' );
		add_filter( 'woocommerce_product_add_to_cart_url', array( __CLASS__, 'fix_add_to_cart_url' ), -9999, 2 );

		// Setup GLOBALS.
		WC_PRL()->locations->setup_environment( $_POST );

		$locations = explode( ',', wc_clean( $_POST[ 'locations' ] ) );
		$output    = array();

		foreach ( $locations as $hook ) {

			ob_start();

			// Hint: All native arguments passed
			//       from the current action are missing in this context.
			WC_PRL()->templates->process_hook( $hook, true );

			// Save output.
			$html = ob_get_clean();
			if ( $html ) {
				$output[ $hook ] = $html;
			}
		}

		// Remove url filter.
		self::$current_url = null;
		remove_filter( 'woocommerce_product_add_to_cart_url', array( __CLASS__, 'fix_add_to_cart_url' ), -9999, 2 );

		wp_send_json( array(

			'result'  => 'success',

			/**
			 * `woocommerce_prl_ajax_response_html` filter.
			 *
			 * @since 1.4.15
			 *
			 * @param  array  $output An array of strings representing each location's HTML.
			 * @return array
			 */
			'html'    => (array) apply_filters( 'woocommerce_prl_ajax_response_html', $output )
		) );
	}

	/**
	 * Fix url add-to-cart button.
	 *
	 * @since 1.1.0
	 *
	 * @param  string     $url
	 * @param  WC_Product $product
	 * @return string
	 */
	public static function fix_add_to_cart_url( $url, $product ) {
		if ( $product->has_options() || ( method_exists( $product, 'requires_input' ) && $product->requires_input() ) ) {
			return $url;
		}

		$url = remove_query_arg( array( 'wc-ajax', 'prl_track' ), add_query_arg( 'add-to-cart', $product->get_id(), self::$current_url ) );
		return $url; // nosemgrep: audit.php.wp.security.xss.query-arg
	}

	/*
	|--------------------------------------------------------------------------
	| Deprecated methods.
	|--------------------------------------------------------------------------
	*/

	public static function prl_log_view_event() {
		_deprecated_function( __METHOD__ . '()', '2.0.0' );
	}

	public static function prl_log_click_event() {
		_deprecated_function( __METHOD__ . '()', '2.0.0' );
	}
}

WC_PRL_Ajax::init();
