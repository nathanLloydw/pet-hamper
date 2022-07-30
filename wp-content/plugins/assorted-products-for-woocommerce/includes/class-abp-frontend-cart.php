<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}
if ( !class_exists('ABP_Assorted_Product_Frontend_Cart') ) {
	class ABP_Assorted_Product_Frontend_Cart {
		public function __construct() {
			add_filter('woocommerce_add_cart_item_data', array( $this, 'abp_assorted_product_add_cart_item_data' ), 10, 2 );
			add_action('woocommerce_add_to_cart', array( $this, 'abp_assorted_product_add_to_cart' ), 10, 6 );
			add_filter('woocommerce_add_cart_item', array( $this, 'abp_assorted_product_add_cart_item' ), 10, 1 );
			add_filter('woocommerce_cart_tax_totals', array($this, 'abp_cart_tax_totals_callback'), 10, 2);
			add_filter('woocommerce_cart_item_name', array( $this, 'abp_assorted_product_cart_item_name' ), 10, 2 );
			add_filter('woocommerce_cart_item_price', array( $this, 'abp_assorted_product_cart_item_price' ), 10, 3 );
			add_filter('woocommerce_cart_item_quantity', array( $this, 'abp_assorted_product_cart_item_quantity' ), 1, 2 );
			add_filter('woocommerce_cart_item_subtotal', array( $this, 'abp_assorted_product_cart_item_subtotal' ), 10, 3 );
			add_filter('woocommerce_cart_item_remove_link', array( $this, 'abp_assorted_product_cart_item_remove_link' ), 10, 3 );
			add_filter('woocommerce_cart_contents_count', array( $this, 'abp_assorted_product_cart_contents_count' ) );
			add_action('woocommerce_after_cart_item_quantity_update', array($this,'abp_assorted_product_update_cart_item_quantity'), 1, 2 );
			add_action('woocommerce_before_cart_item_quantity_zero', array($this,'abp_assorted_product_update_cart_item_quantity'), 1 );
			add_action('woocommerce_cart_item_removed', array( $this, 'abp_assorted_product_cart_item_removed' ), 10, 2 );
			// Checkout item
			add_filter('woocommerce_checkout_item_subtotal', array( $this, 'abp_assorted_product_cart_item_subtotal' ), 10, 3 );
			// Calculate totals
			add_action('woocommerce_before_calculate_totals', array( $this, 'abp_assorted_product_before_calculate_totals' ), 10, 1 );
			if ( 'yes' == get_option('abp_assorted_show_discount_fee') ) {
				add_action( 'woocommerce_cart_calculate_fees', array( $this,  'abp_fee_based_on_cart_total'), 10, 1 );
			}
			// Shipping
			add_filter('woocommerce_cart_shipping_packages', array( $this, 'abp_assorted_product_cart_shipping_packages' ) );
			add_filter('woocommerce_get_item_data', array($this,'abp_get_item_data' ), 25, 2 );
			add_action('woocommerce_checkout_create_order_line_item', array($this,'abp_add_order_item_meta'), 10, 4 );
		}
		public function abp_get_item_data( $cart_data, $cart_item ) {
			if ( !empty($cart_item['abp_assorted_message_field']) ) {
				$product_id=absint($cart_item['product_id']);
				$label=get_post_meta($product_id, 'abp_assorted_message_field_label', true );
				$label=!empty($label) ? $label : esc_html__( 'Message', 'wc-abp');
				$cart_data[] = array(
					'name'    => esc_html($label),
					'display' => $cart_item['abp_assorted_message_field']
				);
			}
			return $cart_data;
		}
		public function abp_add_order_item_meta( $item, $cart_item_key, $values, $order ) {
			if ( isset($values['abp_assorted_message_field']) ) {
				$product_id=$values['product_id'];
				$label=get_post_meta($product_id, 'abp_assorted_message_field_label', true );
				$label=!empty($label) ? $label : esc_html__('Message', 'wc-abp');
				$item->update_meta_data( $label, $values['abp_assorted_message_field']);
			}
		}
		public function abp_cart_tax_totals_callback( $tax_total, $obj ) {
			$array=array();
			return $tax_total;
		}
		public function abp_assorted_product_cart_item_name( $name, $item ) {
			if ( isset( $item['abp_assorted_product_parent_id'] ) && ! empty( $item['abp_assorted_product_parent_id'] ) ) {
				if ( 'yes' == get_option( 'abp_assorted_hide_items_parent_name') ) {
					$title = strip_tags($name);
				} else {
					$title = ( strpos( $name, '</a>' ) !== false ) ? '<a href="' . esc_url( get_permalink( $item['abp_assorted_product_parent_id'] ) ) . '">' . esc_attr(get_the_title( $item['abp_assorted_product_parent_id'] )) . '</a> &rarr; ' . strip_tags($name)  : get_the_title( $item['abp_assorted_product_parent_id'] ) . ' &rarr; ' . strip_tags( $name );
				}
				return $title;
			} else {
				return $name;
			}
		}
		public function abp_assorted_product_add_cart_item_data( $cart_item_data, $product_id ) {
			if ( !isset( $_POST['abp_assorted_mini_nonce'] ) || !wp_verify_nonce( sanitize_text_field( $_POST['abp_assorted_mini_nonce'] ), 'abp_assorted_mini_nonce' ) ) {
				return $cart_item_data;
			}
			$terms        = get_the_terms( $product_id, 'product_type' );
			$product_type = ! empty( $terms ) && isset( current( $terms )->name ) ? sanitize_title( current( $terms )->name ) : 'simple';
			if ( 'assorted_product' == $product_type ) {
				$cart_item_data['abp-assorted-add-to-cart'] = isset($_POST['abp-assorted-add-to-cart']) ? wc_clean($_POST['abp-assorted-add-to-cart']) : '';
				$items = explode( ',', $cart_item_data['abp-assorted-add-to-cart']);
				if ( is_array( $items ) && ( count( $items ) > 0 ) ) {
					$quantities=array();
					foreach ( $items as $item) {
						$qty= isset($_POST['qty_' . $item]) ? absint($_POST['qty_' . $item]) : 1;
						$quantities[$item]=$qty;
					}
					$cart_item_data['abp-assorted-item-quantity']=$quantities;
				}
			}
			if ( !empty($_POST['abp_assorted_message_field']) && get_post_meta($product_id, 'abp_enable_assorted_gift_message', true )=='yes' ) {
				$message=wc_clean($_POST['abp_assorted_message_field']);
				$cart_item_data['abp_assorted_message_field'] =$message;
				WC()->session->set('abp_assorted_message_field', $message);
			}
			return $cart_item_data;
		}
		//remove x link from cart for bix items
		public function abp_assorted_product_cart_item_remove_link( $link, $cart_item_key ) {
			if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['abp_assorted_product_parent_id'] ) ) {
				return '';
			}
			return $link;
		}
		public function abp_assorted_product_cart_item_quantity( $quantity, $cart_item_key ) {
			if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['abp_assorted_product_parent_id'] ) ) {
				return WC()->cart->cart_contents[ $cart_item_key ]['quantity'];
			}
			return $quantity;
		}
		public function abp_assorted_product_cart_item_price( $price, $cart_item, $cart_item_key ) {
			if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['abp_assorted_product_parent_id'] ) ) {
				return '';
			}
			return $price;
		}
		public function abp_assorted_product_cart_item_subtotal( $subtotal, $cart_item, $cart_item_key ) {
			if ( isset( WC()->cart->cart_contents[ $cart_item_key ]['abp_assorted_product_parent_id'] ) ) {
				return '';
			}
			return $subtotal;
		}
		public function abp_assorted_product_cart_contents_count( $count ) {
			$cart_contents = WC()->cart->cart_contents;
			$bundled_items = 0;
			foreach ( $cart_contents as $cart_item_key => $cart_item ) {
				if ( ! empty( $cart_item['abp_assorted_product_parent_id'] ) ) {
					$bundled_items += $cart_item['quantity'];
				}
			}
			return intval( $count - $bundled_items );
		}
		public function abp_assorted_product_update_cart_item_quantity( $cart_item_key, $quantity = 0 ) {
			if ( ! empty( WC()->cart->cart_contents[ $cart_item_key ] ) && ( isset( WC()->cart->cart_contents[ $cart_item_key ]['abp_assorted_product_keys'] ) ) ) {
				if ( $quantity <= 0 ) {
					$quantity = 0;
				} else {
					$quantity = WC()->cart->cart_contents[ $cart_item_key ]['quantity'];
				}
				foreach ( WC()->cart->cart_contents[ $cart_item_key ]['abp_assorted_product_keys'] as $abp_assorted_product_key ) {
					WC()->cart->set_quantity( $abp_assorted_product_key, $quantity * ( WC()->cart->cart_contents[ $abp_assorted_product_key ]['abp_assorted_product_qty'] ? WC()->cart->cart_contents[ $abp_assorted_product_key ]['abp_assorted_product_qty'] : 1 ), false );
				}
			}
		}
		public function abp_assorted_product_cart_item_removed( $cart_item_key, $cart ) {
			if ( isset( $cart->removed_cart_contents[ $cart_item_key ]['abp_assorted_product_keys'] ) ) {
				$abp_assorted_product_keys = $cart->removed_cart_contents[ $cart_item_key ]['abp_assorted_product_keys'];
				foreach ( $abp_assorted_product_keys as $abp_assorted_product_key ) {
					unset( $cart->cart_contents[ $abp_assorted_product_key ] );
				}
			}
		}
		public function abp_assorted_product_add_to_cart( $cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data )
        {
			if ( isset( $cart_item_data['abp-assorted-add-to-cart'] ) && ( '' != $cart_item_data['abp-assorted-add-to-cart'] ) ) {
				$items = explode( ',', $cart_item_data['abp-assorted-add-to-cart'] );
				if ( is_array( $items ) && ( count( $items ) > 0 ) ) {
					// add child products
					foreach ( $items as $item) {
						$abp_assorted_product_item_id           = absint($item);
						$abp_assorted_product_item_qty          = isset($cart_item_data['abp-assorted-item-quantity'][$item]) ? $cart_item_data['abp-assorted-item-quantity'][$item] : 1 ;
						$abp_assorted_product_item_variation_id = 0;
						$abp_assorted_product_item_variation    = array();
						$abp_assorted_product_product = wc_get_product( $abp_assorted_product_item_id );
						if ( $abp_assorted_product_product ) {
							// set price zero for child product
							$abp_assorted_product_product->set_price( 0 );
							// add to cart
							$abp_assorted_product_product_qty = $abp_assorted_product_item_qty * $quantity;
							$abp_assorted_product_cart_id     = WC()->cart->generate_cart_id( $abp_assorted_product_item_id, $abp_assorted_product_item_variation_id, $abp_assorted_product_item_variation, array(
								'abp_assorted_product_parent_id'  => $product_id,
								'abp_assorted_product_parent_key' => $cart_item_key,
								'abp_assorted_product_qty'        => $abp_assorted_product_item_qty
							) );
							$abp_assorted_product_item_key    = WC()->cart->find_product_in_cart( $abp_assorted_product_cart_id );
							if ( ! $abp_assorted_product_item_key ) {
								$abp_assorted_product_item_key                              = $abp_assorted_product_cart_id;
								WC()->cart->cart_contents[ $abp_assorted_product_item_key ] = array(
									'product_id'       			=> $abp_assorted_product_item_id,
									'variation_id'     			=> $abp_assorted_product_item_variation_id,
									'variation'        			=> $abp_assorted_product_item_variation,
									'quantity'         			=> $abp_assorted_product_product_qty,
									'data'             			=> $abp_assorted_product_product,
									'abp_assorted_product_parent_id'  => $product_id,
									'abp_assorted_product_parent_key' => $cart_item_key,
									'abp_assorted_product_qty'        => $abp_assorted_product_item_qty,
								);
							}
							WC()->cart->cart_contents[ $cart_item_key ]['abp_assorted_product_keys'][] = $abp_assorted_product_item_key;
						}
					}
				}
			}
		}
		public function abp_assorted_product_add_cart_item( $cart_item ) {
			if ( isset( $cart_item['abp_assorted_product_parent_key'] ) ) {
				$cart_item['data']->price = 0;
			}
			return $cart_item;
		}
		public function abp_get_discounted_fee( $price, $product_id, $cart, $parent_key ) {
			$fee = 0;
			if ( 'yes' == get_post_meta( $product_id, 'abp_enable_categories_discounts', true ) ) {
				$discounts = get_post_meta( $product_id, 'abp_assorted_category_discounts', true );
				$founds = array();
				if ( !empty($discounts) ) {
					foreach ( $discounts as $key => $discount ) {
						$cats = explode(',', $discount['cats']);
						if ( !empty($cats) ) {
							foreach ( $cats as $cat ) {
								foreach ( $cart as $cart_item_key => $cart_item ) {
									if ( isset( $cart_item['abp_assorted_product_parent_key'] ) && $parent_key == $cart_item['abp_assorted_product_parent_key'] && has_term( $cat, 'product_cat', $cart_item['product_id'] ) ) {
										$founds[$cat] = array(
											'qty' => isset($founds[$cat]['qty']) ? absint($founds[$cat]['qty']) + $cart_item['quantity'] : $cart_item['quantity'],
											'items' => $discount['items'],
											'amount' => $discount['amount'],
											'type' => $discount['type'],
											'cat' => $cat
										);
									}
								}
							}
						}
					}
				}
				if ( !empty($founds) ) {
					foreach ( $founds as $found ) {
						if ( $found['qty'] >= $found['items'] ) {
							if ( 'fixed' == $found['type'] ) {
								$fee += $found['amount'];
							} else {
								$fee += $price - $price / 100 * $found['amount'];
							}
						}
					}
				}
			}
			if ( 'yes' == get_post_meta( $product_id, 'abp_enable_quantities_discounts', true ) ) {
				$qties = 0;
				foreach ( $cart as $cart_item_key => $cart_item ) {
					if ( isset( $cart_item['abp_assorted_product_parent_key'] ) && $parent_key == $cart_item['abp_assorted_product_parent_key'] ) {
						$qties += $cart_item['quantity'];
					}
				}
				$discounts = get_post_meta( $product_id, 'abp_assorted_quantities_discounts', true );
				if ( !empty($discounts['items']) && !empty($discounts['amount']) && $qties >= $discounts['items'] ) {
					if ( 'fixed' == $discounts['type'] ) {
						$fee += $discounts['amount'];
					} else {
						$fee += $price - $price / 100 * $discounts['amount'];
					}
				}
			}
			return $fee;
		}
		public function abp_get_discounted_price( $price, $product_id, $cart, $parent_key ) {
			if ( 'yes' == get_post_meta( $product_id, 'abp_enable_categories_discounts', true ) ) {
				$discounts = get_post_meta( $product_id, 'abp_assorted_category_discounts', true );
				$founds = array();
				if ( !empty($discounts) ) {
					foreach ( $discounts as $key => $discount ) {
						$cats = explode(',', $discount['cats']);
						if ( !empty($cats) ) {
							foreach ( $cats as $cat ) {
								foreach ( $cart as $cart_item_key => $cart_item ) {
									if ( isset( $cart_item['abp_assorted_product_parent_key'] ) && $parent_key == $cart_item['abp_assorted_product_parent_key'] && has_term( $cat, 'product_cat', $cart_item['product_id'] ) ) {
										$founds[$cat] = array(
											'qty' => isset($founds[$cat]['qty']) ? absint($founds[$cat]['qty']) + $cart_item['quantity'] : $cart_item['quantity'],
											'items' => $discount['items'],
											'amount' => $discount['amount'],
											'type' => $discount['type'],
											'cat' => $cat
										);
									}
								}
							}
						}
					}
				}
				if ( !empty($founds) ) {
					foreach ( $founds as $found ) {
						if ( $found['qty'] >= $found['items'] ) {
							if ( 'fixed' == $found['type'] ) {
								$price = $price - $found['amount'];
							} else {
								$price = $price - $price / 100 * $found['amount'];
							}
						}
					}
				}
			}
			if ( 'yes' == get_post_meta( $product_id, 'abp_enable_quantities_discounts', true ) ) {
				$qties = 0;
				foreach ( $cart as $cart_item_key => $cart_item ) {
					if ( isset( $cart_item['abp_assorted_product_parent_key'] ) && $parent_key == $cart_item['abp_assorted_product_parent_key'] ) {
						$qties += $cart_item['quantity'];
					}
				}
				$discounts = get_post_meta( $product_id, 'abp_assorted_quantities_discounts', true );
				if ( !empty($discounts['items']) && !empty($discounts['amount']) && $qties >= $discounts['items'] ) {
					if ( 'fixed' == $discounts['type'] ) {
						$price = $price - $discounts['amount'];
					} else {
						$price = $price - $price / 100 * $discounts['amount'];
					}
				}
			}
			return $price;
		}
		public function abp_assorted_product_before_calculate_totals( $cart_object ) {

			//  This is necessary for WC 3.0+
			if (is_admin() && !defined( 'DOING_AJAX' ) ) {
				return;
			}
			foreach ( $cart_object->get_cart() as $cart_item_key => $cart_item ) {

				// child product price
				if ( isset( $cart_item['abp_assorted_product_parent_id'] ) && ( '' != $cart_item['abp_assorted_product_parent_id'] ) ) {
					$cart_item['data']->set_price(0);
				}
				// main product price
				if ( isset( $cart_item['abp-assorted-add-to-cart'] ) && ( '' != $cart_item['abp-assorted-add-to-cart'] ) ) {
					$abp_assorted_product_items = explode( ',', $cart_item['abp-assorted-add-to-cart'] );
					//$abp_assorted_product_items =array_count_values($abp_assorted_product_items);
					$product_id  = $cart_item['product_id'];
					if ( 'yes'!== get_post_meta($product_id, 'abp_allow_tax_for_bundle', true) ) {
						$cart_item['data']->set_tax_status('none');
					}
					$abp_assorted_product_price = 0;
					$pricing_type = get_post_meta( $product_id, 'abp_pricing_type', true );
					if ( 'per_product_items' == $pricing_type || 'per_product_and_items' == $pricing_type ) {
						if ( is_array( $abp_assorted_product_items ) && count( $abp_assorted_product_items ) > 0 ) {
							foreach ( $abp_assorted_product_items as $item) {
								$item      = absint( $item );
								$qty = isset($cart_item['abp-assorted-item-quantity'][$item]) ? $cart_item['abp-assorted-item-quantity'][$item] : 1 ;
								$abp_assorted_product_item_product = wc_get_product( $item );
								if ( ! $abp_assorted_product_item_product || $abp_assorted_product_item_product->is_type( 'assorted_product' ) ) {
									continue;
								}
								$abp_assorted_product_price += floatval($abp_assorted_product_item_product->get_price())*$qty;
							}
						}
					} else {
						$assorted_product=wc_get_product( $product_id );
						$box_price=$assorted_product->get_price();
						$abp_assorted_product_price = $box_price;
					}
					// per item + base price
					if ( ( 'per_product_and_items' == $pricing_type ) && is_numeric( $abp_assorted_product_price ) ) {
						$assorted_product=wc_get_product( $product_id );
						$box_price=$assorted_product->get_price();
						$abp_assorted_product_price +=$box_price;
					}
					if ( 'yes' != get_option('abp_assorted_show_discount_fee') ) {
						$abp_assorted_product_price = $this->abp_get_discounted_price( $abp_assorted_product_price, $product_id, $cart_object->get_cart(), $cart_item['key'] );
					}
					$cart_item['data']->set_price( floatval($abp_assorted_product_price));
				}
			}
		}
		public function abp_fee_based_on_cart_total( $cart ) {
            return 'test';
			if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
				return;
			}

			$fee = 0;
			foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {
				// main product price
				$abp_assorted_discount = 0;
				if ( isset( $cart_item['abp-assorted-add-to-cart'] ) && ( '' != $cart_item['abp-assorted-add-to-cart'] ) ) {
					$abp_assorted_product_items = explode( ',', $cart_item['abp-assorted-add-to-cart'] );
					$product_id  = $cart_item['product_id'];
					$pricing_type = get_post_meta( $product_id, 'abp_pricing_type', true );
					if ( 'per_product_items' == $pricing_type || 'per_product_and_items' == $pricing_type ) {
						if ( is_array( $abp_assorted_product_items ) && count( $abp_assorted_product_items ) > 0 ) {
							foreach ( $abp_assorted_product_items as $item) {
								$item      = absint( $item );
								$qty = isset($cart_item['abp-assorted-item-quantity'][$item]) ? $cart_item['abp-assorted-item-quantity'][$item] : 1 ;
								$abp_assorted_product_item_product = wc_get_product( $item );
								if ( ! $abp_assorted_product_item_product || $abp_assorted_product_item_product->is_type( 'assorted_product' ) ) {
									continue;
								}
								$abp_assorted_discount += floatval($abp_assorted_product_item_product->get_price())*$qty;
							}
						}
					} else {
						$assorted_product = wc_get_product( $product_id );
						$box_price = $assorted_product->get_price();
						$abp_assorted_discount = $box_price;
					}
					// per item + base price
					if ( ( 'per_product_and_items' == $pricing_type ) && is_numeric( $abp_assorted_discount ) ) {
						$assorted_product = wc_get_product( $product_id );
						$box_price = $assorted_product->get_price();
						$abp_assorted_discount += $box_price;
					}
					$fee += $this->abp_get_discounted_fee( $abp_assorted_discount, $product_id, $cart->get_cart(), $cart_item['key'] );
				}
			}
			if ( $fee > 0 ) {
				$cart->add_fee( esc_html__( 'Discount', 'wc-abp' ), -$fee, false );
			}
		}
		public function abp_assorted_product_cart_shipping_packages( $packages ) {
			if ( ! empty( $packages ) ) {
				foreach ( $packages as $package_key => $package ) {
					if ( ! empty( $package['contents'] ) ) {
						foreach ( $package['contents'] as $cart_item_key => $cart_item ) {
							if ( isset( $cart_item['abp_assorted_product_parent_id'] ) && ( '' != $cart_item['abp_assorted_product_parent_id'] ) ) {
								$prod_id=$cart_item['abp_assorted_product_parent_id'];
								if ( 'yes' !== get_post_meta($prod_id, 'abp_per_item_shipping', true) ) {
									unset( $packages[ $package_key ]['contents'][ $cart_item_key ] );
								}
							}
						}
					}
				}
			}
			return $packages;
		}
	}
	new ABP_Assorted_Product_Frontend_Cart();
}
