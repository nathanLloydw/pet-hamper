<?php
/**
 * WC_PRL_CLI_Process_Generation_Queue class
 *
 * @package  WooCommerce Product Recommendations
 * @since    3.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allows processing the generation queue via WP-CLI.
 *
 * @class    WC_PRL_CLI_Process_Generation_Queue
 * @version  3.0.0
 */
class WC_PRL_CLI_Process_Generation_Queue {

	/**
	 * Registers the update command.
	 */
	public static function register_command() {
		WP_CLI::add_command( 'wc prl process-generation-queue', array( 'WC_PRL_CLI_Process_Generation_Queue', 'process' ) );
	}

	/**
	 * Runs through the queue and generates product recommendations.
	 */
	public static function process() {

		require_once  WC_PRL_ABSPATH . 'includes/class-wc-prl-generator.php' ;
		require_once  WC_PRL_ABSPATH . 'includes/class-wc-prl-generator-queue.php' ;

		$queue = new WC_PRL_Generator_Queue();
		$queue->handle();
	}
}
