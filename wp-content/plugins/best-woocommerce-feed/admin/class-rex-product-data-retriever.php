<?php
/**
 * Class for retriving product data based on user selected feed configuration.
 *
 * Get the product data based on feed config selected by user.
 *
 * @package    Rex_Product_Data_Retriever
 * @subpackage Rex_Product_Feed/admin
 * @author     RexTheme <info@rextheme.com>
 */

use Wdr\App\Controllers\ManageDiscount;
use Wdr\App\Models\DBTable;
use Aelia\WC\CurrencySwitcher\WC_Aelia_CurrencySwitcher;

class Rex_Product_Data_Retriever
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    protected $feed_rules;
    /**
     * @var string $feed_id The id of the feed
     */
    protected $analytics_params;

    /**
     * Contains all available meta keys for products.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    protected $product_meta_keys;

    /**
     * The data of product retrived by feed_rules.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $data The current version of this plugin.
     */
    protected $data;


    /**
     * Metabox instance of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      object $metabox The current metabox of this plugin.
     */
    protected $product;

    /**
     * Variant atts for feed.
     *
     * @since    1.0.0
     * @access   private
     * @var      object $metabox The current metabox of this plugin.
     */
    protected $variant_atts = array( 'color', 'pattern', 'material', 'age_group', 'gender', 'size', 'size_type', 'size_system' );

    /**
     * Additional images of current product.
     *
     * @since    1.0.0
     * @access   private
     * @var      object $metabox The current metabox of this plugin.
     */
    protected $additional_images = array();


    /**
     * Append variation
     *
     * @since    3.2
     * @access   private
     * @var      object $append_variation
     */
    protected $append_variation;


    protected $aelia_currency;

    protected $wmc_currency;


    /**
     * check if debug is enabled
     *
     * @var Rex_Product_Data_Retriever $enable_log
     */
    protected $is_logging_enabled;


    /**
     * @var Rex_Product_Data_Retriever $feed
     */
    protected $feed;

    protected $wcml;

    protected $wcml_currency;

    public $discount_manage;

    protected $feed_format;

    /**
     * @desc Variable for feed country
     * @since 7.2.9
     * @var $feed_country
     */
    protected $feed_country;

    /**
     * @desc Variable for feed zip code
     * @since 7.2.18
     * @var $feed_zip
     */
    protected $feed_zip_codes;

    /**
     * Initialize the class and set its properties.
     *
     * Rex_Product_Data_Retriever constructor.
     * @param WC_Product $product
     * @param Rex_Product_Feed_Abstract_Generator $feed
     * @param $product_meta_keys
     * @since 6.1.0
     */
    public function __construct( WC_Product $product, Rex_Product_Feed_Abstract_Generator $feed, $product_meta_keys )
    {
        $this->is_logging_enabled = is_wpfm_logging_enabled();
        $this->product            = $product;
        $this->analytics_params   = $feed->analytics_params;
        $this->feed_config        = $feed->feed_config;
        $this->feed_rules         = $feed->feed_rules;
        $this->product_meta_keys  = $product_meta_keys;
        $this->append_variation   = $feed->append_variation;
        $this->aelia_currency     = $feed->aelia_currency;
        $this->wmc_currency       = $feed->wmc_currency;
        $this->wcml_currency      = $feed->wcml_currency;
        $this->feed               = $feed;
        $this->wcml               = in_array( 'woocommerce-multilingual/wpml-woocommerce.php', get_option( 'active_plugins', [] ) );
        $this->feed_format        = $feed->get_feed_format();
        $this->feed_country       = $feed->get_shipping();
        $this->feed_zip_codes     = $feed->get_zip_code();

        $log = wc_get_logger();
        if( $this->is_logging_enabled ) {
            $log->info( '*************************', array( 'source' => 'WPFM', ) );
            $log->info( __( 'Start product processing.', 'rex-product-feed' ), array( 'source' => 'WPFM', ) );
            $log->info( 'Product ID: ' . $this->product->get_id(), array( 'source' => 'WPFM', ) );
            $log->info( 'Product Name: ' . $this->product->get_title(), array( 'source' => 'WPFM', ) );
        }

        $this->set_all_value();

        if( $this->is_logging_enabled ) {
            $log->info( __( 'End product processing.', 'rex-product-feed' ), array( 'source' => 'WPFM', ) );
            $log->info( '*************************', array( 'source' => 'WPFM', ) );
        }
    }


    /**
     * Setup Testing feed rules for every attributes.
     * Just to check if this class return proper values.
     *
     * @since    1.0.2
     */
    public function set_test_feed_rules()
    {
        $this->feed_config = array();
        foreach( $this->product_meta_keys as $key_cat => $attrs ) {
            foreach( $attrs as $key => $attr ) {
                $this->feed_config[] = array(
                    'attr'      => $key,
                    'cust_attr' => $key,
                    'type'      => 'meta',
                    'meta_key'  => $key,
                    'st_value'  => '',
                    'prefix'    => '',
                    'suffix'    => '',
                    'escape'    => 'default',
                    'limit'     => 0,
                );
            }
        }
    }


    public function get_random_key()
    {
        return md5( uniqid( rand(), true ) );
    }

    /**
     * Retrive and setup all data for every feed rules.
     *
     * @since    1.0.0
     */
    public function set_all_value()
    {
        $this->data = array();

        foreach( $this->feed_config as $key => $rule ) {
            $value = $this->set_val( $rule );
            $value = $this->maybe_processing_needed( $value, $rule );

            if( array_key_exists( 'attr', $rule ) ) {
                if( $rule[ 'attr' ] ) {
                    if( $rule[ 'attr' ] === 'attributes' ) {
                        $this->data[ $rule[ 'attr' ] ][] = array(
                            'name'  => str_replace( 'bwf_attr_pa_', '', $rule[ 'meta_key' ] ),
                            'value' => $value
                        );
                    }
                    else {
                        $google_shipping_attr = array( 'shipping_country', 'shipping_region', 'shipping_service', 'shipping_price' );
                        if( in_array( $rule[ 'attr' ], $google_shipping_attr ) && $this->feed->merchant === 'google' ) {
                            $this->data[ $rule[ 'attr' ] ][] = $value;
                        }
                        else {
                            $this->data[ $rule[ 'attr' ] ] = $value;
                        }
                    }
                }
            }
            elseif( array_key_exists( 'cust_attr', $rule ) ) {
                if( $rule[ 'cust_attr' ] ) {
                    $this->data[ $rule[ 'cust_attr' ] ] = $value;
                }
            }
            else {
                $this->data[ $rule[ 'attr' ] ] = $value;
            }
        }
    }


    /**
     * Set value for a single feed rule.
     *
     * @since    1.0.0
     */
    public function set_val( $rule )
    {
        $val = '';

        if ( isset( $rule[ 'meta_key' ] ) && isset( $rule[ 'type' ] ) ) {
            if( 'static' === $rule[ 'type' ] ) {
                $val = $rule[ 'st_value' ];
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_primary_attr( $rule[ 'meta_key' ] ) ) {
                $escape = isset( $rule[ 'escape' ] ) ? $rule[ 'escape' ] : '';
                $val    = $this->set_pr_att( $rule[ 'meta_key' ], $escape );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_woodmart_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_woodmart_att( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_price_attr( $rule[ 'meta_key' ] ) ) {
                $val  = $this->set_price_attr( $rule[ 'meta_key' ], $rule );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_yoast_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_yoast_attr( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_rankmath_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_rankmath_attr( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_perfect_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_perfect_attr( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_wc_brand_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_wc_brand_attr( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_berocket_brand_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_berocket_brand_attr( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_image_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_image_att( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_product_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_product_attr( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_product_dynamic_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_product_dynamic_attr( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_wpfm_custom_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_wpfm_custom_att( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_product_custom_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_product_custom_att( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_product_category_mapper_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_cat_mapper_att( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_glami_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_glami_att( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_dropship_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_dropship_att( $rule[ 'meta_key' ] );
            }
            elseif( 'meta' === $rule[ 'type' ] && $this->is_date_attr( $rule[ 'meta_key' ] ) ) {
                $val = $this->set_date_attr( $rule[ 'meta_key' ] );
            }
            elseif ( 'meta' === $rule['type'] && $this->is_product_custom_tax( $rule['meta_key'] ) ) {
                $val = $this->set_product_custom_tax( $rule['meta_key']  );
            }
            elseif ( 'meta' === $rule['type'] && $this->is_woo_discount_rules( $rule['meta_key'] ) ) {
                $val = $this->set_woo_discount_rules( $rule['meta_key']  );
            }
            elseif ( 'meta' === $rule['type'] && $this->is_shipping_attr( $rule['meta_key'] ) ) {
                $val = $this->set_shipping_attr( $rule['meta_key'], $rule  );
            }
            elseif ( 'meta' === $rule['type'] && $this->is_tax_attr( $rule['meta_key'] ) ) {
                $val = $this->set_tax_attr( $rule['meta_key']  );
            }
            elseif ( 'meta' === $rule['type'] && $this->is_discount_price_by_asana_attr( $rule['meta_key'] ) ) {
                $val = $this->set_discount_price_by_asana_attr( $rule['meta_key'], $rule  );
            }
            elseif ( 'meta' === $rule['type'] && $this->is_ean_by_wc_attr( $rule['meta_key'] ) ) {
                $val = $this->set_ean_by_wc_attr( $rule['meta_key']  );
            }
        }
        return $val;
    }


    /**
     * Return all data.
     *
     * @since    1.0.0
     */
    public function get_all_data()
    {
        return $this->data;
    }

    /**
     * Set a woodmart gallery attribute.
     *
     * @since    1.0.0
     */
    protected function set_woodmart_att( $key )
    {
        $key = str_replace( 'woodmart_', '', $key );
        $id  = substr( $key, strpos( $key, "_" ) + 1 );
        if( 'image_' . $id == $key ) {
            return $this->get_woodmart_gallery( $id );
        }
        return '';
    }

    /**
     * Set a YOAST attribute.
     *
     * @since    1.0.0
     */
    protected function set_yoast_attr( $key )
    {
        switch( $key ) {
            case 'yoast_primary_cat':
                return $this->get_seo_primary_cat( 'yoast' );

            case 'yoast_primary_cat_id':
                return $this->get_seo_primary_cat( 'yoast', true );

            case 'yoast_title':
                return preg_replace( '/\s+/', ' ', $this->get_yoast_seo_title() );

            case 'yoast_meta_desc':
                return $this->get_yoast_meta_description();

            case 'yoast_primary_cats_path':
                return $this->get_yoast_product_cats_with_seperator();

            case 'yoast_primary_cats_pipe':
                return $this->get_yoast_product_cats_with_seperator( '', ' | ', '' );

            case 'yoast_primary_cats_comma':
                return $this->get_yoast_product_cats_with_seperator( '', ', ', '' );

            default:
                return '';
        }
    }

    /**
     * @desc Set a RankMath attribute.
     * @since 7.2.20
     * @param $key
     * @return false|string
     */
    protected function set_rankmath_attr( $key )
    {
        switch( $key ) {
            case 'rankmath_primary_cat':
                return $this->get_seo_primary_cat( 'rankmath' );

            case 'rankmath_primary_cat_id':
                return $this->get_seo_primary_cat( 'rankmath', true );

            default:
                return '';
        }
    }


    /**
     * get a woodmart gallery attribute.
     *
     * @since    1.0.0
     */
    public function get_woodmart_gallery( $id )
    {
        $product_id = $this->product->get_id();
        if( 'WC_Product_Variation' == get_class( $this->product ) ) {
            $parent_id   = $this->product->get_parent_id();
            $all_gallery = get_post_meta( $parent_id, 'woodmart_variation_gallery_data', true );
            if( isset( $all_gallery[ $product_id ] ) ) {
                $image_ids = $all_gallery[ $product_id ];
                if( $image_ids ) {
                    $image_ids = explode( ',', $image_ids );
                    if( isset( $image_ids[ $id ] ) ) {
                        $image_id = $image_ids[ $id ];
                        if( $image_id ) {
                            return wp_get_attachment_url( $image_id );
                        }
                    }
                }
            }
        }
        return '';
    }

    /**
     * Set Perfect woocommerce brand attribute
     */
    protected function set_perfect_attr( $key )
    {
        $brands = '';

        if( 'WC_Product_Variation' == get_class( $this->product ) ) {
            $brands = wp_get_post_terms( $this->product->get_parent_id(), 'pwb-brand', array( "fields" => "all" ) );
        }
        else {
            $brands = wp_get_post_terms( $this->product->get_id(), 'pwb-brand', array( "fields" => "all" ) );
        }

        $brnd = '';
        $i    = 0;
        foreach( $brands as $brand ) {
            if( $i == 0 ) {
                $brnd .= $brand->name;
            }
            else {
                $brnd .= ', ' . $brand->name;
            }
            $i++;
        }
        return $brnd;
    }


    /**
     * Set woocommerce brand attribute
     * @param key meta_key
     */
    protected function set_wc_brand_attr( $key )
    {
        $brands = '';

        if( 'WC_Product_Variation' == get_class( $this->product ) ) {
            $brands = wp_get_post_terms( $this->product->get_parent_id(), 'product_brand', array( "fields" => "all" ) );
        }
        else {
            $brands = wp_get_post_terms( $this->product->get_ID(), 'product_brand', array( "fields" => "all" ) );
        }

        $brnd = '';
        if( !empty( $brands ) ) {

            $i = 0;
            foreach( $brands as $brand ) {
                if( $i == 0 ) {
                    $brnd .= $brand->name;
                }
                else {
                    $brnd .= ', ' . $brand->name;
                }
                $i++;
            }
        }

        return $brnd;
    }


    /**
     * Set woocommerce brand attribute
     * @param key meta_key
     */
    protected function set_berocket_brand_attr( $key )
    {
        $brands = '';

        if( 'WC_Product_Variation' == get_class( $this->product ) ) {
            $brands = wp_get_post_terms( $this->product->get_parent_id(), 'berocket_brand', array( "fields" => "all" ) );

        }
        else {
            $brands = wp_get_post_terms( $this->product->get_ID(), 'berocket_brand', array( "fields" => "all" ) );
        }
        $brnd = '';
        if( !empty( $brands ) ) {

            $i = 0;
            foreach( $brands as $brand ) {
                if( $i == 0 ) {
                    $brnd .= $brand->name;
                }
                else {
                    $brnd .= ', ' . $brand->name;
                }
                $i++;
            }

        }

        return $brnd;
    }


    /**
     * Set a primary attribute.
     *
     * @since    1.0.0
     */
    protected function set_pr_att( $key, $rule = 'default' )
    {

        switch( $key ) {
            case 'id':
                return $this->product->get_id();
                break;

            case 'sku':
                return $this->product->get_sku();
                break;

            case 'parent_sku':
                $pr_id = '';
                if( $this->product->is_type( 'variation' ) ) {
                    $parent_id         = $this->product->get_parent_id();
                    $wc_parent_product = wc_get_product( $parent_id );

                    $pr_id = $wc_parent_product->get_sku();

                }
                else {

                    $pr_id = $this->product->get_sku();
                }
                return $pr_id;
                break;

            case 'title':
                if( !$this->append_variation ) {
                    if( $this->product->is_type( 'variation' ) ) {
                        $title = $this->product->get_title();
                        return $title;
                    }

                    $title = $this->product->get_name();
                    return $title;
                }
                else {
                    if( $this->is_children() ) {
                        $_product     = wc_get_product( $this->product );
                        $attr_summary = $_product->get_attribute_summary();
                        $attr_array   = explode( ",", $attr_summary );

                        $each_child_attr = [];
                        foreach( $attr_array as $ata ) {
                            $attr              = strpbrk( $ata, ":" );
                            $each_child_attr[] = $attr;
                        }
                        $each_child_attr_two = [];
                        foreach( $each_child_attr as $eca ) {
                            $each_child_attr_two[] = str_replace( ": ", " ", $eca );
                        }

                        $_title = $this->product->get_title() . " - ";
                        $_title = $_title . implode( ', ', $each_child_attr_two );
                        return $_title;
                    }
                    else {
                        $title = $this->product->get_name();
                        return $title;
                    }
                }
            case 'description':
                if( ( $this->is_children() ) ):
                    $description = $this->product->get_description();
                    if( empty( $description ) ) {
                        $_product = wc_get_product( $this->product->get_parent_id() );
                        if( is_object( $_product ) ) {

                            return $this->remove_short_codes( $_product->get_description() );
                        }
                    }
                    else {
                        return $this->remove_short_codes( $description );
                    }
                else:
                    // $des = preg_replace('/(?:\s\s+|\n|\t)/', ' ',$this->product->get_description());
                    return $this->remove_short_codes( $this->product->get_description() );
                endif;

                break;

            case 'parent_desc':
                if( $this->is_children() ) {
                    $parent_product = wc_get_product( $this->product->get_parent_id() );

                    if( is_object( $parent_product ) ) {
                        return $this->remove_short_codes( $parent_product->get_description() );
                    }
                }

                return $this->product->get_description();
                break;

            case 'short_description':
                if( ( $this->is_children() ) ):
                    $short_description = $this->product->get_short_description();
                    if( empty( $short_description ) ) {
                        $_product = wc_get_product( $this->product->get_parent_id() );
                        if( is_object( $_product ) ) {

                            return $this->remove_short_codes( $_product->get_short_description() );
                        }
                    }
                    else {

                        return $this->remove_short_codes( $short_description );
                    }
                else:
                    return $this->remove_short_codes( $this->product->get_short_description() );
                endif;
                break;

            case 'product_cats':

                return $this->get_product_cats( 'product_cat' );

            case 'product_cat_ids':

                return $this->get_product_cat_ids( 'product_cat' );

            case 'product_cats_path':
                return $this->get_product_cats_with_seperator( 'product_cat' );

            case 'product_cats_path_pipe':
                return $this->get_product_cats_with_seperator('product_cat','', ' | ', '');

            case 'product_subcategory':
                return $this->get_product_subcategory();

            case 'product_tags':
                return $this->get_product_tags();

            case 'spartoo_product_cats':
                return $this->get_spartoo_product_cats();

            case 'sooqr_cats':
                return $this->get_product_cats_for_sooqr();

            case 'perfect_brand':
                $brand = get_products_brands( $this->product->get_id() );
                return $this->product->get_id();

            case 'link':
            case 'review_url':
                $permalink = $this->product->get_permalink();
                if ( function_exists( 'wpfm_is_wpml_active' ) && wpfm_is_wpml_active() ) {
                    $permalink = apply_filters( 'wpml_permalink', $permalink, $this->feed->wpml_language );
                }

                if( $this->analytics_params ) {
                    if( !empty( $this->analytics_params[ 'utm_source' ] ) &&
                        !empty( $this->analytics_params[ 'utm_medium' ] ) &&
                        !empty( $this->analytics_params[ 'utm_campaign' ] )
                    ) {
                        if( is_array( $rule ) && in_array( 'decode_url', $rule, true ) ) {
                            return add_query_arg( array_filter( $this->analytics_params ), urldecode($permalink));
                        }
                        return $this->safeCharEncodeURL(add_query_arg( array_filter( $this->analytics_params ), urldecode($permalink) ));
                    }
                    if( is_array( $rule ) && in_array( 'decode_url', $rule, true ) ) {
                        return urldecode($permalink);
                    }
                    return $this->safeCharEncodeURL(urldecode($permalink));
                }
                if( is_array( $rule ) && in_array( 'decode_url', $rule, true ) ) {
                    return urldecode($permalink);
                }

                return $this->safeCharEncodeURL(urldecode($permalink));

            case 'parent_url':
                $_pr = $this->product;
                if( 'WC_Product_Variation' == get_class( $this->product ) ) {
                    $_pr = wc_get_product( $this->product->get_parent_id() );
                }
                if( $this->analytics_params ) {
                    if( !empty( $this->analytics_params[ 'utm_source' ] ) &&
                        !empty( $this->analytics_params[ 'utm_medium' ] ) &&
                        !empty( $this->analytics_params[ 'utm_campaign' ] )
                    ) {
                        if( is_array( $rule ) && in_array( 'decode_url', $rule, true ) ) {
                            return add_query_arg( array_filter( $this->analytics_params ), urldecode( $_pr->get_permalink() ) );
                        }
                        return $this->safeCharEncodeURL( add_query_arg( array_filter( $this->analytics_params ), urldecode( $_pr->get_permalink() ) ) );
                    }
                    if( is_array( $rule ) && in_array( 'decode_url', $rule, true ) ) {
                        return urldecode( $_pr->get_permalink() );
                    }
                    return $this->safeCharEncodeURL( urldecode( $_pr->get_permalink() ) );
                }
                if( is_array( $rule ) && in_array( 'decode_url', $rule, true ) ) {
                    return urldecode( $_pr->get_permalink() );
                }
                return $this->safeCharEncodeURL( urldecode( $_pr->get_permalink() ) );

            case 'condition':
                return $this->get_condition();

            case 'item_group_id':
                return $this->get_item_group_id();

            case 'availability':
                return $this->get_availability();

            case 'availability_zero_three':
                $if_available = $this->get_availability();
                if( 'out_of_stock' == $if_available ) {
                    return '3';
                }
                return '0';

            case 'availability_zero_one':
                $if_available = $this->get_availability();
                if( 'out_of_stock' == $if_available ) {
                    return '0';
                }
                return '1';

            case 'availability_underscore':
                return $this->get_availability_underscore();

            case 'availability_backorder_instock':
                return $this->get_availability_backorder_instock();

            case 'availability_backorder':
                return $this->get_availability_backorder();

            case 'quantity':
                return $this->product->get_stock_quantity();

            case 'weight':
                return $this->product->get_weight();
                break;

            case 'width':
                return $this->product->get_width();

            case 'height':
                return $this->product->get_height();

            case 'length':
                return $this->product->get_length();

            case 'type':
                return $this->product->get_type();

            case 'in_stock':
                return $this->get_stock();

            case 'rating_average':
                return $this->product->get_average_rating();

            case 'rating_total':
                return $this->product->get_rating_count();

            case 'identifier_exists':
                return $this->calculate_identifier_exists( $this->data );

            case 'current_page':
                $product_id = '';
                if( $this->product->is_type( 'variation' ) ) {
                    $product_id = $this->product->get_parent_id();
                }
                else {
                    $product_id = $this->product->get_id();
                }
                return get_permalink( $product_id );

            case 'author_name':
                $author_id = '';
                if( $this->product->is_type( 'variation' ) ) {
                    $author_id = get_post_field( 'post_author', $this->product->get_parent_id() );
                }
                else {
                    $author_id = get_post_field( 'post_author', $this->product->get_id() );
                }
                return get_the_author_meta( 'display_name', $author_id );

            case 'author_url':
                $author_id = '';
                if( $this->product->is_type( 'variation' ) ) {
                    $author_id = get_post_field( 'post_author', $this->product->get_parent_id() );
                }
                else {
                    $author_id = get_post_field( 'post_author', $this->product->get_id() );
                }
                return get_author_posts_url( $author_id );

            default:
                return '';
                break;
        }
    }


    /**
     * @desc Get shipping and tax attributes value
     * @param $key
     * @param $rule
     * @return array|float|int|mixed|string
     * @since 7.2.9
     */
    protected function set_shipping_attr( $key, $rule ) {
        switch ( $key ) {
            case 'shipping':
                $methods = $this->get_shipping_methods();
                return $this->add_class_no_class_cost( $methods, $rule );

            case 'shipping_class':
                if ( $this->product->get_shipping_class_id() ) {
                    $shipping_class_term = get_term( (int)$this->product->get_shipping_class_id() );
                    return isset( $shipping_class_term->slug ) ? $shipping_class_term->slug : '';
                }
                return '';

            case 'shipping_cost':
                return $this->get_shipping_cost();

            case 'shipping_class_cost':
                return $this->get_shipping_cost('class_cost_' );

            case 'shipping_no_class_cost':
                return $this->get_shipping_cost( 'no_class_cost' );

            case 'shipping_cost_base_class':
                return (float)$this->get_shipping_cost() + (float)$this->get_shipping_cost('class_cost_' );

            case 'shipping_cost_base_no_class':
                return (float)$this->get_shipping_cost() + (float)$this->get_shipping_cost('no_class_cost' );

            case 'local_pickup_cost':
                return $this->get_shipping_cost( 'local_pickup_cost' );

            default:
                return '';
        }
    }


    /**
     * @desc getting individual shipping cost value
     * @since 7.2.17
     * @param $type
     * @return int|mixed|string
     */
    private function get_shipping_cost( $type = 'cost' ) {
        if( !$this->product || is_wp_error( $this->product ) ) {
            return;
        }
        if( $this->product->is_virtual() || $this->product->is_downloadable() ) {
            return 0;
        }

        $shipping_cost = '';
        $country_data  = explode( ':', $this->feed_country );
        $state         = isset( $country_data[ 0 ] ) ? $country_data[ 0 ] : '';
        $country       = isset( $country_data[ 1 ] ) ? $country_data[ 1 ] : '';
        $continent     = isset( $country_data[ 2 ] ) ? $country_data[ 2 ] : '';

        $shipping_methods = wpfm_get_cached_data( 'wc_shipping_methods_' . $continent . $country . $state . $this->feed_zip_codes );

        if( function_exists( 'wc_get_shipping_zone' ) && !$shipping_methods ) {
            $shipping_zone    = wc_get_shipping_zone( [
                'destination' => [
                    'country'  => $country,
                    'state'    => $state,
                    'postcode' => $this->feed_zip_codes,
                ]
            ] );
            $shipping_methods = $shipping_zone ? $shipping_zone->get_shipping_methods( true ) : [];
            wpfm_set_cached_data( 'wc_shipping_methods_' . $continent . $country . $state . $this->feed_zip_codes, $shipping_methods );
        }

        if( is_array( $shipping_methods ) && !empty( $shipping_methods ) ) {
            foreach( $shipping_methods as $method ) {
                if( 'local_pickup_cost' !== $type ) {
                    if( 'WC_Shipping_Flat_Rate' === get_class( $method ) ) {
                        $shipping_rates = isset( $method->instance_settings ) ? $method->instance_settings : [];
                        if( isset( $shipping_rates[ $type ] ) ) {
                            $shipping_cost = $shipping_rates[ $type ];
                        }
                        else {
                            $shipping_id = $this->product->get_shipping_class_id();
                            if( $shipping_id && isset( $shipping_rates[ $type . $shipping_id ] ) ) {
                                $shipping_cost = $shipping_rates[ $type . $shipping_id ];
                            }
                        }
                    }
                    elseif( 'WC_Shipping_Free_Shipping' === get_class( $method ) && isset( $method->min_amount ) && $this->product->get_price() >= $method->min_amount && ( 'min_amount' === $method->requires || 'either' === $method->requires ) ) {
                        $shipping_cost = 0;
                    }
                }
                elseif( 'WC_Shipping_Local_Pickup' === get_class( $method ) ) {
                    $shipping_rates = isset( $method->instance_settings ) ? $method->instance_settings : [];
                    if( isset( $shipping_rates[ 'cost' ] ) ) {
                        $shipping_cost = $shipping_rates[ 'cost' ];
                    }
                }
            }
        }

        return $shipping_cost;
    }


    /**
     * @desc Get tax attributes value
     * @since 7.2.10
     * @param $key
     * @return int|string
     */
    protected function set_tax_attr( $key ) {
        switch ( $key ) {
            case 'tax_class':
                return $this->product ? $this->product->get_tax_class() : '';

            case 'tax':
                $tax_class = $this->product ? $this->product->get_tax_class() : '';
                $tax_rates = wpfm_get_cached_data( 'wc_tax_rates_' . $tax_class );
                return $tax_rates ?: WC_Tax::get_rates_for_tax_class( $tax_class );
            default:
                return '';
        }
    }


    /**
     * @desc Get EAN attribute value by EAN by WooCommerce
     * @since 7.2.19
     * @param $key
     * @return mixed|string
     */
    protected function set_ean_by_wc_attr( $key ) {
        if( '_alg_ean' === $key && $this->product && !is_wp_error( $this->product ) ) {
            return get_post_meta( $this->product->get_id(), $key, true );
        }
        return '';
    }


    /**
     * @desc Get discounted price by Discount Rules and Dynamic Pricing for WooCommerce
     * @param $key
     * @return mixed|string
     * @throws Exception
     * @since 7.2.20
     */
    protected function set_discount_price_by_asana_attr( $key, $rule = [] ) {
        if( is_wp_error( $this->product ) || !$this->product ) {
            return '';
        }
        $key = str_replace( 'asana_', '', $key );
        $price = $this->set_price_attr( $key, $rule );
        if( $price ) {
            $price = Rex_Feed_Discount_Rules_Asana_Plugins::get_discounted_price( $this->product->get_id(), ( float )$price );
            return $price ?: '';
        }
        return '';
    }

    /**
     * Set a price attribute.
     *
     * @throws Exception
     * @since    1.0.0
     */
    protected function set_price_attr( $key, $rule = array() )
    {
        switch( $key ) {
            case 'price':
                if( $this->product->is_type( 'grouped' ) ) {
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( rex_feed_get_grouped_price( $this->product, '_regular_price' ), wc_get_price_decimals() ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;

                        if( !empty( $_custom_prices[ '_regular_price' ] ) && $_custom_prices[ '_regular_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_regular_price' ];
                        }

                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';
                    }
                    else {
                        $_price = rex_feed_get_grouped_price( $this->product, '_regular_price' );
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        $_price = $_price > 0 ? $_price : '';
                        return wc_format_decimal( $_price, wc_get_price_decimals() );
                    }
                }
                elseif( $this->product->is_type( 'composite' ) ) {
                    $_pr = new WC_Product_Composite( $this->product->get_id() );
                    if( $this->wcml ) {
                        global $woocommerce_wpml;

                        if( is_plugin_active( 'wpc-composite-products/wpc-composite-products.php' ) ) {
                            $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_pr->get_composite_price(), wc_get_price_decimals() ), $this->wcml_currency );
                        }
                        else {
                            $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_pr->get_composite_regular_price(), wc_get_price_decimals() ), $this->wcml_currency );
                        }

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;

                        if( !empty( $_custom_prices[ '_regular_price' ] ) && $_custom_prices[ '_regular_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_regular_price' ];
                        }

                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                        return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';
                    }
                    else {
                        if( is_plugin_active( 'wpc-composite-products/wpc-composite-products.php' ) ) {
                            $_price = $_pr->get_composite_price();
                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                            $_price = $_price > 0 ? $_price : '';
                            return wc_format_decimal( $_price, wc_get_price_decimals() );
                        }
                        else {
                            $_price = $_pr->get_composite_regular_price();
                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                            $_price = $_price > 0 ? $_price : '';
                            return wc_format_decimal( $_price, wc_get_price_decimals() );
                        }
                    }
                }
                elseif( $this->product->is_type( 'variable' ) ) {
                    $default_attributes = rex_feed_get_default_variable_attributes( $this->product );

                    if( $default_attributes ) {
                        $variation_id = rex_feed_find_matching_product_variation( $this->product, $default_attributes );
                        if( $variation_id ) {
                            $_variation_product = wc_get_product( $variation_id );
                            if( $this->wcml ) {
                                global $woocommerce_wpml;
                                $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_variation_product->get_regular_price(), wc_get_price_decimals() ), $this->wcml_currency );
                                //if WCML price is set manually
                                $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $variation_id, $this->wcml_currency ) : $_price;
                                if( !empty( $_custom_prices[ '_regular_price' ] ) && $_custom_prices[ '_regular_price' ] > 0 ) {
                                    $_price = $_custom_prices[ '_regular_price' ];
                                }

                                $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                                $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                                return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';
                            }
                            else {
                                $_price = $_variation_product->get_regular_price();
                                $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                                $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                                $_price = $_price > 0 ? $_price : '';
                                return wc_format_decimal( $_price, wc_get_price_decimals() );
                            }
                        }
                        if( $this->wcml ) {
                            global $woocommerce_wpml;
                            $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $this->product->get_variation_regular_price(), wc_get_price_decimals() ), $this->wcml_currency );

                            //if WCML price is set manually
                            $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                            if( !empty( $_custom_prices[ '_regular_price' ] ) && $_custom_prices[ '_regular_price' ] > 0 ) {
                                $_price = $_custom_prices[ '_regular_price' ];
                            }

                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';
                        }
                        else {
                            $_price = $this->product->get_variation_regular_price();
                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                            $_price = $_price > 0 ? $_price : '';
                            return wc_format_decimal( $_price, wc_get_price_decimals() );
                        }
                    }
                    else {
                        if( $this->wcml ) {
                            global $woocommerce_wpml;
                            $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $this->product->get_variation_regular_price(), wc_get_price_decimals() ), $this->wcml_currency );
                            //if WCML price is set manually
                            $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                            if( !empty( $_custom_prices[ '_regular_price' ] ) && $_custom_prices[ '_regular_price' ] > 0 ) {
                                $_price = $_custom_prices[ '_regular_price' ];
                            }

                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';
                        }
                        $_price = $this->product->get_variation_regular_price();
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                        $_price = $_price > 0 ? $_price : '';
                        return wc_format_decimal( $_price, wc_get_price_decimals() );
                    }
                }
                elseif( $this->product->is_type( 'bundle' ) ) {
                    $_price = $this->product->get_bundle_price();
                    $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';
                }

                if( $this->wcml ) {
                    global $woocommerce_wpml;
                    $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $this->product->get_regular_price(), wc_get_price_decimals() ), $this->wcml_currency );

                    //if WCML price is set manually
                    $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                    if( !empty( $_custom_prices[ '_regular_price' ] ) && $_custom_prices[ '_regular_price' ] > 0 ) {
                        $_price = $_custom_prices[ '_regular_price' ];
                    }
                    $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';

                }
                else {
                    $_price = $this->product->get_regular_price();
                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );

                    $_price = $_price > 0 ? $_price : '';
                    return wc_format_decimal( $_price, wc_get_price_decimals() );
                }

            case 'current_price':
                if( !defined( 'WAD_INITIALIZED' ) ) {
                    if( $this->product->is_type( 'grouped' ) ) {
                        if( $this->wcml ) {
                            global $woocommerce_wpml;
                            $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( rex_feed_get_grouped_price( $this->product, '_price' ), wc_get_price_decimals() ), $this->wcml_currency );

                            //if WCML price is set manually
                            $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                            if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                                $_price = $_custom_prices[ '_price' ];
                            }
                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';
                        }
                        else {
                            $_price = rex_feed_get_grouped_price( $this->product, '_price' );
                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                            $_price = $_price > 0 ? $_price : '';
                            return wc_format_decimal( $_price, wc_get_price_decimals() );
                        }
                    }
                    elseif( $this->product->is_type( 'composite' ) ) {
                        $_pr = new WC_Product_Composite( $this->product->get_id() );
                        if( $this->wcml ) {
                            global $woocommerce_wpml;
                            $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_pr->get_composite_price(), wc_get_price_decimals() ), $this->wcml_currency );

                            //if WCML price is set manually
                            $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                            if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                                $_price = $_custom_prices[ '_price' ];
                            }
                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';
                        }
                        else {
                            $_price = $_pr->get_composite_price();
                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                            $_price = $_price > 0 ? $_price : '';
                            return wc_format_decimal( $_price, wc_get_price_decimals() );
                        }
                    }
                    elseif( $this->product->is_type( 'variable' ) ) {
                        $default_attributes = rex_feed_get_default_variable_attributes( $this->product );
                        if( $default_attributes ) {
                            $variation_id = rex_feed_find_matching_product_variation( $this->product, $default_attributes );
                            if( $variation_id ) {
                                $_variation_product = wc_get_product( $variation_id );
                                if( $this->wcml ) {
                                    global $woocommerce_wpml;
                                    $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_variation_product->get_price(), wc_get_price_decimals() ), $this->wcml_currency );

                                    //if WCML price is set manually
                                    $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $variation_id, $this->wcml_currency ) : $_price;
                                    if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                                        $_price = $_custom_prices[ '_price' ];
                                    }
                                    $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                                    return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';
                                }
                                else {
                                    $_price = $_variation_product->get_price();
                                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                                    $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                                    $_price = $_price > 0 ? $_price : '';
                                    return wc_format_decimal( $_price, wc_get_price_decimals() );
                                }
                            }
                        }
                        else {
                            if( $this->wcml ) {
                                global $woocommerce_wpml;
                                $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $this->product->get_price(), wc_get_price_decimals() ), $this->wcml_currency );

                                //if WCML price is set manually
                                $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                                if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                                    $_price = $_custom_prices[ '_price' ];
                                }
                                $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                                $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                                return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';
                            }
                            else {
                                $_price = $this->product->get_price();
                                $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                                $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                                $_price = $_price > 0 ? $_price : '';
                                return wc_format_decimal( $_price, wc_get_price_decimals() );

                            }
                        }
                    }
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $this->product->get_price(), wc_get_price_decimals() ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';
                    }
                    else {
                        $_price = $this->product->get_price();
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                        $_price = $_price > 0 ? $_price : '';
                        return wc_format_decimal( $_price, wc_get_price_decimals() );
                    }
                }
                else {
                    global $wad_discounts;

                    $all_discounts = wad_get_active_discounts( true );
                    foreach( $all_discounts as $discount_type => $discounts ) {
                        $wad_discounts[ $discount_type ] = array();
                        foreach( $discounts as $discount_id ) {
                            $wad_discounts[ $discount_type ][ $discount_id ] = new WAD_Discount( $discount_id );
                        }
                    }
                    if( $this->product->is_type( 'grouped' ) ) {
                        $sale_price = number_format( (float)rex_feed_get_grouped_price( $this->product, '_sale_price' ), 2, '.', '' );

                        $sale_price = $this->get_converted_price( $this->product->get_id(), $sale_price, '_sale_price' );

                        return $sale_price > 0 ? wc_format_decimal( $sale_price, wc_get_price_decimals() ) : '';
                    }
                    elseif( $this->product->is_type( 'composite' ) ) {
                        $_pr    = new WC_Product_Composite( $this->product->get_id() );
                        $_price = $_pr->get_composite_price();

                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );

                        $_price = $_price > 0 ? $_price : '';
                        return wc_format_decimal( $_price, wc_get_price_decimals() );
                    }
                    elseif( $this->product->is_type( 'variable' ) ) {
                        $default_attributes = rex_feed_get_default_variable_attributes( $this->product );
                        if( $default_attributes ) {
                            $variation_id = rex_feed_find_matching_product_variation( $this->product, $default_attributes );
                            if( $variation_id ) {
                                $_variation_product = wc_get_product( $variation_id );
                                $sale_price         = number_format( (float)$_variation_product->get_price(), 2, '.', '' );
                            }
                            else {
                                $sale_price = number_format( (float)$this->product->get_variation_price(), 2, '.', '' );
                            }
                        }
                        else {
                            $sale_price = number_format( (float)$this->product->get_variation_price(), 2, '.', '' );
                        }
                    }
                    else
                        $sale_price = number_format( (float)$this->product->get_price(), 2, '.', '' );

                    $_pid     = wad_get_product_id_to_use( $this->product );
                    $_product = wc_get_product( $_pid );
                    if( $_product->is_type( 'variation' ) ) {
                        $_pid = $_product->get_parent_id();
                    }
                    foreach( $wad_discounts[ "product" ] as $discount_id => $discount_obj ) {
                        $o_discount       = get_post_meta( $discount_id, 'o-discount', true );
                        $pr_list_id       = $o_discount[ 'products-list' ];
                        $product_list     = new WAD_Products_List( $pr_list_id );
                        $raw_args         = get_post_meta( $pr_list_id, "o-list", true );
                        $args             = $product_list->get_args( $raw_args );
                        $args[ 'fields' ] = 'ids';
                        $products         = get_posts( $args );

                        if( $discount_obj->is_applicable( $_pid ) && is_array( $products ) && in_array( $_pid, $products ) ) {
                            $to_widthdraw = 0;
                            if( in_array( $discount_obj->settings[ "action" ], array( "percentage-off-pprice", "percentage-off-osubtotal" ) ) )
                                $to_widthdraw = floatval( floatval( $sale_price ) ) * floatval( $discount_obj->settings[ "percentage-or-fixed-amount" ] ) / 100;
                            //Fixed discount
                            else if( in_array( $discount_obj->settings[ "action" ], array( "fixed-amount-off-pprice", "fixed-amount-off-osubtotal" ) ) ) {
                                $to_widthdraw = $discount_obj->settings[ "percentage-or-fixed-amount" ];
                            }
                            else if( $discount_obj->settings[ "action" ] == "fixed-pprice" )
                                $to_widthdraw = floatval( floatval( $sale_price ) ) - floatval( $discount_obj->settings[ "percentage-or-fixed-amount" ] );
                            $decimals   = wc_get_price_decimals();
                            $discount   = round( $to_widthdraw, $decimals );
                            $sale_price = floatval( $sale_price ) - $discount;
                            if( $this->wcml ) {
                                global $woocommerce_wpml;
                                $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $sale_price, wc_get_price_decimals() ), $this->wcml_currency );

                                //if WCML price is set manually
                                $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                                if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                                    $_price = $_custom_prices[ '_price' ];
                                }
                                $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                                $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                                return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';
                            }
                            else {
                                $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $sale_price ) : $sale_price;
                                $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                                $_price = $_price > 0 ? $_price : '';
                                return wc_format_decimal( $_price, wc_get_price_decimals() );
                            }
                        }
                    }
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $sale_price, wc_get_price_decimals() ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? wc_format_decimal( $_price, wc_get_price_decimals() ) : '';
                    }
                    else {
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $sale_price ) : $sale_price;
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );

                        $_price = $_price > 0 ? $_price : '';
                        return wc_format_decimal( $_price, wc_get_price_decimals() );
                    }
                }

            case 'sale_price':
                if( !defined( 'WAD_INITIALIZED' ) ) {
                    if( $this->product->is_type( 'grouped' ) ) {
                        $sale_price = number_format( (float)rex_feed_get_grouped_price( $this->product, '_sale_price' ), 2, '.', '' );
                    }
                    elseif( $this->product->is_type( 'composite' ) ) {
                        $sale_price = wc_format_decimal( $this->product->get_sale_price(), wc_get_price_decimals() );
                    }
                    elseif( $this->product->is_type( 'variable' ) ) {
                        $default_attributes = rex_feed_get_default_variable_attributes( $this->product );
                        if( $default_attributes ) {
                            $variation_id = rex_feed_find_matching_product_variation( $this->product, $default_attributes );
                            if( $variation_id ) {
                                $_variation_product = wc_get_product( $variation_id );
                                $sale_price         = wc_format_decimal( $_variation_product->get_sale_price(), wc_get_price_decimals() );
                            }
                            else {
                                $sale_price = wc_format_decimal( $this->product->get_variation_sale_price(), wc_get_price_decimals() );
                            }
                        }
                        else {
                            $sale_price = wc_format_decimal( $this->product->get_variation_sale_price(), wc_get_price_decimals() );
                        }
                    }
                    else {
                        $sale_price = wc_format_decimal( $this->product->get_sale_price(), wc_get_price_decimals() );
                    }
                    if( $sale_price > 0 ) {
                        if( $this->wcml ) {
                            global $woocommerce_wpml;
                            $_price = apply_filters( 'wcml_raw_price_amount', $sale_price, $this->wcml_currency );

                            //if WCML price is set manually
                            $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                            if( !is_wp_error( $_custom_prices ) && $_custom_prices && !empty( $_custom_prices[ '_sale_price' ] ) && $_custom_prices[ '_sale_price' ] > 0 ) {
                                $_price = $_custom_prices[ '_sale_price' ];
                            }

                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );

                            $sale_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        }
                        else {
                            $sale_price = $this->get_converted_price( $this->product->get_id(), $sale_price, '_sale_price' );
                            $sale_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $sale_price ) : $sale_price;
                        }
                    }
                    $sale_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $sale_price ) : $sale_price;
                }
                else {
                    global $wad_discounts;

                    $all_discounts = wad_get_active_discounts( true );
                    foreach( $all_discounts as $discount_type => $discounts ) {
                        $wad_discounts[ $discount_type ] = array();
                        foreach( $discounts as $discount_id ) {
                            $wad_discounts[ $discount_type ][ $discount_id ] = new WAD_Discount( $discount_id );
                        }
                    }

                    if( $this->product->is_type( 'grouped' ) ) {
                        $sale_price = number_format( (float)rex_feed_get_grouped_price( $this->product, '_sale_price' ), 2, '.', '' );
                    }
                    elseif( $this->product->is_type( 'variable' ) ) {
                        $default_attributes = rex_feed_get_default_variable_attributes( $this->product );
                        if( $default_attributes ) {
                            $variation_id = rex_feed_find_matching_product_variation( $this->product, $default_attributes );
                            if( $variation_id ) {
                                $_variation_product = wc_get_product( $variation_id );
                                $sale_price         = number_format( (float)$_variation_product->get_sale_price(), 2, '.', '' );
                            }
                            else {
                                $sale_price = number_format( (float)$this->product->get_variation_sale_price(), 2, '.', '' );
                            }
                        }
                        else {
                            $sale_price = number_format( (float)$this->product->get_variation_sale_price(), 2, '.', '' );
                        }
                    }
                    elseif( $this->product->is_type( 'composite' ) ) {
                        $_pr = new WC_Product_Composite( $this->product->get_id() );
                        if( $this->wcml ) {
                            global $woocommerce_wpml;
                            $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_pr->get_sale_price(), wc_get_price_decimals() ), $this->wcml_currency );

                            //if WCML price is set manually
                            $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                            if( !empty( $_custom_prices[ '_sale_price' ] ) && $_custom_prices[ '_sale_price' ] > 0 ) {
                                $_price = $_custom_prices[ '_sale_price' ];
                            }

                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                            $sale_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        }
                        else {
                            $_price = $_pr->get_sale_price();
                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                            $sale_price = wc_format_decimal( $_price, wc_get_price_decimals() );
                        }
                    }
                    else
                        $sale_price = number_format( (float)$this->product->get_sale_price(), 2, '.', '' );


                    $_pid     = wad_get_product_id_to_use( $this->product );
                    $_product = wc_get_product( $_pid );
                    if( $_product->is_type( 'variation' ) ) {
                        $_pid = $_product->get_parent_id();
                    }
                    foreach( $wad_discounts[ "product" ] as $discount_id => $discount_obj ) {
                        $o_discount       = get_post_meta( $discount_id, 'o-discount', true );
                        $pr_list_id       = $o_discount[ 'products-list' ];
                        $product_list     = new WAD_Products_List( $pr_list_id );
                        $raw_args         = get_post_meta( $pr_list_id, "o-list", true );
                        $args             = $product_list->get_args( $raw_args );
                        $args[ 'fields' ] = 'ids';
                        $products         = get_posts( $args );
                        if( $discount_obj->is_applicable( $_pid ) && in_array( $_pid, $products ) ) {
                            $to_widthdraw = 0;
                            if( in_array( $discount_obj->settings[ "action" ], array( "percentage-off-pprice", "percentage-off-osubtotal" ) ) )
                                $to_widthdraw = floatval( floatval( $sale_price ) ) * floatval( $discount_obj->settings[ "percentage-or-fixed-amount" ] ) / 100;
                            //Fixed discount
                            else if( in_array( $discount_obj->settings[ "action" ], array( "fixed-amount-off-pprice", "fixed-amount-off-osubtotal" ) ) ) {
                                $to_widthdraw = $discount_obj->settings[ "percentage-or-fixed-amount" ];
                            }
                            else if( $discount_obj->settings[ "action" ] == "fixed-pprice" )
                                $to_widthdraw = floatval( floatval( $sale_price ) ) - floatval( $discount_obj->settings[ "percentage-or-fixed-amount" ] );
                            $decimals   = wc_get_price_decimals();
                            $discount   = round( $to_widthdraw, $decimals );
                            $sale_price = floatval( $sale_price ) - $discount;
                            if( $this->wcml ) {
                                global $woocommerce_wpml;
                                $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $sale_price, wc_get_price_decimals() ), $this->wcml_currency );

                                //if WCML price is set manually
                                $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                                if( !empty( $_custom_prices[ '_sale_price' ] ) && $_custom_prices[ '_sale_price' ] > 0 ) {
                                    $_price = $_custom_prices[ '_sale_price' ];
                                }

                                $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                                $sale_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            }
                            else {
                                $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $sale_price ) : $sale_price;
                                $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                                $sale_price = wc_format_decimal( $_price, wc_get_price_decimals() );
                            }
                        }
                    }
                    $sale_price = wc_format_decimal( $sale_price, wc_get_price_decimals() );
                    if( $sale_price > 0 ) {
                        if( $this->wcml ) {
                            global $woocommerce_wpml;
                            $_price = apply_filters( 'wcml_raw_price_amount', $sale_price, $this->wcml_currency );

                            //if WCML price is set manually
                            $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                            if( !empty( $_custom_prices[ '_sale_price' ] ) && $_custom_prices[ '_sale_price' ] > 0 ) {
                                $_price = $_custom_prices[ '_sale_price' ];
                            }

                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                            $sale_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        }
                        else {
                            $_price = $this->get_converted_price( $this->product->get_id(), $sale_price, '_sale_price' );
                            $sale_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        }
                    }
                    $sale_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $sale_price ) : $sale_price;
                }

                return $sale_price > 0 ? wc_format_decimal( $sale_price, wc_get_price_decimals() ) : '';

            case 'price_with_tax':
                if( $this->product->is_type( 'grouped' ) ) {
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_get_price_including_tax( $this->product, array( 'price' => rex_feed_get_grouped_price( $this->product, '_regular_price' ) ) ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_regular_price' ] ) && $_custom_prices[ '_regular_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_regular_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        $_price = rex_feed_get_grouped_price( $this->product, '_regular_price' );
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        $_price = wc_get_price_including_tax( $this->product, array( 'price' => $_price ) );
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                        
                        return $_price > 0 ? $_price : '';
                    }
                }
                elseif( $this->product->is_type( 'composite' ) ) {
                    $_pr = new WC_Product_Composite( $this->product->get_id() );
                    if( $this->wcml ) {
                        global $woocommerce_wpml;

                        if( is_plugin_active( 'wpc-composite-products/wpc-composite-products.php' ) ) {
                            $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_pr->get_composite_price(), wc_get_price_decimals() ), $this->wcml_currency );
                        }
                        else {
                            $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_pr->get_composite_regular_price(), wc_get_price_decimals() ), $this->wcml_currency );
                        }

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_regular_price' ] ) && $_custom_prices[ '_regular_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_regular_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {

                        if( is_plugin_active( 'wpc-composite-products/wpc-composite-products.php' ) ) {
                            $_price = $_pr->get_composite_price();
                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                            
                            $_price = $_price > 0 ? $_price : '';
                            return wc_format_decimal( $_price, wc_get_price_decimals() );
                        }
                        else {
                            $_price = $_pr->get_composite_regular_price();
                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                            
                            $_price = $_price > 0 ? $_price : '';
                            return wc_format_decimal( $_price, wc_get_price_decimals() );
                        }
                    }
                }
                if( $this->wcml ) {
                    global $woocommerce_wpml;
                    $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( wc_get_price_including_tax( $this->product, array( 'price' => $this->product->get_regular_price() ) ), wc_get_price_decimals() ), $this->wcml_currency );

                    //if WCML price is set manually
                    $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                    if( !empty( $_custom_prices[ '_regular_price' ] ) && $_custom_prices[ '_regular_price' ] > 0 ) {
                        $_price = $_custom_prices[ '_regular_price' ];
                    }
                    $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                    
                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    return $_price > 0 ? $_price : '';
                }
                else {
                    $_price = $this->product->get_regular_price();
                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    $_price = wc_get_price_including_tax( $this->product, array( 'price' => $_price ) );
                    $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                    
                    $_price = $_price > 0 ? $_price : '';
                    return wc_format_decimal( $_price, wc_get_price_decimals() );
                }

            case 'current_price_with_tax':
                if( $this->product->is_type( 'grouped' ) ) {
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_get_price_including_tax( $this->product, array( 'price' => rex_feed_get_grouped_price( $this->product, '_price' ) ) ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        $_price = rex_feed_get_grouped_price( $this->product, '_price' );
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        $_price = wc_get_price_including_tax( $this->product, array( 'price' => $_price ) );
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );
                        
                        return $_price > 0 ? $_price : '';
                    }
                }
                elseif( $this->product->is_type( 'composite' ) ) {
                    $_pr = new WC_Product_Composite( $this->product->get_id() );
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_pr->get_composite_price(), wc_get_price_decimals() ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        $_price = $_pr->get_composite_price();
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );
                        
                        $_price = $_price > 0 ? $_price : '';
                        return wc_format_decimal( $_price, wc_get_price_decimals() );
                    }
                }
                if( $this->wcml ) {
                    global $woocommerce_wpml;
                    $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( wc_get_price_including_tax( $this->product, array( 'price' => $this->product->get_price() ) ), wc_get_price_decimals() ), $this->wcml_currency );

                    //if WCML price is set manually
                    $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                    if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                        $_price = $_custom_prices[ '_price' ];
                    }
                    $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );
                    
                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    return $_price > 0 ? $_price : '';
                }
                else {
                    $_price = $this->product->get_price();
                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    $_price = wc_get_price_including_tax( $this->product, array( 'price' => $_price ) );
                    $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );
                    
                    $_price = $_price > 0 ? $_price : '';
                    return wc_format_decimal( $_price, wc_get_price_decimals() );
                }

            case 'sale_price_with_tax':
                if( $this->product->is_type( 'grouped' ) ) {
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( wc_get_price_including_tax( $this->product, array( 'price' => rex_feed_get_grouped_price( $this->product, '_sale_price' ) ) ), wc_get_price_decimals() ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_sale_price' ] ) && $_custom_prices[ '_sale_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_sale_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        $_price = rex_feed_get_grouped_price( $this->product, '_sale_price' );
                        $_price = wc_get_price_including_tax( $this->product, array( 'price' => $_price ) );
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                }
                elseif( $this->product->is_type( 'composite' ) ) {
                    $_pr = new WC_Product_Composite( $this->product->get_id() );
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_pr->get_sale_price(), wc_get_price_decimals() ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_sale_price' ] ) && $_custom_prices[ '_sale_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_sale_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        $_price = $_pr->get_sale_price();
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        $_price = $_price > 0 ? $_price : '';
                        return wc_format_decimal( $_price, wc_get_price_decimals() );
                    }
                }
                $sale_price = $this->product->get_sale_price();
                if( $sale_price > 0 ) {
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( wc_get_price_including_tax( $this->product, array( 'price' => $this->product->get_sale_price() ) ), wc_get_price_decimals() ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_sale_price' ] ) && $_custom_prices[ '_sale_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_sale_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        $_price = wc_get_price_including_tax( $this->product, array( 'price' => $this->product->get_sale_price() ) );
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        $_price = $_price > 0 ? $_price : '';
                        return wc_format_decimal( $_price, wc_get_price_decimals() );
                    }
                }
                
                $sale_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $sale_price ) : $sale_price;
                return $sale_price > 0 ? $sale_price : '';

            case 'price_excl_tax':
                if( $this->product->is_type( 'grouped' ) ) {
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_get_price_excluding_tax( $this->product, array( 'price' => rex_feed_get_grouped_price( $this->product, '_regular_price' ) ) ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_regular_price' ] ) && $_custom_prices[ '_regular_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_regular_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        $_price = wc_get_price_excluding_tax( $this->product, array( 'price' => rex_feed_get_grouped_price( $this->product, '_regular_price' ) ) );
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                }
                elseif( $this->product->is_type( 'composite' ) ) {
                    $_pr = new WC_Product_Composite( $this->product->get_id() );
                    if( $this->wcml ) {
                        global $woocommerce_wpml;

                        if( is_plugin_active( 'wpc-composite-products/wpc-composite-products.php' ) ) {
                            $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_pr->get_composite_price(), wc_get_price_decimals() ), $this->wcml_currency );
                        }
                        else {
                            $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_pr->get_composite_regular_price(), wc_get_price_decimals() ), $this->wcml_currency );
                        }
                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;

                        if( !empty( $_custom_prices[ '_regular_price' ] ) && $_custom_prices[ '_regular_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_regular_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        if( is_plugin_active( 'wpc-composite-products/wpc-composite-products.php' ) ) {
                            $_price = $_pr->get_composite_price();
                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            
                            $_price = $_price > 0 ? $_price : '';
                            return wc_format_decimal( $_price, wc_get_price_decimals() );
                        }
                        else {
                            $_price = $_pr->get_composite_regular_price();
                            $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                            $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                            
                            $_price = $_price > 0 ? $_price : '';
                            return wc_format_decimal( $_price, wc_get_price_decimals() );
                        }
                    }
                }
                if( $this->wcml ) {
                    global $woocommerce_wpml;
                    $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( wc_get_price_excluding_tax( $this->product, array( 'price' => $this->product->get_regular_price() ) ), wc_get_price_decimals() ), $this->wcml_currency );

                    //if WCML price is set manually
                    $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                    if( !empty( $_custom_prices[ '_regular_price' ] ) && $_custom_prices[ '_regular_price' ] > 0 ) {
                        $_price = $_custom_prices[ '_regular_price' ];
                    }
                    $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                    
                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    return $_price > 0 ? $_price : '';
                }
                else {
                    $_price = wc_get_price_excluding_tax( $this->product, array( 'price' => $this->product->get_regular_price() ) );
                    $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_regular_price' );
                    
                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    $_price = $_price > 0 ? $_price : '';
                    return wc_format_decimal( $_price, wc_get_price_decimals() );
                }

            case 'current_price_excl_tax':
                if( $this->product->is_type( 'grouped' ) ) {
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_get_price_excluding_tax( $this->product, array( 'price' => rex_feed_get_grouped_price( $this->product, '_price' ) ) ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        $_price = wc_get_price_excluding_tax( $this->product, array( 'price' => rex_feed_get_grouped_price( $this->product, '_price' ) ) );
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                }
                elseif( $this->product->is_type( 'composite' ) ) {
                    $_pr = new WC_Product_Composite( $this->product->get_id() );
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_pr->get_composite_price(), wc_get_price_decimals() ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        $_price = $_pr->get_composite_price();
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        $_price = $_price > 0 ? $_price : '';
                        return wc_format_decimal( $_price, wc_get_price_decimals() );
                    }
                }
                if( $this->wcml ) {
                    global $woocommerce_wpml;
                    $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( wc_get_price_excluding_tax( $this->product, array( 'price' => $this->product->get_price() ) ), wc_get_price_decimals() ), $this->wcml_currency );

                    //if WCML price is set manually
                    $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                    if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                        $_price = $_custom_prices[ '_price' ];
                    }
                    $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );
                    
                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    $_price = $_price > 0 ? $_price : '';
                    return wc_format_decimal( $_price, wc_get_price_decimals() );
                }
                else {
                    $_price = wc_get_price_excluding_tax( $this->product, array( 'price' => $this->product->get_price() ) );
                    $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_price' );
                    
                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    $_price = $_price > 0 ? $_price : '';
                    return wc_format_decimal( $_price, wc_get_price_decimals() );
                }

            case 'sale_price_excl_tax':
                if( $this->product->is_type( 'grouped' ) ) {
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_get_price_excluding_tax( $this->product, array( 'price' => rex_feed_get_grouped_price( $this->product, '_sale_price' ) ) ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_sale_price' ] ) && $_custom_prices[ '_sale_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_sale_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        $_price = wc_get_price_excluding_tax( $this->product, array( 'price' => rex_feed_get_grouped_price( $this->product, '_sale_price' ) ) );
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                }
                elseif( $this->product->is_type( 'composite' ) ) {
                    $_pr = new WC_Product_Composite( $this->product->get_id() );
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $_pr->get_sale_price(), wc_get_price_decimals() ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_sale_price' ] ) && $_custom_prices[ '_sale_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_sale_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        $_price = $_pr->get_sale_price();
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        $_price = $_price > 0 ? $_price : '';
                        return wc_format_decimal( $_price, wc_get_price_decimals() );
                    }
                }
                $sale_price = $this->product->get_sale_price();
                if( $sale_price > 0 ) {
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( wc_get_price_excluding_tax( $this->product, array( 'price' => $this->product->get_sale_price() ) ), wc_get_price_decimals() ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_sale_price' ] ) && $_custom_prices[ '_sale_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_sale_price' ];
                        }
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        $_price = wc_get_price_excluding_tax( $this->product, array( 'price' => $this->product->get_sale_price() ) );
                        $_price = $this->get_converted_price( $this->product->get_id(), $_price, '_sale_price' );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        $_price = $_price > 0 ? $_price : '';
                        return wc_format_decimal( $_price, wc_get_price_decimals() );
                    }
                }
                
                $sale_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $sale_price ) : $sale_price;
                return $sale_price > 0 ? $sale_price : '';

            case 'price_db':
                if( $this->wcml ) {
                    global $woocommerce_wpml;
                    $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( get_post_meta( $this->product->get_id(), '_regular_price', true ), wc_get_price_decimals() ), $this->wcml_currency );

                    //if WCML price is set manually
                    $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                    if( !empty( $_custom_prices[ '_regular_price' ] ) && $_custom_prices[ '_regular_price' ] > 0 ) {
                        $_price = $_custom_prices[ '_regular_price' ];
                    }
                    
                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    return $_price > 0 ? $_price : '';
                }
                else {
                    $meta_key = '_regular_price';
                    if( $this->product->is_type( 'variable' ) || $this->product->is_type( 'grouped' ) ) {
                        $meta_key = '_price';
                    }
                    $_price = wc_format_decimal( get_post_meta( $this->product->get_id(), $meta_key, true ), wc_get_price_decimals() );
                    
                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    return $_price > 0 ? $_price : '';
                }

            case 'current_price_db':
                if( $this->wcml ) {
                    global $woocommerce_wpml;
                    $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( get_post_meta( $this->product->get_id(), '_price', true ), wc_get_price_decimals() ), $this->wcml_currency );

                    //if WCML price is set manually
                    $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                    if( !empty( $_custom_prices[ '_price' ] ) && $_custom_prices[ '_price' ] > 0 ) {
                        $_price = $_custom_prices[ '_price' ];
                    }
                    
                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    return $_price > 0 ? $_price : '';
                }
                else {
                    $_price = wc_format_decimal( get_post_meta( $this->product->get_id(), '_price', true ), wc_get_price_decimals() );
                    
                    $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                    return $_price > 0 ? $_price : '';
                }

            case 'sale_price_db':
                $sale_price = get_post_meta( $this->product->get_id(), '_sale_price', true );
                if( (float)$sale_price > 0 ) {
                    if( $this->wcml ) {
                        global $woocommerce_wpml;
                        $_price = apply_filters( 'wcml_raw_price_amount', wc_format_decimal( $sale_price, wc_get_price_decimals() ), $this->wcml_currency );

                        //if WCML price is set manually
                        $_custom_prices = $woocommerce_wpml ? $woocommerce_wpml->get_multi_currency()->custom_prices->get_product_custom_prices( $this->product->get_id(), $this->wcml_currency ) : $_price;
                        if( !empty( $_custom_prices[ '_sale_price' ] ) && $_custom_prices[ '_sale_price' ] > 0 ) {
                            $_price = $_custom_prices[ '_sale_price' ];
                        }
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                    else {
                        $_price = wc_format_decimal( $sale_price, wc_get_price_decimals() );
                        
                        $_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $_price ) : $_price;
                        return $_price > 0 ? $_price : '';
                    }
                }
                
                $sale_price = function_exists( 'rex_feed_get_dynamic_price' ) ? rex_feed_get_dynamic_price( $rule, $sale_price ) : $sale_price;
                return $sale_price > 0 ? $sale_price : '';

            default:
                return '';
        }
    }


    /**
     * @desc Gets price converted by Aelia
     *
     * @param $price
     * @return mixed|void
     */
    protected function get_converted_price( $product_id, $price, $price_type )
    {
        if( wpfm_is_aelia_active() ) {
            $from_currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'USD';
            $to_currency   = $this->aelia_currency;

            try {
                $price = apply_filters( 'wc_aelia_cs_convert', $price, $from_currency, $to_currency );
            }
            catch( Exception $e ) {
                $log = wc_get_logger();
                $log->warning( $e->getMessage(), array( 'source' => 'wpfm-error' ) );
            }
        }

        if( wpfm_is_wmc_active() ) {
            $wmc_params    = get_option( 'woo_multi_currency_params', [] );

            if ( !empty( $wmc_params ) && isset( $wmc_params[ 'enable_fixed_price' ] ) && $wmc_params[ 'enable_fixed_price' ] ) {
                $prices = get_post_meta( $product_id, $price_type . '_wmcp', true );
                $prices = json_decode( $prices );
                $wmc_currency = $this->wmc_currency;
                if ( !empty( $prices ) && isset( $prices->$wmc_currency ) ) {
                    return $prices->$wmc_currency;
                }
            }
            $wmc_settings      = class_exists( 'WOOMULTI_CURRENCY_Data' ) ? WOOMULTI_CURRENCY_Data::get_ins() : array();
            $wmc_currency_list = !empty( $wmc_settings ) ? $wmc_settings->currencies_list : array();

            if( !empty( $wmc_currency_list ) ) {
                $to_currency = $this->wmc_currency;
                $rate        = $wmc_currency_list[ $to_currency ][ 'rate' ];

                return $price * $rate;
            }
        }

        return $price;
    }


    /**
     * @desc retrieves image metadata
     *
     * @return array|false
     */
    protected function get_image_meta()
    {
        if( 'WC_Product_Variation' == get_class( $this->product ) ) {
            $_pr = wc_get_product( $this->product->get_parent_id() );
            return $_pr ? wp_get_attachment_metadata( $_pr->get_image_id() ) : array();
        }
        else {
            return $this->product ? wp_get_attachment_metadata( $this->product->get_image_id() ) : array();
        }
    }


    /**
     * Set a Image attribute.
     *
     * @since    1.0.0
     */
    protected function set_image_att( $key )
    {
        if( $this->product && !is_wp_error( $this->product ) ) {
            switch( $key ) {
                case 'main_image':
                    if( 'WC_Product_Variation' == get_class( $this->product ) ) {
                        $_pr = wc_get_product( $this->product->get_parent_id() );
                        return $_pr ? wp_get_attachment_url( $_pr->get_image_id() ) : '';
                        break;
                    }
                    else {
                        return $this->product ? wp_get_attachment_url( $this->product->get_image_id() ) : '';
                    }
                    return '';

                case 'image_height':
                    $image_src = $this->get_image_meta();

                    return $image_src[ 'height' ];

                case 'image_width':
                    $image_src = $this->get_image_meta();

                    return $image_src[ 'width' ];

                case 'encoding_format':
                    $image_src = $this->get_image_meta();

                    return $image_src[ 'sizes' ][ 'woocommerce_thumbnail' ][ 'mime-type' ];

                case 'image_size':
                    if( 'WC_Product_Variation' == get_class( $this->product ) ) {
                        $_pr        = wc_get_product( $this->product->get_parent_id() );
                        $image_size = $_pr ? filesize( get_attached_file( $_pr->get_image_id() ) ) : 0;

                    }
                    else {
                        $image_size = $this->product ? filesize( get_attached_file( $this->product->get_image_id() ) ) : 0;
                    }

                    return $image_size;

                case 'keywords':
                    $image_src = $this->get_image_meta();
                    return isset( $image_src[ 'image_meta' ][ 'keywords' ] ) ? implode( ', ', $image_src[ 'image_meta' ][ 'keywords' ] ) : '';

                case 'thumbnail_image':
                    if( 'WC_Product_Variation' == get_class( $this->product ) ) {
                        return get_the_post_thumbnail_url( $this->product->get_parent_id() );

                    }
                    else {
                        return get_the_post_thumbnail_url( $this->product->get_id() );
                    }

                case 'featured_image':
                    if( $this->product && wp_get_attachment_url( $this->product->get_image_id() ) ) {
                        return wp_get_attachment_url( $this->product->get_image_id() );
                    }
                    return '';

                case 'all_image_array':
                    return $this->get_all_image( '', true );

                case 'all_image':
                    return $this->get_all_image();

                case 'all_image_pipe':
                    return $this->get_all_image( '|' );

                case 'variation_img':
                    return wp_get_attachment_url( $this->product->get_image_id() );

                default:
                    $key = str_replace( 'additional_', '', $key );
                    return $this->get_additional_image( $key );
            }
        }
        return '';
    }


	/**
	 * @desc get all product images with separators
	 * @since 7.2.19
	 * @param string $sep
	 * @param bool $return_array
	 * @return array|string
	 */
    private function get_all_image( $sep = ',', $return_array = false ) {
        if( !is_wp_error( $this->product ) && $this->product ) {
            $attachment_ids = $this->product->get_gallery_image_ids();
            $attachment_ids = array_merge( [ $this->product->get_image_id() ], $attachment_ids );
            $all_images     = [];

            foreach( $attachment_ids as $key => $val ) {
                $all_images[] = wp_get_attachment_url( $val );
            }
            if( $return_array ) {
                return $all_images;
            }
            return implode( $sep, $all_images );
        }
        return '';
    }


    /**
     * Set a Product attribute.
     *
     * @since    1.0.0
     */
    protected function set_product_attr( $key )
    {
        if( $this->product && !is_wp_error( $this->product ) ) {
            $key = str_replace( 'bwf_attr_pa_', '', $key );

            if( 'WC_Product_Variation' === get_class( $this->product ) ) {
                $var_id = $this->product->get_parent_id();
                $var_pr = wc_get_product( $var_id );
                $value  = $var_pr ? $var_pr->get_attribute( $key ) : '';
            }
            else {
                $value = $this->product->get_attribute( $key );
            }

            if( !empty( $value ) ) {
                $value = trim( $value );
            }
            return $value;
        }
        return '';
    }


    /**
     * Set a Glami attribute.
     *
     * @since    1.0.0
     */
    protected function set_glami_att( $key )
    {
        if( 'WC_Product_Variation' != get_class( $this->product ) ) {
            return;
        }
        $key   = str_replace( 'param_', '', $key );
        $value = $this->product ? $this->product->get_attribute( $key ) : '';

        if( !empty( $value ) ) {
            $value = trim( $value );
        }
        return $value;
    }


    /**
     * Set a Dropship attribute.
     */
    protected function set_dropship_att( $key )
    {
        if( 'WC_Product_Variation' == get_class( $this->product ) ) {
            return get_post_meta( $this->product->get_parent_id(), $key, true );
        }
        return get_post_meta( $this->product->get_id(), $key, true );
    }


    /**
     * Set a Date attributes.
     */
    protected function set_date_attr( $key )
    {
        switch( $key ) {
            case 'post_publish_date':
                $product_id = '';
                if( $this->product->is_type( 'variation' ) ) {
                    $product_id = $this->product->get_parent_id();
                }
                else {
                    $product_id = $this->product->get_id();
                }
                return get_the_date( '', $product_id ) . 'T' . get_the_time( 'g:i:s', $product_id ) . 'Z';

            case 'last_updated':
                if( 'WC_Product_Variation' == get_class( $this->product ) ) {
                    $_pr = wc_get_product( $this->product->get_parent_id() );
                    return $_pr->get_date_modified()->date( 'Y-m-d' ) . 'T' . $_pr->get_date_modified()->date( 'H:i:s' ) . 'Z';
                }
                return $this->product->get_date_modified()->date( 'Y-m-d' ) . 'T' . $this->product->get_date_modified()->date( 'H:i:s' ) . 'Z';

            case 'sale_price_dates_from':
                $date_starts = $this->product->get_date_on_sale_from();
                $format      = get_option( 'date_format' );
                return !$date_starts ? $date_starts : date( $format, $date_starts->getTimestamp() );

            case 'sale_price_dates_to':
                $date_ends = $this->product->get_date_on_sale_to();
                $format    = get_option( 'date_format' );
                return !$date_ends ? $date_ends : date( $format, $date_ends->getTimestamp() );

            case 'sale_price_effective_date':
                $sale_price_dates_to   = ( $date = get_post_meta( $this->product->get_id(), '_sale_price_dates_to', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';
                $sale_price_dates_from = ( $date = get_post_meta( $this->product->get_id(), '_sale_price_dates_from', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';

                if( !empty( $sale_price_dates_to ) && !empty( $sale_price_dates_from ) ) {
                    $from = date( "c", strtotime( $sale_price_dates_from ) );
                    $to   = date( "c", strtotime( $sale_price_dates_to ) );


                    return $from . '/' . $to;
                }
                else {
                    return '';
                }
        }
    }


    /**
     * Set the value for Custom Taxomonies.
     *
     * @param $key
     * @return false|string
     */
    protected function set_product_custom_tax( $key ) {
        return $this->get_product_cats( $key );
    }


    /**
     * Set the value for WooDiscount Rules attributes
     * @param $key
     * @return mixed|string|void
     */
    protected function set_woo_discount_rules( $key ) {
        $this->discount_manage = new ManageDiscount();
        if( $key === 'woo_discount_rules_price' ) {
            $discounted_price = $this->discount_manage->calculateInitialAndDiscountedPrice( $this->product, 1 );
            $discounted_price = isset( $discounted_price[ 'discounted_price' ] ) ? $discounted_price[ 'discounted_price' ] : '';

            if ( $discounted_price && $discounted_price !== '' ) {
                return $discounted_price;
            }
            return $this->product->get_regular_price();
        }
        elseif( $key === 'woo_discount_rules_expire_date' ) {
            $rules = DBTable::getRules();
            foreach( $rules as $rule ) {
                if( $rule->discount_type === 'wdr_simple_discount' ) {
                    $format     = "Y-m-d H:i";
                    $end_date   = $rule->date_to;
                    return $end_date && $end_date !== '' ? date( $format, (int)$end_date ) : $end_date;
                }
            }
        }
        return '';
    }

    /**
     * Set a Product Dynamic attribute.
     *
     * @since    1.0.0
     */
    protected function set_product_dynamic_attr( $key ) {
        $val = '';
        if( 'WC_Product_Simple' !== get_class( $this->product ) ) {
            $val = $this->product ? trim( $this->product->get_attribute( $key ) ) : '';
            if ( '' === $val ) {
                $val = $this->get_product_cats( $key );
            }
        }
        return $val;
    }

    /**
     * Set a WPFM Custom attribute.
     *
     * @since    1.0.0
     */
    protected function set_wpfm_custom_att( $key )
    {
        $key = str_replace( 'custom_attributes_', '', $key );
        if( 'WC_Product_Variation' == get_class( $this->product ) ) {
            $val = get_post_meta( $this->product->get_id(), $key, true );
            return $val && $val !== '' ? $val : get_post_meta( $this->product->get_parent_id(), $key, true );
        }
        return get_post_meta( $this->product->get_id(), $key, true );
    }

    /**
     * Set a Product Custom attribute.
     *
     * @since    1.0.0
     */
    protected function set_product_custom_att( $key )
    {
        $new_key    = str_replace( 'custom_attributes_', '', $key );

        if( 'WC_Product_Variation' == get_class( $this->product ) ) {
            $pr_id = $this->product->get_parent_id();
            $meta_value = get_post_meta( $this->product->get_id(), $new_key, true );
            // need to check if these attributes value is assigned to the mother product
            if( !$meta_value ) {
                $list = $this->product->get_attributes();

                if( array_key_exists( $new_key, $list ) ) {
                    $meta_value = $list[ $new_key ];
                }
                else {
                    $acf_field = get_post_meta( $this->product->get_parent_id(), '_' . $new_key, true );

                    if( $acf_field !== '' && preg_match( '/field_/', $acf_field ) ) {
                        $meta_value = get_post_meta( $this->product->get_parent_id(), $new_key, true );
                    }
                }
            }
        }
        else {
            $pr_id = $this->product->get_id();
            $meta_value = get_post_meta( $pr_id, $new_key, true );

            if( 'rank_math_primary_product_cat' === $new_key ) {
                $meta_value = $meta_value != '' ? get_the_category_by_ID( $meta_value ) : '';
            }
            if( !$meta_value ) {
                $list = $this->get_product_attributes( $this->product->get_id() );

                if( array_key_exists( $new_key, $list ) ) {
                    $meta_value = str_replace( '|', ',', $list[ $new_key ] );
                }
            }
        }

        if ( $meta_value === '' ) {
            $pr_attr = get_post_meta( $pr_id, '_product_attributes', true );
            if ( isset( $pr_attr[ $new_key ][ 'value' ] ) ) {
                $meta_value = $pr_attr[ $new_key ][ 'value' ];
                $meta_value = explode( '|', $meta_value );
            }
        }

        if( is_array( $meta_value ) && !empty( $meta_value ) ) {
            if( 'wooco_components' === $new_key ) {
                foreach( $meta_value as $meta ) {
                    $meta_temp .= implode( ':', $meta );
                    $meta_temp .= '||';
                }
                $meta_value = rtrim( $meta_temp, '||' );
            }
            else {
                $meta_value = implode( ', ', $meta_value );
            }
        }
        return apply_filters( "product_custom_att_value_{$new_key}", $meta_value, $new_key, $this->product );

    }


    /**
     * get all the product attributes
     * @param $id
     * @return array
     */
    protected function get_product_attributes( $id )
    {

        global $wpdb;
        $list = [];
        $sql  = "SELECT meta_key as name, meta_value as value FROM {$wpdb->prefix}postmeta  as postmeta
                            INNER JOIN {$wpdb->prefix}posts AS posts
                            ON postmeta.post_id = posts.id
                            WHERE posts.post_type LIKE '%product%'
                            AND postmeta.meta_key = '_product_attributes'
                            AND postmeta.post_id = %d";
        $data = $wpdb->get_results( $wpdb->prepare( $sql, $id ) );
        if( count( $data ) ) {
            foreach( $data as $key => $value ) {
                $value_display = str_replace( "_", " ", $value->name );
                if( !preg_match( "/_product_attributes/i", $value->name ) ) {
                    $list[ $value->name ] = ucfirst( $value_display );
                }
                else {
                    $product_attributes = json_decode( $value->value );
                    if( !empty( $product_attributes ) ) {
                        foreach( $product_attributes as $k => $arr_value ) {
                            $value_display = str_replace( "_", " ", $arr_value[ 'value' ] );
                            $list[ $k ]    = ucfirst( $value_display );
                        }
                    }
                }
            }
        }
        return $list;
    }


    public function price_array( $price )
    {
        $del       = array( '<span class="amount">', '</span>', '<del>', '<ins>' );
        $price     = str_replace( $del, '', $price );
        $price     = str_replace( '</del>', '|', $price );
        $price     = str_replace( '</ins>', '|', $price );
        $price_arr = explode( '|', $price );
        $price_arr = array_filter( $price_arr );
        return $price_arr;
    }

    /**
     * Set Product Category Map
     *
     * @since    3.0
     */
    protected function set_cat_mapper_att( $key )
    {

        if( 'WC_Product_Variation' == get_class( $this->product ) ) {
            $cat_lists = get_the_terms( $this->product->get_parent_id(), 'product_cat' );
        }
        else {
            $cat_lists = get_the_terms( $this->product->get_id(), 'product_cat' );
        }
        $wpfm_category_map = get_option( 'rex-wpfm-category-mapping' );

        if( $wpfm_category_map ) {
            $map        = $wpfm_category_map[ $key ];
            $map_config = $map[ 'map-config' ];

            if( $cat_lists ) {
                foreach( $cat_lists as $key => $term ) {
                    $map_keys = is_array( $map_config ) && !empty( $map_config ) ? array_column( $map_config, 'map-key' ) : [];
                    $map_key = array_search( $term->term_id, $map_keys );

                    if( $map_key == 0 || $map_key ) {
                        $map_array = $map_config[ $map_key ];
                        $map_value = $map_array[ 'map-value' ];
                        if( !empty( $map_value ) ) {
                            preg_match( "~^(\d+)~", $map_value, $m );
                            if( count( $m ) > 1 ) {
                                if( $m[ 1 ] ) {
                                    return utf8_decode( urldecode( $m[ 1 ] ) );
                                }
                                else {
                                    return $map_value;
                                }
                            }
                            else {
                                return $map_value;

                            }
                        }
                    }
                }
            }
        }
        return '';
    }


    /**
     * Get yoast seo title
     * @return string
     */
    public function get_yoast_seo_title()
    {
        $title = '';
        if( $this->product->get_type() == 'variation' ) {
            $product_id = $this->product->get_parent_id();
        }
        else {
            $product_id = $this->product->get_id();
        }
        if( function_exists( 'wpseo_replace_vars' ) ) {
            $wpseo_title = get_post_meta( $product_id, '_yoast_wpseo_title', true );
            if( $wpseo_title ) {
                $product_title_pattern = $wpseo_title;
            }
            else {
                $wpseo_titles          = get_option( 'wpseo_titles' );
                $product_title_pattern = $wpseo_titles[ 'title-product' ];
            }
            $title = wpseo_replace_vars( $product_title_pattern, get_post( $product_id ) );
        }
        if( !empty( $title ) ) {
            return $title;
        }
        else {
            return $this->product->get_title();
        }
    }


    /**
     * Get yoast meta descriptions
     * @return string
     */
    public function get_yoast_meta_description()
    {
        $description = '';
        if( $this->product->get_type() == 'variation' ) {
            $product_id = $this->product->get_parent_id();
        }
        else {
            $product_id = $this->product->get_id();
        }
        if( function_exists( 'wpseo_replace_vars' ) ) {
            $wpseo_meta_description = get_post_meta( $product_id, '_yoast_wpseo_metadesc', true );
            if( $wpseo_meta_description ) {
                $product_meta_desc_pattern = $wpseo_meta_description;
            }
            else {
                $wpseo_titles              = get_option( 'wpseo_titles' );
                $product_meta_desc_pattern = $wpseo_titles[ 'metadesc-product' ];
            }
            $description = wpseo_replace_vars( $product_meta_desc_pattern, get_post( $product_id ) );
        }

        if( !empty( $description ) ) {
            return $description;
        }
        else {
            return $this->product->get_description();
        }
    }


    /**
     * Get additional image url by key.
     *
     * @since    1.0.0
     */
    protected function get_additional_image( $key )
    {

        if( empty( $this->additional_images ) ) {
            $this->set_additional_images();
        }


        if( array_key_exists( $key, $this->additional_images ) ) {
            return $this->additional_images[ $key ];
        }

        return '';

    }

    /**
     * Retrieve a product's categories as a list with specified format.
     *
     * @param string $before Optional. Before list.
     * @param string $sep Optional. Separate items using this.
     * @param string $after Optional. After list.
     * @return string|false
     */
    protected function get_product_cats( $taxonomy, $before = '', $sep = ', ', $after = '' ) {
        if ( 'WC_Product_Variation' == get_class($this->product) ) {
            return $this->get_the_term_list( $this->product->get_parent_id(), $taxonomy, $before, $sep, $after );
        }else {
            return $this->get_the_term_list( $this->product->get_id(), $taxonomy, $before, $sep, $after );
        }
    }

    /**
     * Retrieve a product's category ids with comma separated
     * @since 7.2.18
     * @param string $before Optional. Before list.
     * @param string $sep Optional. Separate items using this.
     * @param string $after Optional. After list.
     * @return string|false
     */
    protected function get_product_cat_ids( $taxonomy, $before = '', $sep = ', ', $after = '' ) {
        if ( 'WC_Product_Variation' == get_class($this->product) ) {
            return $this->get_the_term_list( $this->product->get_parent_id(), $taxonomy, $before, $sep, $after, true );
        }else {
            return $this->get_the_term_list( $this->product->get_id(), $taxonomy, $before, $sep, $after, true );
        }
    }


    /**
     * @param string $before
     * @param string $sep
     * @param string $after
     * @return array
     */
    protected function get_spartoo_product_cats( $before = '', $sep = ', ', $after = '' )
    {
        $term_array = array();
        if( 'WC_Product_Variation' == get_class( $this->product ) ) {
            $terms = get_the_terms( $this->product->get_parent_id(), 'product_cat' );
        }
        else {
            $terms = get_the_terms( $this->product->get_id(), 'product_cat' );
        }

        $count = 0;
        if( $terms ) $count = count( $terms );
        if( $count > 1 ) {
            foreach( $terms as $term ) {
                $term_array[] = $term->name;
            }
        }
        return $term_array;
    }


    /**
     * Retrieve a product's categories as a list with specified format.
     *
     * @param string $before Optional. Before list.
     * @param string $sep Optional. Separate items using this.
     * @param string $after Optional. After list.
     * @return string|false
     */

    protected function get_product_cats_with_seperator( $taxonomy, $before = '', $sep = ' > ', $after = '' ) {

        if ( 'WC_Product_Variation' == get_class($this->product) ) {
            return $this->get_the_term_list_with_path( $this->product->get_parent_id(), $taxonomy, $before, $sep, $after );
        }else {
            return $this->get_the_term_list_with_path( $this->product->get_id(), $taxonomy, $before, $sep, $after );
        }
    }


    /**
     * Retrieve a product's categories as a list with specified format.
     *
     * @param string $before Optional. Before list.
     * @param string $sep Optional. Separate items using this.
     * @param string $after Optional. After list.
     * @return string
     */
    protected function get_yoast_product_cats_with_seperator( $before = '', $sep = ' > ', $after = '' )
    {
        $pr_id = $this->product->get_id();
        if( $this->product->is_type( 'variation' ) ) {
            $pr_id = $this->product->get_parent_id();
        }
        $primary_cat_id = get_post_meta( $pr_id, '_yoast_wpseo_primary_product_cat', true );
        $term_name      = [];
        if( $primary_cat_id ) {
            $product_cat = get_term( $primary_cat_id, 'product_cat' );
            if( isset( $product_cat->name ) ) {
                $term_name[]   = $product_cat->name;
                $term_name_arr = $this->get_cat_names_array( $pr_id, 'product_cat', $primary_cat_id, $term_name );
                if( is_array( $term_name_arr ) ) {
                    return implode( $sep, $term_name_arr );
                }
                return $this->get_product_cats( 'product_cat', '', ' > ', '' );
            }
        }
        return $this->get_product_cats( 'product_cat', '', ' > ', '' );
    }


    /**
     * Retrieve a product's sub categories as a list with specified format.
     *
     * @param string $sep Optional. Separate items using this.
     * @return string|false
     */
    protected function get_product_subcategory( $sep = ' > ' ) {
        if( $this->product && !is_wp_error( $this->product ) ) {
            if( 'WC_Product_Variation' == get_class( $this->product ) ) {
                $product_id = $this->product->get_parent_id();
            }
            else {
                $product_id = $this->product->get_id();
            }

            $terms = get_the_terms( $product_id, 'product_cat' );

            if( is_array( $terms ) && !empty( $terms ) ) {
                rsort( $terms );
                $terms = array_reverse( $terms );
            }

            if( empty( $terms ) || is_wp_error( $terms ) ) {
                return '';
            }
            $term_names = array();
            foreach( $terms as $term ) {
                $term_names[] = $term->name;
            }
            return join( $sep, $term_names );
        }
        return '';
    }

    /**
     * Retrieve a product's tags as a list with specified format.
     *
     *
     * @param string $before Optional. Before list.
     * @param string $sep Optional. Separate items using this.
     * @param string $after Optional. After list.
     * @return string|false
     */
    protected function get_product_tags( $before = '', $sep = ', ', $after = '' )
    {

        if( 'WC_Product_Variation' == get_class( $this->product ) ) {
            return $this->get_the_term_list( $this->product->get_parent_id(), 'product_tag', $before, $sep, $after );
        }
        else {
            return $this->get_the_term_list( $this->product->get_id(), 'product_tag', $before, $sep, $after );
        }
    }


    /**
     * get yoast primary category
     * @return string
     */
    public function get_seo_primary_cat( string $seo_name, bool $id = false )
    {
        if( is_wp_error( $this->product ) && !$this->product ) {
            return '';
        }

        $pr_id = $this->product->get_id();
        if( $this->product->is_type( 'variation' ) ) {
            $pr_id = $this->product->get_parent_id();
        }
        $meta_key = '';
        if( 'yoast' === $seo_name ) {
            $meta_key = '_yoast_wpseo_primary_product_cat';
        }
        elseif( 'rankmath' === $seo_name ) {
            $meta_key = 'rank_math_primary_product_cat';
        }

        if( !$meta_key ) {
            return '';
        }

        $primary_cat_id = get_post_meta( $pr_id, $meta_key, true );

        if( $id ) {
            return $primary_cat_id;
        }

        if( $primary_cat_id ) {
            $product_cat = get_term( $primary_cat_id, 'product_cat' );
            if( isset( $product_cat->name ) ) {
                return $product_cat->name;
            }
        }
        return $this->get_product_cats( 'product_cat' );
    }


    /**
     * @param string $before
     * @param string $sep
     * @param string $after
     * @return array
     */
    public function get_product_cats_for_sooqr( $before = '', $sep = ' > ', $after = '' )
    {
        $categories = [];
        if( 'WC_Product_Variation' == get_class( $this->product ) ) {
            $term_list = wp_get_post_terms( $this->product->get_parent_id(), 'product_cat' );
            foreach( $term_list as $term ) {
                if( $term->parent ) {
                    $categories[ 'subcategories' ][] = $term->name;
                }
                else {
                    $categories[ 'categories' ][] = $term->name;
                }
            }
            return $categories;
        }
        else {
            $term_list = wp_get_post_terms( $this->product->get_id(), 'product_cat' );
            foreach( $term_list as $term ) {
                if( $term->parent ) {
                    $categories[ 'subcategories' ][] = $term->name;
                }
                else {
                    $categories[ 'categories' ][] = $term->name;
                }
            }
            return $categories;
        }
    }


    /**
     * Retrieve a product's dynamic attributes as a list with specified format.
     *
     *
     * @param string $before Optional. Before list.
     * @param string $sep Optional. Separate items using this.
     * @param string $after Optional. After list.
     * @return string|false
     * @return string|false
     */
    protected function get_product_dynamic_tags( $id, $key, $before = '', $sep = ', ', $after = '' )
    {
        return $this->get_the_term_list( $id, $key, $before, $sep, $after );
    }

    /**
     * Retrieve a product's terms as a list with specified format.
     *
     *
     * @param int $id Product ID.
     * @param string $taxonomy Taxonomy name.
     * @param string $before Optional. Before list.
     * @param string $sep Optional. Separate items using this.
     * @param string $after Optional. After list.
     * @return string|false
     */
    protected function get_the_term_list( $id, $taxonomy, $before = '', $sep = ', ', $after = '', $ids = false )
    {
        $terms = wp_get_post_terms( $id, $taxonomy, array( 'hide_empty' => false, 'orderby' => 'term_id' ) );

        if( empty( $terms ) || is_wp_error( $terms ) ) {
            return '';
        }
        if( $ids ) {
            return implode( $sep, array_column($terms, 'term_id') );
        }
        $output       = array();
        $child_terms  = array();
        $parent_terms = array();

        foreach( $terms as $term ) {
            if( $term->parent ) {
                $child_terms = $this->get_cat_names_array( $id, $taxonomy, $term->parent, $parent_terms );
            }
            else {
                $parent_terms[] = $term->name;
            }
        }
        $output = array_merge( $parent_terms, $child_terms );

        return implode( ', ', $output );
    }


    /**
     *
     * @param $id
     * @param $taxonomy
     * @param string $before
     * @param string $sep
     * @param string $after
     * @return string
     */
    protected function get_the_term_list_with_path( $id, $taxonomy, $before = '', $sep = '', $after = '' ) {
        wpfm_switch_site_lang( $this->feed->wpml_language );
        $terms = wp_get_post_terms( $id, $taxonomy , array( 'hide_empty' => false, 'orderby' => 'term_id' ));

        if( empty( $terms ) || is_wp_error( $terms ) ) {
            return '';
        }

        $terms_id = array();
        foreach( $terms as $term ) {
            $terms_id[] = $term->term_id;
        }

        $output = array();

        foreach( $terms as $term ) {
            $term_names   = [];
            $term->name   = htmlspecialchars_decode( $term->name );
            $term_names[] = $term->name;


            $term_name_arr = $this->get_cat_names_array( $id, $taxonomy, $term->term_id, $term_names );

            if( !empty( array_diff( $term_name_arr, $term_names ) ) ) {

                foreach( $term_name_arr as $t_name ) {
                    $temp     = array();
                    $temp[]   = $term->name;
                    $temp[]   = $t_name;
                    $output[] = implode( $sep, $temp );
                }
            }
            else if( (($term->parent == 0) || (is_array( $terms_id ) && !in_array( $term->parent, $terms_id ))) && (is_array( $term_name_arr )) ) {
                $output[] = implode( $sep, $term_name_arr );
            }
        }
        return implode( ', ', $output );
    }

    protected function get_cat_names_array($id, $taxonomy, $parent, $term_name_array) {
        wpfm_switch_site_lang( $this->feed->wpml_language );
        $terms = wp_get_post_terms( $id, $taxonomy , array( 'hide_empty' => false, 'parent' => $parent,'orderby' => 'term_id' ));

        if( empty( $terms ) || is_wp_error( $terms ) ) {
            return $term_name_array;
        }
        $term_arr = array();
        foreach( $terms as $term ) {
            $term_name_array   = array();
            $term_name_array[] = $term->name;
            $term_name_array   = $this->get_cat_names_array( $id, $taxonomy, $term->term_id, $term_name_array );
            $term_arr[]        = $term_name_array[ 0 ];
        }
        return $term_arr;
    }


    /**
     * get product default attributes
     *
     * @param $product
     * @return mixed
     */
    protected function get_default_attributes( $product )
    {
        if( method_exists( $product, 'get_default_attributes' ) ) {
            return $product->get_default_attributes();
        }
        else {
            return $product->get_variation_default_attributes();
        }
    }


    /**
     * Get matching variation
     *
     * @param $product
     * @param $attributes
     * @return int Matching variation ID or 0.
     * @throws Exception
     */
    protected function find_matching_product_variation( $product, $attributes )
    {
        foreach( $attributes as $key => $value ) {
            if( strpos( $key, 'attribute_' ) === 0 ) {
                continue;
            }
            unset( $attributes[ $key ] );
            $attributes[ sprintf( 'attribute_%s', $key ) ] = $value;
        }
        if( class_exists( 'WC_Data_Store' ) ) {
            $data_store = WC_Data_Store::load( 'product' );
            return $data_store->find_matching_product_variation( $product, $attributes );
        }
        else {
            return $product->get_matching_variation( $attributes );
        }
    }


    /**
     * Set additional images url.
     *
     * @since    1.0.0
     */
    protected function set_additional_images()
    {

        $_product = $this->product;
        if( $this->product->is_type( 'variation' ) ) {
            $_product = wc_get_product( $this->product->get_parent_id() );
        }

        $img_ids = $_product->get_gallery_image_ids();

        $images = array();
        if( !empty( $img_ids ) ) {
            foreach( $img_ids as $key => $img_id ) {
                $img_key            = 'image_' . ( $key + 1 );
                $images[ $img_key ] = wp_get_attachment_url( $img_id );
            }
            // set images to the property
            $this->additional_images = $images;
        }

    }

    /**
     * Helper to check if a attribute is a Primary Attribute.
     *
     * @since    1.0.0
     */
    protected function is_primary_attr( $key )
    {
        return array_key_exists( $key, $this->product_meta_keys[ 'Primary Attributes' ] );
    }

    /**
     * Helper to check if a attribute is a Woodmart Attribute.
     *
     */
    protected function is_woodmart_attr( $key )
    {
        if( isset( $this->product_meta_keys[ 'Woodmart Image Gallery' ] ) ) {
            return array_key_exists( $key, $this->product_meta_keys[ 'Woodmart Image Gallery' ] );
        }
        return false;
    }

    /**
     * Helper to check if a attribute is a YOAST Attribute.
     *
     */
    protected function is_yoast_attr( $key )
    {
        if( isset( $this->product_meta_keys[ 'YOAST Attributes' ] ) ) {
            return array_key_exists( $key, $this->product_meta_keys[ 'YOAST Attributes' ] );
        }
        return false;
    }

    /**
     * Helper to check if a attribute is a RankMath Attribute.
     *
     */
    protected function is_rankmath_attr( $key )
    {
        if( isset( $this->product_meta_keys[ 'RankMath Attributes' ] ) ) {
            return array_key_exists( $key, $this->product_meta_keys[ 'RankMath Attributes' ] );
        }
        return false;
    }

    /**
     * Helper to check if a attribute is a YOAST Attribute.
     *
     */
    protected function is_price_attr( $key )
    {
        if( isset( $this->product_meta_keys[ 'Price Attributes' ] ) ) {
            return array_key_exists( $key, $this->product_meta_keys[ 'Price Attributes' ] );
        }
        return false;
    }

    /**
     * Helper to check if a attribute is a WooCommerce Attribute.
     *
     */
    protected function is_wc_brand_attr( $key )
    {
        if( isset( $this->product_meta_keys[ 'Woocommerce Brand' ] ) ) {
            return array_key_exists( $key, $this->product_meta_keys[ 'Woocommerce Brand' ] );
        }
        return false;
    }

    /**
     * Helper to check if a attribute is a Brands for WooCommerce by BeRocket Attribute.
     *
     */
    protected function is_berocket_brand_attr( $key )
    {
        if( isset( $this->product_meta_keys[ 'Brands for WooCommerce' ] ) ) {
            return array_key_exists( $key, $this->product_meta_keys[ 'Brands for WooCommerce' ] );
        }
        return false;
    }

    /**
     * Helper to check if a attribute is a Perfect Brand Attribute.
     *
     */
    protected function is_perfect_attr( $key )
    {
        if( isset( $this->product_meta_keys[ 'Perfect Brand' ] ) ) {
            return array_key_exists( $key, $this->product_meta_keys[ 'Perfect Brand' ] );
        }
        return false;
    }

    /**
     * Helper to check if a attribute is a Image Attribute.
     *
     * @since    1.0.0
     */
    protected function is_image_attr( $key )
    {
        return array_key_exists( $key, $this->product_meta_keys[ 'Image Attributes' ] );
    }

    /**
     * Helper to check if a attribute is a Product Attribute.
     *
     * @since    1.0.0
     */
    protected function is_product_attr( $key )
    {
        return array_key_exists( $key, $this->product_meta_keys[ 'Product Attributes' ] ) || array_key_exists( $key, $this->product_meta_keys[ 'Product Attributes' ] );
    }

    /**
     * Helper to check if a attribute is a Glami Attribute.
     */
    protected function is_glami_attr( $key )
    {
        return isset( $this->product_meta_keys[ 'Glami Attributes' ] )
            ? array_key_exists( $key, $this->product_meta_keys[ 'Glami Attributes' ] ) : false;
    }

    /**
     * Helper to check if a attribute is a Dropship Attribute.
     */
    protected function is_dropship_attr( $key )
    {
        return isset( $this->product_meta_keys[ 'Dropship by Mantella' ] )
            ? array_key_exists( $key, $this->product_meta_keys[ 'Dropship by Mantella' ] ) : false;
    }

    /**
     * Helper to check if a attribute is a WPFM Custom Attribute.
     */
    protected function is_wpfm_custom_attr( $key )
    {
        return isset( $this->product_meta_keys[ 'WPFM Custom Attributes' ] )
            ? array_key_exists( $key, $this->product_meta_keys[ 'WPFM Custom Attributes' ] ) : false;
    }


    /**
     * Helper to check if a attribute is a Product dynamic Attribute.
     *
     * @since    1.0.0
     */
    protected function is_product_dynamic_attr( $key )
    {
        return array_key_exists( $key, $this->product_meta_keys[ 'Product Variation Attributes' ] );
    }


    /**
     * Helper to check if a attribute is a Product Custom Attribute.
     *
     * @since    1.0.0
     */
    protected function is_product_custom_attr( $key )
    {
        return array_key_exists( $key, $this->product_meta_keys[ 'Product Custom Attributes' ] );
    }

    /**
     * Helper to check if a attribute is a Category Mapper.
     *
     * @since    1.0.0
     */
    protected function is_product_category_mapper_attr( $key )
    {
        return array_key_exists( $key, $this->product_meta_keys[ 'Category Map' ] );
    }

    /**
     * Helper to check if a attribute is a Category Mapper.
     *
     * @since    1.0.0
     */
    protected function is_date_attr( $key )
    {
        return array_key_exists( $key, $this->product_meta_keys[ 'Date Attributes' ] );
    }

    /**
     * Helper to check if a attribute is a Product custom taxonomy
     * @param $key
     * @return bool
     */
    protected function is_product_custom_tax( $key ) {
        if( isset( $this->product_meta_keys['Product Custom Taxonomies'] ) ) {
            return array_key_exists( $key, $this->product_meta_keys['Product Custom Taxonomies'] );
        }
        return false;
    }

    /**
     * Helper to check if a attribute is a WooDiscount Rules Attribute
     * @param $key
     * @return bool
     */
    protected function is_woo_discount_rules( $key ) {
        if( isset( $this->product_meta_keys['Woo Discount Rules'] ) ) {
            return array_key_exists( $key, $this->product_meta_keys['Woo Discount Rules'] );
        }
        return false;
    }


    /**
     * @desc Helper to check if given attribute is a Shipping Attributes
     * @since 7.2.9
     * @param $key
     * @return bool
     */
    protected function is_shipping_attr( $key ) {
        if( isset( $this->product_meta_keys['Shipping Attributes'] ) ) {
            return array_key_exists( $key, $this->product_meta_keys['Shipping Attributes'] );
        }
        return false;
    }


    /**
     * @desc Helper to check if given attribute is a Shipping Attributes
     * @since 7.2.9
     * @param $key
     * @return bool
     */
    protected function is_tax_attr( $key ) {
        if( isset( $this->product_meta_keys['Tax Attributes'] ) ) {
            return array_key_exists( $key, $this->product_meta_keys['Tax Attributes'] );
        }
        return false;
    }


    /**
     * @desc Helper to check if given attribute
     * is an EAN by WooCommerce Attributes
     * @since 7.2.19
     * @param $key
     * @return bool
     */
    protected function is_ean_by_wc_attr( $key ) {
        if( isset( $this->product_meta_keys['EAN by WooCommerce'] ) ) {
            return array_key_exists( $key, $this->product_meta_keys['EAN by WooCommerce'] );
        }
        return false;
    }


    /**
     * @desc Helper to check if given attribute
     * is a Discount Rules and Dynamic Pricing for WooCommerce Attributes
     * @since 7.2.20
     * @param $key
     * @return bool
     */
    protected function is_discount_price_by_asana_attr( $key ) {
        if( isset( $this->product_meta_keys['Discounted Price - by Asana Plugins'] ) ) {
            return array_key_exists( $key, $this->product_meta_keys['Discounted Price - by Asana Plugins'] );
        }
        return false;
    }


    /**
     * Helper to get condition of a product.
     *
     * @since    1.0.0
     */
    protected function get_condition()
    {
        return 'New';
    }


    /**
     * Helper to get parent product id of a product.
     *
     * @return int
     */
    protected function get_item_group_id()
    {
        if( $this->product->is_type( 'variation' ) ) {
            return $this->product->get_parent_id();
        }
        return '';
    }


    /**
     * Helper to get availability of a product
     *
     * @since    1.0.0
     */
    protected function get_availability()
    {
        if( $this->product->is_on_backorder() ) {
            return apply_filters( 'wpfm_product_availability_backorder', 'out_of_stock' );
        }
        elseif( $this->product->is_in_stock() == TRUE ) {
            return apply_filters( 'wpfm_product_availability', 'in_stock' );
        }
        else {
            return apply_filters( 'wpfm_product_availability', 'out_of_stock' );
        }
    }

    /**
     * Helper to get availability underscore of a product
     *
     * @since    1.0.0
     */
    protected function get_availability_underscore()
    {
        if( $this->product->is_on_backorder() ) {
            return apply_filters( 'wpfm_product_availability_backorder', 'out of stock' );
        }
        elseif( $this->product->is_in_stock() == TRUE ) {
            return apply_filters( 'wpfm_product_availability', 'in stock' );
        }
        else {
            return apply_filters( 'wpfm_product_availability', 'out of stock' );
        }
    }

    /**
     * Helper to get availability underscore of a product
     *
     * @since    1.0.0
     */
    protected function get_availability_backorder_instock()
    {
        if( $this->product->is_on_backorder() ) {
            return apply_filters( 'wpfm_product_availability_backorder', 'in_stock' );
        }
        elseif( $this->product->is_in_stock() == TRUE ) {
            return apply_filters( 'wpfm_product_availability', 'in_stock' );
        }
        else {
            return apply_filters( 'wpfm_product_availability', 'out_of_stock' );
        }
    }

    /**
     * Helper to get availability underscore of a product
     *
     * @since    1.0.0
     */
    protected function get_availability_backorder()
    {
        if( $this->product->is_on_backorder() ) {
            return apply_filters( 'wpfm_product_availability_backorder', 'on_backorder' );
        }
        elseif( $this->product->is_in_stock() == TRUE ) {
            return apply_filters( 'wpfm_product_availability', 'in_stock' );
        }
        else {
            return apply_filters( 'wpfm_product_availability', 'out_of_stock' );
        }
    }


    /**
     * @return string
     */
    protected function get_stock()
    {
        if( $this->product->is_in_stock() == TRUE ) {
            return 'Y';
        }
        else {
            return 'N';
        }
    }

    /**
     * Add neccessary prefix/suffix to a value.
     *
     * @since    1.0.0
     */
    protected function maybe_add_prefix_suffix( $val, $rule )
    {
        $prefix = $rule[ 'prefix' ];
        $suffix = $rule[ 'suffix' ];

        if( !empty( $prefix ) ) {
            $val = $val ? $prefix . $val : '';
        }

        if( !empty( $suffix ) ) {
            $val = $val ? $val . $suffix : '';
        }

        return $val;
    }

    /**
     * Escape a value with specific escape method.
     *
     * @since    1.0.0
     */
    protected function maybe_escape( $val, $escape )
    {
        switch( $escape ) {
            case 'strip_tags':
                $val            = preg_replace( '/(?:<|&lt;).*?(?:>|&gt;)/', '', $val );
                $striped_string = strip_tags( $val );
                return trim( $striped_string );
            case 'utf_8_encode':
                return utf8_encode( $val );
            case 'htmlentities':
                return htmlentities( $val );
            case 'integer':
            case 'price':
                return intval( $val );
            case 'remove_space':
                return trim( preg_replace( '/\s+/', '', $val ) );
            case 'remove_tab':
                return trim( preg_replace( '/\t+/', '', $val ) );
            case 'remove_shortcodes_and_tags':
                $val            = preg_replace( '/(?:<|&lt;).*?(?:>|&gt;)/', '', $val );
                $striped_string = strip_tags( $val );
                if( substr( $striped_string, -1 ) == " " ) {
                    $striped_string = preg_replace( '#\[[^\]]+\]#', '', $striped_string );
                    return rtrim( strip_shortcodes( $striped_string ) );
                }

                $striped_string = preg_replace( '#\[[^\]]+\]#', '', $striped_string );
                return strip_shortcodes( $striped_string );

            case 'remove_shortcodes':
                $val = preg_replace( '#\[[^\]]+\]#', '', $val );
                return strip_shortcodes( $val );
            case 'remove_special':
                return filter_var( $val, FILTER_SANITIZE_STRING );
            case 'cdata':
                return $val && $val !== '' ? "<![CDATA[{$val}]]>" : $val;
            case 'cdata_without_space':
                return $val ? "CDATA$val" : $val;
            case 'remove_underscore':
                return str_replace( '_', ' ', $val );
            case 'remove_decimal':
                if( $this->checkIfFloat( $val ) ) {
                    $val = number_format( $val, 2, '.', '' );
                    for( $i = 0; $i < 2; $i++ ) {
                        $val = $val * 10;
                    }
                }
                else {
                    return intval( $val ) * 100;
                }
                return $val;
            case 'add_two_decimal':
                return number_format( (float)$val, 2 );
            case 'remove_hyphen':
                return str_replace( '-', '', $val );

            case 'remove_hyphen_space':
                return str_replace( '-', ' ', $val );

            case 'replace_space_with_hyphen':
                return str_replace( ' ', '-', $val );

            case 'first_word_uppercase':
                return ucfirst( strtolower( $val ) );
            case 'comma_decimal':
                if ( is_numeric( $val ) ) {
                    return number_format( $val, 2, ',', '' );
                }
                return $val;
            case 'replace_comma_with_backslash':
                return str_replace( ',', '/', str_replace( ', ', '/', $val ) );
                return $val;
            case 'replace_decimal_with_hyphen':
                return str_replace( '.', '-', str_replace( '. ', '-', $val ) );

            default:
                return $val;

        }
    }

    /**
     * Replace tab if exists
     */
    protected function tab_replace( $val )
    {
        if( $this->feed_format === 'text' ) {
            return preg_replace('/[ ]{2,}|[\t]|[\n]/', ' ', trim($val));
        }
        return $val;
    }


    /**
     * check if float
     *
     * @param $num
     * @return bool
     */
    private function checkIfFloat( $num )
    {
        return is_float( $num ) || is_numeric( $num ) && ( (float)$num != (int)$num );
    }


    /**
     * Limit the output chars to specified length.
     *
     * @since    1.0.0
     */
    protected function maybe_limit( $val, $limit )
    {
        $limit = (int)$limit;
        if( $limit > 0 ) {
            return substr( $val, 0, $limit );
        }
        return $val;
    }

    /**
     * Setup variation data if current product is a variable product.
     *
     * @since    1.0.0
     */
    protected function maybe_set_variation_data()
    {

        if( 'WC_Product_Variation' != get_class( $this->product ) ) {
            return;
        }

        $variant_atts = $this->product->get_variation_attributes();

        foreach( $variant_atts as $key => $value ) {
            $key = str_replace( 'attribute_pa_', '', $key );

            if( in_array( $key, $this->variant_atts ) ) {
                $this->data[ $key ] = $value;
            }
        }

    }


    /**
     * Remove shortcode
     * from content
     *
     * @param $content
     * @return string
     * @since    2.0.3
     */

    public function remove_short_codes( $content )
    {
        if( empty( $content ) ) {
            return "";
        }
        $content = $this->remove_invalid_xml( $content );
        return strip_shortcodes( $content );
    }

    /**
     * Removes invalid XML
     *
     * @param string $value
     * @return string
     */
    public function remove_invalid_xml( $value )
    {

        $ret     = "";
        $current = "";
        if( empty( $value ) ) {
            return $ret;
        }

        $length = strlen( $value );
        for( $i = 0; $i < $length; $i++ ) {
            $current = ord( $value[ $i ] );
            if( ( $current == 0x9 ) ||
                ( $current == 0xA ) ||
                ( $current == 0xD ) ||
                ( ( $current >= 0x20 ) && ( $current <= 0xD7FF ) ) ||
                ( ( $current >= 0xE000 ) && ( $current <= 0xFFFD ) ) ||
                ( ( $current >= 0x10000 ) && ( $current <= 0x10FFFF ) ) ) {
                $ret .= chr( $current );
            }
            else {
                $ret .= " ";
            }
        }
        return $ret;

    }


    /**
     * calculate the value of identifier_exists
     *
     * @return string
     * @since    1.2.5
     */

    public function calculate_identifier_exists( $data )
    {

        $identifier_exists = "no";

        if( array_key_exists( "brand", $data ) and ( $data[ 'brand' ] != "" ) ) {
            if( ( array_key_exists( "gtin", $data ) ) and ( $data[ 'gtin' ] != "" ) ) {
                $identifier_exists = "yes";
            }
            elseif( ( array_key_exists( "mpn", $data ) ) and ( $data[ 'mpn' ] != "" ) ) {
                $identifier_exists = "yes";
            }
            else {
                $identifier_exists = "no";
            }
        }
        else {
            if( ( array_key_exists( "gtin", $data ) ) and ( $data[ 'gtin' ] != "" ) ) {
                $identifier_exists = "no";
            }
            elseif( ( array_key_exists( "mpn", $data ) ) and ( $data[ 'mpn' ] != "" ) ) {
                $identifier_exists = "no";
            }
            else {
                $identifier_exists = "no";
            }
        }

        return $identifier_exists;
    }


    /**
     * @desc Get shipping method(s)
     * available for the selected feed country
     * @return bool
     * @since 7.2.11
     */
    public function get_shipping_methods() {
        $feed_location          = explode( ':', $this->feed_country );
        $state                  = isset( $feed_location[ 0 ] ) ? $feed_location[ 0 ] : '';
        $country                = isset( $feed_location[ 1 ] ) ? $feed_location[ 1 ] : '';
        $continent              = isset( $feed_location[ 2 ] ) ? $feed_location[ 2 ] : '';
        $default_shipping_zones = wpfm_get_cached_data( 'wc_shipping_zones_' . $continent . $country . $state . $this->feed_zip_codes );

        if( 'all' !== $this->feed_country && !$default_shipping_zones ) {
            $wc_shipping_zones = WC_Shipping_Zones::get_zones();

            foreach( $wc_shipping_zones as $zone ) {
                if( isset( $zone[ 'zone_locations' ] ) && is_array( $zone[ 'zone_locations' ] ) && !empty( $zone[ 'zone_locations' ] ) ) {
                    $zone_locations = array_column( $zone[ 'zone_locations' ], 'code' );

                    if( is_array( $zone_locations ) && !empty( $zone_locations ) && isset( $zone[ 'shipping_methods' ] ) && ( in_array( $country, $zone_locations ) || in_array( $this->feed_zip_codes, $zone_locations ) ) ) {
                        foreach( $zone[ 'shipping_methods' ] as $method ) {
                            if( $method->is_enabled() ) {
                                $service  = '';
                                $price    = 0;
                                $instance = [];

                                if( isset( $method->instance_settings[ 'cost' ] ) ) {
                                    $price = (float)$method->instance_settings[ 'cost' ];
                                }

                                if( isset( $zone[ 'zone_name' ] ) ) {
                                    $service .= $zone[ 'zone_name' ];
                                }
                                if( isset( $method->instance_settings[ 'title' ] ) ) {
                                    $service .= ' ' . $method->instance_settings[ 'title' ];

                                    if( 'WC_Shipping_Flat_Rate' === get_class( $method ) ) {
                                        $instance = $method->instance_settings;
                                    }
                                }

                                $default_shipping_zones[] = [
                                    'country'  => $country,
                                    'region'   => $state,
                                    'service'  => $service . ' ' . $country,
                                    'price'    => $price,
                                    'instance' => $instance
                                ];
                            }
                        }
                    }
                }
            }
            wpfm_set_cached_data( 'wc_shipping_zones_' . $continent . $country . $state . $this->feed_zip_codes, $default_shipping_zones );
        }
        return $default_shipping_zones;
    }


    /**
     * @desc Add shipping class price/ no class price with base class
     * @since 7.2.20
     * @param $shipping_methods
     * @param $rule
     * @return array|mixed
     */
    private function add_class_no_class_cost( $shipping_methods = [], $rule = [] ) {
        if( !is_wp_error( $this->product ) && $this->product && !empty( $shipping_methods ) ) {
            for( $index = 0; $index < sizeof( $shipping_methods ); $index++ ) {
                if( isset( $shipping_methods[ $index ][ 'instance' ] ) && !is_wp_error( $shipping_methods[ $index ][ 'instance' ] ) && is_array( $shipping_methods[ $index ][ 'instance' ] ) && isset( $shipping_methods[ $index ][ 'price' ] ) ) {
                    if( !empty( $shipping_methods[ $index ][ 'instance' ] ) ) {
                        $class_id = $this->product->get_shipping_class_id();
                        if( 'variation' === $this->product->get_type() ) {
                            $product_id = $this->product->get_parent_id();
                            if( $product_id ) {
                                $class_id = wc_get_product( $product_id )->get_shipping_class_id();
                            }
                        }
                        if( isset( $shipping_methods[ $index ][ 'instance' ][ 'class_cost_' . $class_id ] ) && $shipping_methods[ $index ][ 'instance' ][ 'class_cost_' . $class_id ] ) {
                            $shipping_methods[ $index ][ 'price' ] += $shipping_methods[ $index ][ 'instance' ][ 'class_cost_' . $class_id ];
                        }
                        elseif( isset( $shipping_methods[ $index ][ 'instance' ][ 'no_class_cost' ] ) && $shipping_methods[ $index ][ 'instance' ][ 'no_class_cost' ] ) {
                            $shipping_methods[ $index ][ 'price' ] += $shipping_methods[ $index ][ 'instance' ][ 'no_class_cost' ];
                        }
                    }
                    unset( $shipping_methods[ $index ][ 'instance' ] );
                    if( isset( $rule[ 'prefix' ] ) ) {
                        $shipping_methods[ $index ][ 'price' ] = $rule[ 'prefix' ] . $shipping_methods[ $index ][ 'price' ];
                    }
                    if( isset( $rule[ 'suffix' ] ) ) {
                        $shipping_methods[ $index ][ 'price' ] = $shipping_methods[ $index ][ 'price' ] . $rule[ 'suffix' ];
                    }
                }
            }
        }
        return $shipping_methods;
    }

    /**
     * Check if this product is child product or not
     *
     * @return bool
     * @since    1.0.0
     */
    protected function is_children()
    {
        return $this->product->get_parent_id() ? true : false;
    }

    function __call( $name, $arguments )
    {
        // TODO: Implement __call() method.
    }

    public function get_args( $raw_args = false )
    {
        if( !$raw_args )
            $raw_args = $this->args;

        $args = array(
            "post_type" => array( "product", "product_variation" )
        );
        if( isset( $raw_args[ "type" ] ) && $raw_args[ "type" ] == "by-id" ) {
            $args[ 'post__in' ] = explode( ",", $raw_args[ "ids" ] );
        }
        else {
            //Tax queries
            if( isset( $raw_args[ "tax_query" ][ "queries" ] ) ) {
                $args[ "tax_query" ]               = array();
                $args[ "tax_query" ][ "relation" ] = $raw_args[ "tax_query" ][ "relation" ];
                foreach( $raw_args[ "tax_query" ][ "queries" ] as $query ) {
                    array_push( $args[ "tax_query" ], $query );
                }
            }

            //Metas
            if( isset( $raw_args[ "meta_query" ][ "queries" ] ) ) {
                $args[ "meta_query" ]               = array();
                $args[ "meta_query" ][ "relation" ] = $raw_args[ "meta_query" ][ "relation" ];
                foreach( $raw_args[ "meta_query" ][ "queries" ] as $query ) {
                    //Some operators expect an array as value
                    $array_operators = array( 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN' );
                    if( in_array( $query[ "compare" ], $array_operators ) )
                        $query[ "value" ] = explode( ",", $query[ "value" ] );
                    array_push( $args[ "meta_query" ], $query );
                }
            }

            //Other parameters
            $other_parameters = array( "author__in", "post__not_in" );
            foreach( $other_parameters as $parameter ) {
                if( !isset( $raw_args[ $parameter ] ) )
                    continue;
                if( $parameter == "post__not_in" )
                    $args[ $parameter ] = explode( ",", $raw_args[ $parameter ] );
                else if( $parameter == "author__in" && $raw_args[ $parameter ] == array( "" ) )
                    continue;
                else
                    $args[ $parameter ] = $raw_args[ $parameter ];
            }
        }

        $args[ "nopaging" ] = true;

        return $args;
    }

    /**
     * @param string $string
     * @return string
     */
    private function safeCharEncodeURL( $string )
    {
        return str_replace(
            array( '%', '[', ']', '{', '}', '|', ' ', '"', '<', '>', '#', '\\', '^', '~', '`' ),
            array( '%25', '%5b', '%5d', '%7b', '%7d', '%7c', '%20', '%22', '%3c', '%3e', '%23', '%5c', '%5e', '%7e', '%60' ),
            $string );
    }


    /**
     * @desc Process attribute value if needed.
     *
     * @since 7.2.8
     * @param $value
     * @param $rule
     * @return array|mixed|string|string[]|null
     */
    protected function maybe_processing_needed( $value, $rule ) {

        if ( !is_array( $value ) ) {
            // maybe escape
            $escape = isset( $rule[ 'escape' ] ) ? $rule[ 'escape' ] : '';
            if ( is_array( $escape ) ) {
                foreach ( $escape as $esc ) {
                    $value = $this->maybe_escape( $value, $esc );
                }
            }
            else {
                $value = $this->maybe_escape( $value, $escape );
            }
            // maybe add prefix/suffix
            $value = $this->maybe_add_prefix_suffix($value, $rule);
            // maybe limit
            $value = $this->maybe_limit( $value, isset( $rule[ 'limit' ] ) ? $rule[ 'limit' ] : '' );

            $value = $this->tab_replace( $value );
        }
        return $value;
    }
}