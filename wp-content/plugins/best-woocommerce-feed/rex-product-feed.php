<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://rextheme.com
 * @since             1.0.0
 * @package           Rex_Product_Feed
 *
 * @wordpress-plugin
 * Plugin Name:       Product Feed Manager for WooCommerce
 * Plugin URI:        https://rextheme.com
 * Description:       Generate and maintain your WooCommerce product feed for Google Shopping, Social Catalogs, Yandex, Idealo, Vivino, Pinterest, eBay MIP, BestPrice, Skroutz, Fruugo, Bonanza & 180+ Merchants.
 * Version:           7.2.25
 * Author:            RexTheme
 * Author URI:        https://rextheme.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       rex-product-feed
 * Domain Path:       /languages
 *
 * WP Requirement & Test
 * Requires at least: 4.7
 * Tested up to: 5.8.2
 * Requires PHP: 7.0
 *
 * WC Requirement & Test
 * WC requires at least: 3.2
 * WC tested up to: 5.6.0
 */



// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}
if( !defined( 'WPFM_VERSION' ) ) {
    define( 'WPFM_VERSION', '7.2.25' );
}
if( !defined( 'WPFM__FILE__' ) ) {
    define( 'WPFM__FILE__', __FILE__ );
}
if( !defined( 'WPFM_PLUGIN_BASE' ) && defined( 'WPFM__FILE__' ) ) {
    define( 'WPFM_PLUGIN_BASE', plugin_basename( WPFM__FILE__ ) );
}
if( !defined( 'WPFM_PLUGIN_DIR_URL' ) && defined( 'WPFM__FILE__' ) ) {
    define( "WPFM_PLUGIN_DIR_URL", plugin_dir_url( WPFM__FILE__ ) );
}
if( !defined( 'WPFM_PLUGIN_DIR_PATH' ) && defined( 'WPFM__FILE__' ) ) {
    define( "WPFM_PLUGIN_DIR_PATH", plugin_dir_path( WPFM__FILE__ ) );
}
if( !defined( 'WPFM_PLUGIN_ASSETS_FOLDER' ) && defined( 'WPFM_PLUGIN_DIR_URL' ) ) {
    define( "WPFM_PLUGIN_ASSETS_FOLDER", WPFM_PLUGIN_DIR_URL . 'admin/assets/' );
}
if( !defined( 'WPFM_PLUGIN_ASSETS_FOLDER_PATH' ) && defined( 'WPFM_PLUGIN_DIR_PATH' ) ) {
    define( "WPFM_PLUGIN_ASSETS_FOLDER_PATH", WPFM_PLUGIN_DIR_PATH . 'admin/assets/' );
}
if( !defined( 'WPFM_PRO_REQUIRED_VERSION' ) ) {
    define( 'WPFM_PRO_REQUIRED_VERSION', '6.3.3' );
}
if( !defined( 'WPFM_ETSY_REQUIRED_VERSION' ) ) {
    define( 'WPFM_ETSY_REQUIRED_VERSION', '1.0.1' );
}
if( !defined( 'WPFM_PRO' ) ) {
    define( 'WPFM_PRO', '/best-woocommerce-feed-pro/rex-product-feed-pro.php' );
}
if( !defined( 'WPFM_ETSY' ) ) {
    define( 'WPFM_ETSY', '/etsy-integration/etsy-integration.php' );
}
if( !defined( 'WPFM_FREE_MAX_PRODUCT_LIMIT' ) ) {
    define( 'WPFM_FREE_MAX_PRODUCT_LIMIT', 200 );
}
if( !defined( 'WPFM_SLUG' ) ) {
    define( 'WPFM_SLUG', 'best-woocommerce-feed' );
}
if( !defined( 'WPFM_BASE' ) && defined( 'WPFM__FILE__' ) ) {
    define( 'WPFM_BASE', plugin_basename( WPFM__FILE__ ) );
}


/**
 * Check if WooCommerce is active
 **/
