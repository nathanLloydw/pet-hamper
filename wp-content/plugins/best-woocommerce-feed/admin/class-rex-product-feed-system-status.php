<?php

class Rex_Feed_System_Status {
    /**
     * Get Status Page Info
     * @return array
     */
    public static function get_all_system_status(){
        $status = wpfm_get_cached_data( 'system_status' );
        if ( !$status ) {
            $status = [
                self::get_wpfm_version(), // Product Feed Manager for WooCommerce Version
                self::get_wpfm_pro_version(), // Product Feed Manager for WooCommerce - Pro Version
                self::get_woocommerce_version(), // WooCommerce Version
                self::get_available_product_types(), // WooCommerce Product Types
                self::get_total_wc_products(), // Total WooCommerce Product by Types
                self::get_wordpress_cron_status(), //WordPress Cron Status
                self::get_feed_file_directory(),
            ];
            wpfm_set_cached_data( 'system_status', $status );
        }
        return array_merge( $status, self::get_server_info() );
    }

    /**
     * @desc Get plugin info from wordpress.org
     *
     * @param $slug
     * @return false|mixed
     */
    private static function get_plugin_info( $slug ) {

        if ( empty( $slug ) ) {
            return false;
        }

        $args = (object) array(
            'slug'   => $slug,
            'fields' => array(
                'sections'    => false,
                'screenshots' => false,
                'versions'    => false,
            ),
        );
        $request = array(
            'action'  => 'plugin_information',
            'request' => serialize( $args), //phpcs:ignore
        );
        $url = 'http://api.wordpress.org/plugins/info/1.0/';
        $response = wp_remote_post( $url, array( 'body' => $request ) );

        if ( is_wp_error($response) ) {
            return false;
        }
        return unserialize( $response['body']); //phpcs:ignore
    }

    /**
     * @desc Get Product Feed Manager for WooCommerce Version Status.
     * @return array|false
     */
    private static function get_wpfm_version( ) {
        $status = 'error';
        if ( defined( 'WPFM_VERSION' ) ) {
            $installed_version = WPFM_VERSION;
            $latest_version = self::get_plugin_info('best-woocommerce-feed' );

            if ( version_compare( $latest_version->version, $installed_version,'>' ) ) {
                $message = $installed_version . " - You are not using the latest version of Product Feed Manager for WooCommerce. Update Product Feed Manager for WooCommerce plugin to its latest version: " . $latest_version->version;
            }else {
                $message = $installed_version . " - You are using the latest version of Product Feed Manager for WooCommerce.";
                $status = 'success';
            }

            return [
                'label'   => 'Product Feed Manager for WooCommerce Version',
                'message' => $message,
                'status' => $status
            ];
        }
        return false;
    }

