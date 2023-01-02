<?php
/**
 * Abstract Rex Product Feed Generator
 *
 * An abstract class definition that includes functions used for generating xml feed.
 *
 * @link       https://rextheme.com
 * @since      1.0.0
 * The XML Feed Generator.
 *
 * This is used to generate xml feed based on given settings.
 *
 * @since      1.0.0
 * @package    Rex_Product_Feed_Abstract_Generator
 * @author     RexTheme <info@rextheme.com>
 */
abstract class Rex_Product_Feed_Abstract_Generator
{

    /**
     * The feed Merchant.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator $merchant Contains merchant name of the feed.
     */
    public $merchant;
    /**
     * The feed rules containing all attributes and their value mappings for the feed.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator $feed_config Contains attributes and value mappings for the feed.
     */
    public $feed_config;
    /**
     * Append variation
     * product name
     *
     * @since    3.2
     * @access   private
     * @var      Rex_Product_Feed_Abstract_Generator $append_variation
     */
    public $append_variation;
    /**
     *
     * @var Rex_Product_Feed_Abstract_Generator $aelia_currency
     */
    public $aelia_currency;
    /**
     *
     * @var Rex_Product_Feed_Abstract_Generator $wmc_currency
     */
    public $wmc_currency;
    /**
     * @var $analytics
     */
    public $analytics;
    /**
     * @var $analytics_params
     */
    public $analytics_params = [];
    public $wcml_currency;
    public $wcml;
    public $product_meta_keys;
    public $product_condition;
    public $feed_merchant;
    private $private_product = false;
    /**
     * The Product/Feed Config.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator    config    Feed config.
     */
    protected $config;
    /**
     * The Product/Feed ID.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator    id    Feed id.
     */
    protected $id;
    /**
     * Feed Title.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator    title    Feed title
     */
    protected $title;
    /**
     * Feed Description.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator    desc    Feed description.
     */
    protected $desc;
    /**
     * Feed Link.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator    link    Feed link.
     */
    protected $link;
    /**
     * The feed format.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator $feed_format Contains format of the feed.
     */
    protected $feed_format;
    /**
     * The feed filter rules containing all condition and values for the feed.
     *
     * @since    1.1.10
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator $feed_filters Contains condition and value for the feed.
     */
    protected $feed_filters;
    /**
     * The Product Query args to retrieve specific products for making the Feed.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator $products_args Contains products query args for feed.
     */
    protected $products_args;
    /**
     * Array contains all products.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator $products Contains all products to make feed.
     */
    protected $products;
    /**
     * Array contains all variable products for creating feed with variations.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator $products Contains all products to make feed.
     */
    protected $variable_products;
    /**
     * Array contains all variable products for creating feed with variations.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator $products Contains all products to make feed.
     */
    protected $grouped_products;
    /**
     * The Feed.
     * @since    1.0.0
     * @access   protected
     * @var Rex_Product_Feed_Abstract_Generator $feed Feed as text.
     */
    protected $feed;
    /**
     * Allowed Product
     *
     * @since    1.1.10
     * @access   private
     * @var      bool $allowed
     */
    protected $allowed;
    /**
     * Product Filter Condition
     *
     * @since    1.1.10
     * @access   private
     * @var      bool $allowed
     */
    protected $product_filter_condition;
    /**
     * Post per page
     *
     * @since    1.0.0
     * @access   private
     * @var      Rex_Product_Feed_Abstract_Generator $posts_per_page
     */
    protected $posts_per_page;
    /**
     * Product Scope
     *
     * @since    1.1.10
     * @access   private
     * @var      Rex_Product_Feed_Abstract_Generator $product_scope
     */
    protected $product_scope;
    /**
     * Product Offset
     *
     * @since    1.3.0
     * @access   private
     * @var      Rex_Product_Feed_Abstract_Generator $offset
     */
    protected $offset;
    /**
     * Product Current Batch
     *
     * @since    1.3.0
     * @access   private
     * @var      Rex_Product_Feed_Abstract_Generator $batch
     */
    protected $batch;
    /**
     * Product Total Batch
     *
     * @since    1.3.0
     * @access   private
     * @var      Rex_Product_Feed_Abstract_Generator $tbatch
     */
    protected $tbatch;
    /**
     * Bypass functionality from child
     *
     * @since    2.0.0
     * @access   private
     * @var      Rex_Product_Feed_Abstract_Generator $bypass
     */
    protected $bypass;
    /**
     * Variable Product include/exclude
     *
     * @since    2.0.1
     * @access   private
     * @var      Rex_Product_Feed_Abstract_Generator $variable_product
     */
    protected $variable_product;
    /**
     * Product variations include/exclude
     *
     * @since    2.0.1
     * @access   private
     * @var      Rex_Product_Feed_Abstract_Generator $variations
     */
    protected $variations;
    /**
     * parent product include/exclude
     *
     * @since    2.0.3
     * @access   private
     * @var      Rex_Product_Feed_Abstract_Generator $parent_product
     */
    protected $parent_product;
    /**
     * wpml enable
     *
     * @since    2.2.2
     * @access   private
     * @var      Rex_Product_Feed_Abstract_Generator $wpml_language
     */
    public $wpml_language;
    /**
     * enable logging
     *
     * @var Rex_Product_Feed_Abstract_Generator $is_logging_enabled
     */
    protected $is_logging_enabled;
    /**
     *
     * @var Rex_Product_Feed_Abstract_Generator $exclude_hidden_products
     */
    protected $exclude_hidden_products;
    /**
     *
     * @var Rex_Product_Feed_Abstract_Generator $rex_feed_skip_product
     */
    protected $rex_feed_skip_product;
    /**
     *
     * @var Rex_Product_Feed_Abstract_Generator $rex_feed_skip_row
     */
    protected $rex_feed_skip_row;
    /**
     *
     * @var Rex_Product_Feed_Abstract_Generator $feed_separator
     */
    protected $feed_separator;
    /**
     *
     * @var Rex_Product_Feed_Abstract_Generator $include_out_of_stock
     */
    protected $include_out_of_stock;

    protected $include_zero_priced;

    protected $feed_string_footer = '';

    protected $item_wrapper = '';

    public $feed_rules;

    protected $custom_filter_option;

    protected $custom_filter_var_exclude = false;

    /**
     * @desc Variable to store country to retrieve
     * shipping and tax related values
     * @since 7.2.9
     * @var $feed_country
     */
    protected $feed_country;

    /**
     * @desc Variable to store wrapper value for custom xml feed
     * @since 7.2.18
     * @var $custom_wrapper
     */
    protected $custom_wrapper;

    /**
     * @desc Variable to store items wrapper value for custom xml feed
     * @since 7.2.18
     * @var $custom_items_wrapper
     */
    protected $custom_items_wrapper;

    /**
     * @desc Variable to store wrapper element value for custom xml feed
     * @since 7.2.18
     * @var $custom_wrapper_el
     */
    protected $custom_wrapper_el;

    /**
     * @desc Variable to store custom
     * xml file header option to exclude/include
     * @since 7.2.19
     * @var $custom_xml_header
     */
    protected $custom_xml_header;

    /**
     * @desc Variable to store country to retrieve
     * shipping and tax related values
     * @since 7.2.9
     * @var $feed_zip_code
     */
    protected $feed_zip_code;

    /**
     * @desc Variable to store
     * company name for yandex xml feed
     * @since 7.2.21
     * @var $yandex_company_name
     */
    protected $yandex_company_name;

