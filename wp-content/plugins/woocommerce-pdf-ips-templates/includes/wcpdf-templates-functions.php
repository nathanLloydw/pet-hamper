<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPO\WC\PDF_Invoices\Compatibility\WC_Core as WCX;
use WPO\WC\PDF_Invoices\Compatibility\Order as WCX_Order;
use WPO\WC\PDF_Invoices\Compatibility\Product as WCX_Product;

function wpo_wcpdf_templates_get_table_headers( $document ) {
	$column_settings = WPO_WCPDF_Templates()->settings->get_settings( $document->get_type(), 'columns', $document );
	$order_discount = $document->get_order_discount( 'total', 'incl' );

	// mark first and last column
	if (!empty($column_settings)) {
		end($column_settings);
		$column_settings[key($column_settings)]['position'] = 'last';
		reset($column_settings);
		$column_settings[key($column_settings)]['position'] = 'first';
	}

	$headers = array();
	foreach ( $column_settings as $column_key => $column_setting) {
		if ( !$order_discount && isset($column_setting['only_discounted'])) {
			continue;
		}
		$headers[$column_key] = $column_setting + WPO_WCPDF_Templates()->main->get_order_details_header( $column_setting, $document );
	}

	return apply_filters( 'wpo_wcpdf_templates_table_headers', $headers, $document->get_type(), $document );
}

function wpo_wcpdf_templates_get_table_body( $document ) {
	$column_settings = WPO_WCPDF_Templates()->settings->get_settings( $document->get_type(), 'columns', $document );
	$order_discount = $document->get_order_discount( 'total', 'incl' );

	// mark first and last column
	if (!empty($column_settings)) {
		end($column_settings);
		$column_settings[key($column_settings)]['position'] = 'last';
		reset($column_settings);
		$column_settings[key($column_settings)]['position'] = 'first';
	}
	
	$body = array();
	$items = $document->get_order_items();
	if( sizeof( $items ) > 0 ) {
		foreach ($column_settings as $column_key => $column_setting) {
			$line_number = 1;
			foreach( $items as $item_id => $item ) {
				if ( !$order_discount && isset($column_setting['only_discounted'])) {
					continue;
				}

				$column_setting['line_number'] = $line_number;
				$body[$item_id][$column_key] = $column_setting + WPO_WCPDF_Templates()->main->get_order_details_data( $column_setting, $item, $document );
				$line_number++;
			}
		}
	}

	return apply_filters( 'wpo_wcpdf_templates_table_body', $body, $document->get_type(), $document );
}

function wpo_wcpdf_templates_get_totals( $document ) {
	$total_settings = WPO_WCPDF_Templates()->settings->get_settings( $document->get_type(), 'totals', $document );
	$totals_data = WPO_WCPDF_Templates()->main->get_totals_table_data( $total_settings, $document );

	return apply_filters( 'wpo_wcpdf_templates_totals', $totals_data, $document->get_type(), $document );
}

function wpo_wcpdf_templates_get_footer_settings( $document, $default_height = '5cm' ) {
	$footer_height = str_replace( ' ', '', WPO_WCPDF_Templates()->settings->get_footer_height() );
	if ( empty( $footer_height ) ) {
		$footer_height = $default_height;
	}

	// calculate bottom page margin
	$page_bottom = floatval($footer_height);

	// convert to cm
	if (strpos($footer_height,'in') !== false) {
		$page_bottom = $page_bottom * 2.54;
	} elseif (strpos($footer_height,'mm') !== false) {
		$page_bottom = $page_bottom / 10;
	}

	// add 1 + cm
	$page_bottom = ($page_bottom + 1).'cm';

	return compact( 'footer_height', 'page_bottom' );
}