    /**
     * @desc Get latest version of WPFM Pro with EDD API
     * @return mixed|void
     */
    private static function get_wpfm_pro_latest_version() {
        $license = trim(get_option('wpfm_pro_license_key'));
        // data to send in our API request
        $api_params = array(
            'edd_action' => 'get_version',
            'license' => $license,
            'item_id' => WPFM_SL_ITEM_ID, // The ID of the item in EDD
            'url' => home_url()
        );
        $params = '';
        foreach ($api_params as $key => $value) {
            $params .= $key . '=' . $value . '&';
        }
        $params = trim($params, '&');

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, WPFM_SL_STORE_URL . '?' . $params); //Url together with parameters
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //Return data instead printing directly in Browser
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7); //Timeout after 7 seconds
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 8.0; Windows NT 6.1)");
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $message = __( 'An error occurred, please try again.');
        } else {
            return json_decode( $response );
        }
    }

    /**
     * @desc Get Product Feed Manager for WooCommerce - Pro Version Status.
     * @return array|false
     */
    private static function get_wpfm_pro_version( ) {
        $status = 'error';
        if ( defined( 'REX_PRODUCT_FEED_PRO_VERSION' ) ) {
            $installed_version = defined( 'REX_PRODUCT_FEED_PRO_VERSION' ) ? REX_PRODUCT_FEED_PRO_VERSION : '1.0.0';
            $latest_version = self::get_wpfm_pro_latest_version();

            if ( isset( $latest_version->stable_version ) && version_compare( $latest_version->stable_version, $installed_version,'>' ) ) {
                $message = $installed_version . " - You are not using the latest version of Product Feed Manager for WooCommerce - Pro. Update Product Feed Manager for WooCommerce - Pro plugin to its latest version: " . $latest_version->stable_version;
            } elseif( isset( $latest_version->stable_version ) && version_compare( $latest_version->stable_version, $installed_version,'==' ) ) {
                $message = $installed_version . " - You are using the latest version of Product Feed Manager for WooCommerce - Pro.";
                $status = 'success';
            } else {
                $message = $installed_version;
                $status = 'success';
            }

            return [
                'label'   => 'Product Feed Manager for WooCommerce - Pro Version',
                'message' => $message,
                'status' => $status
            ];
        }
        return false;
    }

    /**
     * @desc Get WooCommerce Version Status.
     * @return array
     */
    private static function get_woocommerce_version( ) {
        $status = 'error';
        $installed_version = ( function_exists('WC' ) ) ? WC()->version : '1.0.0';
        $latest_version = self::get_plugin_info('woocommerce' );

        if ( version_compare($latest_version->version,$installed_version,'>') ) {
            $message = $installed_version." - You are not using the latest version of WooCommerce. Update WooCommerce plugin to its latest version: ".$latest_version->version;
        } else {
            $message = $installed_version." - You are using the latest version of WooCommerce.";
            $status = 'success';
        }

        return [
            'label'   => 'WooCommerce Version',
            'message' => $message,
            'success' => $status
        ];
    }

    /**
     * @desc Gets wordpress cron status
     * @return array
     */
    private static function get_wordpress_cron_status() {
        $message = 'Enabled';
        $status = 'success';
        if ( defined('DISABLE_WP_CRON') && true === DISABLE_WP_CRON ) {
            $message = "WordPress cron is disabled. The <b>Auto Feed Update</b> will not run if WordPress cron is Disabled.";
            $status = 'error';
        }

        return [
            'label'   => 'WP CRON',
            'message' => $message,
            'status' => $status
        ];
    }

    /**
     * @desc Get Server Info
     * @return array
     */
    private static function get_server_info( ) {
        $report             = wc()->api->get_endpoint_data( '/wc/v3/system_status' );
        $environment        = $report['environment'];
        $theme              = $report['theme'];
        $active_plugins     = $report['active_plugins'];
        $info = array();

        if ( ! empty($environment) ) {
            foreach ( $environment as $key => $value ) {

                if ( true === $value ) {
                    $value = 'Yes';
                }elseif ( false === $value ) {
                    $value = 'No';
                }

                if ( in_array($key,[ 'wp_memory_limit', 'php_post_max_size', 'php_max_input_vars', 'max_upload_size' ]) ) {
                    $value = self::get_formated_bytes( $value );
                }

                $info[] = [
                    'label'   => ucwords(str_replace([ '_', 'wp' ],[ ' ', 'WP' ],$key)),
                    'message' => $value,
                ];
            }
        }

        if ( ! empty($theme) ) {
            $new_version = "";
            if ( version_compare($theme['version'],$theme['version_latest']) ) {
                $new_version = ' (Latest:'.$theme['version_latest'].')';
            }

            $info[] = [
                'label'   => 'Installed Theme',
                'message' => $theme['name'] . ' v' . $theme['version'] . $new_version,
            ];
        }

        $info[] = [
            'label'   => '',
            'status'  => '',
            'message' => "<h3>Installed Plugins</h3>",
        ];

        if ( ! empty($active_plugins) ) {
            foreach ( $active_plugins as $key => $plugin ) {
                $new_version = "";
                if ( version_compare($plugin['version'],$plugin['version_latest']) ) {
                    $new_version = ' (Latest:'.$plugin['version_latest'].')';
                }

                $info[] = [
                    'label'   => $plugin['name']. ' ('.$plugin['author_name'].')',
                    'message' => $plugin['version'].$new_version,
                ];
            }
        }
        return $info;
    }

    /**
     * @desc Get Formatted bytes
     * @param $bytes
     * @param $precision
     * @return string
     */
    private static function get_formated_bytes( $bytes, $precision = 2 ) {
        $units = array( 'B', 'KB', 'MB', 'GB', 'TB' );

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        // Uncomment one of the following alternatives
        $bytes /= pow(1024, $pow);
        // $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision) . ' ' . $units[ $pow ];
    }

    private static function get_feed_file_directory() {
        $path = wp_upload_dir();
        $path = $path['basedir'] . '/rex-feed';
        if ( is_writable( $path ) ) {
            $is_writable = "True";
        } else {
            $is_writable = "False";
        }

        return [
            'label' => 'Product Feed Directory',
            'message' => $path,
            'is_writable' => $is_writable
        ];
    }

    /**
     * @desc Get system status as texts/strings.
     * @return void
     */
    public static function get_system_status_text() {
        $system_status = self::get_all_system_status();
        $texts = '';
        $index = 1;
        foreach ( $system_status as $status ) {
            if ( isset( $status[ 'label' ] ) && $status[ 'label' ] !== '' && isset( $status[ 'message' ] ) && $status[ 'message' ] !== '' ) {
                $texts .= '#' . $index++ . ' ' . $status[ 'label' ] . ': ' . $status[ 'message' ] . "\n\n";
            }
        }
        return $texts;
    }

    /**
     * @desc Get available woocommerce product types
     * @return array
     */
    private static function get_available_product_types(){
        $types = wc_get_product_types();
        $status = 'success';
        $message = '';

        if ( ! empty($types) ) {
            foreach ( $types as $key => $type ) {
                $message .= '✰ '. ucwords($type) . ' [' . $key . '] <br/>';
            }
        }
        return [
            'label'   => 'Product Types',
            'status'  => $status,
            'message' => $message,
        ];
    }

    /**
     * @desc Get WooCommerce Total Products.
     * @return array
     */
    private static function get_total_wc_products( ) {
        $status = 'success';
        $message = '';

        // Product Totals by Product Type (WP Query)
        $type_totals = self::get_product_total_by_type();
        if ( ! empty($type_totals) ) {
            foreach ( $type_totals as $type => $total ) {
                $message .= "✰ ". ucwords($type)." Product: ".$total."<br/>";
            }
        }

        // Total Product Variations (WP Query)
        $total_variations = self::get_total_product_variation();
        if ( $total_variations ) {
            $message .= "✰ Product Variations: ".$total_variations."<br/>";
        }

        return [
            'label'   => 'Total Products by Types',
            'status'  => $status,
            'message' => $message,
        ];
    }

    /**
     * Count products by type.
     * @return array
     */
    private static function get_product_total_by_type( ) {
        $product_types = get_terms( 'product_type');
        $product_count = [];
        $args = array(
            'posts_per_page'         => - 1,
            'post_type'              => 'product',
            'post_status'            => 'publish',
            'order'                  => 'DESC',
            'fields'                 => 'ids',
            'cache_results'          => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'suppress_filters'       => false,
        );
        if ( ! empty($product_types) ) {
            foreach ( $product_types as $product_type ) {
                $args['tax_query']  = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                    array(
                        'taxonomy' => 'product_type',
                        'field'    => 'name',
                        'terms'    => $product_type->name,
                    ),
                );
                $product_count[ $product_type->name ] = (new WP_Query($args))->post_count;
            }
        }

        return $product_count;
    }

    /**
     * Count total product variations.
     * @return int
     */
    private static function get_total_product_variation(){
        $args = array(
            'posts_per_page'         => - 1,
            'post_type'              => 'product_variation',
            'post_status'            => 'publish',
            'order'                  => 'DESC',
            'fields'                 => 'ids',
            'cache_results'          => false,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
            'suppress_filters'       => false,
        );

        return ( new WP_Query( $args ) )->post_count;
    }
}