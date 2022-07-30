<?php
/**
 * WC_PRL_Admin_Analytics class
 *
 * @package  WooCommerce Product Recommendations
 * @since    2.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Recommendations WooCommerce Admin Analytics.
 *
 * @version 2.0.0
 */
class WC_PRL_Admin_Analytics {

	/*
	 * Init.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'setup' ) );
	}

	/*
	 * Setup Analytics.
	 */
	public static function setup() {

		if ( ! self::is_enabled() ) {
			return;
		}

		self::includes();

		// Analytics init.
		add_filter( 'woocommerce_analytics_report_menu_items', array( __CLASS__, 'add_report_menu_item' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'register_script' ) );

		// Define custom woocommerce_meta keys.
		add_filter( 'woocommerce_admin_get_user_data_fields', array( __CLASS__, 'add_user_data_fields' ) );

		// REST API Controllers.
		add_filter( 'woocommerce_admin_rest_controllers', array( __CLASS__, 'add_rest_api_controllers' ) );

		// Register data stores.
		add_filter( 'woocommerce_data_stores', array( __CLASS__, 'register_data_stores' ) );

		add_filter( 'woocommerce_admin_shared_settings', array( __CLASS__, 'add_component_settings' ) );
	}

	/**
	 * Injects custom script data into `getSetting(wc_admin)`.
	 *
	 * @return void
	 */
	public static function add_component_settings( $settings ) {
		if ( ! is_admin() ) {
			return $settings;
		}

		$location_options = array();
		foreach ( WC_PRL()->locations->get_locations( 'view' ) as $location ) {
				foreach ( $location->get_hooks() as $hook => $data ) {
					$hook_hash = substr( md5( $hook ), 0, 7 );
					$location_options[] = array(
						'label' => $location->get_title() . ' > ' . $data[ 'label' ],
						'value' => $hook_hash,
						'key'   => $hook_hash
					);
				}
		}
		$settings[ 'prlLocationOptions' ] = $location_options;

		return $settings;
	}

	/**
	 * Includes.
	 *
	 * @return void
	 */
	protected static function includes() {

		// Global.
		require_once WC_PRL_ABSPATH . '/includes/admin/analytics/reports/class-wc-prl-analytics-data-store.php';

		// Revenue.
		require_once WC_PRL_ABSPATH . '/includes/admin/analytics/reports/revenue/class-wc-prl-analytics-revenue-rest-controller.php';
		require_once WC_PRL_ABSPATH . '/includes/admin/analytics/reports/revenue/class-wc-prl-analytics-revenue-data-store.php';
		require_once WC_PRL_ABSPATH . '/includes/admin/analytics/reports/revenue/class-wc-prl-analytics-revenue-query.php';
	}


	/**
	 * Add "Recommendations" as a Analytics submenu item.
	 *
	 * @param  array  $report_pages  Report page menu items.
	 * @return array
	 */
	public static function add_report_menu_item( $report_pages ) {

		$prl_report = array( array(
			'id'     => 'wc-prl-recommendations-analytics-report',
			'title'  => __( 'Recommendations', 'woocommerce-product-recommendations' ),
			'parent' => 'woocommerce-analytics',
			'path'   => '/analytics/recommendations',
			'nav_args' => array(
				'order'  => 110,
				'parent' => 'woocommerce-analytics',
			),
		) );

		// Make sure that we are at least above the "Setting" menu item.
		array_splice( $report_pages, count( $report_pages ) - 1, 0, $prl_report );

		return $report_pages;
	}

	/**
	 * Register analytics JS.
	 */
	public static function register_script() {
		if ( ! WC_PRL_Core_Compatibility::is_admin_or_embed_page() ) {
			return;
		}

		$suffix            = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$script_path       = '/assets/dist/admin/analytics' . $suffix . '.js';
		$script_asset_path = WC_PRL_ABSPATH . 'assets/dist/admin/analytics.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require( $script_asset_path )
			: array(
				'dependencies' => array(),
				'version'      => WC_PRL()->get_plugin_version()
			);
		$script_url        = WC_PRL()->get_plugin_url() . $script_path;

		wp_register_script(
			'wc-prl-recommendations-analytics-report',
			$script_url,
			$script_asset[ 'dependencies' ],
			$script_asset[ 'version' ],
			true
		);

		// Load JS translations.
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'wc-prl-recommendations-analytics-report', 'woocommerce-product-recommendations', WC_PRL_ABSPATH . 'languages/' );
		}

		// Enqueue script.
		wp_enqueue_script( 'wc-prl-recommendations-analytics-report' );
	}

	/**
	 * Adds fields so that we can store user preferences for the columns to display on a report.
	 *
	 * @param array $user_data_fields User data fields.
	 * @return array
	 */
	public static function add_user_data_fields( $user_data_fields ) {
		return array_merge(
			$user_data_fields,
			array(
				'prl_revenue_report_columns',
			)
		);
	}

	/**
	 * Analytics includes and register REST contollers.
	 *
	 * @param  array  $controllers
	 * @return array
	 */
	public static function add_rest_api_controllers( $controllers ) {
		$controllers[] = 'WC_PRL_Analytics_Revenue_REST_Controller';
		return $controllers;
	}

	/**
	 * Register Analytics data stores.
	 *
	 * @param  array  $stores
	 * @return array
	 */
	public static function register_data_stores( $stores ) {
		$stores[ 'report-recommendations-revenue' ]        = 'WC_PRL_Analytics_Revenue_Data_Store';
		return $stores;
	}

	/**
	 * Whether or not the new Analytics reports are enabled.
	 *
	 * @return bool
	 */
	public static function is_enabled() {
		$enabled                   = WC_PRL_Core_Compatibility::is_wc_admin_enabled();
		$minimum_wc_admin_required = $enabled && defined( 'WC_ADMIN_VERSION_NUMBER' ) && version_compare( WC_ADMIN_VERSION_NUMBER, '1.6.1', '>=' );
		$minimum_wc_required       = WC_PRL_Core_Compatibility::is_wc_version_gte( '4.8' );

		$is_enabled = $enabled && $minimum_wc_required && $minimum_wc_admin_required;
		return (bool) apply_filters( 'woocommerce_prl_analytics_enabled', $is_enabled );
	}
}

WC_PRL_Admin_Analytics::init();
