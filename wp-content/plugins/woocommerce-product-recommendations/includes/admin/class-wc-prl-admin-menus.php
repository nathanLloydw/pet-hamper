<?php
/**
 * WC_PRL_Admin_Menus class
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Admin\Features\Navigation\Menu;
use Automattic\WooCommerce\Admin\Features\Navigation\Screen;

/**
 * Setup PRL menus in WP admin.
 *
 * @class    WC_PRL_Admin_Menus
 * @version  3.0.0
 */
class WC_PRL_Admin_Menus {

	/**
	 * The CSS classes used to hide the submenu items in navigation.
	 *
	 * @var string
	 */
	protected static $HIDE_CSS_CLASS = 'hide-if-js';

	/**
	 * Setup.
	 */
	public static function init() {
		self::add_hooks();
	}

	/**
	 * Admin hooks.
	 */
	public static function add_hooks() {

		// Tabs.
		add_action( 'all_admin_notices', array( __CLASS__, 'render_tabs' ), 5 );

		// Menu.
		add_action( 'admin_menu', array( __CLASS__, 'prl_menu' ), 10 );
		add_filter( 'parent_file', array( __CLASS__, 'prl_fix_menu_highlight' ) );

		// Integrate WooCommerce breadcrumb bar.
		add_action( 'admin_menu', array( __CLASS__, 'wc_admin_connect_prl_pages' ) );
		add_filter( 'woocommerce_navigation_pages_with_tabs', array( __CLASS__, 'wc_admin_navigation_pages_with_tabs' ) );
		add_filter( 'woocommerce_navigation_page_tab_sections', array( __CLASS__, 'wc_admin_navigation_page_tab_sections' ) );
		add_filter( 'woocommerce_navigation_screen_ids', array( __CLASS__, 'wc_admin_navigation_screen_ids' ) );

		// Integrate WooCommerce side navigation.
		add_action( 'admin_menu', array( __CLASS__, 'register_navigation_pages' ) );
		add_action( 'woocommerce_navigation_core_excluded_items', array( __CLASS__, 'exclude_navigation_items' ) );
	}

	/**
	 * Configure giftcard tabs.
	 *
	 * @param  array  $pages
	 * @return array
	 */
	public static function wc_admin_navigation_page_tab_sections( $pages ) {
		$pages[ 'prl' ] = array( 'section' );
		return $pages;
	}

	/**
	 * Configure giftcard page sections.
	 *
	 * @param  array  $pages
	 * @return array
	 */
	public static function wc_admin_navigation_pages_with_tabs( $pages ) {
		$pages[ 'prl_locations' ] = 'prl';
		return $pages;
	}

	/**
	 * Add screen id to WooCommerce.
	 *
	 * @since 3.0.0
	 * @param  array  $screen_ids  List of screen IDs.
	 * @return array
	 */
	public static function wc_admin_navigation_screen_ids( $screen_ids ) {
		$screen_ids = array_merge( $screen_ids, WC_PRL()->get_screen_ids() );

		return $screen_ids;
	}

