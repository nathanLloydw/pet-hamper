<?php $icon = 'icon/icon-svg/icon-question.php'; ?>

<div class="rex_feed_config_div rex-feed-merchant">
	<label for="<?php echo esc_attr($this->prefix) . 'merchant'; ?>"><?php esc_html_e( 'Feed Merchant', 'rex-product-feed' ) ?>
		<span class="rex_feed-tooltip">
            <?php include WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon;?>
            <p><?php esc_html_e( 'Select your preferred merchant from the list', 'rex-product-feed' ) ?></p>
        </span>
	</label>
    <?php
    $class = 'rex-merchant-list-select2';
    $id = $name = $this->prefix . 'merchant';
    $selected = $saved_merchant !== '' ? $saved_merchant : '-1';
    Rex_Feed_Merchants::render_merchant_dropdown( $class, $id, $name, $selected );
    ?>
</div>

<div class="rex_feed_config_div rex-feed-feed-format">
	<label for="<?php echo esc_attr($this->prefix) . 'feed_format'; ?>"><?php esc_html_e( 'Feed Type', 'rex-product-feed' ) ?>
		<span class="rex_feed-tooltip">
            <?php include WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon;?>
            <p><?php esc_html_e( 'Select your feed file type', 'rex-product-feed' ); ?></p>
        </span>
	</label>
	<select name="<?php echo esc_attr($this->prefix) . 'feed_format'; ?>" id="<?php echo esc_attr($this->prefix) . 'feed_format'; ?>" class="<?php echo esc_attr($this->prefix) . 'feed-format'; ?>">
		<option value="xml" <?php echo $file_format === 'xml' ? 'selected' : '';?> ><?php echo esc_html__( 'XML', 'rex-product-feed' ) ?></option>
		<option value="text" <?php echo $file_format === 'text' ? 'selected' : '';?> ><?php echo esc_html__( 'TEXT', 'rex-product-feed' ) ?></option>
		<option value="csv" <?php echo $file_format === 'csv' ? 'selected' : '';?> ><?php echo esc_html__( 'CSV', 'rex-product-feed' ) ?></option>
		<option value="tsv" <?php echo $file_format === 'tsv' ? 'selected' : '';?> ><?php echo esc_html__( 'TSV', 'rex-product-feed' ) ?></option>
		<option value="json" <?php echo $file_format === 'json' ? 'selected' : '';?> ><?php echo esc_html__( 'JSON', 'rex-product-feed' ) ?></option>
        <option value="yml" <?php echo $file_format === 'yml' ? 'selected' : '';?> ><?php echo esc_html__( 'YML (Yandex Market Language)', 'rex-product-feed' ) ?></option>
	</select>
</div>

<div class="rex_feed_config_div rex-feed-feed-separator" style="display: none">
	<label for="<?php
	echo esc_attr($this->prefix) . 'separator'; ?>"><?php esc_html_e( 'Separator', 'rex-product-feed' ) ?>
		<span class="rex_feed-tooltip">
            <?php include WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon;?>
            <p><?php esc_html_e( 'Select separator','rex-product-feed' ); ?></p>
        </span>
	</label>
	<?php
	$saved_value        = get_post_meta( get_the_ID(), 'rex_feed_separator', true );
	$saved_value        = $saved_value !== '' ? $saved_value : 'comma';
	$checked_comma      = $saved_value === 'comma' ? ' selected' : '';
	$checked_semi_colon = $saved_value === 'semi_colon' ? ' selected' : '';
	$checked_pipe       = $saved_value === 'pipe' ? ' selected' : '';
	?>
	<select name="<?php echo esc_attr($this->prefix) . 'separator'; ?>" id="<?php echo esc_attr($this->prefix) . 'separator'; ?>" class="">
		<option value="comma" <?php echo esc_attr($checked_comma)?>><?php echo esc_html__( 'Comma (,)', 'rex-product-feed' ) ?></option>
		<option value="semi_colon" <?php echo esc_attr($checked_semi_colon)?>><?php echo esc_html__( 'Semi-colon (;)', 'rex-product-feed' ) ?></option>
		<option value="pipe" <?php echo esc_attr($checked_pipe)?>><?php echo esc_html__( 'Pipe (|)', 'rex-product-feed' ) ?></option>
	</select>
</div>