    /**
     * @desc Variable to store option to
     * include/exclude old price for yandex xml feed
     * @since 7.2.21
     * @var $yandex_company_name
     */
    protected $yandex_old_price;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     * @param $config
     * @param $bypass
     * @since    1.0.0
     */
    public function __construct( $config, $bypass = false, $product_ids = array() )
    {
        $this->products           = [];
        $this->variable_products  = [];
        $this->grouped_products   = [];
        $this->config             = $config;
        $this->is_logging_enabled = is_wpfm_logging_enabled();
        $this->bypass             = $bypass;
        if ( $this->bypass ) {
            $this->id                      = isset( $config[ 'info' ][ 'post_id' ] ) ? $config[ 'info' ][ 'post_id' ] : 0;
            $this->title                   = isset( $config[ 'info' ][ 'title' ] ) && '' !== $config[ 'info' ][ 'title' ] ? $config[ 'info' ][ 'title' ] : get_bloginfo();
            $this->desc                    = isset( $config[ 'info' ][ 'desc' ] ) && '' !== $config[ 'info' ][ 'desc' ] ? $config[ 'info' ][ 'desc' ] : get_bloginfo();
            $this->batch                   = isset( $config[ 'info' ][ 'batch' ] ) ? (int)$config[ 'info' ][ 'batch' ] : 1;
            $this->tbatch                  = isset( $config[ 'info' ][ 'total_batch' ] ) ? (int)$config[ 'info' ][ 'total_batch' ] : 1;
            $this->offset                  = isset( $config[ 'info' ][ 'offset' ] ) ? (int)$config[ 'info' ][ 'offset' ] : -1;
            $this->posts_per_page          = isset( $config[ 'info' ][ 'per_page' ] ) ? (int)$config[ 'info' ][ 'per_page' ] : 0;
            $this->feed_config             = isset( $config[ 'feed_config' ] ) ? $config[ 'feed_config' ] : [];
            $this->feed_filters            = isset( $config[ 'feed_filter' ] ) ? $config[ 'feed_filter' ] : [];
            $this->feed_rules              = isset( $config[ 'feed_rules' ] ) ? $config[ 'feed_rules' ] : [];
            $this->variations              = isset( $config[ 'include_variations' ] ) ? $config[ 'include_variations' ] : '';
            $this->parent_product          = isset( $config[ 'parent_product' ] ) ? $config[ 'parent_product' ] : '';
            $this->variable_product        = isset( $config[ 'variable_product' ] ) ? $config[ 'variable_product' ] : '';
            $this->append_variation        = isset( $config[ 'append_variations' ] ) ? $config[ 'append_variations' ] : '';
            $this->include_out_of_stock    = isset( $config[ 'include_out_of_stock' ] ) && $config[ 'include_out_of_stock' ] === 'yes' ? true : false;
            $this->include_zero_priced     = isset( $config[ 'include_zero_price_products' ] ) && $config[ 'include_zero_price_products' ] === 'yes' ? true : false;
            $this->exclude_hidden_products = isset( $config[ 'exclude_hidden_products' ] ) ? $config[ 'exclude_hidden_products' ] : '';
            $this->feed_separator          = isset( $config[ 'feed_separator' ] ) ? $config[ 'feed_separator' ] : '';
            $this->rex_feed_skip_product   = isset( $config[ 'skip_product' ] ) ? $config[ 'skip_product' ] : false;
            $this->rex_feed_skip_row       = isset( $config[ 'skip_row' ] ) ? $config[ 'skip_row' ] : false;
            $this->wpml_language           = isset( $config[ 'wpml_language' ] ) ? $config[ 'wpml_language' ] : '';
            $this->wcml                    = isset( $config[ 'wcml' ] ) ? $config[ 'wcml' ] : '';
            $this->wcml_currency           = isset( $config[ 'wcml_currency' ] ) ? $config[ 'wcml_currency' ] : 'USD';;
            $this->analytics            = isset( $config[ 'analytics' ] ) ? $config[ 'analytics' ] : '';
            $this->analytics_params     = isset( $config[ 'analytics_params' ] ) ? $config[ 'analytics_params' ] : '';
            $this->product_condition    = isset( $config[ 'product_condition' ] ) ? $config[ 'product_condition' ] : '';
            $this->aelia_currency       = isset( $config[ 'aelia_currency' ] ) ? $config[ 'aelia_currency' ] : 'USD';
            $this->feed_country         = isset( $config[ 'feed_country' ] ) ? $config[ 'feed_country' ] : '';
            $this->custom_wrapper       = isset( $config[ 'custom_wrapper' ] ) ? $config[ 'custom_wrapper' ] : '';
            $this->custom_wrapper_el    = isset( $config[ 'custom_wrapper_el' ] ) ? $config[ 'custom_wrapper_el' ] : '';
            $this->custom_items_wrapper = isset( $config[ 'custom_items_wrapper' ] ) ? $config[ 'custom_items_wrapper' ] : '';
	        $this->feed_zip_code        = isset( $config[ 'feed_zip_code' ] ) ? $config[ 'feed_zip_code' ] : '';
	        $this->custom_xml_header    = isset( $config[ 'custom_xml_header' ] ) ? $config[ 'custom_xml_header' ] : '';
	        $this->yandex_company_name  = isset( $config[ 'yandex_company_name' ] ) ? $config[ 'yandex_company_name' ] : '';
	        $this->yandex_old_price     = isset( $config[ 'yandex_old_price' ] ) ? $config[ 'yandex_old_price' ] : '';
            $this->link                 = esc_url( home_url( '/' ) );

            if ( isset( $config[ 'custom_filter_option' ] ) && 'added' === $config[ 'custom_filter_option' ] ) {
                $this->custom_filter_option = true;
            }
            else {
                $this->custom_filter_option = false;
            }

            if( isset( $config[ 'wmc_currency' ] ) ) {
                $this->wmc_currency   = $config[ 'wmc_currency' ];
            }
            elseif( function_exists( 'get_woocommerce_currency' ) ) {
                $this->wmc_currency   = get_woocommerce_currency();
            }
            else {
                $this->wmc_currency       = 'USD';
            }

            $this->prepare_products_args( $config[ 'info' ] );
        }
        else {
            $this->setup_feed_data( $config[ 'info' ] );
            $this->setup_feed_configs( $config[ 'feed_config' ] );
            $this->setup_feed_meta( $config[ 'feed_config' ] );
            $this->setup_feed_filter_rules( $config[ 'feed_config' ] );
            if( 1 === $this->batch ) {
                $this->save_feed_meta( $config[ 'feed_config' ] );
            }
            $this->prepare_products_args( $config[ 'products' ] );
        }

        $this->setup_products();
        $this->merchant    = $config[ 'merchant' ];
        $this->feed_format = $config[ 'feed_format' ];
        /**
         * log for feed
         */
        if ( $this->is_logging_enabled ) {
            $log = wc_get_logger();
            if ( $this->bypass ) {
                if ( $this->batch == 1 ) {
                    $log->info( __( 'Start feed processing job by cron', 'rex-product-feed' ), array( 'source' => 'WPFM', ) );
                    $log->info( 'Feed ID: ' . $config[ 'info' ][ 'post_id' ], array( 'source' => 'WPFM', ) );
                    $log->info( 'Feed Name: ' . $config[ 'info' ][ 'title' ], array( 'source' => 'WPFM', ) );
                    $log->info( 'Merchant Type: ' . $this->merchant, array( 'source' => 'WPFM', ) );
                }
                $log->info( 'Total Batches: ' . $this->batch, array( 'source' => 'WPFM', ) );
                $log->info( 'Current Batch: ' . $this->tbatch, array( 'source' => 'WPFM', ) );
            }
            else {
                if ( $this->batch == 1 ) {
                    $log->info( __( 'Start feed processing job.', 'rex-product-feed' ), array( 'source' => 'WPFM', ) );
                    $log->info( 'Feed ID: ' . $config[ 'info' ][ 'post_id' ], array( 'source' => 'WPFM', ) );
                    $log->info( 'Feed Name: ' . $config[ 'info' ][ 'title' ], array( 'source' => 'WPFM', ) );
                    $log->info( 'Merchant Type: ' . $this->merchant, array( 'source' => 'WPFM', ) );
                }
                $log->info( 'Total Batches: ' . $this->batch, array( 'source' => 'WPFM', ) );
                $log->info( 'Current Batch: ' . $this->tbatch, array( 'source' => 'WPFM', ) );
            }
        }

        if ( $this->tbatch == $this->batch ) {
            $wp_date_format = 'F j, Y';
            $wp_time_format = 'g:i a';
            update_post_meta( $this->id, 'updated', current_time( $wp_date_format . ' ' . $wp_time_format ) );
        }
    }

