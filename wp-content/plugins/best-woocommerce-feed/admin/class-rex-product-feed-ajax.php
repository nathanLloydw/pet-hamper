<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://rextheme.com
 * @since      1.0.0
 *
 * @package    Rex_Product_Metabox
 * @subpackage Rex_Product_Feed/admin
 */


class Rex_Product_Feed_Ajax {

    /**
     * The Product/Feed Config.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator    config    Feed config.
     */
    protected $config;


    /**
     * The feed format.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator    $feed_format    Contains format of the feed.
     */
    protected $feed_format;

    /**
     * The feed rules containing all attributes and their value mappings for the feed.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator    $feed_rules    Contains attributes and value mappings for the feed.
     */
    protected $feed_rules;


    /**
     * The feed filter rules containing all condition and values for the feed.
     *
     * @since    1.1.10
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator    $feed_rules_filter    Contains condition and value for the feed.
     */
    protected $feed_rules_filter;

    /**
     * The Product Query args to retrieve specific products for making the Feed.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Rex_Product_Feed_Abstract_Generator    $products_args    Contains products query args for feed.
     */
    protected $products_args;


    /**
     * Product Scope
     *
     * @since    1.1.10
     * @access   private
     * @var      Rex_Product_Feed_Abstract_Generator    $product_scope
     */
    protected $product_scope;

    /**
     * Hook in ajax handlers.
     *
     * @since    1.0.0
     */
	public static function init()
	{
		$validations = array(
			'logged_in' => true,
			'user_can'  => 'manage_options',
		);

		wp_ajax_helper()->handle( 'my-handle' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'get_product_number' ) )
		                ->with_validation( $validations );


