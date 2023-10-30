<?php
/**
 * WC_PRL_Admin_List_Table_Engines class
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Adds a custom deployments list table.
 *
 * @class    WC_PRL_Deployments_List_Table
 * @version  3.0.0
 */
class WC_PRL_Deployments_List_Table extends WP_List_Table {

	/**
	 * Page home URL.
     *
	 * @const PAGE_URL
	 */
	const PAGE_URL = 'admin.php?page=prl_locations';

	public function __construct() {
		global $status, $page;

		parent::__construct( array(
			'singular' => 'deployment',
			'plural'   => 'deployments',
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
			do_action( 'manage_prl_deployments_custom_column', $column_name, $item );
		}
	}

	/**
	 * Handles the title column output.
	 *
	 * @param array $item
	 */
	public function column_title( $item ) {

		$edit_url = add_query_arg( array(
			'page'       => 'prl_locations',
			'section'    => 'deploy',
			'deployment' => $item[ 'id' ],
		), admin_url( 'admin.php' ) );

		$delete_url = add_query_arg( array(
			'page'   => 'prl_locations',
			'delete' => $item[ 'id' ],
		), admin_url( 'admin.php' ) );
		$delete_url = wp_nonce_url( $delete_url, 'wc_prl_delete_location_action', '_wc_prl_admin_nonce' );

		$actions = array(
			'edit'       => sprintf( '<a href="%s">%s</a>', esc_url( $edit_url ), __( 'Edit', 'woocommerce-product-recommendations' ) ),
			'regenerate' => sprintf( '<a id="%d" href="#">%s</a>', $item[ 'id' ], __( 'Regenerate', 'woocommerce-product-recommendations' ) ),
			'delete'     => sprintf( '<a href="%s">%s</a>', esc_url( $delete_url ), __( 'Delete', 'woocommerce-product-recommendations' ) ),
		);

		// Remove edit if it's a CPT.
		if ( wc_prl_is_cpt_hook( $item[ 'hook' ] ) ) {
			unset( $actions[ 'edit' ] );
		}

		$title          = $item[ 'title' ] ? $item[ 'title' ] : esc_html__( '(no title)', 'woocommerce-product-recommendations' );
		$inactive_label = sprintf(
				'<i>&nbsp;&mdash;&nbsp;%s</i>',
				esc_html__( 'inactive', 'woocommerce-product-recommendations' )
			);

		printf(
			'<a class="row-title" href="%s" aria-label="%s">%s</a>%s%s',
			esc_url( admin_url( 'admin.php?page=prl_locations&section=deploy&deployment=' . $item[ 'id' ] ) ),
			esc_attr( sprintf( __( '&#8220;%s&#8221; (Edit)', 'woocommerce-product-recommendations' ), $title ) ),
			$title,
			isset( $item[ 'active' ] ) && 'on' !== $item[ 'active' ] ? wp_kses_post( $inactive_label ) : '',
			wp_kses_post( $this->row_actions( $actions ) )
		);
	}

	/**
	 * Handles the checkbox column output.
	 *
	 * @param array $item
	 */
	public function column_cb( $item ) {
		?><label class="screen-reader-text" for="cb-select-<?php the_ID(); ?>"><?php
			printf( esc_html__( 'Select %s', 'woocommerce-product-recommendations' ), esc_html( $item[ 'title' ] ) );
		?></label>
		<input id="cb-select-<?php echo esc_attr( $item[ 'id' ] ); ?>" type="checkbox" name="deployment[]" value="<?php echo esc_attr( $item[ 'id' ] ); ?>" />
		<?php
	}

	/**
	 * Handles the page column output.
	 *
	 * @param array $item
	 */
	public function column_page( $item ) {
		$location_id = $item[ 'location_id' ] ? $item[ 'location_id' ] : '';
		$locations   = WC_PRL()->locations->get_locations();

		if ( $location_id && isset( $locations[ $location_id ] ) ) {
			echo esc_html( $locations[ $location_id ]->get_title() );
		} else {
			echo esc_html__( 'N/A', 'woocommerce-product-recommendations' );
		}
	}

	/**
	 * Handles the location column output.
	 *
	 * @param array $item
	 */
	public function column_location( $item ) {
		$hook     = $item[ 'hook' ] ? $item[ 'hook' ] : '';
		$location = WC_PRL()->locations->get_location_by_hook( $hook );

		if ( ! $location ) {
			echo esc_html( __( 'N/A', 'woocommerce-product-recommendations' ) );
			return;
		}

		$hooks = $location->get_hooks();
		if ( empty( $hooks ) || ! isset( $hooks[ $hook ] ) ) {
			$output = esc_html( __( 'N/A', 'woocommerce-product-recommendations' ) );
		} else {
			$output = sprintf(
				'<a href="%s">%s</a>',
				admin_url( 'admin.php?page=prl_locations&section=hooks&location=' . $item[ 'location_id' ] . '&hook=' . $item[ 'hook' ] ),
				$hooks[ $hook ][ 'label' ]
			);
		}

		echo wp_kses_post( apply_filters( 'manage_prl_deployments_location_column', $output, $location, $item ) );
	}

	/**
	 * Handles the location column output.
	 *
	 * @param array $item
	 */
	public function column_engine( $item ) {
		$engine_id = $item[ 'engine_id' ] ? $item[ 'engine_id' ] : 0;
		$engine    = new WC_PRL_Engine( absint( $engine_id ) );

		echo '<a href="' . esc_url( admin_url( sprintf( 'post.php?post=%d&action=edit', $engine->get_id() ) ) ) . '" title="' . esc_attr__( 'Edit engine', 'woocommerce-product-recommendations' ) . '">' . ( $engine->get_name() ? esc_html( $engine->get_name() ) : esc_html__( '(untitled)', 'woocommerce-product-recommendations' ) ) . '</a>';
	}

	/**
	 * Handles the location column output.
	 *
	 * @param array $item
	 */
	public function column_wc_actions( $item ) {
		?>
		<p>
		<?php if ( ! wc_prl_is_cpt_hook( $item[ 'hook' ] ) ) { ?>
			<a class="button wc-action-button edit" href="<?php echo esc_url( admin_url( 'admin.php?page=prl_locations&section=deploy&deployment=' . $item[ 'id' ] ) ) ?>" aria-label="<?php esc_attr_e( 'Edit deployment', 'woocommerce-product-recommendations' ) ?>" title="<?php esc_attr_e( 'Edit', 'woocommerce-product-recommendations' ) ?>"><?php esc_html_e( 'Edit deployment', 'woocommerce-product-recommendations' ) ?></a>
		<?php } ?>
			<a id="<?php echo esc_attr( $item[ 'id' ] ); ?>" class="button wc-action-button wc-action-button-regenerate" href="#" aria-label="<?php esc_attr_e( 'Regenerate recommendations', 'woocommerce-product-recommendations' ) ?>" title="<?php esc_attr_e( 'Regenerate recommendations', 'woocommerce-product-recommendations' ) ?>"><?php esc_html_e( 'Regenerate recommendations', 'woocommerce-product-recommendations' ) ?></a>
			<a class="button wc-action-button delete" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=prl_locations&delete=' . $item[ 'id' ] ), 'wc_prl_delete_location_action', '_wc_prl_admin_nonce' ) ) ?>" aria-label="<?php esc_attr_e( 'Delete deployment', 'woocommerce-product-recommendations' ) ?>" title="<?php esc_attr_e( 'Delete', 'woocommerce-product-recommendations' ) ?>"><?php esc_html_e( 'Delete deployment', 'woocommerce-product-recommendations' ) ?></a>
		</p>
		<?php
	}

	/**
	 * Get a list of columns. The format is:
	 * 'internal-name' => 'Title'
	 */
	public function get_columns() {

		$columns                 = array();
		$columns[ 'cb' ]         = '<input type="checkbox" />';
		$columns[ 'title' ]      = _x( 'Title', 'column_name', 'woocommerce-product-recommendations' );
		$columns[ 'page' ]       = _x( 'Page', 'column_name', 'woocommerce-product-recommendations' );
		$columns[ 'location' ]   = _x( 'Location', 'column_name', 'woocommerce-product-recommendations' );
		$columns[ 'engine' ]     = _x( 'Engine', 'column_name', 'woocommerce-product-recommendations' );
		$columns[ 'wc_actions' ] = _x( 'Actions', 'column_name', 'woocommerce-product-recommendations' );

		return $columns;
	}

	public function get_sortable_columns() {
		$sortable_columns = array(
			'title'    => array( 'title', true ),
			'page'     => array( 'location_id', true ),
			'location' => array( 'hook', true ),
			'engine'   => array( 'engine_id', true )
		);

		return $sortable_columns;
	}

	protected function get_bulk_actions() {
		$actions           = array();
		$actions['delete'] = __( 'Delete Permanently', 'woocommerce-product-recommendations' );
		return $actions;
	}

	private function process_bulk_action() {

		if ( $this->current_action() ) {

			check_admin_referer( 'bulk-' . $this->_args['plural'] );

			$deployments = array();
			if ( isset( $_GET[ 'deployment' ] ) && is_array( $_GET[ 'deployment' ] ) ) {
				$deployments = array_map( 'absint', wc_clean( $_GET[ 'deployment' ] ) );
			}

			if ( ! empty( $deployments ) && 'delete' === $this->current_action() ) {

				foreach ( $deployments as $id ) {
					WC_PRL()->db->deployment->delete( $id );
				}

				WC_PRL_Admin_Notices::add_notice( __( 'Deployments deleted.', 'woocommerce-product-recommendations' ), 'success', true );
			}

			wp_safe_redirect( admin_url( self::PAGE_URL ) );
			exit();
		}
	}

	public function prepare_items() {

		$per_page = 10;

		// Table columns;
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->process_bulk_action();

		$total_items = WC_PRL()->db->deployment->count();

		$paged   = isset( $_REQUEST[ 'paged' ] ) ? max( 0, absint( $_REQUEST[ 'paged' ] ) - 1 ) : 0;
		$orderby = ( isset( $_REQUEST[ 'orderby' ] ) && in_array( $_REQUEST[ 'orderby' ], array_keys( $this->get_sortable_columns() ) ) ) ? sanitize_text_field( $_REQUEST[ 'orderby' ] ) : 'id';

		$order = ( isset( $_REQUEST[ 'order' ] ) && in_array( $_REQUEST[ 'order' ], array( 'asc', 'desc' ) ) ) ? sanitize_text_field( $_REQUEST[ 'order' ] ) : 'desc';

		// It's safe to ignore semgrep warning, as everything is properly escaped.
		// nosemgrep: audit.php.wp.security.sqli.input-in-sinks
		$this->items = WC_PRL()->db->deployment->query( array(
			'order_by' => array( $orderby => $order ),
			'limit'    => $per_page,
			'offset'   => $paged * $per_page
		) );

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
		// Show a boarding based on deployments and engines....
		$engines_count = wp_count_posts( 'prl_engine', 'readable' );
		if ( 0 < absint( $engines_count->publish ) ) {
			?><div class="prl-deployments-empty-state">
				<p class="main">
					<?php esc_html_e( 'Ready to start your Engines?', 'woocommerce-product-recommendations' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Deploy an Engine now to offer product recommendations at a Location of your store.', 'woocommerce-product-recommendations' ); ?>
				</p>
				<div class="quick-deploy__search" data-action="<?php echo esc_url( admin_url( 'admin.php?page=prl_locations&section=deploy&quick=1&engine=%%engine_id%%' ) ); ?>">
					<select class="wc-engine-search" data-swtheme="woo" data-placeholder="<?php esc_attr_e( 'Search for an Engine&hellip;', 'woocommerce-product-recommendations' ); ?>" data-limit="100" name="engine">
					</select>
				</div>
			</div><?php
		} else {
			?><div class="prl-engines-empty-state">
				<p class="main">
					<?php esc_html_e( 'Create an Engine', 'woocommerce-product-recommendations' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'Want to add product recommendations to a Location of your store?', 'woocommerce-product-recommendations' ); ?>
					<br/>
					<?php esc_html_e( 'Start by creating an Engine &mdash; then, return here to deploy it.', 'woocommerce-product-recommendations' ); ?>
				</p>
				<a class="button sw-button-primary sw-button-primary--woo" id="sw-button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=prl_engine' ) ); ?>"><?php esc_html_e( 'Create engine', 'woocommerce-product-recommendations' ); ?></a>
			</div><?php
		}
	}
}