function rex_is_woocommerce_active(){
    $all_plugins = get_option( 'active_plugins' );
    $woocommerce = 'woocommerce/woocommerce.php';
	return in_array( $woocommerce, $all_plugins);
}


/**
 * Check if WPFM Pro is compatible with new ui [version > 6.0.0]
 *
 * @return bool
 */
function wpfm_pro_compatibility() {
	if ( wpfm_get_plugin_version( WPFM_PRO ) ) {
		return ( wpfm_get_plugin_version( WPFM_PRO ) >= WPFM_PRO_REQUIRED_VERSION );
	}
	return false;
}


/**
 * Check if WPFM ETSY is compatible with new ui [version > 6.0.0]
 *
 * @return bool
 */
function wpfm_etsy_compatibility() {
	if ( wpfm_get_plugin_version( WPFM_ETSY ) ) {
		return ( wpfm_get_plugin_version( WPFM_ETSY ) >= WPFM_ETSY_REQUIRED_VERSION );
	}
	return false;
}


/**
 * Gets plugin version
 *
 * @param $file
 * @return mixed|string
 */
function wpfm_get_plugin_version( $file ) {
	$plugin_file = WP_PLUGIN_DIR . $file;

	if ( file_exists( $plugin_file ) && function_exists( 'get_file_data' ) ) {
		$plugin_data = get_file_data( $plugin_file, array('Version' => 'Version'), false );

		if ( $plugin_data && is_array( $plugin_data ) && isset( $plugin_data[ 'Version' ] ) ) {
			return $plugin_data[ 'Version' ];
		}
	}
	return false;
}


/**
 * Run dependency check and abort if required.
 **/
function rex_check_dependency(){
    $wpfm_pro_abs = WP_PLUGIN_DIR . WPFM_PRO;
    $wpfm_etsy_abs = WP_PLUGIN_DIR . WPFM_ETSY;

    if ( ! rex_is_woocommerce_active() ) {
        add_action( 'admin_init', 'rex_product_feed_deactivate' );
        add_action( 'admin_notices', 'rex_product_feed_admin_notice' );
    }

    if ( ( file_exists( $wpfm_pro_abs ) && ! wpfm_pro_compatibility() ) || ( file_exists( $wpfm_etsy_abs ) && ! wpfm_etsy_compatibility() ) ) {
	    add_action( 'admin_notices', 'wpfm_pro_update_notice' );
    }
}


/**
 * Prints a notice to update WPFM Pro [version > 6.7.5]
 */
function wpfm_pro_update_notice() {
    $wpfm_pro_abs = WP_PLUGIN_DIR . WPFM_PRO;
    $wpfm_etsy_abs = WP_PLUGIN_DIR . WPFM_ETSY;
    $wpfm_pro = file_exists( $wpfm_pro_abs ) && ! wpfm_pro_compatibility() ? '<strong>WooCommerce Product Feed Manager Pro</strong>' : '';
    $wpfm_etsy = file_exists( $wpfm_etsy_abs ) && ! wpfm_etsy_compatibility() ? '<strong>WooCommerce Product Feed Manager - Etsy Addon</strong>' : '';
    $and = file_exists( $wpfm_pro_abs ) && ! wpfm_pro_compatibility() && file_exists( $wpfm_etsy_abs ) && ! wpfm_etsy_compatibility() ? ' and ' : '';

    $message = __( 'It looks like you have an older version of ' . $wpfm_pro . $and . $wpfm_etsy . '. Please update ' . $wpfm_pro . $and . $wpfm_etsy . ' to the latest version to use <strong>Pro</strong> features properly.', 'rex-product-feed' );
	?>
	<div class="error">
        <p>
            <?php echo $message;?>
        </p>
	</div>
	<?php
}


/**
 * Display admin notice if WooCoomerce not activated
 **/
function rex_product_feed_admin_notice() {
    echo '<div class="error"><p><strong>WooCcommerce Product Feed Manager</strong> has been <strong>deactivated</strong>. Please install and activate <b>WooCoommerce</b> before activating this plugin.</p></div>';
    $activate = filter_input( INPUT_GET, 'activate', FILTER_SANITIZE_STRING );

    if ( $activate ){
        unset( $_GET['activate'] );
    }
}