		wp_ajax_helper()->handle( 'generate-feed' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'generate_feed' ) )
		                ->with_validation( $validations );


		wp_ajax_helper()->handle( 'generate-promotion-feed' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'generate_promotion_feed' ) )
		                ->with_validation( $validations );


		wp_ajax_helper()->handle( 'save-feed' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'save_feed' ) )
		                ->with_validation( $validations );

		wp_ajax_helper()->handle( 'merchant-change' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'show_feed_template' ) )
		                ->with_validation( $validations );


		/**
		 * Stop Admin Notices
		 */
		wp_ajax_helper()->handle( 'stop-notices' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'stop_notices' ) )
		                ->with_validation( $validations );

		/**
		 * Google Category Mapping
		 */
		wp_ajax_helper()->handle( 'category-mapping' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'category_mapping' ) )
		                ->with_validation( $validations );

		wp_ajax_helper()->handle( 'category-mapping-update' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'category_mapping_update' ) )
		                ->with_validation( $validations );

		wp_ajax_helper()->handle( 'category-mapping-delete' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'category_mapping_delete' ) )
		                ->with_validation( $validations );


		/**
		 * Google merchant settings
		 */
		wp_ajax_helper()->handle( 'google-merchant-settings' )
		                ->with_callback( array( 'Rex_Google_Merchant_Settings_Api', 'save_settings' ) )
		                ->with_validation( $validations );


		/**
		 * Send to Google
		 * Merchant Center
		 */
		wp_ajax_helper()->handle( 'send-to-google' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'send_to_google' ) )
		                ->with_validation( $validations );


		/**
		 * Add custom field
		 * to product
		 */
		wp_ajax_helper()->handle( 'rex-product-change-merchant-status' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rex_product_change_merchant_status' ) )
		                ->with_validation( $validations );


		/**
		 * Database Update
		 */
		wp_ajax_helper()->handle( 'rex-wpfm-database-update' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rex_wpfm_database_update' ) )
		                ->with_validation( $validations );


		/**
		 * Database Update
		 */
		wp_ajax_helper()->handle( 'rex-wpfm-fetch-google-category' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'fetch_google_category' ) )
		                ->with_validation( $validations );


		/**
		 * update batch
		 */
		wp_ajax_helper()->handle( 'rex-product-update-batch-size' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'update_batch_size' ) )
		                ->with_validation( $validations );


		/**
		 * clear batch
		 */
		wp_ajax_helper()->handle( 'rex-product-clear-batch' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'clear_batch' ) )
		                ->with_validation( $validations );


		/**
		 * Show log
		 */
		wp_ajax_helper()->handle( 'rex-product-feed-show-log' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'show_wpfm_log' ) )
		                ->with_validation( $validations );


		/**
		 * Show black friday notices
		 */
		wp_ajax_helper()->handle( 'wpfm_bf_notice_dismiss' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'wpfm_bf_notice_dismiss' ) )
		                ->with_validation( $validations );


		wp_ajax_helper()->handle( 'wpfm-enable-fb-pixel' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'wpfm_enable_fb_pixel' ) )
		                ->with_validation( $validations );


		wp_ajax_helper()->handle( 'save-fb-pixel-value' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'save_fb_pixel_value' ) )
		                ->with_validation( $validations );


		wp_ajax_helper()->handle( 'rex-enable-log' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'wpfm_enable_log' ) )
		                ->with_validation( $validations );


		wp_ajax_helper()->handle( 'save-wpfm-transient' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'save_transient' ) )
		                ->with_validation( $validations );

		wp_ajax_helper()->handle( 'purge-wpfm-transient-cache' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'purge_transient_cache' ) )
		                ->with_validation( $validations );

		wp_ajax_helper()->handle( 'allow-private-products' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'allow_private_products' ) )
		                ->with_validation( $validations );

		wp_ajax_helper()->handle( 'bf-notice-dismiss' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rt_black_friday_offer_notice_dismiss' ) )
		                ->with_validation( $validations );

		/**
		 * Trigger review request
		 */
		wp_ajax_helper()->handle( 'trigger-review-request' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rex_feed_trigger_review_request' ) )
		                ->with_validation( $validations );

		/**
		 * Save WPFM Custom meta field values to show in the front view
		 */
		wp_ajax_helper()->handle( 'rex-product-save-custom-fields-data' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rex_product_save_custom_fields_data' ) )
		                ->with_validation( $validations );

		/**
		 * New changes message
		 */
		wp_ajax_helper()->handle( 'new-changes-message' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rex_feed_new_changes_message' ) )
		                ->with_validation( $validations );

	    /**
	     * Loads taxonomies
	     */
		wp_ajax_helper()->handle( 'rex-feed-load-taxonomies' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rex_feed_load_taxonomies' ) )
		                ->with_validation( $validations );


		wp_ajax_helper()->handle( 'rex-feed-get-appsero-options' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rex_feed_get_appsero_options' ) )
		                ->with_validation( $validations );


		wp_ajax_helper()->handle( 'wpfm-remove-plugin-data' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rex_feed_remove_plugin_data' ) )
		                ->with_validation( $validations );


		wp_ajax_helper()->handle( 'rex-feed-custom-filters' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rex_feed_custom_filter_option' ) )
		                ->with_validation( $validations );

		wp_ajax_helper()->handle( 'rex-feed-save-char-limit-option' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rex_feed_save_char_limit_option' ) )
                        ->with_validation( $validations );

		wp_ajax_helper()->handle( 'rex-feed-delete-publish-btn-id' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rex_feed_delete_publish_btn_id' ) )
		                ->with_validation( $validations );

		wp_ajax_helper()->handle( 'rex-feed-hide-char-limit-col' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rex_feed_hide_char_limit_col' ) )
		                ->with_validation( $validations );

		wp_ajax_helper()->handle( 'rex-feed-update-abandoned-child-list' )
		                ->with_callback( array( 'Rex_Product_Feed_Ajax', 'rex_feed_update_abandoned_child_list' ) )
		                ->with_validation( $validations );
    }


    /**
     * Get total number of products
     *
     * @since    2.0.0
     */
    public static function get_product_number( $payload ) {
        $feed_id = isset( $payload[ 'feed_id' ] ) ? $payload[ 'feed_id' ] : '';

        if( isset( $payload[ 'feed_title' ] ) && '' !== $payload[ 'feed_title' ] ) {
            $args = [
                'post_type'      => 'product-feed',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'title'          => $payload[ 'feed_title' ]
            ];

            $feed_ids     = get_posts( $args );
            $current_feed = array_search( $feed_id, $feed_ids );
            if( false !== $current_feed ) {
                unset( $feed_ids[ $current_feed ] );
            }

            if( !empty( $feed_ids ) ) {
                return array(
                    'feed_title' => 'duplicate'
                );
            }
        }

        $btn_id         = isset( $payload[ 'button_id' ] ) ? $payload[ 'button_id' ] : '';
        $is_premium     = apply_filters( 'wpfm_is_premium', false );
        $products       = apply_filters( 'wpfm_get_total_number_of_products', array( 'products' => WPFM_FREE_MAX_PRODUCT_LIMIT ), $feed_id );
        $per_page       = get_option( 'rex-wpfm-product-per-batch', WPFM_FREE_MAX_PRODUCT_LIMIT );
        $posts_per_page = $is_premium ? ( int )$per_page : ( ( int )$per_page >= WPFM_FREE_MAX_PRODUCT_LIMIT ? WPFM_FREE_MAX_PRODUCT_LIMIT : ( int )$per_page );

        update_post_meta( $feed_id, '_rex_feed_publish_btn', $btn_id );

        return array(
            'products'    => $products[ 'products' ],
            'per_batch'   => $posts_per_page,
            'total_batch' => ceil( $products[ 'products' ] / (int)$posts_per_page ),
            'feed_title'  => 'unique'
        );
    }


    /**
     * Generate feed
     * @param $config
     * @return string
     */
    public static function generate_feed( $config ){
        try {
            $merchant = Rex_Product_Feed_Factory::build( $config );
        } catch (Exception $e) {
            return $e->getMessage();
        }
        return $merchant->make_feed();
    }


    /**
     * Generate google promotion feed
     * @param $config
     * @return string
     */
    public static function generate_promotion_feed( $config ) {
        $merchant = new Rex_Product_Feed_Google_merchant_promotion();
        return $merchant->make_feed($config);
    }


    /**
     * @desc Show feed template
     * @param $merchant
     * @return array
     * @throws Exception
     */
    public static function show_feed_template( $merchant )
    {
	    $post_id       = isset( $merchant[ 'post_id' ] ) ? $merchant[ 'post_id' ] : '';
	    $feed_rules    = get_post_meta( $post_id, '_rex_feed_feed_config', true ) ?: get_post_meta( $post_id, 'rex_feed_feed_config', true );
	    $merchant_name = isset( $merchant[ 'merchant' ] ) ? $merchant[ 'merchant' ] : '';
        $saved_merchant = get_post_meta( $post_id, '_rex_feed_merchant', true ) ?: get_post_meta( $post_id, 'rex_feed_merchant', true );
        if ( $merchant_name !== $saved_merchant ) {
            $feed_rules = false;
        }

        $feed_template = Rex_Feed_Template_Factory::build( $merchant_name, $feed_rules );
        $feed_format = Rex_Feed_Merchants::get_feed_formats( $merchant_name );
        $feed_separator = Rex_Feed_Merchants::get_csv_feed_separators( $merchant_name );

        ob_start();
        if( in_array($merchant_name, apply_filters('wpfm_has_custom_feed_config', array()))) {
	        if ( wpfm_pro_compatibility() ) {
		        do_action('wpfm_custom_metabox_display_'. $merchant_name, $merchant_name, $feed_template);
            }
        }else {
            include plugin_dir_path( __FILE__ ) . 'partials/feed-config-metabox-display.php';
        }
        $result = ob_get_contents();

        ob_end_clean();
        ob_flush();
        $selected_format = get_post_meta($merchant['post_id'], '_rex_feed_feed_format', true) ?: get_post_meta($merchant['post_id'], 'rex_feed_feed_format', true);
        if(!$selected_format) {
            $selected_format = $feed_format[0];
        }

	    return array(
		    'success'        => true,
		    'html'           => $result,
		    'feed_format'    => $feed_format,
		    'feed_separator' => $feed_separator,
		    'select'         => $selected_format
	    );
    }


    /**
     * Save Category Map
     * @param $payload
     * @return string
     */
    public static function category_mapping($payload){
        $map_name = $payload['map_name'];
        $category_map = get_option('rex-wpfm-category-mapping') ? get_option('rex-wpfm-category-mapping') : array();
        $status = 'success';
        $wpfm_hash = isset( $payload[ 'hash' ] ) ? $payload[ 'hash' ] : '';

        if ( $wpfm_hash !== '' && array_key_exists( $wpfm_hash, $category_map ) ) {
            wp_send_json_success( [ 'status' => $status, 'location' => esc_url( admin_url( 'admin.php?page=category_mapping' ) ) ] );
        }
        if ( $wpfm_hash !== '' ) {
            $status = 'reload';
        }

        $map_name_hash = $wpfm_hash !== '' ? $wpfm_hash : md5(sanitize_title($map_name).time());
        $cat_map_array = array();
        parse_str( $payload['cat_map'], $cat_map_array );
        $config_array = array();
        $map_array = array();
        if($cat_map_array) {
            foreach ($cat_map_array as $key=>$value) {
                $cat_id = preg_replace('/[^0-9]/', '', $key);
                $product_cat = get_term_by('id', $cat_id, 'product_cat');
                $category_name = '';
                if($product_cat) {
                    $category_name = $product_cat->name;
                }
                array_push($config_array, array('map-key' => $cat_id, 'map-value' => $value, 'cat-name' => $category_name));
            }
        }

        $map_array['map-name'] = $map_name;
        $map_array['map-config'] = $config_array;

        $category_map[$map_name_hash] = $map_array;
        update_option('rex-wpfm-category-mapping', $category_map);
        wp_send_json_success( [ 'status' => $status, 'location' => esc_url( admin_url( 'admin.php?page=category_mapping' ) ) ] );
    }


    /**
     * generate category mapping
     * @param $payload
     * @return string
     */
    public static function category_mapping_update($payload){
        $map_key = $payload['map_key'];
        $map_name = $payload['map_name'];
        $cat_map_array       = array();
        parse_str( $payload['cat_map'], $cat_map_array );
        $config_array = array();
        $map_array = array();
        if($cat_map_array) {
            foreach ($cat_map_array as $key=>$value) {
                $cat_id = preg_replace('/[^0-9]/', '', $key);
                $product_cat = get_term_by('id', $cat_id, 'product_cat');
                $category_name = '';
                if($product_cat) {
                    $category_name = $product_cat->name;
                }
                array_push($config_array, array('map-key' => $cat_id, 'map-value' => $value, 'cat-name' => $category_name));
            }
        }

        $map_array['map-name'] = $map_name;
        $map_array['map-config'] = $config_array;
        $category_map = get_option('rex-wpfm-category-mapping') ? get_option('rex-wpfm-category-mapping') : array();
        $category_map[$map_key] = $map_array;
        update_option('rex-wpfm-category-mapping', $category_map);
        return 'success';
    }


    /**
     * Delete Category Mapping
     * @param $payload
     * @return string
     */
    public static function category_mapping_delete($payload){
        $map_key = $payload['map_key'];
        $category_map = get_option('rex-wpfm-category-mapping');
        unset($category_map[$map_key]);
        update_option('rex-wpfm-category-mapping', $category_map);
        return 'Success';
    }


    /**
     * Stop admin notices
     * @param $payload
     * @return string
     */
    function stop_notices($payload) {
        update_option('rex_bwfm_notification_status', 'no');
        return 'success';
    }


    /**
     * Change merchant status
     * @param $payload
     * @return string
     */
    public static function rex_product_change_merchant_status($payload) {
        $merchants = get_option('rex_wpfm_merchant_status');

        if(!$merchants) {
            $latest_merchants = $payload;
        }else {
            $latest_merchants = array_merge($merchants, $payload);
        }
        update_option('rex_wpfm_merchant_status', $latest_merchants);
        return 'success';
    }


    /**
     * Send feed to Google
     * @param $payload
     * @return array
     */
    public static function send_to_google($payload) {
	    $feed_id             = $payload[ 'feed_id' ];
	    $rex_google_merchant = new Rex_Google_Merchant_Settings_Api();
	    if ( $rex_google_merchant->is_authenticate() ) {
		    $feed_url      = get_post_meta( $feed_id, '_rex_feed_xml_file', true ) ?: get_post_meta( $feed_id, 'rex_feed_xml_file', true );
		    $feed_title    = get_the_title( $feed_id );
		    $client        = $rex_google_merchant::get_client();
		    $client_id     = $rex_google_merchant::$client_id;
		    $client_secret = $rex_google_merchant::$client_secret;
		    $merchant_id   = $rex_google_merchant::$merchant_id;


		    $access_token = $rex_google_merchant->get_access_token();
		    $client->setClientId( $client_id );
		    $client->setClientSecret( $client_secret );
		    $client->setScopes( 'https://www.googleapis.com/auth/content' );
		    $client->setAccessToken( $access_token );

		    /*
			 * Initialize service and datafeed
			 */
		    $service  = new RexFeed\Google\Service\ShoppingContent( $client );
		    $datafeed = new RexFeed\Google\Service\ShoppingContent\Datafeed();
		    $target   = new RexFeed\Google\Service\ShoppingContent\DatafeedTarget();

		    $name     = $feed_title;
		    $filename = $name . uniqid();

		    $target->setLanguage( $payload[ 'language' ] );
		    $target->setCountry( $payload[ 'country' ] );
		    /*if ( count( $payload[ 'destination' ] ) ) {
			    $target->setIncludedDestinations( $payload[ 'destination' ] );
		    }*/

		    $datafeed->setName( $name );
		    $datafeed->setContentType( 'products' );
		    $datafeed->setAttributeLanguage( $payload[ 'language' ] );
		    $datafeed->setTargets( [ $target ] );

		    if ( !$rex_google_merchant->feed_exists( $feed_id ) ) {
			    $datafeed->setFileName( $filename );
		    }
		    else {
                $data_feed_file = get_post_meta( $feed_id, '_rex_feed_google_data_feed_file_name', true ) ?: get_post_meta( $feed_id, 'rex_feed_google_data_feed_file_name', true );
			    $datafeed->setFileName( $data_feed_file );
		    }

		    /*
			 * Initialize Schedule
			 */
		    $fetch_schedule = new RexFeed\Google\Service\ShoppingContent\DatafeedFetchSchedule();
		    if ( $payload[ 'schedule' ] === 'monthly' ) {
			    $fetch_schedule->setDayOfMonth( $payload[ 'month' ] );
		    }
		    if ( $payload[ 'schedule' ] === 'weekly' ) {
			    $fetch_schedule->setWeekday( $payload[ 'day' ] );
		    }
		    $fetch_schedule->setHour( $payload[ 'hour' ] );
		    $fetch_schedule->setFetchUrl( $feed_url );

		    /*
			 * initialize feed format
			 */
		    $format = new RexFeed\Google\Service\ShoppingContent\DatafeedFormat();
		    $format->setFileEncoding( 'utf-8' );
		    $datafeed->setFormat( $format );
		    $datafeed->setFetchSchedule( $fetch_schedule );

		    try {
			    if ( $rex_google_merchant->feed_exists( $feed_id ) ) {
				    $datafeedID = get_post_meta( $feed_id, '_rex_feed_google_data_feed_id', true ) ?: get_post_meta( $feed_id, 'rex_feed_google_data_feed_id', true );
				    $datafeed->setId( $datafeedID );
				    $service->datafeeds->update( $merchant_id, $datafeedID, $datafeed );
			    }
			    else {
				    $datafeed         = $service->datafeeds->insert( $merchant_id, $datafeed );
				    $datafeedID       = $datafeed->getId();
				    $datafeedFileName = $datafeed->getFileName();
				    update_post_meta( $feed_id, '_rex_feed_google_data_feed_id', $datafeedID );
				    update_post_meta( $feed_id, '_rex_feed_google_data_feed_file_name', $datafeedFileName );
			    }
			    $service->datafeeds->fetchnow( $merchant_id, $datafeedID );
		    }
		    catch ( Exception $e ) {
			    if ( is_wpfm_logging_enabled() ) {
				    $log = wc_get_logger();
				    $log->info( $e->getMessage(), array( 'source' => 'WPFM-google' ) );
			    }

                if ( !is_string( $e->getMessage() ) && is_object( $e->getMessage() ) ) {
                    $error = json_decode( $e->getMessage() );
                    $reason = isset($error->error->errors) ? $error->error->errors : '';
                }
                else {
                    $error = $e->getMessage();
                }

			    return array(
				    'success' => false,
				    'message' => isset( $error->error->message ) ? $error->error->message : $error,
				    'reason'  => isset( $reason[ 0 ]->reason ) ? $reason[ 0 ]->reason : $error
			    );
		    }
	    }

	    update_post_meta( $feed_id, '_rex_feed_google_schedule', $payload[ 'schedule' ] );
	    update_post_meta( $feed_id, '_rex_feed_google_schedule_time', $payload[ 'hour' ] );
	    update_post_meta( $feed_id, '_rex_feed_google_schedule_month', $payload[ 'month' ] );
	    update_post_meta( $feed_id, '_rex_feed_google_schedule_week_day', $payload[ 'day' ] );
	    update_post_meta( $feed_id, '_rex_feed_google_target_country', $payload[ 'country' ] );
	    update_post_meta( $feed_id, '_rex_feed_google_target_language', $payload[ 'language' ] );
	    return array( 'success' => true );
    }


    /**
     * WPFM database update
     */
    public static function rex_wpfm_database_update() {
        check_ajax_referer('rex-wpfm-ajax', 'security');
        require_once WPFM_PLUGIN_DIR_PATH . 'includes/class-rex-product-feed-activator.php';
        set_transient( 'rex-wpfm-database-update-running', true, 3153600000 );
        global $rex_product_feed_database_update;
        $db_updates_callbacks = Rex_Product_Feed_Activator::get_db_update_callbacks();
        $rex_product_feed_database_update->push_to_queue( $db_updates_callbacks);
        $rex_product_feed_database_update->save()->dispatch();
        Rex_Product_Feed_Activator::update_db_version('2.2.5');
        wp_send_json_success('success');
        wp_die();
    }


    /**
     * Fetch google category
     * @param $payload
     * @return string
     */
    public static function fetch_google_category($payload) {
        $file =  dirname(__FILE__) . '/partials/google_category_list.txt';
        $matches = array();
        $handle = @fopen($file, "r");
        while (!feof($handle)) {
            $cat = fgets($handle);
            $matches[] = $cat;
        }
        fclose($handle);
        return json_encode($matches, JSON_PRETTY_PRINT);
    }


    /**
     * Clear current batch
     *
     * @return void
     *
     * @since 1.0.0
     */
    public static function clear_batch() {
        global $wpdb;

        $WP_Background_Process = new Rex_Product_Feed_Background_Process();
        $WP_Background_Process->cancel_process();

        delete_option('rex_wpfm_feed_queue');

        $wpdb->update(
            $wpdb->postmeta,
            [ 'meta_value' => 'completed' ],
            [ 'meta_key' => '_rex_feed_status' ],
        );

        $find_key_1 = $wpdb->esc_like( 'wp_rex_product_feed_background_process_batch_' ) . '%';
        $find_key_2 = '%' . $wpdb->esc_like( 'wp_rex_product_feed_background_process_cron' ) . '%';

        $wpdb->query(
            $wpdb->prepare(
                'DELETE FROM %1s WHERE `option_name` LIKE %s OR `option_name` LIKE %s;',
                $wpdb->options,
                $find_key_1,
                $find_key_2
            )
        );

        wp_send_json_success('success');
        wp_die();
    }

    /**
     * Update batch size
     * @param $payload
     */
    public static function update_batch_size($payload) {
        update_option('rex-wpfm-product-per-batch', $payload);
        wp_send_json_success('success');
        wp_die();
    }


    /**
     * WPFM log
     * @param $payload
     * @return array
     */
    public static function show_wpfm_log($payload) {

        $key = $payload['logKey'];
        $upload_dir = wp_upload_dir( null, false );
        $wc_log_url = $upload_dir['basedir'].'/wc-logs/';
        $file_url = $wc_log_url  . $key;
        ob_start();
        include_once $file_url;
        $out = ob_get_clean();
        ob_end_clean();
        return array(
            'success' => true,
            'content' => $out,
            'file_url' => $wc_log_url. $key
        );

    }


    /**
     * Black friday notice dismiss
     * @param $payload
     * @return array
     */
    public static function wpfm_bf_notice_dismiss($payload) {

        $current_time = time();
        $date_now = date("Y-m-d", $current_time);
        if( $date_now == '2019-11-29' || $date_now == '2019-11-28') {
            $wpfm_bf_notice = array(
                'show_notice' => 'never',
                'updated_at' => time(),
            );
        }else {
            $wpfm_bf_notice = array(
                'show_notice' => 'no',
                'updated_at' => time(),
            );
        }
        update_option('wpfm_bf_notice', json_encode($wpfm_bf_notice));
        return array(
            'success' => true,
        );
    }


    /**
     * @param $payload
     * @return array
     */
    public static function wpfm_enable_fb_pixel($payload) {
        if($payload['wpfm_fb_pixel_enabled'] == 'yes') {
            update_option('wpfm_fb_pixel_enabled', 'yes');
            return array(
                'success' => true,
                'data'  => 'enabled'
            );
        }else if ($payload['wpfm_fb_pixel_enabled'] == 'no') {
            update_option('wpfm_fb_pixel_enabled', 'no');
            return array(
                'success' => true,
                'data'  => 'disabled'
            );
        }
    }


    /**
     * @param $payload
     * @return array
     */
    public static function save_fb_pixel_value($payload) {
        update_option('wpfm_fb_pixel_value', $payload);
        return array(
            'success' => true,
        );
    }


    /**
     * Enable logging
     * @param $payload
     * @return array
     */
    public static function wpfm_enable_log($payload) {
        if($payload['wpfm_enable_log'] == 'yes') {
            update_option('wpfm_enable_log', 'yes');
            return array(
                'success' => true,
                'data'  => 'enabled'
            );
        }else if ($payload['wpfm_enable_log'] == 'no') {
            update_option('wpfm_enable_log', 'no');
            return array(
                'success' => true,
                'data'  => 'disabled'
            );
        }
    }


    public static function save_transient($payload) {
        update_option('wpfm_cache_ttl', $payload['value']);
        return array(
            'success' => true,
        );
    }


    public static function purge_transient_cache() {
        wpfm_purge_cached_data();
        return array(
            'success' => true,
        );
    }


    /**
     * Enable/Disable private products
     *
     * @param $payload
     * @return array
     */
    public static function allow_private_products($payload) {
        update_option('wpfm_allow_private', $payload['allow_private']);
        return array(
            'success' => true,
        );
    }


    /**
     * Black friday notice dismiss
     *
     * @param $payload
     * @return array
     * @since 6.1.0
     */
    public static function rt_black_friday_offer_notice_dismiss($payload) {
        $current_time = time();
        $info = array(
            'show_notice'   => 'no',
            'updated_at'    => $current_time,
        );
        update_option('rt_bf_notice', $info);
        return array(
            'success' => true,
        );
    }


	/**
     * @desc Update into database - Trigger Based Review Request
     *
	 * @param $payload
	 * @return bool[]
	 */
    public static function rex_feed_trigger_review_request( $payload ) {

	    $data = array(
		    'show'      => isset( $payload[ 'show' ] ) ? $payload[ 'show' ] : '',
		    'time'      => isset( $payload[ 'frequency' ] ) && $payload[ 'frequency' ] != 'never' ? time() : '',
		    'frequency' => isset( $payload[ 'frequency' ] ) ? $payload[ 'frequency' ] : ''
	    );

	    update_option( 'rex_feed_review_request', $data );

	    return array(
		    'success' => true,
	    );
    }


	/**
     * @desc Update into database - New Changes Message
     *
	 * @return bool[]
	 */
    public static function rex_feed_new_changes_message() {
	    update_option( 'rex_feed_new_changes_msg', 'hide' );

	    return array(
		    'success' => true,
	    );
    }


	/**
     * @desc Loads product taxonomies
     *
	 * @param $payload
	 * @return bool[]
	 */
    public static function rex_feed_load_taxonomies( $payload ) {
	    ob_start();
	    $feed_id = ( int ) $payload['feed_id'];
	    require_once plugin_dir_path( __FILE__ ) . 'partials/rex-feed-product-taxonomies-section.php';
	    $html_content = ob_get_contents();
	    ob_get_clean();

	    return array(
		    'success'      => true,
		    'html_content' => $html_content,
	    );
    }


    /**
     * Checks if there's any required attribute missing in Google Shopping Feed
     * @return void
     */
    public static function rex_feed_check_for_missing_attributes() {
        $nonce = isset( $_POST[ 'security' ] ) ? htmlspecialchars( trim( $_POST[ 'security' ] ) ) : ''; // phpcs:ignore

        if ( wp_verify_nonce( $nonce, 'rex-wpfm-ajax' ) ) {
            $feed_config = [];
            $config = isset( $_POST[ 'payload' ][ 'feed_config' ] ) ? $_POST[ 'payload' ][ 'feed_config' ] : ''; // phpcs:ignore
            parse_str( $config, $feed_config );

            $feed_config = function_exists( 'rex_feed_get_sanitized_get_post' ) ? rex_feed_get_sanitized_get_post( $feed_config ) : [];
            $feed_config = isset( $feed_config[ 'fc' ] ) ? $feed_config[ 'fc' ] : '';
            $feed_attr = [];

            if ( is_array( $feed_config ) ) {
                $feed_config = filter_var_array( $feed_config, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
                array_shift( $feed_config );
                $feed_attr = array_column( $feed_config, 'attr' );
            }

            $required_attr = array('id', 'title', 'description', 'link', 'image_link', 'availability', 'price', 'brand', 'gtin', 'mpn');
            $labels = array
            (
                'id' => 'Product Id [id]',
                'title' => 'Product Title [title]',
                'description' => 'Product Description [description]',
                'link' => 'Product URL [link]',
                'image_link' => 'Main Image [image_link]',
                'availability' => 'Stock Status [availability]',
                'price' => 'Regular Price [price]',
                'brand' => 'Manufacturer [brand]',
                'gtin' => 'GTIN [gtin]',
                'mpn' => 'MPN [mpn]'
            );

            wp_send_json_success( array( 'feed_attr' => $feed_attr, 'feed_config' => $feed_config, 'req_attr' => $required_attr, 'labels' => $labels) );
        }
        wp_send_json_error( array( 'feed_attr' => '', 'feed_config' => '', 'req_attr' => '', 'labels' => '') );
    }


	/**
     * Get Appsero options
	 */
    public static function rex_feed_get_appsero_options( $payload ) {
        $nonce = isset( $payload[ 'security' ] ) ? $payload[ 'security' ] : '';
        $html = '';

        if ( wp_verify_nonce( $nonce, 'rex-wpfm-ajax' ) ) {
            ob_start();
            ?>
            <li data-placeholder="Which plugin?">
                <label>
                    <input type="radio" name="selected-reason" value="found-better-plugin">
                    <div class="wd-de-reason-icon"><svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 23 23"><g fill="none"><g fill="#3B86FF"><path d="M17.1 14L22.4 19.3C23.2 20.2 23.2 21.5 22.4 22.4 21.5 23.2 20.2 23.2 19.3 22.4L19.3 22.4 14 17.1C15.3 16.3 16.3 15.3 17.1 14L17.1 14ZM8.6 0C13.4 0 17.3 3.9 17.3 8.6 17.3 13.4 13.4 17.2 8.6 17.2 3.9 17.2 0 13.4 0 8.6 0 3.9 3.9 0 8.6 0ZM8.6 2.2C5.1 2.2 2.2 5.1 2.2 8.6 2.2 12.2 5.1 15.1 8.6 15.1 12.2 15.1 15.1 12.2 15.1 8.6 15.1 5.1 12.2 2.2 8.6 2.2ZM8.6 3.6L8.6 5C6.6 5 5 6.6 5 8.6L5 8.6 3.6 8.6C3.6 5.9 5.9 3.6 8.6 3.6L8.6 3.6Z"></path></g></g></svg></div>
                    <div class="wd-de-reason-text">Found a better plugin</div>
                </label>
            </li>
            <li data-placeholder="How many products do you have in you store?">
                <label>
                    <input type="radio" name="selected-reason" value="product-limit">
                    <div class="wd-de-reason-icon"><svg width="24" height="24" xmlns="http://www.w3.org/2000/svg" fill-rule="evenodd" clip-rule="evenodd" fill="#3B86FF"><path d="M11.5 23l-8.5-4.535v-3.953l5.4 3.122 3.1-3.406v8.772zm1-.001v-8.806l3.162 3.343 5.338-2.958v3.887l-8.5 4.534zm-10.339-10.125l-2.161-1.244 3-3.302-3-2.823 8.718-4.505 3.215 2.385 3.325-2.385 8.742 4.561-2.995 2.771 2.995 3.443-2.242 1.241v-.001l-5.903 3.27-3.348-3.541 7.416-3.962-7.922-4.372-7.923 4.372 7.422 3.937v.024l-3.297 3.622-5.203-3.008-.16-.092-.679-.393v.002z"/></svg></div>
                    <div class="wd-de-reason-text">Product limit</div>
                </label>
            </li>
            <li data-placeholder="Would you like us to assist you?">
                <label>
                    <input type="radio" name="selected-reason" value="could-not-understand">
                    <div class="wd-de-reason-icon"><svg xmlns="http://www.w3.org/2000/svg" width="23" height="23" viewBox="0 0 23 23"><g fill="none"><g fill="#3B86FF"><path d="M11.5 0C17.9 0 23 5.1 23 11.5 23 17.9 17.9 23 11.5 23 10.6 23 9.6 22.9 8.8 22.7L8.8 22.6C9.3 22.5 9.7 22.3 10 21.9 10.3 21.6 10.4 21.3 10.4 20.9 10.8 21 11.1 21 11.5 21 16.7 21 21 16.7 21 11.5 21 6.3 16.7 2 11.5 2 6.3 2 2 6.3 2 11.5 2 13 2.3 14.3 2.9 15.6 2.7 16 2.4 16.3 2.2 16.8L2.1 17.1 2.1 17.3C2 17.5 2 17.7 2 18 0.7 16.1 0 13.9 0 11.5 0 5.1 5.1 0 11.5 0ZM6 13.6C6 13.7 6.1 13.8 6.1 13.9 6.3 14.5 6.2 15.7 6.1 16.4 6.1 16.6 6 16.9 6 17.1 6 17.1 6.1 17.1 6.1 17.1 7.1 16.9 8.2 16 9.3 15.5 9.8 15.2 10.4 15 10.9 15 11.2 15 11.4 15 11.6 15.2 11.9 15.4 12.1 16 11.6 16.4 11.5 16.5 11.3 16.6 11.1 16.7 10.5 17 9.9 17.4 9.3 17.7 9 17.9 9 18.1 9.1 18.5 9.2 18.9 9.3 19.4 9.3 19.8 9.4 20.3 9.3 20.8 9 21.2 8.8 21.5 8.5 21.6 8.1 21.7 7.9 21.8 7.6 21.9 7.3 21.9L6.5 22C6.3 22 6 21.9 5.8 21.9 5 21.8 4.4 21.5 3.9 20.9 3.3 20.4 3.1 19.6 3 18.8L3 18.5C3 18.2 3 17.9 3.1 17.7L3.1 17.6C3.2 17.1 3.5 16.7 3.7 16.3 4 15.9 4.2 15.4 4.3 15 4.4 14.6 4.4 14.5 4.6 14.2 4.6 13.9 4.7 13.7 4.9 13.6 5.2 13.2 5.7 13.2 6 13.6ZM11.7 11.2C13.1 11.2 14.3 11.7 15.2 12.9 15.3 13 15.4 13.1 15.4 13.2 15.4 13.4 15.3 13.8 15.2 13.8 15 13.9 14.9 13.8 14.8 13.7 14.6 13.5 14.4 13.2 14.1 13.1 13.5 12.6 12.8 12.3 12 12.2 10.7 12.1 9.5 12.3 8.4 12.8 8.3 12.8 8.2 12.8 8.1 12.8 7.9 12.8 7.8 12.4 7.8 12.2 7.7 12.1 7.8 11.9 8 11.8 8.4 11.7 8.8 11.5 9.2 11.4 10 11.2 10.9 11.1 11.7 11.2ZM16.3 5.9C17.3 5.9 18 6.6 18 7.6 18 8.5 17.3 9.3 16.3 9.3 15.4 9.3 14.7 8.5 14.7 7.6 14.7 6.6 15.4 5.9 16.3 5.9ZM8.3 5C9.2 5 9.9 5.8 9.9 6.7 9.9 7.7 9.2 8.4 8.2 8.4 7.3 8.4 6.6 7.7 6.6 6.7 6.6 5.8 7.3 5 8.3 5Z"></path></g></g></svg></div>
                    <div class="wd-de-reason-text">Couldn't understand</div>
                </label>
            </li>
            <li data-placeholder="Could you tell us more about that feature?">
                <label>
                    <input type="radio" name="selected-reason" value="not-have-that-feature">
                    <div class="wd-de-reason-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="17" viewBox="0 0 24 17"><g fill="none"><g fill="#3B86FF"><path d="M19.4 0C19.7 0.6 19.8 1.3 19.8 2 19.8 3.2 19.4 4.4 18.5 5.3 17.6 6.2 16.5 6.7 15.2 6.7 15.2 6.7 15.2 6.7 15.2 6.7 14 6.7 12.9 6.2 12 5.3 11.2 4.4 10.7 3.3 10.7 2 10.7 1.3 10.8 0.6 11.1 0L7.6 0 7 0 6.5 0 6.5 5.7C6.3 5.6 5.9 5.3 5.6 5.1 5 4.6 4.3 4.3 3.5 4.3 3.5 4.3 3.5 4.3 3.4 4.3 1.6 4.4 0 5.9 0 7.9 0 8.6 0.2 9.2 0.5 9.7 1.1 10.8 2.2 11.5 3.5 11.5 4.3 11.5 5 11.2 5.6 10.8 6 10.5 6.3 10.3 6.5 10.2L6.5 10.2 6.5 17 6.5 17 7 17 7.6 17 22.5 17C23.3 17 24 16.3 24 15.5L24 0 19.4 0Z"></path></g></g></svg></div>
                    <div class="wd-de-reason-text">Missing a specific feature</div>
                </label>
            </li>
            <li data-placeholder="Could you tell us a bit more?">
                <label>
                    <input type="radio" name="selected-reason" value="bugs">
                    <div class="wd-de-reason-icon"><svg xmlns="http://www.w3.org/2000/svg" width="25" height="25" viewBox="0 0 20 20"><g fill="none"><g fill="#3B86FF"><path d="M4.355.522a.5.5 0 0 1 .623.333l.291.956A4.979 4.979 0 0 1 8 1c1.007 0 1.946.298 2.731.811l.29-.956a.5.5 0 1 1 .957.29l-.41 1.352A4.985 4.985 0 0 1 13 6h.5a.5.5 0 0 0 .5-.5V5a.5.5 0 0 1 1 0v.5A1.5 1.5 0 0 1 13.5 7H13v1h1.5a.5.5 0 0 1 0 1H13v1h.5a1.5 1.5 0 0 1 1.5 1.5v.5a.5.5 0 1 1-1 0v-.5a.5.5 0 0 0-.5-.5H13a5 5 0 0 1-10 0h-.5a.5.5 0 0 0-.5.5v.5a.5.5 0 1 1-1 0v-.5A1.5 1.5 0 0 1 2.5 10H3V9H1.5a.5.5 0 0 1 0-1H3V7h-.5A1.5 1.5 0 0 1 1 5.5V5a.5.5 0 0 1 1 0v.5a.5.5 0 0 0 .5.5H3c0-1.364.547-2.601 1.432-3.503l-.41-1.352a.5.5 0 0 1 .333-.623zM4 7v4a4 4 0 0 0 3.5 3.97V7H4zm4.5 0v7.97A4 4 0 0 0 12 11V7H8.5zM12 6a3.989 3.989 0 0 0-1.334-2.982A3.983 3.983 0 0 0 8 2a3.983 3.983 0 0 0-2.667 1.018A3.989 3.989 0 0 0 4 6h8z"/></g></g></svg></div>
                    <div class="wd-de-reason-text">Bugs</div>
                </label>
            </li>
            <?php
            $html = ob_get_clean();
            wp_send_json_success( array( 'html' => $html ) );
        }
        wp_send_json_error( array( 'html' => $html ) );
    }


    /**
     * Save WPFM Custom meta field values to show in the front view
     * @param $payload
     */
    public static function rex_product_save_custom_fields_data( $payload ) {
        $nonce = isset( $payload[ 'security' ] ) ? $payload[ 'security' ] : '';

        if ( wp_verify_nonce( $nonce, 'rex-wpfm-ajax' ) ) {
            $fields_value = isset( $payload[ 'fields_value' ] ) ? $payload[ 'fields_value' ] : array();

            if( !empty( $fields_value ) ) {
                update_option( 'wpfm_product_custom_fields_frontend', $fields_value );
            }
            else {
                delete_option( 'wpfm_product_custom_fields_frontend' );
            }
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    /**
     * @desc Update plugin removal option data
     * @param $payload
     * @return void
     */
    public static function rex_feed_remove_plugin_data( $payload ) {
        if ( isset( $payload[ 'wpfm_remove_plugin_data' ] ) ) {
            update_option('wpfm_remove_plugin_data', $payload['wpfm_remove_plugin_data']);
            wp_send_json_success();
        }
        wp_send_json_error();
    }

    /**
     * @desc Save custom filter option
     * @since 7.2.5
     * @param $payload
     * @return void
     */
    public static function rex_feed_custom_filter_option( $payload ) {
        $feed_id = isset( $payload[ 'feed_id' ] ) ? $payload[ 'feed_id' ] : '';
        $filter_option = isset( $payload[ 'button_text' ] ) ? $payload[ 'button_text' ] : 'removed';
        $event = isset( $payload[ 'button_event' ] ) ? $payload[ 'button_event' ] : 'on_load';

        if ( '' !== $feed_id ) {
            $prev_product_filter_option = get_post_meta( $feed_id, '_rex_feed_products', true ) ?: get_post_meta( $feed_id, 'rex_feed_products', true );
            if ( 'filter' === $prev_product_filter_option ) {
                update_post_meta( $feed_id, '_rex_feed_custom_filter_option', 'added' );
                update_post_meta( $feed_id, '_rex_feed_products', 'all' );
                wp_send_json_success(
                        array(
                                'button_text' => 'added'
                        )
                );
                wp_die();
            }
            elseif( 'on_load' === $event ) {
                $option = get_post_meta( $feed_id, '_rex_feed_custom_filter_option', true ) ?: get_post_meta( $feed_id, 'rex_feed_custom_filter_option', true );
                $option = '' !== $option ? $option : 'removed';
            }
            else {
                $option = $filter_option;
            }
            wp_send_json_success(
                array(
                    'button_text' => $option
                )
            );
            wp_die();
        }
    }


    /**
     * @desc Save option value to show/hide character
     * limit field in the field mapping table
     * @since 7.2.18
     * @param $opt_val
     * @return void
     */
    public static function rex_feed_save_char_limit_option( $opt_val ) {
        if( $opt_val ) {
            update_option( 'rex_feed_hide_character_limit_field', $opt_val );
            wp_send_json_success();
        }
        wp_send_json_error();
        wp_die();
    }

    /**
     * @desc Delete publish button id on page load
     * @since 7.2.18
     * @param $feed_id
     * @return void
     */
    public static function rex_feed_delete_publish_btn_id( $feed_id ) {
        if( $feed_id ) {
            delete_post_meta( $feed_id, '_rex_feed_publish_btn' );
            delete_post_meta( $feed_id, 'rex_feed_publish_btn' );
        }
        wp_send_json_success();
        wp_die();
    }


    /**
     * @desc Get the plugin global option status
     * for hiding character limit column
     * @since 7.2.18
     * @return void
     */
    public static function rex_feed_hide_char_limit_col() {
        wp_send_json( [ 'hide_char' => get_option( 'rex_feed_hide_character_limit_field', 'on' ) ] );
    }


    /**
     * @desc Get abandoned child list
     * and save them in database option table
     * @since 7.2.20
     * @return string[]
     */
    public static function rex_feed_update_abandoned_child_list() {
        $abandoned_childs = wpfm_get_abandoned_child();
        if( !is_wp_error( $abandoned_childs ) && !empty( $abandoned_childs ) ) {
            update_option( 'rex_feed_abandoned_child_list', $abandoned_childs );
        }
        if( is_wp_error( $abandoned_childs ) ) {
            return [ 'status' => 'error' ];
        }
        return [ 'status' => 'success' ];
    }
}