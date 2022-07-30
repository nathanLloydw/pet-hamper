<?php
/*
  Plugin Name: WP Sheet Editor - WooCommerce Coupons (Premium)
  Description: Edit WooCommerce Coupons in spreadsheet.
  Version: 1.3.32
  Author:      WP Sheet Editor
  Author URI:  https://wpsheeteditor.com/?utm_source=wp-admin&utm_medium=plugins-list&utm_campaign=coupons
  Plugin URI: https://wpsheeteditor.com/extensions/woocommerce-coupons-spreadsheet/?utm_source=wp-admin&utm_medium=plugins-list&utm_campaign=coupons
  License:     GPL2
  License URI: https://www.gnu.org/licenses/gpl-2.0.html
  WC requires at least: 3.0
  WC tested up to: 6.6
  Text Domain: vg_sheet_editor_wc_coupons
  Domain Path: /lang
  @fs_premium_only /modules/user-path/send-user-path.php, /modules/advanced-filters/, /modules/columns-renaming/, /modules/formulas/, /modules/custom-columns/, /modules/posts-templates/, /modules/spreadsheet-setup/, /modules/universal-sheet/, /modules/columns-manager/,  /modules/wp-sheet-editor/inc/integrations/notifier.php,/modules/wp-sheet-editor/inc/integrations/extensions.json,
 */

if (isset($_GET['wpse_troubleshoot8987'])) {
	return;
}
if (!defined('ABSPATH')) {
	exit;
}
if (function_exists('wpsewcc_fs')) {
	wpsewcc_fs()->set_basename(true, __FILE__);
}
require_once 'vendor/vg-plugin-sdk/index.php';
require_once 'vendor/freemius/start.php';
require_once 'inc/freemius-init.php';
require_once 'inc/columns.php';