	/**
	 * Connect pages with navigation bar.
	 *
	 * @return void
	 */
	public static function wc_admin_connect_prl_pages() {

		if ( function_exists( 'wc_admin_connect_page' ) ) {

			wc_admin_connect_page(
				array(
					'id'        => 'woocommerce-product-recommendations',
					'screen_id' => wc_prl_get_formatted_screen_id( 'woocommerce_page_prl_performance' ),
					'title'     => __( 'Recommendations', 'woocommerce-product-recommendations' ),
					'path'      => add_query_arg(
						array(
							'page' => 'prl_performance'
						),
						'admin.php'
					)
				)
			);

			wc_admin_connect_page(
				array(
					'id'        => 'woocommerce-product-locations',
					'parent'    => 'woocommerce-product-recommendations',
					'screen_id' => wc_prl_get_formatted_screen_id( 'woocommerce_page_prl_locations' ) . '-prl',
					'title'     => __( 'Locations', 'woocommerce-product-recommendations' ),
					'path'      => add_query_arg(
						array(
							'page' => 'prl_locations'
						),
						'admin.php'
					)
				)
			);

			if ( ! empty( $_GET[ 'location' ] ) ) {
				$location = WC_PRL()->locations->get_location( sanitize_text_field( $_GET[ 'location' ] ) );
			}

			wc_admin_connect_page(
				array(
					'id'        => 'woocommerce-product-locations-hooks',
					'parent'    => 'woocommerce-product-locations',
					'screen_id' => wc_prl_get_formatted_screen_id( 'woocommerce_page_prl_locations' ) . '-prl-hooks',
					'title'     => isset( $location ) && is_a( $location, 'WC_PRL_Location' ) ? $location->get_title()  : __( 'View Location', 'woocommerce-product-recommendations' )
				)
			);

			$posttype_list_base = 'edit.php';

			// WooCommerce > Engines.
			wc_admin_connect_page(
				array(
					'id'        => 'woocommerce-engines',
					'parent'    => 'woocommerce-product-recommendations',
					'screen_id' => wc_prl_get_formatted_screen_id( 'edit-prl_engine' ),
					'title'     => __( 'Engines', 'woocommerce-product-recommendations' ),
					'path'      => add_query_arg( 'post_type', 'prl_engine', $posttype_list_base ),
				)
			);

			// WooCommerce > Engines > Add New.
			wc_admin_connect_page(
				array(
					'id'        => 'woocommerce-add-engine',
					'parent'    => 'woocommerce-engines',
					'screen_id' => wc_prl_get_formatted_screen_id( 'prl_engine' ) . '-add',
					'title'     => __( 'Add New', 'woocommerce-product-recommendations' ),
				)
			);

			// WooCommerce > Engines > Edit Engine.
			wc_admin_connect_page(
				array(
					'id'        => 'woocommerce-edit-engine',
					'parent'    => 'woocommerce-engines',
					'screen_id' => wc_prl_get_formatted_screen_id( 'prl_engine' ),
					'title'     => __( 'Edit Engine', 'woocommerce-product-recommendations' ),
				)
			);
		}
	}

	/**
	 * Renders tabs on our custom post types pages.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 */
	public static function render_tabs() {
		$screen = get_current_screen();

		// Handle tabs on the relevant WooCommerce pages.
		if ( $screen && ! in_array( $screen->id, WC_PRL()->get_screen_ids(), true ) ) {
			return;
		}

		$tabs = array();

		if ( wc_prl_tracking_enabled() ) {
			$tabs[ 'performance' ] = array(
				'title' => __( 'Performance', 'woocommerce-product-recommendations' ),
				'url'   => admin_url( 'admin.php?page=prl_performance' ),
			);
		}

		$tabs[ 'engines' ] = array(
			'title' => __( 'Engines', 'woocommerce-product-recommendations' ),
			'url'   => admin_url( 'edit.php?post_type=prl_engine' ),
		);

		$tabs[ 'locations' ] = array(
			'title' => __( 'Locations', 'woocommerce-product-recommendations' ),
			'url'   => admin_url( 'admin.php?page=prl_locations' ),
		);

		if ( ! wc_prl_tracking_enabled() ) {
			$tabs = array_reverse( $tabs );
		}

		$tabs = apply_filters( 'woocommerce_prl_admin_tabs', $tabs );

		if ( is_array( $tabs ) ) {
			?>
			<div class="wrap woocommerce">
				<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
					<?php $current_tab = self::get_current_tab(); ?>
					<?php foreach ( $tabs as $tab_id => $tab ) : ?>
						<?php $class = $tab_id === $current_tab ? array( 'nav-tab', 'nav-tab-active' ) : array( 'nav-tab' ); ?>
						<?php printf( '<a href="%1$s" class="%2$s">%3$s</a>', esc_url( $tab[ 'url' ] ), implode( ' ', array_map( 'sanitize_html_class', $class ) ), esc_html( $tab[ 'title' ] ) ); ?>
					<?php endforeach; ?>
				</nav>
			</div>
			<?php
		}
	}

	/**
	 * Returns the current admin tab.
	 *
	 * @param  string  $current_tab
	 * @return string
	 */
	public static function get_current_tab( $current_tab = 'performance' ) {

		$screen = get_current_screen();
		if ( $screen ) {
			if ( in_array( $screen->id, array( 'prl_engine', 'edit-prl_engine' ), true ) ) {
				$current_tab = 'engines';
			} elseif ( in_array( $screen->id, array( wc_prl_get_formatted_screen_id( 'woocommerce_page_prl_locations' ) ), true ) ) {
				$current_tab = 'locations';
			} elseif ( in_array( $screen->id, array( wc_prl_get_formatted_screen_id( 'woocommerce_page_prl_performance' ) ), true ) ) {
				$current_tab = 'performance';
			}
		}

		/**
		 * Filters the current Recommendations Admin tab.
		 *
		 * @param  string    $current_tab
		 * @param  WP_Screen $screen
		 */
		return (string) apply_filters( 'woocommerce_prl_admin_current_tab', $current_tab, $screen );
	}

