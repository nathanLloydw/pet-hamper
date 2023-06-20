<?php
/**
 * WC_PRL_Admin_Performance class
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_PRL_Admin_Performance Class.
 *
 * @class    WC_PRL_Admin_Performance
 * @version  2.4.0
 */
class WC_PRL_Admin_Performance {

	/**
	 * Page home URL.
     *
	 * @const PAGE_URL
	 */
	const PAGE_URL = 'admin.php?page=prl_performance';

	/**
	 * Performance page.
	 *
	 * Handles the display of performance page.
	 */
	public static function output() {

		$range = self::calculate_current_range();

		do_action( 'woocommerce_prl_performance_start', $range );

		$cached_results = get_transient( strtolower( __CLASS__ ) );
		if ( ! is_array( $cached_results ) || wc_prl_should_update_report( 'performance' ) ) {

			$cached_results = array();

			$args = array(
				'start_date' => $range[ 'start_date' ],
				'end_date'   => $range[ 'end_date' ]
			);

			$conversions       = WC_PRL()->db->tracking->query_conversions( $args );
			$total_conversions = 0;
			if ( ! empty( $conversions ) ) {
				$total_qtys = array_map( 'absint', wp_list_pluck( $conversions, 'product_qty' ) );
				foreach ( $total_qtys as $qty ) {
					if ( 0 === $qty ) {
						$qty = 1;
					}

					$total_conversions = $qty + $total_conversions;
				}
			}

			$prev_args        = array(
				'start_date' => $range[ 'prev_start_date' ],
				'end_date'   => $range[ 'prev_end_date' ]
			);
			$prev_conversions = WC_PRL()->db->tracking->query_conversions( $prev_args );
			$total_prev_conversions = 0;
			if ( ! empty( $prev_conversions ) ) {
				$total_qtys        = array_map( 'absint', wp_list_pluck( $prev_conversions, 'product_qty' ) );
				foreach ( $total_qtys as $qty ) {

					// Backwards compatibility version lt 2.0.
					if ( 0 === $qty ) {
						$qty = 1;
					}

					$total_prev_conversions = $qty + $total_prev_conversions;
				}
			}

			// Generate numbers.
			foreach ( $conversions as $index => $conv ) {
				$conversions[ $index ][ 'total_with_tax' ] = $conv[ 'total' ] + $conv[ 'total_tax' ];
			}
			foreach ( $prev_conversions as $index => $conv ) {
				$prev_conversions[ $index ][ 'total_with_tax' ] = $conv[ 'total' ] + $conv[ 'total_tax' ];
			}

			// Init container.
			$glance_data = array();

			// GROSS R.
			$glance_data[ 'gross' ]               = array();
			$glance_data[ 'gross' ][ 'current' ]  = wc_format_decimal( array_sum( wp_list_pluck( $conversions, 'total_with_tax' ) ), 2 );
			$glance_data[ 'gross' ][ 'previous' ] = wc_format_decimal( array_sum( wp_list_pluck( $prev_conversions, 'total_with_tax' ) ), 2 );
			$glance_data[ 'gross' ][ 'data' ]     = array_values( self::prepare( $conversions, 'ordered_time', 'total_with_tax', $range[ 'chart_interval' ], $range[ 'start_date' ], 'day' ) );

			// NET R.
			$glance_data[ 'net' ]               = array();
			$glance_data[ 'net' ][ 'current' ]  = wc_format_decimal( array_sum( wp_list_pluck( $conversions, 'total' ) ), 2 );
			$glance_data[ 'net' ][ 'previous' ] = wc_format_decimal( array_sum( wp_list_pluck( $prev_conversions, 'total' ) ), 2 );
			$glance_data[ 'net' ][ 'data' ]     = array_values( self::prepare( $conversions, 'ordered_time', 'total', $range[ 'chart_interval' ], $range[ 'start_date' ], 'day' ) );

			// Conversions.
			$glance_data[ 'conversions' ]               = array();
			$glance_data[ 'conversions' ][ 'current' ]  = $total_conversions;
			$glance_data[ 'conversions' ][ 'previous' ] = $total_prev_conversions;
			$glance_data[ 'conversions' ][ 'data' ]     = array_values( self::prepare( $conversions, 'ordered_time', false, $range[ 'chart_interval' ], $range[ 'start_date' ], 'day' ) );

			// Cache results.
			$cached_results[ 'glance_data' ] = $glance_data;

			// Top products data.
			$top_products_args                 = array(
				'start_date' => $range[ 'start_date' ],
				'end_date'   => $range[ 'end_date' ],
				'group'      => 'products'
			);
			$top_products                      = array();
			$top_products[ 'top_grossing' ]    = WC_PRL()->db->tracking->get_top_conversions( $top_products_args, 10 );

			// Cache.
			$cached_results[ 'top_products' ] = $top_products;

			// Top locations data.
			$top_locations_args                 = array(
				'start_date' => $range[ 'start_date' ],
				'end_date'   => $range[ 'end_date' ],
				'group'      => 'locations'
			);
			$top_locations                      = array();
			$top_locations[ 'top_grossing' ]    = WC_PRL()->db->tracking->get_top_conversions( $top_locations_args, 10 );

			// Cache.
			$cached_results[ 'top_locations' ] = $top_locations;

			// Save transient until the end of the day.
			set_transient( strtolower( __CLASS__ ), $cached_results, strtotime( 'tomorrow' ) - time() );

		} else {
			$glance_data   = $cached_results[ 'glance_data' ];
			$top_products  = $cached_results[ 'top_products' ];
			$top_locations = $cached_results[ 'top_locations' ];
		}

		include dirname( __FILE__ ) . '/views/html-admin-performance.php';
	}

