<?php
/**
 * REST API Reports recommendations revenue datastore
 *
 * @package  WooCommerce Product Recommendations
 * @since    2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Admin\API\Reports\TimeInterval;
use Automattic\WooCommerce\Admin\API\Reports\SqlQuery;

/**
 * WC_PRL_Analytics_Revenue_Data_Store class.
 *
 * @version 2.0.0
 */
class WC_PRL_Analytics_Revenue_Data_Store extends WC_PRL_Analytics_Data_Store {

	/**
	 * Table used to get the data.
	 *
	 * @var string
	 */
	protected static $table_name = 'woocommerce_prl_tracking_conversions';

	/**
	 * Cache identifier.
	 *
	 * @var string
	 */
	protected $cache_key = 'recommendations_revenue';

	/**
	 * Date field name.
	 * @var string
	 */
	protected $date_field_name = 'ordered_time';

	/**
	 * Mapping columns to data type to return correct response types.
	 *
	 * @var array
	 */
	protected $column_types = array(
		'products_count' => 'intval',
		'items_sold'     => 'intval',
		'orders_count'   => 'intval',
		'net_sales'      => 'floatval',
		'gross_sales'    => 'floatval',
	);

	/**
	 * Extended giftcard attributes to include in the data. @TODO: remove this.
	 *
	 * @var array
	 */
	protected $extended_attributes = array();

	/**
	 * Data store context used to pass to filters.
	 *
	 * @var string
	 */
	protected $context = 'recommendations_revenue';

	/**
	 * Assign report columns once full table name has been assigned.
	 */
	protected function assign_report_columns() {
		$table_name           = self::get_db_table_name();
		$this->report_columns = array(
			'items_sold'    => "SUM( CASE WHEN product_qty > 0 THEN {$table_name}.product_qty ELSE 1 END ) as items_sold",
			'net_sales'    => 'SUM( total ) AS net_sales',
			'gross_sales'    => 'SUM( total + total_tax ) AS gross_sales',
			'orders_count'   => "COUNT( DISTINCT ( CASE WHEN total >= 0 THEN {$table_name}.order_id END ) ) as orders_count",
			'products_count' => 'COUNT( distinct( product_id ) ) as products_count',
		);
	}

	/*
	 * Set up all the hooks for maintaining and populating table data.
	 */
	public static function init() {
		// ...
	}

	/**
	 * Maps ordering specified by the user to columns in the database/fields in the data.
	 *
	 * @param string $order_by Sorting criterion.
	 * @return string
	 */
	protected function normalize_order_by( $order_by ) {
		global $wpdb;

		if ( 'date' === $order_by ) {
			return 'time_interval';
		}

		return $order_by;
	}

	/**
	 * Updates the database query with parameters used for Products Stats report: categories and order status.
	 *
	 * @param  array  $query_args
	 * @return void
	 */
	protected function update_sql_query_params( $query_args ) {
		global $wpdb;

		$where_clause = '';
		$from_clause  = '';
		$table_name   = self::get_db_table_name();

		$included_products = $this->get_included_products( $query_args );
		if ( $included_products ) {
			$where_clause .= " AND {$table_name}.product_id IN ({$included_products})";
		}

		$included_products = $this->get_included_products( $query_args );
		$excluded_products = $this->get_excluded_products( $query_args );
		if ( $included_products ) {
			$where_clause .= " AND {$table_name}.product_id IN ({$included_products})";
		}
		if ( $excluded_products ) {
			$where_clause .= " AND {$table_name}.product_id NOT IN ({$excluded_products})";
		}

		$included_locations = $this->get_included_locations( $query_args );
		$excluded_locations = $this->get_excluded_locations( $query_args );
		if ( $included_locations ) {
			$where_clause .= " AND {$table_name}.location_hash IN ({$included_locations})";
		}
		if ( $excluded_locations ) {
			$where_clause .= " AND {$table_name}.location_hash NOT IN ({$excluded_locations})";
		}

		$order_status_filter = $this->get_status_subquery( $query_args );
		if ( $order_status_filter ) {
			$from_clause  .= " JOIN {$wpdb->prefix}wc_order_stats ON {$table_name}.order_id = {$wpdb->prefix}wc_order_stats.order_id";
			$where_clause .= " AND ( {$order_status_filter} )";
		}

		$this->add_time_period_sql_params( $query_args, $table_name );
		$this->total_query->add_sql_clause( 'where', $where_clause );
		$this->total_query->add_sql_clause( 'join', $from_clause );

		$this->add_intervals_sql_params( $query_args, $table_name );
		$this->interval_query->add_sql_clause( 'where', $where_clause );
		$this->interval_query->add_sql_clause( 'join', $from_clause );
		$this->interval_query->add_sql_clause( 'select', $this->get_sql_clause( 'select' ) . ' AS time_interval' );
	}

	/**
	 * Returns comma separated hashes of allowed locations, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_included_locations( $query_args ) {
		$included_locations = $query_args[ 'location_includes' ];
		if ( ! empty( $included_locations ) ) {
			return '\'' . implode( '\', \'', $included_locations ) .'\'';
		}

		return false;
	}

	/**
	 * Returns comma separated hashes of excluded locations, based on query arguments from the user.
	 *
	 * @param array $query_args Parameters supplied by the user.
	 * @return string
	 */
	protected function get_excluded_locations( $query_args ) {
		$excluded_locations = $query_args[ 'location_excludes' ];
		if ( ! empty( $excluded_locations ) ) {
			return '\'' . implode( '\', \'', $excluded_locations ) .'\'';
		}

		return false;
	}

