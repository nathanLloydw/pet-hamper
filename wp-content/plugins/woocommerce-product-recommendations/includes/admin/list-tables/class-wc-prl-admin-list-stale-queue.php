<?php
/**
 * WC_PRL_Generator_Stale_Queue_List_Table class
 *
 * @package  WooCommerce Product Recommendations
 * @since    3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a custom queue list table.
 *
 * @class    WC_PRL_Generator_Stale_Queue_List_Table
 * @version  3.0.0
 */
class WC_PRL_Generator_Stale_Queue_List_Table extends WC_PRL_Generator_Queue_List_Table {

	/**
	 * Page home URL.
     *
	 * @const PAGE_URL
	 */
	const PAGE_URL = 'admin.php?page=prl_generator_queue';

	public function __construct() {
		global $status, $page;

		WP_List_Table::__construct( array(
			'singular' => 'stale_queue_item',
			'plural'   => 'stale_queue_items',
		) );
	}


	/**
	 * Handles the title column output.
	 *
	 * @param array $item
	 */
	public function column_deployment( $item ) {

		$delete_url = add_query_arg( array(
			'page'   => 'prl_generator_queue',
			'delete' => $item['key'],
		), admin_url( 'admin.php' ) );
		$delete_url = wp_nonce_url( $delete_url, 'wc_prl_delete_generator_queue_item_action', '_wc_prl_admin_nonce' );

		$dispatch_url = add_query_arg( array(
			'page'     => 'prl_generator_queue',
			'dispatch' => $item['key'],
		), admin_url( 'admin.php' ) );
		$dispatch_url = wp_nonce_url( $dispatch_url, 'wc_prl_dispatch_generator_queue_item_action', '_wc_prl_admin_nonce' );

		$reset_url = add_query_arg( array(
			'page'   => 'prl_generator_queue',
			'reset'  => $item['key'],
		), admin_url( 'admin.php' ) );
		$reset_url = wp_nonce_url( $reset_url, 'wc_prl_reset_generator_queue_item_action', '_wc_prl_admin_nonce' );

		$edit_url = add_query_arg( array(
			'page'       => 'prl_locations',
			'section'    => 'deploy',
			'deployment' => $item['deployment']->get_id(),
		), admin_url( 'admin.php' ) );

		$title   = sprintf( '%s (#%d)', $item['deployment']->get_title(), $item['deployment']->get_id() );
		$actions = array(
			'dispatch'   => sprintf( '<a href="%s">%s</a>', esc_url( $dispatch_url ), __( 'Run', 'woocommerce-product-recommendations' ) ),
			'reset'  => sprintf( '<a href="%s">%s</a>', esc_url( $reset_url ), __( 'Reset', 'woocommerce-product-recommendations' ) ),
			'edit'   => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), __( 'Edit deployment', 'woocommerce-product-recommendations' ) ),
			'delete' => sprintf( '<a href="%s">%s</a>', esc_url( $delete_url ), __( 'Delete', 'woocommerce-product-recommendations' ) ),
		);

		printf(
			'%s%s',
			esc_html( $title ),
			wp_kses_post( $this->row_actions( $actions ) )
		);
	}

	public function get_columns() {
		$cols = parent::get_columns();
		unset( $cols['cb'] );
		return $cols;
	}

	public function get_sortable_columns() {
		return array();
	}

	protected function get_bulk_actions() {
		return array();
	}

	public function prepare_items() {

		$per_page = 0;

		// Table columns;
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$total_items = WC_PRL()->deployments->queue->count( array( 'stale' => true ));

		$orderby = 'iterations';
		$order   = 'desc';

		// It's safe to ignore semgrep warning, as everything is properly escaped.
		// nosemgrep: audit.php.wp.security.sqli.input-in-sinks
		$results = WC_PRL()->deployments->queue->query( array(
			'stale'    => true,
			'order_by' => array( $orderby => $order ),
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
		) );
	}
}
