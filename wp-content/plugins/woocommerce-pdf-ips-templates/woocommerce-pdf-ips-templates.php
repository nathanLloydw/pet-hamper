<?php
/**
 * Plugin Name: WooCommerce PDF Invoices & Packing Slips Premium Templates
 * Plugin URI: http://www.wpovernight.com
 * Description: Premium templates for the WooCommerce PDF Invoices & Packing Slips extension
 * Version: 2.9.3
 * Author: Ewout Fernhout
 * Author URI: http://www.wpovernight.com
 * License: GPLv2 or later
 * License URI: http://www.opensource.org/licenses/gpl-license.php
 * Text Domain: wpo_wcpdf_templates
 * WC requires at least: 2.2.0
 * WC tested up to: 4.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'WPO_WCPDF_Templates' ) ) :

class WPO_WCPDF_Templates {

	public $version = '2.9.3';
	public $plugin_basename;

	protected static $_instance = null;

	/**
	 * Main Plugin Instance
	 *
	 * Ensures only one instance of plugin is loaded or can be loaded.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->plugin_basename = plugin_basename(__FILE__);

		$this->define( 'WPO_WCPDF_TEMPLATES_VERSION', $this->version );

		// load the localisation & classes
		add_action( 'plugins_loaded', array( $this, 'translations' ) );
		add_action( 'wpo_wcpdf_reload_attachment_translations', array( $this, 'translations' ) );
		add_action( 'wpo_wcpdf_reload_text_domains', array( $this, 'translations' ) );
		add_action( 'init', array( $this, 'load_classes' ) );

		// Load the updater
		add_action( 'init', array( $this, 'load_updater' ), 0 );

		// run lifecycle methods
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			add_action( 'wp_loaded', array( $this, 'do_install' ) );
		}
	}

	/**
	 * Define constant if not already set
	 * @param  string $name
	 * @param  string|bool $value
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Load the translation / textdomain files
	 * 
	 * Note: the first-loaded translation file overrides any following ones if the same translation is present
	 */
	public function translations() {
		if ( function_exists( 'determine_locale' ) ) { // WP5.0+
			$locale = determine_locale();
		} else {
			$locale = is_admin() && function_exists( 'get_user_locale' ) ? get_user_locale() : get_locale();
		}
		$locale = apply_filters( 'plugin_locale', $locale, 'woocommerce-pdf-invoices-packing-slips' );
		$dir    = trailingslashit( WP_LANG_DIR );

		/**
		 * Frontend/global Locale. Looks in:
		 *
		 * 		- WP_LANG_DIR/woocommerce-pdf-ips-templates/wpo_wcpdf_templates-LOCALE.mo
		 * 	 	- WP_LANG_DIR/plugins/wpo_wcpdf_templates-LOCALE.mo
		 * 	 	- woocommerce-pdf-ips-templates/languages/wpo_wcpdf_templates-LOCALE.mo (which if not found falls back to:)
		 * 	 	- WP_LANG_DIR/plugins/wpo_wcpdf_templates-LOCALE.mo
		 */
		if ( in_array( current_filter(), array( 'wpo_wcpdf_reload_attachment_translations', 'wpo_wcpdf_reload_text_domains' ) ) ) {
			unload_textdomain( 'wpo_wcpdf_templates' );
		}
		load_textdomain( 'wpo_wcpdf_templates', $dir . 'woocommerce-pdf-ips-templates/wpo_wcpdf_templates-' . $locale . '.mo' );
		load_textdomain( 'wpo_wcpdf_templates', $dir . 'plugins/wpo_wcpdf_templates-' . $locale . '.mo' );
		load_plugin_textdomain( 'wpo_wcpdf_templates', false, dirname( plugin_basename(__FILE__) ) . '/languages' );
	}

	/**
	 * Load the main plugin classes and functions
	 */
	public function includes() {
		// compatibility classes
		include_once( 'includes/compatibility/abstract-wc-data-compatibility.php' );
		include_once( 'includes/compatibility/class-wc-date-compatibility.php' );
		include_once( 'includes/compatibility/class-wc-core-compatibility.php' );
		include_once( 'includes/compatibility/class-wc-order-compatibility.php' );
		include_once( 'includes/compatibility/class-wc-product-compatibility.php' );
		include_once( 'includes/compatibility/wc-datetime-functions-compatibility.php' );

		$this->settings = include_once( 'includes/class-wcpdf-templates-settings.php' );
		$this->main = include_once( 'includes/class-wcpdf-templates-main.php' );
		include_once( 'includes/wcpdf-templates-functions.php' );

		// Backwards compatibility with self
		include_once( 'includes/legacy/class-wcpdf-templates-legacy.php' );
	}

	/**
	 * Instantiate classes when woocommerce is activated
	 */
	public function load_classes() {
		if ( $this->is_woocommerce_activated() === false ) {
			add_action( 'admin_notices', array ( $this, 'need_woocommerce' ) );
			return;
		}

		if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
			add_action( 'admin_notices', array ( $this, 'required_php_version' ) );
			return;
		}

		if ( $this->is_base_plugin_activated() === false ) {
			add_action( 'admin_notices', array ( $this, 'base_plugin_requirement' ) );
			return;
		}

		// all systems ready - GO!
		$this->includes();
	}

	/**
	 * Check if base plugin is activated and 2.0+
	 */
	public function is_base_plugin_activated() {
		if ( class_exists('WooCommerce_PDF_Invoices') && version_compare( WooCommerce_PDF_Invoices::$version, '2.0-dev', '<' ) ) {
			return false;
		} elseif ( function_exists('WPO_WCPDF') && version_compare( WPO_WCPDF()->version, '2.0-dev', '>=' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if woocommerce is activated
	 */
	public function is_woocommerce_activated() {
		$blog_plugins = get_option( 'active_plugins', array() );
		$site_plugins = is_multisite() ? (array) maybe_unserialize( get_site_option('active_sitewide_plugins' ) ) : array();

		if ( in_array( 'woocommerce/woocommerce.php', $blog_plugins ) || isset( $site_plugins['woocommerce/woocommerce.php'] ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * WooCommerce not active notice.
	 *
	 * @return string Fallack notice.
	 */
	 
	public function need_woocommerce() {
		$error = sprintf( __( 'WooCommerce PDF Invoices & Packing Slips requires %sWooCommerce%s to be installed & activated!' , 'woocommerce-pdf-invoices-packing-slips' ), '<a href="http://wordpress.org/extend/plugins/woocommerce/">', '</a>' );
		
		$message = '<div class="error"><p>' . $error . '</p></div>';
	
		echo $message;
	}

	/**
	 * PHP version requirement notice
	 */
	
	public function required_php_version() {
		$error = __( 'WooCommerce PDF Invoices & Packing Slips requires PHP 5.3 or higher (5.6 or higher recommended).', 'woocommerce-pdf-invoices-packing-slips' );
		$how_to_update = __( 'How to update your PHP version', 'woocommerce-pdf-invoices-packing-slips' );
		$message = sprintf('<div class="error"><p>%s</p><p><a href="%s">%s</a></p></div>', $error, 'http://docs.wpovernight.com/general/how-to-update-your-php-version/', $how_to_update);
	
		echo $message;
	}

	/**
	 * Base Plugin not active or 2.0+ notice.
	 *
	 * @return string Fallack notice.
	 */
	 
	public function base_plugin_requirement() {
		$error = sprintf( __( 'WooCommerce PDF Invoices & Packing Slips Premium Templates requires at least version 2.0 of WooCommerce PDF Invoices & Packing Slips - get it %shere%s!' , 'wpo_wcpdf_templates' ), '<a href="https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/">', '</a>' );

		$message = '<div class="error"><p>' . $error . '</p></div>';
	
		echo $message;
	}

	/** Lifecycle methods *******************************************************
	 * Because register_activation_hook only runs when the plugin is manually
	 * activated by the user, we're checking the current version against the
	 * version stored in the database
	****************************************************************************/

	/**
	 * Handles version checking
	 */
	public function do_install() {
		// only install when woocommerce is active
		if ( !$this->is_woocommerce_activated() ) {
			return;
		}

		$version_setting = 'wpo_wcpdf_templates_version';
		$installed_version = get_option( $version_setting );

		// installed version lower than plugin version?
		if ( version_compare( $installed_version, WPO_WCPDF_TEMPLATES_VERSION, '<' ) ) {

			if ( ! $installed_version ) {
				$this->install();
			} else {
				$this->upgrade( $installed_version );
			}

			// new version number
			update_option( $version_setting, WPO_WCPDF_TEMPLATES_VERSION );
		}
	}

	/**
	 * Plugin install method. Perform any installation tasks here
	 */
	protected function install() {
		if ( $settings = get_option('wpo_wcpdf_settings_general') ) {
			$option = 'wpo_wcpdf_settings_general'; // wcpdf 2.0+
		} elseif ( $settings = get_option('wpo_wcpdf_template_settings') ) {
			$option = 'wpo_wcpdf_template_settings'; // wcpdf 1.6.5 or older
		}

		if ( is_array( $settings ) && isset( $settings['template_path'] ) ) {
			// plugin folder:
			$plugin_folder = trailingslashit(basename(__DIR__));
			// check if old (1.3.2) template paths are used. If so - upgrade to new /templates folder
			$settings['template_path'] = str_replace('woocommerce-pdf-ips-templates/Business', $plugin_folder.'templates/Business', $settings['template_path']);
			$settings['template_path'] = str_replace('woocommerce-pdf-ips-templates/Modern', $plugin_folder.'templates/Modern', $settings['template_path']);

			// automatically switch to simple premium
			$settings['template_path'] = str_replace('woocommerce-pdf-invoices-packing-slips/templates/pdf/Simple', $plugin_folder.'templates/Simple Premium', $settings['template_path']);
			$settings['template_path'] = str_replace('woocommerce-pdf-invoices-packing-slips/templates/Simple', $plugin_folder.'templates/Simple Premium', $settings['template_path']);

			update_option( $option, $settings );
		}

	}

	/**
	 * Plugin upgrade method.  Perform any required upgrades here
	 *
	 * @param string $installed_version the currently installed ('old') version
	 */
	protected function upgrade( $installed_version ) {
		// 2.1.5 Upgrade: set default footer height for Simple Premium (2cm)
		if ( version_compare( $installed_version, '2.1.5', '<' ) ) {
			$template_settings = get_option('wpo_wcpdf_template_settings');
			if (isset($template_settings['template_path']) && strpos($template_settings['template_path'],'Simple Premium') !== false ) {
				$template_settings['footer_height'] = '2cm';
				update_option( 'wpo_wcpdf_template_settings', $template_settings );
			}
		}

		// 2.1.7 Upgrade: set show meta as default in product block
		if ( version_compare( $installed_version, '2.1.7', '<' ) ) {
			$editor_settings = get_option('wpo_wcpdf_editor_settings');
			$documents = array('invoice','packing-slip','proforma','credit-note');
			foreach ($documents as $document) {
				if (isset($editor_settings['fields_'.$document.'_columns'])) {
					foreach ($editor_settings['fields_'.$document.'_columns'] as $key => $column) {

						if (isset($column['type']) && $column['type'] == 'description') {
							$column['show_meta'] = 1;
						}
						$editor_settings['fields_'.$document.'_columns'][$key] = $column;
					}
				}
			}
			update_option('wpo_wcpdf_editor_settings', $editor_settings);
		}

		// 2.4.0 Upgrade: footer height moved to General settings
		if ( version_compare( $installed_version, '2.4.0', '<' ) ) {
			// load legacy settings
			$template_settings = get_option('wpo_wcpdf_template_settings');
			if (!empty($template_settings['footer_height'])) {
				// copy footer height to new general settings option
				$general_settings = get_option('wpo_wcpdf_settings_general');
				$general_settings['footer_height'] = $template_settings['footer_height'];
				update_option( 'wpo_wcpdf_settings_general', $general_settings );
			}
		}
	}

	/**
	 * Run the updater scripts from the WPO Sidekick
	 * @return void
	 */
	public function load_updater() {
		// Init updater data
		$item_name		= 'WooCommerce PDF Invoices & Packing Slips Premium Templates';
		$file			= __FILE__;
		$license_slug	= 'wpo_wcpdf_templates_license';
		$version		= $this->version;
		$author			= 'Ewout Fernhout';

		// Check if sidekick is loaded
		if (class_exists('WPO_Updater')) {
			$this->updater = new WPO_Updater( $item_name, $file, $license_slug, $version, $author );
		}
	}


	/**
	 * Get the plugin url.
	 * @return string
	 */
	public function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	/**
	 * Get the plugin path.
	 * @return string
	 */
	public function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

} // class WPO_WCPDF_Templates

endif; // class_exists

/**
 * Returns the main instance of WooCommerce PDF Invoices & Packing Slips to prevent the need to use globals.
 *
 * @since  1.6
 * @return WPO_WCPDF_Templates
 */
function WPO_WCPDF_Templates() {
	return WPO_WCPDF_Templates::instance();
}

WPO_WCPDF_Templates(); // load plugin

/**
 * WPOvernight updater admin notice
 */
if ( ! class_exists( 'WPO_Updater' ) && ! function_exists( 'wpo_updater_notice' ) ) {

	if ( ! empty( $_GET['hide_wpo_updater_notice'] ) ) {
		update_option( 'wpo_updater_notice', 'hide' );
	}

	/**
	 * Display a notice if the "WP Overnight Sidekick" plugin hasn't been installed.
	 * @return void
	 */
	function wpo_updater_notice() {
		$wpo_updater_notice = get_option( 'wpo_updater_notice' );

		$blog_plugins = get_option( 'active_plugins', array() );
		$site_plugins = get_site_option( 'active_sitewide_plugins', array() );
		$plugin = 'wpovernight-sidekick/wpovernight-sidekick.php';

		if ( in_array( $plugin, $blog_plugins ) || isset( $site_plugins[$plugin] ) || $wpo_updater_notice == 'hide' ) {
			return;
		}

		echo '<div class="updated fade"><p>Install the <strong>WP Overnight Sidekick</strong> plugin to receive updates for your WP Overnight plugins - check your order confirmation email for more information. <a href="'.add_query_arg( 'hide_wpo_updater_notice', 'true' ).'">Hide this notice</a></p></div>' . "\n";
	}

	add_action( 'admin_notices', 'wpo_updater_notice' );
}
