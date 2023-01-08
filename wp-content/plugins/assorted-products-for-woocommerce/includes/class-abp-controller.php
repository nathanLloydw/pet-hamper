<?php
if ( !defined('ABSPATH') ) {
	exit(); // Exit if accessed directly.
}
if ( !class_exists('ABP_Assorted_Products_Controller') ) {
	class ABP_Assorted_Products_Controller {
		public function __construct() {
			add_action( 'wp_ajax_abp_assorted_search_products', array($this, 'abp_assorted_search_products'));
			add_action( 'wp_ajax_nopriv_abp_assorted_search_products', array($this, 'abp_assorted_search_products'));
			add_action( 'wp_ajax_abp_assorted_quick_view', array($this, 'abp_assorted_quick_view'));
			add_action( 'wp_ajax_nopriv_abp_assorted_quick_view', array($this, 'abp_assorted_quick_view'));
		}
		public function abp_assorted_search_products() {
			check_ajax_referer( 'assorted_bundle', 'security' );
			if ( !isset($_POST['product_id']) ) {
				wp_send_json_error(esc_html__('Something is wrong please try again.', 'wc-abp'));
			}
			$product_id = absint($_POST['product_id']);
			$filter_term = '';
			$filter_cat = '';
			$filter_tag = '';
			$filter_atts = array();
			$filters = false;
			
			if ( isset($_POST['search_filters']['category']) ) {
				$filters = true;
				$filter_cat = wc_clean($_POST['search_filters']['category']);
			}
			if ( !empty($_POST['search_filters']['attributes']) ) {
				$filter_atts = (array) wc_clean($_POST['search_filters']['attributes']);
			}
			if ( isset($_POST['search_filters']['tag']) ) {
				$filters = true;
				$filter_tag = wc_clean($_POST['search_filters']['tag']);
			}
			if ( isset($_POST['search_filters']['search']) ) {
				$filter_term = sanitize_text_field($_POST['search_filters']['search']);
			}
			$all = get_post_meta($product_id, 'abp_complete_store_available', true);
			$count = get_option('abp_assorted_products_per_page');
			$count = empty($count) ? 12 : $count;
			$paged = 1;
			if ( isset($_POST['paged']) && is_numeric($_POST['paged']) ) {
				$paged = absint($_POST['paged']);
			}
			$tax_query = array();
			$relation = array();
			$attribute_meta_query = array();
			if ( !empty( $filter_atts ) && null != $filter_atts ) {
				$filters = true;
				$tax_query['relation'] = 'AND';
				$attribute_meta_query['relation'] = 'AND';
				foreach ( $filter_atts as $key => $att ) {
					$att_term = get_term_by('term_id', $att[1], $att[0]);
					$attribute_meta_query[] = array(
						'key' => 'attribute_' . $att[0],
						'value' => $att_term->slug
					);
					$tax_query[] = array(
						'taxonomy' => $att[0],
						'field'    => 'term_id',
						'terms'    => $att[1],
						'operator' => 'IN',
					);
				}
			}
			if ( 'yes' !== $all ) {
				$post_ids = array();
				$variation_ids = array();
				$cat_ids = get_post_meta($product_id, 'abp_products_categories_enabled', true);
				
				if ( !empty($filter_cat) && !empty($cat_ids) ) {
					$filter_cat = array_intersect($filter_cat, $cat_ids);
					if ( empty($filter_cat) ) {
						$filter_cat = array(0);
					}
				}
				
				$products_ids = get_post_meta($product_id, 'abp_products_items_enabled', true);
				if ( !empty($products_ids) ) {
					$post_ids = array_merge($post_ids, $products_ids);
				}
				$tag_ids = get_post_meta($product_id, 'abp_products_tags_enabled', true);
				if ( empty($filter_cat) ) {
					$cat_ids = get_post_meta($product_id, 'abp_products_categories_enabled', true);
				}
				if ( empty($filter_tag) ) {
					$tag_ids = get_post_meta($product_id, 'abp_products_tags_enabled', true);
				}
				if ( !empty($filter_cat) ) {
					$cat_ids = $filter_cat;
				}
				if ( !empty($filter_tag) ) {
					$tag_ids = $filter_tag;
				}
				if ( !empty($cat_ids) || !empty($tag_ids) ) {
					$tag_cats_tax = array();
					if ( $filters ) {
						$tag_cats_tax['relation'] = 'AND';
					} else {
						$tag_cats_tax['relation'] = 'OR';
					}
					if ( !empty($cat_ids) ) {
						$tag_cats_tax[] = array(
							'taxonomy' => 'product_cat',
							'field' => 'term_id',
							'terms'    => $cat_ids
						);
					}
					if ( !empty($tag_ids) ) {
						$tag_cats_tax[] = array(
							'taxonomy' => 'product_tag',
							'field' => 'term_id',
							'terms'    => $tag_ids
						);
					}
					
					$args = array(
						'post_type'	=> array('product'),
						'posts_per_page'=> -1,
						'post_status'   => 'publish',
						'fields' => 'ids',
						'tax_query' => $tag_cats_tax
					);
					$posts = get_posts( $args );
					
					if ( !empty($posts) ) {
						$args = array(
							'post_type'	=> array('product'),
							'posts_per_page'=> -1,
							'post_status'   => 'publish',
							'fields' => 'ids',
							'post__in'		=> $posts,
							'tax_query' => $tax_query 
						);
						$posts = get_posts( $args );
					}
					if ( !empty($posts) ) {
						$post_ids = $posts;
						$args = array(
							'post_type'      => array('product_variation'),
							'posts_per_page' => -1,
							'post_status'    => 'publish',
							'fields'		 => 'ids',
							'post_parent__in' => $posts
						);
						$variation_ids = get_posts($args);
					}
				}
				if ( !empty($attribute_meta_query) ) {
					$args = array(
						'post_status' => 'publish',
						'post_type' => array('product_variation'),
						'posts_per_page' => -1,
						'fields' => 'ids',
						'meta_query' => $attribute_meta_query,
					);
					if ( !empty($post_ids) ) {
						$args['post_parent__in'] = $post_ids;
					}
					$temp_variation_ids = get_posts($args);
					if ( !empty($variation_ids) && !empty($temp_variation_ids) ) {
						$variation_ids = array_intersect($variation_ids, $temp_variation_ids);
					} else {
						$variation_ids = array_merge($variation_ids, $temp_variation_ids);
					}
				}
				if ( !empty($variation_ids) ) {
					$post_ids = array_merge($variation_ids, $post_ids);
				}
				
				if ( empty($post_ids) ) {
					$post_ids = array(0);
				}
				
				$args = array (
					'post_type'	=> array('product', 'product_variation'),
					'posts_per_page'=> $count,
					'paged'			=> $paged,
					'post_status'   => 'publish',
					'post__in'		=> $post_ids,
					'orderby' => 'title',
					'order' => 'ASC',
					'tax_query' => array(
						'relation' => 'OR',
						array(
							'taxonomy' => 'product_type',
							'field'    => 'slug',
							'terms'    => array('simple'),
							'operator' => 'IN',
						),
						array(
							'taxonomy' => 'product_type',
							'operator' => 'NOT EXISTS',
						)
					)
				);
			} else {
				$args = array(
					'post_type'	=> array('product', 'product_variation'),
					'posts_per_page' => $count,
					'paged'			 => $paged,
					'post_status'    => 'publish',
					'order' => 'ASC',
					'orderby' => 'title',
					'tax_query' => array(
						'relation' => 'OR',
						array(
							'taxonomy' => 'product_type',
							'field'    => 'slug',
							'terms'    => array('simple'),
							'operator' => 'IN',
						),
						array(
							'taxonomy' => 'product_type',
							'operator' => 'NOT EXISTS',
						)
					)
				);
				$posts_in = array();
				if ( !empty($filter_cat) ) {
					$post_ids = get_posts(
						array(
							'post_type'	=> array('product'),
							'posts_per_page'=> -1,
							'post_status'   => 'publish',
							'fields' => 'ids',
							'tax_query' => array(
								array(
									'taxonomy' => 'product_cat',
									'field' => 'term_id',
									'terms'    => $filter_cat
								)
							)
						)
					);
					if ( !empty($post_ids) ) {
						$variation_ids=get_posts(array(
							'post_type'      => array('product_variation'),
							'posts_per_page' => -1,
							'post_status'    => 'publish',
							'fields'		 => 'ids',
							'post_parent__in' => $post_ids
						));
						$post_ids = array_merge($post_ids, $variation_ids);
						$posts_in = array_merge($posts_in, $post_ids);
					}
				}
				if ( !empty($filter_tag) ) {
					$post_ids = get_posts(
						array(
							'post_type'	=> array('product'),
							'posts_per_page'=> -1,
							'post_status'   => 'publish',
							'fields' => 'ids',
							'tax_query' => array(
								array(
									'taxonomy' => 'product_tag',
									'field' => 'term_id',
									'terms'    => $filter_tag
								)
							)
						)
					);
					if ( !empty($post_ids) ) {
						$variation_ids = get_posts(array(
							'post_type'      => array('product_variation'),
							'posts_per_page' => -1,
							'post_status'    => 'publish',
							'fields'		 => 'ids',
							'post_parent__in' => $post_ids
						));
						$post_ids = array_merge($post_ids, $variation_ids);
						$posts_in = array_merge($posts_in, $post_ids);
					}
				}
				if ( !empty($attribute_meta_query) ) {
					$args = array(
						'post_status' => 'publish',
						'post_type' => array('product_variation'),
						'posts_per_page' => -1,
						'fields' => 'ids',
						'meta_query' => $attribute_meta_query
					);
					$variation_ids = get_posts($args);
					$post_ids = array_intersect($variation_ids, $post_ids);
					$args = array(
						'post_type'	=> array('product'),
						'posts_per_page'=> -1,
						'post_status'   => 'publish',
						'fields' => 'ids',
						'tax_query' => array( $tax_query )
					);
					$products_ids = get_posts($args);
					$post_ids = array_merge($post_ids, $products_ids);
				}
				$args['post__in'] = $posts_in;
			}
			if ( !empty($filter_term) ) {
				$args['s'] = $filter_term;
			}
			$html = '';
			$query = new WP_Query($args);
			$html = $this->abp_default_template($product_id, $query, $paged);
			wp_send_json_success(array('success'=> true, 'html' => $html, 'paged' => $paged) );
		}
		public function abp_default_template( $product_id, $query, $paged ) {
			$cols = get_post_meta($product_id, 'abp_assorted_columns', true);
			$hide_unpurchasable = get_post_meta($product_id, 'abp_assorted_product_hide_unpurchasable', true);
			$show_sku = get_post_meta($product_id, 'abp_assorted_product_show_sku', true);
			$sku_title = get_option('abp_assorted_product_show_sku_text');
			$btn_text = get_option('abp_assorted_products_item_btn_text');
			$item_desc = get_option('abp_assorted_items_show_description');
			$btn_text = !empty($btn_text) ? esc_html__($btn_text, 'wc-abp') : esc_html__('Add to bundle', 'wc-abp');
			$item_btn_text = get_option('abp_assorted_products_readmore_item');
			$item_btn_text = !empty($item_btn_text) ? esc_html__($item_btn_text, 'wc-abp') : esc_html__('Read More', 'wc-abp');
			if ( $query->have_posts() ) {
				$html = '';
				ob_start();
				do_action('abp_before_product_items_loop');
				$html .= ob_get_clean();
				if ( 1 === $paged ) {
					$html .= '<ul class="abp_assorted_row">';
				}
				while ( $query->have_posts() ) {
					$query->the_post();
					$_product = wc_get_product(get_the_ID());
					$item_id = ( 'product_variation' == get_post_type( get_the_ID() ) ) ? $_product->get_parent_id() : $_product->get_id();
					$term_list = wp_get_post_terms( $item_id, 'product_cat', array( 'fields' => 'ids' ) );
					$tags_list = wp_get_post_terms( $item_id, 'product_tag', array( 'fields' => 'ids' ) );
					$options['id'] = $_product->get_id();
					$options['price'] = $_product->get_price();
					$options['title'] = $_product->get_name();
					$options['purchaseable'] = ( $_product->is_purchasable() && $_product->is_in_stock() ) ? 1 : 0;
					$options['cats'] = $term_list;
					$options['tags'] = $tags_list;
					$options['qty'] = ( $_product->is_sold_individually() ) ? 1 : $_product->get_stock_quantity();
					if ( 0 == $options['purchaseable'] && 'yes' == $hide_unpurchasable ) {
						continue;
					}
					$html .= '<li class="abp-col-' . $cols . '" data-product-id="' . esc_attr($_product->get_id()) . '" data-categories="' . esc_attr( implode(',', $term_list) ) . '" data-tags="' . esc_attr( implode(',', $tags_list) ) . '">';
					$html .= '<div class="abp-inner">';
					$html .= '<div class="abp-figure">';
					$html .= $_product->get_image();
					$html .= '</div>';
					$html .= '<div class="abp-captions">';
					$html .= '<span class="apb-title"><a href="' . esc_url(get_the_permalink($_product->get_id())) . '" target="_blank"><strong>' . esc_html($_product->get_name()) . '</strong></a></span>';
					if ( 'yes' == $item_desc ) {
						$html .= '<span class="apb-item-excerpt apb-short-description">' . wp_kses_post( get_the_excerpt() ) . '</span>';
					}
					$html .= '<span class="abp_assorted_item_price">' . wp_kses_post( $_product->get_price_html() ) . '</span>';
					if ( 'yes' == $show_sku && !empty($_product->get_sku()) ) {
						$html .= '<span class="abp_item_sku"><span class="abp_sku_title">' . esc_html($sku_title) . ' </span><span class="abp_sku_code">' . esc_html($_product->get_sku()) . '</span></span>';
					}
					if ($options['purchaseable']) {
						$html .= '<span class="abp_button"><button class="button add-product-to-assorted" type="button" data-product-id="' . esc_attr($_product->get_id()) . '">' . esc_html__($btn_text, 'wc-abp') . '</button></span>';
					} else {
						$html .= '<span class="abp_button"><button class="button" type="button" data-product-id="' . esc_attr($_product->get_id()) . '" disabled="disabled">' . esc_html__($item_btn_text, 'wc-abp') . '</button></span>';
					}
					$html .= '<input type="hidden" name="abp_bundle_item_meta" class="abp_bundle_item_meta" value="' . esc_attr(json_encode($options)) . '">';
					$html .= '</di>';
					$html .= '</div>';
					$html .= '</li>';
				}
				wp_reset_postdata();
				if ( 1 === $paged ) {
					$html .= '</ul>';
				}
				$btn_text = get_option('abp_assorted_products_loadmore_text');
				$btn_text = !empty($btn_text) ? esc_html__($btn_text, 'wc-abp') : esc_html__('Load More', 'wc-abp');
				if ( $query->max_num_pages>$paged && 1=== $paged ) {
					$html .= '<div class="abp_products_footer"><button type="button" class="button" id="abp-load-more-btn" data-max="' . esc_attr($query->max_num_pages) . '" data-paged="' . esc_attr($paged) . '">' . esc_html( $btn_text ) . '</button><div>';
				}
				ob_start();
				do_action('abp_after_product_items_loop');
				$html .= ob_get_clean();
			} else {
				$html = '';
				ob_start();
				do_action('abp_before_product_items_loop');
				$html .= ob_get_clean();
				$html .= esc_html__('No results found please try again!', 'wc-abp');
				ob_start();
				do_action('abp_after_product_items_loop');
				$html .= ob_get_clean();
			}
			return $html;
		}
		public function abp_assorted_quick_view() {
			check_ajax_referer( 'assorted_bundle', 'security' );
			if ( !isset($_POST['product_id']) ) {
				wp_send_json_error(esc_html__('Something is wrong please try again.', 'wc-abp'));
			}
			$product_id= absint($_POST['product_id']);
			if ( 'publish' != get_post_status($product_id) ) {
				$html = esc_html__('Product can not be found!', 'wc-abp');
				wp_send_json_success(array('success'=> true, 'html' => $html) );
			}
			$product = wc_get_product($product_id);
			$args = array(
				'post_type'      => array('product', 'product_variation'),
				'post__in'       => array($product_id),
				'post_status'    => 'publish',
				'posts_per_page' => 1
			);           
			$product_query = new WP_Query( $args );
			ob_start();
			?>
			<div class="abp_boxes_item_data">
				<div id="product-<?php echo esc_attr($product_id); ?>" <?php wc_product_class( '', $product ); ?>>
					<div class="woocommerce-product-gallery woocommerce-product-gallery--with-images images">
						<figure class="woocommerce-product-gallery__wrapper">
						<?php 
						$attachment_ids = $product->get_gallery_image_ids();
						array_unshift($attachment_ids, $product->get_image_id());
						if ( !empty($attachment_ids) ) {
							echo '<div class="abp-assorted-carousel">';
							foreach ( $attachment_ids as $attachment_id ) {
								echo '<div class="abp-assorted-slide">' . wp_get_attachment_image($attachment_id, 'full') . '</div>';
							}
							echo '</div>';
						}
						?>
						</figure>
					</div>
					<?php 
					while ( $product_query->have_posts() ) { 
						$product_query->the_post();
						?>
						<div class="summary entry-summary">
							<?php do_action( 'woocommerce_single_product_summary' ); ?>
						</div>
						<?php 
					} 
					wp_reset_postdata();
					?>
				</div>
			</div>
			<?php
			$html = ob_get_clean();
			
			wp_send_json_success( array('success'=> true, 'html' => $html) );
		}
	}
	new ABP_Assorted_Products_Controller();
}
