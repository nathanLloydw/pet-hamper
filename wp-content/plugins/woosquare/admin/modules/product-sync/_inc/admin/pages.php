<?php

// don't load directly
if ( !defined('ABSPATH') )
	die('-1');

/**
 * Settings page action
 */
function square_settings_page() {
   
    // checkOrAddPluginTables();
    $square = new Square(get_option('woo_square_access_token'.get_transient('is_sandbox')), get_option('woo_square_location_id'.get_transient('is_sandbox')),WOOSQU_PLUS_APPID);

    $errorMessage = '';
    $successMessage = '';
    
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['terminate_sync'])) {
        
        //clear session variables if exists
        if (isset($_SESSION["square_to_woo"])){ unset($_SESSION["square_to_woo"]); };
        if (isset($_SESSION["woo_to_square"])){ unset($_SESSION["woo_to_square"]); };
        
        update_option('woo_square_running_sync', false);
        update_option('woo_square_running_sync_time', 0);

        $successMessage = 'Sync terminated successfully!';
    }
    
    // check if the location is not setuped
    if (get_option('woo_square_access_token'.get_transient('is_sandbox')) && !get_option('woo_square_location_id'.get_transient('is_sandbox'))) {
        $square->authorize();
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // setup account
		if (isset($_POST['woosquare_setting_nonce']) && !wp_verify_nonce($_POST['woosquare_setting_nonce'], 'woosquare_setting_nonce')){
			exit();
		}
        // save settings
        if (isset($_POST['woo_square_settings'])) {
			
			if(isset($_POST['sync_on_add_edit'])){
				update_option('sync_on_add_edit', sanitize_text_field(wp_unslash($_POST['sync_on_add_edit'])));
			}
            if(isset($_POST['disable_auto_delete'])){
				update_option('disable_auto_delete', sanitize_text_field($_POST['disable_auto_delete']));
			} else {
				update_option('disable_auto_delete', '');
			}
            
			if(!empty($_POST['woosquare_pro_edit_fields'])){
				update_option('woosquare_pro_edit_fields',  array_map( 'esc_attr', $_POST['woosquare_pro_edit_fields'] )) ;
			} else {
				update_option('woosquare_pro_edit_fields',  array()) ;
			}
            
            //update location id
            if( !empty($_POST['woo_square_location_id'.get_transient('is_sandbox')])){
                $location_id = sanitize_text_field(wp_unslash($_POST['woo_square_location_id'.get_transient('is_sandbox')]));
                update_option('woo_square_location_id'.get_transient('is_sandbox'), $location_id);               
                $square->setLocationId($location_id);
                $square->getCurrencyCode();
               
            }
			if(isset($_POST['html_sync_des'])){
				update_option('html_sync_des', sanitize_text_field(wp_unslash(@$_POST['html_sync_des'])));
			} else {
				update_option('html_sync_des', '');
			}
            $successMessage = 'Settings updated successfully!';
        }
    }
    $wooCurrencyCode    = get_option('woocommerce_currency');
    $squareCurrencyCode = get_option('woo_square_account_currency_code');
    
    if(!$squareCurrencyCode){
        $square->getCurrencyCode();
        $square->getapp_id();
        $squareCurrencyCode = get_option('woo_square_account_currency_code');
    }
    if ( $currencyMismatchFlag = ($wooCurrencyCode != $squareCurrencyCode) ){

    }
    include WOO_SQUARE_PLUGIN_PATH . 'views/settings.php';
}


/**
 * Logs page action
 * @global type $wpdb
 */
function logs_plugin_page(){
        
        checkOrAddPluginTables();       
        global $wpdb;
        
        $query = "
        SELECT log.id as log_id,log.action as log_action, log.date as log_date,log.sync_type as log_type,log.sync_direction as log_direction, children.*
        FROM ".$wpdb->prefix.WOO_SQUARE_TABLE_SYNC_LOGS." AS log
        LEFT JOIN ".$wpdb->prefix.WOO_SQUARE_TABLE_SYNC_LOGS." AS children
            ON ( log.id = children.parent_id )
        WHERE log.action = %d ";
              
        $parameters = [Helpers::ACTION_SYNC_START];
        
        //get the post params if sent or 'any' option was not chosen
        $sync_type = (isset($_POST['log_sync_type']) && strcmp($_POST['log_sync_type'],'any')) ?intval(sanitize_text_field($_POST['log_sync_type'])):null;
        $sync_direction = (isset($_POST['log_sync_direction']) && strcmp($_POST['log_sync_direction'],'any'))?intval(sanitize_text_field($_POST['log_sync_direction'])):null;
        $sync_date = isset($_POST['log_sync_date'])?
            (strcmp($_POST['log_sync_date'],'any')?intval(sanitize_text_field($_POST['log_sync_date'])):null):1;

        
        if (!is_null($sync_type)){
            $query.=" AND log.sync_type = %d ";
            $parameters[] = $sync_type; 
        }
        if (!is_null($sync_direction)){
           $query.=" AND log.sync_direction = %d ";
           $parameters[] = $sync_direction;  
        }
        if (!is_null($sync_date)){
           $query.=" AND log.date > %s ";
           $parameters[] = date("Y-m-d H:i:s", strtotime("-{$sync_date} days"));
        }
        
        
        $query.="
            ORDER BY log.id DESC,
                     id ASC";

        $sql =$wpdb->prepare($query, $parameters);
        $results = $wpdb->get_results($sql);
        $helper = new Helpers();
        
        include WOO_SQUARE_PLUGIN_PATH . 'views/logs.php';
       
}