	/**
	 * Returns the report data based on parameters supplied by the user.
	 *
	 * @param array $query_args  Query parameters.
	 * @return stdClass|WP_Error Data.
	 */
	public function get_data( $query_args ) {
		global $wpdb;

		$table_name = self::get_db_table_name();
		// These defaults are only partially applied when used via REST API, as that has its own defaults.
		$defaults   = array(
			'per_page'          => get_option( 'posts_per_page' ),
			'page'              => 1,
			'order'             => 'DESC',
			'orderby'           => 'date',
			'before'            => TimeInterval::default_before(),
			'after'             => TimeInterval::default_after(),
			'fields'            => '*',
			'interval'          => 'week',
			'product_includes'  => array(),
			'product_excludes'  => array(),
			'location_includes' => array(),
			'location_excludes' => array(),
		);
		$query_args = wp_parse_args( $query_args, $defaults );
		$this->normalize_timezones( $query_args, $defaults );

		/*
		 * We need to get the cache key here because
		 * parent::update_intervals_sql_params() modifies $query_args.
		 */
		$cache_key = $this->get_cache_key( $query_args );
		$data      = $this->get_cached_data( $cache_key );

		if ( false === $data ) {
			$this->initialize_queries();

			$selections = $this->selected_columns( $query_args );
			$params     = $this->get_limit_params( $query_args );

			$this->update_sql_query_params( $query_args );
			$this->get_limit_sql_params( $query_args );
			$this->interval_query->add_sql_clause( 'where_time', $this->get_sql_clause( 'where_time' ) );


			$db_intervals = $wpdb->get_col(
				$this->interval_query->get_query_statement()
			);

			$db_interval_count       = count( $db_intervals );
			$expected_interval_count = TimeInterval::intervals_between( $query_args[ 'after' ], $query_args[ 'before' ], $query_args[ 'interval' ] );
			$total_pages             = (int) ceil( $expected_interval_count / $params[ 'per_page' ] );
			if ( $query_args[ 'page' ] < 1 || $query_args[ 'page' ] > $total_pages ) {
				return array();
			}

			$intervals = array();
			$this->update_intervals_sql_params( $query_args, $db_interval_count, $expected_interval_count, $table_name );
			$this->total_query->add_sql_clause( 'select', $selections );
			$this->total_query->add_sql_clause( 'where_time', $this->get_sql_clause( 'where_time' ) );

			$totals = $wpdb->get_results(
				$this->total_query->get_query_statement(),
				ARRAY_A
			);

			if ( null === $totals ) {
				return new \WP_Error( 'woocommerce_analytics_recommendations_stats_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce-product-recommendations' ) );
			}

			$this->interval_query->add_sql_clause( 'order_by', $this->get_sql_clause( 'order_by' ) );
			$this->interval_query->add_sql_clause( 'limit', $this->get_sql_clause( 'limit' ) );
			$this->interval_query->add_sql_clause( 'select', ", MAX(${table_name}.{$this->date_field_name}) AS datetime_anchor" );
			if ( '' !== $selections ) {
				$this->interval_query->add_sql_clause( 'select', ', ' . $selections );
			}

			$intervals = $wpdb->get_results(
				$this->interval_query->get_query_statement(),
				ARRAY_A
			);

			if ( null === $intervals ) {
				return new \WP_Error( 'woocommerce_analytics_recommendations_stats_result_failed', __( 'Sorry, fetching revenue data failed.', 'woocommerce-product-recommendations' ) );
			}

			$totals = (object) $this->cast_numbers( $totals[ 0 ] );

			$data = (object) array(
				'totals'    => $totals,
				'intervals' => $intervals,
				'total'     => $expected_interval_count,
				'pages'     => $total_pages,
				'page_no'   => (int) $query_args[ 'page' ],
			);

			if ( TimeInterval::intervals_missing( $expected_interval_count, $db_interval_count, $params[ 'per_page' ], $query_args[ 'page' ], $query_args[ 'order' ], $query_args[ 'orderby' ], count( $intervals ) ) ) {
				$this->fill_in_missing_intervals( $db_intervals, $query_args[ 'adj_after' ], $query_args[ 'adj_before' ], $query_args[ 'interval' ], $data );
				$this->sort_intervals( $data, $query_args[ 'orderby' ], $query_args[ 'order' ] );
				$this->remove_extra_records( $data, $query_args[ 'page' ], $params[ 'per_page' ], $db_interval_count, $expected_interval_count, $query_args[ 'orderby' ], $query_args[ 'order' ] );
			} else {
				$this->update_interval_boundary_dates( $query_args[ 'after' ], $query_args[ 'before' ], $query_args[ 'interval' ], $data->intervals );
			}

			$this->create_interval_subtotals( $data->intervals );

			$this->set_cached_data( $cache_key, $data );
		}

		return $data;
	}

	/**
	 * Initialize query objects.
	 */
	protected function initialize_queries() {
		$this->clear_all_clauses();
		unset( $this->subquery );
		$this->total_query = new SqlQuery( $this->context . '_total' );
		$this->total_query->add_sql_clause( 'from', self::get_db_table_name() );

		$this->interval_query = new SqlQuery( $this->context . '_interval' );
		$this->interval_query->add_sql_clause( 'from', self::get_db_table_name() );
		$this->interval_query->add_sql_clause( 'group_by', 'time_interval' );
	}
}
