<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is display the custom filter for product
 *
 * @link       https://rextheme.com
 * @since      1.1.10
 *
 * @package    Rex_Product_Feed
 * @subpackage Rex_Product_Feed/admin/partials
 */


// Exit if $feed_template obj isn't available.
if ( ! isset($feed_filter) ) {
    return;
}

unset($feed_filter->getFilterMappings()['Primary Attributes']['product_cats']);
unset($feed_filter->getFilterMappings()['Primary Attributes']['product_tags']);
$is_premium = apply_filters('wpfm_is_premium_activate', false);
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<?php
if ( wpfm_pro_compatibility() ) {
	do_action( 'wpfm_pro_filter_rules' );
}
?>
<h2 class="filer-rules-header"><?php echo esc_html__('Custom Filter', 'rex-product-feed') ?></h2>
<div class="rex__filter-table">
    <table id="config-table" class="filter-config-table responsive-table">
        <thead>
            <tr>
                <th class="large-col"><?php echo esc_html__('If', 'rex-product-feed') ?><span>*</span></th>
                <th class="large-col"><?php echo esc_html__('Condition', 'rex-product-feed') ?><span>*</span></th>
                <th class="large-col"><?php echo esc_html__('Value', 'rex-product-feed') ?></th>
                <th class="2"><?php echo esc_html__('Then', 'rex-product-feed') ?></th>
                <th class="2"><?php echo esc_html__('Action', 'rex-product-feed') ?></th>
            </tr>
        </thead>

        <tbody>

        <?php
            $keyt = rand(999, 3000); ?>
            <tr data-row-id="<?php echo esc_html($keyt); ?>" style="display: none;">
                <td data-title="If : "><?php $feed_filter->printSelectDropdown( $keyt, 'if', 'ff', '' ); ?></td>
                <td data-title="condition : "><?php $feed_filter->printSelectDropdown( $keyt, 'condition', 'ff', '' ); ?></td>
                <td data-title="value : " ><?php $feed_filter->printInput( $keyt, 'value', 'ff', '' ); ?></td>
                <td data-title="then : "><?php $feed_filter->printSelectDropdown( $keyt, 'then', 'ff', '' ); ?></td>
                <td>
                    <a class="delete-row" title="Delete">
                        <i class="fa fa-trash"></i>
                    </a>
                </td>
            </tr>

            <?php foreach ( $feed_filter->getFilterMappings() as $key => $item): ?>
                <tr data-row-id="<?php echo esc_html($key); ?>">
                    <td data-title="If : "><?php $feed_filter->printSelectDropdown( $key, 'if', 'ff', $item['if'] ); ?></td>
                    <td data-title="condition : "><?php $feed_filter->printSelectDropdown( $key, 'condition', 'ff', $item['condition'] ); ?></td>
                    <td data-title="value : "><?php $feed_filter->printInput( $key, 'value', 'ff', $item['value'] ); ?></td>
                    <td data-title="then : "><?php $feed_filter->printSelectDropdown( $key, 'then', 'ff', $item['then'] ); ?></td>
                    <td>
                        <a class="delete-row" title="Delete">
                            <i class="fa fa-trash"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>