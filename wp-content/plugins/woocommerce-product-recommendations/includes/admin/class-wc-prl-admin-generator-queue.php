<?php
/**
 * WC_PRL_Admin_Generator_Queue class
 *
 * @package  WooCommerce Product Recommendations
 * @since    3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Admin_Locations Class.
 *
 * @class    WC_PRL_Admin_Generator_Queue
 * @version  3.0.0
 */
class WC_PRL_Admin_Generator_Queue {

	/**
	 * Page home URL.
     *
	 * @const PAGE_URL
	 */
	const PAGE_URL = 'admin.php?page=wc-status&tab=recommendations_queue';

	/**
	 * Deployments page.
	 *
	 * Handles the display of the pages list and the deployments accordion.
	 */
	public static function output() {

		self::handle_actions();

		$table        = new WC_PRL_Generator_Queue_List_Table();
		$stale_table  = new WC_PRL_Generator_Stale_Queue_List_Table();
		$table->prepare_items();
		$stale_table->prepare_items();

		self::render_queue_status_notice();
		include dirname( __FILE__ ) . '/views/html-admin-generator-queue.php';
	}

	/**
	 * Reports the status of the queue at any point.
	 */
	public static function render_queue_status_notice() {

		if ( WC_PRL()->deployments->queue->is_handled_via_wp_cli() ) {
			$notice_text = __( 'Product recommendations are currently generated and refreshed manually via WP CLI.', 'woocommerce-product-recommendations' );
		} elseif ( ! WC_PRL()->deployments->queue->is_queue_empty() ) {
			if ( WC_PRL()->deployments->queue->is_process_running() ) {
				$notice_text = __( 'Running', 'woocommerce-product-recommendations' );
			} else {
				$key = 'wp_' . get_current_blog_id() . 'wc_prl_generator_cron';
				$notice_text     = '';
				$next_occurrence = wp_next_scheduled( 'wp_1_wc_prl_generator_cron' );
				if ( $next_occurrence ) {
					// Calculate the time remaining in seconds
					$time_remaining = $next_occurrence - time();
					if ( 0 > $time_remaining ) {
						$notice_text = __( 'In queue', 'woocommerce-product-recommendations' );
					} else {
						// Convert seconds to hours, minutes, and seconds
						$hours = floor($time_remaining / 3600);
						$minutes = floor(($time_remaining % 3600) / 60);
						$seconds = $time_remaining % 60;

						$notice_text = sprintf( __( 'Next run in %d seconds','woocommerce-product-recommendations' ), ceil( $time_remaining % 60 ) );
					}
				}
			}
		}

		if ( isset( $notice_text ) ) {
			echo wp_kses_post( sprintf( '<div class="wc_prl_notice notice notice-info"><p>%s</p></div>', $notice_text ) );
		}
	}

	/**
	 * Handles page's actions.
	 */
	private static function handle_actions() {

		if ( isset( $_GET[ 'delete' ] ) ) {

			$admin_nonce = isset( $_GET[ '_wc_prl_admin_nonce' ] ) ? sanitize_text_field( $_GET[ '_wc_prl_admin_nonce' ] ) : '';

			if ( ! wp_verify_nonce( $admin_nonce, 'wc_prl_delete_generator_queue_item_action' ) ) {
				WC_PRL_Admin_Notices::add_notice( __( 'Queue item could not be deleted.', 'woocommerce-product-recommendations' ), 'error', true );
				wp_redirect( admin_url( self::PAGE_URL ) );
				exit();
			}

			$key_to_delete = wc_clean( $_GET[ 'delete' ] );

			WC_PRL()->deployments->queue->delete( $key_to_delete );

			WC_PRL_Admin_Notices::add_notice( __( 'Queue item deleted.', 'woocommerce-product-recommendations' ), 'success', true );

			wp_redirect( admin_url( self::PAGE_URL ) );
			exit();

		} elseif ( isset( $_GET[ 'reset' ] ) ) {

			$admin_nonce = isset( $_GET[ '_wc_prl_admin_nonce' ] ) ? sanitize_text_field( $_GET[ '_wc_prl_admin_nonce' ] ) : '';

			if ( ! wp_verify_nonce( $admin_nonce, 'wc_prl_reset_generator_queue_item_action' ) ) {
				WC_PRL_Admin_Notices::add_notice( __( 'Queue item could not be resetted.', 'woocommerce-product-recommendations' ), 'error', true );
				wp_redirect( admin_url( self::PAGE_URL ) );
				exit();
			}

			$key_to_reset = wc_clean( $_GET[ 'reset' ] );
			WC_PRL()->deployments->queue->set_number_of_iterations( $key_to_reset, 0 );
			WC_PRL_Admin_Notices::add_notice( __( 'Queue item resetted.', 'woocommerce-product-recommendations' ), 'success', true );

			wp_redirect( admin_url( self::PAGE_URL ) );
			exit();
		} elseif ( isset( $_GET[ 'dispatch' ] ) ) {

			$admin_nonce = isset( $_GET[ '_wc_prl_admin_nonce' ] ) ? sanitize_text_field( $_GET[ '_wc_prl_admin_nonce' ] ) : '';

			if ( ! wp_verify_nonce( $admin_nonce, 'wc_prl_dispatch_generator_queue_item_action' ) ) {
				WC_PRL_Admin_Notices::add_notice( __( 'The item could not be dispatched.', 'woocommerce-product-recommendations' ), 'error', true );
				wp_redirect( admin_url( self::PAGE_URL ) );
				exit();
			}

			$key_to_dispatch = wc_clean( $_GET[ 'dispatch' ] );
			WC_PRL()->deployments->queue->dispatch_single_item( $key_to_dispatch );
			WC_PRL_Admin_Notices::add_notice( __( 'The item has been dispatched.', 'woocommerce-product-recommendations' ), 'success', true );
			
			wp_redirect( admin_url( self::PAGE_URL ) );
			exit();
        }
	}
}
