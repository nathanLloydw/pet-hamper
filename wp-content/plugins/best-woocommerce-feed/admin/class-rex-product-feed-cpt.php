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

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines all the Metaboxes for Products
 *
 * @package    Rex_Product_Metabox
 * @subpackage Rex_Product_Feed/admin
 * @author     RexTheme <info@rextheme.com>
 */
class Rex_Product_CPT {

    /**
     * Register all metaboxes.
     *
     * @since    1.0.0
     */
    public function register() {
        $this->post_types();
        add_filter('manage_product-feed_posts_columns' , array( $this, 'product_feed_custom_columns' ));
        add_action( 'manage_product-feed_posts_custom_column' , array($this,'fill_product_feed_columns'), 10, 2 );
    }

    /**
     * Metabox for Google Merchant.
     *
     * @since    1.0.0
     */
    private function post_types(){
        register_extended_post_type( 'product-feed', array(
            'show_in_menu'       => 'product-feed',
            'rewrite'            => false,
            'query_var'          => true,
            'publicly_queryable' => false,
            'supports'           => array( 'title' ),
            'enter_title_here'   => 'Enter feed title here',
            'menu_icon'           => WPFM_PLUGIN_ASSETS_FOLDER . 'icon/icon.png',
        ));
    }


    /**
     * register custom admin column
     * for product feed
     *
     * @param $columns
     * @return array
     * @since 6.1.2
     */
    public function product_feed_custom_columns( $columns ){
        return array(
            'cb' => '<input type="checkbox" />',
            'title' => __('Title'),
            'merchant' => __('Merchant', 'rex-product-feed'),
            'xml_feed' => __('Feed File', 'rex-product-feed'),
            'refresh_interval' =>__( 'Refresh Interval', 'rex-product-feed'),
            'feed_status' =>__( 'Status', 'rex-product-feed'),
            'view_feed' =>__( 'View/Download', 'rex-product-feed'),
            'total_products' =>__( 'Total Products', 'rex-product-feed'),
            'date' =>__( 'Date', 'rex-product-feed'),
            'scheduled' =>__( 'Updated', 'rex-product-feed'),
        );
    }


    /**
     * Fill contents for
     * custom products
     *
     * @param $column
     * @param $post_id
     * @since 6.1.2
     */
    public function fill_product_feed_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'merchant' :
                echo esc_html( ucwords( str_replace('_', ' ' , get_post_meta( $post_id, 'rex_feed_merchant', true )) ) );
                break;
            case 'xml_feed' :
                echo  esc_url( get_post_meta( $post_id , 'rex_feed_xml_file' , true ) ) ;
                break;
            case 'refresh_interval' :
                $schedule = get_post_meta( $post_id, 'rex_feed_schedule', true );
                $custom_time = $schedule === 'custom' ? get_post_meta( $post_id, 'rex_feed_custom_time', true ) . ':00' : '';
                $format      = get_option( 'time_format', 'g:i a' );