    /**
     * Prepare the Products Query args for retrieving  products.
     * @param $args
     */
    protected function prepare_products_args( $args )
    {
        $this->product_scope = $args[ 'products_scope' ];
        $post_types          = array( 'product' );

        if ( $this->variations ) {
            $post_types[] = 'product_variation';
        }

        if ( $this->custom_filter_option ) {
            foreach ( $this->feed_filters as $filter ) {

                $if = $filter[ 'if' ];

                if ( $if === 'product_cats' || $if === 'product_tags' ) {
                    unset( $post_types[ 1 ] );
                    $this->custom_filter_var_exclude = true;
                }
            }
        }

        $post_status = array( 'publish' );

        $wpfm_allow_private_products = get_option( 'wpfm_allow_private', 'no' );
        if ( $wpfm_allow_private_products === 'yes' ) {
            $post_status[] = 'private';
        }

        $this->products_args = array(
            'post_type'              => $post_types,
            'fields'                 => 'ids',
            'post_status'            => $post_status,
            'posts_per_page'         => $this->posts_per_page,
            'offset'                 => $this->offset,
            'orderby'                => 'ID',
            'order'                  => 'ASC',
            'post__in'               => array(),
            'post__not_in'           => get_option( 'rex_feed_abandoned_child_list', [] ),
            'update_post_term_cache' => true,
            'update_post_meta_cache' => true,
            'cache_results'          => false,
            'suppress_filters'       => false,
        );

        if ( $args[ 'products_scope' ] === 'product_cat' || $args[ 'products_scope' ] === 'product_tag' ) {
            $terms = $args[ 'products_scope' ] === 'product_tag' ? 'tags' : 'cats';
            $this->products_args[ 'post_type' ] = array( 'product' );

            if ( isset( $args[ $terms ] ) && is_array( $args[ $terms ] ) ) {
                $this->products_args[ 'tax_query' ][] = array(
                    'taxonomy' => $args[ 'products_scope' ],
                    'field'    => 'slug',
                    'terms'    => $args[ $terms ],
                );
                $this->products_args[ 'tax_query' ][ 'relation' ] = 'OR';

                if ( $this->batch == 1 ) {
                    wp_set_object_terms( $this->id, $args[ $terms ], $args[ 'products_scope' ] );
                }
            }
        }

        if ( $args[ 'products_scope' ] === 'product_filter' ) {

            $ids = get_post_meta( $this->id, '_rex_feed_product_filter_ids', true ) ?: get_post_meta( $this->id, 'rex_feed_product_filter_ids', true );

            if ( !$this->product_filter_condition ) {
                $condition     = get_post_meta( $this->id, '_rex_feed_product_condition' ) ?: get_post_meta( $this->id, 'rex_feed_product_condition' );
                $condition_str = implode( '', $condition );

                if ( is_array( $ids ) && !empty( $ids ) ) {
                    if ( $condition_str == 'inc' ) {
                        $this->products_args[ 'post__in' ] =  array_merge( $ids, $this->products_args[ 'post__in' ] );
                    }
                    else {
                        $this->products_args[ 'post__not_in' ] = array_merge( $ids, $this->products_args[ 'post__not_in' ] );
                    }
                }

            }
            else {

                if ( isset( $args[ 'data' ] ) && is_array( $args[ 'data' ] ) && !empty( $args[ 'data' ] ) ) {
                    if ( $this->product_filter_condition == 'inc' ) {

                        $this->products_args[ 'post__in' ] = array_merge( $args[ 'data' ], $this->products_args[ 'post__in' ] );
                    }
                    else {
                        $this->products_args[ 'post__not_in' ] = array_merge( $args[ 'data' ], $this->products_args[ 'post__not_in' ] );
                    }
                }
                else {
                    if ( is_array( $ids ) && !empty( $ids ) ) {
                        if ( $this->product_filter_condition == 'inc' ) {

                            $this->products_args[ 'post__in' ] =  array_merge( $ids, $this->products_args[ 'post__in' ] );
                        }
                        else {
                            $this->products_args[ 'post__not_in' ] = array_merge( $ids, $this->products_args[ 'post__not_in' ] );
                        }
                    }
                }
            }
        }

        if ( $args[ 'products_scope' ] === 'featured' ) {
            $this->products_args[ 'tax_query' ][] = array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'featured',
                'operator' => 'IN',
            );
        }
    }

    /**
     * Setup the Feed Related info
     * @param $info
     */
    protected function setup_feed_data( $info )
    {

        $this->tbatch         = isset( $info[ 'total_batch' ] ) ? (int) $info[ 'total_batch' ] : 1;
        $this->posts_per_page = isset( $info[ 'per_batch' ] ) ? $info[ 'per_batch' ] : 0;
        $this->id             = isset( $info[ 'post_id' ] ) ? $info[ 'post_id' ] : 0;
        $this->title          = isset( $info[ 'title' ] ) && '' !== $info[ 'title' ] ? $info[ 'title' ] : get_bloginfo();
        $this->desc           = isset( $info[ 'desc' ] ) && '' !== $info[ 'desc' ] ? $info[ 'desc' ] : get_bloginfo();
        $this->offset         = isset( $info[ 'offset' ] ) ? $info[ 'offset' ] : -1;
        $this->batch          = isset( $info[ 'batch' ] ) ? (int) $info[ 'batch' ] : 1;
        $this->link           = esc_url( home_url( '/' ) );
    }

    /**
     * Setup the rules
     * @param $info
     */
    protected function setup_feed_configs( $info )
    {
        $feed_rules = array();
        parse_str( $info, $feed_rules );

        $this->product_scope = $feed_rules[ 'rex_feed_products' ];
        if ( array_key_exists( 'rex_feed_analytics_params_options', $feed_rules ) ) {
            $analytics_on    = $feed_rules[ 'rex_feed_analytics_params_options' ];
            $this->analytics = $analytics_on == 'on' ? true : false;
            if ( $analytics_on ) {
                if ( $this->batch == 1 ) {
                    update_post_meta( $this->id, '_rex_feed_analytics_params_options', $analytics_on );
                }
                if ( 'on' === $analytics_on || 'yes' === $analytics_on ) {
                    $this->analytics_params = isset( $feed_rules[ 'rex_feed_analytics_params' ] ) ? $feed_rules[ 'rex_feed_analytics_params' ] : [];
                    if ( $this->batch == 1 ) {
                        update_post_meta( $this->id, '_rex_feed_analytics_params', $this->analytics_params );
                    }
                }
            }
        }


        if ( array_key_exists( 'rex_feed_wcml_currency', $feed_rules ) ) {
            $this->wcml_currency = $feed_rules[ 'rex_feed_wcml_currency' ];
            $this->wcml          = true;
        }

        if ( function_exists( 'icl_object_id' ) ) {
            if ( !class_exists( 'Polylang' ) ) {
                $language = get_post_meta( $this->id, '_rex_feed_wpml_language', true ) ?: get_post_meta( $this->id, 'rex_feed_wpml_language', true );
                if ( $language ) {
                    $this->wpml_language = $language;
                }
                else {
                    $this->wpml_language = ICL_LANGUAGE_CODE;
                }

                if ( $this->batch == 1 ) {
                    update_post_meta( $this->id, '_rex_feed_wpml_language', ICL_LANGUAGE_CODE );
                }
            }
        }
        else {
            $this->wpml_language = false;
        }

        if ( wpfm_is_wpml_active() ) {
            $wcml_currency = isset( $feed_rules[ 'rex_feed_wcml_currency' ] ) ? $feed_rules[ 'rex_feed_wcml_currency' ] : '';
            update_post_meta( $this->id, '_rex_feed_wcml_currency', $wcml_currency );
        }

        $this->feed_config= isset( $feed_rules[ 'fc' ] ) ? $feed_rules[ 'fc' ] : array();

        // save the feed_rules into feed post_meta.
        if ( $this->batch == 1 ) {
            update_post_meta( $this->id, '_rex_feed_feed_config', $this->feed_config);
        }
    }

    /**
     * Setup the rules for filter
     * @param $info
     */
    protected function setup_feed_filter_rules( $info )
    {
        parse_str( $info, $feed_rules_filters );

        if ( $this->custom_filter_option ) {
            $this->feed_filters = isset( $feed_rules_filters[ 'ff' ] ) ? $feed_rules_filters[ 'ff' ] : array();

            // save the feed_rules_filter into feed post_meta.
            if ( $this->batch == 1 && !empty( $this->feed_filters ) ) {
                reset( $this->feed_filters );
                $key = key( $this->feed_filters );
                unset( $this->feed_filters[ $key ] );
                update_post_meta( $this->id, '_rex_feed_feed_config_filter', $this->feed_filters );
            }
        }

        $this->feed_rules = isset( $feed_rules_filters[ 'fr' ] ) ? $feed_rules_filters[ 'fr' ] : array();

        if ( $this->batch == 1 ) {
            if( !empty( $this->feed_rules ) ) {
                reset( $this->feed_rules );
                $key = key( $this->feed_rules );
                unset( $this->feed_rules[ $key ] );
                update_post_meta( $this->id, '_rex_feed_feed_config_rules', $this->feed_rules );
            }
            else {
                delete_post_meta( $this->id, '_rex_feed_feed_config_rules' );
            }
        }
    }

    /**
     * Setup the feed meta values
     *
     * @param $config
     */
    protected function setup_feed_meta( $config )
    {
        $feed_configs = array();
        parse_str( $config, $feed_configs );

        $include_variable_product   = isset( $feed_configs[ 'rex_feed_variable_product' ] ) ? esc_attr( $feed_configs[ 'rex_feed_variable_product' ] ) : '';
        $include_variations         = isset( $feed_configs[ 'rex_feed_variations' ] ) ? esc_attr( $feed_configs[ 'rex_feed_variations' ] ) : '';
        $include_parent             = isset( $feed_configs[ 'rex_feed_parent_product' ] ) ? esc_attr( $feed_configs[ 'rex_feed_parent_product' ] ) : '';
        $include_variations_name    = isset( $feed_configs[ 'rex_feed_variation_product_name' ] ) ? esc_attr( $feed_configs[ 'rex_feed_variation_product_name' ] ) : '';
        $exclude_hidden_products    = isset( $feed_configs[ 'rex_feed_hidden_products' ] ) ? esc_attr( $feed_configs[ 'rex_feed_hidden_products' ] ) : '';
        $rex_feed_skip_product      = isset( $feed_configs[ 'rex_feed_skip_product' ] ) ? esc_attr( $feed_configs[ 'rex_feed_skip_product' ] ) : '';
        $rex_feed_skip_row          = isset( $feed_configs[ 'rex_feed_skip_row' ] ) ? esc_attr( $feed_configs[ 'rex_feed_skip_row' ] ) : '';
        $include_out_of_stock       = isset( $feed_configs[ 'rex_feed_include_out_of_stock' ] ) ? esc_attr( $feed_configs[ 'rex_feed_include_out_of_stock' ] ) : '';
        $include_zero_priced        = isset( $feed_configs[ 'rex_feed_include_zero_price_products' ] ) ? esc_attr( $feed_configs[ 'rex_feed_include_zero_price_products' ] ) : '';
        $this->feed_separator       = isset( $feed_configs[ 'rex_feed_separator' ] ) ? esc_attr( $feed_configs[ 'rex_feed_separator' ] ) : '';
        $this->aelia_currency       = isset( $feed_configs[ 'rex_feed_aelia_currency' ] ) ? esc_attr( $feed_configs[ 'rex_feed_aelia_currency' ] ) : 'USD';
        $custom_filter_option       = isset( $feed_configs[ 'rex_feed_custom_filter_option_btn' ] ) ? esc_attr( $feed_configs[ 'rex_feed_custom_filter_option_btn' ] ) : 'removed';
        $this->feed_country         = isset( $feed_configs[ 'rex_feed_feed_country' ] ) ? esc_attr( $feed_configs[ 'rex_feed_feed_country' ] ) : '';
        $this->custom_wrapper       = isset( $feed_configs[ 'rex_feed_custom_wrapper' ] ) ? esc_attr( $feed_configs[ 'rex_feed_custom_wrapper' ] ) : '';
        $this->custom_wrapper_el    = isset( $feed_configs[ 'rex_feed_custom_wrapper_el' ] ) ? esc_attr( $feed_configs[ 'rex_feed_custom_wrapper_el' ] ) : '';
        $this->custom_items_wrapper = isset( $feed_configs[ 'rex_feed_custom_items_wrapper' ] ) ? esc_attr( $feed_configs[ 'rex_feed_custom_items_wrapper' ] ) : '';
        $this->feed_zip_code        = isset( $feed_configs[ 'rex_feed_zip_codes' ] ) ? esc_attr( $feed_configs[ 'rex_feed_zip_codes' ] ) : '';
        $this->custom_xml_header    = isset( $feed_configs[ 'rex_feed_custom_xml_header' ] ) ? esc_attr( $feed_configs[ 'rex_feed_custom_xml_header' ] ) : '';
        $this->yandex_company_name  = isset( $feed_configs[ 'rex_feed_yandex_company_name' ] ) ? esc_attr( $feed_configs[ 'rex_feed_yandex_company_name' ] ) : '';
        $this->yandex_old_price     = isset( $feed_configs[ 'rex_feed_yandex_old_price' ] ) ? esc_attr( $feed_configs[ 'rex_feed_yandex_old_price' ] ) : '';
        $this->yandex_old_price     = 'include' === $this->yandex_old_price;

        if( isset( $feed_configs[ 'rex_feed_wmc_currency' ] ) ) {
            $this->wmc_currency   = $feed_configs[ 'rex_feed_wmc_currency' ];
        }
        elseif( function_exists( 'get_woocommerce_currency' ) ) {
            $this->wmc_currency   = get_woocommerce_currency();
        }
        else {
            $this->wmc_currency       = 'USD';
        }

        $this->wcml_currency      = isset( $feed_configs[ 'rex_feed_wcml_currency' ] ) ? $feed_configs[ 'rex_feed_wcml_currency' ] : 'USD';

        if ( isset( $feed_configs[ 'product_filter_condition' ] ) ) {
            $this->product_filter_condition = $feed_configs[ 'product_filter_condition' ];
        }

        if ( $include_variable_product == 'yes' ) {
            $this->variable_product = true;
        }
        else {
            $this->variable_product = false;
        }

        if ( $include_out_of_stock == 'yes' ) {
            $this->include_out_of_stock = true;
        }
        else {
            $this->include_out_of_stock = false;
        }

        if ( $include_variations == 'yes' ) {
            $this->variations = true;
        }
        else {
            $this->variations = false;
        }

        if ( $include_parent == 'yes' ) {
            $this->parent_product = true;
        }
        else {
            $this->parent_product = false;
        }

        if ( $include_variations_name == 'yes' ) {
            $this->append_variation = true;
        }
        else {
            $this->append_variation = false;
        }

        if ( $exclude_hidden_products == 'yes' ) {
            $this->exclude_hidden_products = true;
        }
        else {
            $this->exclude_hidden_products = false;
        }

        if ( $rex_feed_skip_product == 'yes' ) {
            $this->rex_feed_skip_product = true;
        }
        else {
            $this->rex_feed_skip_product = false;
        }

        if ( $rex_feed_skip_row == 'yes' ) {
            $this->rex_feed_skip_row = true;
        }
        else {
            $this->rex_feed_skip_row = false;
        }

        if ( $include_zero_priced == 'yes' ) {
            $this->include_zero_priced = true;
        }
        else {
            $this->include_zero_priced = false;
        }

        if ( 'added' === $custom_filter_option ) {
            $this->custom_filter_option = true;
        }
        else {
            $this->custom_filter_option = false;
        }
    }

    /**
     * Saving feed meta into database
     * @param $config
     */
    protected function save_feed_meta( $config )
    {
        $feed_configs = array();
        parse_str( $config, $feed_configs );

        if ( isset( $feed_configs[ 'rex_feed_schedule' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_schedule', $feed_configs[ 'rex_feed_schedule' ] );
            delete_post_meta( $this->id, 'rex_feed_schedule' );

            if ( isset( $feed_configs[ 'rex_feed_custom_time' ] ) && $feed_configs[ 'rex_feed_schedule' ] === 'custom' ) {
                update_post_meta( $this->id, '_rex_feed_custom_time', $feed_configs[ 'rex_feed_custom_time' ] );
                delete_post_meta( $this->id, 'rex_feed_custom_time' );
            }
            else {
                delete_post_meta( $this->id, '_rex_feed_custom_time' );
                delete_post_meta( $this->id, 'rex_feed_custom_time' );
            }
        }
        if ( isset( $feed_configs[ 'rex_feed_merchant' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_merchant', $feed_configs[ 'rex_feed_merchant' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_include_out_of_stock' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_include_out_of_stock', $feed_configs[ 'rex_feed_include_out_of_stock' ] );
        }
        else {
            update_post_meta( $this->id, '_rex_feed_include_out_of_stock', 'no' );
        }
        if ( isset( $feed_configs[ 'rex_feed_products' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_products', $feed_configs[ 'rex_feed_products' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_variable_product' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_variable_product', $feed_configs[ 'rex_feed_variable_product' ] );
        }
        else {
            update_post_meta( $this->id, '_rex_feed_variable_product', 'no' );
        }
        if ( isset( $feed_configs[ 'rex_feed_variations' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_variations', $feed_configs[ 'rex_feed_variations' ] );
        }
        else {
            update_post_meta( $this->id, '_rex_feed_variations', 'no' );
        }
        if ( isset( $feed_configs[ 'rex_feed_parent_product' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_parent_product', $feed_configs[ 'rex_feed_parent_product' ] );
        }
        else {
            update_post_meta( $this->id, '_rex_feed_parent_product', 'no' );
        }
        if ( isset( $feed_configs[ 'rex_feed_variation_product_name' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_variation_product_name', $feed_configs[ 'rex_feed_variation_product_name' ] );
        }
        else {
            update_post_meta( $this->id, '_rex_feed_variation_product_name', 'no' );
        }
        if ( isset( $feed_configs[ 'rex_feed_hidden_products' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_hidden_products', $feed_configs[ 'rex_feed_hidden_products' ] );
        }
        else {
            update_post_meta( $this->id, '_rex_feed_hidden_products', 'no' );
        }
        if ( isset( $feed_configs[ 'rex_feed_skip_product' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_skip_product', $feed_configs[ 'rex_feed_skip_product' ] );
        }
        else {
            update_post_meta( $this->id, '_rex_feed_skip_product', 'no' );
        }
        if ( isset( $feed_configs[ 'rex_feed_skip_row' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_skip_row', $feed_configs[ 'rex_feed_skip_row' ] );
        }
        else {
            update_post_meta( $this->id, '_rex_feed_skip_row', 'no' );
        }
        if ( isset( $feed_configs[ 'rex_feed_include_zero_price_products' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_include_zero_price_products', $feed_configs[ 'rex_feed_include_zero_price_products' ] );
        }
        else {
            update_post_meta( $this->id, '_rex_feed_include_zero_price_products', 'no' );
        }
        if ( isset( $feed_configs[ 'rex_feed_analytics_params_options' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_analytics_params_options', $feed_configs[ 'rex_feed_analytics_params_options' ] );
        }
        else {
            update_post_meta( $this->id, '_rex_feed_analytics_params_options', 'no' );
        }

        if ( isset( $feed_configs[ 'rex_feed_cats' ] ) ) {
            $cats = array();
            foreach ( $feed_configs[ 'rex_feed_cats' ] as $cat ) {
                $cats[] = get_term_by('slug', $cat, 'product_cat' )->term_id;
            }
            wp_set_object_terms( $this->id, $cats, 'product_cat' );
        }
        else {
            wp_set_object_terms( $this->id, array(), 'product_cat' );
        }
        if ( isset( $feed_configs[ 'rex_feed_tags' ] ) ) {
            $tags = array();
            foreach ( $feed_configs[ 'rex_feed_tags' ] as $tag ) {
                $tags[] = get_term_by('slug', $tag, 'product_tag' )->term_id;
            }
            wp_set_object_terms( $this->id, $tags, 'product_tag' );
        }
        else {
            wp_set_object_terms( $this->id, array(), 'product_tag' );
        }

        if ( isset( $feed_configs[ 'rex_feed_aelia_currency' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_aelia_currency', $feed_configs[ 'rex_feed_aelia_currency' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_wmc_currency' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_wmc_currency', $feed_configs[ 'rex_feed_wmc_currency' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_wcml_currency' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_wcml_currency', $feed_configs[ 'rex_feed_wcml_currency' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_separator' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_separator', $feed_configs[ 'rex_feed_separator' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_google_destination' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_google_destination', $feed_configs[ 'rex_feed_google_destination' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_google_target_country' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_google_target_country', $feed_configs[ 'rex_feed_google_target_country' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_google_target_language' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_google_target_language', $feed_configs[ 'rex_feed_google_target_language' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_google_schedule' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_google_schedule', $feed_configs[ 'rex_feed_google_schedule' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_google_schedule_month' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_google_schedule_month', $feed_configs[ 'rex_feed_google_schedule_month' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_google_schedule_week_day' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_google_schedule_week_day', $feed_configs[ 'rex_feed_google_schedule_week_day' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_google_schedule_time' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_google_schedule_time', $feed_configs[ 'rex_feed_google_schedule_time' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_ebay_seller_site_id' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_ebay_seller_site_id', $feed_configs[ 'rex_feed_ebay_seller_site_id' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_ebay_seller_country' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_ebay_seller_country', $feed_configs[ 'rex_feed_ebay_seller_country' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_ebay_seller_currency' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_ebay_seller_currency', $feed_configs[ 'rex_feed_ebay_seller_currency' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_analytics_params' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_analytics_params', $feed_configs[ 'rex_feed_analytics_params' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_product_filter_ids' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_product_filter_ids', $feed_configs[ 'rex_feed_product_filter_ids' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_custom_filter_option_btn' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_custom_filter_option', $feed_configs[ 'rex_feed_custom_filter_option_btn' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_feed_country' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_feed_country', $feed_configs[ 'rex_feed_feed_country' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_custom_wrapper' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_custom_wrapper', $feed_configs[ 'rex_feed_custom_wrapper' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_custom_items_wrapper' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_custom_items_wrapper', $feed_configs[ 'rex_feed_custom_items_wrapper' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_custom_wrapper_el' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_custom_wrapper_el', $feed_configs[ 'rex_feed_custom_wrapper_el' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_custom_xml_header' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_custom_xml_header', $feed_configs[ 'rex_feed_custom_xml_header' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_cats_check_all_btn' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_cats_check_all_btn', $feed_configs[ 'rex_feed_cats_check_all_btn' ] );
        }
        else {
            delete_post_meta( $this->id, '_rex_feed_cats_check_all_btn' );
        }
        if ( isset( $feed_configs[ 'rex_feed_tags_check_all_btn' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_tags_check_all_btn', $feed_configs[ 'rex_feed_tags_check_all_btn' ] );
        }
        else {
            delete_post_meta( $this->id, '_rex_feed_tags_check_all_btn' );
        }
        if ( isset( $feed_configs[ 'rex_feed_zip_codes' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_zip_codes', $feed_configs[ 'rex_feed_zip_codes' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_yandex_company_name' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_yandex_company_name', $feed_configs[ 'rex_feed_yandex_company_name' ] );
        }
        if ( isset( $feed_configs[ 'rex_feed_yandex_old_price' ] ) ) {
            update_post_meta( $this->id, '_rex_feed_yandex_old_price', $feed_configs[ 'rex_feed_yandex_old_price' ] );
        }

        do_action( 'rex_feed_after_feed_config_saved', $this->id, $feed_configs );
    }

    /**
     * Get the products to generate feed
     */
    protected function setup_products()
    {
        wpfm_switch_site_lang( $this->wpml_language );

        if ( $this->custom_filter_option ) {
            $filter_args = Rex_Product_Filter::createFilterQueryParams( $this->feed_filters );
            add_filter( 'posts_where', array( $this, 'wpfm_post_title_filter' ), 10, 2 );

            foreach ( $filter_args[ 'args' ] as $key => $value ) {
                if ( isset( $this->products_args[ $key ] ) ) {
                    $value = array_merge( $this->products_args[ $key ], $value );
                }
                $this->products_args[ $key ] = $value;
            }

            if ( array_key_exists( 'meta_query', $this->products_args ) ) {
                $this->products_args[ 'meta_query' ][ 'relation' ] = 'AND';
            }
            if ( array_key_exists( 'tax_query', $this->products_args ) ) {
                $this->products_args[ 'tax_query' ][ 'relation' ] = 'AND';
            }
        }

//        add_filter( 'posts_distinct_request', array( $this, 'wpfm_set_distinct' ) );
        add_filter( 'posts_where', array( $this, 'wpfm_custom_language_where_queries' ), 10, 2 );
        add_filter( 'posts_join', array( $this, 'wpfm_get_custom_join_query' ) );
        /*if ( $this->variations ) {
            add_filter( 'posts_join', array( $this, 'wpfm_get_custom_join_query' ) );
            add_filter( 'posts_request', array( $this, 'wpfm_get_custom_requests' ) );
        }*/

        $result         = new WP_Query( $this->products_args );
        $this->products = $result->posts;
        $condition      = $this->product_filter_condition;

        if ( isset( $this->products_args[ 'post__in' ] ) && $this->products_args[ 'post__in' ] ) {
            if ( $condition ) {
                update_post_meta( $this->id, '_rex_feed_product_condition', $condition );
            }
            $result         = new WP_Query( $this->products_args );
            $this->products = $result->posts;
        }
        else {
            if ( $condition ) {
                update_post_meta( $this->id, '_rex_feed_product_condition', $condition );
            }
            $result         = new WP_Query( $this->products_args );
            $this->products = $result->posts;
        }

//        remove_filter( 'posts_distinct_request', array( $this, 'wpfm_set_distinct' ) );
        remove_filter( 'posts_where', array( $this, 'wpfm_custom_language_where_queries' ) );
        remove_filter( 'posts_join', array( $this, 'wpfm_get_custom_join_query' ) );
        /*if( $this->variations ) {
            remove_filter( 'posts_join', array( $this, 'wpfm_get_custom_join_query' ) );
            remove_filter( 'posts_request', array( $this, 'wpfm_get_custom_requests' ) );
        }*/

        if ( is_array( $this->products ) ) {
            $this->products = array_unique( $this->products );

            if ( $this->batch == 1 ) {
                update_post_meta( $this->id, '_rex_feed_product_ids', $this->products );
            }
            else {
                $product_ids = get_post_meta( $this->id, '_rex_feed_product_ids', true ) ?: get_post_meta( $this->id, 'rex_feed_product_ids', true );
                if ( $product_ids ) {
                    $prev_product_ids = $product_ids;
                    $product_ids      = array_merge( $prev_product_ids, $this->products );
                    update_post_meta( $this->id, '_rex_feed_product_ids', $product_ids );
                }
                else {
                    update_post_meta( $this->id, '_rex_feed_product_ids', $this->products );
                }
            }
            remove_filter( 'posts_where', array( $this, 'wpfm_post_title_filter' ), 10, 2 );
        }
    }


    /**
     * Modifies wordpress core query requests to DISTINCT results
     *
     * @param $join
     * @return string
     */
    public function wpfm_set_distinct()
    {
        return 'DISTINCT';
    }


    /**
     * Customize WPML where clause
     *
     * @param $where
     * @param $query
     * @return array|mixed|string|string[]
     */
    public function wpfm_custom_language_where_queries( $where, $query ) {
        if ( wpfm_is_wpml_active() ) {
            global $sitepress;
            $search = "language_code = '".$sitepress->get_default_language()."'";
            $replace = "language_code = '".$this->wpml_language."'";
            return str_replace( $search, $replace, $where );
        }
        if ( wpfm_is_polylang_active() && $this->bypass ) {
            global $wpdb;
            $polylang = get_the_terms( $this->id, 'language' );
            $polylang = array_column($polylang, 'term_id');
            $polylang = implode( ', ', $polylang );
            $where .= " AND {$wpdb->prefix}term_relationships.term_taxonomy_id IN({$polylang}) ";
        }
        if( $this->custom_filter_option ) {
            $search  = ') AND ( 
  ( wp_postmeta.meta_key =';
            $replace = ') OR ( ( wp_postmeta.meta_key =';
            $where = str_replace( $search, $replace, $where );
        }

        return $where;
    }

    /**
     * Modifies wordpress core query requests
     *
     * @param $join
     * @return string
     */
    public function wpfm_get_custom_requests( $request)
    {
        $search  = "WHERE 1=1  AND wp_posts.post_type = 'product' AND ((wp_posts.post_status = 'publish'))";
        $request = str_replace( $search, 'WHERE 1=1', $request);
        $search  = "AND wp_posts.post_type = 'product' AND ((wp_posts.post_status = 'publish'))";
        $request = str_replace( $search, '', $request);
        $search  = "AND wp_posts.post_type = 'product'";
        $request = str_replace( $search, '', $request);
        return $request;
    }

    /**
     * Modifies wordpress core join statements
     * in order to exclude variations with drafted/deleted parent
     *
     * @param $join
     * @return string
     */
    public function wpfm_get_custom_join_query( $join )
    {
        if ( wpfm_is_polylang_active() && $this->bypass ) {
            global $wpdb;
            $join .= "LEFT JOIN wp_term_relationships ";
            $join .= "ON ({$wpdb->prefix}posts.ID = {$wpdb->prefix}term_relationships.object_id)";
        }
        return $join;
    }


    /**
     * product serach by title
     * @param $where
     * @param $wp_query
     * @return string
     */
    public function wpfm_post_title_filter( $where, $wp_query )
    {
        global $wpdb;

        if ( $wp_query->get( 'title_contain' ) ) {
            $title_contain = $wp_query->get( 'title_contain' );
            $i             = 0;
            $where         .= ' AND (';
            foreach ( $title_contain as $title ) {
                $i     = $i + 1;
                $op    = ( $i > 1 ) ? 'OR' : '';
                $where .= ' ' . $op . ' ' . $wpdb->posts . '.post_title LIKE \'%' . $wpdb->esc_like( $title ) . '%\'';
            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'title_dn_contain' ) ) {
            $title_dn_contain = $wp_query->get( 'title_dn_contain' );
            $i                = 0;
            $where            .= ' AND (';
            foreach ( $title_dn_contain as $title ) {
                $i     = $i + 1;
                $op    = ( $i > 1 ) ? 'AND' : '';
                $where .= ' ' . $op . ' ' . $wpdb->posts . '.post_title NOT LIKE \'%' . $wpdb->esc_like( $title ) . '%\'';
            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'title_equal_to' ) ) {
            $title_dn_contain = $wp_query->get( 'title_equal_to' );
            $i                = 0;
            $where            .= ' AND (';
            foreach ( $title_dn_contain as $title ) {
                $i     = $i + 1;
                $op    = ( $i > 1 ) ? 'OR' : '';
                $where .= ' ' . $op . ' ' . $wpdb->posts . '.post_title = \'' . $wpdb->esc_like( $title ) . '\'';
            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'title_nequal_to' ) ) {
            $title_dn_contain = $wp_query->get( 'title_nequal_to' );
            $i                = 0;
            $where            .= ' AND (';
            foreach ( $title_dn_contain as $title ) {
                $i     = $i + 1;
                $op    = ( $i > 1 ) ? 'AND' : '';
                $where .= ' ' . $op . ' ' . $wpdb->posts . '.post_title <> \'' . $wpdb->esc_like( $title ) . '\'';
            }
            $where .= ' )';

        }

        if ( $wp_query->get( 'description_contain' ) ) {
            $title_contain = $wp_query->get( 'title_contain' );
            $i             = 0;
            $where         .= ' AND (';
            foreach ( $title_contain as $title ) {
                $i  = $i + 1;
                $op = ( $i > 1 ) ? 'OR' : '';

                $where .= ' ' . $op . ' ' . $wpdb->posts . '.post_content LIKE \'%' . $wpdb->esc_like( $title ) . '%\'';
            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'brand_contain' ) ) {
            $title_contain = $wp_query->get( 'brand_contain' );
            $i             = 0;
            $where         .= ' AND (';
            foreach ( $title_contain as $title ) {
                $query = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wpfm_product_brand' AND meta_value like '%'%s'%'", $wpdb->esc_like( $title ) );
                $post_id = $wpdb->get_results( $query );
                foreach ( $post_id as $pi ) {
                    $i     = $i + 1;
                    $op    = ( $i > 1 ) ? 'OR' : '';
                    $where .= ' ' . $op . ' ' . $wpdb->posts . '.ID = \'' . $pi->post_id . '\'';
                }
            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'brand_equal_to' ) ) {
            $title_contain = $wp_query->get( 'brand_equal_to' );
            $i             = 0;
            $where         .= ' AND (';
            foreach ( $title_contain as $title ) {
                $query = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wpfm_product_brand' AND meta_value like '%'%s'%'", $wpdb->esc_like( $title ) ) ;
                $post_id = $wpdb->get_results( $query );
                if ( !empty( $post_id ) ) {
                    foreach ( $post_id as $pi ) {
                        $i     = $i + 1;
                        $op    = ( $i > 1 ) ? 'OR' : '';
                        $where .= ' ' . $op . ' ' . $wpdb->posts . '.ID = \'' . $pi->post_id . '\'';
                    }
                }
                else {
                    $post_id = $this->get_post_id_by_term( $title );
                    if ( !empty( $post_id ) ) {
                        foreach ( $post_id as $pi ) {
                            $i     = $i + 1;
                            $op    = ( $i > 1 ) ? 'OR' : '';
                            $where .= ' ' . $op . ' ' . $wpdb->posts . '.ID = \'' . $pi . '\'';
                        }
                    }
                    else {
                        $post_id = $this->get_post_by_attribute_name( $title );
                        foreach ( $post_id as $pi ) {
                            $i     = $i + 1;
                            $op    = ( $i > 1 ) ? 'OR' : '';
                            $where .= ' ' . $op . ' ' . $wpdb->posts . '.ID = \'' . $pi . '\'';
                        }
                    }
                }
            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'brand_dn_contain' ) ) {
            $title_contain = $wp_query->get( 'brand_dn_contain' );
            $i             = 0;
            $where         .= ' AND (';
            foreach ( $title_contain as $title ) {
                $query = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wpfm_product_brand' AND meta_value NOT like '%'%s'%'", $wpdb->esc_like( $title ) );
                $post_id = $wpdb->get_results( $query );
                foreach ( $post_id as $pi ) {
                    $i     = $i + 1;
                    $op    = ( $i > 1 ) ? 'AND' : '';
                    $where .= ' ' . $op . ' ' . $wpdb->posts . '.ID = \'' . $pi->post_id . '\'';
                }

            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'brand_nequal_to' ) ) {
            $title_contain = $wp_query->get( 'brand_nequal_to' );
            $i             = 0;
            $where         .= ' AND (';
            foreach ( $title_contain as $title ) {
                $query = $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wpfm_product_brand' AND meta_value like '%'%s'%'", $wpdb->esc_like( $title ) );
                $post_id = $wpdb->get_results(  );
                foreach ( $post_id as $pi ) {
                    $i     = $i + 1;
                    $op    = ( $i > 1 ) ? 'AND' : '';
                    $where .= ' ' . $op . ' ' . $wpdb->posts . '.ID != \'' . $pi->post_id . '\'';
                }

            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'description_dn_contain' ) ) {
            $title_dn_contain = $wp_query->get( 'title_dn_contain' );
            $i                = 0;
            $where            .= ' AND (';
            foreach ( $title_dn_contain as $title ) {
                $i     = $i + 1;
                $op    = ( $i > 1 ) ? 'AND' : '';
                $where .= ' ' . $op . ' ' . $wpdb->posts . '.post_content NOT LIKE \'%' . $wpdb->esc_like( $title ) . '%\'';
            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'description_equal_to' ) ) {
            $title_dn_contain = $wp_query->get( 'title_equal_to' );
            $i                = 0;
            $where            .= ' AND (';
            foreach ( $title_dn_contain as $title ) {
                $i     = $i + 1;
                $op    = ( $i > 1 ) ? 'OR' : '';
                $where .= ' ' . $op . ' ' . $wpdb->posts . '.post_content = \'' . $wpdb->esc_like( $title ) . '\'';
            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'description_nequal_to' ) ) {
            $title_dn_contain = $wp_query->get( 'title_nequal_to' );
            $i                = 0;
            $where            .= ' AND (';
            foreach ( $title_dn_contain as $title ) {
                $i     = $i + 1;
                $op    = ( $i > 1 ) ? 'AND' : '';
                $where .= ' ' . $op . ' ' . $wpdb->posts . '.post_content <> \'' . $wpdb->esc_like( $title ) . '\'';
            }
            $where .= ' )';

        }

        if ( $wp_query->get( 'sdescription_contain' ) ) {
            $title_contain = $wp_query->get( 'title_contain' );
            $i             = 0;
            $where         .= ' AND (';
            foreach ( $title_contain as $title ) {
                $i     = $i + 1;
                $op    = ( $i > 1 ) ? 'OR' : '';
                $where .= ' ' . $op . ' ' . $wpdb->posts . '.post_excerpt LIKE \'%' . $wpdb->esc_like( $title ) . '%\'';
            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'sdescription_dn_contain' ) ) {
            $title_dn_contain = $wp_query->get( 'title_dn_contain' );
            $i                = 0;
            $where            .= ' AND (';
            foreach ( $title_dn_contain as $title ) {
                $i     = $i + 1;
                $op    = ( $i > 1 ) ? 'AND' : '';
                $where .= ' ' . $op . ' ' . $wpdb->posts . '.post_excerpt NOT LIKE \'%' . $wpdb->esc_like( $title ) . '%\'';
            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'sdescription_equal_to' ) ) {
            $title_dn_contain = $wp_query->get( 'title_equal_to' );
            $i                = 0;
            $where            .= ' AND (';
            foreach ( $title_dn_contain as $title ) {
                $i     = $i + 1;
                $op    = ( $i > 1 ) ? 'OR' : '';
                $where .= ' ' . $op . ' ' . $wpdb->posts . '.post_excerpt = \'' . $wpdb->esc_like( $title ) . '\'';
            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'sdescription_nequal_to' ) ) {
            $title_dn_contain = $wp_query->get( 'title_nequal_to' );
            $i                = 0;
            $where            .= ' AND (';
            foreach ( $title_dn_contain as $title ) {
                $i     = $i + 1;
                $op    = ( $i > 1 ) ? 'AND' : '';
                $where .= ' ' . $op . ' ' . $wpdb->posts . '.post_excerpt <> \'' . $wpdb->esc_like( $title ) . '\'';
            }
            $where .= ' )';
        }

        if ( $wp_query->get( 'post__greater_than' ) ) {
            $post_greater_than_id = $wp_query->get( 'post__greater_than' );
            $where                .= ' AND (ID > ' . $post_greater_than_id . ')';
        }

        if ( $wp_query->get( 'post__greater_than_equal' ) ) {
            $post_greater_than_equal_id = $wp_query->get( 'post__greater_than_equal' );
            $where                      .= ' AND (ID >= ' . $post_greater_than_equal_id . ')';
        }

        if ( $wp_query->get( 'post__less_than' ) ) {
            $post_less_than_id = $wp_query->get( 'post__less_than' );
            $where             .= ' AND (ID < ' . $post_less_than_id . ')';
        }

        if ( $wp_query->get( 'post__less_than_equal' ) ) {
            $post_less_than_equal_id = $wp_query->get( 'post__less_than_equal' );
            $where                   .= ' AND (ID <= ' . $post_less_than_equal_id . ')';
        }

        return $where;
    }

    /**
     * Get post_id by taxonomy.
     * @return array
     * params $title
     **/

    public function get_post_id_by_term( $title )
    {
        $term = get_term_by( 'name', $title, 'pwb-brand' );
        if ( !empty( $term ) ) {
            $args    = array(
                'post_type' => array( 'product', 'product_variation' ),
                'fields'    => 'ids',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'pwb-brand',
                        'field'    => 'term_id',
                        'terms'    => $term->term_id
                    )
                )
            );
            $query   = new WP_Query( $args );
            $post_id = $query->get_posts();
            return $post_id;
        }
    }

    /**
     * Get post_id by attribute name.
     * @param $title
     * @return array post_id
     */

    public function get_post_by_attribute_name( $title )
    {
        global $wpdb;
        $query    = $wpdb->prepare( 'SELECT term_id FROM ' . $wpdb->prefix . 'terms WHERE name =  %s', $title );
        $term     = $wpdb->get_results( $query );
        $term_tax = get_term( $term[ 0 ]->term_id );
        $args     = array(
            'post_type' => array( 'product', 'product_variation' ),
            'fields'    => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => $term_tax->taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term[ 0 ]->term_id
                )
            )
        );
        $query    = new WP_Query( $args );
        $post_id  = $query->get_posts();
        return $post_id;
    }

    /**
     * @desc Gets feed id.
     *
     * @return mixed
     */
    public function get_feed_id()
    {
        return $this->config[ 'info' ][ 'post_id' ];
    }

    public function cleanString( $string )
    {
        // allow only letters
        $res = preg_replace( "/[^a-zA-Z]/", "", $string );

        // trim what's left to 8 chars
        $res = substr( $res, 0, 8 );

        // make lowercase
        $res = strtolower( $res );

        // return
        return $res;
    }

    /**
     * Responsible for creating the feed.
     * @return string
     **/
    abstract public function make_feed();

    /**
     * Include Product Variations
     * @param $info
     * @return bool
     */
    protected function include_product_variations( $info )
    {
        $feed_rules = array();
        parse_str( $info, $feed_rules );
        $include_variations = $feed_rules[ 'rex_feed_variations' ];
        if ( $include_variations == 'yes' ) {
            return true;
        }
        return false;
    }

    /**
     * Append product variation
     * name
     * @param $info
     * @return bool
     */
    protected function append_variation_product_name( $info )
    {
        $feed_rules = array();
        parse_str( $info, $feed_rules );
        $include_variations = $feed_rules[ 'rex_feed_variation_product_name' ];
        if ( $include_variations === 'yes' ) {
            return true;
        }
        return false;
    }

    /**
     * Include Product Variations
     * @param $info
     * @return bool
     */
    protected function include_parent_product( $info )
    {
        $feed_rules = array();
        parse_str( $info, $feed_rules );
        $include_parent = $feed_rules[ 'rex_feed_parent_product' ];
        if ( $include_parent === 'yes' ) {
            return true;
        }
        return false;
    }

    /**
     * Setup the variable products from products array.
     */
    protected function setup_group_products()
    {

        $this->grouped_products = array();

        // Loop through all products and separate the variable products.
        foreach ( $this->products as $product_id ) {
            if ( $this->is_grouped_product( $product_id ) ) {
                $this->grouped_products[] = $product_id;
            }
        }

        // remove variable products from products array
        if ( !empty( $this->grouped_products ) ) {
            $this->products = array_diff( $this->products, $this->grouped_products );
        }

        // remove all variable product if product variations is exclude
        if ( !$this->parent_product ) {
            $this->grouped_products = array();
        }
    }

    /**
     * Setup the variable products from products array.
     */
    protected function is_grouped_product( $product_id = false )
    {

        if ( false === $product_id ) {
            return false;
        }

        $product = wc_get_product( $product_id );

        if ( $product->is_type( 'grouped' ) && $this->parent_product ) {
            return true;
        }

        return false;
    }

    /**
     * Setup the variable products from products array.
     */
    protected function is_variable_product( $product_id = false )
    {

        if ( false === $product_id ) {
            return false;
        }

        $product = wc_get_product( $product_id );

        if ( $product->is_type( 'variable' ) ) {
            return true;
        }

        return false;
    }

    /**
     * Check if simple product
     * or not
     * @param bool $product_id
     * @return bool
     */
    protected function is_simple_product( $product_id = false )
    {

        if ( false === $product_id ) {
            return false;
        }
        $product = wc_get_product( $product_id );
        if ( $product->is_type( 'simple' ) ) {
            return true;
        }
        return false;
    }

    /**
     * Check if this is child product
     * @param bool $product_id
     * @return bool
     */
    protected function is_variation_product( $product_id = false )
    {

        if ( false === $product_id ) {
            return false;
        }

        $product = wc_get_product( $product_id );
        $type    = get_post_type( $product_id );
        if ( $type ) {
            if ( $type === 'product_variation' ) {
                $parent_post_status = get_post_status( $product->get_parent_id() );
                if ( $parent_post_status === 'publish' ) {
                    return true;
                }
                return false;
            }
            return false;
        }
        return false;
    }

    /**
     * Get product data
     * @param WC_Product $product
     * @return string
     */
    protected function get_product_data( WC_Product $product, $product_meta_keys )
    {
        $retriever_class = 'Rex_Product_Data_Retriever';
        if ( class_exists( 'Rex_Product_Data_Retriever_Pro' ) ) {
            $retriever_class = 'Rex_Product_Data_Retriever_Pro';
        }
        if ( 'etsy' === $this->merchant ) {
            $retriever_class = 'Etsy_Data_Retriever';
        }

        $data     = new $retriever_class( $product, $this, $product_meta_keys );
        $all_data = $data->get_all_data();

        if ( $this->merchant === 'pinterest' && ( $this->feed_format === 'csv' ) ) {
            return $this->additional_img_link_pinterest( $all_data );
        }

        return $all_data;

        /*if ( class_exists( 'SitePress' ) ) {
            global $sitepress;
            $wpml = get_post_meta( $this->id, 'rex_feed_wpml_language', true ) ? get_post_meta( $this->id, 'rex_feed_wpml_language', true ) : $sitepress->get_default_language();
            if ( $wpml ) {
                $sitepress->switch_lang( $wpml );
                $data = new Rex_Product_Data_Retriever( $product, $this->feed_config, null, $this->append_variation, $product_meta_keys, $analytics_params );
            }
        }
        else {
            $data = new Rex_Product_Data_Retriever( $product, $this->feed_config, null, $this->append_variation, $product_meta_keys, $analytics_params );
        }
        return $data->get_all_data();*/
    }

    /**
     * @desc Converts all additional image link
     * as one string for pinterest.
     *
     * @param $data
     * @return mixed
     */
    protected function additional_img_link_pinterest( $data )
    {
        $additional_image_link_keys   = array();
        $additional_image_link_values = array();
        $additional_image_link_keys   = $this->preg_array_key_exists( '/^additional_image_link_/', $data );

        if ( !empty( $additional_image_link_keys ) ) {

            foreach ( $additional_image_link_keys as $key ) {
                array_push( $additional_image_link_values, $data[ $key ] );
                unset( $data[ $key ] );
            }

            $additional_image_link_str       = implode( ', ', $additional_image_link_values );
            $data[ 'additional_image_link' ] = $additional_image_link_str;

            return $data;
        }
        return $data;
    }

    /**
     * @desc Returns keys of an array with matching pattern.
     *
     * @param $pattern
     * @param $array
     * @return array|false
     */
    protected function preg_array_key_exists( $pattern, $array )
    {
        // extract the keys.
        $keys = array_keys( $array );

        // convert the preg_grep() returned array to int..and return.
        // the ret value of preg_grep() will be an array of values
        // that match the pattern.
        return preg_grep( $pattern, $keys );
    }

    /**
     * Save the feed as XML file.
     *
     * @return bool
     */
    protected function save_feed( $format )
    {
        $publish_btn = get_post_meta( $this->id, '_rex_feed_publish_btn', true ) ?: get_post_meta( $this->id, 'rex_feed_publish_btn', true );

        if( 'rex-bottom-preview-btn' === $publish_btn ) {
            $feed_file_name = "preview-feed-{$this->id}";
            $feed_file_meta_key = '_rex_feed_preview_file';
        }
        else {
            $feed_file_name = "feed-{$this->id}";
            $feed_file_meta_key = '_rex_feed_xml_file';
        }

        $prev_feed_name = $this->get_prev_feed_file_name();

        $path    = wp_upload_dir();
        $baseurl = $path[ 'baseurl' ];
        $path    = $path[ 'basedir' ] . '/rex-feed';

        // make directory if not exist
        if ( !file_exists( $path ) ) {
            wp_mkdir_p( $path );
        }

        if ( $this->is_logging_enabled ) {
            $log = wc_get_logger();
            if ( $this->batch == $this->tbatch ) {
                $log->info( __( 'Completed feed generation job.', 'rex-product-feed' ), array( 'source' => 'WPFM', ) );
                $log->info( '**************************************************', array( 'source' => 'WPFM', ) );
            }
        }

        if ( 'xml' === $format || 'rss' === $format ) {
            $file = trailingslashit( $path ) . "temp-{$feed_file_name}." . $format;

            update_post_meta( $this->id, '_rex_feed_feed_format', $this->feed_format );

            $this->feed = wpfm_replace_special_char( $this->feed );

            if ( file_exists( $file ) ) {
                if ( $this->batch == 1 ) {
                    $feed = new DOMDocument;
                    $feed->loadXML( $this->feed );
                    $this->feed = $feed->saveXML( $feed, LIBXML_NOEMPTYTAG );

                    if ( $this->tbatch > 1 ) {
                        $this->footer_replace();
                    }
                    file_put_contents( $file, $this->feed );
                }
                else {
                    $feed = $this->get_items();
                    file_put_contents( $file, $feed, FILE_APPEND );
                }
            }
            else {
                if ( (int) $this->tbatch > 1 ) {
                    $this->footer_replace();
                }

                file_put_contents( $file, $this->feed, FILE_APPEND );
            }

            if ( $this->batch === $this->tbatch && file_exists( $file ) && function_exists( 'rename' ) ) {
                if ( function_exists( 'rex_feed_is_valid_xml' ) && rex_feed_is_valid_xml( $file, $this->id, $this->merchant ) ) {
                    rename( $file, trailingslashit( $path ) . "{$feed_file_name}.{$format}" );
                    delete_post_meta( $this->id, '_rex_feed_temp_xml_file' );
                    delete_post_meta( $this->id, 'rex_feed_temp_xml_file' );
                    update_post_meta( $this->id, $feed_file_meta_key,  "{$baseurl}/rex-feed/{$feed_file_name}.{$format}" );

                    if( 'publish' === $publish_btn ) {
                        $this->delete_prev_feed_file( "{$feed_file_name}.{$format}", $prev_feed_name, $path );
                    }
                }
                else {
                    update_post_meta( $this->id, '_rex_feed_temp_xml_file', "{$baseurl}/rex-feed/temp-{$feed_file_name}.{$format}" );
                    return 'false';
                }
            }
            return 'true';
        }
        elseif ( $format === 'text' ) {
            if( $this->feed ) {
                //$this->feed = iconv( "UTF-8", "Windows-1252//IGNORE", $this->feed );
                $file = trailingslashit( $path ) . "{$feed_file_name}.txt";

                if( (int) $this->batch === 1 && file_exists( $file ) ) {
                    unlink( $file );
                }

                if ( (int) $this->batch > 1 && file_exists( $file ) ) {
                    $header       = strtok( $this->feed, "\n" );
                    $saved        = file_get_contents( $file );
                    $saved_header = strtok( $saved, "\n" );

                    if( false !== strpos( $saved_header, $header ) ) {
                        $this->feed = substr( $this->feed, strpos( $this->feed, "\n" ) + 1 );
                    }
                }

                if( file_exists( $file ) ) {
                    if( $this->batch == 1 ) {
                        file_put_contents( $file, $this->feed );
                    }
                    else {
                        $feed = $this->feed;
                        if( $feed ) {
                            file_put_contents( $file, $feed, FILE_APPEND );
                        }
                    }
                }
                else {
                    file_put_contents( $file, $this->feed );
                }
                if( $this->batch === $this->tbatch ) {
                    if( 'publish' === $publish_btn ) {
                        $this->delete_prev_feed_file( "{$feed_file_name}.txt", $prev_feed_name, $path );
                    }
                    update_post_meta( $this->id, $feed_file_meta_key, $baseurl . "/rex-feed/{$feed_file_name}.txt" );
                }
            }
            return 'true';
        }
        elseif ( $format === 'tsv' ) {
            $this->feed = iconv( "UTF-8", "Windows-1252//IGNORE", $this->feed );

            $file = trailingslashit( $path ) . "{$feed_file_name}.tsv";
            update_post_meta( $this->id, '_rex_feed_feed_format', $this->feed_format );

            if ( file_exists( $file ) ) {
                if ( $this->batch == 1 ) {
                    file_put_contents( $file, $this->feed ) ? 'true' : 'false';
                }
                else {
                    $feed = $this->feed;
                    $first_element = strtok($feed, "\n");
                    $feed = ltrim(str_replace( $first_element, '', $feed ));

                    if ( $feed ) {
                        file_put_contents( $file, $feed, FILE_APPEND ) ? 'true' : 'false';
                    }
                }
            }
            else {
                file_put_contents( $file, $this->feed ) ? 'true' : 'false';
            }
            if( $this->batch === $this->tbatch ) {
                if( 'publish' === $publish_btn ) {
                    $this->delete_prev_feed_file( "{$feed_file_name}.{$format}", $prev_feed_name, $path );
                }
                update_post_meta( $this->id, $feed_file_meta_key, $baseurl . "/rex-feed/{$feed_file_name}.tsv" );
            }
            return 'true';
        }
        elseif ( $format === 'csv' ) {
            $file = trailingslashit( $path ) . "{$feed_file_name}.csv";
            update_post_meta( $this->id, '_rex_feed_feed_format', $this->feed_format );
            update_post_meta( $this->id, '_rex_feed_separator', $this->feed_separator );

            if( $this->batch === $this->tbatch ) {
                if( 'publish' === $publish_btn ) {
                    $this->delete_prev_feed_file( "{$feed_file_name}.{$format}", $prev_feed_name, $path );
                }
                update_post_meta( $this->id, $feed_file_meta_key, $baseurl . "/rex-feed/{$feed_file_name}.csv" );
            }

            return wpfm_generate_csv_feed( $this->feed, $file, $this->feed_separator, $this->batch );
        }
        else {
            $file = trailingslashit( $path ) . "{$feed_file_name}.xml";
            update_post_meta( $this->id, $feed_file_meta_key, $baseurl . "/rex-feed/{$feed_file_name}.xml" );
            update_post_meta( $this->id, '_rex_feed_feed_format', $this->feed_format );

            $this->feed = wpfm_replace_special_char( $this->feed );

            if ( file_exists( $file ) ) {
                if ( $this->batch == 1 ) {
                    $this->footer_replace();
                    return file_put_contents( $file, $this->feed ) ? 'true' : 'false';
                }
                else {
                    $feed = $this->get_items();

                    if ( $this->merchant === 'google' && $this->feed_string_footer !== '' ) {
                        $request        = wp_remote_get($baseurl .'/rex-feed'.  "/{$feed_file_name}." . $format, array('sslverify' => FALSE));
                        if( is_wp_error( $request ) ) {
                            return 'false';
                        }
                        $file_contents  = wp_remote_retrieve_body( $request );
                        if ( !strpos( $file_contents, $this->item_wrapper ) ) {
                            $feed = '';
                        }
                    }

                    file_put_contents( $file, $feed, FILE_APPEND );
                    return 'true';
                }
            }
            else {
                return file_put_contents( $file, $this->feed ) ? 'true' : 'false';
            }
        }
    }

    abstract public function footer_replace();

    /**
     * get feed item as string
     *
     * @return string
     */
    public function get_items()
    {

        $feed = new DOMDocument;
        $feed->loadXML( $this->feed );

        if ( $this->merchant === 'google' || $this->merchant === 'facebook'
            || $this->merchant === 'pinterest'
            || $this->merchant === 'ciao'
            || $this->merchant === 'daisycon'
            || $this->merchant === 'instagram'
            || $this->merchant === 'liveintent'
            || $this->merchant === 'google_shopping_actions'
            || $this->merchant === 'google_express'
            || $this->merchant === 'doofinder'
            || $this->merchant === 'emarts'
            || $this->merchant === 'epoq'
            || $this->merchant === 'google_local_products_inventory'
            || $this->merchant === 'google_merchant_promotion'
            || $this->merchant === 'google_manufacturer_center'
            || $this->merchant === 'bing_image'
            || $this->merchant === 'rss'
            || $this->merchant === 'criteo'
            || $this->merchant === 'adcrowd'
            || $this->merchant === 'google_local_inventory_ads'
            || $this->merchant === 'compartner'
        ) {
            $node = $feed->getElementsByTagName( "item" );
            if ( $this->batch === $this->tbatch ) {
                $this->item_wrapper = '<item>';
                $this->feed_string_footer .= '</channel></rss>';
            }
        }
        elseif ( $this->merchant === 'ebay_mip' ) {
            if ( $feed->getElementsByTagName( "product" ) ) {
                $node = $feed->getElementsByTagName( "product" );
                $this->item_wrapper = '<product>';
            }
            else {
                $node = $feed->getElementsByTagName( "productVariationGroup" );
                $this->item_wrapper = '<productVariationGroup>';
            }
            if ( $this->batch == $this->tbatch ) {
		        $this->feed_string_footer .= '</productRequest>';
	        }
        }
        elseif ( $this->merchant === 'ceneo' ) {
            $node = $feed->getElementsByTagName( "o" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<o>';
                $this->feed_string_footer .= '</offers>';
            }
        }
        elseif ( $this->merchant === 'heureka'
            || $this->merchant === 'zbozi'
            || $this->merchant === 'rakuten'
            || $this->merchant === 'domodi'
            || $this->merchant === 'glami'
        ) {
            $node = $feed->getElementsByTagName( "SHOPITEM" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<SHOPITEM>';
                $this->feed_string_footer .= '</SHOP>';
            }
        }
        elseif ( $this->merchant === 'marktplaats' ) {
            $node = $feed->getElementsByTagName( "admarkt:ad" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<admarkt:ad>';
                $this->feed_string_footer .= '</admarkt:ads>';
            }
        }
        elseif ( $this->merchant === 'trovaprezzi' ) {
            $node = $feed->getElementsByTagName( "Offer" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<Offer>';
                $this->feed_string_footer .= '</Products>';
            }
        }
        elseif( $this->merchant === 'yandex'
            || $this->merchant === 'rozetka'
            || $this->merchant === 'admitad'
            || $this->merchant === 'ibud'
        ) {
            $node = $feed->getElementsByTagName( "offer" );
            if( $this->batch == $this->tbatch ) {
                $this->item_wrapper       = '<offer>';
                $this->feed_string_footer .= '</offers></shop></yml_catalog>';
            }
        }
        elseif ( $this->merchant === 'vivino' ) {
            $node = $feed->getElementsByTagName( "product" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<product>';
                $this->feed_string_footer .= '</vivino-product-list>';
            }
        }
        elseif ( $this->merchant === 'skroutz' ) {
            $node = $feed->getElementsByTagName( "product" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<product>';
                $this->feed_string_footer .= '</products></mywebstore>';
            }
        }
        elseif ( $this->merchant === 'google_review' ) {
            $node = $feed->getElementsByTagName( "review" );

            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<review>';
                $this->feed_string_footer .= '</feed>';
            }
        }
        elseif ( $this->merchant === 'drezzy'
            || $this->merchant === 'homedeco'
            || $this->merchant === 'fashiola'
            || $this->merchant === 'datatrics'
            || $this->merchant === 'listupp'
            || $this->merchant === 'adform'
            || $this->merchant === 'clubic'
            || $this->merchant === 'drezzy'
            || $this->merchant === 'drm'
            || $this->merchant === 'job_board_io'
            || $this->merchant === 'kleding'
            || $this->merchant === 'shopalike'
            || $this->merchant === 'ladenzeile'
            || $this->merchant === 'winesearcher'
            || $this->merchant === 'whiskymarketplace'
        ) {
            $node = $feed->getElementsByTagName( "item" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<item>';
                $this->feed_string_footer .= '</items>';
            }
        }
        elseif ( $this->merchant === 'homebook' ) {
            $node = $feed->getElementsByTagName( "offer" );
            if( $this->batch == $this->tbatch ) {
                $this->item_wrapper       = '<offer>';
                $this->feed_string_footer .= '</offers>';
            }
        }
        elseif ( $this->merchant === 'emag' ) {
            $node = $feed->getElementsByTagName( "product" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<product>';
                $this->feed_string_footer .= '</shop>';
            }
        }
        elseif ( $this->merchant === 'grupo_zap' ) {
            $node = $feed->getElementsByTagName( "Listing" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<Listing>';
                $this->feed_string_footer .= '</Listings></ListingDataFeed>';
            }
        }
        elseif ( $this->merchant === 'lyst' ) {
            $node = $feed->getElementsByTagName( "item" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<item>';
                $this->feed_string_footer .= '</channel>';
            }
        }
        elseif ( $this->merchant === 'hertie' ) {
            $node = $feed->getElementsByTagName( "Artikel" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<Artikel>';
                $this->feed_string_footer .= '</Katalog>';
            }
        }
        elseif ( $this->merchant === 'leguide' || $this->merchant === 'whiskymarketplace' ) {
            $node = $feed->getElementsByTagName( "item" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<item>';
                $this->feed_string_footer .= '</products>';
            }
        }
        elseif ( $this->merchant === '123i' ) {
            $node = $feed->getElementsByTagName( "item" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<item>';
                $this->feed_string_footer .= '</Imoveis></Carga>';
            }
        }
        elseif ( $this->merchant === 'adtraction' || $this->merchant === 'webgains' ) {
            $node = $feed->getElementsByTagName( "item" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<item>';
                $this->feed_string_footer .= '</feed>';
            }
        }
        elseif ( $this->merchant === 'bloomville' ) {
            $node = $feed->getElementsByTagName( "CourseTemplate" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<CourseTemplate>';
                $this->feed_string_footer .= '</CourseTemplates>';
            }
        }
        elseif ( $this->merchant === 'custom' ) {
            $node = $feed->getElementsByTagName( "product" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<product>';
                $this->feed_string_footer .= '</products>';
                if( $this->custom_wrapper ) {
                    $this->item_wrapper = '</' . $this->custom_wrapper . '>';
                }
                if( $this->custom_items_wrapper ) {
                    $this->feed_string_footer = '</' . $this->custom_items_wrapper . '>';
                }
                if( $this->custom_wrapper_el ) {
                    $this->feed_string_footer = '</' . $this->custom_wrapper_el . '>' . $this->feed_string_footer;
                }
            }
        }
        elseif ( $this->merchant === 'domodi' ) {
            $node = $feed->getElementsByTagName( "SHOP" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<SHOP>';
                $this->feed_string_footer .= '</SHOPITEM>';
            }
        }
        elseif ( $this->merchant === 'incurvy' ) {
            $node = $feed->getElementsByTagName( "item" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<item>';
                $this->feed_string_footer .= '</produkte>';
            }
        }
        elseif ( $this->merchant === 'indeed' ) {
            $node = $feed->getElementsByTagName( "job" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<job>';
                $this->feed_string_footer .= '</source>';
            }
        }
        elseif ( $this->merchant === 'jobbird' ) {
            $node = $feed->getElementsByTagName( "job" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<job>';
                $this->feed_string_footer .= '</jobs>';
            }
        }
        elseif ( $this->merchant === 'joblift' ) {
            $node = $feed->getElementsByTagName( "job" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<job>';
                $this->feed_string_footer .= '</feed>';
            }
        }
        elseif ( $this->merchant === 'skroutz' ) {
            $node = $feed->getElementsByTagName( "mywebstore" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<mywebstore>';
                $this->feed_string_footer .= '</product>';
            }
        }
        elseif ( $this->merchant === 'ibud' ) {
            $node = $feed->getElementsByTagName( "shop" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<shop>';
                $this->feed_string_footer .= '</shop>';
            }
        }
        elseif ( $this->merchant === 'mirakl' ) {
            $node = $feed->getElementsByTagName( "import" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<import>';
                $this->feed_string_footer .= '</import>';
            }
        }
        elseif ( $this->merchant === 'spartooFr' ) {
            $node = $feed->getElementsByTagName( "product" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<product>';
                $this->feed_string_footer .= '</products></root>';
            }
        }
        elseif ( $this->merchant === 'Bestprice' ) {
            $node = $feed->getElementsByTagName( "product" );
            if ( $this->batch == $this->tbatch ) {
                $this->item_wrapper = '<product>';
                $this->feed_string_footer .= '</products></store>';
            }
        }
        elseif ( $this->merchant === 'DealsForU' ) {
            $node = $feed->getElementsByTagName( "offer" );
            if( $this->batch == $this->tbatch ) {
                $this->item_wrapper       = '<offer>';
                $this->feed_string_footer .= '</offers></import>';
            }
        }
        elseif ($this->merchant === 'gulog_gratis') {
            $node = $feed->getElementsByTagName("ad");

            if($this->batch == $this->tbatch) {
                $this->item_wrapper = '<ad>';
                $this->feed_string_footer .= '</ads>';
            }
        }elseif ($this->merchant === 'zap_co_il') {
            $node = $feed->getElementsByTagName("PRODUCT");

            if($this->batch == $this->tbatch) {
                $this->item_wrapper = '<PRODUCT>';
                $this->feed_string_footer .= '</PRODUCTS></STORE>';
            }
        }elseif ($this->merchant === 'hotline') {
            $node = $feed->getElementsByTagName("item");

            if($this->batch == $this->tbatch) {
                $this->item_wrapper = '<item>';
                $this->feed_string_footer .= '</items></price>';
            }
        }
        elseif ($this->merchant === 'heureka_availability') {
            $node = $feed->getElementsByTagName("item");

            if($this->batch == $this->tbatch) {
                $this->item_wrapper = '<item>';
                $this->feed_string_footer .= '</item_list>';
            }
        }
        else {
            $node = $feed->getElementsByTagName( "product" );
            if( $this->batch == $this->tbatch ) {
                $this->item_wrapper       = '<product>';
                $this->feed_string_footer .= '</products>';
            }
        }
        $str = '';

        if ( !empty( $node ) ) {
            for ( $i = 0; $i < $node->length; $i++ ) {
                $item = $node->item( $i );
                if ( $item != NULL ) {
                    $str .= $feed->saveXML( $item, LIBXML_NOEMPTYTAG );
                }
            }
        }

        $str .= $this->feed_string_footer;

        return $str;
    }

    /**
     * Gets the feed format of current feed.
     * @return mixed|Rex_Product_Feed_Abstract_Generator
     */
    public function get_feed_format() {
        return $this->feed_format;
    }

    /**
     * @desc Gets selected country for the feed.
     * @since 7.2.9
     * @return mixed|string
     */
    public function get_shipping() {
        return $this->feed_country;
    }

    /**
     * @desc Gets zip code country for the feed.
     * @since 7.2.18
     * @return mixed|string
     */
    public function get_zip_code() {
        return $this->feed_zip_code;
    }

    /**
     * @desc get previously save for the current feed
     * @since 7.2.12
     * @return string
     */
    private function get_prev_feed_file_name() {
        $prev_feed_url = get_post_meta( $this->id, '_rex_feed_xml_file', true ) ?: get_post_meta( $this->id, 'rex_feed_xml_file', true );

        $feed_file_name = explode( '/', $prev_feed_url );
        return $feed_file_name[ array_key_last( $feed_file_name ) ];
    }

    /**
     * @desc Delete previous feed file incase of new feed title/format
     * @since 7.2.12
     * @param $new_name
     * @param $prev_name
     * @param $path
     * @return void
     */
    private function delete_prev_feed_file( $new_name, $prev_name, $path ) {
        if( $prev_name !== $new_name ) {
            unlink( trailingslashit( $path ) . $prev_name );
        }
    }
}