<?php
if ( !defined('ABSPATH') ) {
	exit(); // Exit if accessed directly.
}
if ( !class_exists('ABP_Assorted_Product_Backend') ) {
	class ABP_Assorted_Product_Backend {
		public function __construct() {
			add_filter('product_type_selector', array(__CLASS__, 'abp_add_custom_product_type' ));
			add_filter('woocommerce_product_data_tabs', array(__CLASS__, 'abp_custom_product_tabs' ), 22, 1);
			add_action('woocommerce_product_data_panels', array(__CLASS__, 'abp_product_tab_content'));
			add_action('woocommerce_process_product_meta_assorted_product', array(__CLASS__, 'abp_save_product_box_options_field' ), 10, 1 );
			add_filter('woocommerce_json_search_found_products', array(__CLASS__, 'abp_search_found_products'), 10, 1 );
			add_filter('woocommerce_product_pre_search_products', array(__CLASS__, 'abp_disable_search_products'), 10, 1 );
			add_filter('woocommerce_json_search_limit', array(__CLASS__, 'abp_disable_search_products'), 10, 1 );
			add_action('admin_enqueue_scripts', array(__CLASS__, 'abp_enqueue_scripts' ));
		}
		public static function abp_add_custom_product_type( $types ) {
			$types['assorted_product'] = esc_html__( 'Assorted Product', 'wc-abp');
			return $types;
		}
		public static function abp_custom_product_tabs( $tabs ) {
			$tabs['abp_assorted'] = array(
				'label'		=> esc_html__( 'Assorted Product', 'wc-abp' ),
				'target'	=> 'abp_custom_product_box',
				'class'		=> array('hide_if_simple', 'hide_if_variable', 'hide_if_grouped', 'hide_if_external'),
				'priority' 	=> 80,
			);
			$tabs['abp_assorted_extra_options'] = array(
				'label'		=> esc_html__( 'Extra Product Options', 'wc-abp' ),
				'target'	=> 'abp_assorted_extra_options',
				'class'		=> array( 'hide_if_simple', 'hide_if_variable', 'hide_if_grouped', 'hide_if_external'),
				'priority' 	=> 85,
			);
			return $tabs;
		}
		public static function abp_product_tab_content() {
			global $woocommerce, $post, $product_object;
			$post_id = $post->ID;
			echo "<div id='abp_custom_product_box' class='panel woocommerce_options_panel wc-metaboxes-wrapper'>";
			wp_nonce_field( 'abp_assorted_product_nonce', 'abp_assorted_product_nonce' );
			$abp = get_post_meta($post_id, 'abp_pricing_type', true);
			woocommerce_wp_select(
				array(
					'id'                => 'abp_pricing_type',
					'label'             => esc_html__( 'Pricing Type', 'wc-abp' ),
					'description'       => esc_html__( 'Select Pricing Type', 'wc-abp' ),
					'type'              => 'select',
					'options'     => array(
							'regular' 				=> esc_html__('Fixed Regular Price', 'wc-abp'),
							'per_product_items'  	=> esc_html__('Per item price', 'wc-abp'),
							'per_product_and_items' => esc_html__('Per product + bundle price', 'wc-abp')
							),
					'desc_tip'    	   => 'true',
					'value'			   => $abp
				)
			);
			$abp = get_post_meta($post_id, 'abp_assorted_products_layout', true);
			woocommerce_wp_select(
				array(
					'id'                => 'abp_assorted_products_layout',
					'label'             => esc_html__( 'Product Layout', 'wc-abp' ),
					'description'       => esc_html__( 'Select layout of product items display.', 'wc-abp' ),
					'type'              => 'select',
					'options'     => array(
							'left_side' 	=> esc_html__('Left Side Items Listings', 'wc-abp'),
							'right_side'  => esc_html__('Right Side Items Listings', 'wc-abp')
							),
					'desc_tip'    	   => 'true',
					'value'			   => $abp
				)
			);
			$abp = get_post_meta($post_id, 'abp_assorted_min_products', true);
			woocommerce_wp_text_input(
				array(
					'id'                => 'abp_assorted_min_products',
					'label'             => esc_html__( 'Bundle Minimum Products', 'wc-abp' ),
					'placeholder'       => '',
					'value'			   => ( !empty($abp) ? $abp : 1 ),
					'desc_tip'    	   => 'true',
					'description'       => esc_html__( 'Enter minimum number of product to be added for a bundle.', 'wc-abp' ),
					'type'              => 'number'
				)
			);
			// Create a number field, for box max products
			$abp = get_post_meta($post_id, 'abp_assorted_max_products', true);
			woocommerce_wp_text_input(
				array(
					'id'                => 'abp_assorted_max_products',
					'label'             => esc_html__( 'Bundle Maximum Products', 'wc-abp' ),
					'placeholder'       => '',
					'value'			   => ( !empty($abp) ? $abp : 1 ),
					'desc_tip'    	   => 'true',
					'description'       => esc_html__( 'Enter maximum number of product to be added for a bundle.', 'wc-abp' ),
					'type'              => 'number'
				)
			);
			// Create a number field, for box columns
			$abp = get_post_meta($post_id, 'abp_assorted_columns', true);
			woocommerce_wp_select(
				array(
					'id'                => 'abp_assorted_columns',
					'label'             => esc_html__( 'Bundle Layout Columns', 'wc-abp' ),
					'type'              => 'select',
					'desc_tip'    	   => 'true',
					'description'       => esc_html__( 'Select bundle products columns', 'wc-abp' ),
					'options'     => array(
								'4'	=>  4,
								'3' =>	3,
								'2' =>	2,
								),
					'value'			   => $abp
				)
			);
			// enable categories
			$cats = get_terms( array(
				'taxonomy' => 'product_cat',
				'hide_empty' => false
			) ); 
			$cat_options = array();
			if ( !is_wp_error( $cats ) && !empty($cats) ) {
				foreach ( $cats as $key => $cat ) {
					$cat_options[$cat->term_id] = $cat->name;
				}
			}
			$abp = get_post_meta($post_id, 'abp_products_categories_enabled', true);
			woocommerce_wp_select(
				array(
					'id'                => 'abp_products_categories_enabled[]',
					'label'             => esc_html__( 'Choose Categories', 'wc-abp' ),
					'type'              => 'select',
					'class'			   => 'wc-enhanced-select',
					'style'			   => 'min-width: 50%;',
					'desc_tip'    	   => 'true',
					'description'       => esc_html__( 'Choose product categories for a bundle product to allow product items in the search.', 'wc-abp' ),
					'options'     => $cat_options,
					'value' => $abp,
					'custom_attributes'	=>	array(
											'multiple'	=>	'multiple'
										)
				)
			);
			$tags = get_terms( array(
				'taxonomy' => 'product_tag',
				'hide_empty' => false
			)); 
			$tag_options = array();
			if ( !is_wp_error( $tags ) && !empty($tags) ) {
				foreach ( $tags as $key => $tag ) {
					$tag_options[$tag->term_id] = $tag->name;
				}
			}
			$tags_filter = get_post_meta($post_id, 'abp_products_tags_enabled', true);
			woocommerce_wp_select(
				array(
					'id'                => 'abp_products_tags_enabled[]',
					'label'             => esc_html__( 'Choose Tags', 'wc-abp' ),
					'type'              => 'select',
					'class'			   => 'wc-enhanced-select',
					'style'			   => 'min-width: 50%;',
					'desc_tip'    	   => 'true',
					'description'       => esc_html__( 'Choose product tags for a bundle product to allow product items in the search.', 'wc-abp' ),
					'options'     => $tag_options,
					'value' => $tags_filter,
					'custom_attributes'	=>	array(
										'multiple'	=>	'multiple'
									)
				)
			);
			// choose products
			$items = get_post_meta($post_id, 'abp_products_items_enabled', true);
			$options = array();
			if ( !empty($items) ) {
				foreach ($items as $item) {
					$_prod=wc_get_product($item);
					if ( !is_wp_error($_prod) && is_object($_prod) ) {
						$options[$item] = $_prod->get_name() . ' &ndash; ' . $_prod->get_id();
					}
				}
			}
			woocommerce_wp_select(
				array(
					'id'                => 'abp_products_items_enabled[]',
					'label'             => esc_html__( 'Choose Products', 'wc-abp' ),
					'type'              => 'select',
					'style'			   => 'width: 50%;',
					'desc_tip'    	   => 'true',
					'description'       => esc_html__( 'Choose product categories for a bundle product to allow product items in the search.', 'wc-abp' ),
					'options'     => $options,
					'value'=> $items,
					'class'	=> 'wc-product-search',
					'custom_attributes'	=>	array(
											'data-placeholder'	=> esc_html__('Search for products', 'wc-abp'),
											'data-action'	=>	'woocommerce_json_search_products_and_variations',
											'data-exclude'	=>	'assorted_product',
											'multiple'	=>	'multiple'
										)
				)
			);
			// Create a checkbox for 
			$abp = get_post_meta($post_id, 'abp_complete_store_available', true);
			woocommerce_wp_checkbox(
				array(
					'id'            => 'abp_complete_store_available',
					'label'         => esc_html__('All Products', 'wc-abp' ),
					'description'   => esc_html__( 'Allow customers to create the bundle from all products available in the shop.', 'wc-abp' ),
					'value'		   => $abp
				)
			);
			// create a checkboc for tax
			$abp = get_post_meta($post_id, 'abp_allow_tax_for_bundle', true);
			woocommerce_wp_checkbox(
				array(
					'id'            => 'abp_allow_tax_for_bundle',
					'label'         => esc_html__('Allow Tax Calculation?', 'wc-abp' ),
					'description'   => esc_html__( 'Enable tax calculation for box products', 'wc-abp' ),
					'value'		   => $abp
				)
			);
			// Create a checkbox for per item shipping
			$abp = get_post_meta($post_id, 'abp_per_item_shipping', true);
			woocommerce_wp_checkbox(
				array(
					'id'            => 'abp_per_item_shipping',
					'label'         => esc_html__('Per Item Shipping?', 'wc-abp' ),
					'description'   => esc_html__( 'Enable per item shipping for box products', 'wc-abp' ),
					'value'		   => $abp
				)
			);
			$abp = get_post_meta($post_id, 'abp_assorted_product_individually', true);
			woocommerce_wp_checkbox(
				array(
					'id'            => 'abp_assorted_product_individually',
					'label'         => esc_html__('Restrict Twice Items?', 'wc-abp' ),
					'description'   => esc_html__( 'Restrict users to added the same item twice to the bundle.', 'wc-abp' ),
					'value'		   => $abp
				)
			);
			$abp = get_post_meta($post_id, 'abp_assorted_product_hide_unpurchasable', true);
			woocommerce_wp_checkbox(
				array(
					'id'            => 'abp_assorted_product_hide_unpurchasable',
					'label'         => esc_html__('Hide Unpurchasable Items?', 'wc-abp' ),
					'description'   => esc_html__( 'Hide the unpurchasable items like out of stock & empty price items.', 'wc-abp' ),
					'value'		   => $abp
				)
			);
			$abp = get_post_meta($post_id, 'abp_assorted_product_show_sku', true);
			woocommerce_wp_checkbox(
				array(
					'id'            => 'abp_assorted_product_show_sku',
					'label'         => esc_html__('Show SKU for Items?', 'wc-abp' ),
					'description'   => esc_html__( 'Show sku for items of Assorted products.', 'wc-abp' ),
					'value'		   => $abp
				)
			);
			do_action('abp_after_assorted_product_settings');
			?>
			<hr>
			<div class="abp_categories_filters wc-metaboxes ui-sortable" data-product_id="<?php echo esc_attr($post_id); ?>">
				<div class="wc-metabox closed abp_categories_discounts">
					<h3 class="">
						<div class="tips sort ui-sortable-handle"></div>
						<div class="handlediv"></div>
						<span class="wccp_box_serial"><?php esc_html_e('Categories Filters Settings', 'wc-abp'); ?></span>
					</h3>
					<div style="display:none;" class="wc-metabox-content">
						<?php
						$abp = get_post_meta($post_id, 'abp_enable_categories_filters', true);
						woocommerce_wp_checkbox(
							array(
								'id'            => 'abp_enable_categories_filters',
								'label'         => esc_html__('Enable Categories Filter', 'wc-abp' ),
								'description'   => esc_html__( 'Enable categories filters on frontend.', 'wc-abp' ),
								'value'		   => $abp
							)
						);
						$abp = get_post_meta($post_id, 'abp_assorted_cats_heading', true);
						woocommerce_wp_text_input(
							array(
								'id'                => 'abp_assorted_cats_heading',
								'label'             => esc_html__( 'Categories Filters Heading', 'wc-abp' ),
								'placeholder'       => '',
								'value'			   => $abp,
								'desc_tip'    	   => 'true',
								'description'       => esc_html__( 'Enter heading text for categories filter sections.', 'wc-abp' ),
							)
						);
						$abp = get_post_meta($post_id, 'abp_products_filter_type', true);
						woocommerce_wp_select(
							array(
								'id'                => 'abp_products_filter_type',
								'label'             => esc_html__( 'Filter Type', 'wc-abp' ),
								'type'              => 'select',
								'desc_tip'    	   => 'true',
								'description'       => esc_html__( 'Select the filter type.', 'wc-abp' ),
								'options'     => array(
									'dropdown'	=>  esc_html__( 'Select Dropdown Field', 'wc-abp' ),
									'radio' 	=>	esc_html__( 'Radio Button Fields', 'wc-abp' ),
									'checkbox' 	=>	esc_html__( 'Checkbox Button Fields', 'wc-abp' ),
									'multiple-dropdown'	=>  esc_html__( 'Multi-Select Dropdown Field', 'wc-abp' )
								),
								'value'	=> $abp
							)
						);
						$abp = get_post_meta($post_id, 'abp_assorted_categories_filter', true);
						woocommerce_wp_select(
							array(
							'id'                => 'abp_assorted_categories_filter[]',
							'label'             => esc_html__( 'Choose Categories', 'wc-abp' ),
							'type'              => 'select',
							'class'			   => 'wc-enhanced-select',
							'style'			   => 'min-width: 50%;',
							'desc_tip'    	   => 'true',
							'description'       => esc_html__( 'Choose product categories for a bundle product to show in filters.', 'wc-abp' ),
							'options'     => $cat_options,
							'value' => $abp,
							'custom_attributes'	=>	array(
													'multiple'	=>	'multiple'
												)
							)
						);
						$abp = get_post_meta($post_id, 'abp_assorted_cats_show_count', true);
						woocommerce_wp_checkbox(
							array(
							'id'            => 'abp_assorted_cats_show_count',
							'label'         => esc_html__('Show Product Count?', 'wc-abp' ),
							'description'   => esc_html__( 'Show the products count against each category.', 'wc-abp' ),
							'value'		   => $abp
							)
						);
						$abp = get_post_meta($post_id, 'abp_assorted_cats_show_child', true);
						woocommerce_wp_checkbox(
							array(
							'id'            => 'abp_assorted_cats_show_child',
							'label'         => esc_html__('Show Child Categories?', 'wc-abp' ),
							'description'   => esc_html__( 'Show the hierarchical child categories against each category.', 'wc-abp' ),
							'value'		   => $abp
							)
						);
						?>
					</div>
				</div>
			</div>
			<!-- Attribute filters settings -->
			<div class="cpb_composite_steps wc-metaboxes ui-sortable" data-product_id="<?php echo esc_attr($post_id); ?>">
				<div class="wc-metabox closed abp_assorted_products">
					<h3 class="">
						<div class="tips sort ui-sortable-handle"></div>
						<div class="handlediv"></div>
						<span class="wccp_box_serial"><?php esc_html_e('Attributes Filter Settings', 'wc-abp'); ?></span>
					</h3>
					<div style="display:none;" class="wc-metabox-content">
						<?php
						$attr_filter_options = array();
						$product_attributes = wc_get_attribute_taxonomies();
						if ( !empty($product_attributes) ) {
							foreach ( $product_attributes as $attribute ) {
								$attr_filter_options[$attribute->attribute_id] = $attribute->attribute_name;
							}
						}
						$cpb = get_post_meta($post_id, 'abp_assorted_enable_attribute_filter', true);
						woocommerce_wp_checkbox(
							array(
								'id'            => 'abp_assorted_enable_attribute_filter',
								'label'         => esc_html__('Enable Attributes Filter?', 'wc-abp'),
								'description'   => esc_html__('Enable attributes filters on the frontend.', 'wc-abp'),
								'value'         => $cpb
							)
						);
						$cpb = get_post_meta($post_id, 'abp_assorted_attribute_hide_empty', true);
						woocommerce_wp_checkbox(
							array(
								'id'            => 'abp_assorted_attribute_hide_empty',
								'label'         => esc_html__('Attributes Hide Empty', 'wc-abp'),
								'description'   => esc_html__('Enable to hide the empty attribute terms.', 'wc-abp'),
								'value'         => $cpb
							)
						);
						$attr_filters = get_post_meta($post_id, 'abp_assorted_product_attribute_filters', true);
						if ( empty($attr_filters) ) {
							$attr_filters = array();
							$attr_filters['heading'][0] = '';
							$attr_filters['type'][0] = '';
							$attr_filters['items'][0] = array();
							$attr_filters['show_count'][0] = '';
						}
						?>
						<table class="form-table">
							<thead>
								<tr>
									<th><?php esc_html_e('Heading', 'wc-abp'); ?></th>
									<th><?php esc_html_e('Filter Type', 'wc-abp'); ?></th>
									<th><?php esc_html_e('Choose Attributes', 'wc-abp'); ?></th>
									<th><?php esc_html_e('Show Count?', 'wc-abp'); ?></th>
									<th><?php esc_html_e('Actions', 'wc-abp'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								if ( !empty($attr_filters) ) {
									
									for ( $i = 0; $i < count($attr_filters['type']); $i++ ) {
										?>
										<tr>
											<td><input type="text" name="abp_assorted_product_attribute_filters[heading][]" value="<?php echo isset($attr_filters['heading'][$i]) ? esc_attr($attr_filters['heading'][$i]) : ''; ?>"></td>
											<td>
												<select name="abp_assorted_product_attribute_filters[type][]">
													<option value="dropdown" <?php selected($attr_filters['type'][$i], 'dropdown'); ?>><?php esc_html_e('Select Dropdown Field', 'wc-abp'); ?></option>
													<option value="radio" <?php selected($attr_filters['type'][$i], 'radio'); ?>><?php esc_html_e('Radio Button Fields', 'wc-abp'); ?></option>
													<option value="checkbox" <?php selected($attr_filters['type'][$i], 'checkbox'); ?>><?php esc_html_e('Checkbox Button Fields', 'wc-abp'); ?></option>
													<option value="multiple-dropdown" <?php selected($attr_filters['type'][$i], 'multiple-dropdown'); ?>><?php esc_html_e('Multi-Select Dropdown Field', 'wc-abp'); ?></option>
												</select>
											</td>
											<td>
												<select name="abp_assorted_product_attribute_filters[items][<?php echo esc_attr($i); ?>][]" class="wc-enhanced-select" multiple>
													<?php
													if ( !empty($attr_filter_options) ) {
														foreach ( $attr_filter_options as $key2 => $attr ) {
															$selected = ( !empty($attr_filters['items'][$i]) && in_array($key2, $attr_filters['items'][$i] ) ) ? 'selected' : '';
															echo '<option value="' . esc_attr($key2) . '" ' . esc_attr($selected) . '>' . esc_html($attr) . '</option>';
														}
													}
													?>
												</select>
											</td>
											<td>
												<select name="abp_assorted_product_attribute_filters[show_count][]">
													<option value="no" <?php selected($attr_filters['show_count'][$i], 'no'); ?>><?php esc_html_e('No', 'wc-abp'); ?></option>
													<option value="yes" <?php selected($attr_filters['show_count'][$i], 'yes'); ?>><?php esc_html_e('Yes', 'wc-abp'); ?></option>
												</select>
											</td>
											<td>
												<span class="dashicons dashicons-minus abp_remove_cat_discount"></span>
												<span class="dashicons dashicons-plus-alt2 abp_add_cat_discount"></span>
											</td>
										</tr>
										<?php
									}
								} else {
									?>
									<tr>
										<td>
											<input type="text" name="abp_assorted_product_attribute_filters[heading][]" value="">
										</td>
										<td>
											<select name="abp_assorted_product_attribute_filters[type][]">
												<option value="dropdown"><?php esc_html_e('Select Dropdown Field', 'wc-abp'); ?></option>
												<option value="radio"><?php esc_html_e('Radio Button Fields', 'wc-abp'); ?></option>
												<option value="checkbox"><?php esc_html_e('Checkbox Button Fields', 'wc-abp'); ?></option>
												<option value="multiple-dropdown"><?php esc_html_e('Multi-Select Dropdown Field', 'wc-abp'); ?></option>
											</select>
										</td>
										<td>
											<select name="abp_assorted_product_attribute_filters[items][0][]" class="wc-enhanced-select" multiple>
												<?php
												if ( !empty($tag_options) ) {
													foreach ( $tag_options as $key2 => $tag ) {
														echo '<option value="' . esc_attr($key2) . '">' . esc_html($tag) . '</option>';
													}
												}
												?>
											</select>
										</td>
										<td>
											<select name="abp_assorted_product_attribute_filters[show_count][]">
												<option value="no"><?php esc_html_e('No', 'wc-abp'); ?></option>
												<option value="yes"><?php esc_html_e('Yes', 'wc-abp'); ?></option>
											</select>
										</td>
										<td>
											<span class="dashicons dashicons-minus abp_remove_cat_discount"></span>
											<span class="dashicons dashicons-plus-alt2 abp_add_cat_discount"></span>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="abp_tags_filters wc-metaboxes ui-sortable" data-product_id="<?php echo esc_attr($post_id); ?>">
				<div class="wc-metabox closed abp_categories_discounts">
					<h3 class="">
						<div class="tips sort ui-sortable-handle"></div>
						<div class="handlediv"></div>
						<span class="wccp_box_serial"><?php esc_html_e('Tags Filters Settings', 'wc-abp'); ?></span>
					</h3>
					<div style="display:none;" class="wc-metabox-content">
					<?php
					$abp = get_post_meta($post_id, 'abp_enable_tags_filters', true);
					woocommerce_wp_checkbox(
						array(
						'id'            => 'abp_enable_tags_filters',
						'label'         => esc_html__('Enable Tags Filter', 'wc-abp' ),
						'description'   => esc_html__( 'Enable tags filters on frontend.', 'wc-abp' ),
						'value'		   => $abp
						)
					);
					$tag_filters = get_post_meta($post_id, 'abp_assorted_product_tag_filters', true);
					if ( empty($tag_filters) ) {
						$tag_filters = array();
						$tag_filters['heading'][0] = get_post_meta($post_id, 'abp_assorted_tags_heading', true);
						$tag_filters['type'][0] = get_post_meta($post_id, 'abp_products_tags_filter_type', true);
						$tag_filters['items'][0] = $tags_filter;
						$tag_filters['show_count'][0] = 'no';
					}
					?>
					<table class="form-table">
							<thead>
								<tr>
									<th><?php esc_html_e('Heading', 'wc-abp'); ?></th>
									<th><?php esc_html_e('Filter Type', 'wc-abp'); ?></th>
									<th><?php esc_html_e('Choose Tags', 'wc-abp'); ?></th>
									<th><?php esc_html_e('Show Count?', 'wc-abp'); ?></th>
									<th><?php esc_html_e('Actions', 'wc-abp'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								if ( !empty($tag_filters) ) {
									
									for ( $i = 0; $i < count($tag_filters['type']); $i++ ) {
										?>
										<tr>
											<td><input type="text" name="abp_assorted_product_tag_filters[heading][]" value="<?php echo isset($tag_filters['heading'][$i]) ? esc_attr($tag_filters['heading'][$i]) : ''; ?>"></td>
											<td>
												<select name="abp_assorted_product_tag_filters[type][]">
													<option value="dropdown" <?php selected($tag_filters['type'][$i], 'dropdown'); ?>><?php esc_html_e('Select Dropdown Field', 'wc-abp'); ?></option>
													<option value="radio" <?php selected($tag_filters['type'][$i], 'radio'); ?>><?php esc_html_e('Radio Button Fields', 'wc-abp'); ?></option>
													<option value="checkbox" <?php selected($tag_filters['type'][$i], 'checkbox'); ?>><?php esc_html_e('Checkbox Button Fields', 'wc-abp'); ?></option>
													<option value="multiple-dropdown" <?php selected($tag_filters['type'][$i], 'multiple-dropdown'); ?>><?php esc_html_e('Multi-Select Dropdown Field', 'wc-abp'); ?></option>
												</select>
											</td>
											<td>
												<select name="abp_assorted_product_tag_filters[items][<?php echo esc_attr($i); ?>][]" class="wc-enhanced-select" multiple>
													<?php
													if ( !empty($tag_options) ) {
														foreach ( $tag_options as $key2 => $tag ) {
															$selected = ( !empty($tag_filters['items'][$i]) && in_array($key2, $tag_filters['items'][$i] ) ) ? 'selected' : '';
															echo '<option value="' . esc_attr($key2) . '" ' . esc_attr($selected) . '>' . esc_html($tag) . '</option>';
														}
													}
													?>
												</select>
											</td>
											<td>
												<select name="abp_assorted_product_tag_filters[show_count][]">
													<option value="no" <?php selected($tag_filters['show_count'][$i], 'no'); ?>><?php esc_html_e('No', 'wc-abp'); ?></option>
													<option value="yes" <?php selected($tag_filters['show_count'][$i], 'yes'); ?>><?php esc_html_e('Yes', 'wc-abp'); ?></option>
												</select>
											</td>
											<td>
												<span class="dashicons dashicons-minus abp_remove_cat_discount"></span>
												<span class="dashicons dashicons-plus-alt2 abp_add_cat_discount"></span>
											</td>
										</tr>
										<?php
									}

								} else {
									?>
									<tr>
										<td>
											<input type="text" name="abp_assorted_product_tag_filters[heading][]" value="">
										</td>
										<td>
											<select name="abp_assorted_product_tag_filters[type][]">
												<option value="dropdown"><?php esc_html_e('Select Dropdown Field', 'wc-abp'); ?></option>
												<option value="radio"><?php esc_html_e('Radio Button Fields', 'wc-abp'); ?></option>
												<option value="checkbox"><?php esc_html_e('Checkbox Button Fields', 'wc-abp'); ?></option>
												<option value="multiple-dropdown"><?php esc_html_e('Multi-Select Dropdown Field', 'wc-abp'); ?></option>
											</select>
										</td>
										<td>
											<select name="abp_assorted_product_tag_filters[items][0][]" class="wc-enhanced-select" multiple>
												<?php
												if ( !empty($tag_options) ) {
													foreach ( $tag_options as $key2 => $tag ) {
														echo '<option value="' . esc_attr($key2) . '">' . esc_html($tag) . '</option>';
													}
												}
												?>
											</select>
										</td>
										<td>
											<select name="abp_assorted_product_tag_filters[show_count][]">
												<option value="no"><?php esc_html_e('No', 'wc-abp'); ?></option>
												<option value="yes"><?php esc_html_e('Yes', 'wc-abp'); ?></option>
											</select>
										</td>
										<td>
											<span class="dashicons dashicons-minus abp_remove_cat_discount"></span>
											<span class="dashicons dashicons-plus-alt2 abp_add_cat_discount"></span>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="abp_categories_discounts wc-metaboxes ui-sortable" data-product_id="<?php echo esc_attr($post_id); ?>">
				<div class="wc-metabox closed abp_categories_discounts">
					<h3 class="">
						<div class="tips sort ui-sortable-handle"></div>
						<div class="handlediv"></div>
						<span class="wccp_box_serial"><?php esc_html_e('Categories Based Discounts', 'wc-abp'); ?></span>
					</h3>
					<div style="display:none;" class="wc-metabox-content">
						<?php
						$abp = get_post_meta($post_id, 'abp_enable_categories_discounts', true);
						woocommerce_wp_checkbox(
							array(
							'id'            => 'abp_enable_categories_discounts',
							'label'         => esc_html__('Enable Category Discount', 'wc-abp' ),
							'description'   => esc_html__( 'Enable to apply discounts based on categories for items.', 'wc-abp' ),
							'value'		   => $abp
							)
						);
						$discounts = get_post_meta( $post_id, 'abp_assorted_category_discounts', true );
						?>
						<table class="form-table">
							<thead>
								<tr>
									<th><?php esc_html_e('Choose category', 'wc-abp'); ?></th>
									<th><?php esc_html_e('Enter Number of Items', 'wc-abp'); ?></th>
									<th><?php esc_html_e('Enter Discount Amount', 'wc-abp'); ?></th>
									<th><?php esc_html_e('Choose Discount Type', 'wc-abp'); ?></th>
									<th><?php esc_html_e('Actions', 'wc-abp'); ?></th>
								</tr>
							</thead>
							<tbody>
								<?php
								if ( !empty($discounts) ) {

									foreach ( $discounts as $key => $discount ) {
										?>
										<tr>
											<td>
												<select name="abp_category_discount_cats[]">
													<option value=""><?php esc_html_e('Choose category', 'wc-abp'); ?></option>
													<?php
													if ( !empty($cat_options) ) {
														foreach ( $cat_options as $key2 => $category ) {
															$selected = ( !empty($discount['cats']) && $key2 == $discount['cats'] ) ? 'selected' : '';
															echo '<option value="' . esc_attr($key2) . '" ' . esc_attr($selected) . '>' . esc_html($category) . '</option>';
														}
													}
													?>
												</select>
											</td>
											<td><input type="number" name="abp_category_num_of_items[]" min="0" step="any" value="<?php echo isset($discount['items']) ? esc_attr($discount['items']) : ''; ?>"></td>
											<td><input type="number" name="abp_category_discount_amount[]" min="0" step="any" value="<?php echo isset($discount['amount']) ? esc_attr($discount['amount']) : ''; ?>"></td>
											<td>
												<select name="abp_category_discount_type[]">
													<option value="percent" <?php echo ( !empty($discount['type']) && 'percent' == $discount['type'] ) ? 'selected' : ''; ?>><?php esc_html_e('Percentage', 'wc-abp'); ?></option>
													<option value="fixed" <?php echo ( !empty($discount['type']) && 'fixed' == $discount['type'] ) ? 'selected' : ''; ?>><?php esc_html_e('Fixed', 'wc-abp'); ?></option>
												</select>
											</td>
											<td>
												<span class="dashicons dashicons-minus abp_remove_cat_discount"></span>
												<span class="dashicons dashicons-plus-alt2 abp_add_cat_discount"></span>
											</td>
										</tr>
										<?php
									}
								} else {
									?>
									<tr>
										<td>
											<select name="abp_category_discount_cats[]">
												<option value=""><?php esc_html_e('Choose category', 'wc-abp'); ?></option>
												<?php
												if ( !empty($cat_options) ) {
													foreach ( $cat_options as $key => $category ) {
														echo '<option value="' . esc_attr($key) . '">' . esc_html($category) . '</option>';
													}
												}
												?>
											</select>
										</td>
										<td><input type="number" name="abp_category_num_of_items[]" min="0" step="any"></td>
										<td><input type="number" name="abp_category_discount_amount[]" min="0" step="any"></td>
										<td>
											<select name="abp_category_discount_type[]">
												<option value="percent"><?php esc_html_e('Percentage', 'wc-abp'); ?></option>
												<option value="fixed"><?php esc_html_e('Fixed', 'wc-abp'); ?></option>
											</select>
										</td>
										<td>
											<span class="dashicons dashicons-minus abp_remove_cat_discount"></span>
											<span class="dashicons dashicons-plus-alt2 abp_add_cat_discount"></span>
										</td>
									</tr>
								<?php } ?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
			<div class="abp_quantities_discounts wc-metaboxes ui-sortable" data-product_id="<?php echo esc_attr($post_id); ?>">
				<div class="wc-metabox closed abp_quantities_discounts">
					<h3 class="">
						<div class="tips sort ui-sortable-handle"></div>
						<div class="handlediv"></div>
						<span class="wccp_box_serial"><?php esc_html_e('Items` Quantities Based Discounts', 'wc-abp'); ?></span>
					</h3>
					<div style="display:none;" class="wc-metabox-content">
						<?php
						$abp = get_post_meta($post_id, 'abp_enable_quantities_discounts', true);
						woocommerce_wp_checkbox(
							array(
							'id'            => 'abp_enable_quantities_discounts',
							'label'         => esc_html__('Enable Items Quantities Discount', 'wc-abp' ),
							'description'   => esc_html__( 'Enable to apply discounts based on quantities for items.', 'wc-abp' ),
							'value'		   => $abp
							)
						);
						$abp = get_post_meta($post_id, 'abp_quantities_num_of_items', true);
						woocommerce_wp_text_input(
							array(
								'id'                => 'abp_quantities_num_of_items',
								'label'             => esc_html__( 'Min Number Of Items', 'wc-abp' ),
								'placeholder'       => '',
								'value'			   => $abp,
								'desc_tip'    	   => 'true',
								'description'       => esc_html__( 'Enter minimum number of items to be added for a bundle.', 'wc-abp' ),
								'type'              => 'number'
							)
						);
						$abp = get_post_meta($post_id, 'abp_quantities_discount_amount', true);
						woocommerce_wp_text_input(
							array(
								'id'                => 'abp_quantities_discount_amount',
								'label'             => esc_html__( 'Discount Amount', 'wc-abp' ),
								'placeholder'       => '',
								'value'			   => $abp,
								'desc_tip'    	   => 'true',
								'description'       => esc_html__( 'Enter discount amount.', 'wc-abp' ),
								'type'              => 'number'
							)
						);
						$abp = get_post_meta($post_id, 'abp_quantities_discount_type', true);
						woocommerce_wp_select(
							array(
								'id'                => 'abp_quantities_discount_type',
								'label'             => esc_html__( 'Discount Type', 'wc-abp' ),
								'type'              => 'select',
								'desc_tip'    	   => 'true',
								'description'       => esc_html__( 'Select the discount type.', 'wc-abp' ),
								'options'     => array(
									'percentage'	=>  esc_html__( 'Percentage', 'wc-abp' ),
									'fixed' 	=>	esc_html__( 'Fixed', 'wc-abp' )
								),
								'value'	=> $abp
							)
						);
						?>
					</div>
				</div>
			</div>
			<!-- Per Product Discounts Setting -->
			<div class="abp_per_item_extra_fee wc-metaboxes ui-sortable" data-product_id="<?php echo esc_attr($post_id); ?>">
				<div class="wc-metabox closed cpb_composite_boxes">
					<h3 class="">
						<div class="tips sort ui-sortable-handle"></div>
						<div class="handlediv"></div>
						<span class="wccp_box_serial"><?php esc_html_e('Per Product Exrtra Fee Settings', 'wc-abp'); ?></span>
					</h3>
					<div style="display:none;" class="wc-metabox-content">
						<?php
						$abp = get_post_meta($post_id, 'abp_per_item_extra_fee_enable', true);
						woocommerce_wp_checkbox(
							array(
								'id'            => 'abp_per_item_extra_fee_enable',
								'label'         => esc_html__('Enable Per Extra Item Fee?', 'wc-abp'),
								'description'   => esc_html__('Enable per item extra fee for items.', 'wc-abp'),
								'value'         => $abp
							)
						);
						$restrictions = get_post_meta($post_id, 'abp_per_item_extra_fees', true);
						?>
						<table class="abp_extra_fee_table">
							<tr>
								<th><?php esc_html_e('Choose items', 'wc-abp'); ?></th>
								<th><?php esc_html_e('Extra Fee', 'wc-abp'); ?></th>
								<th><?php esc_html_e('Actions', 'wc-abp'); ?></th>
							</tr>
							<tbody>
							<?php 
							if ( !empty($restrictions) ) {
								$check = true;
								foreach ( $restrictions as $_prod_id => $option ) {
									if ( 'publish' != get_post_status($_prod_id) ) {
										continue;
									}
									$check = false;
									?>
									<tr>
										<td>
											<select data-placeholder="Search for products" data-action="woocommerce_json_search_products_and_variations" data-exclude="cpb_custom_product_boxes" style="width: 50%;" id="abp_extra_fee_item[]" name="abp_extra_fee_item[]" class="wc-product-search">
												<option value="<?php echo esc_attr($_prod_id); ?>" selected="selected"><?php echo esc_html(get_the_title($_prod_id)) . ' - ' . esc_html($_prod_id); ?></option>
											</select>
										</td>
										<td><input type="number" min="0" step="any" value="<?php echo esc_attr($option['fee']); ?>" name="abp_extra_fee[]" class="cpb_restrict_qty"></td>
										<td class="cpb_actions">
											<span><span class="dashicons dashicons-plus-alt2 abp_add_extra_fee"></span></span>
											<span><span class="dashicons dashicons-minus abp_remove_extra_fee"></span></span>
										</td>
									</tr>
									<?php
								}
								if ( $check ) {
									?>
									<tr>
										<td>
											<select data-placeholder="Search for products" data-action="woocommerce_json_search_products_and_variations" data-exclude="cpb_custom_product_boxes" style="width: 50%;" id="abp_extra_fee_item[]" name="abp_extra_fee_item[]" class="wc-product-search">
												<option value="<?php echo esc_attr($_prod_id); ?>" selected="selected"><?php echo esc_html(get_the_title($_prod_id)) . ' - ' . esc_html($_prod_id); ?></option>
											</select>
										</td>
										<td><input type="number" min="0" step="any" value="<?php echo esc_attr($option['fee']); ?>" name="abp_extra_fee[]" class="cpb_restrict_qty"></td>
										<td class="cpb_actions">
											<span><span class="dashicons dashicons-plus-alt2 abp_add_extra_fee"></span></span>
											<span><span class="dashicons dashicons-minus abp_remove_extra_fee"></span></span>
										</td>
									</tr>
									<?php
								}
							} else {
								?>
								<tr>
									<td>
										<select data-placeholder="Search for products" data-action="woocommerce_json_search_products_and_variations" data-exclude="cpb_custom_product_boxes" style="width: 50%;" id="abp_extra_fee_item[]" name="abp_extra_fee_item[]" class="wc-product-search"></select>
									</td>
									<td><input type="number" min="0" step="any" value="" name="abp_extra_fee[]" class="cpb_restrict_qty"></td>
									<td class="cpb_actions">
										<span><span class="dashicons dashicons-plus-alt2 abp_add_extra_fee"></span></span>
										<span><span class="dashicons dashicons-minus abp_remove_extra_fee"></span></span>
									</td>
								</tr>
							<?php } ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			<div id="abp_assorted_extra_options" class="panel woocommerce_options_panel">
			<?php
			$abp = get_post_meta($post_id, 'abp_enable_assorted_gift_message', true);
			woocommerce_wp_checkbox(
				array(
				'id'            => 'abp_enable_assorted_gift_message',
				'label'         => esc_html__('Enable Message Field', 'wc-abp' ),
				'description'   => esc_html__( 'Enable a text field to collect necessary information.', 'wc-abp' ),
				'value'		   => $abp
			));
			$abp = get_post_meta($post_id, 'abp_assorted_message_field_label', true);
			woocommerce_wp_text_input(
				array(
				'id'                => 'abp_assorted_message_field_label',
				'label'             => esc_html__( 'Field Label', 'wc-abp' ),
				'placeholder'       => '',
				'value'			   => ( !empty($abp) ? $abp : esc_html__('Message', 'wc-abp') ),
				'desc_tip'    	   => 'true',
				'description'       => esc_html__( 'Enter field Label', 'wc-abp' )
			));
			$abp = get_post_meta($post_id, 'abp_enable_assorted_gift_field_type', true);
			woocommerce_wp_select(
				array(
				'id'                => 'abp_enable_assorted_gift_field_type',
				'label'             => esc_html__( 'Field Type', 'wc-abp' ),
				'desc_tip'    	   => 'true',
				'description'       => esc_html__( 'Select field type.', 'wc-abp' ),
				'type'              => 'select',
				'options'     => array(
						'text' =>	esc_html__( 'Text Field', 'wc-abp' ),
						'textarea' => esc_html__( 'Textarea Field', 'wc-abp' )
						),
				'value'			   => $abp
			));
			$abp = get_post_meta($post_id, 'abp_enable_assorted_gift_required', true);
			woocommerce_wp_checkbox(
				array(
				'id'            => 'abp_enable_assorted_gift_required',
				'label'         => esc_html__('Field requried?', 'wc-abp' ),
				'description'   => esc_html__( 'Enable a text field to be required.', 'wc-abp' ),
				'value'		   => $abp
			));
			echo '</div>';
		}
		public static function abp_disable_search_products ( $status ) {
			if ( !empty($_GET['exclude']) && 'assorted_product' == $_GET['exclude'] && !empty($_GET['term']) ) {
				$status = 0;
			}
			return $status;
		}
		public static function abp_search_found_products ( $products ) {
			if ( !empty($_GET['exclude']) && 'assorted_product' == $_GET['exclude'] && !empty($_GET['term']) ) {
				$args=array(
					'post_type'             => array('product', 'product_variation'),
					'post_status'           => 'publish',
					'posts_per_page'        => '-1',
					's'	=>	sanitize_text_field($_GET['term']),
					'tax_query' => array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => array( 'variable', 'assorted_product', 'subscription', 'variable-subscription'),
						'operator' => 'NOT IN',
					)
					)		    
					);
				$args = apply_filters('abp_assorted_product_items_search_args', $args);
				$arr = array();
				$_products = new WP_Query($args);
				if ( $_products->have_posts() ) {
					while ( $_products->have_posts() ) : 
						$_products->the_post();
						$product_id=get_the_ID();
						$arr[$product_id] = get_the_title() . ' &ndash; ' . $product_id;
					endwhile;
				}
				$products=$arr;
			}
			if ( !empty($_GET['exclude']) && 'only_assorted_products' == $_GET['exclude'] && !empty($_GET['term']) ) {
				$args = array(
					'post_type'             => array('product'),
					'post_status'           => 'publish',
					'posts_per_page'        => '-1',
					's'	=>	sanitize_text_field($_GET['term']),
					'tax_query' => array(
					array(
						'taxonomy' => 'product_type',
						'field'    => 'slug',
						'terms'    => array('assorted_product'),
						'operator' => 'IN',
					)
					),
					'meta_query' => array(
						array(
						'key' => 'abp_assorted_subscription_enable',
						'value'    => 'yes',
						'compare'  => '='
						)
					)	
				);
				$args = apply_filters('abp_assorted_product_items_search_args', $args);
				$arr = array();
				$_products = new WP_Query($args);
				if ( $_products->have_posts() ) {
					while ( $_products->have_posts() ) : 
						$_products->the_post();
						$product_id=get_the_ID();
						$arr[$product_id] = get_the_title() . ' &ndash; ' . $product_id;
					endwhile;
				}
				$products = $arr;
			}
			return $products;
		}
		/**
		 * Save Assorted products meta
		 * 
		 * @since 1.0.0
		 */
		public static function abp_save_product_box_options_field( $post_id ) {
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

			if ( isset( $_POST['abp_pricing_type'] ) ) {
				update_post_meta($post_id, 'abp_pricing_type', sanitize_text_field($_POST['abp_pricing_type']));
			}
			if ( isset($_POST['abp_assorted_products_layout']) ) {
				update_post_meta($post_id, 'abp_assorted_products_layout', sanitize_text_field($_POST['abp_assorted_products_layout']));
			}
			$min = 1;
			$max = 1;
			if ( isset($_POST['abp_assorted_min_products']) && is_numeric($_POST['abp_assorted_min_products']) && $_POST['abp_assorted_min_products']>=0 ) {
				$min = wc_clean($_POST['abp_assorted_min_products']);
			}
			if ( isset($_POST['abp_assorted_max_products']) && is_numeric($_POST['abp_assorted_max_products']) && $_POST['abp_assorted_max_products']>=0 ) {
				$max=wc_clean($_POST['abp_assorted_max_products']);
			}
			//min & max
			if ( $min>0 && $max>0 && $min<=$max ) {
				update_post_meta($post_id, 'abp_assorted_min_products', sanitize_text_field($min));
				update_post_meta($post_id, 'abp_assorted_max_products', sanitize_text_field($max));
			}
			if ( isset($_POST['abp_assorted_columns']) && is_numeric($_POST['abp_assorted_columns']) && $_POST['abp_assorted_columns']>=0 ) {
				update_post_meta($post_id, 'abp_assorted_columns', sanitize_text_field($_POST['abp_assorted_columns']));
			} 
			
			if ( isset($_POST['abp_products_categories_enabled']) ) {
				update_post_meta( $post_id, 'abp_products_categories_enabled', wc_clean($_POST['abp_products_categories_enabled']));
			} else {
				update_post_meta( $post_id, 'abp_products_categories_enabled', '');
			}
			if ( isset($_POST['abp_products_tags_enabled']) ) {
				update_post_meta( $post_id, 'abp_products_tags_enabled', wc_clean($_POST['abp_products_tags_enabled']));
			} else {
				update_post_meta( $post_id, 'abp_products_tags_enabled', '');
			}
			if ( isset($_POST['abp_products_items_enabled']) ) {
				update_post_meta( $post_id, 'abp_products_items_enabled', array_map( 'intval', (array) wp_unslash( $_POST['abp_products_items_enabled'] )));
			} else {
				update_post_meta( $post_id, 'abp_products_items_enabled', '');
			}

			if ( isset($_POST['abp_complete_store_available']) ) {
				update_post_meta($post_id, 'abp_complete_store_available', 'yes');
			} else {
				update_post_meta($post_id, 'abp_complete_store_available', 'no');
			}
			if ( isset($_POST['abp_allow_tax_for_bundle'])) {
				update_post_meta($post_id, 'abp_allow_tax_for_bundle', 'yes');
			} else {
				update_post_meta($post_id, 'abp_allow_tax_for_bundle', 'no');
			}
			if ( isset($_POST['abp_per_item_shipping']) ) {
				update_post_meta($post_id, 'abp_per_item_shipping', 'yes');
			} else {
				update_post_meta($post_id, 'abp_per_item_shipping', 'no');
			}
			if ( isset($_POST['abp_assorted_product_individually']) ) {
				update_post_meta($post_id, 'abp_assorted_product_individually', 'yes');
			} else {
				update_post_meta($post_id, 'abp_assorted_product_individually', 'no');
			}
			if ( isset($_POST['abp_assorted_product_hide_unpurchasable']) ) {
				update_post_meta($post_id, 'abp_assorted_product_hide_unpurchasable', 'yes');
			} else {
				update_post_meta($post_id, 'abp_assorted_product_hide_unpurchasable', 'no');
			}
			if ( isset($_POST['abp_assorted_product_show_sku']) ) {
				update_post_meta($post_id, 'abp_assorted_product_show_sku', 'yes');
			} else {
				update_post_meta($post_id, 'abp_assorted_product_show_sku', 'no');
			}
			if ( isset($_POST['abp_enable_assorted_gift_message']) ) {
				update_post_meta($post_id, 'abp_enable_assorted_gift_message', 'yes');
			} else {
				update_post_meta($post_id, 'abp_enable_assorted_gift_message', 'no');
			}
			if ( isset($_POST['abp_assorted_message_field_label']) ) {
				update_post_meta($post_id, 'abp_assorted_message_field_label', wc_clean($_POST['abp_assorted_message_field_label']));
			}
			if ( isset($_POST['abp_enable_assorted_gift_required']) ) {
				update_post_meta($post_id, 'abp_enable_assorted_gift_required', 'yes');
			} else {
				update_post_meta($post_id, 'abp_enable_assorted_gift_required', 'no');
			}
			if ( isset($_POST['abp_enable_assorted_gift_field_type']) ) {
				update_post_meta($post_id, 'abp_enable_assorted_gift_field_type', wc_clean($_POST['abp_enable_assorted_gift_field_type']));
			}

			if ( isset($_POST['abp_enable_categories_filters']) ) {
				update_post_meta($post_id, 'abp_enable_categories_filters', 'yes');
			} else {
				update_post_meta($post_id, 'abp_enable_categories_filters', 'no');
			}
			if ( isset($_POST['abp_assorted_cats_heading']) ) {
				update_post_meta($post_id, 'abp_assorted_cats_heading', wc_clean($_POST['abp_assorted_cats_heading']));
			}
			if ( isset($_POST['abp_products_filter_type']) ) {
				update_post_meta($post_id, 'abp_products_filter_type', wc_clean($_POST['abp_products_filter_type']));
			}
			if ( isset($_POST['abp_assorted_categories_filter']) ) {
				update_post_meta($post_id, 'abp_assorted_categories_filter', wc_clean($_POST['abp_assorted_categories_filter']));
			} else {
				update_post_meta($post_id, 'abp_assorted_categories_filter', '');
			}
			if ( isset($_POST['abp_assorted_cats_show_count']) ) {
				update_post_meta($post_id, 'abp_assorted_cats_show_count', 'yes');
			} else {
				update_post_meta($post_id, 'abp_assorted_cats_show_count', 'no');
			}
			if ( isset($_POST['abp_assorted_cats_show_child']) ) {
				update_post_meta($post_id, 'abp_assorted_cats_show_child', 'yes');
			} else {
				update_post_meta($post_id, 'abp_assorted_cats_show_child', 'no');
			}

			if ( isset($_POST['abp_assorted_enable_attribute_filter']) ) {
				update_post_meta($post_id, 'abp_assorted_enable_attribute_filter', 'yes');
			} else {
				update_post_meta($post_id, 'abp_assorted_enable_attribute_filter', 'no');
			}
			if ( isset($_POST['abp_assorted_attribute_hide_empty']) ) {
				update_post_meta($post_id, 'abp_assorted_attribute_hide_empty', 'yes');
			} else {
				update_post_meta($post_id, 'abp_assorted_attribute_hide_empty', 'no');
			} 
			if ( isset($_POST['abp_assorted_product_attribute_filters']) ) {
				update_post_meta($post_id, 'abp_assorted_product_attribute_filters', wc_clean($_POST['abp_assorted_product_attribute_filters']));
			} else {
				update_post_meta($post_id, 'abp_assorted_product_attribute_filters', '');
			}

			if ( isset($_POST['abp_enable_tags_filters']) ) {
				update_post_meta($post_id, 'abp_enable_tags_filters', 'yes');
			} else {
				update_post_meta($post_id, 'abp_enable_tags_filters', 'no');
			}
			if ( isset($_POST['abp_assorted_product_tag_filters']) ) {
				update_post_meta($post_id, 'abp_assorted_product_tag_filters', wc_clean($_POST['abp_assorted_product_tag_filters']));
			} else {
				update_post_meta($post_id, 'abp_assorted_product_tag_filters', '');
			}
			
			if ( isset($_POST['abp_per_item_extra_fee_enable']) ) {
				update_post_meta($post_id, 'abp_per_item_extra_fee_enable', 'yes');
			} else {
				update_post_meta($post_id, 'abp_per_item_extra_fee_enable', 'no');
			}
			if ( !empty($_POST['abp_extra_fee_item']) ) {
				$extra_fee = array();
				for ( $i=0; $i<count($_POST['abp_extra_fee_item']); $i++ ) {
					$fee = isset($_POST['abp_extra_fee'][$i]) ? wc_clean($_POST['abp_extra_fee'][$i]) : '';
					$item = isset($_POST['abp_extra_fee_item'][$i]) ? wc_clean($_POST['abp_extra_fee_item'][$i]) : '';
					$extra_fee[$item] = array(
						'fee' => $fee
					);
				}
				update_post_meta($post_id, 'abp_per_item_extra_fees', $extra_fee);
			}

			if ( isset($_POST['abp_enable_categories_discounts']) ) {
				update_post_meta($post_id, 'abp_enable_categories_discounts', 'yes');
			} else {
				update_post_meta($post_id, 'abp_enable_categories_discounts', 'no');
			}
			$discounts = array();
			if ( isset($_POST['abp_category_discount_type']) ) {
				for ( $i = 0; $i < count( wc_clean($_POST['abp_category_discount_type']) ); $i++ ) {
					$discounts[$i]['cats'] = isset($_POST['abp_category_discount_cats'][$i]) ? wc_clean( $_POST['abp_category_discount_cats'][$i] ) : '';
					$discounts[$i]['items'] = isset($_POST['abp_category_num_of_items'][$i]) ? wc_clean( $_POST['abp_category_num_of_items'][$i] ) : '';
					$discounts[$i]['amount'] = isset($_POST['abp_category_discount_amount'][$i]) ? wc_clean( $_POST['abp_category_discount_amount'][$i] ) : '';
					$discounts[$i]['type'] = isset($_POST['abp_category_discount_type'][$i]) ? wc_clean( $_POST['abp_category_discount_type'][$i] ) : '';
				}
			}
			update_post_meta($post_id, 'abp_assorted_category_discounts', $discounts);
			$discounts = array();
			if ( isset($_POST['abp_enable_quantities_discounts']) ) {
				update_post_meta($post_id, 'abp_enable_quantities_discounts', 'yes');
			} else {
				update_post_meta($post_id, 'abp_enable_quantities_discounts', 'no');
			}
			if ( isset($_POST['abp_quantities_num_of_items']) ) {
				$discounts['items'] = wc_clean($_POST['abp_quantities_num_of_items']);
				update_post_meta($post_id, 'abp_quantities_num_of_items', wc_clean($_POST['abp_quantities_num_of_items']));
			}
			if ( isset($_POST['abp_quantities_discount_amount']) ) {
				$discounts['amount'] = wc_clean($_POST['abp_quantities_discount_amount']);
				update_post_meta($post_id, 'abp_quantities_discount_amount', wc_clean($_POST['abp_quantities_discount_amount']));
			}
			if ( isset($_POST['abp_quantities_discount_type']) ) {
				$discounts['type'] = wc_clean($_POST['abp_quantities_discount_type']);
				update_post_meta($post_id, 'abp_quantities_discount_type', wc_clean($_POST['abp_quantities_discount_type']));
			}
			update_post_meta($post_id, 'abp_assorted_quantities_discounts', $discounts);
		}
		public static function abp_enqueue_scripts() {
			wp_enqueue_script( 'abp-admin-script', WC_ABP_URL . '/assets/js/backend_script.js', array('jquery'), '1.0.9' );
			wp_localize_script( 'abp-admin-script', 'abpAssorted', array(
				'ajaxurl'		=>	admin_url('admin-ajax.php'),
				'ajax_nonce' => wp_create_nonce('assorted_bundle')
			) );
			wp_enqueue_style( 'abp-admin-style', WC_ABP_URL . '/assets/css/backend_style.css', '', '1.0.9' );
		}
	}
	new ABP_Assorted_Product_Backend();
}
