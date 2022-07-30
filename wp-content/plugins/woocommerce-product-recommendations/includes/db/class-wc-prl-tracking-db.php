<?php
/**
 * WC_PRL_Tracking_DB class
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracking DB API class.
 *
 * @class    WC_PRL_Tracking_DB
 * @version  2.0.0
 */
class WC_PRL_Tracking_DB {

	/**
	 * Cloning is forbidden.
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Foul!', 'woocommerce-product-recommendations' ), '1.0.0' );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Foul!', 'woocommerce-product-recommendations' ), '1.0.0' );
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		//...
	}

	/**
	 * Get top conversions by group (products or locations).
	 *
	 * @since  2.0.0
	 *
	 * @param  array  $args
	 * @param  int  $limit
	 * @return array
	 */
	public function get_top_conversions( $args, $limit = 5 ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'group'           => 'products', // 'products, locations'
			'start_date'      => '',
			'end_date'        => ''
		) );

		if ( ! in_array( $args[ 'group' ], array( 'products', 'locations' ) ) ) {
			return array();
		}

		if ( 'products' === $args[ 'group' ] ) {
			$select = 'product_id';
			$group  = 'product_id';
		} elseif ( 'locations' === $args[ 'group' ] ) {
			$select = 'location_hash';
			$group  = 'location_hash';
		}

		$table    = $wpdb->prefix . 'woocommerce_prl_tracking_conversions';
		$select  .= ', SUM(total) as rate';

		// Build the query.
		$sql      = 'SELECT ' . $select . " FROM {$table}";
		$where    = '';
		$group_by = ' GROUP BY ' . $group;
		$order_by = ' ORDER BY rate DESC';
		$limit    = ' LIMIT ' . absint( $limit );

		$where_clauses = array( '1=1' );

		// DATE BETWEEN.
		if ( ! empty( $args[ 'start_date' ] ) ) {
			$start_date      = absint( $args[ 'start_date' ] );
			$where_clauses[] = "`{$table}`.`ordered_time` >= {$start_date}";
		}
		if ( ! empty( $args[ 'end_date' ] ) ) {
			$end_date        = absint( $args[ 'end_date' ] );
			$where_clauses[] = "`{$table}`.`ordered_time` < {$end_date}";
		}

		$where = ' WHERE ' . implode( ' AND ', $where_clauses );
		$sql  .= $where . $group_by . $order_by . $limit;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $sql );

		if ( empty( $results ) ) {
			return array();
		}

		$a = array();
		foreach ( $results as $result ) {
			$a[] = (array) $result;
		}

		return $a;
	}

	public function query_conversions( $args ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'return'          => 'all', // 'ids'
			'deployment_id'   => 0,
			'engine_id'       => 0,
			'product_id'      => 0,
			'location_hash'   => '',
			'start_date'      => '',
			'end_date'        => '',
			'order_by'        => array( 'id' => 'ASC' )
		) );

		$table = $wpdb->prefix . 'woocommerce_prl_tracking_conversions';

		if ( in_array( $args[ 'return' ], array( 'ids' ) ) ) {
			$select = $table . '.id';
		} else {
			$select = '*';
		}

		// Build the query.
		$sql      = 'SELECT ' . $select . " FROM {$table}";
		$join     = '';
		$where    = '';
		$order_by = '';

		$where_clauses    = array( '1=1' );
		$order_by_clauses = array();

		// WHERE clauses.
		if ( $args[ 'engine_id' ] ) {
			$engine_ids = array_map( 'absint', is_array( $args[ 'engine_id' ] ) ? $args[ 'engine_id' ] : array( $args[ 'engine_id' ] ) );
			$engine_ids = array_map( 'esc_sql', $engine_ids );

			$where_clauses[] = "{$table}.engine_id IN ('" . implode( "', '", $engine_ids ) . "')";
		}

		if ( $args[ 'deployment_id' ] ) {
			$deployment_ids = array_map( 'absint', is_array( $args[ 'deployment_id' ] ) ? $args[ 'deployment_id' ] : array( $args[ 'deployment_id' ] ) );
			$deployment_ids = array_map( 'esc_sql', $deployment_ids );

			$where_clauses[] = "{$table}.deployment_id IN ('" . implode( "', '", $deployment_ids ) . "')";
		}

		if ( $args[ 'product_id' ] ) {
			$product_ids = array_map( 'absint', is_array( $args[ 'product_id' ] ) ? $args[ 'product_id' ] : array( $args[ 'product_id' ] ) );
			$product_ids = array_map( 'esc_sql', $product_ids );

			$where_clauses[] = "{$table}.product_id IN ('" . implode( "', '", $product_ids ) . "')";
		}

		if ( $args[ 'location_hash' ] ) {
			$location_hashes = is_array( $args[ 'location_hash' ] ) ? $args[ 'location_hash' ] : array( $args[ 'location_hash' ] );
			$location_hashes = array_map( 'esc_sql', $location_hashes );

			$where_clauses[] = "{$table}.location_hash IN ('" . implode( "', '", $location_hashes ) . "')";
		}

		// DATE BETWEEN.
		if ( ! empty( $args[ 'start_date' ] ) ) {
			$start_date      = absint( $args[ 'start_date' ] );
			$where_clauses[] = "{$table}.ordered_time >= {$start_date}";
		}
		if ( ! empty( $args[ 'end_date' ] ) ) {
			$end_date        = absint( $args[ 'end_date' ] );
			$where_clauses[] = "{$table}.ordered_time < {$end_date}";
		}

		// ORDER BY clauses.
		if ( $args[ 'order_by' ] && is_array( $args[ 'order_by' ] ) ) {
			foreach ( $args[ 'order_by' ] as $what => $how ) {
				$order_by_clauses[] = $table . '.' . esc_sql( strval( $what ) ) . ' ' . esc_sql( strval( $how ) );
			}
		}

		$order_by_clauses = empty( $order_by_clauses ) ? array( $table . '.id, ASC' ) : $order_by_clauses;

		// Build SQL query components.

		$where    = ' WHERE ' . implode( ' AND ', $where_clauses );
		$order_by = ' ORDER BY ' . implode( ', ', $order_by_clauses );

		// Assemble and run the query.

		$sql .= $join . $where . $order_by;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $sql );

		if ( empty( $results ) ) {
			return array();
		}

		$a = array();

		if ( 'ids' === $args[ 'return' ] ) {
			foreach ( $results as $result ) {
				$a[] = $result->id;
			}
		} else {
			foreach ( $results as $result ) {
				$a[] = (array) $result;
			}
		}

		return $a;
	}

	/**
	 * Create a tracking conversion in the DB.
	 *
	 * @param  array  $args
	 * @return false|int
	 *
	 * @throws Exception
	 */
	public function add_conversion_event( $args ) {

		$args = wp_parse_args( $args, array(
			'added_to_cart_time' => 0,
			'ordered_time'       => 0,
			'deployment_id'      => 0,
			'engine_id'          => 0,
			'location_hash'      => '',
			'source_hash'        => '',
			'product_id'         => 0,
			'product_qty'        => 0,
			'order_id'           => 0,
			'order_item_id'      => 0,
			'total'              => null,
			'total_tax'          => null
		) );

		// Empty attributes.
		if ( empty( $args[ 'deployment_id' ] ) || empty( $args[ 'engine_id' ] ) || empty( $args[ 'location_hash' ] ) || empty( $args[ 'product_id' ] ) || empty( $args[ 'order_id' ] ) ) {
			throw new Exception( __( 'Missing event attributes.', 'woocommerce-product-recommendations' ) );
		}

		// Add current time.
		if ( empty( $args[ 'ordered_time' ] ) ) {
			$args[ 'ordered_time' ] = time();
		}

		global $wpdb;

		// Increment views bucket counter into the DB or Create new Bucket.
		$create_sql = '
			INSERT INTO `' . $wpdb->prefix . 'woocommerce_prl_tracking_conversions`
				( added_to_cart_time, ordered_time, deployment_id, engine_id, location_hash, source_hash, product_id, product_qty, order_id, order_item_id, total, total_tax )
			VALUES
				( ' . absint( $args[ 'added_to_cart_time' ] ) . ', ' . absint( $args[ 'ordered_time' ] ) . ', ' . absint( $args[ 'deployment_id' ] ) . ', ' . absint( $args[ 'engine_id' ] ) . ', \'' . wc_clean( $args[ 'location_hash' ] ) . '\', \'' . wc_clean( $args[ 'source_hash' ] ) . '\', ' . absint( $args[ 'product_id' ] ) . ', ' . absint( $args[ 'product_qty' ] ) . ', ' . absint( $args[ 'order_id' ] ) . ', ' . absint( $args[ 'order_item_id' ] ) . ', ' . (float) ( $args[ 'total' ] ) . ', ' . (float) ( $args[ 'total_tax' ] ) . ' )';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $create_sql );

		wc_prl_invalidate_reports();

		return ( $wpdb->insert_id ) ? $wpdb->insert_id : false;
	}

	/*
	|--------------------------------------------------------------------------
	| Deprecated methods.
	|--------------------------------------------------------------------------
	*/

	public function get_top( $args, $limit ) {
		_deprecated_function( __METHOD__ . '()', '2.0.0' );
		return array();
	}

	public function query_views( $query_args ) {
		_deprecated_function( __METHOD__ . '()', '2.0.0' );
		return array();
	}

	public function query_clicks( $query_args ) {
		_deprecated_function( __METHOD__ . '()', '2.0.0' );
		return array();
	}

	public function add_view_event( $args ) {
		_deprecated_function( __METHOD__ . '()', '2.0.0' );
		return false;
	}

	public function add_click_event( $args ) {
		_deprecated_function( __METHOD__ . '()', '2.0.0' );
		return false;
	}

	public function get_current_time_span() {
		_deprecated_function( __METHOD__ . '()', '2.0.0' );
		return 0;
	}
}
