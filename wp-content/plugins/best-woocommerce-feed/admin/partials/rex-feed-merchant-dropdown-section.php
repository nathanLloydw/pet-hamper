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
        <option value="rss" <?php echo $file_format === 'rss' ? 'selected' : '';?> ><?php echo esc_html__( 'RSS', 'rex-product-feed' ) ?></option>
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
	$saved_value        = get_post_meta( get_the_ID(), '_rex_feed_separator', true );
	$saved_value        = $saved_value ?: get_post_meta( get_the_ID(), 'rex_feed_separator', true );
	$saved_value        = $saved_value ?: 'comma';
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
<?php
$style = '';
if( 'custom' !== $saved_merchant || ( 'custom' === $saved_merchant && 'xml' !== $file_format ) ) {
    $style = ' style="display: none"';
}
?>

<!-- New include and exclude header -->
<div class="rex_feed_config_div rex_feed_custom_wrapper" <?php echo $style?>>
    <label for="<?php echo esc_attr($this->prefix) . 'custom_xml_header'; ?>"><?php
        _e( 'XML Header', 'rex-product-feed-pro' ) ?>
        <span class="rex_feed-tooltip">
            <?php include plugin_dir_path(__FILE__) . '../assets/icon/icon-svg/icon-question.php';?>
            <p><?php _e('Include or exclude XML file header attributes (title, link, description, datetime)', 'rex-product-feed' )?></p>
        </span>
    </label>
    <?php
    $saved_value = get_post_meta( get_the_ID(), '_rex_feed_custom_xml_header', true );
    ?>
    <select name="<?php echo esc_attr($this->prefix) . 'custom_xml_header'; ?>" id="<?php echo esc_attr($this->prefix) . 'custom_xml_header'; ?>" class="<?php echo esc_attr($this->prefix) . 'custom-xml-header'; ?>">
        <option value="include" <?php echo $saved_value === 'include' ? 'selected' : '';?> ><?php echo esc_html__( 'Include', 'rex-product-feed' ) ?></option>
        <option value="exclude" <?php echo $saved_value === 'exclude' ? 'selected' : '';?> ><?php echo esc_html__( 'Exclude', 'rex-product-feed' ) ?></option>
    </select>
</div>

<div class="rex_feed_config_div rex_feed_custom_items_wrapper" <?php echo $style?>>
    <label for="<?php echo esc_attr($this->prefix) . 'custom_items_wrapper'; ?>"><?php
        _e( 'Items Wrapper', 'rex-product-feed-pro' ) ?>
        <span class="rex_feed-tooltip">
            <?php include plugin_dir_path(__FILE__) . '../assets/icon/icon-svg/icon-question.php';?>
            <p><?php _e('Put custom xml attribute items wrapper name. Keep blank incase of using default structure.', 'rex-product-feed-pro' )?></p>
        </span>
    </label>
    <?php
    $saved_value = get_post_meta( get_the_ID(), '_rex_feed_custom_items_wrapper', true );
    ?>
    <input type="text" name="<?php echo 'rex_feed_custom_items_wrapper'; ?>" id="<?php echo esc_attr($this->prefix) . 'custom_items_wrapper'; ?>" value="<?php echo $saved_value;?>">
</div>

<div class="rex_feed_config_div rex_feed_custom_wrapper" <?php echo $style?>>
    <label for="<?php echo esc_attr($this->prefix) . 'custom_wrapper_el'; ?>"><?php
        _e( 'Wrapper Element', 'rex-product-feed-pro' ) ?>
        <span class="rex_feed-tooltip">
            <?php include plugin_dir_path(__FILE__) . '../assets/icon/icon-svg/icon-question.php';?>
            <p><?php _e('Put custom xml attribute item wrapper_el name. Keep blank incase of using default structure.', 'rex-product-feed-pro' )?></p>
        </span>
    </label>
    <?php
    $saved_value = get_post_meta( get_the_ID(), '_rex_feed_custom_wrapper_el', true );
    ?>
    <input type="text" name="<?php echo 'rex_feed_custom_wrapper_el'; ?>" id="<?php echo esc_attr($this->prefix) . 'custom_wrapper_el'; ?>" value="<?php echo $saved_value;?>">
