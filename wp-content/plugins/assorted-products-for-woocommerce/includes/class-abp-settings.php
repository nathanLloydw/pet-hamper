<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly.
}
if ( !class_exists('ABP_Assorted_Product_General_Settings') ) {
	class ABP_Assorted_Product_General_Settings {
		public function __construct() {
			add_action('admin_menu', array(__CLASS__, 'abp_add_menu_page'));
			add_action('admin_init', array(__CLASS__, 'abp_register_settings'));
			add_filter('woocommerce_screen_ids', array(__CLASS__, 'abp_screen_ids'), 12, 1 );
		}
		public static function abp_screen_ids( $screen_ids ) {
			$screen_ids[] = 'assorted-products_page_abp-assorted-suscriptions';
			return $screen_ids;
		}
		public static function abp_add_menu_page() {
			add_menu_page(esc_html__('Assorted Products', 'wc-abp'), esc_html__('Assorted Products', 'wc-abp'), 'manage_options', 'abp-assorted-products', array(__CLASS__, 'abp_menu_page_callback'), 'dashicons-store', 58);
			add_submenu_page( 'abp-assorted-products', esc_html__('Assorted Products', 'wc-abp'), esc_html__('Assorted Products', 'wc-abp'), 'manage_options', 'abp-assorted-products', array(__CLASS__, 'abp_menu_page_callback'), 0 );
		}
		public static function abp_register_settings() {
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_per_page');
			register_setting('abp-assorted-products-settings', 'abp_assorted_load_template');
			register_setting('abp-assorted-products-settings', 'abp_assorted_hide_search_filters');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_item_btn_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_addtocart_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_readmore_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_readmore_item');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_loadmore_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_order_details_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_search_btn_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_reset_btn_text'); 
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_item_added_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_product_show_sku_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_max_error_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_item_max_error');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_individually_error_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_show_description');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_description_position');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_item_click');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_remove_addtocart');
			register_setting('abp-assorted-products-settings', 'abp_edit_box_subscription_myaccount');
			register_setting('abp-assorted-products-settings', 'abp_edit_box_subscription_message');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_counter');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_clear');
			register_setting('abp-assorted-products-settings', 'abp_assorted_products_clear_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_items_show_description');
			register_setting('abp-assorted-products-settings', 'abp_assorted_hide_items_parent_name');
			register_setting('abp-assorted-products-settings', 'abp_assorted_show_product_discount');
			register_setting('abp-assorted-products-settings', 'abp_assorted_show_discount_fee');
			register_setting('abp-assorted-products-settings', 'abp_assorted_discount_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_show_extra_fee');
			register_setting('abp-assorted-products-settings', 'abp_assorted_extra_fee_cart');
			register_setting('abp-assorted-products-settings', 'abp_assorted_extra_fee_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_show_mobile_bar');
			register_setting('abp-assorted-products-settings', 'abp_assorted_mobile_bar_text');
			register_setting('abp-assorted-products-settings', 'abp_assorted_mobile_bar_button_text');
		}
		public static function abp_menu_page_callback() { 
			?>
			<div class="wrap">
				<h1><?php esc_html_e('Assorted Products - Settings', 'wc-abp'); ?></h1>
				<form method="post" action="options.php">
					<?php settings_errors(); ?>
					<?php settings_fields('abp-assorted-products-settings'); ?>
					<?php do_settings_sections('abp-assorted-products-settings'); ?>
					<table class="form-table">
						<tr valign="top">
							<th>
								<label for="abp_assorted_products_per_page"><?php esc_html_e('Per Page Items', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_per_page', '12'); ?>
								<input type="number" name="abp_assorted_products_per_page" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_per_page" min="1" class="regular-text">
								<p><i><?php esc_html_e('The number of product items per page.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_load_template"><?php esc_html_e('Load Template Forcefully', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_load_template'); ?>
								<input type="checkbox" name="abp_assorted_load_template" value="yes" id="abp_assorted_load_template" <?php checked('yes', $value); ?>>
								<span><i><?php esc_html_e('Enable this option to load single product template forcefully, if product page is designed with any page builder & Assorted products do not show actual layout.', 'wc-abp'); ?></i></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_hide_search_filters"><?php esc_html_e('Hide Search Filters', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_hide_search_filters'); ?>
								<input type="checkbox" name="abp_assorted_hide_search_filters" value="yes" id="abp_assorted_hide_search_filters" <?php checked('yes', $value); ?>>
								<span><i><?php esc_html_e('Enable this option to hide filters on product page.', 'wc-abp'); ?></i></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_addtocart_text"><?php esc_html_e('Add To Cart Button Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_addtocart_text', esc_html__('Add to cart', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_products_addtocart_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_addtocart_text" class="regular-text">
								<p><i><?php esc_html_e('The Add to cart button text for assorted products on single product page.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_readmore_text"><?php esc_html_e('Read More Button Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_readmore_text', esc_html__('Read More', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_products_readmore_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_readmore_text" class="regular-text">
								<p><i><?php esc_html_e('The Add to cart button text for assorted products on shop & archive pages.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_readmore_item"><?php esc_html_e('Read More Button Text For Items', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_readmore_item', esc_html__('Read More', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_products_readmore_item" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_readmore_item" class="regular-text">
								<p><i><?php esc_html_e('The Add to cart button text for assorted product items if not purchasable.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_loadmore_text"><?php esc_html_e('Load More Button Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_loadmore_text', esc_html__('Load More', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_products_loadmore_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_loadmore_text" class="regular-text">
								<p><i><?php esc_html_e('The load more button text for assorted products.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_item_btn_text"><?php esc_html_e('Add To Bundle Button Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_item_btn_text', esc_html__('Add to bundle', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_products_item_btn_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_item_btn_text" class="regular-text">
								<p><i><?php esc_html_e('The Add to bundle text for product items on single product page.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_product_show_sku_text"><?php esc_html_e('Show SKU Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_product_show_sku_text'); ?>
								<input type="text" name="abp_assorted_product_show_sku_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_product_show_sku_text" class="regular-text">
								<p><i><?php esc_html_e('The sku text for items on Assorted product page.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_order_details_text"><?php esc_html_e('Order Details Heading Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_order_details_text', esc_html__('Order Details', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_products_order_details_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_order_details_text" class="regular-text">
								<p><i><?php esc_html_e('The Order Details heading text for product items on single product page.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_search_btn_text"><?php esc_html_e('Search Filters Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_search_btn_text', esc_html__('Search', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_products_search_btn_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_search_btn_text" class="regular-text">
								<p><i><?php esc_html_e('The Search filters button text on single product page.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_reset_btn_text"><?php esc_html_e('Reset Filters Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_reset_btn_text', esc_html__('Reset Filters', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_products_reset_btn_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_reset_btn_text" class="regular-text">
								<p><i><?php esc_html_e('The Reset filters button text on single product page.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_item_added_text"><?php esc_html_e('Product Added To Bundle Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_item_added_text', esc_html__('Product has been added to bundle.', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_products_item_added_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_item_added_text" class="regular-text">
								<p><i><?php esc_html_e('The text when an item is added to bundle.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_max_error_text"><?php esc_html_e('Max Items Added To Bundle Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_max_error_text', esc_html__('You can not add more products.', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_products_max_error_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_max_error_text" class="regular-text">
								<p><i><?php esc_html_e('The error text when maximum items added to bundle.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_item_max_error"><?php esc_html_e('Item Max Quantity Error Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_item_max_error', esc_html__('Maximum item quantity is added.', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_products_item_max_error" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_item_max_error" class="regular-text">
								<p><i><?php esc_html_e('The error text when maximum quantity of an item is added to bundle.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_individually_error_text"><?php esc_html_e('Restrict Twice Item Add Error', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_individually_error_text'); ?>
								<input type="text" name="abp_assorted_products_individually_error_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_individually_error_text" class="regular-text">
								<p><i><?php esc_html_e('The error text when if the same item is added to the bundle & restrict twice is enabled.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_show_description"><?php esc_html_e('Assorted Products Short Description', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_show_description'); ?>
								<input type="checkbox" name="abp_assorted_products_show_description" value="yes" id="abp_assorted_products_show_description" <?php checked('yes', $value); ?>>
								<span><i><?php esc_html_e('Show assorted products short description on product page.', 'wc-abp'); ?></i></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_description_position"><?php esc_html_e('Short Description Position', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_description_position'); ?>
								<p><label><input type="radio" name="abp_assorted_products_description_position" value="after_price" id="abp_assorted_products_description_position" <?php checked('after_price', $value); ?>>
									<span><i><?php esc_html_e('After Price', 'wc-abp'); ?></i></span></label></p>
								<p><label><input type="radio" name="abp_assorted_products_description_position" value="before_price" id="abp_assorted_products_description_position" <?php checked('before_price', $value); ?>>
									<span><i><?php esc_html_e('Before Price', 'wc-abp'); ?></i></span></label></p>
								<p><label><input type="radio" name="abp_assorted_products_description_position" value="after_title" id="abp_assorted_products_description_position" <?php checked('after_title', $value); ?>>
									<span><i><?php esc_html_e('After Product Title', 'wc-abp'); ?></i></span></label></p>
								<p><label><input type="radio" name="abp_assorted_products_description_position" value="before_title" id="abp_assorted_products_description_position" <?php checked('before_title', $value); ?>>
									<span><i><?php esc_html_e('Before Product Title', 'wc-abp'); ?></i></span></label></p>
								<p><label><input type="radio" name="abp_assorted_products_description_position" value="after_layout" id="abp_assorted_products_description_position" <?php checked('after_layout', $value); ?>>
									<span><i><?php esc_html_e('After Product Items', 'wc-abp'); ?></i></span></label></p>
								<p><label><input type="radio" name="abp_assorted_products_description_position" value="after_order" id="abp_assorted_products_description_position" <?php checked('after_order', $value); ?>>
									<span><i><?php esc_html_e('After Order Details', 'wc-abp'); ?></i></span></label></p>
							</td>
						</tr>
						<tr>
							<?php $box_item_click = get_option('abp_assorted_products_item_click'); ?>
							<th><label for="enable_scroll_top"><?php esc_html_e('On Click Bundle Items Title & Image Link', 'wc-abp'); ?></label></th>
							<td>
								<p>
									<label><input type="radio" name="abp_assorted_products_item_click" class="regular-text" value="redirect" <?php checked($box_item_click, 'redirect'); ?> /> <i><?php esc_html_e('Redirect to product item\'s single product page on click of product item\'s title & image link.', 'wc-abp'); ?></i></label>
								</p>
								<p>
									<label><input type="radio" name="abp_assorted_products_item_click" class="regular-text" value="quickview" <?php checked($box_item_click, 'quickview'); ?> /> <i><?php esc_html_e('Open quick view popup on click of product item\'s title & image link.', 'wc-abp'); ?></i></label>
								</p>
								<p>
									<label><input type="radio" name="abp_assorted_products_item_click" class="regular-text" value="addtobundle" <?php checked($box_item_click, 'addtobundle'); ?> /> <i><?php esc_html_e('Add item to bundle on click of product item\'s title & image link.', 'wc-abp'); ?></i></label>
								</p>
								<p>
									<label><input type="radio" name="abp_assorted_products_item_click" class="regular-text" value="noaction" <?php checked($box_item_click, 'noaction'); ?> /> <i><?php esc_html_e('Disable any action on click of product item\'s title & image link.', 'wc-abp'); ?></i></label>
								</p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_remove_addtocart"><?php esc_html_e('Remove Add To Cart Button', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_remove_addtocart'); ?>
								<input type="checkbox" name="abp_assorted_products_remove_addtocart" value="yes" id="abp_assorted_products_remove_addtocart" <?php checked('yes', $value); ?>>
								<span><i><?php esc_html_e('Remove add to cart button in quick view for the assorted product items.', 'wc-abp'); ?></i></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_edit_box_subscription_myaccount"><?php esc_html_e('Allow Subscription Bundle Edit On My Account Page', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_edit_box_subscription_myaccount'); ?>
								<input type="checkbox" name="abp_edit_box_subscription_myaccount" value="yes" id="abp_edit_box_subscription_myaccount" <?php checked('yes', $value); ?>>
								<span><i><?php esc_html_e('Allow customer to edit the subscribed bundle on the my account page.', 'wc-abp'); ?></i></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_edit_box_subscription_message"><?php esc_html_e('Subscription Update Message', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_edit_box_subscription_message', esc_html__('Your subscription has been updated.', 'wc-abp')); ?>
								<input type="text" name="abp_edit_box_subscription_message" value="<?php esc_attr_e($value); ?>" id="abp_edit_box_subscription_message" class="regular-text">
								<p><i><?php esc_html_e('Enter the message when bundle items of subscriptions are updated.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_counter"><?php esc_html_e('Bundle Counter', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_counter', esc_html__('{counter} added', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_products_counter" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_counter" class="regular-text">
								<p><i><?php esc_html_e('Enter the bundle items counter text, kindly use the {counter} tag for dynamic counting.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_items_show_description"><?php esc_html_e('Enable Items Short Description', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_items_show_description'); ?>
								<input type="checkbox" name="abp_assorted_items_show_description" value="yes" id="abp_assorted_items_show_description" <?php checked('yes', $value); ?>>
								<span><i><?php esc_html_e('Enable short description for items of Assorted products.', 'wc-abp'); ?></i></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_clear"><?php esc_html_e('Enable Clear Bundle', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_clear'); ?>
								<input type="checkbox" name="abp_assorted_products_clear" value="yes" id="abp_assorted_products_clear" <?php checked('yes', $value); ?>>
								<span><i><?php esc_html_e('Enable clear button to allow customers to clear the bundle created.', 'wc-abp'); ?></i></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_products_clear_text"><?php esc_html_e('Bundle Counter', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_products_clear_text', esc_html__('Clear All', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_products_clear_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_products_clear_text" class="regular-text">
								<p><i><?php esc_html_e('Add the clear bundle link text.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_hide_items_parent_name"><?php esc_html_e('Hide Items Parent Name From Cart Item Name', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_hide_items_parent_name'); ?>
								<input type="checkbox" name="abp_assorted_hide_items_parent_name" value="yes" id="abp_assorted_hide_items_parent_name" <?php checked('yes', $value); ?>>
								<span><i><?php esc_html_e('Enable this option to hide items parent name from cart item names.', 'wc-abp'); ?></i></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_show_product_discount"><?php esc_html_e('Show Discount Separate On Product Page', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_show_product_discount'); ?>
								<input type="checkbox" name="abp_assorted_show_product_discount" value="yes" id="abp_assorted_show_product_discount" <?php checked('yes', $value); ?>>
								<span><i><?php esc_html_e('Enable this option to show discount on the product page.', 'wc-abp'); ?></i></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_show_discount_fee"><?php esc_html_e('Show Discount Separate On Cart Page', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_show_discount_fee'); ?>
								<input type="checkbox" name="abp_assorted_show_discount_fee" value="yes" id="abp_assorted_show_discount_fee" <?php checked('yes', $value); ?>>
								<span><i><?php esc_html_e('Enable this option to show discount separate on the cart page below subtotal.', 'wc-abp'); ?></i></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_discount_text"><?php esc_html_e('Discount Label Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_discount_text', esc_html__('Discount', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_discount_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_discount_text" class="regular-text">
								<p><i><?php esc_html_e('Add the discount label text.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<td colspan="2"><h2><?php esc_html_e('Extra Fee Options', 'wc-abp'); ?></h2><hr></td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_show_extra_fee"><?php esc_html_e('Show Extra Fee On Product Page', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_show_extra_fee'); ?>
								<input type="checkbox" name="abp_assorted_show_extra_fee" value="yes" id="abp_assorted_show_extra_fee" <?php checked('yes', $value); ?>>
								<span><i><?php esc_html_e('Enable this option to show extra fee on the product page below subtotal.', 'wc-abp'); ?></i></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_extra_fee_cart"><?php esc_html_e('Show Extra Fee Separate On Cart Page', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_extra_fee_cart'); ?>
								<input type="checkbox" name="abp_assorted_extra_fee_cart" value="yes" id="abp_assorted_extra_fee_cart" <?php checked('yes', $value); ?>>
								<span><i><?php esc_html_e('Enable this option to show extra fee separate on the cart page below subtotal.', 'wc-abp'); ?></i></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_extra_fee_text"><?php esc_html_e('Extra Fee Label Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_extra_fee_text', esc_html__('Extra Fee', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_extra_fee_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_extra_fee_text" class="regular-text">
								<p><i><?php esc_html_e('Add the extra fee label text.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<td colspan="2"><h2><?php esc_html_e('Footer Bar For Mobiles Options', 'wc-abp'); ?></h2><hr></td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_show_mobile_bar"><?php esc_html_e('Show Footer Bar For Mobiles On Product Page', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_show_mobile_bar'); ?>
								<input type="checkbox" name="abp_assorted_show_mobile_bar" value="yes" id="abp_assorted_show_mobile_bar" <?php checked('yes', $value); ?>>
								<span><i><?php esc_html_e('Enable this option to show footer bar for mobiles on the product page.', 'wc-abp'); ?></i></span>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_mobile_bar_text"><?php esc_html_e('Foot Bar Subtotal Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_mobile_bar_text', esc_html__('Subtotal: ', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_mobile_bar_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_mobile_bar_text" class="regular-text">
								<p><i><?php esc_html_e('Add the subtotal label text for mobile bar.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
						<tr>
							<th>
								<label for="abp_assorted_mobile_bar_button_text"><?php esc_html_e('Foot Bar Button Text', 'wc-abp'); ?></label>
							</th>
							<td>
								<?php $value = get_option('abp_assorted_mobile_bar_button_text', esc_html__('Add To Cart', 'wc-abp')); ?>
								<input type="text" name="abp_assorted_mobile_bar_button_text" value="<?php esc_attr_e($value); ?>" id="abp_assorted_mobile_bar_button_text" class="regular-text">
								<p><i><?php esc_html_e('Add the button label text for mobile bar, add {count} tag for dynamic counting of items with label.', 'wc-abp'); ?></i></p>
							</td>
						</tr>
					</table>
					<?php submit_button(); ?>
				</form>
			</div>
			<?php
		}
	}
	new ABP_Assorted_Product_General_Settings();
}
