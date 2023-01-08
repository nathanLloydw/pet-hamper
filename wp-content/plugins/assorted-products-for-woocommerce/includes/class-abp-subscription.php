<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}
if ( !class_exists('ABP_Assorted_Products_Subscription') ) {
	class ABP_Assorted_Products_Subscription {
		public function __construct() {
			add_filter( 'woocommerce_is_subscription', array(__CLASS__, 'is_subscription'), 10, 3 );
			add_action( 'woocommerce_checkout_update_order_meta', array(__CLASS__, 'new_assorted_subscription_order'), 10, 2 );
			add_action( 'woocommerce_checkout_subscription_created', array(__CLASS__, 'subscription_created'), 10, 3 );
			add_action( 'wcs_can_item_be_removed', array(__CLASS__, 'can_item_be_removed'), 10, 3 );
			if ( 'yes' == get_option('abp_edit_box_subscription_myaccount') ) {
				add_filter( 'wcs_view_subscription_actions', array(__CLASS__, 'subscription_actions'), 10, 2 );
				add_filter( 'template_include', array(__CLASS__, 'load_edit_subscription_template'), 10, 1 );
				add_filter( 'wc_abp_add_to_cart_button_name', array(__CLASS__, 'add_to_cart_button_name'), 10, 2 );
				add_action( 'wp_loaded', array(__CLASS__, 'submit_form_data') );
				add_filter( 'wc_abp_assorted_edit_subscription_product_id', array(__CLASS__, 'edit_subscription_product_id'), 10, 1 );
			}
			add_action( 'abp_assorted_product_after_price', array(__CLASS__, 'assorted_product_after_price'), 10, 1 );
			add_action( 'woocommerce_after_shop_loop_item_title', array(__CLASS__, 'abp_product_after_price') );
			add_action( 'abp_after_assorted_product_settings', array(__CLASS__, 'abp_after_assorted_product_settings') );
			add_action( 'woocommerce_process_product_meta', array(__CLASS__, 'abp_save_product_options_field' ), 999, 1 );
			add_action( 'admin_menu', array(__CLASS__, 'abp_add_menu_page'));
			add_action( 'wp_ajax_abp_assorted_edit_subscriptions', array(__CLASS__, 'abp_assorted_edit_subscriptions'));
		}
		public static function abp_add_menu_page() {
			add_submenu_page( 'abp-assorted-products', esc_html__('Edit Subscriptions', 'wc-abp'), esc_html__('Edit Subscriptions', 'wc-abp'), 'manage_options', 'abp-assorted-suscriptions', array(__CLASS__, 'abp_subscriptions_page_callback'));
		}
		public static function abp_assorted_edit_subscriptions() {
			check_ajax_referer( 'assorted_bundle', 'security' );
			if ( empty($_POST['product_id']) || empty($_POST['old_item']) || empty($_POST['new_item']) ) {
				wp_send_json_error( esc_html__('Any of the field is empty', 'wc-abp') );
			}
			$product_id = wc_clean($_POST['product_id']);
			$old_item = wc_clean($_POST['old_item']);
			$new_item = wc_clean($_POST['new_item']);
			$addon = $new_item;
			$updated_ids = array();
			$subscriptions = wcs_get_subscriptions( array( 'product_id' => $product_id, 'subscription_status' => 'wc-active', 'subscriptions_per_page' => -1) );
			$product = wc_get_product($product_id);
			if ( !empty($subscriptions) ) {
				foreach ( $subscriptions as $subscription ) {
					
					$flag = false;
					$qty = 0;
					$new_item_qty = 0;
					$new_item_key = '';
					$main_product_key = '';
					$order_id = $subscription->get_parent_id();
					$order = wc_get_order( $order_id );
					//modify subscription price
					$price = 0;
					$old_addons_price = 0;
					$pricing = get_post_meta($product_id, 'abp_pricing_type', true);
					// Unprotected data in an accessible array
					$line_items = $subscription->get_items();
					foreach ( $line_items as $key => $item ) {
						if ( $item['product_id'] == $product_id ) {
							$product_qty = $item['quantity'];
							$main_product_key = $key;
						} else {
							if ( $item['product_id'] == $new_item ) {
								$new_item_qty = $item['quantity'];
								$new_item_key = $key;
							}
							if ( $item['product_id'] == $old_item ) {
								$qty = $item['quantity'];
								wc_delete_order_item($key);
								$flag = true;
							} else {
								if ( 'regular' != $pricing ) {
									$item_id = $item['product_id'];
									$_product = wc_get_product($item_id);
									if ( is_object($_product) && $_product->is_purchasable() ) {
										$old_addons_price += $_product->get_price() * $item['quantity'];
									}
								}
							}
						}
					}
					if ( !$flag ) {
						continue;
					}
					if ( !empty($new_item_key) && !empty($new_item_qty) ) {
						wc_delete_order_item($new_item_key);
						$qty += $new_item_qty;
					}
					$price = $old_addons_price;
					if ( 'per_product_and_items' == $pricing || 'regular' == $pricing ) {
						$price += $product->get_price();
					}
					$addon_prod = wc_get_product($addon);
					if ( is_object($addon_prod) && $addon_prod->is_purchasable() && $addon_prod->is_in_stock() ) {
						$addon_prod_id = $addon_prod->get_id();
						$addon_price = ( 'regular' == $pricing ) ? 0 : $addon_prod->get_price();
						$subscription->add_product(
							$addon_prod,
							$qty,
							array(
									'variation' => $addon_prod_id,
									'totals'    => array(
										'subtotal'     => 0,
										'subtotal_tax' => '',
										'total'        => 0,
										'tax'          => '',
										'tax_data'     => '',
									),
								)
							);
						if ( 'regular' != $pricing ) {
							$price += $addon_price * $qty;
						}
						$price = $price * $product_qty;
						if ( !empty($main_product_key) ) {
							wc_update_order_item_meta($main_product_key, '_line_total', $price);
							wc_update_order_item_meta($main_product_key, '_line_subtotal', $price);
							wc_update_order_item_meta($main_product_key, '_qty', $product_qty);
						}
						$updated_ids[] = $subscription->get_id();
						$subscription1 = wcs_get_subscription( $subscription->get_id() );
						$subscription1->update_taxes();
						$subscription1->calculate_totals();
					}
				}
			}
			if ( !empty($updated_ids) ) {
				$message = sprintf('%s %s', esc_html__('Updated for the subscriptions:', 'wc-abp'), implode(',', $updated_ids));
			} else {
				$message = esc_html__('None of the subscriptions found to update the items. You can try with different items.', 'wc-abp');
			}
			wp_send_json_success($message);
		}
		public static function abp_subscriptions_page_callback() {
			?>
			<div class="wrap">
				<h1><?php esc_html_e('Assorted Products - Edit Subscriptions Settings', 'wc-abp'); ?></h1>
				<p><?php esc_html_e( 'This page allows you to replace the items for the subscriptions created by customers. These settings only work if the WooCommerce Subscriptions is installed & is active.', 'wc-abp' ); ?></p>
				<form method="post" action="">
					<table class="form-table">
						<tr>
							<th>
								<label for="abp_assorted_product_subsc"><?php esc_html_e('Choose Assorted Subscription Product', 'wc-abp'); ?></label>
							</th>
							<td>
								<select name="abp_assorted_product_subsc" class="wc-product-search regular-text" id="abp_assorted_product_subsc"  data-action="woocommerce_json_search_products_and_variations" data-exclude="only_assorted_products">
								</select>
								<p><i><?php esc_html_e( 'Choose an assorted subscription product you want to replace items for.', 'wc-abp' ); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_item_old"><?php esc_html_e('Assorted Product Item Old', 'wc-abp'); ?></label>
							</th>
							<td>
								<select name="abp_assorted_item_old" class="wc-product-search regular-text" id="abp_assorted_item_old" data-action="woocommerce_json_search_products_and_variations" data-exclude="assorted_product">
								</select>
								<p><i><?php esc_html_e( 'Choose item you want to remove from assorted subscriptions.', 'wc-abp' ); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_item_new"><?php esc_html_e('Assorted Product Item New', 'wc-abp'); ?></label>
							</th>
							<td>
								<select name="abp_assorted_item_new" class="wc-product-search regular-text" id="abp_assorted_item_new" data-action="woocommerce_json_search_products_and_variations" data-exclude="assorted_product">
								</select>
								<p><i><?php esc_html_e( 'Choose item you want to add for as the replacement of old item assorted subscriptions.', 'wc-abp' ); ?></i></p>
							</td>
						</tr>
						<tr>
							<td>
								<button type="button" class="abp_submit_edit_subscription button button-primary"><?php esc_html_e('Apply Settings', 'wc-abp'); ?></button>
								<span class="is-active spinner abp_spinner"></span>
								<p class="abp_submit_subscription_msg"><?php esc_html_e( 'All fields are required.', 'wc-abp' ); ?></p>
								<p class="abp_update_subscription_msg"></p>
							</td>
						</tr>
					</table>
				</form>
			</div>
			<?php
		}
		public static function is_subscription( $is_subscription, $product_id, $product ) {
			if ( 'yes' == get_post_meta($product_id, 'abp_assorted_subscription_enable', true) ) {
				$is_subscription = true;
			}
			return $is_subscription;
		}
		public static function assorted_product_after_price( $product_id ) {
			$product = wc_get_product($product_id);
			if ( $product->is_type('assorted_product') && 'yes' == get_post_meta($product_id, 'abp_assorted_subscription_enable', true) ) {
				$interval = array( '1' => ' every', '2' => 'every 2nd', '3' => 'every 3rd', '4' => 'every 4th', '5' => 'every 5th', '6' => 'every 6th' );
				$interv = get_post_meta($product_id, '_subscription_period_interval', true);
				$period = get_post_meta($product_id, '_subscription_period', true);
				echo '<span class="box_subscription_details">' . ( isset($interval[$interv]) ? esc_html($interval[$interv]) . ' ' : ' / ' ) . esc_html($period) . '</span>';
			}
		}
		public static function abp_product_after_price() {
			$product_id = get_the_ID();
			$product = wc_get_product( $product_id );
			if ( $product->is_type('assorted_product') && 'yes' == get_post_meta($product_id, 'abp_assorted_subscription_enable', true) ) {
				$interval = array( '1' => ' every', '2' => 'every 2nd', '3' => 'every 3rd', '4' => 'every 4th', '5' => 'every 5th', '6' => 'every 6th' );
				$interv = get_post_meta($product_id, '_subscription_period_interval', true);
				$period = get_post_meta($product_id, '_subscription_period', true);
				echo '<span class="box_subscription_details">' . ( isset($interval[$interv]) ? esc_html($interval[$interv]) . ' ' : ' / ' ) . esc_html($period) . '</span>';
			}
		}
		public static function new_assorted_subscription_order( $order_id, $posted ) {
			global $woocommerce;
			$assorted_products = array();
			$assorted_product_items = array();
			$items = WC()->cart->get_cart();
			foreach ( $items as $item => $cart_item ) { 
				if ( isset( $cart_item['abp-assorted-add-to-cart'] ) && '' != ( $cart_item['abp-assorted-add-to-cart'] ) ) {
					$abp_assorted_product_items = explode( ',', $cart_item['abp-assorted-add-to-cart'] );
					array_push($assorted_products, $cart_item['product_id']);
					$id = $cart_item['product_id'];
					$assorted_product_items[$id] = $abp_assorted_product_items;
				}
			}
			if ( !empty($box_products) ) {
				update_post_meta( $order_id, 'abp_assorted_product_item', $assorted_products);
				update_post_meta( $order_id, 'abp_assorted_product_item_addons', $assorted_product_items);
			}
			return $order_id;
		}
		public static function subscription_created( $subscription, $order, $cart ) {
			global $woocommerce;
			$assorted_ids = array();
			$line_items = $subscription->get_items();
			foreach ( $line_items as $key => $sub_item ) {
				if ( $sub_item['product_id'] ) {
					$assorted = wc_get_product($sub_item['product_id']);
					if ( $assorted->is_type('assorted_product') ) {
						array_push( $assorted_ids, $assorted->get_id() );
					}
				}
			}
			$items = WC()->cart->get_cart();
			foreach ( $items as $item => $cart_item ) { 
				if ( !empty( $cart_item['abp_assorted_product_parent_id'] ) && in_array($cart_item['abp_assorted_product_parent_id'], $assorted_ids ) && 'yes' == get_post_meta($cart_item['abp_assorted_product_parent_id'], 'abp_assorted_subscription_enable', true) ) {
					$subscription->add_product(
						$cart_item['data'],
						$cart_item['quantity'],
						array(
							'variation' => $cart_item['variation'],
							'totals'    => array(
								'subtotal'     => $cart_item['line_subtotal'],
								'subtotal_tax' => $cart_item['line_subtotal_tax'],
								'total'        => $cart_item['line_total'],
								'tax'          => $cart_item['line_tax'],
								'tax_data'     => $cart_item['line_tax_data'],
							),
						)
					);
				}
			}
			return $subscription;
		}
		public static function can_item_be_removed( $allow_remove, $item, $subscription ) {
			$product = wc_get_product($item['product_id']);
			if ( count( $subscription->get_items() ) > 1 && $product->is_type('assorted_product') ) {
				$allow_remove = false;
			}
			if ( $allow_remove ) {
				foreach ( $subscription->get_items() as $item_id => $_item ) {
					$_product =  $_item->get_product();
					if ( $_product->is_type('assorted_product') ) {
						$allow_remove = false;
						break;
					}
				}
			}
			return $allow_remove;
		}
		public static function subscription_actions( $actions, $subscription ) {
			if ( user_can(get_current_user_id(), 'edit_shop_subscription_status', $subscription->get_id() )  ) {
				$items = $subscription->get_items();
				foreach ( $items as $items_key => $item ) {  
					$box = $item->get_product();
					if ( $box->is_type('assorted_product') ) {
						$actions['edit-assorted-subscription-' . $subscription->get_id()] = array(
						'url'	=>	home_url() . '/edit-assorted-subscription/' . $subscription->get_id() . '/' . $box->get_id(),
						'name'	=>	esc_html_x( 'Edit Subscription', 'an action on a subscription', 'wc-abp' ),
						);
						break;
					}
				}
			}
			return $actions;
		}
		public static function load_edit_subscription_template( $template ) {
			$url_path = trim( parse_url( add_query_arg(array()), PHP_URL_PATH), '/' );
			if ( strpos( $url_path, 'edit-assorted-subscription/' ) !== false ) {
				global $wp_query;
				$wp_query->is_404=false;
				status_header(200);
				$template = WC_ABP_DIR . 'templates/edit-assorted-subscription.php';
			}
			return $template;
		}
		public static function add_to_cart_button_name( $name, $product_id ) {
			$url_path = trim( parse_url( add_query_arg(array()), PHP_URL_PATH ), '/' );
			if ( strpos($url_path, 'edit-assorted-subscription/') !== false ) {
				$name = 'edit-assorted-subscription';
			}
			return $name;
		}
		public static function submit_form_data() {
			if ( !empty($_POST['edit-assorted-subscription']) && is_user_logged_in() && isset($_POST['abp_assorted_edit_subscription_nonce']) && wp_verify_nonce( wc_clean($_POST['abp_assorted_edit_subscription_nonce']), 'abp_assorted_edit_subscription_nonce' ) && !empty($_POST['abp-assorted-add-to-cart']) ) {
				$subscription = self::get_subscription();
				$product = self::get_product();
				if ( $subscription && $product && $_POST['edit-assorted-subscription'] == $product->get_id() ) {
					$product_id = $product->get_id();
					// remove pre line items
					$line_items = $subscription->get_items();
					foreach ( $line_items as $key => $item ) {
						if ( $item['product_id'] != $product->get_id() ) {
							wc_delete_order_item($key);
						}
					} // removed all product items
					//modify subscription price
					$product_qty = isset($_POST['quantity']) ? absint($_POST['quantity']) : 1;
					$price = 0;
					// extra fee
					$extra_fee = 0;
					$extra_fees = get_post_meta( $product_id, 'abp_per_item_extra_fees', true );

					$pricing = get_post_meta($product->get_id(), 'abp_pricing_type', true);
					if ( 'per_product_and_items' == $pricing || 'regular' == $pricing ) {
						$price = $product->get_price();
					}
					$addons = explode( ',', wc_clean($_POST['abp-assorted-add-to-cart']) );
					$dups = array_count_values($addons );
					foreach ( $dups as $addon => $addon_qty ) {
						$qty_key = 'qty_' . esc_attr($addon);
						$qty = isset($_POST[$qty_key]) ? wc_clean($_POST[$qty_key]) : 0;
						$addon_prod = wc_get_product($addon);
						if ( is_object($addon_prod) && $addon_prod->is_purchasable() && $addon_prod->is_in_stock() ) {
							$addon_prod_id = $addon_prod->get_id();
							$addon_price = ( 'regular' == $pricing ) ? 0 : $addon_prod->get_price();
							$subscription->add_product(
								$addon_prod,
								$qty,
								array(
										'variation' => $addon_prod_id,
										'totals'    => array(
											'subtotal'     => 0,
											'subtotal_tax' => '',
											'total'        => 0,
											'tax'          => '',
											'tax_data'     => '',
										),
									)
								);
							if ( 'regular' != $pricing ) {
								$price += $addon_price * $qty;
							}
							// extra fee
							if ( isset($extra_fees[$addon_prod_id]) ) {
								$extra_fee += $qty * $extra_fees[$addon_prod_id]['fee'];
							}
						}
					}
					$price = $price * $product_qty;
					// extra fee
					if ( 'yes' == get_post_meta( $product_id, 'abp_per_item_extra_fee_enable', true ) ) {
						$price = $price + $extra_fee;
					}
					// update main assorted line item
					foreach ( $line_items as $key => $item ) {
						if ( $item['product_id'] == $product->get_id() ) {
							wc_update_order_item_meta($key, '_line_total', $price);
							wc_update_order_item_meta($key, '_line_subtotal', $price);
							wc_update_order_item_meta($key, '_qty', $product_qty);
						}
					}
					//modify subscription price
					update_post_meta( $subscription->get_id(), '_order_total', floatval($price) );
					$period = get_post_meta( $product->get_id(), '_subscription_period', true );
					update_post_meta( $subscription->get_id(), '_billing_period', $period );
					$interval = get_post_meta($product->get_id(), '_subscription_period_interval', true );
					update_post_meta( $subscription->get_id(), '_billing_interval', $interval );
					$sync = get_post_meta($product->get_id(), '_subscription_payment_sync_date', true );
					update_post_meta( $subscription->get_id(), '_subscription_payment_sync_date', $sync );

					$message = get_option('abp_edit_box_subscription_message');
					$message = !empty($message) ? esc_html__($message, 'wc-abp') : esc_html__('Your subscription has been updated.', 'wc-abp');
					wc_add_notice( esc_html( $message ), 'notice' );
					if ( wp_safe_redirect( esc_url($subscription->get_view_order_url()) ) ) {
						exit();
					}
				}
			}
		}
		private static function get_segments() {
			$current = trim( parse_url(add_query_arg(array()), PHP_URL_PATH), '/' );
			$segments = explode('edit-assorted-subscription/', $current);
			$parts = isset($segments[1]) ? explode('/', $segments[1]) : '';
			return ( is_array($parts) && count($parts)>0 ) ? $parts : false;
		}
		public static function get_subscription() {
			$parts = self::get_segments();
			if ( isset($parts[0]) && is_numeric($parts[0]) ) {
				$subscription = wcs_get_subscription( $parts[0] );
				if ( $subscription instanceof WC_Subscription) {
					return $subscription;
				}
			}
			return false;
		}
		public static function get_product() {
			$subscription = self::get_subscription();
			$parts = self::get_segments();
			if ( $subscription && isset($parts[1]) && is_numeric($parts[1]) ) {
				$product = wc_get_product( $parts[1] );
				if ( is_object($product) ) {
					$line_items = $subscription->get_items();
					foreach ( $line_items as $item ) {
						if ( $item['product_id'] == $product->get_id() ) {
							return $product;
							break;
						}
					}
				}
			}
			return false;
		}
		public static function edit_subscription_product_id( $product_id ) {
			$url_path = trim(parse_url(add_query_arg(array()), PHP_URL_PATH), '/');
			$product = self::get_product();
			if ( strpos($url_path, 'edit-assorted-subscription/') !== false && $product ) {
				$product_id = $product->get_id();
			}
			return $product_id;
		}
		public static function abp_after_assorted_product_settings() {
			global $post;
			$post_id = $post->ID;
			$abp = get_post_meta($post_id, 'abp_assorted_subscription_enable', true);
			woocommerce_wp_checkbox(
				array(
					'id'            => 'abp_assorted_subscription_enable',
					'label'         => esc_html__('Enable Subscription?', 'wc-abp' ),
					'description'   => esc_html__( 'Enable subscription for this product', 'wc-abp' ),
					'value'		   => $abp
				)
			);
		}
		public static function abp_save_product_options_field( $post_id ) {
			//if doing an auto save
			if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
				return;
			}
			// if our nonce isn't there, or we can't verify it
			if ( !isset( $_POST['abp_assorted_product_nonce'] ) || !wp_verify_nonce( sanitize_text_field($_POST['abp_assorted_product_nonce']), 'abp_assorted_product_nonce' ) ) {
				return;
			}
			// if current user can't edit this post
			if ( !current_user_can( 'edit_post', $post_id ) ) {
				return;
			}
			if ( isset($_POST['product-type']) && 'assorted_product' == wc_clean($_POST['product-type']) ) {
				if ( isset($_POST['abp_assorted_subscription_enable']) ) {
					wp_set_object_terms($post_id, 'subscription', 'assorted_product' );
					if ( isset($_POST['_subscription_price']) && !empty($_POST['_subscription_price']) ) {
						update_post_meta($post_id, '_subscription_price', wc_clean($_POST['_subscription_price']));
					} else {
						$price = isset($_POST['_regular_price']) ? wc_clean($_POST['_regular_price']) : '';
						update_post_meta($post_id, '_subscription_price', $price);
					}
					if ( isset($_POST['_subscription_sign_up_fee']) ) {
						update_post_meta($post_id, '_subscription_sign_up_fee', wc_clean($_POST['_subscription_sign_up_fee']));
					} else {
						update_post_meta($post_id, '_subscription_sign_up_fee', '');
					}
					if ( isset($_POST['_subscription_period']) ) {
						update_post_meta($post_id, '_subscription_period', wc_clean($_POST['_subscription_period']));
					} else {
						update_post_meta($post_id, '_subscription_period', '');
					}
					if ( isset($_POST['_subscription_period_interval']) ) {
						update_post_meta($post_id, '_subscription_period_interval', wc_clean($_POST['_subscription_period_interval']));
					} else {
						update_post_meta($post_id, '_subscription_period_interval', '');
					}
					if ( isset($_POST['_subscription_length']) ) {
						update_post_meta($post_id, '_subscription_length', wc_clean($_POST['_subscription_length']));
					} else {
						update_post_meta($post_id, '_subscription_length', '');
					}
					if ( isset($_POST['_subscription_trial_period']) ) {
						update_post_meta($post_id, '_subscription_trial_period', wc_clean($_POST['_subscription_trial_period']));
					} else {
						update_post_meta($post_id, '_subscription_trial_period', '');
					}
					if ( isset($_POST['_subscription_trial_length']) ) {
						update_post_meta($post_id, '_subscription_trial_length', wc_clean($_POST['_subscription_trial_length']));
					} else {
						update_post_meta($post_id, '_subscription_trial_length', '');
					}
					if ( isset($_POST['_subscription_payment_sync_date']) ) {
						update_post_meta($post_id, '_subscription_payment_sync_date', wc_clean($_POST['_subscription_payment_sync_date']));
					} else {
						update_post_meta($post_id, '_subscription_payment_sync_date', '');
					}
					update_post_meta($post_id, 'abp_assorted_subscription_enable', 'yes');
				} else {
					wp_remove_object_terms( $post_id, 'subscription', 'assorted_product' );
					delete_post_meta($post_id, '_subscription_price');
					delete_post_meta($post_id, '_subscription_sign_up_fee');
					delete_post_meta($post_id, '_subscription_period');
					delete_post_meta($post_id, '_subscription_period_interval');
					delete_post_meta($post_id, '_subscription_length');
					delete_post_meta($post_id, '_subscription_trial_period');
					delete_post_meta($post_id, '_subscription_trial_length');
					delete_post_meta($post_id, '_subscription_payment_sync_date');
					update_post_meta($post_id, 'abp_assorted_subscription_enable', 'no');
				}
			}
		}
	}
	new ABP_Assorted_Products_Subscription();
}