</div>

<div class="rex_feed_config_div rex_feed_custom_wrapper" <?php echo $style?>>
    <label for="<?php echo esc_attr($this->prefix) . 'custom_wrapper'; ?>"><?php
        _e( 'Item Wrapper', 'rex-product-feed-pro' ) ?>
        <span class="rex_feed-tooltip">
            <?php include plugin_dir_path(__FILE__) . '../assets/icon/icon-svg/icon-question.php';?>
            <p><?php _e('Put custom xml attribute item wrapper name. Keep blank incase of using default structure.', 'rex-product-feed-pro' )?></p>
        </span>
    </label>
    <?php
    $saved_value = get_post_meta( get_the_ID(), '_rex_feed_custom_wrapper', true );
    ?>
    <input type="text" name="<?php echo 'rex_feed_custom_wrapper'; ?>" id="<?php echo esc_attr($this->prefix) . 'custom_wrapper'; ?>" value="<?php echo $saved_value;?>">
</div>

<?php
$style = '';
if( 'yandex' !== $saved_merchant || ( 'yandex' === $saved_merchant && 'xml' !== $file_format ) ) {
    $style = ' style="display: none"';
}
?>

<div class="rex_feed_config_div rex_feed_yandex_old_price" <?php echo $style?>>
    <label for="<?php echo esc_attr($this->prefix) . 'yandex_old_price'; ?>"><?php
        _e( 'Old Price', 'rex-product-feed-pro' ) ?>
        <span class="rex_feed-tooltip">
            <?php include plugin_dir_path(__FILE__) . '../assets/icon/icon-svg/icon-question.php';?>
            <p><?php esc_html_e('Choose option if you want to include/exclude the old price attribute from the feed if it is less/equal than/to the current price.', 'rex-product-feed' )?></p>
        </span>
    </label>
    <?php
    $saved_value = get_post_meta( get_the_ID(), '_rex_feed_yandex_old_price', true );
    ?>
    <select name="<?php echo esc_attr($this->prefix) . 'yandex_old_price'; ?>" id="<?php echo esc_attr($this->prefix) . 'yandex_old_price'; ?>" class="<?php echo esc_attr($this->prefix) . 'yandex_old_price'; ?>">
        <option value="include" <?php echo 'include' === $saved_value ? 'selected' : '';?> ><?php esc_html_e( 'Include', 'rex-product-feed' ) ?></option>
        <option value="exclude" <?php echo 'exclude' === $saved_value ? 'selected' : '';?> ><?php esc_html_e( 'Exclude', 'rex-product-feed' ) ?></option>
    </select>
</div>

<div class="rex_feed_config_div rex_feed_yandex_company_name" <?php echo $style?>>
    <label for="<?php echo esc_attr($this->prefix) . 'yandex_company_name'; ?>"><?php
        _e( 'Company Name', 'rex-product-feed-pro' ) ?>
        <span class="rex_feed-tooltip">
            <?php include plugin_dir_path(__FILE__) . '../assets/icon/icon-svg/icon-question.php';?>
            <p><?php esc_html_e('Put your company name to include in the xml header section with company tag.', 'rex-product-feed-pro' )?></p>
        </span>
    </label>
    <?php
    $saved_value = get_post_meta( get_the_ID(), '_rex_feed_yandex_company_name', true );
    ?>
    <input type="text" name="<?php echo 'rex_feed_yandex_company_name'; ?>" id="<?php echo esc_attr($this->prefix) . 'yandex_company_name'; ?>" value="<?php echo $saved_value;?>">
</div>