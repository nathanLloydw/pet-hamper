<?php
/**
 * The Google Feed Template class.
 *
 * @link       https://rextheme.com
 * @since      1.0.0
 *
 * @package    Rex_Product_Feed
 * @subpackage Rex_Product_Feed/admin/feed-templates/
 */

/**
 *
 * Defines the attributes and template for Google feed.
 *
 * @package    Rex_Product_Feed
 * @subpackage Rex_Product_Feed/admin/feed-templates/Rex_Feed_Template_Google
 * @author     RexTheme <info@rextheme.com>
 */
abstract class Rex_Feed_Abstract_Template {

	/**
	 * The Feed Attributes
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Rex_Feed_Abstract_Template $attributes Feed attributes.
	 */
	protected $attributes;

	/**
	 * WooCommerce Product Meta Keys.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Rex_Feed_Abstract_Template $product_meta_keys Feed attributes.
	 */
	protected $product_meta_keys;

	/**
	 * The Feed Template Mappings Attributes and associated value and other constraints.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Rex_Feed_Abstract_Template $template_mappings Feed attributes mapping for template genaration.
	 */
	protected $template_mappings;

	/**
	 * Data Sanitization options
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Rex_Feed_Abstract_Template $sanitization_options Feed attributes mapping for template genaration.
	 */
	protected $sanitization_options;

	/**
	 * Set the plugin atts and mapping.
	 *
	 * @param bool|array $feed_rules Feed rules.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $feed_rules = false ) {
		$this->init_atts();
		$this->init_template_mappings( $feed_rules );
		$this->init_sanitization_options();
	}

	/**
	 * Return the attributes
	 *
	 * @since    1.0.0
	 */
	public function get_attributes() {
		return $this->attributes;
	}

	/**
	 * Return the template_mappings
	 *
	 * @since    1.0.0
	 */
	public function get_template_mappings() {
		return $this->template_mappings;
	}


	/**
	 * Retrieve markups for product dropdown
	 *
	 * @param string $selected Option already selected.
	 *
	 * @since 1.0.0
	 * @return bool|string|string[]
	 */
	public function print_product_attributes( $selected = '' ) {
		$product_attribute_dropdown = $this->get_feed_cached_dropdown( 'product_attributes_dropdown', $selected );
		if ( false === $product_attribute_dropdown ) {
			$product_attributes = Rex_Feed_Attributes::get_attributes();
			return $this->make_cache_dropdown( 'product_attributes_dropdown', $product_attributes, $selected );
		}
		return $product_attribute_dropdown;
	}


	/**
	 * Get select dropdown from cache
	 *
	 * @param string $key Select field name key.
	 * @param string $selected Option already selected.
	 *
	 * @return bool|string|string[]
	 */
	private function get_feed_cached_dropdown( $key, $selected = '' ) {
		$product_attribute_dropdown = wpfm_get_cached_data( $key );
		$selected                   = esc_attr( $selected );
		if ( $selected && false !== strpos( $product_attribute_dropdown, "value='{$selected}'" ) ) {
			$product_attribute_dropdown = str_replace( "value='{$selected}'", "value='{$selected}' selected", $product_attribute_dropdown );
		}
		return empty( $product_attribute_dropdown ) ? false : $product_attribute_dropdown;
	}


	/**
	 * Make cached dropdown list for future use
	 *
	 * @param string $key Select field name key.
	 * @param array  $items Select items.
	 * @param string $selected Option already selected.
	 *
	 * @return string|string[]
	 */
	private function make_cache_dropdown( $key, $items, $selected = '' ) {
		$drop_down = '';
		$i         = 1;

		foreach ( $items as $group_label => $groups ) {
			if ( !empty( $group_label ) ) {
				$drop_down .= "<optgroup label='" . esc_attr( $group_label ) . "' data-i='" . esc_attr( $i ) . "'>";
			}
			foreach ( $groups as $k => $it ) {
				$drop_down .= "<option value='" . esc_attr( $k ) . "'>" . esc_html( $it ) . "</option>";
			}

			if ( !empty( $group_label ) ) {
				$drop_down .= "</optgroup>";
			}
			$i++;
		}
		wpfm_set_cached_data( $key, $drop_down );
		if ( $selected && false !== strpos( $drop_down, "value='{$selected}'" ) ) {
			$drop_down = str_replace( "value='{$selected}'", "value='{$selected}' selected", $drop_down );
		}
		return $drop_down;
	}


