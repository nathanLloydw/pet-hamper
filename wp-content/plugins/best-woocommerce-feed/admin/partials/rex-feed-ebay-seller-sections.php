<div class="rex_feed_config_div rex_feed_ebay_seller_fields" style="display: none">
	<label for="<?php echo $this->prefix . 'ebay_seller_site_id'; ?>"><?php
		_e( 'eBaySeller Site ID', 'rex-product-feed-pro' ) ?>
        <span class="rex_feed-tooltip">
            <?php include plugin_dir_path(__FILE__) . '../assets/icon/icon-svg/icon-question.php';?>
            <p><?php _e('eBaySeller Site ID', 'rex-product-feed-pro' )?></p>
        </span>
	</label>
	<?php
	$saved_value = get_post_meta( get_the_ID(), '_rex_feed_ebay_seller_site_id', true ) ?: get_post_meta( get_the_ID(), 'rex_feed_ebay_seller_site_id', true );
	?>
	<input type="text" name="<?php echo $this->prefix . 'ebay_seller_site_id'; ?>" id="<?php echo $this->prefix . 'ebay_seller_site_id'; ?>" value="<?php echo $saved_value;?>">
</div>

<div class="rex_feed_config_div rex_feed_ebay_seller_fields" style="display: none">
	<label for="<?php echo $this->prefix . 'ebay_seller_country'; ?>"><?php _e( 'Country', 'rex-product-feed-pro' ) ?>
		<span class="rex_feed-tooltip">
            <?php include plugin_dir_path(__FILE__) . '../assets/icon/icon-svg/icon-question.php';?>
            <p><?php _e( 'Country', 'rex-product-feed-pro' )?></p>
        </span>
	</label>
	<?php
	$saved_value = get_post_meta( get_the_ID(), '_rex_feed_ebay_seller_country', true ) ?: get_post_meta( get_the_ID(), 'rex_feed_ebay_seller_country', true );
	?>
	<select name="<?php echo $this->prefix . 'ebay_seller_country'; ?>" id="<?php echo $this->prefix . 'ebay_seller_country'; ?>">
		<?php
		foreach ( $countries as $key => $value ) {
			$selected = $saved_value === $key ? ' selected' : '';
			echo '<option value="' . $key . '" '. $selected .'>' . $value . '</option>';
		}
		?>
	</select>
</div>

<div class="rex_feed_config_div rex_feed_ebay_seller_fields" style="display: none">
	<label for="<?php echo $this->prefix . 'ebay_seller_currency'; ?>"><?php
		_e( 'eBaySeller Currency', 'rex-product-feed-pro' ) ?>
		<span class="rex_feed-tooltip">
            <?php include plugin_dir_path(__FILE__) . '../assets/icon/icon-svg/icon-question.php';?>
            <p><?php _e('eBaySeller Currency', 'rex-product-feed-pro' )?></p>
        </span>
	</label>
	<?php
	$saved_value = get_post_meta( get_the_ID(), '_rex_feed_ebay_seller_currency', true ) ?: get_post_meta( get_the_ID(), 'rex_feed_ebay_seller_currency', true );
	?>
	<input type="text" name="<?php echo $this->prefix . 'ebay_seller_currency'; ?>" id="<?php echo $this->prefix . 'ebay_seller_currency'; ?>" value="<?php echo $saved_value;?>">
</div>