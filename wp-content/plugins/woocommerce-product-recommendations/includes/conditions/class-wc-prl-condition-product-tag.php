<?php
/**
 * WC_PRL_Condition_Product_Tag class
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Tag condition class.
 *
 * @class    WC_PRL_Condition_Product_Tag
 * @version  2.4.0
 */
class WC_PRL_Condition_Product_Tag extends WC_PRL_Condition {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                     = 'product_tag';
		$this->complexity             = WC_PRL_Condition::LOW_COMPLEXITY;
		$this->title                  = __( 'Product tag', 'woocommerce-product-recommendations' );
		$this->supported_modifiers    = array(
			'in'         => _x( 'in', 'prl_modifiers', 'woocommerce-product-recommendations' ),
			'not-in'     => _x( 'not in', 'prl_modifiers', 'woocommerce-product-recommendations' ),
		);
		$this->supported_engine_types = array( 'product' );
		$this->needs_value            = true;
	}

	/**
	 * Check the condition to the current request.
	 *
	 * @param  array  $data
	 * @param  WC_PRL_deployment  $deployment
	 * @return bool
	 */
	public function check( $data, $deployment ) {

		if ( empty( $data[ 'value' ] ) ) {
			return true;
		}

		if ( ! is_array( $data[ 'value' ] ) ) {
			$data[ 'value' ] = array( $data[ 'value' ] );
		}

		// Shorthands.
		$modifier = $data[ 'modifier' ];
		$tag_ids  = $data[ 'value' ];

		global $product;
		$found_items     = false;
		$product_tag_ids = $product instanceof WC_Product ? $product->get_tag_ids() : array();

		if ( ! empty( $product_tag_ids ) ) {

			$tags_matching = 0;

			foreach ( $product_tag_ids as $product_tag_id ) {
				if ( in_array( $product_tag_id, $tag_ids ) ) {
					$tags_matching++;
				}
			}

			$term_relationship = $this->get_default_term_relationship();

			if ( 'or' === $term_relationship && $tags_matching ) {
				$found_items = true;
			} elseif ( 'and' === $term_relationship && sizeof( $data[ 'value' ] ) === $tags_matching ) {
				$found_items = true;
			}
		}

		if ( $found_items ) {
			return $this->modifier_is( $modifier, 'in' );
		} else {
			return $this->modifier_is( $modifier, 'not-in' );
		}
	}

	/*---------------------------------------------------*/
	/*  Force methods.                                   */
	/*---------------------------------------------------*/

	/**
	 * Get admin html for filter inputs.
	 *
	 * @param  string|null $post_name
	 * @param  int      $condition_index
	 * @param  array    $condition_data
	 * @return void
	 */
	public function get_admin_fields_html( $post_name, $condition_index, $condition_data ) {

		$post_name    = ! is_null( $post_name ) ? $post_name : 'prl_deploy';
		$product_tags = ( array ) get_terms( 'product_tag', array( 'get' => 'all' ) );
		$modifier     = '';
		$tags         = array();

		// Default modifier.
		if ( ! empty( $condition_data[ 'modifier' ] ) ) {
			$modifier = $condition_data[ 'modifier' ];
		} else {
			$modifier = 'max';
		}

		if ( isset( $condition_data[ 'value' ] ) ) {
			$tags = is_array( $condition_data[ 'value' ] ) ? $condition_data[ 'value' ] : array( $condition_data[ 'value' ] );
		}

		?>
		<input type="hidden" name="<?php echo esc_attr( $post_name ); ?>[conditions][<?php echo esc_attr( $condition_index ); ?>][id]" value="<?php echo esc_attr( $this->id ); ?>" />
		<div class="os_row_inner">
			<div class="os_modifier">
				<div class="sw-enhanced-select">
					<select name="<?php echo esc_attr( $post_name ); ?>[conditions][<?php echo esc_attr( $condition_index ); ?>][modifier]">
						<?php $this->get_modifiers_select_options( $modifier ); ?>
					</select>
				</div>
			</div>
			<div class="os_value">
				<select name="<?php echo esc_attr( $post_name ); ?>[conditions][<?php echo esc_attr( $condition_index ); ?>][value][]" class="multiselect sw-select2" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select tags&hellip;', 'woocommerce-product-recommendations' ); ?>">
					<?php
						foreach ( $product_tags as $product_tag ) {
							echo '<option value="' . esc_attr( $product_tag->term_id ) . '" ' . selected( in_array( $product_tag->term_id, $tags ), true, false ) . '>' . esc_html( $product_tag->name ) . '</option>';
                        }
					?>
				</select>
			</div>
		</div>
		<?php
	}
}