/**
 * Force deactivate the plugin.
 **/
function rex_product_feed_deactivate() {
    deactivate_plugins( plugin_basename( __FILE__ ) );
}


/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-rex-product-feed-activator.php
 */
function activate_rex_product_feed() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-rex-product-feed-activator.php';
    if ( !rex_is_woocommerce_active() ) {
        // Stop activation redirect and show error
        wp_die('Sorry, but this plugin requires the WooCommerce Plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
    }
    else{
        Rex_Product_Feed_Activator::activate();
    }

}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-rex-product-feed-deactivator.php
 */
function deactivate_rex_product_feed() {
    require_once plugin_dir_path( __FILE__ ) . 'includes/class-rex-product-feed-deactivator.php';
    Rex_Product_Feed_Deactivator::deactivate();
}
register_activation_hook( __FILE__, 'activate_rex_product_feed' );
register_deactivation_hook( __FILE__, 'deactivate_rex_product_feed' );



/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-rex-product-feed.php';
require plugin_dir_path( __FILE__ ) . 'includes/helper.php';

function wpfm_plugin_redirect() {
    if (get_option('rex_wpfm_plugin_do_activation_redirect', false)) {
        delete_option('rex_wpfm_plugin_do_activation_redirect');
        $url = "admin.php?page=bwfm-dashboard";
        $url = filter_var( $url, FILTER_SANITIZE_URL );
        exit( wp_redirect( $url ) );
    }
}


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_rex_product_feed() {

    $plugin = new Rex_Product_Feed();
    $plugin->run();

	rex_check_dependency();
}
run_rex_product_feed();


/**
 * Initialize the tracker
 *
 * @return void
 */
function appsero_init_tracker_bwfm() {
    $client = new Appsero\Client( '5fab4a18-aaf4-4565-816a-47858011d96f', 'Product Feed Manager for WooCommerce', __FILE__ );

    // Active insights
    $client->insights()->init();
}

appsero_init_tracker_bwfm();


/**
 * is_edit_page
 * function to check if the current page is a post edit page
 *
 * @param  string  $new_edit what page to check for accepts new - new post page ,edit - edit post page, null for either
 * @return boolean
 */
function is_edit_page($new_edit = null){
    global $pagenow;
    if (!is_admin()) return false;
    if($new_edit == "edit")
        return in_array( $pagenow, array( 'post.php',  ) );
    elseif($new_edit == "new") //check for new post page
        return in_array( $pagenow, array( 'post-new.php' ) );
    else
        return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
}

/**
 * @param $pages
 * @return mixed
 */
function wpfm_top_pages_modify($pages) {
    global $typenow;
    if ( ( is_edit_page('edit') && "product-feed" === $typenow ) || ( is_edit_page('new') && "product-feed" === $typenow ) ){
        unset( $pages[0] );
        unset( $pages[1] );
    }
    return $pages;
}
add_filter('themify_top_pages', 'wpfm_top_pages_modify' );

function wpfm_plugin_major_update_message( $data, $response ) {
    if( isset( $data['upgrade_notice'] ) ) {
        printf(
            '<div class="update-message">%s</div>',
            wpautop( $data['upgrade_notice'] )
        );
    }
}
add_action( 'in_plugin_update_message-best-woocommerce-feed/rex-product-feed.php', 'wpfm_plugin_major_update_message', 10, 2 );

function rex_feed_redirect_after_activation( $plugin ) {
    if ( $plugin === plugin_basename( __FILE__ ) ) {
        $query_args = [
            'page' => 'setup-wizard',
            'plugin_activated' => 1
        ];
        $url = add_query_arg( urlencode_deep( $query_args ), esc_url( admin_url( 'admin.php' ) ) );
        exit( wp_redirect( $url ) );
    }
}
add_action( 'activated_plugin', 'rex_feed_redirect_after_activation' );