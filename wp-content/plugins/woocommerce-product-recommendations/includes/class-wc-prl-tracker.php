<?php
/**
 * WC_PRL_Tracker class
 *
 * @package  WooCommerce Product Recommendations
 * @since    2.1.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Recommendations Helper Functions.
 *
 * @class    WC_PRL_Tracker
 * @version  2.1.0
 */
class WC_PRL_Tracker {

	/**
	 * Initialize the Tracker.
	 */
	public static function init() {

		if ( 'yes' === get_option( 'woocommerce_allow_tracking', 'no' ) ) {

			add_filter( 'woocommerce_tracker_data', array( __CLASS__, 'add_tracking_data' ), 10 );
			// Async tasks.
			add_action( 'wc_prl_daily', array( __CLASS__, 'maybe_calculate_tracking_data' ) );
		}
	}

	/**
	 * Adds PRL data to the tracked data.
	 *
	 * @param array $data
	 * @return array all the tracking data.
	 */
	public static function add_tracking_data( $data ) {
		self::maybe_calculate_tracking_data();
		$data[ 'extensions' ][ 'wc_prl' ] = self::get_tracking_data();
		return $data;
	}

	/**
	 * Get all tracking data from cache.
	 *
	 * @return array All the tracking data.
	 */
	protected static function get_tracking_data() {
		$tracking_data = get_option( self::get_cache_key() );
		if ( empty( $tracking_data ) ) {
			$tracking_data = array();
		}

		return $tracking_data;
	}

	/**
	 * Calculates all tracking-related data for the previous month and year.
	 * Runs indepedently in a background task. @see ::maybe_calculate_tracking_data().
	 *
	 * @return array All the tracking data.
	 */
	protected static function calculate_tracking_data() {
		$tracking_data                  = array();
		$tracking_data[ 'settings' ]    = self::get_settings();
		$tracking_data[ 'products' ]    = self::get_products_data();
		$tracking_data[ 'orders' ]      = self::get_orders_data();
		$tracking_data[ 'deployments' ] = self::get_deployments_data();
		$tracking_data[ 'locations' ]   = self::get_locations_data();
		$tracking_data[ 'filters' ]     = self::get_filters_data();
		$tracking_data[ 'amplifiers' ]  = self::get_amplifiers_data();
		$tracking_data[ 'conditions' ]  = self::get_conditions_data();
		return $tracking_data;
	}

	/**
	 * Maybe calculate orders data. Also, handles the caching strategy.
	 *
	 * @return bool Returns true if the data are re-calculated, false otherwise.
	 */
	public static function maybe_calculate_tracking_data() {
		$dates     = self::get_dates( 'previous_month' );
		$cache_key = self::get_cache_key();
		$data      = get_option( $cache_key );
		if ( empty( $data ) ) {

			$data = self::calculate_tracking_data();

			// Cache.
			update_option( $cache_key, $data );

			// Auto-clear previous cache key, once a new one is generated.
			$prev_dates     = self::get_dates( 'previous_month', $dates[ 'start' ] );
			$prev_cache_key = self::get_cache_key( $prev_dates[ 'start' ] );
			delete_option( $prev_cache_key );

			return true;
		}

		return false;
	}

	/**
	 * Orders data cache key based on time.
	 *
	 * @param  DateTime $reference_date (Optional) A reference date to calculate on the previous month and year cache key. Defaults to 'now'.
	 * @return string The cache key string.
	 */
	private static function get_cache_key( $reference_date = null ) {
		$dates = self::get_dates( 'previous_month', $reference_date );
		return sprintf(
			'woocommerce_prl_tracking_data_%s_%s',
			$dates[ 'start' ]->format( 'n' ),
			$dates[ 'start' ]->format( 'Y' )
		);
	}

