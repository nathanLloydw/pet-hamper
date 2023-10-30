<?php
/**
 * WC_PRL_Amplifier_Conversion_Rate class
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_PRL_Amplifier_Conversion_Rate class for amplifying products based on their price.
 *
 * @class    WC_PRL_Amplifier_Conversion_Rate
 * @version  3.0.0
 */
class WC_PRL_Amplifier_Conversion_Rate extends WC_PRL_Amplifier {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                     = 'conversion_rate';
		$this->title                  = __( 'Conversion', 'woocommerce-product-recommendations' );
		$this->supported_modifiers    = array(
			'DESC' => _x( 'high to low', 'prl_modifiers', 'woocommerce-product-recommendations' )
		);
		$this->supported_engine_types = array( 'cart', 'product', 'order', 'archive' );
	}

	/**
	 * Apply the amplifier to the query args array.
	 *
	 * @param  array $query_args
	 * @param  WC_PRL_Deployment $deployment
	 * @param  array $data
	 * @return array
	 */
	public function amp( $query_args, $deployment, $data ) {

		add_filter( 'posts_clauses', array( $this, 'add_order_clauses' ) );

		return $query_args;
	}

	/**
	 * Alters the raw query in order to add conversion rate order support.
	 *
	 * @param  array $args
	 * @return array
	 */
	public function add_order_clauses( $args ) {
		global $wpdb;

		if ( wc_prl_lookup_tables_enabled( 'order' ) ) {

			$args['join']   .= "
				INNER JOIN (
					SELECT product_id, count(*) AS conv_count FROM {$wpdb->prefix}woocommerce_prl_tracking_conversions WHERE 1=1 GROUP BY product_id
				) as prl_conversions ON ($wpdb->posts.ID = prl_conversions.product_id)
				LEFT JOIN (
					SELECT product_id, count(*) as orders_count
						FROM `{$wpdb->prefix}wc_order_product_lookup` as d
						WHERE 1=1
						GROUP BY d.product_id
				) as prl_total_purchases ON ($wpdb->posts.ID = prl_total_purchases.product_id)
			";

		} else {

			$args['join']   .= "
				INNER JOIN (
					SELECT product_id, count(*) AS conv_count FROM {$wpdb->prefix}woocommerce_prl_tracking_conversions WHERE 1=1 GROUP BY product_id
				) as prl_conversions ON ($wpdb->posts.ID = prl_conversions.product_id)
				LEFT JOIN (
					SELECT product_id, count(*) as orders_count
					FROM (
						SELECT meta_value as product_id, post_date
						INNER JOIN {$wpdb->prefix}woocommerce_order_items ON($wpdb->order_itemmeta.order_item_id = {$wpdb->prefix}woocommerce_order_items.order_item_id)
						FROM $wpdb->order_itemmeta
						WHERE $wpdb->order_itemmeta.meta_key = '_product_id'
						AND {$wpdb->prefix}woocommerce_order_items.order_item_type = 'line_item'
						AND $wpdb->posts.post_type = 'shop_order' ) as d
					GROUP BY d.product_id
				) as prl_total_purchases ON ($wpdb->posts.ID = prl_total_purchases.product_id)
			";
		}

		$args['orderby'] = "( prl_conversions.conv_count/prl_total_purchases.orders_count ) DESC, $wpdb->posts.post_date DESC";
		$args['groupby'] = "$wpdb->posts.ID";

		return $args;
	}

	/**
	 * Removes any global amp settings.
	 *
	 * @return void
	 */
	public function remove_amp() {
		remove_filter( 'posts_clauses', array( $this, 'add_order_clauses' ) );
	}

	/*---------------------------------------------------*/
	/*  Force methods.                                   */
	/*---------------------------------------------------*/

	/**
	 * Get admin html for filter inputs.
	 *
	 * @param  string|null $post_name
	 * @param  int      $amplifier_index
	 * @param  array    $amplifier_data
	 * @return void
	 */
	public function get_admin_fields_html( $post_name, $amplifier_index, $amplifier_data ) {

		$post_name = ! is_null( $post_name ) ? $post_name : 'prl_engine';

		// Default modifier.
		if ( ! empty( $amplifier_data[ 'modifier' ] ) ) {
			$modifier = $amplifier_data[ 'modifier' ];
		} else {
			$modifier = 'DESC';
		}

		// Default weight.
		if ( ! empty( $amplifier_data[ 'weight' ] ) ) {
			$weight = absint( $amplifier_data[ 'weight' ] );
		} else {
			$weight = 4;
		}

		?>
		<input type="hidden" name="<?php echo esc_attr( $post_name ); ?>[amplifiers][<?php echo esc_attr( $amplifier_index ); ?>][id]" value="<?php echo esc_attr( $this->id ); ?>" />
		<div class="os_row_inner">
			<div class="os_modifier">
				<div class="sw-enhanced-select">
					<select name="<?php echo esc_attr( $post_name ); ?>[amplifiers][<?php echo esc_attr( $amplifier_index ); ?>][modifier]">
						<?php $this->get_modifiers_select_options( $modifier ); ?>
					</select>
				</div>
			</div>
			<div class="os_semi_value">
				<div class="os--disabled"></div>
			</div>
			<div class="os_slider column-wc_actions">
				<?php wc_prl_print_weight_select( $weight, $post_name . '[amplifiers][' . $amplifier_index . '][weight]' ) ?>
			</div>
		</div><?php
	}
}
