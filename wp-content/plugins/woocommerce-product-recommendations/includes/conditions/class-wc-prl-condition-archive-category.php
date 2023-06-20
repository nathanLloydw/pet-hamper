<?php
/**
 * WC_PRL_Condition_Archive_Category class
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Archive Category condition class.
 *
 * @class    WC_PRL_Condition_Archive_Category
 * @version  2.4.0
 */
class WC_PRL_Condition_Archive_Category extends WC_PRL_Condition {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id                     = 'archive_category';
		$this->complexity             = WC_PRL_Condition::LOW_COMPLEXITY;
		$this->title                  = __( 'Archive category', 'woocommerce-product-recommendations' );
		$this->supported_modifiers    = array(
			'in'     => _x( 'in', 'prl_modifiers', 'woocommerce-product-recommendations' ),
			'not-in' => _x( 'not in', 'prl_modifiers', 'woocommerce-product-recommendations' )
		);
		$this->supported_engine_types = array( 'archive' );
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

		global $product;
		$found           = false;
		$current_archive = get_queried_object();
		if ( ! ( $current_archive instanceof WP_Term ) || 'product_cat' !== $current_archive->taxonomy ) {
			return false;
		}

		$data[ 'value' ] = array_map( 'absint', $data[ 'value' ] );

		foreach ( $data[ 'value' ] as $cat_id ) {
			if ( $cat_id === $current_archive->term_id ) {
				$found = true;
				break;
			}
		}

		if ( $found ) {
			return $this->modifier_is( $data[ 'modifier' ], 'in' );
		} else {
			return $this->modifier_is( $data[ 'modifier' ], 'not-in' );
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

		$post_name          = ! is_null( $post_name ) ? $post_name : 'prl_deploy';
		$product_categories = ( array ) get_terms( 'product_cat', array( 'get' => 'all' ) );
		$modifier           = '';
		$categories         = array();

		// Default modifier.
		if ( ! empty( $condition_data[ 'modifier' ] ) ) {
			$modifier = $condition_data[ 'modifier' ];
		} else {
			$modifier = 'max';
		}

		if ( isset( $condition_data[ 'value' ] ) ) {
			$categories = is_array( $condition_data[ 'value' ] ) ? $condition_data[ 'value' ] : array( $condition_data[ 'value' ] );
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
				<select name="<?php echo esc_attr( $post_name ); ?>[conditions][<?php echo esc_attr( $condition_index ); ?>][value][]" class="multiselect sw-select2" multiple="multiple" data-placeholder="<?php esc_attr_e( 'Select categories&hellip;', 'woocommerce-product-recommendations' ); ?>">
					<?php
						foreach ( $product_categories as $product_category ) {
							echo '<option value="' . esc_attr( $product_category->term_id ) . '" ' . selected( in_array( $product_category->term_id, $categories ), true, false ) . '>' . esc_html( $product_category->name ) . '</option>';
                        }
					?>
				</select>
			</div>
		</div>
		<?php
	}
}