	/**
	 * Get the current range and calculate the start and end dates.
	 *
	 */
	public static function calculate_current_range() {

		// Current & Previous period.
		$end_date        = strtotime( '+1 day 00:00:00', current_time( 'timestamp' ) );
		$start_date      = strtotime( '-7 days', $end_date );
		$prev_end_date   = $start_date;
		$prev_start_date = strtotime( '-14 days', $end_date );

		return array(
			'prev_start_date' => $prev_start_date,
			'prev_end_date'   => $prev_end_date,
			'start_date'      => $start_date,
			'end_date'        => $end_date,
			'chart_interval'  => absint( ceil( max( 0, ( $end_date - $start_date ) / ( 60 * 60 * 24 ) ) ) )
		);
	}

	/**
	 * Prepares data for the report. Bucketing into time periods.
	 */
	public static function prepare( $data, $date_key, $data_key, $interval, $start_date, $group_by ) {

		$prepared_data = array();

		// Ensure all days (or months) have values in this range.
		if ( 'day' === $group_by ) {
			for ( $i = 0; $i < $interval; $i ++ ) {
				$time = strtotime( gmdate( 'Ymd', strtotime( "+{$i} DAY", $start_date ) ) ) . '000';

				if ( ! isset( $prepared_data[ $time ] ) ) {
					$prepared_data[ $time ] = array( esc_js( $time ), 0 );
				}
			}
		} else {
			$current_yearnum  = gmdate( 'Y', $start_date );
			$current_monthnum = gmdate( 'm', $start_date );

			for ( $i = 0; $i < $interval; $i ++ ) {
				$time = strtotime( $current_yearnum . str_pad( $current_monthnum, 2, '0', STR_PAD_LEFT ) . '01' ) . '000';

				if ( ! isset( $prepared_data[ $time ] ) ) {
					$prepared_data[ $time ] = array( esc_js( $time ), 0 );
				}

				$current_monthnum ++;

				if ( $current_monthnum > 12 ) {
					$current_monthnum = 1;
					$current_yearnum  ++;
				}
			}
		}

		foreach ( $data as $d ) {
			switch ( $group_by ) {
				case 'day':
					$time = strtotime( gmdate( 'Ymd', $d[ $date_key ] ) ) . '000';
					break;
				case 'month':
				default:
					$time = strtotime( gmdate( 'Ym', $d[ $date_key ] ) . '01' ) . '000';
					break;
			}

			if ( ! isset( $prepared_data[ $time ] ) ) {
				continue;
			}

			if ( $data_key ) {
				$prepared_data[ $time ][ 1 ] += $d[ $data_key ];
			} else {
				$prepared_data[ $time ][ 1 ] ++;
			}
		}

		return $prepared_data;
	}

