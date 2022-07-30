<?php
/**
 * WC_PRL_PB_Compatibility class
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.0.6
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hooks for Product Bundles compatibility.
 *
 * @class    WC_PRL_PB_Compatibility
 * @version  1.4.16
 */
class WC_PRL_PB_Compatibility {

	/**
	 * Initialize.
	 */
	public static function init() {

		// Aggregate parent + child item totals.
		add_filter( 'woocommerce_prl_conversion_event_data', array( __CLASS__, 'conversion_event_data' ), 10, 3 );

		// Add 'Relative Bundle' filter.
		add_filter( 'woocommerce_prl_filters', array( __CLASS__, 'relative_bundle_filter' ) );

		// Remove bundled products from product recommendations.
		add_filter( 'woocommerce_prl_processed_deployment_products', array( __CLASS__, 'remove_bundled_products_from_single_bundle' ), 10, 3 );
	}

	/**
	 * Aggregate bundled order item totals into parent order item container.
	 *
	 * @param  array                  $data
	 * @param  WC_Order_Item_Product  $item
	 * @param  WC_Order               $order
	 * @return array
	 */
	public static function conversion_event_data( $data, $item, $order ) {

		$bundled_items = wc_pb_get_bundled_order_items( $item, $order );
		if ( $bundled_items ) {

			// Aggregate totals.
			$bundle_totals = array(
				'total'     => $item->get_total(),
				'total_tax' => $item->get_total_tax(),
			);

			foreach ( $bundled_items as $bundled_item ) {
				$bundle_totals[ 'total' ]     += $bundled_item->get_total();
				$bundle_totals[ 'total_tax' ] += $bundled_item->get_total_tax();
			}

			$data[ 'total' ]     = wc_format_decimal( $bundle_totals[ 'total' ] );
			$data[ 'total_tax' ] = wc_format_decimal( $bundle_totals[ 'total_tax' ] );
		}

		return $data;
	}

	/**
	 * Add 'Relative Bundle' filter.
	 *
	 * @param  array  $classes
	 * @return array
	 */
	public static function relative_bundle_filter( $classes ) {

		require_once  WC_PRL_ABSPATH . 'includes/filters/class-wc-prl-filter-bundle-context.php' ;

		$start         = array_search( 'WC_PRL_Filter_Recently_Viewed', $classes );
		$spliced_array = array_splice( $classes, $start, 0, array( 'WC_PRL_Filter_Bundle_Context' ) );

		return $classes;
	}

	/**
	 * Remove bundled products for Bundle recommendations.
	 *
	 * @since 1.4.12
	 *
	 * @param  array              $products
	 * @param  WC_PRL_Deployment  $deployment
	 * @param  WC_PRL_Engine      $engine
	 * @return array
	 */
	public static function remove_bundled_products_from_single_bundle( $products, $deployment, $engine ) {

		if ( 'product' === $engine->get_type() ) {

			global $product;
			if ( ! is_a( $product, 'WC_Product' ) ) {
				return $products;
			}

			if ( ! $product->is_type( 'bundle' ) ) {
				return $products;
			}

			$bundled_item_product_ids = array();
			foreach ( $product->get_bundled_data_items() as $bundled_item ) {
				$bundled_item_product_ids[] = $bundled_item->get_product_id();
			}

			$products = array_diff( $products, $bundled_item_product_ids );
		}

		return $products;
	}
}

WC_PRL_PB_Compatibility::init();