if (wpsewcc_fs()->can_use_premium_code__premium_only()) {
	if (!defined('VGSE_WC_COUPONS_IS_PREMIUM')) {
		define('VGSE_WC_COUPONS_IS_PREMIUM', true);
	}
}
if (!class_exists('WP_Sheet_Editor_WC_Coupons')) {

	/**
	 * Filter rows in the spreadsheet editor.
	 */
	class WP_Sheet_Editor_WC_Coupons {

		static private $instance = false;
		var $plugin_url = null;
		var $plugin_dir = null;
		var $textname = 'vg_sheet_editor_wc_coupons';
		var $buy_link = null;
		var $version = '1.3.9';
		var $settings = null;
		var $args = null;
		var $vg_plugin_sdk = null;
		var $post_type = 'shop_coupon';

		private function __construct() {
			
		}

		function init_plugin_sdk() {
			$this->args = array(
				'main_plugin_file' => __FILE__,
				'show_welcome_page' => true,
				'welcome_page_file' => $this->plugin_dir . '/views/welcome-page-content.php',
				'logo' => plugins_url('/assets/imgs/logo-248x102.png', __FILE__),
				'buy_link' => $this->buy_link,
				'plugin_name' => 'Bulk Edit Coupons',
				'plugin_prefix' => 'wpsewcc_',
				'show_whatsnew_page' => true,
				'whatsnew_pages_directory' => $this->plugin_dir . '/views/whats-new/',
				'plugin_version' => $this->version,
				'plugin_options' => $this->settings,
			);
			$this->vg_plugin_sdk = new VG_Freemium_Plugin_SDK($this->args);
		}

		function notify_wrong_core_version() {
			$plugin_data = get_plugin_data(__FILE__, false, false);
			?>
			<div class="notice notice-error">
				<p><?php _e('Please update the WP Sheet Editor plugin and all its extensions to the latest version. The features of the plugin "' . $plugin_data['Name'] . '" will be disabled to prevent errors and they will be enabled automatically after you install the updates.', vgse_wc_coupons()->textname); ?></p>
			</div>
			<?php
		}

		function init() {
			require_once __DIR__ . '/modules/init.php';
			$this->modules_controller = new WP_Sheet_Editor_CORE_Modules_Init(__DIR__, wpsewcc_fs());

			$this->plugin_url = plugins_url('/', __FILE__);
			$this->plugin_dir = __DIR__;
			$this->buy_link = wpsewcc_fs()->checkout_url();

			$this->init_plugin_sdk();

			// After core has initialized
			add_action('vg_sheet_editor/initialized', array($this, 'after_core_init'));
			add_action('vg_sheet_editor/after_init', array($this, 'after_full_core_init'));

			add_action('admin_init', array($this, 'disable_free_plugins_when_premium_active'), 1);
			add_action('admin_menu', array($this, 'register_menu'));
			add_action('init', array($this, 'after_init'));
		}

		function after_init() {
			load_plugin_textdomain($this->textname, false, basename(dirname(__FILE__)) . '/lang/');
		}

		function disable_free_plugins_when_premium_active() {
			$free_plugins_path = array(
				'woo-coupons-bulk-editor/coupons.php',
			);
			if (is_plugin_active('woo-coupons-bulk-editor-premium/coupons.php')) {
				foreach ($free_plugins_path as $relative_path) {
					$path = wp_normalize_path(WP_PLUGIN_DIR . '/' . $relative_path);
					if (is_plugin_active($relative_path)) {
						deactivate_plugins(plugin_basename($path));
					}
				}
			}
		}

		function after_core_init() {
			if (version_compare(VGSE()->version, '2.24.15') < 0) {
				add_action('admin_notices', array($this, 'notify_wrong_core_version'));
				return;
			}

			// Override core buy link with this plugin´s
			VGSE()->buy_link = $this->buy_link;

			add_filter('vg_sheet_editor/allowed_post_types', array($this, 'allow_wc_coupons'));
			add_filter('vg_sheet_editor/provider/post/get_item_meta', array($this, 'filter_cell_data_for_readings'), 10, 5);
			add_filter('vg_sheet_editor/provider/post/update_item_meta', array($this, 'filter_cell_data_for_saving'), 10, 3);
			add_filter('vg_sheet_editor/api/all_post_types', array($this, 'append_wc_coupons_to_post_types_list'), 10, 3);

			// Enable admin pages in case "frontend sheets" addon disabled them
			add_filter('vg_sheet_editor/register_admin_pages', '__return_true', 11);

			if (wpsewcc_fs()->can_use_premium_code__premium_only()) {
				add_filter('vg_sheet_editor/formulas/sql_execution/can_execute', array($this, 'disable_sql_formulas_for_serialized_columns__premium_only'), 10, 4);
			}
			add_action('vg_sheet_editor/editor/before_init', array($this, 'register_toolbar_items'));
		}

		function register_toolbar_items($editor) {
			if ($editor->args['provider'] !== $this->post_type) {
				return;
			}
			if (!current_user_can('manage_options')) {
				return;
			}
			$editor->args['toolbars']->register_item('wpse_license', array(
				'type' => 'button',
				'content' => __('My license', vgse_wc_coupons()->textname),
				'url' => wpsewcc_fs()->get_account_url(),
				'extra_html_attributes' => ' target="_blank" ',
				'toolbar_key' => 'secondary',
				'allow_in_frontend' => false,
				'fs_id' => wpsewcc_fs()->get_id()
					), $this->post_type);
		}

		function filter_cell_data_for_saving($new_value, $id, $key) {
			if (get_post_type($id) !== $this->post_type) {
				return $new_value;
			}

			if ($key === 'expiry_date') {
				$timestamp = ( empty($new_value)) ? '' : strtotime($new_value);
				update_post_meta($id, 'date_expires', $timestamp);
			}
			if ($key === 'customer_email') {
				if (is_string($new_value)) {
					$new_value = explode(',', $new_value);
				} elseif (empty($new_value)) {
					$new_value = '';
				}
				$new_value = ( is_array($new_value)) ? array_map('trim', array_values(array_filter(VGSE()->helpers->array_flatten($new_value)))) : '';
			}
			if (in_array($key, array('product_categories', 'exclude_product_categories'))) {
				$new_value = VGSE()->data_helpers->prepare_post_terms_for_saving($new_value, 'product_cat');
			}

			return $new_value;
		}

		function filter_cell_data_for_readings($value, $id, $cell_key, $single, $context) {
			if ($context !== 'read' || get_post_type($id) !== $this->post_type) {
				return $value;
			}
			if ($cell_key === 'usage_count') {
				$value = (int) $value;
			}
			if ($cell_key === 'customer_email') {
				if (empty($value)) {
					$value = '';
				} elseif (is_string($value)) {
					$value = $value;
				} elseif (is_array($value)) {
					$value = implode(', ', $value);
				}
			}
			if ($cell_key === '_used_by') {
				$user_emails = array();
				$value = get_post_meta($id, '_used_by');
				if (is_array($value)) {
					foreach ($value as $user) {
						if (is_numeric($user)) {
							$user_data = get_userdata((int) $user);
							if ($user_data) {
								$user_emails[] = $user_data->user_email;
							}
						} else {
							$user_emails[] = $user;
						}
					}
				}
				$value = ( empty($user_emails)) ? '' : implode(', ', array_unique($user_emails));
			}
			if (in_array($cell_key, array('product_categories', 'exclude_product_categories'))) {
				if (!empty($value)) {
					$terms = get_terms(array(
						'taxonomy' => 'product_cat',
						'hide_empty' => false,
						'include' => $value,
						'update_term_meta_cache' => false
					));
					if (!empty($terms) && !is_wp_error($terms)) {
						$names = wp_list_pluck($terms, 'name');
						$value = implode(', ', $names);
					}
				} else {
					$value = '';
				}
			}
			return $value;
		}

		function disable_sql_formulas_for_serialized_columns__premium_only($allowed, $formula, $column, $post_type) {
			if ($post_type === $this->post_type && in_array($column['key'], array('product_categories', 'exclude_product_categories', 'customer_email', 'expiry_date'))) {
				$allowed = false;
			}

			return $allowed;
		}

		function append_wc_coupons_to_post_types_list($post_types, $args, $output) {

			$post_type_found = wp_list_filter($post_types, array('name' => $this->post_type));
			if (isset($post_types[$this->post_type]) || !empty($post_type_found)) {
				return $post_types;
			}

			if ($output === 'names') {
				$post_types[$this->post_type] = __('Coupons', 'woocommerce');
			} else {
				$post_types[$this->post_type] = (object) array(
							'label' => __('Coupons', 'woocommerce'),
							'name' => $this->post_type
				);
			}
			return $post_types;
		}

		function after_full_core_init() {
			// Set up spreadsheet.
			// Allow to bootstrap editor manually, later.
			if (!apply_filters('vg_sheet_editor/wc_coupons/bootstrap/manual_init', false)) {
				$this->sheets_bootstrap = new WP_Sheet_Editor_Bootstrap(array(
					'allowed_post_types' => array(),
					'only_allowed_spreadsheets' => false,
					'enabled_post_types' => array($this->post_type),
					'register_toolbars' => true,
					'register_columns' => true,
					'register_taxonomy_columns' => false,
					'register_admin_menus' => true,
					'post_type_labels' => array($this->post_type => __('Coupons', 'woocommerce')),
					'register_spreadsheet_editor' => true,
					'current_provider' => VGSE()->helpers->get_provider_from_query_string()
				));
			}
		}

		function register_menu() {
			$bulk_label = __('Bulk Edit coupons', vgse_wc_coupons()->textname);
			$page_slug = 'vgse-bulk-edit-' . $this->post_type;

			add_submenu_page('woocommerce', $bulk_label, $bulk_label, 'manage_woocommerce', 'admin.php?page=' . $page_slug, null);
		}

		function allow_wc_coupons($post_types) {
			$post_types[$this->post_type] = __('Coupons', 'woocommerce');
			return $post_types;
		}

		/**
		 * Creates or returns an instance of this class.
		 */
		static function get_instance() {
			if (null == WP_Sheet_Editor_WC_Coupons::$instance) {
				WP_Sheet_Editor_WC_Coupons::$instance = new WP_Sheet_Editor_WC_Coupons();
				WP_Sheet_Editor_WC_Coupons::$instance->init();
			}
			return WP_Sheet_Editor_WC_Coupons::$instance;
		}

		function __set($name, $value) {
			$this->$name = $value;
		}

		function __get($name) {
			return $this->$name;
		}

	}

}

if (!function_exists('vgse_wc_coupons')) {

	function vgse_wc_coupons() {
		return WP_Sheet_Editor_WC_Coupons::get_instance();
	}

	vgse_wc_coupons();
}	