<?php
/**
 * Template Functions
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.0.0
 * @version  1.4.16
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wc_prl_print_weight_select( $weight, $input_name ) {
	?><div class="sw-enhanced-weight">
		<button type="button" class="button dec"></button>
		<div class="points">
			<input type="hidden" value="<?php echo $weight ?>" name="<?php echo $input_name; ?>">
			<?php for ( $i = 0; $i < $weight; $i++ ) { ?>
				<span class="active"></span>
			<?php } ?>

			<?php
			if ( $weight < 5 ) {
				for ( $i = 0; $i < 5 - $weight; $i++ ) { ?>
				<span></span>
				<?php } ?>
			<?php } ?>
		</div>
		<button type="button" class="button inc"></button>
	</div><?php
}

function wc_prl_print_currency_amount( $amount ) {

	$amount = wc_format_decimal( $amount, 2 );
	$return = $amount;

	switch ( get_option( 'woocommerce_currency_pos' ) ) {
		case 'right':
			$return = $amount . get_woocommerce_currency_symbol();
			break;
		case 'right_space':
			$return = $amount . ' ' . get_woocommerce_currency_symbol();
			break;
		case 'left':
			$return = get_woocommerce_currency_symbol() . $amount;
			break;
		case 'left_space':
			$return = get_woocommerce_currency_symbol() . ' ' . $amount;
			break;
		default:
			break;
	}

	return $return;
}

/*
|--------------------------------------------------------------------------
| Deprecated functions.
|--------------------------------------------------------------------------
*/

function woocommerce_prl_add_link_track_param( $link ) {
	_deprecated_function( __FUNCTION__ . '()', '2.0.0' );
	return $link;
}

function woocommerce_prl_add_data_track_param( $link ) {
	_deprecated_function( __FUNCTION__ . '()', '2.0.0' );
	return $link;
}
