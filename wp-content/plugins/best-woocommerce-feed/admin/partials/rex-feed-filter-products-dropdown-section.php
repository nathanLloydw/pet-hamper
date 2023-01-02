<div class="rex-content-filter__area">
	<label for="<?php echo esc_attr( $this->prefix ) . 'products';?>"><?php esc_html_e('Products', 'rex-product-feed')?>
		<span class="rex_feed-tooltip">
            <?php include WPFM_PLUGIN_ASSETS_FOLDER_PATH . 'icon/icon-svg/icon-question.php';?>
            <p><?php esc_html_e( 'Filter Products', 'rex-product-feed' );?></p>
        </span>
	</label>
	<select name="<?php echo esc_attr( $this->prefix ) . 'products'; ?>" id="<?php echo esc_attr( $this->prefix ) . 'products'; ?>">
		<?php
		$prev_value = get_post_meta( get_the_ID(), '_rex_feed_products', true );
		$prev_value = $prev_value ?: get_post_meta( get_the_ID(), 'rex_feed_products', true );
		$prev_value = $prev_value !== '' && 'filter' !== $prev_value ? $prev_value : 'all';
		foreach ( $options as $key => $value ) {
			$selected = $key === $prev_value ? ' selected' : '';
			echo '<option value="' . esc_attr( $key ) . '" '. esc_attr( $selected ) .'>' . esc_html( $value ) . '</option>';
		}
		?>
	</select>
</div>