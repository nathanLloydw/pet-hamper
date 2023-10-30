<?php
/**
 * WC_PRL_Generator_Queue class
 *
 * @package  WooCommerce Product Recommendations
 * @since    3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_Async_Request', false ) ) {
	include_once  WC_ABSPATH . 'includes/libraries/wp-async-request.php' ;
}

/**
 * Generator Queue class.
 *
 * @class    WC_PRL_Generator_Queue
 * @version  3.0.0
 */
class WC_PRL_Generator_Queue extends WP_Async_Request {

	/**
	 * Action
	 *
	 * (default value: 'background_process')
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'background_process';

	/**
	 * Start time of current process.
	 *
	 * (default value: 0)
	 *
	 * @var int
	 * @access protected
	 */
	protected $start_time = 0;

	/**
	 * How many seconds will the method is_running will return true counting from the start.
	 * Throttle queue processing every 50 seconds (in seconds.)
	 * 
	 * The repeating interval has been set to 1 minute, thus a lower number would be preferable.
	 *
	 * @var int
	 */
	protected $queue_lock_time = 50;

	/**
	 * How many deployments can be processed in sequential dispatches, thus blocking the cron healthcheck.
	 *
	 * (default value: 10)
	 *
	 * @var int
	 * @access protected
	 */
	protected $batch_size = 2;

	/**
	 * Property to set when dispatching a single queue item.
	 *
	 * @var string
	 */
	protected $run_for_key;

	/**
	 * Cron_hook_identifier
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $cron_hook_identifier;

	/**
	 * Cron_interval_identifier
	 *
	 * @var mixed
	 * @access protected
	 */
	protected $cron_interval_identifier;

	/**
	 * Throttle cron healthcheck every 1 minutes.
	 *
	 * @var int
	 */
	protected $cron_interval = 1;

	/**
	 * Max size of the queue. After that number, the system no longer saves the task request.
	 *
	 * @var int
	 */
	protected $max_queue_allowed;

	/**
	 * Queue db table name
	 *
	 * @var int
	 */
	protected $queue_table_name = 'woocommerce_prl_generator_queue';

