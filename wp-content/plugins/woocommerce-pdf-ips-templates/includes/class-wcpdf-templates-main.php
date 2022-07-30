<?php
use WPO\WC\PDF_Invoices\Compatibility\WC_Core as WCX;
use WPO\WC\PDF_Invoices\Compatibility\Order as WCX_Order;
use WPO\WC\PDF_Invoices\Compatibility\Product as WCX_Product;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists( 'WooCommerce_PDF_IPS_Templates_Main' ) ) {

	class WooCommerce_PDF_IPS_Templates_Main {
		public function __construct() {
			// Add premium templates to settings page listing
			add_filter( 'wpo_wcpdf_template_paths', array( $this, 'add_templates' ), 1, 1 );

			// Load custom styles from settings
			add_action( 'wpo_wcpdf_custom_styles', array( $this, 'custom_template_styles' ) );

			// hook custom blocks to template actions
			add_action( 'wpo_wcpdf_before_document', array( $this, 'custom_blocks_data' ), 10, 2 );
			add_action( 'wpo_wcpdf_after_document_label', array( $this, 'custom_blocks_data' ), 10, 2 );
			add_action( 'wpo_wcpdf_before_billing_address', array( $this, 'custom_blocks_data' ), 10, 2 );
			add_action( 'wpo_wcpdf_after_billing_address', array( $this, 'custom_blocks_data' ), 10, 2 );
			add_action( 'wpo_wcpdf_before_shipping_address', array( $this, 'custom_blocks_data' ), 10, 2 );
			add_action( 'wpo_wcpdf_after_shipping_address', array( $this, 'custom_blocks_data' ), 10, 2 );
			add_action( 'wpo_wcpdf_before_order_data', array( $this, 'custom_blocks_data' ), 10, 2 );
			add_action( 'wpo_wcpdf_after_order_data', array( $this, 'custom_blocks_data' ), 10, 2 );
			add_action( 'wpo_wcpdf_before_customer_notes', array( $this, 'custom_blocks_data' ), 10, 2 );
			add_action( 'wpo_wcpdf_after_customer_notes', array( $this, 'custom_blocks_data' ), 10, 2 );
			add_action( 'wpo_wcpdf_before_order_details', array( $this, 'custom_blocks_data' ), 10, 2 );
			add_action( 'wpo_wcpdf_after_order_details', array( $this, 'custom_blocks_data' ), 10, 2 );
			add_action( 'wpo_wcpdf_after_document', array( $this, 'custom_blocks_data' ), 10, 2 );

			// make replacements in template settings fields
			add_action( 'wpo_wcpdf_footer_settings_text', array( $this, 'settings_fields_replacements' ), 999, 2 );
			add_action( 'wpo_wcpdf_extra_1_settings_text', array( $this, 'settings_fields_replacements' ), 999, 2 );
			add_action( 'wpo_wcpdf_extra_2_settings_text', array( $this, 'settings_fields_replacements' ), 999, 2 );
			add_action( 'wpo_wcpdf_extra_3_settings_text', array( $this, 'settings_fields_replacements' ), 999, 2 );
			add_action( 'wpo_wcpdf_shop_name_settings_text', array( $this, 'settings_fields_replacements' ), 999, 2 );
			add_action( 'wpo_wcpdf_shop_address_settings_text', array( $this, 'settings_fields_replacements' ), 999, 2 );

			// store regular price in item meta
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_regular_item_price' ), 10, 2 );
			add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hide_regular_price_itemmeta' ) );

			// store rate percentage in tax meta
			add_action( 'woocommerce_checkout_order_processed', array( $this, 'save_tax_rate_percentage_frontend' ), 10, 2 );
			add_action( 'woocommerce_order_after_calculate_totals', array( $this, 'save_tax_rate_percentage_recalculate' ), 10, 2 );

			// sort items on documents
			add_action( 'wpo_wcpdf_order_items_data', array( $this, 'sort_items' ), 10, 3 );
		}

		/**
		 * Add premium templates to settings page listing
		 */
		public function add_templates( $template_paths ) {
			$template_paths['premium_plugin'] = WPO_WCPDF_Templates()->plugin_path() . '/templates/';
			return $template_paths;
		}

		/**
		 * Load custom styles from settings
		 */
		public function custom_template_styles ( $template_type, $document = null ) {
			$editor_settings = get_option('wpo_wcpdf_editor_settings');
			if (isset($editor_settings['custom_styles'])) {
				echo $editor_settings['custom_styles'];
			}
		}

		public function sort_items ( $items, $order, $template_type ) {
			$editor_settings = get_option('wpo_wcpdf_editor_settings');
			if ( is_array( $editor_settings ) && array_key_exists( 'sort_items', $editor_settings ) ) {
				if ( is_array( $editor_settings['sort_items'] ) && isset($editor_settings['sort_items'][$template_type]) ) {
					$sort_by = $editor_settings['sort_items'][$template_type];

					switch ($sort_by) {
						case "product":
							uasort($items, function ($a, $b) { return strnatcasecmp($a['name'], $b['name']); });
							break;
						case "sku":
							uasort($items, function ($a, $b) {
								$sku_a = !empty( $a['sku'] ) ? $a['sku'] : "";
								$sku_b = !empty( $b['sku'] ) ? $b['sku'] : "";
								return strnatcasecmp($sku_a, $sku_b);			
							});
							break;
						case "category":
							uasort($items, function ($a, $b) {
								$categories_a = strip_tags( wc_get_product_category_list( $a['product_id'] ) );
								$categories_b = strip_tags( wc_get_product_category_list( $b['product_id'] ) );
								return strnatcasecmp($categories_a, $categories_b);
							});
							break;
					}
				}
			}
			return $items;
		}

		public function get_totals_table_data ( $total_settings, $document ) {
			$totals_table_data = array();
			foreach ($total_settings as $total_key => $total_setting) {
				// reset possibly absent vars
				$method = $percent = $base = $show_unit = $only_discounted = $label = $single_total = NULL;
				// extract vars
				extract($total_setting);

				// remove label if empty!
				if( empty($total_setting['label']) ) {
					unset($total_setting['label']);
				} elseif ( !in_array( $type, array( 'fees' ) ) ) {
					$label = $total_setting['label'] = __( $total_setting['label'], 'woocommerce-pdf-invoices-packing-slips' ); // not proper gettext, but it makes it possible to reuse po translations!
				}

				switch ($type) {
					case 'subtotal':
						// $tax, $discount, $only_discounted
						$order_discount = $document->get_order_discount( 'total', 'incl' );
						if ( !$order_discount && isset($only_discounted) ) {
							break;
						}
						switch ($discount) {
							case 'before':

								$totals_table_data[$total_key] = (array) $total_setting + $document->get_order_subtotal( $tax );
								break;

							case 'after':
								$subtotal_value = 0;
								$items = $document->order->get_items();
								if( sizeof( $items ) > 0 ) {
									foreach( $items as $item ) {
										$subtotal_value += $item['line_total'];
										if ( $tax == 'incl' ) {
											$subtotal_value += $item['line_tax'];
										}
									}
								}
								$subtotal_data = array(
									'label'	=> __('Subtotal', 'woocommerce-pdf-invoices-packing-slips'),
									'value'	=> $document->format_price( $subtotal_value ),
								);
								$totals_table_data[$total_key] = (array) $total_setting + $subtotal_data;
								break;
						}
						break;
					case 'discount':
						// $tax, $show_codes, $show_percentage
						if ( $discount = $document->get_order_discount( 'total', $tax ) ) {
							if (isset($discount['raw_value'])) {
								// support for positive discount (=more expensive/price corrections)
								$discount['value'] = $document->format_price( $discount['raw_value'] * -1 );
							} else {
								$discount['value'] = '-'.$discount['value'];
							}
							
							$discount['label'] = !empty($label) ? $label : $discount['label'];
							unset($total_setting['label']);

							$discount_percentage = $this->get_discount_percentage( $document->order );
							if (isset($show_percentage) && $discount_percentage) {
								$discount['label'] = "{$discount['label']} ({$discount_percentage}%)";
							}

							$used_coupons = implode(', ', $document->order->get_used_coupons() );
							if (isset($show_codes) && !empty($used_coupons)) {
								$discount['label'] = "{$discount['label']} ({$used_coupons})";
							}

							$totals_table_data[$total_key] = (array) $total_setting + $discount;
						}
						break;
					case 'shipping':
						// $tax, $method, $hide_free
						$shipping_cost = WCX_Order::get_prop( $document->order, 'shipping_total', 'view' );
						if ( !(round( $shipping_cost, 3 ) == 0 && isset($hide_free)) ) {
							$totals_table_data[$total_key] = (array) $total_setting + $document->get_order_shipping( $tax );
							if (!empty($method)) {
								$totals_table_data[$total_key]['value'] = $document->order->get_shipping_method();
							}
						}
						break;
					case 'fees':
						// $tax
						if ( $fees = $document->get_order_fees( $tax ) ) {

							// WooCommerce Checkout Add-Ons compatibility
							if ( function_exists('wc_checkout_add_ons')) {
								$wc_checkout_add_ons = wc_checkout_add_ons();
								// we're adding a 'fee_' prefix because that's what woocommerce does in its
								// order total keys and wc_checkout_add_ons uses this to determine the total type (fee)
								$fees = $this->array_keys_prefix($fees, 'fee_', 'add');
								if (method_exists($wc_checkout_add_ons, 'get_frontend_instance')) {
									$wc_checkout_add_ons_frontend = $wc_checkout_add_ons->get_frontend_instance();
									$fees = $wc_checkout_add_ons_frontend->append_order_add_on_fee_meta( $fees, $document->order );
								} elseif ( is_object(wc_checkout_add_ons()->frontend) && method_exists(wc_checkout_add_ons()->frontend, 'append_order_add_on_fee_meta') ) {
									$fees = wc_checkout_add_ons()->frontend->append_order_add_on_fee_meta( $fees, $document->order );
								}
								$fees = $this->array_keys_prefix($fees, 'fee_', 'remove');
							}

							reset($fees);
							$first = key($fees);
							end($fees);
							$last = key($fees);
							
							foreach( $fees as $fee_key => $fee ) {
								$class = 'fee-line';
								if ($fee_key == $first) $class .= ' first';
								if ($fee_key == $last) $class .= ' last';

								$totals_table_data[$total_key.$fee_key] = (array) $total_setting + $fee;
								$totals_table_data[$total_key.$fee_key]['class'] = $class;
							}
						}
						break;
					case 'vat':
						// $percent, $base
						$total_tax = $document->order->get_total_tax();
						$shipping_tax = $document->order->get_shipping_tax();

						if ( isset ( $single_total ) ) {
							$tax = array();

							// override label if set
							// unset($total_setting['label']);
							$tax['label'] = !empty($label) ? $label : __( 'VAT', 'wpo_wcpdf_templates' );


							if ( isset($tax_type) && $tax_type == 'product' ) {
								$tax['value'] = $document->format_price( $total_tax - $shipping_tax );
							} elseif ( isset($tax_type) && $tax_type == 'shipping' ) {
								$tax['value'] = $document->format_price( $shipping_tax );
							} else {
								$tax['value'] = $document->format_price( $total_tax );
							}
							
							$totals_table_data[$total_key] = (array) $total_setting + (array) $tax;
							$totals_table_data[$total_key]['class'] = 'vat tax-line';
						} elseif ($taxes = $document->get_order_taxes()) {
							$taxes = $this->add_tax_base( $taxes, $document->order );

							reset($taxes);
							$first = key($taxes);
							end($taxes);
							$last = key($taxes);

							foreach( $taxes as $tax_key => $tax ) {
								$class = 'tax-line';
								if ($tax_key == $first) $class .= ' first';
								if ($tax_key == $last) $class .= ' last';

								// prepare label format based on settings
								$label_format = '{{label}}';
								if (isset($percent)) $label_format .= ' {{rate}}';

								// prevent errors if base not set
								if ( empty( $tax['base'] ) ) $tax['base'] = 0;

								// override label if set
								$tax_label = !empty($label) ? $label : $tax['label'];
								unset($total_setting['label']);

								if ( isset($tax_type) && $tax_type == 'product' ) {
									if ( apply_filters( 'woocommerce_order_hide_zero_taxes', true ) && $tax['tax_amount'] == 0 ) {
										continue;
									}
									$tax_amount = $tax['tax_amount'];
								} elseif ( isset($tax_type) && $tax_type == 'shipping' ) {
									if ( apply_filters( 'woocommerce_order_hide_zero_taxes', true ) && $tax['shipping_tax_amount'] == 0 ) {
										continue;
									}
									$tax_amount = $tax['shipping_tax_amount'];
								} else {
									$tax_amount = $tax['tax_amount'] + $tax['shipping_tax_amount'];
									if (isset($base) && !empty($tax['base'])) $label_format .= ' ({{base}})'; // add base to label
								}
								$tax['value'] = $document->format_price( $tax_amount );

								// fallback to tax calculation if we have no rate
								// if ( empty( $tax['rate'] ) && method_exists( $document, 'calculate_tax_rate' ) ) {
								// 	$tax['rate'] = $document->calculate_tax_rate( $tax['base'], $tax_amount );
								// }

								$label_format = apply_filters( 'wpo_wcpdf_templates_tax_total_label_format', $label_format );

								if ( isset( $tax['stored_rate'] ) ) {
									$tax_rate = $tax['stored_rate'];
								} else {
									$tax_rate = $tax['calculated_rate'];
								}

								$tax['label'] = str_replace( array( '{{label}}', '{{rate}}', '{{base}}' ) , array( $tax_label, $tax_rate, $document->format_price( $tax['base'] ) ), $label_format );

								$totals_table_data[$total_key.$tax_key] = (array) $total_setting + $tax;
								$totals_table_data[$total_key.$tax_key]['class'] = $class;
							}
						}
						break;
					case 'vat_base':
						// $percent
						if ($taxes = $document->get_order_taxes()){
							$taxes = $this->add_tax_base( $taxes, $document->order );

							reset($taxes);
							$first = key($taxes);
							end($taxes);
							$last = key($taxes);

							if (empty($total_setting['label'])) {
								$total_setting['label'] = $label = __( 'Total ex. VAT', 'woocommerce-pdf-invoices-packing-slips' );
							}

							foreach( $taxes as $tax_key => $tax ) {
								// prevent errors if base not set
								if ( empty( $tax['base'] ) ) continue;

								$class = 'tax-base-line';
								if ($tax_key == $first) $class .= ' first';
								if ($tax_key == $last) $class .= ' last';

								// prepare label format based on settings
								$label_format = '{{label}}';
								if (isset($percent)) $label_format .= ' ({{rate}})';
								$label_format = apply_filters( 'wpo_wcpdf_templates_tax_base_total_label_format', $label_format, $tax );

								$tax['value'] = $document->format_price( $tax['base'] );

								$total_setting['label'] = str_replace( array( '{{label}}', '{{rate}}', '{{rate_label}}' ) , array( $label, $tax['rate'], $tax['label'] ), $label_format );

								$totals_table_data[$total_key.$tax_key] = (array) $total_setting + $tax;
								$totals_table_data[$total_key.$tax_key]['class'] = $class;
							}
						}
						break;
					case 'total':
						// $tax
						if ( $tax == 'excl' && apply_filters( 'wpo_wcpdf_add_up_grand_total_excl', false ) ) {
							// alternative calculation method that adds up product prices, fees & shipping
							// rather than subtracting tax from the grand total => WC3.0+ only!
							$grand_total_ex = 0;
							foreach ( $document->order->get_items() as $item_id => $item ) {
								$grand_total_ex += $item->get_total(); // total = after discount!
							}
							foreach ( $document->order->get_fees() as $item_id => $item ) {
								$grand_total_ex += $item->get_total(); // total = after discount!
							}
							$grand_total_ex += $document->order->get_shipping_total();
							$grand_total_row = array(
								'label' => __( 'Total ex. VAT', 'woocommerce-pdf-invoices-packing-slips' ),
								'value' => wc_price( $grand_total_ex, array( 'currency' => $document->order->get_currency() ) ),
							);
							$totals_table_data[$total_key] = (array) $total_setting + $grand_total_row;
						} else {
							$totals_table_data[$total_key] = (array) $total_setting + $document->get_order_grand_total( $tax );
						}
						if ( $tax == 'incl') {
							$totals_table_data[$total_key]['class'] = 'total grand-total';
						}
						break;
					case 'order_weight':
						// $show_unit
						$order_weight = array (
							'label'	=> __( 'Total weight', 'wpo_wcpdf_templates' ),
							'value'	=> $this->get_order_weight( $document->order, $document, isset( $show_unit) ),
						);

						$totals_table_data[$total_key] = (array) $total_setting + $order_weight;
						break;
					case 'total_qty':
						$total_qty_total = array (
							'label'	=> __( 'Total quantity', 'wpo_wcpdf_templates' ),
							'value'	=> $this->get_order_total_qty( $document->order, $document ),
						);

						$totals_table_data[$total_key] = (array) $total_setting + $total_qty_total;
						break;
					default:
						break;
				}

			}

			foreach ($totals_table_data as $total_key => $total_setting) {
				// set class if not set. note that fees and taxes have modified keys!
				if ( !isset($totals_table_data[$total_key]['class']) ) {
					$totals_table_data[$total_key]['class'] = $total_setting['type'];
				}
			}

			return $totals_table_data;
		}


		public function get_order_details_header ( $column_setting, $document ) {
			extract($column_setting);

			if (!empty($label)) {
				$header['title'] = __( $label, 'woocommerce-pdf-invoices-packing-slips' ); // not proper gettext, but it makes it possible to reuse po translations!
			} else {
				switch ($type) {
					case 'position':
						$header['title'] = '';
						break;
					case 'sku':
						$header['title'] = __( 'SKU', 'woocommerce-pdf-invoices-packing-slips' );
						break;
					case 'thumbnail':
						$header['title'] = '';
						break;
					case 'description':
						$header['title'] = __( 'Product', 'woocommerce-pdf-invoices-packing-slips' );
						break;
					case 'quantity':
						$header['title'] = __( 'Quantity', 'woocommerce-pdf-invoices-packing-slips' );
						break;
					case 'price':
						switch ($price_type) {
							case 'single':
								$header['title'] = __( 'Price', 'woocommerce-pdf-invoices-packing-slips' );
								$header['class'] = 'price';
								break;
							case 'total':
								$header['title'] = __( 'Total', 'woocommerce-pdf-invoices-packing-slips' );
								$header['class'] = 'total';
								break;
						}
						break;
					case 'regular_price':
						$header['title'] = __( 'Regular price', 'wpo_wcpdf_templates' );
						break;
					case 'discount':
						$header['title'] = __( 'Discount', 'woocommerce-pdf-invoices-packing-slips' );
						break;
					case 'vat':
						$header['title'] = __( 'VAT', 'woocommerce-pdf-invoices-packing-slips' );
						break;
					case 'tax_rate':
						$header['title'] = __( 'Tax rate', 'woocommerce-pdf-invoices-packing-slips' );
						break;
					case 'weight':
						$header['title'] = __( 'Weight', 'woocommerce-pdf-invoices-packing-slips' );
						break;
					case 'dimensions':
						$header['title'] = __( 'Dimensions', 'wpo_wcpdf_templates' );
						break;
					case 'product_attribute':
						$header['title'] = '';
						break;
					case 'product_custom':
						$header['title'] = '';
						break;
					case 'product_description':
						$header['title'] = __( 'Product description', 'wpo_wcpdf_templates' );
						break;
					case 'product_categories':
						$header['title'] = __( 'Categories', 'wpo_wcpdf_templates' );
						break;
					case 'all_meta':
						$header['title'] = __( 'Variation', 'wpo_wcpdf_templates' );
						break;
					case 'item_meta':
						$header['title'] = isset( $meta_key ) ? $meta_key : '';
						break;
					case 'cb':
						$header['title'] = '';
						break;
					case 'static_text':
						$header['title'] = '';
						break;
					default:
						$header['title'] = $type;
						break;
				}
			}

			// set class if not set;
			if (!isset($header['class'])) {
				$header['class'] = $type;
			}

			// column specific classes
			switch ($type) {
				case 'product_attribute':
					if (!empty($attribute_name)) {
						$attribute_name_class = sanitize_title( $attribute_name );
						$header['class'] = "{$type} {$attribute_name_class}";
					}
					break;
				case 'product_custom':
					if (!empty($field_name)) {
						$field_name_class = sanitize_title( $field_name );
						$header['class'] .= " {$field_name_class}";
					}
					break;
				default:
					break;
			}

			// mark first and last column
			if (isset($position)) {
				$header['class'] .= " {$position}-column";
			}

			return $header;
		}

		public function get_order_details_data ( $column_setting, $item, $document ) {
			extract($column_setting);

			switch ($type) {
				case 'position':
					$column['data'] = $line_number;
					break;
				case 'sku':
					$column['data'] = isset($item['sku']) ? $item['sku'] : '';
					break;
				case 'thumbnail':
					$column['data'] = isset($item['thumbnail']) ? $item['thumbnail'] : '';
					break;
				case 'description':
					// $show_sku, $show_weight, $show_meta, $show_external_plugin_meta, $custom_text
					ob_start();
					?>
					<span class="item-name"><?php echo $item['name']; ?></span>
					<?php if ( isset($show_external_plugin_meta) ) : ?>
					<div class="external-meta-start">
					<?php do_action( 'woocommerce_order_item_meta_start', $item['item_id'], $item['item'], $document->order ); ?>
					</div>
					<?php endif; ?>
					<?php do_action( 'wpo_wcpdf_before_item_meta', $document->get_type(), $item, $document->order ); ?>
					<?php if ( isset($show_meta) ) : ?>
					<span class="item-meta"><?php echo $item['meta']; ?></span>
					<?php endif; ?>
					<?php if ( isset($show_sku) || isset($show_weight) ) : ?>
					<dl class="meta">
						<?php $description_label = __( 'SKU', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
						<?php if( !empty( $item['sku'] ) && isset($show_sku) ) : ?><dt class="sku"><?php _e( 'SKU:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt><dd class="sku"><?php echo $item['sku']; ?></dd><?php endif; ?>
						<?php if( !empty( $item['weight'] ) && isset($show_weight) ) : ?><dt class="weight"><?php _e( 'Weight:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt><dd class="weight"><?php echo $item['weight']; ?><?php echo get_option('woocommerce_weight_unit'); ?></dd><?php endif; ?>
					</dl>
					<?php endif; ?>
					<?php do_action( 'wpo_wcpdf_after_item_meta', $document->get_type(), $item, $document->order ); ?>
					<?php if ( isset($show_external_plugin_meta) ) : ?>
					<div class="external-meta-end">
					<?php do_action( 'woocommerce_order_item_meta_end', $item['item_id'], $item['item'], $document->order ); ?>
					</div>
					<?php endif; ?>
					<?php if ( isset($custom_text) ) : ?>
					<div class="custom-text">
					<?php echo nl2br( wptexturize( $this->make_item_replacements( $custom_text, $item, $document ) ) ); ?>
					</div>
					<?php endif; ?>
					<?php
					$column['data'] = ob_get_clean();
					break;
				case 'quantity':
					$column['data'] = $item['quantity'];
					if ( absint( $item['quantity'] ) > 1 ) {
						$column['class'] = "{$type} multiple";
					}
					break;
				case 'price':
					// $price_type, $tax, $discount
					// using a combined value to make this more readable...
					$price_type_full = "{$price_type}_{$tax}_{$discount}";
					switch ($price_type_full) {
						// before discount
						case 'single_incl_before':
							$column['data'] = $item['single_price'];
							break;
						case 'single_excl_before':
							$column['data'] = $item['ex_single_price'];
							break;
						case 'total_incl_before':
							$column['data'] = $item['price'];
							break;
						case 'total_excl_before':
							$column['data'] = $item['ex_price'];
							break;

						// after discount
						case 'single_incl_after':
							$price = ( $item['item']['line_total'] + $item['item']['line_tax'] ) / max( 1, abs( $item['quantity'] ) );
							$column['data'] = $document->format_price( $price );
							break;
						case 'single_excl_after':
							$column['data'] = $item['single_line_total'];
							break;
						case 'total_incl_after':
							$price = $item['item']['line_total'] + $item['item']['line_tax'];
							$column['data'] = $document->format_price( $price );
							break;
						case 'total_excl_after':
							$column['data'] = $item['line_total'];
							break;
					}

					if ($price_type == 'total') {
						$column['class'] = 'total';
					}
					break;
				case 'regular_price':
					// $price_type, $tax, $only_sale
					$regular_prices = $this->get_regular_item_price( $item['item'], $item['item_id'], $document->order );

					// check if item price is different from sale price
					$single_item_price = ( $item['item']['line_subtotal'] + $item['item']['line_subtotal_tax'] ) / max( 1, $item['quantity'] );
					if ( isset($only_sale) && round( $single_item_price, 2 ) == round( $regular_prices['incl'], 2 ) ) {
						$column['data'] = '';
					} else {
						// get including or excluding tax
						$regular_price = $regular_prices[$tax];
						// single or total
						if ($price_type == 'total') {
							$regular_price = $regular_price * $item['quantity'];
						}
						$column['data'] = $document->format_price( $regular_price );
					}
					break;
				case 'discount':
					// $price_type, $tax
					if ($price_type == 'percent') {
						$discount = ( ($item['item']['line_subtotal'] + $item['item']['line_subtotal_tax']) - ( $item['item']['line_total'] + $item['item']['line_tax'] ) );
						if ($discount > 0) {
							$percent = round( ( $discount / ( $item['item']['line_subtotal'] + $item['item']['line_subtotal_tax'] ) ) * 100 );
							$column['data'] = "{$percent}%";
						} else {
							$column['data'] = "";
						}
						break;
					}
					
					$price_type = "{$price_type}_{$tax}";
					switch ($price_type) {
						case 'single_incl':
							$price = ( ($item['item']['line_subtotal'] + $item['item']['line_subtotal_tax']) - ( $item['item']['line_total'] + $item['item']['line_tax'] ) ) / max( 1, abs( $item['quantity'] ) );
							$column['data'] = $document->format_price( (float) $price * -1 );
							break;
						case 'single_excl':
							$price = ( $item['item']['line_subtotal'] - $item['item']['line_total'] ) / max( 1, abs( $item['quantity'] ) );
							$column['data'] = $document->format_price( (float) $price * -1  );
							break;
						case 'total_incl':
							$price = ($item['item']['line_subtotal'] + $item['item']['line_subtotal_tax']) - ( $item['item']['line_total'] + $item['item']['line_tax'] );
							$column['data'] = $document->format_price( (float) $price * -1  );
							break;
						case 'total_excl':
							$price = $item['item']['line_subtotal'] - $item['item']['line_total'];
							$column['data'] = $document->format_price( (float) $price * -1  );
							break;
					}
					break;
				case 'vat':
					// $price_type, $discount
					$price_type = "{$price_type}_{$discount}";
					switch ($price_type) {
						// before discount
						case 'single_before':
							$price = ( $item['item']['line_subtotal_tax'] ) / max( 1, $item['quantity'] );
							$column['data'] = $document->format_price( $price );
							break;
						case 'single_after':
							$price = ( $item['item']['line_tax'] ) / max( 1, $item['quantity'] );
							$column['data'] = $document->format_price( $price );
							break;
						case 'total_before':
							$column['data'] = $item['line_subtotal_tax'];
							break;
						case 'total_after':
							$column['data'] = $item['line_tax'];
							break;
					}
					break;
				case 'tax_rate':
					$column['data'] = $item['tax_rates'];
					break;
				case 'weight':
					if ( !isset($qty) ) {
						$qty = 'single';
					}

					switch ($qty) {
						case 'single':
							$column['data'] = !empty($item['weight']) ? $item['weight'] : '';
							break;
						case 'total':
							$column['data'] = !empty($item['weight']) ? $item['weight'] * $item['quantity'] : '';
							break;
					}
					if (isset($show_unit) && !empty($item['weight'])) {
						$column['data'] .= get_option('woocommerce_weight_unit');
					}
					break;
				case 'dimensions':
					$column['data'] = $this->get_product_dimensions( $item['product'] );
					break;
				case 'product_attribute':
					if (isset($item['product'])) {
						$attribute_name_class = sanitize_title( $attribute_name );
						$column['class'] = "{$type} {$attribute_name_class}";
						$column['data'] = $document->get_product_attribute( $attribute_name, $item['product'] );
					} else {
						$column['data'] = '';
					}
					break;
				case 'product_custom':
					// setup
					$meta_key_class = sanitize_title( $field_name );
					$column['class'] = "{$type} {$meta_key_class}";
					$column['data'] = $this->get_product_custom_field( $item['product'], $field_name );
					break;
				case 'product_description':
					$column['data'] = $this->get_product_description( $item['product'], $description_type, $use_variation_description );
					break;
				case 'product_categories':
					$column['data'] = $this->get_product_categories( $item['product'] );
					break;
				case 'all_meta':
					// $product_fallback
					// For an order added through the admin) we can display
					// the formatted variation data (if fallback enabled)
					if ( isset($product_fallback) && empty($item['meta']) && isset($item['product']) && function_exists('wc_get_formatted_variation') ) {
						$variation_data = WCX_Product::get_prop( $item['product'], 'variation_data' );
						$item['meta'] = wc_get_formatted_variation( $variation_data, true );
					}
					$column['data'] = '<span class="item-meta">'.$item['meta'].'</span>';
					break;
				case 'item_meta':
					// $field_name
					if ( !empty($field_name) ) {
						$column['data'] = $this->get_order_item_meta( $item, $field_name );
					} else {
						$column['data'] = '';
					}
					break;
				case 'cb':
					$column['data'] = '<span class="checkbox"></span>';
					break;
				case 'static_text':
					// $text
					$column['data'] = !empty( $text ) ? nl2br( wptexturize( $this->make_item_replacements( $text, $item, $document ) ) ) : '';
					break;

				default:
					$column['data'] = '';
					break;
			}

			// set class if not set;
			if (!isset($column['class'])) {
				$column['class'] = $type;
			}

			// mark first and last column
			if (isset($position)) {
				$column['class'] .= " {$position}-column";
			}

			return apply_filters( 'wpo_wcpdf_templates_item_column_data', $column, $column_setting, $item, $document );
		}

		/**
		 * Output custom blocks (if set for template)
		 */
		public function custom_blocks_data( $template_type, $order = null ) {
			$editor_settings = get_option('wpo_wcpdf_editor_settings');
			if (!empty($editor_settings["fields_{$template_type}_custom"])) {
				foreach ($editor_settings["fields_{$template_type}_custom"] as $key => $custom_block) {
					// echo "<pre>";var_dump($custom_block);echo "</pre>";die();
					if ( current_filter() != $custom_block['position']) {
						continue;
					}

					// only process blocks with input
					if ( ( $custom_block['type'] == 'custom_field' || $custom_block['type'] == 'user_meta' ) && empty( $custom_block['meta_key'] ) ) {
						continue;
					} elseif ( $custom_block['type'] == 'text' && empty( $custom_block['text'] ) ) {
						continue;
					}

					switch ($custom_block['type']) {
						case 'custom_field':
							if ( empty( $order ) ) {
								continue 2;
							}
							if ( $this->check_custom_block_condition( $custom_block, $order ) == false ) {
								continue 2;
							}
							$order_id = WCX_Order::get_id( $order );

							$class = $custom_block['meta_key'];

							// support for array data
							$array_key_position = strpos( $custom_block['meta_key'], '[' );
							if ( $array_key_position !== false ) {
								$array_key = trim( substr( $custom_block['meta_key'], $array_key_position), "[]'");
								$meta_key = strtok( $custom_block['meta_key'], '[' );
								$array_data = WCX_Order::get_meta( $order, $meta_key, true, 'view' );
								// parent order fallback
								if ( empty($array_data) && get_post_type( $order_id ) == 'shop_order_refund' && $parent_order_id = wp_get_post_parent_id( $order_id ) ) {
									$parent_order = WCX::get_order( $parent_order_id );
									$array_data = WCX_Order::get_meta( $parent_order, $meta_key, true, 'view' );
								}
								if ( is_array( $array_data ) && !empty( $array_data[$array_key] ) ) {
									$data = $array_data[$array_key];
									break;
								}
							}
							$data = WCX_Order::get_meta( $order, $custom_block['meta_key'], true, 'view' );
							// parent order fallback
							if ( empty($data) && get_post_type( $order_id ) == 'shop_order_refund' && $parent_order_id = wp_get_post_parent_id( $order_id ) ) {
								$parent_order = WCX::get_order( $parent_order_id );
								$data = WCX_Order::get_meta( $parent_order, $custom_block['meta_key'], true, 'view' );
							}
							
							// format date fields with WC format automatically
							$data = $this->maybe_format_date_field( $data, $custom_block['meta_key'] );

							// format array data
							if ( is_array( $data ) ) {
								$data_strings = array();
								foreach ($data as $key => $value) {
									if ( !is_array($value) && !is_object($value) ) {
										$data_strings[] = "$key: $value";
									}
								}
								$data = implode(', ', $data_strings);
							}

							// WC3.0+ fallback to properties
							$property = str_replace('-', '_', sanitize_title( ltrim( $custom_block['meta_key'], '_' ) ) );
							if ( empty( $data ) && is_callable( array( $order, "get_{$property}" ) ) ) {
								$data = $order->{"get_{$property}"}( 'view' );
							}

							break;
						case 'user_meta':
							if ( empty( $order ) ) {
								continue 2;
							}
							if ( $this->check_custom_block_condition( $custom_block, $order ) == false ) {
								continue 2;
							}
							$order_id = WCX_Order::get_id( $order );
							if ( get_post_type( $order_id ) == 'shop_order_refund' && $parent_order_id = wp_get_post_parent_id( $order_id ) ) {
								$parent_order = WCX::get_order( $parent_order_id );
								$user_id = $parent_order->get_user_id();
							} else {
								$user_id = $order->get_user_id();
							}
							if ( !empty($user_id) ) {
								$meta_key = $custom_block['meta_key']; 
								$data = get_user_meta( $user_id, $meta_key, true );
							} else {
								$data = '';
							}
							$class = $custom_block['meta_key'];
							break;
						case 'text':
							if ( !empty( $order ) && $this->check_custom_block_condition( $custom_block, $order ) == false ) {
								continue 2;
							}
							if ( !empty( $order ) ) {
								$document = wcpdf_get_document( $template_type, $order );
								$formatted_text = $this->make_replacements( $custom_block['text'], $order, $document );
							} else {
								$formatted_text = $custom_block['text'];
							}
							$data =  nl2br( wptexturize( $formatted_text ) );
							$class = 'custom-block-text';
							break;						
					}

					// Hide if empty option
					if ( !empty($custom_block['hide_if_empty']) ) {
						if ( $custom_block['type'] == 'text' && empty( strip_tags( $data ) ) ) {
							continue;
						} elseif ( $custom_block['type'] != 'text' && empty( $data ) ){
							continue;
						}
					}

					// output table rows if in order data table
					if ( in_array( current_filter(), array( 'wpo_wcpdf_before_order_data', 'wpo_wcpdf_after_order_data') ) ) {
						printf('<tr class="%s"><th>%s</th><td>%s</td></tr>', $class, $custom_block['label'], $data );
					} else {
						if (!empty($custom_block['label'])) {
							printf('<h3 class="%s-label">%s</h3>', $class, $custom_block['label'] );
						}
						// only apply div wrapper if not already in div
						if ( stripos($data, '<div') !== false ) {
							echo $data;
						} else {
							printf('<div class="%s">%s</div>', $class, $data );
						}
					}
				};
			}
		}

		public function check_custom_block_condition( $custom_block, $order ) {
			if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '<' ) ) {
				return true; // function disabled for WC2.X
			}

			// we're always checking against the parent order data for refunds
			if ( $order->get_type() == 'shop_order_refund' ) {
				$order = wc_get_order( $order->get_parent_id() );
			}

			// var_dump( !empty($custom_block['order_statuses']) && is_array($custom_block['order_statuses']) );die();
			// Order status
			if ( !empty($custom_block['order_statuses']) && is_array($custom_block['order_statuses']) && is_callable(array($order,'get_status')) ) {
				// Standardise status names (make sure wc-prefix is used)
				$order_status = 'wc-' === substr( $order->get_status(), 0, 3 ) ? $order->get_status() : 'wc-' . $order->get_status();
				if ( !in_array($order_status, $custom_block['order_statuses']) ) {
					return false;
				}
			}

			// Payment Method
			if ( !empty($custom_block['payment_methods']) && is_array($custom_block['payment_methods']) && is_callable(array($order,'get_payment_method')) ) {
				if ( !in_array($order->get_payment_method(), $custom_block['payment_methods']) ) {
					return false;
				}
			}

			// VAT reverse charge
			if ( !empty($custom_block['vat_reverse_charge']) ) {
				$is_eu_vat = in_array( $order->get_billing_country(), WC()->countries->get_european_union_countries( 'eu_vat' ) );
				if ( $is_eu_vat && $order->get_total() > 0 && $order->get_total_tax() == 0 ) {
					// Try fetching VAT Number from meta
					$vat_meta_keys = array (
						'_vat_number', // WooCommerce EU VAT Number
						'VAT Number', // WooCommerce EU VAT Compliance
						'_eu_vat_evidence' // Aelia EU VAT Assistant
					);

					foreach ($vat_meta_keys as $meta_key) {
						if ( $vat_number = $order->get_meta( $meta_key ) ) {
							// Aelia EU VAT Assistant stores the number in a multidimensional array
							if ($meta_key == '_eu_vat_evidence' && is_array($vat_number)) {
								$vat_number = !empty($vat_number['exemption']['vat_number']) ? $vat_number['exemption']['vat_number'] : '';
							}
							break;
						}
					}
					
				}
				// if we got here and we don't have a VAT number,
				// this is NOT a 0 tax order from the EU either
				if ( ! apply_filters( 'wpo_wcpdf_vat_reverse_charge_order', ! empty( $vat_number ), $order ) ) {
					return false;
				}
			}

			// 's all good man
			return true;
		}

		public function settings_fields_replacements( $text, $document ) {
			// make replacements if placeholders present
			if ( strpos( $text, '{{' ) !== false ) {
				$text = $this->make_replacements( $text, $document->order, $document );
			}

			return $text;
		}

		public function make_replacements ( $text, $order, $document = null ) {
			$order_id = WCX_Order::get_id( $order );

			// load parent order for refunds
			if ( get_post_type( $order_id ) == 'shop_order_refund' && $parent_order_id = wp_get_post_parent_id( $order_id ) ) {
				$parent_order = WCX::get_order( $parent_order_id );
			}

			// make an index of placeholders used in the text
			preg_match_all('/\{\{.*?\}\}/', $text, $placeholders_used);
			$placeholders_used = array_shift($placeholders_used); // we only need the first match set

			// load countries & states
			$countries = new WC_Countries;

			// loop through placeholders and make replacements
			foreach ($placeholders_used as $placeholder) {
				$placeholder_clean = trim($placeholder,"{{}}");
				$ignore = array( '{{PAGE_NUM}}', '{{PAGE_COUNT}}' );
				if (in_array($placeholder, $ignore)) {
					continue;
				}

				// first try to read data from order, fallback to parent order (for refunds)
				$data_sources = array( 'order', 'parent_order' );
				foreach ($data_sources as $data_source) {
					if (empty($$data_source)) {
						continue;
					}

					// custom/third party filters
					$filter = "wpo_wcpdf_templates_replace_".sanitize_title( $placeholder_clean );
					if ( has_filter( $filter ) ) {
						$custom_filtered = apply_filters( $filter, null, $$data_source );
						if ( isset( $custom_filtered ) ) {
							$text = str_replace($placeholder, $custom_filtered, $text);
							continue 2;
						}
					}

					// special treatment for country & state
					$country_placeholders = array( 'shipping_country', 'billing_country' );
					$state_placeholders = array( 'shipping_state', 'billing_state' );
					foreach ( array_merge($country_placeholders, $state_placeholders) as $country_state_placeholder ) {
						if ( strpos( $placeholder_clean, $country_state_placeholder ) !== false ) {
							// check if formatting is needed
							if ( strpos($placeholder_clean, '_code') !== false ) {
								// no country or state formatting
								$placeholder_clean = str_replace('_code', '', $placeholder_clean);
								$format = false;
							} else {
								$format = true;
							}

							$country_or_state = WCX_Order::get_prop( $$data_source, $placeholder_clean );

							if ($format === true) {
								// format country or state
								if (in_array($placeholder_clean, $country_placeholders)) {
									$country_or_state = ( $country_or_state && isset( $countries->countries[ $country_or_state ] ) ) ? $countries->countries[ $country_or_state ] : $country_or_state;
								} elseif (in_array($placeholder_clean, $state_placeholders)) {
									// get country for address
									$country = WCX_Order::get_prop( $$data_source, str_replace( 'state', 'country', $placeholder_clean ) );
									$country_or_state = ( $country && $country_or_state && isset( $countries->states[ $country ][ $country_or_state ] ) ) ? $countries->states[ $country ][ $country_or_state ] : $country_or_state;
								}
							}

							if ( !empty( $country_or_state ) ) {
								$text = str_replace($placeholder, $country_or_state, $text);
								continue 3;
							}
						}
					}

					// date offset placeholders
					if ( strpos($placeholder_clean, '|+') !== false ) {
						$calculated_date = '';
						$placeholder_args = explode('|+', $placeholder_clean);
						if (!empty($placeholder_args[1])) {
							$date_name = $placeholder_args[0];
							$date_offset = $placeholder_args[1];
							switch ($date_name) {
								case 'order_date':
									$order_date = WCX_Order::get_prop( $$data_source, 'date_created' );
									$calculated_date = date_i18n( wc_date_format(), strtotime( $order_date->date_i18n('Y-m-d H:i:s') . " + {$date_offset}") );
									break;
								case 'invoice_date':
									$invoice_date_set = WCX_Order::get_meta( $$data_source, "_wcpdf_invoice_date" );
									// prevent creating invoice date when not already set
									if (!empty($invoice_date_set) && !empty($document)) {
										$invoice_date = $document->get_date('invoice');
										$calculated_date = date_i18n( wc_date_format(), strtotime( $invoice_date->date_i18n('Y-m-d H:i:s') . " + {$date_offset}" ) );
									}
									break;
							}
						}
						if (!empty($calculated_date)) {
							$text = str_replace($placeholder, $calculated_date, $text);
							continue 2;
						}
					}

					// Custom placeholders
					$custom = '';
					switch ($placeholder_clean) {
						case 'invoice_number':
							if (!empty($document)) {
								$custom = $document->get_invoice_number();
							}
							break;
						case 'invoice_date':
							$invoice_date = WCX_Order::get_meta( $$data_source, "_wcpdf_invoice_date" );
							// prevent creating invoice date when not already set
							if (!empty($invoice_date) && !empty($document)) {
								$custom = $document->get_invoice_date();
							}
							break;
						case 'document_number':
							if (!empty($document)) {
								if ( $number = $document->get_number() ) {
									$custom = $number->get_formatted();
								}
							}
							break;
						case 'document_date':
							if (!empty($document)) {
								if ( $date = $document->get_date() ) {
									$custom = $date->date_i18n( wc_date_format() );
								}
							}
							break;
						case 'site_title':
							$custom = get_bloginfo();
							break;
						case 'shipping_notes':
						case 'customer_note':
							$custom = WCX_Order::get_prop( $$data_source, 'customer_note', 'view' );
							if (!empty($custom)) {
								$custom = wpautop( wptexturize( $custom ) );
							}
							break;
						case 'order_notes':
							$custom = $this->get_order_notes( $$data_source );
							break;
						case 'private_order_notes':
							$custom = $this->get_order_notes( $$data_source, 'private' );
							break;
						case 'order_number':
							if ( method_exists( $$data_source, 'get_order_number' ) ) {
								$custom = ltrim($$data_source->get_order_number(), '#');
							} else {
								$custom = '';
							}
							break;
						case 'order_status':
							if ( version_compare( WOOCOMMERCE_VERSION, '2.2', '>=' ) ) {
								$custom = wc_get_order_status_name( $$data_source->get_status() );
							} else {
								$status = get_term_by( 'slug', $$data_source->status, 'shop_order_status' );
								$custom = __( $status->name, 'woocommerce' );
							}
							break;
						case 'payment_status':
							if ( is_callable( array( $$data_source, 'is_paid' ) ) ) {
								$custom = $$data_source->is_paid() ? __( 'Paid', 'wpo_wcpdf_templates' ) : __( 'Unpaid', 'wpo_wcpdf_templates' );
							}
							break;
						case 'order_date':
							$order_date = WCX_Order::get_prop( $$data_source, 'date_created' );
							$custom = $order_date->date_i18n( wc_date_format() );
							break;
						case 'order_time':
							$order_date = WCX_Order::get_prop( $$data_source, 'date_created' );
							$custom = $order_date->date_i18n( wc_time_format() );
							break;
						case 'order_weight':
							$custom = $this->get_order_weight( $$data_source, $document );
							break;
						case 'order_qty':
							$custom = $this->get_order_total_qty( $$data_source, $document );
							break;
						case 'date_paid':
						case 'paid_date':
							$date_paid = WCX_Order::get_prop( $$data_source, 'date_paid' );
							$custom = !empty($date_paid) ? $date_paid->date_i18n( wc_date_format() ) : '-';
							break;
						case 'date_completed':
						case 'completed_date':
							$date_completed = WCX_Order::get_prop( $$data_source, 'date_completed' );
							$custom = !empty($date_completed) ? $date_completed->date_i18n( wc_date_format() ) : '-';
							break;
						case 'current_date':
							$custom = date_i18n( wc_date_format() );
							break;
						case 'payment_method_description':
							if ( $payment_gateway = wc_get_payment_gateway_by_order( $$data_source ) ) {
								$custom = $payment_gateway->get_method_description();
							}
							break;
						case 'payment_method_instructions':
							if ( $payment_gateway = wc_get_payment_gateway_by_order( $$data_source ) ) {
								if ( isset( $payment_gateway->instructions ) ) {
									$custom = $payment_gateway->instructions;
								}
							}
							break;
						case 'payment_method_thankyou_page_text':
							if ( $payment_gateway = wc_get_payment_gateway_by_order( $$data_source ) ) {
								if ( method_exists( $payment_gateway, 'thankyou_page' ) ) {
									ob_start();
									$payment_gateway->thankyou_page( WCX_Order::get_id( $$data_source ) );
									$custom = ob_get_clean();
									if (!empty($custom)) {
										$custom = str_replace( PHP_EOL, '', $custom );
									}
								}
							}
							break;
						case 'used_coupons':
							$custom = implode(', ', $$data_source->get_used_coupons() );
							$text = str_replace($placeholder, $custom, $text);
							continue 3; // do not fallback to parent order
							break;
						case 'current_user_name':
							$user = wp_get_current_user();
							if ( $user instanceof \WP_User ) {
								$custom = $user->display_name;
							}
							break;
						case 'formatted_order_total':
							if (!empty($document)) {
								$grand_total 	= $document->get_order_grand_total('incl');
								$custom			= $grand_total['value'];
							}
							break;
						case 'formatted_subtotal':
							if (!empty($document)) {
								$subtotal 		= $document->get_order_subtotal('incl');
								$custom			= $subtotal['value'];
							}
							break;
						case 'formatted_discount':
							if (!empty($document)) {
								$discount 		= $document->get_order_discount('total', 'incl');
								$custom			= isset($discount['value']) ? $discount['value'] : '';
							}
							break;
						case 'formatted_shipping':
							if (!empty($document)) {
								$shipping 		= $document->get_order_shipping('incl');
								$custom			= $shipping['value'];
							}
							break;
						case 'formatted_order_total_ex':
							if (!empty($document)) {
								$grand_total 	= $document->get_order_grand_total('excl');
								$custom			= $grand_total['value'];
							}
							break;
						case 'formatted_subtotal_ex':
							if (!empty($document)) {
								$subtotal 		= $document->get_order_subtotal('excl');
								$custom			= $subtotal['value'];
							}
							break;
						case 'formatted_discount_ex':
							if (!empty($document)) {
								$discount 		= $document->get_order_discount('total', 'excl');
								$custom			= isset($discount['value']) ? $discount['value'] : '';
							}
							break;
						case 'formatted_shipping_ex':
							if (!empty($document)) {
								$shipping 		= $document->get_order_shipping('excl');
								$custom			= $shipping['value'];
							}
							break;
						case 'wc_order_barcode':
							if ( function_exists('WC_Order_Barcodes') ) {
								$barcode_url = WC_Order_Barcodes()->barcode_url( WCX_Order::get_id( $$data_source ) );
								$barcode_text = WCX_Order::get_meta( $$data_source, "_barcode_text" );
								if (WC_Order_Barcodes()->barcode_type == 'qr') {
									$css = 'height: 40mm; width: 40mm; position:relative';
								} else {
									$css = 'height: 10mm; width: 40mm; overflow:hidden; position:relative';
								}
								$custom = sprintf('<div style="text-align: center; width: 40mm;" class="wc-order-barcode"><div style="%s"><img src="%s" style="width: 40mm; height:40mm; position: absolute; bottom: 0mm; left: 0;"/></div><span class="wc-order-barcodes-text">%s</span></div>', $css, $barcode_url, $barcode_text );
							}
							break;
						case 'local_pickup_plus_pickup_details':
							$custom = $this->get_local_pickup_plus_pickup_details( $$data_source );
							break;
						case 'wpo_wcpdf_shop_name':
							if (!empty($document)) {
								$custom = $document->get_shop_name();
							}
							break;
						case 'checkout_payment_url':
						case 'payment_url':
							if (is_callable(array($$data_source,'get_checkout_payment_url'))) {
								$custom = $$data_source->get_checkout_payment_url();
							}
							break;
						default:
							break;
					}
					if ( !empty( $custom ) ) {
						$text = str_replace($placeholder, $custom, $text);
						continue 2;
					}

					// Order Properties
					if (in_array($placeholder_clean, array('shipping_address', 'billing_address'))) {
						$placeholder_clean = "formatted_{$placeholder_clean}";
					}

					$property_meta_keys = array(
						'_order_currency'		=> 'currency',
						'_order_tax'			=> 'total_tax',
						'_order_total'			=> 'total',
						'_order_version'		=> 'version',
						'_order_shipping'		=> 'shipping_total',
						'_order_shipping_tax'	=> 'shipping_tax',
					);
					if (in_array($placeholder_clean, array_keys($property_meta_keys))) {
						$property_name = $property_meta_keys[$placeholder_clean];
					} else {
						$property_name = str_replace('-', '_', sanitize_title( ltrim($placeholder_clean, '_') ) );
					}
					$prop = WCX_Order::get_prop( $$data_source, $property_name, 'view' );
					if ( !empty( $prop ) ) {
						$text = str_replace($placeholder, $prop, $text);
						continue 2;
					}

					// Order Meta
					if ( !$this->is_order_prop( $placeholder_clean ) ) {
						$meta = WCX_Order::get_meta( $$data_source, $placeholder_clean, true, 'view' );
						if ( !empty( $meta ) ) {
							// format date fields with WC format automatically
							$meta = $this->maybe_format_date_field( $meta, $placeholder_clean );

							$text = str_replace($placeholder, $meta, $text);
							continue 2;
						} else {
							// Fallback to hidden meta
							$meta = WCX_Order::get_meta( $$data_source, "_{$placeholder_clean}", true, 'view' );
							if ( !empty( $meta ) ) {
								$text = str_replace($placeholder, $meta, $text);
								continue 2;
							}
						}
					}
				}

				// remove placeholder if no replacement was made
				$text = str_replace($placeholder, '', $text);
			}

			return $text;
		}

		public function maybe_format_date_field( $date_value, $meta_key ) {
			$known_date_fields = array(
				'_local_pickup_time_select', // WooCommerce Local Pickup Time Select - array with timestamp
				'ywcdd_order_delivery_date', // YITH WooCommerce Delivery Date Premium
				'_delivery_date', // WooCommerce Order Delivery ... or generic
			);

			if ( in_array( $meta_key, $known_date_fields ) ) {
				if ( $meta_key == '_local_pickup_time_select' && is_array( $date_value ) ) {
					$date_value = array_shift( $date_value );
				}

				// could be timestamp or formatted date
				if ( is_numeric( $date_value ) ) {
					$timestamp = intval( $date_value );
				} elseif ( is_string( $date_value ) ) {
					$timestamp = strtotime( $date_value );
				} else { // not something we can use
					return $date_value;
				}

				// sanity check (party like it's 1999, huh?)
				if ( $timestamp > strtotime( '1999-12-31' ) ) {
					// determine whether to include time in formatted date (if the original format had it)
					if ( $meta_key == '_local_pickup_time_select' || ( !is_numeric( $date_value ) && strpos( (string) $date_value, ':' ) !== false ) ) {
						$date_format = wc_date_format() . ' ' . wc_time_format();
					} else {
						$date_format = wc_date_format();
					}

					$date_value = date_i18n( apply_filters( 'wpo_wcpdf_templates_date_field_format', $date_format ), $timestamp );
				}
			}

			return $date_value;
		}

		public function is_order_prop( $key ) {
			// Taken from WC class
			$order_props = array(
				// Abstract order props
				'parent_id',
				'status',
				'currency',
				'version',
				'prices_include_tax',
				'date_created',
				'date_modified',
				'discount_total',
				'discount_tax',
				'shipping_total',
				'shipping_tax',
				'cart_tax',
				'total',
				'total_tax',
				// Order props
				'customer_id',
				'order_key',
				'billing_first_name',
				'billing_last_name',
				'billing_company',
				'billing_address_1',
				'billing_address_2',
				'billing_city',
				'billing_state',
				'billing_postcode',
				'billing_country',
				'billing_email',
				'billing_phone',
				'shipping_first_name',
				'shipping_last_name',
				'shipping_company',
				'shipping_address_1',
				'shipping_address_2',
				'shipping_city',
				'shipping_state',
				'shipping_postcode',
				'shipping_country',
				'payment_method',
				'payment_method_title',
				'transaction_id',
				'customer_ip_address',
				'customer_user_agent',
				'created_via',
				'customer_note',
				'date_completed',
				'date_paid',
				'cart_hash',
			);
			return in_array($key, $order_props);
		}

		public function make_item_replacements( $text, $item, $document ) {
			// make replacements if placeholders present
			if ( strpos( $text, '{{' ) === false ) {
				return $text;
			}

			// make an index of placeholders used in the text
			preg_match_all('/\{\{.*?\}\}/', $text, $placeholders_used);
			$placeholders_used = array_shift($placeholders_used); // we only need the first match set

			// loop through placeholders and make replacements
			foreach ($placeholders_used as $placeholder) {
				$replacement = null;
				$placeholder_clean = trim($placeholder,"{{}}");

				// custom product field placeholders
				if ( strpos($placeholder_clean, 'product_custom_field::') !== false ) {
					$meta_key = trim(str_replace('product_custom_field::', '', $placeholder_clean));
					if (!empty($meta_key)) {
						$replacement = $this->get_product_custom_field( $item['product'], $meta_key );
						if (!empty($replacement)) {
							$text = str_replace($placeholder, $replacement, $text);
							continue;
						}
					}
				}

				// custom product field placeholders
				if ( strpos($placeholder_clean, 'item_meta::') !== false ) {
					$meta_key = trim(str_replace('item_meta::', '', $placeholder_clean));
					if (!empty($meta_key)) {
						$replacement = $this->get_order_item_meta( $item, $meta_key );
						if (!empty($replacement)) {
							$text = str_replace($placeholder, $replacement, $text);
							continue;
						}
					}
				}

				// product attribute placeholders
				if ( strpos($placeholder_clean, 'product_attribute::') !== false && !empty( $item['product'] ) ) {
					$attribute_name = trim(str_replace('product_attribute::', '', $placeholder_clean));
					if (!empty($attribute_name)) {
						$replacement = $document->get_product_attribute( $attribute_name, $item['product'] );
						if (!empty($replacement)) {
							$text = str_replace($placeholder, $replacement, $text);
							continue;
						}
					}
				}

				switch ($placeholder_clean) {
					case 'product_description':
						$replacement = $this->get_product_description( $item['product'] );
						break;
					case 'product_description_short':
						$replacement = $this->get_product_description( $item['product'], 'short' );
						break;
					case 'product_description_long':
						$replacement = $this->get_product_description( $item['product'], 'long' );
						break;
					case 'product_categories':
						$replacement = $this->get_product_categories( $item['product'] );
						break;
					case 'product_tags':
						$replacement = $this->get_product_tags( $item['product'] );
						break;
					case 'purchase_note':
						$replacement = $this->get_product_purchase_note( $item['product'] );
						break;
					case 'product_dimensions':
						$replacement = $this->get_product_dimensions( $item['product'] );
						break;
					case 'sale_price_discount_excl_tax':
						$replacement = $this->get_sale_price_discount( $item['item'], $item['item_id'], $document->order, 'price_excl_tax' );
						break;
					case 'sale_price_discount_incl_tax':
						$replacement = $this->get_sale_price_discount( $item['item'], $item['item_id'], $document->order, 'price_incl_tax' );
						break;
					case 'sale_price_discount_percent':
						$replacement = $this->get_sale_price_discount( $item['item'], $item['item_id'], $document->order, 'percent' );
						break;
					case 'wc_brands':
						$replacement = $this->get_product_brands( $item['product'] );
						break;
				}

				if (!empty($replacement)) {
					$text = str_replace($placeholder, $replacement, $text);
					continue;
				}

				// remove placeholder if no replacement was made
				$text = str_replace($placeholder, '', $text);
			}

			return $text;
		}

		public function get_product_custom_field( $product, $meta_key ) {
			if (isset($product) && !empty($meta_key)) {
				// backwards compatible meta keys of properties
				$property_meta_keys = array(
					'_stock'		=> 'stock_quantity',
				);
				$property = in_array($meta_key, array_keys($property_meta_keys)) ? $property_meta_keys[$meta_key] : str_replace('-', '_', sanitize_title( ltrim($meta_key, '_') ) );

				// try actual product first, starting with properties
				if ( is_callable( array( $product, "get_{$property}" ) ) ) {
					$custom = $product->{"get_{$property}"}( 'view' );
				}
				if ( empty( $custom ) ) {
					$custom = WCX_Product::get_meta( $product, $meta_key, true, 'view' );
				}
				
				// fallback to parent for variations (WC3+)
				if ( empty($custom) && version_compare( WOOCOMMERCE_VERSION, '3.0', '>=' ) && $product->is_type( 'variation' ) ) {
					$_product = wc_get_product( $product->get_parent_id() );
					// try actual product first, starting with properties
					if ( is_callable( array( $_product, "get_{$property}" ) ) ) {
						$custom = $_product->{"get_{$property}"}( 'view' );
					}
					if ( empty( $custom ) ) {
						$custom = WCX_Product::get_meta( $_product, $meta_key, true, 'view' );
					}
				}

				return $custom;
			} else {
				return '';
			}
		}

		public function get_order_item_meta( $document_item, $meta_key ) {
			return wc_get_order_item_meta( $document_item['item_id'], $meta_key, true );
		}

		public function get_product_description( $product, $type = 'short', $use_variation_description = true ) {
			if (!empty($product)) {
				if ( isset( $use_variation_description ) && $product->is_type( 'variation' ) && version_compare( WOOCOMMERCE_VERSION, '2.4', '>=' ) ) {
					$description = $product->get_variation_description();
				} else {
					if ( version_compare( WOOCOMMERCE_VERSION, '3.0', '>=' ) && $product->is_type( 'variation' ) ) {
						$_product = wc_get_product( $product->get_parent_id() );
					} else {
						$_product = $product;
					}
					switch ($type) {
						case 'short':
							if ( method_exists( $_product, 'get_short_description' ) ) {
								$description = $_product->get_short_description();
							} else {
								$description =  $_product->post->post_excerpt;
							}
							break;
						case 'long':
							if ( method_exists( $_product, 'get_description' ) ) {
								$description = $_product->get_description();
							} else {
								$description = $_product->post->post_content;
							}
							break;
					}
				}
			} else {
				$description = '';
			}

			return $description;
		}

		public function get_product_categories( $product ) {
			if (isset($product)) {
				if (function_exists('wc_get_product_category_list')) {
					// WC3.0+
					if ( $product->is_type( 'variation' ) ) {
						// variations don't have categories so we take the parent
						$category_list = wc_get_product_category_list( $product->get_parent_id() );
					} else {
						$category_list = wc_get_product_category_list( $product->get_id() );
					}
				} else {
					$category_list = $product->get_categories();
				}
				$product_categories = strip_tags( $category_list );
			} else {
				$product_categories = '';
			}
			return $product_categories;
		}

		public function get_product_tags( $product ) {
			if (isset($product)) {
				if (function_exists('wc_get_product_tag_list')) {
					// WC3.0+
					if ( $product->is_type( 'variation' ) ) {
						// variations don't have tags so we take the parent
						$tag_list = wc_get_product_tag_list( $product->get_parent_id() );
					} else {
						$tag_list = wc_get_product_tag_list( $product->get_id() );
					}
				} else {
					$tag_list = $product->get_tags();
				}
				$product_tags = strip_tags( $tag_list );
			} else {
				$product_tags = '';
			}
			return $product_tags;
		}

		public function get_product_purchase_note( $product ) {
			if (!empty($product)) {
				$purchase_note = method_exists($product, 'get_purchase_note') ? $product->get_purchase_note() : $product->purchase_note;
				$purchase_note = do_shortcode( wp_kses_post( $purchase_note ) );
			} else {
				$purchase_note = '';
			}
			return $purchase_note;
		}

		public function get_product_dimensions( $product ) {
			if ( !empty($product) && function_exists('wc_format_dimensions') && is_callable( array( $product, 'get_dimensions' ) ) ) {
				return wc_format_dimensions( $product->get_dimensions( false ) );
			} else {
				return '';
			}
		}

		public function get_sale_price_discount( $item, $item_id, $order, $type = null ) {
			$regular_prices = $this->get_regular_item_price( $item, $item_id, $order );

			if ( round( $item['line_total'], 2 ) == round( $regular_prices['excl'] * $item['qty'], 2 ) ) {
				return '';
			}

			switch ($type) {
				default:
				case 'price_excl_tax':
					$item_price = $item['line_total']; // before coupon discounts
					$regular_price = $regular_prices['excl'] * $item['qty'];
					return wc_price( $regular_price - $item_price, array ( 'currency' => $order->get_currency() ) );
					break;
				case 'price_incl_tax':
					$item_price = $item['line_total'] + $item['line_tax']; // before coupon discounts
					$regular_price = $regular_prices['incl'] * $item['qty'];
					return wc_price( $regular_price - $item_price, array ( 'currency' => $order->get_currency() ) );
					break;
				case 'percent':
					$item_price = $item['line_total'] + $item['line_tax']; // before coupon discounts
					$regular_price = $regular_prices['incl'] * $item['qty'];
					$percent = round( ( ( $regular_price - $item_price ) / $regular_price ) * 100 );
					return "{$percent}%";
					break;
			}
		}

		public function get_product_brands( $product ) {
			if ( function_exists('get_brands') && !empty($product) ) {
				if ( $product->is_type( 'variation' ) ) {
					$product_id = method_exists( $product, 'get_parent_id' ) ? $product->get_parent_id() : wp_get_post_parent_id( $product->get_id() );
				} else {
					$product_id = $product->get_id();
				}

				$terms = get_the_terms( $product_id, 'product_brand' );
				$brand_count = is_array( $terms ) ? sizeof( $terms ) : 0;
				if ( $brand_count == 0 ) {
					return '';
				}

				$taxonomy = get_taxonomy( 'product_brand' );
				$labels   = $taxonomy->labels;

				$brands = get_brands( $product_id, ', ' );
				$label = '<span class="wc-brands-label">' . sprintf( _n( '%1$s: ', '%2$s: ', $brand_count ), $labels->singular_name, $labels->name ). '</span>';
				return sprintf( '<div class="brands">%s %s</div>', $label, $brands );
			} else {
				return '';
			}
		}

		public function get_order_notes( $order, $filter = 'customer' ) {
			$order_id = WCX_Order::get_id( $order );
			if ( get_post_type( $order_id ) == 'shop_order_refund' && $parent_order_id = wp_get_post_parent_id( $order_id ) ) {
				$post_id = $parent_order_id;
			} else {
				$post_id = $order_id;
			}

			$args = array(
				'post_id' 	=> $post_id,
				'approve' 	=> 'approve',
				'type' 		=> 'order_note'
			);

			remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

			$notes = get_comments( $args );

			add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

			if ( $notes ) {
				$formatted_notes = array();
				foreach( $notes as $key => $note ) {
					if ( $filter == 'customer' && !get_comment_meta( $note->comment_ID, 'is_customer_note', true ) ) {
						unset($notes[$key]);
						continue;
					}
					if ( $filter == 'private' && get_comment_meta( $note->comment_ID, 'is_customer_note', true ) ) {
						unset($notes[$key]);
						continue;
					}
					$note_classes   = array( 'note_content' );
					$note_classes[] = ( __( 'WooCommerce', 'woocommerce' ) === $note->comment_author ) ? 'system-note' : '';

					$formatted_notes[$key] = sprintf( '<div class="%s">%s</div>', esc_attr( implode( ' ', $note_classes ) ), wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ) );
				}
				return implode("\n", $formatted_notes);
			} else {
				return false;
			}
		}

		public function add_tax_base( $taxes, $order ) {
			$tax_rates_base = $this->get_tax_rates_base( $order );
			foreach ($taxes as $item_id => $tax) {
				if ( isset( $tax_rates_base[$tax['rate_id']] ) ) {
					$taxes[$item_id]['base'] = $tax_rates_base[$tax['rate_id']]->base;
					$taxes[$item_id]['calculated_rate'] = $tax_rates_base[$tax['rate_id']]->calculated_rate;
				}

				$created_via = WCX_Order::get_prop( $order, 'created_via' );
				if ( $created_via == 'subscription' ) {
					// subscription renewals didn't properly record the rate_percent property between WC3.7 and WCS3.0.1
					// so we use a fallback if the rate_percent = 0
					// if we the tax is bigger than 0 stored the rate percentage in the past, use that
					$tax_amount = $tax['tax_amount'] + $tax['shipping_tax_amount'];
					if ( $tax_amount > 0 && isset($tax_rates_base[$tax['rate_id']]->rate_percent) && $tax_rates_base[$tax['rate_id']]->rate_percent > 0 ) {
						$taxes[$item_id]['stored_rate'] = $this->format_tax_rate( $tax_rates_base[$tax['rate_id']]->rate_percent );
					} elseif ( is_numeric($item_id) && $tax_amount > 0 && $stored_rate = wc_get_order_item_meta( absint($item_id), '_wcpdf_rate_percentage', true ) ) {
						$taxes[$item_id]['stored_rate'] = $this->format_tax_rate( $stored_rate );
					}
					// not setting 'stored_rate' will let the plugin fall back to the calculated_rate
				} elseif ( method_exists( $order, 'get_version' ) && version_compare( $order->get_version(), '3.7', '>=' ) && version_compare( WC_VERSION, '3.7', '>=' ) ) {
					$taxes[$item_id]['stored_rate'] = $this->format_tax_rate( $tax_rates_base[$tax['rate_id']]->rate_percent );
				} elseif ( is_numeric($item_id) && $stored_rate = wc_get_order_item_meta( absint($item_id), '_wcpdf_rate_percentage', true ) ) {
					$taxes[$item_id]['stored_rate'] = $this->format_tax_rate( $stored_rate );
				}
			}
			return $taxes;
		}

		public function get_tax_rates_base( $order ) {
			// only works in WC2.2+
			if ( version_compare( WOOCOMMERCE_VERSION, '2.2', '<' ) ) {
				return $taxes;
			}

			// get tax totals from order and preset base
			$taxes = $this->get_tax_totals( $order );
			foreach ($taxes as $rate_id => $tax) {
				$tax->base = $tax->shipping_tax_amount = 0;
			}

			$hide_zero_tax = apply_filters( 'wpo_wcpdf_tax_rate_base_hide_zero', true );

			// get subtotals from regular line items and fees
			$items = $order->get_items( array( 'fee', 'line_item', 'shipping' ) );
			foreach ($items as $item_id => $item) {
				// get tax data
				if ( $item['type'] == 'shipping' ) {
					$line_taxes = maybe_unserialize( $item['taxes'] );
					// WC3.0 stores taxes as 'total' (like line items);
					if (isset($line_taxes['total'])) {
						$line_taxes = $line_taxes['total'];
					}
				} else {
					$line_tax_data = maybe_unserialize( $item['line_tax_data'] );
					$line_taxes = $line_tax_data['total'];
				}

				foreach ( $line_taxes as $rate_id => $tax ) {
					if ( isset( $taxes[$rate_id] ) ) {
						// convert tax to float, but only if numeric
						$tax = (is_numeric($tax)) ? (float) $tax : $tax;
						if ( $tax != 0 || ( $tax === 0.0 && $hide_zero_tax === false ) ) {
							$taxes[$rate_id]->base += ($item['type'] == 'shipping') ? $item['cost'] : $item['line_total'];
							if ($item['type'] == 'shipping') {
								$taxes[$rate_id]->shipping_tax_amount += $tax;
							}
						}
					}
				}
			}

			// add calculated rate
			foreach ($taxes as $rate_id => $tax) {
				$calculated_rate = $this->calculate_tax_rate( $tax->base, $tax->amount );
				if (function_exists('wc_get_price_decimal_separator')) {
					$tax_rate = str_replace('.', wc_get_price_decimal_separator(), strval($calculated_rate) );
				}
				$taxes[$rate_id]->calculated_rate = $calculated_rate;
			}

			return $taxes;
		}

		public function get_tax_totals( $order ) {
			$taxes = array();
			$merge_by_code = apply_filters( 'wpo_wcpdf_tax_rate_base_merge_by_code', false );
			if ($merge_by_code || version_compare( WOOCOMMERCE_VERSION, '3.0', '<' ) ) {
				// get taxes from WC
				$tax_totals = $order->get_tax_totals();
				// put taxes in new array with tax_id as key
				foreach ($tax_totals as $code => $tax) {
					$tax->code = $code;
					$taxes[$tax->rate_id] = $tax;
				}
			} else {
				// DON'T MERGE BY CODE
				foreach ( $order->get_items( 'tax' ) as $key => $tax ) {
					$code = $tax->get_rate_code();
					$rate_id = $tax->get_rate_id();

					if ( ! isset( $taxes[ $rate_id ] ) ) {
						$taxes[ $rate_id ] = new stdClass();
						$taxes[ $rate_id ]->amount = 0;
					}

					$taxes[ $rate_id ]->id                = $key;
					$taxes[ $rate_id ]->base              = 0;
					$taxes[ $rate_id ]->code              = $code;
					$taxes[ $rate_id ]->rate_id           = $rate_id;
					$taxes[ $rate_id ]->is_compound       = $tax->is_compound();
					$taxes[ $rate_id ]->label             = $tax->get_label();
					$taxes[ $rate_id ]->amount           += (float) $tax->get_tax_total() + (float) $tax->get_shipping_tax_total();
					$taxes[ $rate_id ]->formatted_amount  = wc_price( wc_round_tax_total( $taxes[ $rate_id ]->amount ), array( 'currency' => $order->get_currency() ) );
					
					// WC3.7 stores rate percent
					if ( is_callable( array( $tax, 'get_rate_percent' ) ) ) {
						$taxes[ $rate_id ]->rate_percent = $tax->get_rate_percent();
					}
				}

				if ( apply_filters( 'woocommerce_order_hide_zero_taxes', true ) ) {
					$amounts = array_filter( wp_list_pluck( $taxes, 'amount' ) );
					$taxes   = array_intersect_key( $taxes, $amounts );
				}
			}
			return $taxes;
		}

		public function calculate_tax_rate( $price_ex_tax, $tax ) {
			if ( $price_ex_tax != 0) {
				$tax_rate = $this->format_tax_rate( ($tax / $price_ex_tax)*100 );
			} else {
				$tax_rate = '-';
			}
			return $tax_rate;
		}

		public function format_tax_rate( $tax_rate ) {
			$precision = apply_filters( 'wpo_wcpdf_calculate_tax_rate_precision', 1 );
			$formatted_tax_rate = round( (float) $tax_rate , $precision ).' %';
			return apply_filters( 'wpo_wcpdf_formatted_tax_rate', $formatted_tax_rate, $tax_rate );
		}

		public function save_regular_item_price( $order_id, $posted = array() ) {
			if ( $order = wc_get_order( $order_id ) ) {
				$items = $order->get_items();
				if (empty($items)) {
					return;
				}

				foreach ($items as $item_id => $item) {
					// this function will directly store the item price
					$regular_price = $this->get_regular_item_price( $item, $item_id, $order );
				}
			}
		}

		// get regular price from item - query product when not stored in item yet
		public function get_regular_item_price( $item, $item_id, $order ) {
			// first check if we alreay have stored the regular price of this item
			$regular_price = wc_get_order_item_meta( $item_id, '_wcpdf_regular_price', true );
			if ( !empty( $regular_price ) && is_array( $regular_price ) && array_key_exists( 'incl', $regular_price ) && array_key_exists( 'excl', $regular_price ) ) {
				return $regular_price;
			}

			$product = $order->get_product_from_item( $item );
			if ($product) {
				$product_regular_price = WCX_Product::get_prop( $product, 'regular_price', 'view' );
				// get different incarnations
				$regular_price = array(
					'incl'	=> WCX_Product::wc_get_price_including_tax( $product, 1, $product_regular_price ),
					'excl'	=> WCX_Product::wc_get_price_excluding_tax( $product, 1, $product_regular_price ),
				);
			} else {
				// fallback to item price
				$regular_price = array(
					'incl'	=> $order->get_line_subtotal( $item, true /* $inc_tax */, false ),
					'excl'	=> $order->get_line_subtotal( $item, false /* $inc_tax */, false ),
				);
			}

			wc_update_order_item_meta( $item_id, '_wcpdf_regular_price', $regular_price );
			return $regular_price;
		}

		public function get_discount_percentage( $order ) {
			if (method_exists($order, 'get_total_discount')) {
				// WC2.3 introduced an $ex_tax parameter
				$ex_tax = false;
				$discount = $order->get_total_discount( $ex_tax );
			} elseif (method_exists($order, 'get_discount_total')) {
				// was this ever included in a release?
				$discount = $order->get_discount_total();
			} else {
				return false;
			}

			$order_total = $order->get_total();

			// shipping and fees are not discounted
			$shipping_total = $order->get_total_shipping() + $order->get_shipping_tax();
			$fee_total = 0;
			if (method_exists($order, 'get_fees')) { // old versions of WC don't support fees
				foreach ( $order->get_fees() as $fees ) {
					$fee_total += $fees['line_total'] + $fees['line_tax'];
				}
			}

			$percentage = ( $discount / ( $order_total + $discount - $shipping_total - $fee_total) ) * 100;

			return round($percentage);
		}

		public function get_order_weight( $order, $document = null, $add_unit = true ) {
			$items = $order->get_items();
			$weight = 0;
			if( sizeof( $items ) > 0 ) {
				foreach( $items as $item_id => $item ) {
					$product = $order->get_product_from_item( $item );

					if ( $this->subtract_refunded_qty( $document ) && $refunded_qty = $order->get_qty_refunded_for_item( $item_id ) ) {
						$qty = (int) $item['qty'] + $refunded_qty;
					} else {
						$qty = (int) $item['qty'];
					}

					if ( !empty($product) && is_numeric($product->get_weight()) ) {
						$weight += $product->get_weight() * $qty;
					}
				}
			}
			if ( $add_unit == true ) {
				$weight .= get_option('woocommerce_weight_unit');
			}
			return apply_filters( 'wpo_wcpdf_templates_order_weight', $weight, $order, $document );
		}

		public function get_order_total_qty( $order, $document = null ) {
			$items = $order->get_items();
			$total_qty = 0;
			if( sizeof( $items ) > 0 ) {
				foreach( $items as $item_id => $item ) {
					// only count visible items (product bundles compatibiity)
					if ( ! apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {
						continue;
					}
					$total_qty += $item['qty'];

					if ( $this->subtract_refunded_qty( $document ) && $refunded_qty = $order->get_qty_refunded_for_item( $item_id ) ) {
						$total_qty += $refunded_qty;
					}
				}
			}

			return apply_filters( 'wpo_wcpdf_templates_order_qty', $total_qty, $order, $document );
		}

		public function subtract_refunded_qty( $document ) {
			$subtract_refunded_qty = false;
			if (!empty($document) && $document->get_type() == 'packing-slip') {
				$packing_slip_settings = get_option( 'wpo_wcpdf_documents_settings_packing-slip' );
				if ( isset($packing_slip_settings['subtract_refunded_qty'] ) ) {
					$subtract_refunded_qty = true;
				}
			}
			return $subtract_refunded_qty;
		}

		// hide regular price item eta
		public function hide_regular_price_itemmeta( $hidden_keys ) {
			$hidden_keys[] = '_wcpdf_regular_price';
			return $hidden_keys;
		}

		public function array_keys_prefix( $array, $prefix, $add_or_remove = 'add' ) {
			if (empty($array) || !is_array($array) ) {
				return $array;
			}

			foreach ($array as $key => $value) {
				if ( $add_or_remove == 'add' ) {
					$array[$prefix.$key] = $value;
					unset($array[$key]);
				} else { // remove
					$new_key = str_replace($prefix, '', $key);
					$array[$new_key] = $value;
					unset($array[$key]);
				}
			}

			return $array;

		}

		public function get_local_pickup_plus_pickup_details( $order ) {
			if ( function_exists('wc_local_pickup_plus') ) {
				ob_start();

				$local_pickup   = wc_local_pickup_plus();
				$orders_handler = $local_pickup->get_orders_instance();

				if ( $orders_handler && ( $pickup_data = $orders_handler->get_order_pickup_data( $order ) ) ) {
					$shipping_method = $local_pickup->get_shipping_method_instance();
					$package_number = 1;
					$packages_count = count( $pickup_data );
					?>

					<h3><?php echo esc_html( $shipping_method->get_method_title() ); ?></h3>

					<?php foreach ( $pickup_data as $pickup_meta ) : ?>

						<div>
							<?php if ( $packages_count > 1 ) : ?>
								<h5><?php echo sprintf( '%1$s #%2$s', esc_html( $shipping_method->get_method_title() ), $package_number ); ?></h5>
							<?php endif; ?>
							<ul>
								<?php foreach ( $pickup_meta as $label => $value ) : ?>
									<li>
										<strong><?php echo esc_html( $label ); ?>:</strong> <?php echo wp_kses_post( $value ); ?>
									</li>
								<?php endforeach; ?>
							</ul>
							<?php $package_number++; ?>
						</div>

					<?php endforeach; ?>
					<?php
					$order_pickup_data = ob_get_clean();
					return $order_pickup_data;				
				}
			}
		}

		/**
		 * Save tax rate percentage in tax meta every time totals are calculated
		 * @param  bool $and_taxes Calc taxes if true.
		 * @param  WC_Order $order Order object.
		 * @return void
		 */
		public function save_tax_rate_percentage_recalculate( $and_taxes, $order ) {
			// it seems $and taxes is mostly false, meaning taxes are calculated separately,
			// but we still update just in case anything changed
			if ( !empty( $order ) && method_exists( $order, 'get_version' ) && version_compare( $order->get_version(), '3.7', '>=' ) ) {
				return; // WC3.7 already stores the rate in the tax lines
			} else {
				$this->save_tax_rate_percentage( $order );
			}
		}

		public function save_tax_rate_percentage_frontend( $order_id, $posted ) {
			if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '3.7', '<' ) ) {
				$order = wc_get_order( $order_id );
				if ( !empty( $order ) ) {
					$this->save_tax_rate_percentage( $order );
				}
			}
		}

		public function save_tax_rate_percentage( $order ) {
			foreach ( $order->get_taxes() as $item_id => $tax_item ) {
				if ( is_a( $tax_item, '\WC_Order_Item_Tax' ) && is_callable( array( $tax_item, 'get_rate_id' ) ) ) {
					// get tax rate id from item
					$tax_rate_id = $tax_item->get_rate_id();
					// read tax rate data from db
					if ( class_exists('\WC_TAX') && is_callable( array( '\WC_TAX', '_get_tax_rate' ) ) ) {
						$tax_rate = WC_Tax::_get_tax_rate( $tax_rate_id, OBJECT );
						if ( $tax_rate && !empty( $tax_rate->tax_rate ) ) {
							// store percentage in tax item meta
							wc_update_order_item_meta( $item_id, '_wcpdf_rate_percentage', $tax_rate->tax_rate );
						}
					}
				}
			}
		}

	} // end class
} // end class_exists

return new WooCommerce_PDF_IPS_Templates_Main();
