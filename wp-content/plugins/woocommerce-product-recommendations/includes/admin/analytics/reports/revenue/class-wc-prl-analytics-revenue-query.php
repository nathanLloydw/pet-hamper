<?php
/**
 * REST API Reports recommendations revenue query
 *
 * @package  WooCommerce Product Recommendations
 * @since    2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Admin\API\Reports\Query as ReportsQuery;

/**
 * WC_PRL_Analytics_Revenue_Query class.
 *
 * @version 2.0.0
 */
class WC_PRL_Analytics_Revenue_Query extends ReportsQuery {

	/**
	 * Valid fields for report.
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array();
	}

	/**
	 * Get gift card data based on the current query vars.
	 *
	 * @return array
	 */
	public function get_data() {
		$args       = apply_filters( 'woocommerce_analytics_recommendations_revenue_query_args', $this->get_query_vars() );
		$data_store = WC_Data_Store::load( 'report-recommendations-revenue' );
		$results    = $data_store->get_data( $args );
		return apply_filters( 'woocommerce_analytics_recommendations_revenue_select_query', $results, $args );
	}

}