	/**
	 * Print attributes as select dropdown
	 *
	 * @param string $key Select field name key.
	 * @param string $name Select field name.
	 * @param string $selected Option already selected.
	 * @param string $class Select field class.
	 * @param string $multiple Select multiple value.
	 * @param mixed  $array Array.
	 *
	 * @return void
	 * @since    1.0.0
	 */
	public function print_select_dropdown( $key, $name, $selected = '', $class = '', $multiple = '', $array = '' ) {
		if ( 'attr' === $name ) {
			$items = $this->attributes;
		}
		elseif ( 'meta_key' === $name ) {
			$items = $this->product_meta_keys;
		}
		elseif ( 'escape' === $name ) {
			$items = $this->sanitization_options;
		}
		else {
			return;
		}

		echo '<select class="' . esc_attr( $class ) . '" name="fc[' . esc_attr( $key ) . '][' . esc_attr( $name ) . ']' . esc_attr( $array ) . '" ' . esc_attr( $multiple ) . '>';
		echo "<option value='-1' disabled>Please Select</option>";

		$i = 1;
		foreach ( $items as $group_label => $group ) {
			if ( !empty( $group_label ) ) {
				echo "<optgroup label='" . esc_html( $group_label ) . "' data-i='" . esc_attr( $i ) . "'>";
			}

			foreach ( $group as $key => $item ) {
				if ( ( is_array( $selected ) && in_array( $key, $selected ) ) || ( $selected === $key ) ) {
					echo "<option value='" . esc_attr( $key ) . "' selected='selected'>" . esc_attr( $item ) . "</option>";
				}
				else {
					echo "<option value='" . esc_attr( $key ) . "'>" . esc_attr( $item ) . "</option>";
				}
			}

			if ( !empty( $group_label ) ) {
				echo "</optgroup>";
			}
			$i++;
		}

		echo "</select>";
	}


	/**
	 * Print attributes Type
	 *
	 * @param string $key Select field name key.
	 * @param string $select Option already selected.
	 *
	 * @since    1.0.0
	 */
	public function print_attr_type( $key, $select = '' ) {
		$options = apply_filters(
			'wpfm_pro_feed_attribute_type_render',
			array(
				'meta'   => 'Attribute',
				'static' => 'Static',
			)
		);
		echo "<select class='type-dropdown' name='fc[" . esc_attr( $key ) . "][type]'>";
		echo "<option value=''>Please Select</option>";
		foreach ( $options as $key => $option ) {
			$selected = $select === $key ? "selected='selected'" : "";
			echo "<option value='" . esc_attr( $key ) . "' " . esc_html( $selected ) . ">" . esc_html( $option ) . "</option>";
		}
		echo "</select>";
	}

	/**
	 * Print Prefix input
	 *
	 * @since    1.0.0
	 * @param string $key Input field name key.
	 * @param string $name Input field name.
	 * @param string $val Input field value.
	 * @param string $class Input field class.
	 */
	public function print_input( $key, $name = '', $val = '', $class = '' ) {
		echo '<input type="text" class="' . esc_attr( $class ) . '" name="fc[' . esc_attr( $key ) . '][' . esc_attr( $name ) . ']" value="' . esc_attr( $val ) . '">';
	}

	/**
	 * Initialize Product Meta Attributes
	 *
	 * @since    1.0.0
	 */
	protected function init_product_meta_keys() {
		$this->product_meta_keys = Rex_Feed_Attributes::get_attributes();
	}

	/**
	 * Initialize Sanitization Options
	 *
	 * @since    1.0.0
	 */
	protected function init_sanitization_options() {
		$this->sanitization_options = array(
			'' => array(
				'default'                      => 'Default',
				'strip_tags'                   => 'Strip Tags',
				'utf_8_encode'                 => 'UTF-8 Encode',
				'htmlentities'                 => 'htmlentities',
				'integer'                      => 'Integer',
				'price'                        => 'Price',
				'remove_space'                 => 'Remove Space',
				'remove_tab'                   => 'Remove Tab',
				'first_word_uppercase'         => 'First Word Uppercase Only',
				'remove_shortcodes'            => 'Remove ShortCodes',
				'remove_shortcodes_and_tags'   => 'Remove ShortCodes and Strip Tags',
				'remove_special character'     => 'Remove Special Character',
				'cdata'                        => 'CDATA',
				'cdata_without_space'          => 'CDATA without space',
				'remove_underscore'            => 'Remove underscore',
				'decode_url'                   => 'Decode url',
				'remove_decimal'               => 'Remove decimal points (Marktplaats only)',
				'add_two_decimal'              => 'Two decimal points',
				'comma_decimal'                => 'Decimal Separator - Comma (,)',
				'remove_hyphen'                => 'Remove hyphen',
				'remove_hyphen_space'          => 'Remove hyphen(space)',
				'replace_space_with_hyphen'    => 'Replace space ( ) with hyphen (-)',
				'replace_comma_with_backslash' => 'Replace comma (,) with backslash (/)',
				'replace_decimal_with_hyphen'  => 'Replace decimal point (.) with hyphen (-)',
			),
		);
	}


	/**
	 * Initialize Template Mappings with Attributes from feed post_meta.
	 *
	 * @since    1.0.0
	 * @param string $feed_rules The Rules Of Feeds.
	 */
	protected function init_template_mappings( $feed_rules ) {
		if ( !empty( $feed_rules ) ) {
			$this->template_mappings = $feed_rules;
		}else {
			$this->init_default_template_mappings();
		}
	}


	/**
	 * Initialize Attributes
	 *
	 * @since    1.0.0
	 */
	abstract protected function init_atts();

	/**
	 * Initialize Default Template Mappings with Attributes.
	 *
	 * @since    1.0.0
	 */
	abstract protected function init_default_template_mappings();
}