	/**
	 * Initiate new background process
	 */
	public function __construct() {

		// Uses unique prefix per blog so each blog has its own queue.
		$this->prefix                   = 'wp_' . get_current_blog_id();
		$this->action                   = 'wc_prl_generator';

		parent::__construct();

		$this->cron_hook_identifier     = $this->identifier . '_cron';
		$this->cron_interval_identifier = $this->identifier . '_cron_interval';

		// Determine queue.
		$this->max_queue_allowed        = apply_filters( 'woocommerce_prl_queue_max_size', $this->is_handled_via_wp_cli() ? 2000 : 20 );

		add_action( $this->cron_hook_identifier, array( $this, 'handle_cron_healthcheck' ) );
		add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ) );
		$this->schedule_event();
	}

	/**
	 * Is queue processing handled via wp-cli.
	 */
	public function is_handled_via_wp_cli() {
		return (bool) apply_filters( 'woocommerce_prl_queue_via_wp_cli', false );
	}

	/*
	|--------------------------------------------------------------------------
	| Cron Management.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Schedule event
	 */
	protected function schedule_event() {
		if ( apply_filters( 'wc_prl_enable_cron_generation', true ) && ! wp_next_scheduled( $this->cron_hook_identifier ) ) {
			wp_schedule_event( time(), $this->cron_interval_identifier, $this->cron_hook_identifier );
		}
	}

	/**
	 * Handle cron healthcheck
	 *
	 * Restart the background process if not already running
	 * and data exists in the queue.
	 */
	public function handle_cron_healthcheck() {

		/**
		 * Use this filter to disable the cron healthcheck. 
		 * If you enable this, then the queue must be handled manually using the WP-CLI command. @see WC_PRL_CLI::hooks()
		 */
		if ( $this->is_handled_via_wp_cli() ) {
			// Queue will be handled by WP-CLI.
			exit;
		}

		if ( $this->is_process_running() ) {
			// Background process already running.
			exit;
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			exit;
		}

		$this->handle();

		exit;
	}

	/**
	 * Schedule cron healthcheck
	 *
	 * @access public
	 * @param mixed $schedules Schedules.
	 * @return mixed
	 */
	public function schedule_cron_healthcheck( $schedules ) {
		$interval = apply_filters( $this->identifier . '_cron_interval', 5 );

		if ( property_exists( $this, 'cron_interval' ) ) {
			$interval = apply_filters( $this->identifier . '_cron_interval', $this->cron_interval );
		}

		$schedules[ $this->identifier . '_cron_interval' ] = array(
			'interval' => MINUTE_IN_SECONDS * $interval,
			'display'  => sprintf( __( 'Every %d minutes', 'woocommerce' ), $interval ),
		);

		return $schedules;
	}

	/*
	|--------------------------------------------------------------------------
	| Queue management.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Generate key
	 *
	 * Generates a unique key based on microtime. Queue items are
	 * given a unique key so that they can be merged upon save.
	 *
	 * @param array  $data Data.
	 *
	 * @return string
	 */
	protected function generate_key( $data ) {
		$prepend = $this->identifier . '_item_';
		return sprintf( '%s%d%s', $prepend, $data['id'], $data['source_data_key']);
	}

	/**
	 * Get number of queue items.
	 *
	 * @param array $args
	 * @return int
	 */
	public function count( $args = array() ) {
		if ( ! is_array( $args ) ) {
			$args = array();
		}

		$args['count'] = true;
		return $this->query( $args );
	}

	/**
	 * Query queue items.
	 *
	 * @return bool
	 */
	public function query( $args ) {
		global $wpdb;

		$args = wp_parse_args( $args, array(
			'count'           => false,
			'stale'           => null, // Possible values: null, false, true.
			'order_by'        => array( 'added_time' => 'ASC' ),
			'limit'           => -1,
			'offset'          => -1
		) );

		$table       = $wpdb->prefix . $this->queue_table_name;
		$key         = $this->identifier . '_item_%';
		$is_counting = true === $args['count'];

		// Build the query.
		$sql      = $is_counting ? "SELECT COUNT(*) FROM {$table}" : "SELECT * FROM {$table}";
		$join     = '';
		$where    = '';
		$order_by = '';

		$where_clauses    = array( '`item_key` LIKE %s' );
		$where_values     = array( $key );
		$order_by_clauses = array();

		if ( true === $args['stale'] ) {
			$where_clauses[] = '`iterations` > %d';
			$where_values[]  = $this->get_max_iterations_per_item(); 
		} elseif ( false === $args['stale'] ) {
			$where_clauses[] = '`iterations` < %d';
			$where_values[]  = $this->get_max_iterations_per_item(); 
		}

		// ORDER BY clauses.
		if ( $args[ 'order_by' ] && is_array( $args[ 'order_by' ] ) ) {
			foreach ( $args[ 'order_by' ] as $what => $how ) {
				$order_by_clauses[] = $table . '.' . esc_sql( strval( $what ) ) . ' ' . esc_sql( strval( $how ) );
			}
		}

		$order_by_clauses = empty( $order_by_clauses ) ? array( $table . '.id, ASC' ) : $order_by_clauses;

		$where    = ' WHERE ' . implode( ' AND ', $where_clauses );
		$order_by = ' ORDER BY ' . implode( ', ', $order_by_clauses );
		$limit    = $args[ 'limit' ] > 0 ? ' LIMIT ' . absint( $args[ 'limit' ] ) : '';
		$offset   = $args[ 'offset' ] > 0 ? ' OFFSET ' . absint( $args[ 'offset' ] ) : '';
		
		$sql .= $join . $where . $order_by . $limit . $offset;

		$results = $is_counting ? $wpdb->get_var( $wpdb->prepare( $sql, $where_values ) ) : $wpdb->get_results( $wpdb->prepare( $sql, $where_values ), ARRAY_A );

		return $results;
	}

	/**
	 * Is queue empty
	 *
	 * @return bool
	 */
	public function is_queue_empty() {
		global $wpdb;

		$table  = $wpdb->prefix . $this->queue_table_name;
		$key    = $this->identifier . '_item_%';

		$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$table}
			WHERE `item_key` LIKE %s
			AND `iterations` < %d
		", $key, $this->get_max_iterations_per_item()  ) );

		return ! ( $count > 0 );
	}

	/**
	 * Delete queue items based on deployment
	 *
	 * @param  array  $deployment_ids
	 * @return bool
	 */
	public function delete_by_deployment_id( $deployment_ids ) {

		if ( ! is_array( $deployment_ids ) ) {
			return false;
		}

		global $wpdb;

		$table          = $wpdb->prefix . $this->queue_table_name;
		$key            = $this->identifier . '_item_%';
		$deployment_ids = array_map( 'absint', $deployment_ids );

		$deleted = $wpdb->query( $wpdb->prepare( "
			DELETE
			FROM {$table}
			WHERE `deployment_id` IN ('" . implode( ', ', array_fill( 0, count( $deployment_ids ), '%d' ) ) . "')
		", $deployment_ids ) );

		return (int) $deleted > 0;
	}

	/**
	 * Checks if the queue is full.
	 *
	 * @return bool
	 */
	public function is_queue_full() {
		global $wpdb;

		$table  = $wpdb->prefix . $this->queue_table_name;
		$key    = $this->identifier . '_item_%';

		$count = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(*)
			FROM {$table}	
			WHERE `item_key` LIKE %s
			AND `iterations` < %d
		", $key, $this->get_max_iterations_per_item()  ) );

		return $count > $this->max_queue_allowed;
	}

	/**
	 * Get item.
	 *
	 * @param string $key The key of the queue item to get.
	 * @return array Return an array of stdClass objects from the queue
	 */
	protected function get_item( $key ) {
		global $wpdb;

		$table        = $wpdb->prefix . $this->queue_table_name;

		$row = $wpdb->get_row(
			$wpdb->prepare( "
				SELECT *
				FROM {$table}
				WHERE `item_key` = %s
				LIMIT 1
			", 
			$key
			) 
		);

		if ( ! $row ) {
			return false;
		}

		$item                = new stdClass();
		$item->key           = $row->item_key;
		$item->deployment_id = absint( $row->deployment_id );
		$item->data          = maybe_unserialize( $row->data );
		$item->iterations    = absint( $row->iterations );
		return $item;
	}

	/**
	 * Get batch
	 *
	 * @return array Return an array of stdClass objects from the queue
	 */
	protected function get_next_item() {
		global $wpdb;

		$table        = $wpdb->prefix . $this->queue_table_name;
		$key          = $this->identifier . '_item_%';

		$row = $wpdb->get_row(
			$wpdb->prepare( "
				SELECT *
				FROM {$table}
				WHERE `item_key` LIKE %s
				AND `iterations` < %d
				ORDER BY `added_time` ASC
				LIMIT 1
			", 
			$key, 
			$this->get_max_iterations_per_item()
			) 
		);

		if ( ! $row ) {
			return false;
		}

		$item                = new stdClass();
		$item->key           = $row->item_key;
		$item->deployment_id = absint( $row->deployment_id );
		$item->data          = maybe_unserialize( $row->data );
		$item->iterations    = absint( $row->iterations );
		return $item;
	}

	/**
	 * Saves local data to queue.
	 *
	 * @return $this
	 */
	public function save() {

		if ( empty( $this->data ) || $this->is_queue_full() ) {
			// Drop requests if limits are reached.
			return;
		}

		$sql_values = array();
		$now        = time();
		foreach( $this->data as $index => $data ) {
			// Make added time distinct for every queue item.
			$now         += $index;
			// Generate unique key.
			$key          = $this->generate_key( $data );
			// Grab necessary data to save in the table.
			$sql_data     = array(
				'id'       => absint( $data['id'] ),
				'item_key' => $key,
				'source'   => $data['source'],
				'force'    => (bool) $data['force']
			);
			// Construct INSERT values.
			$sql_values         = array_merge( $sql_values, array( $key, absint( $data['id'] ), serialize($sql_data), $now, 0 ) );
			$sql_placeholders[] = '(%s, %d, %s, %d, %d)';
		}

		global $wpdb;
		$table = $wpdb->prefix . $this->queue_table_name;
		$query = sprintf( "INSERT IGNORE INTO {$table}(`item_key`,`deployment_id`,`data`,`added_time`,`iterations`) VALUES %s", implode(', ', $sql_placeholders ));
		$wpdb->query( $wpdb->prepare( $query, $sql_values ) );
		return $this;
	}

	/**
	 * Add to queue.
	 *
	 * @param mixed $data Data.
	 *
	 * @return string The key to be added.
	 */
	public function add( $data ) {
		$this->data[] = $data;
		return $this->generate_key( $data );
	}


	/**
	 * Update queue item.
	 *
	 * @param string $key Key.
	 * @param array  $data Data.
	 *
	 * @return $this
	 */
	public function update( $key, $data ) {
		if ( ! empty( $data ) ) {
			global $wpdb;
			$table = $wpdb->prefix . $this->queue_table_name;
			$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET `data` = %s WHERE `item_key` = %s LIMIT 1", maybe_serialize($data), $key ) );
		}

		return $this;
	}

	/**
	 * Delete queue item.
	 *
	 * @param string $key Key.
	 *
	 * @return $this
	 */
	public function delete( $key ) {
		global $wpdb;
		$table = $wpdb->prefix . $this->queue_table_name;
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE `item_key` = %s LIMIT 1", $key ) );

		return $this;
	}

	/**
	 * Increment number of execution iterations.
	 *
	 * In order to prevent the loss of this number, we have implemented a mandatory update of the attempts at the beginning. Loss may occur due to script timeouts or memory overflow.
	 * 
	 * @param string $key Key.
	 *
	 * @return $this
	 */
	public function increment_iterations( $key ) {
		global $wpdb;
		$table = $wpdb->prefix . $this->queue_table_name;
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET `iterations` = `iterations` + 1 WHERE `item_key` = %s LIMIT 1", $key ) );

		return $this;
	}

	/**
	 * Force set a number of iterations in a queue item.
	 * 
	 * @param string $key Key.
	 * @param int    $number The number of iterations to set.
	 *
	 * @return $this
	 */
	public function set_number_of_iterations( $key, $number ) {
		global $wpdb;
		$table = $wpdb->prefix . $this->queue_table_name;
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET `iterations` = %d WHERE `item_key` = %s LIMIT 1", $number, $key ) );

		return $this;
	}

	/**
	 * Get the maximum number of iterations allowed before dropping.
	 * 
	 * @param string $key Key.
	 *
	 * @return $this
	 */
	public function get_max_iterations_per_item() {
		return (int) apply_filters( 'wc_prl_generator_max_number_of_iterations', 10 );
	}

	/*
	|--------------------------------------------------------------------------
	| Process locking utilities.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Is process running
	 *
	 * Check whether the current process is already running
	 * in a background process.
	 */
	public function is_process_running() {
		if ( get_site_transient( $this->identifier . '_process_lock' ) ) {
			// Process already running.
			return true;
		}

		return false;
	}

	/**
	 * Lock process
	 *
	 * Lock the process so that multiple instances can't run simultaneously.
	 * Override if applicable, but the duration should be greater than that
	 * defined in the time_exceeded() method.
	 */
	protected function lock_process() {
		$this->start_time = time(); // Set start time of current process.

		$lock_duration = ( property_exists( $this, 'queue_lock_time' ) ) ? $this->queue_lock_time : 60; // 1 minute
		$lock_duration = apply_filters( $this->identifier . '_queue_lock_time', $lock_duration );

		set_site_transient( $this->identifier . '_process_lock', microtime(), $lock_duration );
	}

	/**
	 * Unlock process
	 *
	 * Unlock the process so that other instances can spawn.
	 *
	 * @return $this
	 */
	protected function unlock_process() {
		delete_site_transient( $this->identifier . '_process_lock' );

		return $this;
	}

	/*
	|--------------------------------------------------------------------------
	| Handling the queue.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Maybe process queue. This is the AJAX's action callback.
	 *
	 * Checks whether data exists within the queue and that
	 * the process is not already running.
	 */
	public function maybe_handle() {
		// Don't lock up other requests while processing
		session_write_close();

		if ( $this->is_process_running() ) {
			// Background process already running.
			wp_die();
		}

		if ( $this->is_queue_empty() && ! isset( $_GET['item_key'] ) ) {
			// No data to process.
			wp_die();
		}

		check_ajax_referer( $this->identifier, 'nonce' );
		// Check for single dispatch.
		$key = null;
		if ( isset( $_GET['item_key'] ) ) {
			$key = wc_clean( wp_unslash( $_GET['item_key'] ) );
		}

		$this->handle( $key );

		wp_die();
	}

	/**
	 * Handle
	 *
	 * Pass each queue item to the task handler, while remaining
	 * within server memory and time limit constraints.
	 */
	public function handle( $key = null ) {

		if ( is_null( $key ) && $this->is_queue_empty() ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::log( 'Queue is empty.' );
			}
			
			return;
		}

		$this->lock_process();

		$generator = new WC_PRL_Generator();
		$is_wp_cli = defined( 'WP_CLI' ) && WP_CLI;

		do {
			$processing_item = false;
			$item = is_null( $key ) ? $this->get_next_item() : $this->get_item($key);
			if ( false === $item ) {
				$processing_item = false;
				break;
			}

			$results = $generator->run( $item->data );
			if ( false !== $results ) {
				$processing_item = true;
				$this->update( $item->key, $results );
			} else {
				$this->delete( $item->key );
			}

		} while ( ( $is_wp_cli || ( ! $this->time_exceeded() && ! $this->memory_exceeded() ) ) && ( ( ! is_null( $key ) && $processing_item ) || ! $this->is_queue_empty() ) );

		$this->unlock_process();

		if ( $is_wp_cli ) {
			WP_CLI::success( 'Queue processed.' );
		}

		// Start next batch or complete process.
		if ( is_null( $key ) && ! $is_wp_cli && ! $this->is_queue_empty() ) {
			$this->dispatch();
		} elseif ( ! is_null( $key ) && $processing_item && ! $is_wp_cli ) {
			$this->dispatch_single_item( $key );
		}

		wp_die();
	}

	/**
	 * Dispatch for specific key.
	 *
	 * @param string $key The key from the queue item to dispatch.
	 * @return bool
	 */
	public function dispatch_single_item( $key ) {
		$this->run_for_key = $key;
		parent::dispatch();
		// Reset single mark.
		$this->run_for_key = null;
	}

	/**
	 * Get query args
	 *
	 * @return array
	 */
	protected function get_query_args() {
		$args = parent::get_query_args();
		if ( ! empty( $this->run_for_key ) ) {
			$args['item_key'] = $this->run_for_key;
		}

		return $args;
	}

	/*
	|--------------------------------------------------------------------------
	| Keeping execution limits.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Memory exceeded
	 *
	 * Ensures the batch process never exceeds 90%
	 * of the maximum WordPress memory.
	 *
	 * @return bool
	 */
	protected function memory_exceeded() {
		$memory_limit   = $this->get_memory_limit() * 0.9; // 90% of max memory
		$current_memory = memory_get_usage( true );
		$return         = false;

		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}

		return apply_filters( $this->identifier . '_memory_exceeded', $return );
	}

	/**
	 * Get memory limit
	 *
	 * @return int
	 */
	protected function get_memory_limit() {
		if ( function_exists( 'ini_get' ) ) {
			$memory_limit = ini_get( 'memory_limit' );
		} else {
			// Sensible default.
			$memory_limit = '128M';
		}

		if ( ! $memory_limit || -1 === $memory_limit ) {
			// Unlimited, set to 32GB.
			$memory_limit = '32000M';
		}

		return wp_convert_hr_to_bytes( $memory_limit );
	}

	/**
	 * Time exceeded.
	 *
	 * Ensures the batch never exceeds a sensible time limit.
	 * A timeout limit of 30s is common on shared hosting.
	 *
	 * @return bool
	 */
	protected function time_exceeded() {
		$finish = $this->start_time + apply_filters( $this->identifier . '_default_time_limit', 20 ); // 20 seconds
		$return = false;

		if ( time() >= $finish ) {
			$return = true;
		}

		return apply_filters( $this->identifier . '_time_exceeded', $return );
	}
}