	/**
	 * Gets products data.
	 *
	 * @return array
	 */
	private static function get_products_data() {
		global $wpdb;

		$data = array(
			'count' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->posts}` WHERE `post_type` = 'product' AND `post_status` NOT IN ( 'trash', 'auto-draft' )" ),
		);

		return $data;
	}

	/**
	 * Gets deployments data.
	 *
	 * @return array
	 */
	private static function get_deployments_data() {
		global $wpdb;

		$data = array(
			'count'         => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->prefix}woocommerce_prl_deployments`" ),
			'total_engines' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$wpdb->posts}` WHERE `post_type` = 'prl_engine' AND `post_status` NOT IN ( 'trash', 'auto-draft' )" ),
		);

		return $data;
	}

	/**
	 * Gets locations data.
	 *
	 * @return array
	 */
	private static function get_locations_data() {
		global $wpdb;

		$results    = $wpdb->get_results( "SELECT `location_id`, COUNT(*) as `count` FROM `{$wpdb->prefix}woocommerce_prl_deployments` GROUP BY `location_id`" );
		$counts    = array();
		if ( ! empty( $results ) ) {
			foreach ( $results as $result ) {
				$counts[ $result->location_id ] = (int) $result->count;
			}
		}

		$locations = WC_PRL()->locations->get_locations();
		foreach ( $locations as $location ) {
			$data[ $location->get_location_id() ] = isset( $counts[ $location->get_location_id() ] ) ? $counts[ $location->get_location_id() ] : 0;
		}

		return $data;
	}

	/**
	 * Gets conditions data.
	 *
	 * @return array
	 */
	private static function get_conditions_data() {
		global $wpdb;

		$data       = array();
		$conditions = WC_PRL()->conditions->get_supported_conditions();
		foreach ( $conditions as $condition ) {
			$data[ $condition->get_id() ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->prefix}woocommerce_prl_deployments` WHERE `conditions_data` LIKE %s", '%' . $condition->get_id() . '";s:%' ) );
		}

		return $data;
	}

	/**
	 * Gets filters data.
	 *
	 * @return array
	 */
	private static function get_filters_data() {
		global $wpdb;

		$data       = array();
		$filters = WC_PRL()->filters->get_supported_filters();
		foreach ( $filters as $filter ) {
			$data[ $filter->get_id() ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->prefix}postmeta` WHERE `meta_key` = '_filters_data' AND `meta_value` LIKE %s", '%' . $filter->get_id() . '";s:%' ) );
		}

		return $data;
	}

	/**
	 * Gets amplifiers data.
	 *
	 * @return array
	 */
	private static function get_amplifiers_data() {
		global $wpdb;

		$data       = array();
		$amplifiers = WC_PRL()->amplifiers->get_supported_amplifiers();
		foreach ( $amplifiers as $amplifier ) {
			$data[ $amplifier->get_id() ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$wpdb->prefix}postmeta` WHERE `meta_key` = '_amplifiers_data' AND `meta_value` LIKE %s", '%' . $amplifier->get_id() . '";s:%' ) );
		}

		return $data;
	}

	/**
	 * Gets settings data.
	 *
	 * @return array
	 */
	private static function get_settings() {

		return array(
			'shopping_session_interval'    => (int) get_option( 'wc_prl_shopping_session_interval', 12 ),
			'cache_regeneration_threshold' => (int) get_option( 'wc_prl_cache_regeneration_threshold', 24 ),
			'max_location_deployments'     => (int) get_option( 'wc_prl_max_location_deployments', 3 ),
			'render_using_ajax'            => 'yes' === get_option( 'wc_prl_render_using_ajax', 'no' ) ? 'on' : 'off',
			'debug_enabled'                => 'yes' === get_option( 'wc_prl_debug_enabled', 'no' ) ? 'on' : 'off',
		);
	}

	/**
	 * Gets orders data.
	 *
	 * @return array
	 */
	private static function get_orders_data() {
		global $wpdb;

		$previous_year_dates  = self::get_dates( 'previous_year' );
		$previous_month_dates = self::get_dates( 'previous_month' );

		// Revenue - Previous month.
		$recs_revenue_prev_month = (float) $wpdb->get_var( "
			SELECT SUM( `total` )
			FROM `{$wpdb->prefix}woocommerce_prl_tracking_conversions` as `conversions`
			LEFT JOIN `{$wpdb->posts}` AS `orders` ON `conversions`.`order_id` = `orders`.`ID`
			WHERE `ordered_time` >= '{$previous_month_dates[ 'start' ]->getTimestamp()}'
			AND `ordered_time` <= '{$previous_month_dates[ 'end' ]->getTimestamp()}'
			AND `orders`.`post_status` NOT IN ( 'wc-cancelled', 'wc-pending', 'wc-failed', 'wc-on-hold' )
		" );

		$total_revenue_prev_month = (float) $wpdb->get_var( "
			SELECT SUM( `order_total`.`meta_value` )
			FROM `{$wpdb->posts}` AS `orders`
			LEFT JOIN `{$wpdb->prefix}postmeta` AS `order_total` ON `order_total`.`post_id` = `orders`.`ID`
			WHERE `orders`.`post_date_gmt` >= '{$previous_month_dates[ 'start' ]->format( 'Y-m-d h:i:s' )}'
			AND `orders`.`post_date_gmt` <= '{$previous_month_dates[ 'end' ]->format( 'Y-m-d h:i:s' )}'
			AND `orders`.`post_status` NOT IN ( 'wc-cancelled', 'wc-pending', 'wc-failed', 'wc-on-hold' )
			AND `order_total`.`meta_key` = '_order_total'
			AND `orders`.`post_type` = 'shop_order'
		" );

		$revenue_monthly_percentage = ! empty( $total_revenue_prev_month ) ? round( $recs_revenue_prev_month / $total_revenue_prev_month * 100, 4 ) : 0;

		// Revenue - Previous year.
		$recs_revenue_prev_year = (float) $wpdb->get_var( "
			SELECT SUM( `total` )
			FROM `{$wpdb->prefix}woocommerce_prl_tracking_conversions` AS `conversions`
			LEFT JOIN `{$wpdb->posts}` AS `orders` ON `conversions`.`order_id` = `orders`.`ID`
			WHERE `ordered_time` >= '{$previous_year_dates[ 'start' ]->getTimestamp()}'
			AND `ordered_time` <= '{$previous_year_dates[ 'end' ]->getTimestamp()}'
			AND `orders`.`post_status` NOT IN ( 'wc-cancelled', 'wc-pending', 'wc-failed', 'wc-on-hold' )
		" );

		$total_revenue_prev_year = (float) $wpdb->get_var( "
			SELECT SUM( `order_total`.`meta_value` )
			FROM `{$wpdb->posts}` AS `orders`
			LEFT JOIN `{$wpdb->prefix}postmeta` AS `order_total` ON `order_total`.`post_id` = `orders`.`ID`
			WHERE `orders`.`post_date_gmt` >= '{$previous_year_dates[ 'start' ]->format( 'Y-m-d h:i:s' )}'
			AND `orders`.`post_date_gmt` <= '{$previous_year_dates[ 'end' ]->format( 'Y-m-d h:i:s' )}'
			AND `orders`.`post_status` NOT IN ( 'wc-cancelled', 'wc-pending', 'wc-failed', 'wc-on-hold' )
			AND `order_total`.`meta_key` = '_order_total'
			AND `orders`.`post_type` = 'shop_order'
		" );

		$revenue_yearly_percentage = ! empty( $total_revenue_prev_year ) ? round( $recs_revenue_prev_year / $total_revenue_prev_year * 100, 4 ) : 0;

		$data = array(
			'recommendations_revenue_previous_month'         => $recs_revenue_prev_month,
			'revenue_previous_month'                         => $total_revenue_prev_month,
			'recommendations_revenue_previous_month_percent' => $revenue_monthly_percentage,
			'recommendations_revenue_previous_year'          => $recs_revenue_prev_year,
			'revenue_previous_year'                          => $total_revenue_prev_year,
			'recommendations_revenue_previous_year_percent'  => $revenue_yearly_percentage
		);

		return $data;
	}

	/**
	 * Get dates.
	 *
	 * @param string $time_period
	 * @param DateTime $reference_date (Optional) Date to act as reference for calculating the previous month, year. Defaults to 'now'.
	 * @return array Array of DateTime objects.
	 */
	protected static function get_dates( $time_period, $reference_date = null ) {

		if ( ! in_array( $time_period, array( 'previous_month', 'previous_year' ) ) ) {
			return array();
		}

		$today = is_a( $reference_date, 'DateTime' ) ? $reference_date : new DateTime();
		$today->setTime( 0,0,0 );

		switch ( $time_period ) {

			case 'previous_month':

				$ref       = $today->setDate( $today->format( 'Y' ), $today->format( 'm' ), 1 );
				$last_day  = $ref->sub( new \DateInterval( 'P1D' ) );
				$first_day = clone $last_day;
				$first_day->setDate( $last_day->format( 'Y' ), $last_day->format( 'm' ), 1 );
				$last_day->setTime( 23, 59, 59 );

				break;

			case 'previous_year':

				$ref       = $today->setDate( $today->format( 'Y' ), 1, 1 );
				$last_day  = $ref->sub( new \DateInterval( 'P1D' ) );
				$first_day = clone $last_day;
				$first_day->setDate( $last_day->format( 'Y' ), 1, 1 );
				$last_day->setTime( 23, 59, 59 );

				break;
		}

		return array(
			'start' => $first_day,
			'end'   => $last_day
		);
	}
}

WC_PRL_Tracker::init();