	/**
	 * Prints the diff between 2 values in HTML format.
	 *
	 * @param  float  $current
	 * @param  float  $previous
	 * @return string
	 */
	private static function print_difference( $current, $previous ) {
		if ( $previous > 0 ) {
			$difference = ( (float) $current - (float) $previous ) / abs( (float) $previous ) * 100;
		} else {
			$difference = __( '0%', 'woocommerce-product-recommendations' );
		}
		$class = $difference >= 0 ? 'up' : 'down';
		$class = $difference == 0 ? '' : $class;
		?>
		<span class="difference <?php echo esc_attr( $class ); ?>">
			<?php echo is_numeric( $difference ) ? esc_html( wc_format_decimal( abs( $difference ), 0 ) ) . '%' : esc_html( $difference ); ?>
		</span>
		<?php
	}

	/**
	 * Get a product's object.
	 *
	 * @param  int  $id
	 * @return string
	 */
	private static function get_product( $product_id ) {

		// Local cache product instances.
		static $products_map;

		if ( empty( $products_map ) ) {
			$products_map = array();
		}

		if ( ! isset( $products_map[ $product_id ] ) ) {
			$products_map[ $product_id ] = wc_get_product( $product_id );
		}

		return $products_map[ $product_id ];
	}

	/**
	 * Get a WCA analytics link by arguments.
	 *
	 * @param  int  $id
	 * @return string
	 */
	private static function get_analytics_link( $args ) {

		$range = self::calculate_current_range();
		$args  = wp_parse_args( $args, array(
			'after'   => gmdate( 'D M d Y H:i:s O', $range[ 'start_date' ] ),
			'before'  => gmdate( 'D M d Y H:i:s O', strtotime( '-1 minute', $range[ 'end_date' ] ) ), // Fix date display in WCA.
			'period'  => 'custom',
			'compare' => 'previous_period',
			'chart'   => '',
			'orderby' => '',
			'order'   => 'desc',
			'page'    => 'wc-admin',
			'path'    => '/analytics/recommendations',
			'section' => 'revenue',
		) );

		return admin_url( add_query_arg( $args, 'admin.php' ) ); // nosemgrep: audit.php.wp.security.xss.query-arg
	}

	/**
	 * Get a locations's object by hash.
	 *
	 * @param  int  $id
	 * @return string
	 */
	private static function get_location_by_hash( $hash ) {

		// Local cache locations.
		static $locations = array();

		// Construct locations map.
		if ( empty( $locations ) ) {
			foreach ( WC_PRL()->locations->get_locations() as $location ) {
				foreach ( $location->get_hooks() as $hook => $data ) {
					$key               = substr( md5( $hook ), 0, 7 );
					$locations[ $key ] = array(
						'title' => $location->get_title(),
						'hook'  => $hook,
						'id'    => $location->get_location_id(),
						'label' => $data[ 'label' ],
						'link'  => self::get_analytics_link( [ 'filter' => 'advanced', 'location_includes' => array( $key ) ] )
					);
				}
			}
		}

		$found_data = false;
		if ( isset( $locations[ $hash ] ) ) {
			$found_data = $locations[ $hash ];
		}

		/**
		 * `woocommerce_prl_performance_location_data` filter.
		 *
		 * @since 1.4.12
		 */
		return apply_filters( 'woocommerce_prl_performance_location_data', $found_data, $hash );
	}

	/*
	|--------------------------------------------------------------------------
	| Deprecated methods.
	|--------------------------------------------------------------------------
	*/

	private static function calc_clicks_per_view( $clicks, $views ) {
		_deprecated_function( __METHOD__ . '()', '2.0.0' );
		return array();
	}

	private static function calc_conversion_rate( $conversions, $clicks ) {
		_deprecated_function( __METHOD__ . '()', '2.0.0' );
		return array();
	}
}


