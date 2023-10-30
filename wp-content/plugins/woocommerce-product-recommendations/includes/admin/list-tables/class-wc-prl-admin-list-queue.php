<?php
/**
 * WC_PRL_Generator_Queue_List_Table class
 *
 * @package  WooCommerce Product Recommendations
 * @since    3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Adds a custom queue list table.
 *
 * @class    WC_PRL_Generator_Queue_List_Table
 * @version  3.0.0
 */
class WC_PRL_Generator_Queue_List_Table extends WP_List_Table {

	/**
	 * Page home URL.
     *
	 * @const PAGE_URL
	 */
	const PAGE_URL = 'admin.php?page=wc-status&tab=recommendations_queue';

	public function __construct() {

		parent::__construct( array(
			'singular' => 'queue_item',
			'plural'   => 'queue_items',
		) );
	}

	/**
	 * This is a default column renderer
	 *
	 * @param $item - row (key, value array)
	 * @param $column_name - string (key)
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		if ( isset( $item[ $column_name ] ) ) {

			echo wp_kses_post( $item[ $column_name ] );

		} else {

			/**
			 * Fires in each custom column in the list table.
			 *
			 * This hook only fires if the current column_name is not set inside the $item's keys.
			 *
			 * @param string $column_name The name of the column to display.
			 * @param array  $item
			 */
			do_action( 'manage_prl_generator_queue_custom_column', $column_name, $item );
		}
	}

	/**
	 * Handles the title column output.
	 *
	 * @param array $item
	 */
	public function column_deployment( $item ) {

		$delete_url = add_query_arg( array(
			'delete' => $item['key'],
		), admin_url( self::PAGE_URL ) );
		$delete_url = wp_nonce_url( $delete_url, 'wc_prl_delete_generator_queue_item_action', '_wc_prl_admin_nonce' );

		$dispatch_url = add_query_arg( array(
			'dispatch' => $item['key'],
		), admin_url( self::PAGE_URL ) );
		$dispatch_url = wp_nonce_url( $dispatch_url, 'wc_prl_dispatch_generator_queue_item_action', '_wc_prl_admin_nonce' );

		$edit_url = add_query_arg( array(
			'page'       => 'prl_locations',
			'section'    => 'deploy',
			'deployment' => $item['deployment']->get_id(),
		), admin_url( 'admin.php' ) );

		$title   = sprintf( '%s (#%d)', $item['deployment']->get_title(), $item['deployment']->get_id() );
		$actions = array(
			'dispatch'   => sprintf( '<a href="%s">%s</a>', esc_url( $dispatch_url ), __( 'Run', 'woocommerce-product-recommendations' ) ),
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), __( 'Edit', 'woocommerce-product-recommendations' ) ),
			'delete' => sprintf( '<a href="%s">%s</a>', esc_url( $delete_url ), __( 'Delete', 'woocommerce-product-recommendations' ) ),
		);

		printf(
			'%s%s',
			esc_html( $title ),
			wp_kses_post( $this->row_actions( $actions ) )
		);
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @param array $item
	 */
	public function column_cb( $item ) {
		?><label class="screen-reader-text" for="cb-select-<?php esc_attr( $item['key'] ); ?>"><?php
			printf( esc_html__( 'Select %s', 'woocommerce-product-recommendations' ), esc_html( $item[ 'key' ] ) );
		?></label>
		<input id="cb-select-<?php echo esc_attr( $item[ 'key' ] ); ?>" type="checkbox" name="queue_items[]" value="<?php echo esc_attr( $item[ 'key' ] ); ?>" />
		<?php
	}

    /**
	 * Handles the location column output.
	 *
	 * @param array $item
	 */
	public function column_wc_actions( $item ) {

		$delete_url = add_query_arg( array(
			'delete' => $item['key'],
		), admin_url( self::PAGE_URL ) );
		$delete_url = wp_nonce_url( $delete_url, 'wc_prl_delete_generator_queue_item_action', '_wc_prl_admin_nonce' );

		$dispatch_url = add_query_arg( array(
			'dispatch' => $item['key'],
		), admin_url( self::PAGE_URL ) );
		$dispatch_url = wp_nonce_url( $dispatch_url, 'wc_prl_dispatch_generator_queue_item_action', '_wc_prl_admin_nonce' );

		$edit_url = add_query_arg( array(
			'page'       => 'prl_locations',
			'section'    => 'deploy',
			'deployment' => $item['deployment']->get_id(),
		), admin_url( 'admin.php' ) );

		?>
		<p>
			<a class="button wc-action-button prl-dispatch" href="<?php echo esc_url( $dispatch_url ) ?>" aria-label="<?php esc_attr_e( 'Run', 'woocommerce-product-recommendations' ) ?>" title="<?php esc_attr_e( 'Run', 'woocommerce-product-recommendations' ) ?>"><?php esc_html_e( 'Run', 'woocommerce-product-recommendations' ) ?></a>
		
			<a class="button wc-action-button edit" href="<?php echo esc_url( $edit_url ) ?>" aria-label="<?php esc_attr_e( 'Edit deployment', 'woocommerce-product-recommendations' ) ?>" title="<?php esc_attr_e( 'Edit deployment', 'woocommerce-product-recommendations' ) ?>"><?php esc_html_e( 'Edit', 'woocommerce-product-recommendations' ) ?></a>


			<a class="button wc-action-button prl-remove" href="<?php echo esc_url( $delete_url ) ?>" aria-label="<?php esc_attr_e( 'Remove from queue', 'woocommerce-product-recommendations' ) ?>" title="<?php esc_attr_e( 'Delete', 'woocommerce-product-recommendations' ) ?>"><?php esc_html_e( 'Remove', 'woocommerce-product-recommendations' ) ?></a>
		</p>
		<?php
	}

    /**
	 * Handles the added time column output.
	 *
	 * @param array $item
	 */
	public function column_added_time( $item ) {
		echo esc_html( gmdate( 'D M d Y H:i:s O', $item['added_time'] ) );
	}


	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 */
	public function get_columns() {

		$columns                 = array();
		$columns[ 'cb' ]         = '<input type="checkbox" />';
		$columns[ 'deployment' ] = _x( 'Deployment', 'column_name', 'woocommerce-product-recommendations' );
		$columns[ 'added_time' ] = _x( 'Date', 'column_name', 'woocommerce-product-recommendations' );
		$columns[ 'wc_actions' ] = _x( 'Actions', 'column_name', 'woocommerce-product-recommendations' );

		return $columns;
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'iterations' => array( 'iterations', true ),
			'added_time' => array( 'added_time', true ),
		);

		return $sortable_columns;
	}

	protected function get_bulk_actions() {
		$actions             = array();
		$actions[ 'delete' ] = __( 'Delete Permanently', 'woocommerce-product-recommendations' );
		return $actions;
	}

	private function process_bulk_action() {

		if ( $this->current_action() ) {

			check_admin_referer( 'bulk-' . $this->_args['plural'] );

			$queue_items = array();
			if ( isset( $_GET[ 'queue_items' ] ) && is_array( $_GET[ 'queue_items' ] ) ) {
				$queue_items = wc_clean( $_GET[ 'queue_items' ] );
			}

			if ( ! empty( $queue_items ) && 'delete' === $this->current_action() ) {

				foreach ( $queue_items as $key ) {
					WC_PRL()->deployments->queue->delete( $key );
				}

				WC_PRL_Admin_Notices::add_notice( __( 'Tasks deleted successfully.', 'woocommerce-product-recommendations' ), 'success', true );
			}

			wp_redirect( admin_url( self::PAGE_URL ) );
			exit();
		}
	}

	public function get_total_items_count() {

		if ( empty( $this->_pagination_args ) ) {
			return 0;
		}

		return $this->_pagination_args['total_items'];
	}

	public function prepare_items() {

		$per_page = 10;

		// Table columns;
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$total_items = WC_PRL()->deployments->queue->count( array( 'stale' => false ));

		$paged   = isset( $_REQUEST[ 'paged' ] ) ? max( 0, absint( $_REQUEST[ 'paged' ] ) - 1 ) : 0;
		$orderby = ( isset( $_REQUEST[ 'orderby' ] ) && in_array( $_REQUEST[ 'orderby' ], array_keys( $this->get_sortable_columns() ) ) ) ? sanitize_text_field( $_REQUEST[ 'orderby' ] ) : 'added_time';
		$order   = ( isset( $_REQUEST[ 'order' ] ) && in_array( $_REQUEST[ 'order' ], array( 'asc', 'desc' ) ) ) ? sanitize_text_field( $_REQUEST[ 'order' ] ) : 'asc';

		// It's safe to ignore semgrep warning, as everything is properly escaped.
		// nosemgrep: audit.php.wp.security.sqli.input-in-sinks
		$results = WC_PRL()->deployments->queue->query( array(
			'stale'    => false,
			'order_by' => array( $orderby => $order ),
			'limit'    => $per_page,
			'offset'   => $paged * $per_page
		) );

        // Transform items.
        $this->items = array();
        foreach ( $results as $result_item ) {

            $data       = maybe_unserialize( $result_item['data'] ); // nosemgrep: audit.php.wp.security.object-injection
            $deployment = new WC_PRL_Deployment( (int) $data['id'] );
            if ( ! $deployment || ! $deployment->get_id() ) {
                continue;
            }

            $step       = isset( $data['step'] ) ? absint( $data['step'] ) : 1;

            $this->items[] = array(
                'key'        => $result_item['item_key'],
                'deployment' => $deployment,
                'source'     => $data['source'],
                'step'       => $step,
                'added_time' => (int) $result_item['added_time'],
                'iterations' => (int) $result_item['iterations']
            );
        }

		// [REQUIRED] configure pagination
		$this->set_pagination_args( array(
			'total_items' => $total_items, // total items defined above
			'per_page'    => $per_page, // per page constant defined at top of method
			'total_pages' => ceil( $total_items / $per_page ) // calculate pages count
		) );
	}

	/**
	 * Message to be displayed when there are no items
	 *
	 */
	public function no_items() {
        ?>
		<p>
			<?php esc_html_e( 'No tasks in queue.', 'woocommerce-product-recommendations' ); ?>
		</p>
		<?php
	}
}
