<?php
$prefix = 'rex_feed_';
$icon = '../assets/icon/icon-svg/icon-question.php';
?>

<div id="rex-feed-product-taxonomies-contents">
	<div id="rex-feed-product-cats" style="display: none">
		<label for="<?php echo esc_attr($prefix) . 'cats';?>"><?php esc_html_e('Product Categories', 'rex-product-feed')?>
			<span class="rex_feed-tooltip">
                    <?php include plugin_dir_path(__FILE__) . $icon;?>
                    <p><?php esc_html_e('Product Categories', 'rex-product-feed')?></p>
                </span>
		</label>
		<ul id="<?php echo esc_attr($prefix) . 'cats';?>">
			<?php
			$terms      = get_terms( array( 'taxonomy' => 'product_cat' ) );
			$terms      = is_array( $terms ) ? $terms : array();
			$post_terms = wp_get_post_terms( $feed_id, 'product_cat', array( 'fields' => 'slugs' ) );
			$post_terms = is_array( $post_terms ) ? $post_terms : array();
			$index      = 1;

            if ( empty( $terms ) ) {
                echo '<li>';
                echo '<label for="'. esc_attr($prefix) . 'tags' . esc_attr($index++) . '">'.esc_html__('No Categories', 'rex-product-feed').'</label>';
                echo '</li>';
            }
            else {
                foreach( $terms as $term ) {
                    $checked = in_array( $term->slug, $post_terms) ? ' checked' : '';
                    echo '<li>';
                    echo '<input type="checkbox" id="'. esc_attr($prefix) . 'cats' . esc_attr($index) . '" name="'. esc_attr($prefix) . 'cats[]' . '" value="'. esc_attr($term->slug) .'" ' .esc_attr($checked). '>';
                    echo '<label for="'. esc_attr($prefix) . 'cats' . esc_attr($index++) . '">'.esc_html__($term->name, 'rex-product-feed').'</label>';
                    echo '</li>';
                }
            }
			?>
		</ul>
	</div>
	<div id="rex-feed-product-tags" style="display: none">
		<label for="<?php echo esc_attr($prefix) . 'tags';?>"><?php esc_html_e('Product Tags', 'rex-product-feed')?>
			<span class="rex_feed-tooltip">
                    <?php include plugin_dir_path(__FILE__) . $icon;?>
                    <p><?php esc_html_e('Product Tags', 'rex-product-feed')?></p>
                </span>
		</label>
		<ul id="<?php echo esc_attr($prefix) . 'tags';?>">
			<?php
			$terms      = get_terms( array( 'taxonomy' => 'product_tag' ) );
			$terms      = is_array( $terms ) ? $terms : array();
            $post_terms = wp_get_post_terms( $feed_id, 'product_tag', array( 'fields' => 'slugs' ) );
			$post_terms = is_array( $post_terms ) ? $post_terms : array();
			$index      = 1;

            if ( empty( $terms ) ) {
                echo '<li>';
                echo '<label for="'. esc_attr($prefix) . 'tags' . esc_attr($index++) . '">'.esc_html__('No Terms', 'rex-product-feed').'</label>';
                echo '</li>';
            }
            else {
                foreach( $terms as $term ) {
                    $checked = in_array( $term->slug, $post_terms) ? ' checked' : '';
                    echo '<li>';
                    echo '<input type="checkbox" id="'. esc_attr($prefix) . 'tags' . esc_attr($index) . '" name="'. esc_attr($prefix) . 'tags[]' . '" value="'. esc_attr($term->slug) .'" ' .esc_attr($checked). '>';
                    echo '<label for="'. esc_attr($prefix) . 'tags' . esc_attr($index++) . '">'.esc_html__($term->name, 'rex-product-feed').'</label>';
                    echo '</li>';
                }
            }
			?>
		</ul>
	</div>
</div>