	/**
	 * Fix the active menu item when editing/creating an Engine.
	 */
	public static function prl_fix_menu_highlight() {
		global $parent_file, $submenu_file;

		if ( WC_PRL()->is_current_screen() ) {
			$submenu_file = wc_prl_tracking_enabled() ? 'prl_performance' : 'prl_locations';
			$parent_file  = 'woocommerce';
		}

		return $parent_file;
	}

	/**
	 * Add menu items.
	 */
	public static function prl_menu() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return false;
		}

		$tracking_enabled = wc_prl_tracking_enabled();

		if ( $tracking_enabled ) {
			add_submenu_page( 'woocommerce', __( 'Performance', 'woocommerce-product-recommendations' ), __( 'Recommendations', 'woocommerce-product-recommendations' ), 'manage_woocommerce', 'prl_performance', array( __CLASS__, 'performance_page' ) );
		}

		$locations_page = add_submenu_page( 'woocommerce', __( 'Locations', 'woocommerce-product-recommendations' ), wc_prl_tracking_enabled() ? null : __( 'Recommendations', 'woocommerce-product-recommendations' ), 'manage_woocommerce', 'prl_locations', array( __CLASS__, 'locations_page' ) );

		// If tracking is disabled, then the entrypoint would be the locations page, so we do not want to hide it.
		if ( $tracking_enabled ) {
			self::hide_submenu_page( 'woocommerce', 'prl_locations' );
		}

		add_action( 'load-' . $locations_page, array( __CLASS__, 'locations_page_init' ) );

		add_filter( 'woocommerce_admin_status_tabs', array( __CLASS__, 'add_generator_queue_page_tab' ) );
		add_action( 'woocommerce_admin_status_content_recommendations_queue', array( __CLASS__, 'generator_queue_page' ) );
	}

	/**
	 * Add Recommendations Queue tab in WooCommerce status page.
	 * 
	 * @since 3.0.0
	 * @param  array  $tabs
	 * @return array
	 */
	public static function add_generator_queue_page_tab( $tabs ) {
		if ( ! is_array( $tabs ) ) {
			return $tabs;
		}

		$tabs[ 'recommendations_queue' ] = __('Recommendations Queue','woocommerce-product-recommendations');
		return $tabs;
	}

	/**
	 * Init admin locations page. Setups the `save` feature and adds messages.
	 */
	public static function locations_page_init() {

		if ( ! empty( $_POST ) ) {

			$section = 'locations_overview';

			if ( isset( $_GET[ 'section' ] ) ) {
				$section = wc_clean( $_GET[ 'section' ] );
			}

			switch ( $section ) {
				case 'deploy':
					WC_PRL_Admin_Deploy::save();
					break;
				case 'hooks':
					WC_PRL_Admin_Hooks::save();
					break;
				case 'locations_overview':
					WC_PRL_Admin_Locations::save();
					break;
			}
		}

		do_action( 'woocommerce_prl_locations_page_init' );
	}

	/**
	 * Render "Performance" page.
	 */
	public static function performance_page() {
		WC_PRL_Admin_Performance::output();
	}

	/**
	 * Render "Locations" page.
	 */
	public static function locations_page() {

		$section = 'locations_overview';

		if ( isset( $_GET[ 'section' ] ) ) {
			$section = wc_clean( $_GET[ 'section' ] );
		}

		switch ( $section ) {
			case 'deploy':
				WC_PRL_Admin_Deploy::output();
				break;
			case 'hooks':
				WC_PRL_Admin_Hooks::output();
				break;
			case 'locations_overview':
				WC_PRL_Admin_Locations::output();
				break;
		}
	}

	/**
	 * Render "Generator Queue" page.
	 */
	public static function generator_queue_page() {
		WC_PRL_Admin_Generator_Queue::output();
	}

	/**
	 * Render "Deploy" page.
	 */
	public static function deploy_page() {
		WC_PRL_Admin_Deploy::output();
	}

	/**
	 * Render "Hooks" page.
	 */
	public static function hooks_page() {
		WC_PRL_Admin_Hooks::output();
	}

	/**
	 * Exclude menu items from WooCommerce menu migration.
	 *
	 * @since  1.4.11
	 *
	 * @param  array $excluded_items
	 * @return array
	 */
	public static function exclude_navigation_items( $excluded_items ) {
		$excluded_items[] = 'prl_performance';
		$excluded_items[] = 'prl_locations';
		$excluded_items[] = 'prl_generator_queue';

		return $excluded_items;
	}

	/**
	 * Register WooCommerce menu pages.
	 *
	 * @since  1.4.11
	 *
	 * @return void
	 */
	public static function register_navigation_pages() {

		if ( ! class_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Menu' ) || ! class_exists( '\Automattic\WooCommerce\Admin\Features\Navigation\Screen' ) ) {
			return;
		}

		Menu::add_plugin_category(
			array(
				'id'     => 'prl-recommendations-category',
				'title'  => __( 'Recommendations', 'woocommerce-product-recommendations' ),
				'parent' => 'woocommerce',
			)
		);

		Menu::add_plugin_item(
			array(
				'id'         => 'prl-performance',
				'title'      => __( 'Performance', 'woocommerce-product-recommendations' ),
				'capability' => 'manage_woocommerce',
				'url'        => 'admin.php?page=prl_performance',
				'parent'     => 'prl-recommendations-category',
				'order'      => 10
			)
		);

		$match_expression = isset( $_GET[ 'post' ] ) && get_post_type( intval( $_GET[ 'post' ] ) ) === 'prl_engine'
			? '(edit.php|post.php)'
			: null;
		if ( is_null( $match_expression ) ) {
			$match_expression = isset( $_GET[ 'post_type' ] ) && $_GET[ 'post_type' ] === 'prl_engine'
			? '(post-new.php)'
			: null;
		}

		Menu::add_plugin_item(
			array(
				'id'              => 'prl-engines',
				'title'           => __( 'Engines', 'woocommerce-product-recommendations' ),
				'capability'      => 'manage_woocommerce',
				'url'             => 'edit.php?post_type=prl_engine',
				'parent'          => 'prl-recommendations-category',
				'matchExpression' => $match_expression,
				'order'           => 20
			)
		);

		Menu::add_plugin_item(
			array(
				'id'         => 'prl-locations',
				'title'      => __( 'Locations', 'woocommerce-product-recommendations' ),
				'capability' => 'manage_woocommerce',
				'url'        => 'admin.php?page=prl_locations',
				'parent'     => 'prl-recommendations-category',
				'order'      => 30
			)
		);

		Screen::register_post_type( 'prl_engine' );
	}

	/**
	 * Hide the submenu page based on slug and return the item that was hidden.
	 *
	 * @since 2.4.0
	 *
	 * Instead of actually removing the submenu item, a safer approach is to hide it and filter it in the API response.
	 * In this manner we'll avoid breaking third-party plugins depending on items that no longer exist.
	 *
	 * @param string $menu_slug The parent menu slug.
	 * @param string $submenu_slug The submenu slug that should be hidden.
	 * @return false|array
	 */
	protected static function hide_submenu_page( $menu_slug, $submenu_slug ) {
		global $submenu;

		if ( ! isset( $submenu[ $menu_slug ] ) ) {
			return false;
		}

		foreach ( $submenu[ $menu_slug ] as $i => $item ) {
			if ( $submenu_slug !== $item[2] ) {
				continue;
			}

			self::hide_submenu_element( $i, $menu_slug, $item );

			return $item;
		}

		return false;
	}

	/**
	 * Apply the hide-if-js CSS class to a submenu item.
	 *
	 * @since 2.4.0
	 * 
	 * @param int    $index The position of a submenu item in the submenu array.
	 * @param string $parent_slug The parent slug.
	 * @param array  $item The submenu item.
	 */
	protected static function hide_submenu_element( $index, $parent_slug, $item ) {
		global $submenu;

		$css_classes = empty( $item[4] ) ? self::$HIDE_CSS_CLASS : $item[4] . ' ' . self::$HIDE_CSS_CLASS;

		// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		$submenu[ $parent_slug ][ $index ][4] = $css_classes;
	}

	/*
	|--------------------------------------------------------------------------
	| Deprecated methods.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Removes multiple submenu links for Recommendations that are not being used as a menu item.
	 * 
	 * @deprecated
	 */
	public static function prl_remove_submenu_link() {
		_deprecated_function( __METHOD__ . '()', '2.4.0' );
	}
}

WC_PRL_Admin_Menus::init();