                echo esc_html( ucwords( $schedule ) );
                if( $schedule === 'custom' && $custom_time !== '' ) {
                    $time = date($format, strtotime($custom_time));
                    echo "<br>";
                    echo 'Daily at ' . esc_html( $time );
                }
                break;
            case 'feed_status' :
                if ( get_post_meta( $post_id, 'rex_feed_status', true ) ) {

                    if(get_post_meta( $post_id, 'rex_feed_status', true ) == 'processing') {
                    	?>
	                    <script>
                            (function($) {
	                            $(document).ready( function ( e ) {
	                                var post_id = '<?php echo esc_attr( $post_id ); ?>';
	                                var id      = '#post-' + post_id;
                                    $( id + ' .view_feed a' ).attr( 'disabled', 'disabled' );
                                    $( id + ' .view_feed a' ).css( 'pointer-events', 'none' );
                                } );
                            })(jQuery);
	                    </script>
						<?php
                        echo '<div class="blink">' . esc_html( ucwords( get_post_meta( $post_id, 'rex_feed_status', true ) ) ) . '<span>.</span><span>.</span><span>.</span></div>';
                    }else {
                        echo esc_html( ucwords( get_post_meta( $post_id, 'rex_feed_status', true ) ) );
                    }
                }else {
                    echo 'Completed';
                }
                break;
            case 'view_feed' :
                $url = esc_url( get_post_meta( $post_id, 'rex_feed_xml_file', true ) );
                echo '<a target="_blank" class="button" href="' . esc_url( $url ) . '">View</a> ';
                echo '<a target="_blank" class="button" href="' . esc_url( $url ) . '" download>Download</a>';
                break;
            case 'total_products' :
	            $total_products = get_post_meta( $post_id, 'rex_feed_total_products', true )
                    ? get_post_meta( $post_id, 'rex_feed_total_products', true ) : array(
		            'total'           => 0,
		            'simple'          => 0,
		            'variable'        => 0,
		            'variable_parent' => 0,
		            'group'           => 0,
	            );

	            if ( !array_key_exists( 'variable_parent', $total_products ) ) {
		            $total_products[ 'variable_parent' ] = 0;
	            }

	            $product_count = get_post_meta( $post_id, 'rex_feed_total_products_for_all_feed', true )
                    ? get_post_meta( $post_id, 'rex_feed_total_products_for_all_feed', true ) : $total_products[ 'total' ];
                $product_count = $product_count < $total_products[ 'total' ] ? $total_products[ 'total' ] : $product_count;

                echo '<ul style="margin: 0;">';
                echo '<li><b>' . esc_html__('Total products : ', 'rex-product-feed'). esc_html( $total_products[ 'total' ] ) . '/'. esc_html( $product_count ) .'</b></li>';
                echo '<li><b>' . esc_html__('Simple products : ', 'rex-product-feed'). esc_html( $total_products['simple'] ) . '</b></li>';
                echo '<li><b>' . esc_html__('Variable parent : ', 'rex-product-feed'). esc_html( $total_products['variable_parent'] ) . '</b></li>';
                echo '<li><b>' . esc_html__('Variations : ', 'rex-product-feed'). esc_html( $total_products['variable'] ) . '</b></li>';
                echo '<li><b>' . esc_html__('Group products : ', 'rex-product-feed'). esc_html( $total_products['group'] ) . '</b></li>';
                echo '</ul><b>';
                break;
            case 'scheduled' :
	            $format         = get_option( 'time_format', 'g:i a' ) . ', ' . get_option( 'date_format', 'F j, Y' );
	            $last_updated   = get_post_meta( $post_id, 'updated', true );
	            $formatted_time = '';

                if($last_updated) {
                    $formatted_time = date($format, strtotime($last_updated));
                }

                $schedule = get_post_meta( $post_id, 'rex_feed_schedule', true );
              
                echo '<div><strong>'.esc_html__('Last Updated: ', 'rex-product-feed').'</strong><span style="text-decoration: dotted underline;" title="'.esc_attr( $formatted_time ).'">'.esc_html($formatted_time).'</span></div></br>';

                $next_update = '';
                if($schedule === 'hourly') {
                    $next_update = date($format, strtotime('+1 hours', strtotime($last_updated)));
                }elseif ($schedule === 'daily' || $schedule === 'custom') {
                    $next_update = date($format, strtotime('+1 days', strtotime($last_updated)));
                }elseif ($schedule === 'weekly') {
                    $next_update = date($format, strtotime('+ 7 days', strtotime($last_updated)));
                }
                if($schedule !== 'no') {
                    echo '<div><strong>'.esc_html__('Next Schedule: ', 'rex-product-feed').'</strong><span style="text-decoration: dotted underline;" title="'.esc_attr($next_update).'">'.esc_html($next_update).'</span></div>';
                }
                break;
        }
    }
}
