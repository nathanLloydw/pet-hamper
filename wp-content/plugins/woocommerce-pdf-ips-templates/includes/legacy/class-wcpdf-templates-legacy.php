<?php
namespace WPO\WC\PDF_Invoices_Templates\Legacy;

defined( 'ABSPATH' ) or exit;

if ( !class_exists( '\\WPO\\WC\\PDF_Invoices_Templates\\Legacy\\WPO_WCPDF_Templates_Legacy' ) ) :

class WPO_WCPDF_Templates_Legacy {

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
	}

	public function get_table_headers ( $template_type ) {
		global $wpo_wcpdf;
		if ( !empty( $wpo_wcpdf->export->document ) && is_object( $wpo_wcpdf->export->document ) ) {
			return wpo_wcpdf_templates_get_table_headers( $wpo_wcpdf->export->document );
		}
	}

	public function get_table_body ( $template_type ) {
		global $wpo_wcpdf;
		if ( !empty( $wpo_wcpdf->export->document ) && is_object( $wpo_wcpdf->export->document ) ) {
			return wpo_wcpdf_templates_get_table_body( $wpo_wcpdf->export->document );
		}
	}

	public function get_totals ( $template_type ) {
		global $wpo_wcpdf;
		if ( !empty( $wpo_wcpdf->export->document ) && is_object( $wpo_wcpdf->export->document ) ) {
			return wpo_wcpdf_templates_get_totals( $wpo_wcpdf->export->document );
		}
	}

	public function get_footer_height_page_bottom ( $default_height = '5cm' ) {
		global $wpo_wcpdf;
		if ( !empty( $wpo_wcpdf->export->document ) && is_object( $wpo_wcpdf->export->document ) ) {
			return wpo_wcpdf_templates_get_footer_settings( $wpo_wcpdf->export->document, $default_height );
		}
	}

}

endif; // Class exists check

function WPO_WCPDF_Templates_Legacy() {
	return WPO_WCPDF_Templates_Legacy::instance();
}

// Global for backwards compatibility.
$GLOBALS['wpo_wcpdf_templates'] = WPO_WCPDF_Templates_Legacy();