<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is display the custom feed configuration part of the metabox on feed edit screen.
 *
 * @link       https://rextheme.com
 * @since      1.0.0
 *
 * @package    Rex_Product_Feed
 * @subpackage Rex_Product_Feed/admin/partials
 */

// Exit if $feed_template obj isn't available.
if ( ! isset( $feed_template ) ) {
	return;
}
$wpfm_hide_char = get_option( 'rex_feed_hide_character_limit_field', 'on' );
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->

<thead>
	<tr>
		<th class="" id="rex_feed_attr_head"><?php echo esc_html__( 'Required Attributes', 'rex-product-feed' ); ?><span>*</span></th>
		<th class="" id="rex_feed_type_head"><?php echo esc_html__( 'Attribute Type', 'rex-product-feed' ); ?><span>*</span></th>
		<th class="" id="rex_feed_val_head"><?php echo esc_html__( 'Assigned Values', 'rex-product-feed' ); ?><span>*</span></th>
		<th class="" id="rex_feed_prefix_head"><?php echo esc_html__( 'Prefix', 'rex-product-feed' ); ?></th>
		<th class="" id="rex_feed_suffix_head"><?php echo esc_html__( 'Suffix', 'rex-product-feed' ); ?></th>
		<th class="" id="rex_feed_sanitization_head"><?php echo esc_html__( 'Output Filter', 'rex-product-feed' ); ?></th>
		<th class="" id="rex_feed_output_limit_head"><?php echo esc_html__( 'Character Limit', 'rex-product-feed' ); ?></th>
		<th class="" id="rex_feed_output_action_head"><?php echo esc_html__( 'Action', 'rex-product-feed' ); ?></th>
	</tr>
</thead>

<tbody>

<?php
$keyy = wp_rand( 999, 3000 );
?>
<tr data-row-id="<?php echo esc_attr( $keyy ); ?>" style="display: none; ">
	<td data-title="Attributes : "><?php $feed_template->print_select_dropdown( $keyy, 'attr', '', 'attr-dropdown' ); ?>
	</td>
	<td data-title="Type : "><?php $feed_template->print_attr_type( $keyy, '' ); ?></td>
	<td data-title="Value : ">
		<div class="meta-dropdown">
			<?php
			echo '<select class="attr-val-dropdown" name="fc[' . esc_attr( $keyy ) . '][meta_key]" >';
			echo "<option value=''>" . esc_html__( 'Please Select', 'rex-product-feed' ) . "</option>";
			echo $feed_template->print_product_attributes(); // phpcs:ignore
			echo "</select>";
			?>
		</div>
		<div class="static-input">
			<?php $feed_template->print_input( $keyy, 'st_value', '' ); ?>
		</div>
		<?php do_action( 'rex_feed_after_static_input', $feed_template, $keyy, '' ); ?>
	</td>
	<td data-title="Prefix : "><?php $feed_template->print_input( $keyy, 'prefix', '' ); ?></td>
	<td data-title="Suffix : "><?php $feed_template->print_input( $keyy, 'suffix', '' ); ?></td>
	<td data-title="Output Sanitization : "><?php $feed_template->print_select_dropdown( $keyy, 'escape', 'default', 'default-sanitize-dropdown', 'multiple', '[]' ); ?></td>
	<td data-title="Output Limit : "><?php $feed_template->print_input( $keyy, 'limit', 0 ); ?></td>
	<td>
		<a class="delete-row" title="Delete">
			<i class="fa fa-trash"></i>
		</a>
	</td>
</tr>

<?php foreach ( $feed_template->get_template_mappings() as $key => $item ) : ?>
	<?php
	$display_none = 'style="display: none"';
	$hide_meta    = $display_none;
	$hide_static  = $display_none;

	if ( isset( $item[ 'type' ] ) ) {
		if ( 'meta' === $item[ 'type' ] ) {
			$hide_meta = '';
		}
		elseif ( 'static' === $item[ 'type' ] ) {
			$hide_static = '';
		}
		elseif ( 'combined' === $item[ 'type' ] && ( function_exists( 'rex_feed_is_wpfm_pro_active' ) && !rex_feed_is_wpfm_pro_active() ) ) {
			$hide_meta      = '';
			$item[ 'type' ] = 'meta';
		}
	}
	?>
	<tr data-row-id="<?php echo esc_html( $key ); ?>">
		<td data-title="Attributes : ">
			<?php
			if ( array_key_exists( 'attr', $item ) ) {
				$feed_template->print_select_dropdown( $key, 'attr', !empty( $item['attr'] ) ? $item['attr'] : '', 'attr-dropdown' );
			} else {
				$feed_template->print_input( $key, 'cust_attr', !empty( $item['cust_attr'] ) ? $item['cust_attr'] : '' );
			}
			?>
		</td>
		<td data-title="Type : "><?php $feed_template->print_attr_type( $key, !empty( $item['type'] ) ? $item['type'] : '' ); ?></td>
		<td data-title="Value : ">
			<div class="meta-dropdown" <?php echo filter_var( $hide_meta ); ?>>
				<?php
				echo '<select class="attr-val-dropdown select2-attr-dropdown" name="fc[' . esc_attr( $key ) . '][' . esc_attr( 'meta_key' ) . ']" >';
				echo "<option value=''>" . esc_html__( 'Please Select', 'rex-product-feed' ) . "</option>";
				echo $feed_template->print_product_attributes( !empty( $item['meta_key'] ) ? $item['meta_key'] : '' ); // phpcs:ignore
				echo "</select>";
				?>
			</div>
			<div class="static-input" <?php echo filter_var( $hide_static ); ?>>
				<?php $feed_template->print_input( $key, 'st_value', !empty( $item['st_value'] ) ? $item['st_value'] : '' ); ?>
			</div>
			<?php do_action( 'rex_feed_after_static_input', $feed_template, $key, $item ); ?>
		</td>
		<td data-title="Prefix : "><?php $feed_template->print_input( $key, 'prefix', !empty( $item['prefix'] ) ? $item['prefix'] : '' ); ?></td>
		<td data-title="Suffix : "><?php $feed_template->print_input( $key, 'suffix', !empty( $item['suffix'] ) ? $item['suffix'] : '' ); ?></td>
		<td data-title="Output Sanitization : "><span class="rex-product-picker-count" title=""></span><?php $feed_template->print_select_dropdown( $key, 'escape', !empty( $item['escape'] ) ? $item['escape'] : '', 'sanitize-dropdown', 'multiple', '[]' ); ?></td>
		<td data-title="Output Limit : "><?php $feed_template->print_input( $key, 'limit', !empty( $item['limit'] ) ? $item['limit'] : '' ); ?></td>
		<td>
			<a class="delete-row d" title="Delete">
				<i class="fa fa-trash"></i>
			</a>
		</td>
	</tr>
<?php endforeach ?>
</tbody>
