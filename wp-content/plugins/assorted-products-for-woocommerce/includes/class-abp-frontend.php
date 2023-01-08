<?php
if ( !defined('ABSPATH') ) {
    exit; // Exit if accessed directly.
}
if ( !class_exists('ABP_Assorted_Product_Frontend') ) {
    class ABP_Assorted_Product_Frontend {
        public function __construct() {
            add_filter('wc_get_template_part', array($this, 'abp_custom_product_template'), 100, 3);
            add_filter('template_include', array($this, 'abp_template_loader' ), 999, 1 );
            add_action('abp_assorted_products_layout', array($this, 'abp_product_page'), 10, 1 );
            add_action('abp_assorted_product_filters_layout', array($this, 'abp_product_filters_layout'), 10, 1);
            add_action('abp_assorted_product_checkout_layout', array($this, 'abp_product_checkout_layout'), 10, 1);
            add_action('abp_assorted_product_items_layout', array($this, 'abp_product_items_layout'), 10, 1 );
            add_action('wp_enqueue_scripts', array($this, 'abp_enqueue_front_scripts'));
            add_action('wp_footer', array($this, 'abp_error_message'));
        }
        public function abp_template_loader( $template ) {
            if ( is_singular('product') ) {
                $product = wc_get_product(get_the_ID());
                if ( 'assorted_product' === $product->get_type() && 'yes' == get_option('abp_assorted_load_template') ) {
                    $template = WC()->plugin_path() . '/templates/single-product.php';
                }
            }
            return $template;
        }

        public function abp_custom_product_template( $template, $slug, $name ) {
            global $product;
            if ( is_singular('product') && ( 'single-product' === $name || 'single-product-custom' === $name ) && 'content' === $slug && 'assorted_product' === $product->get_type() ) {
                $template = WC_ABP_DIR . '/templates/content-single-product.php';
            }
            return $template;
        }

        public function abp_product_page( $product_id ) {
            $product=wc_get_product($product_id);
            ?>
            <div id="abp_custom_assorted_product" class="abp_custom_assorted_product">
                <?php
                if ( 'before_title' == get_option('abp_assorted_products_description_position') ) {
                    wp_kses_post( $this->abp_print_short_description($product_id) );
                }
                echo '<div class="product_title"><h1>' . wp_kses_post(get_the_title($product_id)) . '</h1></div>';
                if ( 'after_title' == get_option('abp_assorted_products_description_position') ) {
                    wp_kses_post( $this->abp_print_short_description($product_id) );
                }
                ?>
                <div class="abp_custom_assorted_product_content">
                    <div>
                        <?php do_action('abp_assorted_product_filters_layout', $product_id); ?>
                    </div>
                    <div class="abp_assorted_row">

                        <div class="abp-col-9">
                            <div class="abp_assorted_products" data-product-id="<?php esc_attr_e($product_id); ?>">
                                <?php do_action('abp_assorted_product_items_layout', $product_id); ?>
                            </div>

                            <div class="show_all_hamper_products">Next: select products</div>

                        </div>
                        <div class="abp-col-3">
                            <div class="abp-col-sidebar-inner" id="edit_hamper">
                                <div class="abp-col-sidebar-content">
                                    <?php do_action('abp_assorted_product_checkout_layout', $product_id); ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div id="mobile_view_hamper_button">
                    <div>View/Edit Hamper</div>
                </div>

                <?php
                if ( 'after_layout' == get_option('abp_assorted_products_description_position') ) {
                    $this->abp_print_short_description($product_id);
                }
                ?>
            </div>
            <?php
        }

        public function abp_filter_type_tag( $tags, $product_id ) {
            if ( empty($tags) ) {
                return;
            }
            $type = get_post_meta($product_id, 'abp_products_tags_filter_type', true);
            $heading = get_post_meta($product_id, 'abp_assorted_tags_heading', true);
            if ( !empty($heading) ) {
                echo '<h3>' . esc_html__( $heading, 'wc-abp' ) . '</h3>';
            }
            if ( 'radio' == $type )
            {
                echo '<div class="abp_products_filter_type_radio">';
                echo '<span class="abp_filter_item"><label><input type="radio" name="filter-tag" value="">' . esc_html__('All Tags', 'wc-abp') . '</label></span>';
                foreach ($tags as $key => $term) {
                    echo '<span class="abp_filter_item"><label><input type="radio" name="filter-tag" value="' . esc_attr($term->term_id) . '">' . esc_attr($term->name) . '</label></span>';
                }
                echo '</div>';
            } elseif ( 'checkbox' == $type ) {
                echo '<div class="abp_products_filter_type_checkbox">';
                foreach ($tags as $key => $term) {
                    echo '<span class="abp_filter_item"><label><input type="checkbox" name="filter-tag[]" class="abp-search-filter-cat-btn" value="' . esc_attr($term->term_id) . '">' . esc_attr($term->name) . '</label></span>';
                }
                echo '</div>';
            } else {
                ?>
                <select name="filter-tag">
                    <option value=""><?php esc_html_e('All Tags', 'wc-abp'); ?></option>
                    <?php
                    foreach ($tags as $key => $term) {
                        echo '<option value="' . esc_attr($term->term_id) . '">' . esc_attr($term->name) . '</option>';
                    }
                    ?>
                </select>
                <?php
            }
        }




        public function abp_filter_type( $categories, $product_id )
        {
            if ( empty($categories) )
            {
                return;
            }

            $type = get_post_meta($product_id, 'abp_products_filter_type', true);
            $heading = get_post_meta($product_id, 'abp_assorted_cats_heading', true);

            echo '<h2 class="linetitle">Step 1 & 2</h2><p style="text-align: center;border-bottom: 1px solid #56422b;padding-bottom: 2rem;">Choose Your hamper packaging, followed by adding all your contents.</p>';

            ?>


            <div class="desktop-filters">
                <h3 class="text-center">SEARCH BY</h3>
                <div style="display:flex;">

                    <div class="abp_products_filter_type_checkbox">

                        <?php
                        foreach ($categories as $key => $term)
                        {
                            echo '<span class="abp_filter_item abp-search-filter-cat-btn '.($term->term_id == 239 ? "active" : "").'"><label><input type="checkbox" name="filter-category[]" value="' . esc_attr($term->term_id) . '">' . esc_attr($term->name) .' ('.$term->count.')</label></span>';
                        }

                        ?>
                    </div>
                    <div class="desktop-sort-filters">
                        <h4 class="text-center cat-sort-btn">
                            SORT BY
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Pro 6.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M224 352c-4.094 0-8.188 1.562-11.31 4.688L144 425.4V48C144 39.16 136.8 32 128 32S112 39.16 112 48v377.4l-68.69-68.69c-6.25-6.25-16.38-6.25-22.62 0s-6.25 16.38 0 22.62l96 96c6.25 6.25 16.38 6.25 22.62 0l96-96c6.25-6.25 6.25-16.38 0-22.62C232.2 353.6 228.1 352 224 352zM427.3 132.7l-96-96c-6.25-6.25-16.38-6.25-22.62 0l-96 96c-6.25 6.25-6.25 16.38 0 22.62s16.38 6.25 22.62 0L304 86.63V464c0 8.844 7.156 16 16 16s16-7.156 16-16V86.63l68.69 68.69C407.8 158.4 411.9 160 416 160s8.188-1.562 11.31-4.688C433.6 149.1 433.6 138.9 427.3 132.7z"/></svg>
                        </h4>
                        <div class="content abp_products_sort_type_checkbox" style="display:none;">
                            <span class="abp_filter_item abp-search-sort-cat-btn"><label><input type="checkbox" name="filter-category[]" value="meta_value_num DESC">Price High-Low</label></span>
                            <span class="abp_filter_item abp-search-sort-cat-btn"><label><input type="checkbox" name="filter-category[]" value="meta_value_num ASC">Price Low-High</label></span>
                            <span class="abp_filter_item abp-search-sort-cat-btn"><label><input type="checkbox" name="filter-category[]" value="title DESC">Name Z-A</label></span>
                            <span class="abp_filter_item abp-search-sort-cat-btn"><label><input type="checkbox" name="filter-category[]" value="title ASC">Name A-Z</label></span>
                        </div>
                    </div>
                </div>
            </div>




            <div class="mobile-cat-filters custom-modal">
                <div class="content">
                    <i class="fa-solid fa-xmark close-modal-cat"></i>
                    <h3 class="linetitle">SEARCH BY...</h3>

                    <?php
                    echo '<div class="abp_products_filter_type_checkbox">';
                    foreach ($categories as $key => $term)
                    {
                        echo '<span class="abp_filter_item abp-search-filter-cat-btn '.($term->term_id == 239 ? "active" : "").'"><label><input type="checkbox" name="filter-category[]" value="' . esc_attr($term->term_id) . '" '.($term->term_id == 239 ? "checked" : "").'>' . esc_attr($term->name) . ' ('.$term->count.')</label></span>';
                    }
                    echo '</div>';
                    ?>
                </div>
                <div class="footer">
                    <div class="cat-filter-done-btn center" style="width: 100%!important;">DONE</div>
                </div>
            </div>

            <div class="mobile-sort-filters custom-modal">
                <div class="content">
                    <i class="fa-solid fa-xmark close-modal-sort"></i>
                    <h3 class="linetitle">SORT BY</h3>
                    <div class="abp_products_sort_type_checkbox">
                        <span class="abp_filter_item abp-search-sort-cat-btn"><label><input type="checkbox" name="filter-category[]" value="meta_value_num DESC">Price High-Low</label></span>
                        <span class="abp_filter_item abp-search-sort-cat-btn"><label><input type="checkbox" name="filter-category[]" value="meta_value_num ASC">Price Low-High</label></span>
                        <span class="abp_filter_item abp-search-sort-cat-btn"><label><input type="checkbox" name="filter-category[]" value="title DESC">Name Z-A</label></span>
                        <span class="abp_filter_item abp-search-sort-cat-btn"><label><input type="checkbox" name="filter-category[]" value="title ASC">Name A-Z</label></span>
                    </div>
                </div>
                <div class="footer">
                    <div class="cat-sort-done-btn center" style="width: 100%!important;">APPLY SORT</div>
                </div>
            </div>

            <div class="mobile-filters">
                <h4 class="text-center cat-filter-btn">
                    SEARCH BY
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M324.4 64C339.6 64 352 76.37 352 91.63C352 98.32 349.6 104.8 345.2 109.8L240 230V423.6C240 437.1 229.1 448 215.6 448C210.3 448 205.2 446.3 200.9 443.1L124.7 385.6C116.7 379.5 112 370.1 112 360V230L6.836 109.8C2.429 104.8 0 98.32 0 91.63C0 76.37 12.37 64 27.63 64H324.4zM144 224V360L208 408.3V223.1C208 220.1 209.4 216.4 211.1 213.5L314.7 95.1H37.26L140 213.5C142.6 216.4 143.1 220.1 143.1 223.1L144 224zM496 400C504.8 400 512 407.2 512 416C512 424.8 504.8 432 496 432H336C327.2 432 320 424.8 320 416C320 407.2 327.2 400 336 400H496zM320 256C320 247.2 327.2 240 336 240H496C504.8 240 512 247.2 512 256C512 264.8 504.8 272 496 272H336C327.2 272 320 264.8 320 256zM496 80C504.8 80 512 87.16 512 96C512 104.8 504.8 112 496 112H400C391.2 112 384 104.8 384 96C384 87.16 391.2 80 400 80H496z"/></svg>
                </h4>
                <h4 class="text-center cat-sort-btn">
                    SORT BY
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><!--! Font Awesome Pro 6.0.0 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2022 Fonticons, Inc. --><path d="M224 352c-4.094 0-8.188 1.562-11.31 4.688L144 425.4V48C144 39.16 136.8 32 128 32S112 39.16 112 48v377.4l-68.69-68.69c-6.25-6.25-16.38-6.25-22.62 0s-6.25 16.38 0 22.62l96 96c6.25 6.25 16.38 6.25 22.62 0l96-96c6.25-6.25 6.25-16.38 0-22.62C232.2 353.6 228.1 352 224 352zM427.3 132.7l-96-96c-6.25-6.25-16.38-6.25-22.62 0l-96 96c-6.25 6.25-6.25 16.38 0 22.62s16.38 6.25 22.62 0L304 86.63V464c0 8.844 7.156 16 16 16s16-7.156 16-16V86.63l68.69 68.69C407.8 158.4 411.9 160 416 160s8.188-1.562 11.31-4.688C433.6 149.1 433.6 138.9 427.3 132.7z"/></svg>
                </h4>
            </div>

            <?php

        }

        public function abp_product_checkout_layout( $product_id )
        {
            $product=wc_get_product($product_id);
            $all_products=get_post_meta($product_id, 'abp_complete_store_available', true);
            $max = get_post_meta($product_id, 'abp_assorted_max_products', true);
            $categories = get_post_meta($product_id, 'abp_products_categories_enabled', true);
            $tags = get_post_meta($product_id, 'abp_products_tags_enabled', true);
            $search_text=get_option('abp_assorted_products_search_btn_text');
            $search_text=!empty($search_text) ? $search_text : esc_html__('Search', 'wc-abp');
            $reset_text=get_option('abp_assorted_products_reset_btn_text');
            $reset_text=!empty($reset_text) ? $reset_text : esc_html__('Reset Filters', 'wc-abp');
            $order_details=get_option('abp_assorted_products_order_details_text');
            $order_details=!empty($order_details) ? $order_details : esc_html__('Order Details', 'wc-abp');

            $price = $product->get_price();
            $type = get_post_meta($product_id, 'abp_pricing_type', true);
            if ( 'per_product_items' == $type )
            {
                $price = 0;
            }
            ?>
            <div class="abp_review_before_cart">
                <div class="abp_review_order center">
                    <h2><?php esc_html_e($order_details, 'wc-abp'); ?></h2>
                </div>
                <div class="abp_bundle_itmes_content">
                    <table>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                <?php
                $this->abp_extra_options($product_id);
                if ( 'before_price' == get_option('abp_assorted_products_description_position') ) {
                    $this->abp_print_short_description($product_id);
                }
                ?>
                <span class="price abp_assorted_bundle_price center" style="margin-top: 1em;">
					<span class="assorted_price">Total: <?php echo wp_kses_post( wc_price($price) ); ?></span>
					<?php do_action('abp_assorted_product_after_price', $product_id); ?>
				</span>
                <div class="abp_assorted_bundle_discount"></div>
                <?php
                if ( 'after_price' == get_option('abp_assorted_products_description_position') ) {
                    $this->abp_print_short_description($product_id);
                }
                echo wp_kses_post( wc_get_stock_html( $product ) );
                wp_nonce_field( 'abp_assorted_mini_nonce', 'abp_assorted_mini_nonce' );
                if ( $product->is_purchasable() && $product->is_in_stock() ) {
                    woocommerce_quantity_input( array(
                        'min_value'   => apply_filters( 'woocommerce_quantity_input_min', $product->get_min_purchase_quantity(), $product ),
                        'max_value'   => apply_filters( 'woocommerce_quantity_input_max', $product->get_max_purchase_quantity(), $product ),
                        'input_value' => $product->get_min_purchase_quantity(),
                    ));
                }

                // if ( $product->is_purchasable() && $product->is_in_stock() )
                // {
                $btn_text = get_option('abp_assorted_products_addtocart_text');
                $btn_text = !empty($btn_text) ? esc_html__($btn_text, 'wc-abp') : esc_html__('Add to basket', 'wc-abp');
                $btn_name = apply_filters('wc_abp_add_to_cart_button_name', 'add-to-cart', $product_id);
                ?>
                <button type="submit" name="<?php echo esc_attr($btn_name); ?>" value="<?php echo esc_attr( $product_id ); ?>" class="single_add_to_cart_button abp_assorted_bundle button alt" disabled="disabled"><?php echo esc_html( $btn_text ); ?></button>
                <input type="hidden" id="abp-assorted-add-to-cart" name="abp-assorted-add-to-cart" value="" />
                <?php
                // }

                echo '<div class="abp_assorted_footer">';
                $counter = get_option('abp_assorted_products_counter');
                if ( !empty($counter) ) {
                    $counter = str_replace('{counter}', '', $counter);
                    ?>
                    <div class="abp_bundle_counter" style="display: none;"><span class="abp_bundle_count"><?php echo '0/' . esc_html($max); ?></span><?php echo esc_html($counter); ?></div>
                    <?php
                }
                if ( 'yes' == get_option('abp_assorted_products_clear') ) {
                    $text = get_option('abp_assorted_products_clear_text');
                    $text = !empty($text) ? esc_html__($text, 'wc-abp') : esc_html__('Clear All', 'wc-abp');
                    ?>
                    <div class="abp_assorted_clear_wrap"><a href="#" class="abp_assorted_clear"><?php echo esc_html($text); ?></a></div>
                <?php } ?>
            </div>
            </div>

            <?php
            if ( 'after_order' == get_option('abp_assorted_products_description_position') )
            {
                $this->abp_print_short_description($product_id);
            }
        }

        public function abp_product_filters_layout( $product_id )
        {

            $all_products=get_post_meta($product_id, 'abp_complete_store_available', true);
            $categories = get_post_meta($product_id, 'abp_products_categories_enabled', true);
            $tags = get_post_meta($product_id, 'abp_products_tags_enabled', true);
            $search_text=get_option('abp_assorted_products_search_btn_text');
            $reset_text=get_option('abp_assorted_products_reset_btn_text');

            if ( 'yes' != get_option('abp_assorted_hide_search_filters') )
            {
                ?>
                <div class="abp-filter-content">
                    <?php
                    do_action('abp_assorted_filter_form_start', $product_id);
                    do_action('abp_assorted_filter_after_search_field', $product_id);

                    if ( ( 'yes' == $all_products || !empty($categories) ) && 'no' != get_post_meta($product_id, 'abp_enable_categories_filters', true) )
                    {
                        if ( 'yes' == $all_products )
                        {
                            $args = array(
                                'taxonomy'   => 'product_cat',
                                'hide_empty' => true,
                                'order'    => 'DESC'
                            );
                            $categories = get_terms($args);
                        }
                        else
                        {
                            $args = array(
                                'taxonomy'   => 'product_cat',
                                'hide_empty' => true,
                                'include'    => $categories,
                                'order'    => 'DESC'
                            );
                            $categories = get_terms($args);
                        }
                        ?>
                        <div class="filter-field abp-filter-cats">
                            <?php $this->abp_filter_type( $categories, $product_id ); ?>
                        </div>
                        <?php
                    }
                    if ( ( 'yes' == $all_products || !empty($tags) ) && 'yes' == get_post_meta($product_id, 'abp_enable_tags_filters', true) )
                    {
                        if ( 'yes' == $all_products )
                        {
                            $args2 = array(
                                'taxonomy'   => 'product_tag',
                                'hide_empty' => true,
                            );
                            $tags = get_terms($args2);
                        }
                        else
                        {
                            $args2 = array(
                                'taxonomy'   => 'product_tag',
                                'hide_empty' => true,
                                'include'    => $tags
                            );
                            $tags = get_terms($args2);
                        }
                        ?>
                        <div class="filter-field abp-filter-tags">
                            <?php $this->abp_filter_type_tag( $tags, $product_id ); ?>
                        </div>
                        <?php
                    }
                    do_action('abp_assorted_filter_form_before_search', $product_id);
                    ?>

                    <?php do_action('abp_assorted_filter_form_end', $product_id); ?>
                </div>
                <div class="bundle_search">
                    <input type="text" placeholder="Search" name="search" value="">
                    <i class="fa-solid fa-magnifying-glass abp-search-filter-btn"></i>
                </div>
                <?php
            }

            if ( 'after_order' == get_option('abp_assorted_products_description_position') )
            {
                $this->abp_print_short_description($product_id);
            }
        }

        public function abp_product_items_layout( $product_id ) {
            ?>
            <div class="apb_products_items">
                <div class="apb_products_items_container">

                </div>
                <div class="abp_loader"><div></div><div></div><div></div><div></div></div>
            </div>
            <?php
        }

        public function abp_extra_options( $product_id )
        {
            $type=get_post_meta($product_id, 'abp_enable_assorted_gift_field_type', true);
            $required=get_post_meta($product_id, 'abp_enable_assorted_gift_required', true);

            $label=!empty($label) ? $label : esc_html__('Message', 'wc-abp');
            $required= ( 'yes' == $required ) ? 'requried' : '';
            echo '<div class="abp_extra_field center">';
            echo '<label class="linetitle" for="abp_assorted_message_field">Step 3</label><p>Add your gift message</p>';
            if ( 'textarea' == $type ) {
                echo '<span class="abp_field"><textarea name="abp_assorted_message_field" id="abp_assorted_message_field" ' . esc_attr($required) . '></textarea></span>';
            } else {
                echo '<span class="abp_field"><input type="text" name="abp_assorted_message_field" id="abp_assorted_message_field" value="" ' . esc_attr($required) . '></span>';
            }
            echo '</div>';
        }

        public function abp_error_message() {
            $product_id = apply_filters( 'wc_abp_assorted_edit_subscription_product_id', get_the_id() );
            $product = wc_get_product($product_id);
            if ( 'product' == get_post_type($product_id) && $product->is_type('assorted_product') ) {
                if ( $product->is_type('assorted_product') ) {
                    echo '<div id="abp-max-error" style="display:none;"></div>';
                    echo '<div id="abp-max-success" style="display:none;"></div>';
                }
                echo '<div class="abp_product_boxes_layer" style="display: none;"></div>
				<div class="abp_product_quick_view" style="display: none;">
					<div class="abp_product_quick_view_head"><span class="abp_product_quick_view_close dashicons dashicons-no-alt"></span></div>
					<div class="abp_product_quick_view_content"></div>
					<div class="abp_product_quick_view_footer"><div class="abp_loader"><div></div><div></div><div></div><div></div></div></div>
				</div>';
            }
        }

        public function abp_print_short_description( $product_id ) {
            if ( 'yes' == get_option('abp_assorted_products_show_description') ) {
                echo '<div class="abp-short-description">';
                echo wp_kses_post( apply_filters( 'woocommerce_short_description', get_the_excerpt($product_id) ) );
                echo '</div>';
            }
        }

        public function abp_enqueue_front_scripts() {
            $product_id = apply_filters( 'wc_abp_assorted_edit_subscription_product_id', get_the_id() );
            $product = wc_get_product($product_id);
            if ( 'product' == get_post_type($product_id) && $product->is_type('assorted_product') ) {
                $min = get_post_meta($product_id, 'abp_assorted_min_products', true);
                $min = !empty($min) ? absint( $min ) : 1 ;
                $max = get_post_meta($product_id, 'abp_assorted_max_products', true);
                $max = !empty($max) ? absint( $max ) : 1 ;
                $price_type = get_post_meta($product_id, 'abp_pricing_type', true);
                $msg_success = get_option('abp_assorted_products_item_added_text');
                $msg_success = !empty($msg_success) ? esc_html__($msg_success, 'wc-abp') : esc_html__('Product has been added to bundle.', 'wc-abp');
                $msg_error = get_option('abp_assorted_products_max_error_text');
                $msg_error = !empty($msg_error) ? esc_html__($msg_error, 'wc-abp') : esc_html__('You can not add more products.', 'wc-abp');
                $max_item_error = get_option('abp_assorted_products_item_max_error');
                $max_item_error = !empty($max_item_error) ? esc_html__($max_item_error, 'wc-abp') : esc_html__('Maximum item quantity is added.', 'wc-abp');

                $currency_position = !empty(get_option('woocommerce_currency_pos')) ? get_option('woocommerce_currency_pos') : 'left';
                $thousand_sep = !empty(get_option('woocommerce_price_thousand_sep')) ? get_option('woocommerce_price_thousand_sep') : ',';
                $decimal_sep = !empty(get_option('woocommerce_price_decimal_sep')) ? get_option('woocommerce_price_decimal_sep') : '.';
                $no_of_decimal = !empty(get_option('woocommerce_price_num_decimals')) ? get_option('woocommerce_price_num_decimals') : 2;
                $box_item_click = get_option('abp_assorted_products_item_click');
                $box_item_click = !empty($box_item_click) ? $box_item_click : 'redirect';
                $discounts = get_post_meta($product_id, 'abp_assorted_category_discounts', true);
                $qty_discounts = get_post_meta($product_id, 'abp_assorted_quantities_discounts', true);
                wp_enqueue_style('dashicons');
                wp_enqueue_style('slick', WC_ABP_URL . '/assets/css/slick.css', array(), '1.5');
                wp_enqueue_style('slick-theme', WC_ABP_URL . '/assets/css/slick-theme.css', array(), '1.5');
                wp_enqueue_style('abp-product-style', WC_ABP_URL . '/assets/css/frontend_style.css', array(), '1.0.3');
                wp_enqueue_script('slick', WC_ABP_URL . '/assets/js/slick.min.js', array('jquery'), '1.5');
                wp_register_script('abp-product-script', WC_ABP_URL . '/assets/js/frontend_script.js', array('jquery'), '1.0.8', true );
                wp_localize_script('abp-product-script', 'abpAssorted', array(
                    'ajaxurl'		=>	admin_url('admin-ajax.php'),
                    'product_id'	=> $product_id,
                    'type'	=>	$product->is_type('assorted_product'),
                    'ajax_nonce' => wp_create_nonce('assorted_bundle'),
                    'price'	=> $product->get_price(),
                    'price_type' => $price_type,
                    'min'	=> $min,
                    'max'	=> $max,
                    'max_error'=> $msg_error,
                    'max_item_error' => $max_item_error,
                    'msg_success'=> $msg_success,
                    'removebtn' => get_option('abp_assorted_products_remove_addtocart'),
                    'currency_symbol' => get_woocommerce_currency_symbol(),
                    'currency_pos'    => esc_attr($currency_position),
                    'thousand_sep'    => esc_attr($thousand_sep),
                    'decimal_sep'     => esc_attr($decimal_sep),
                    'no_of_decimal'   => esc_attr($no_of_decimal),
                    'box_item_click'  => $box_item_click,
                    'enable_discounts' => get_post_meta($product_id, 'abp_enable_categories_discounts', true),
                    'discounts' => $discounts,
                    'enable_qty_discounts' => get_post_meta($product_id, 'abp_enable_quantities_discounts', true),
                    'qty_discounts' => $qty_discounts,
                    'show_discount' => get_option('abp_assorted_show_product_discount'),
                    'discount_label' => esc_html__(get_option('abp_assorted_discount_text', 'Discount'), 'wc-abp')
                ));
                wp_enqueue_script('abp-product-script');
            }
        }
    }
    new ABP_Assorted_Product_Frontend();
}