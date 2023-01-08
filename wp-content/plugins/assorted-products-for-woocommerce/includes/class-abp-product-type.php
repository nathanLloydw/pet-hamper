<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}
if ( !class_exists('WC_Product_Assorted_Product') ) {
	class WC_Product_Assorted_Product extends WC_Product {
		public function __construct( $product ) {
			$this->product_type = 'assorted_product';
			parent::__construct( $product );
		}
		/*
		* Custom Product Type
		*/
		public function get_type() {
			return 'assorted_product';
		}
		/*
		* Add to cart change
		*/
		public function add_to_cart_text() {
			$btn_text = get_option('abp_assorted_products_readmore_text');
			$btn_text = !empty($btn_text) ? esc_html__($btn_text, 'wc-abp') : esc_html__('Read More', 'wc-abp');
			$text = $this->is_purchasable() && $this->is_in_stock() ? $btn_text : esc_html__( 'Read More', 'wc-abp' );
			return apply_filters( 'woo_ubp_product_add_to_cart_text', $text, $this );
		}
		public function add_to_cart_description() {
			/* translators: %s: Product title */
			$text = $this->is_purchasable() && $this->is_in_stock() ? esc_html__( 'View Products &ldquo;%s&rdquo; and add to your cart', 'wc-abp' ) : esc_html__( 'Read more about &ldquo;%s&rdquo;', 'wc-abp' );
			return apply_filters( 'woo_ubp_product_add_to_cart_description', sprintf( $text, $this->get_name() ), $this );
		}
		public function single_add_to_cart_text() {
			return apply_filters( 'ubp_box_product_single_add_to_cart_text', esc_html__( 'Add to cart', 'wc-abp' ), $this );
		}
	}
}
