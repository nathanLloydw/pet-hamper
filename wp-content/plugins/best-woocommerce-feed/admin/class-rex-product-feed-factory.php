<?php
/**
 * The Rex_Product_Feed_Factory class file that
 * returns a feed generator object based on selected merchant.
 *
 * @link       https://rextheme.com
 * @since      1.0.0
 *
 * @package    Rex_Product_Feed_Factory
 * @subpackage Rex_Product_Feed_Factory/includes
 */
class Rex_Product_Feed_Factory {

    private static $other_merchants;
    private static $google_format;
    private static $facebook_format;
    private static $mirakl_format;
    private static $bestprice_format;
    private static $DealsForU;
    private static $spartooFr;

    public static function build( $config, $bypass = false , $product_ids = array()){
        
        $log = wc_get_logger();
        self::$other_merchants = apply_filters('wpfm_merchant_custom',
            array(
                'adform',
                'adcrowd',
                'beslist',
                'cdiscount',
                'custom',
                'kieskeurig',
                'kleding',
                'ladenzeile',
                'skroutz',
                'winesearcher',
                'whiskymarketplace',
                'trovaprezzi',
                'nextag',
                'nextag',
                'pricegrabber',
                'bing',
                'cercavino',
                'kelkoo',
                'ebay',
                'become' ,
                'shopzilla',
                'google_Ad',
                'adroll',
                'admarkt',
                'pricerunner',
                'billiger',
                'vergelijk',
                'twenga',
                'tweakers',
                'koopkeus',
                'scoupz',
                'kelkoonl',
                'uvinum',
                'pricesearcher',
                'pricemasher',
                'google_dsa',
                'fashionchick',
                'choozen',
                'prisjkat',
                'crowdfox',
                'powerreviews',
                'otto',
                'sears',
                'ammoseek',
                'fnac',
                'pixmania',
                'coolblue',
                'shopmania',
                'preis',
                'walmart',
                'verizon',
                'kelkoo_group',
                'target',
                'pepperjam',
                'cj_affiliate',
                'guenstiger',
                'hood',
                'livingo',
                'jet',
                'bonanza',
                'adcell',
                'stylefruits',
                'medizinfuchs',
                'moebel',
                'restposten',
                'sparmedo',
                'newegg',
                '123i',
                'bikeexchange',
                'cenowarka',
                'cezigue',
                'check24',
                'clang',
                'cherchons',
                'boetiek',
                'comparer',
                'converto',
                'coolshop',
                'commerce_connector',
                'everysize',
                'encuentraprecios',
                'geizhals',
                'geizkragen',
                'giftboxx',
                'go_banana',
                'goed_geplaatst',
                'grosshandel',
                'hardware',
                'hatch',
                'hintaopas',
                'fyndiq',
                'fasha',
                'realde',
                'hintaseuranta',
                'family_blend',
                'hitmeister',
                'lazada',
                'get_price',
                'home_tiger',
                'jurkjes',
                'kiesproduct',
                'kiyoh',
                'kompario',
                'kwanko',
                'ledenicheur',
                'les_bonnes_bouilles',
                'lions_home',
                'locamo',
                'logicsale',
                'pronto',
                'awin',
                'google_dynamic_display_ads',
                'indeed',
                'incurvy',
                'jobbird',
                'job_board_io',
                'joblift',
                'kuantokusta',
                'kauftipp',
                'rakuten_advertising',
                'pricefalls',
                'google_hotel_ads',
                'facebook_dynamic_ads_travel',
                'clubic',
                'shopalike',
                'adtraction',
                'bloomville',
                'bipp',
                'datatrics',
                'deltaprojects',
                'drezzy',
                'domodi',
                'homebook',
                'homedeco',
                'imovelweb',
                'onbuy',
                'fashiola',
                'emag',
                'lyst',
                'listupp',
                'hertie',
                'pricepanda',
                'eytsy',
                'okazii',
                'webgains',
                'vidaXL',
                'mydeal',
                'trovino',
                'bol',
                'leguide',
                'connexity',
                'drm',
            )
        );
        self::$google_format = array(
            'google',
            'ciao',
            'liveintent',
            'google_shopping_actions',
            'google_merchant_promotion',
            'google_express',
            'criteo',
            'compartner',
            'doofinder',
            'emarts',
            'epoq',
            'google_local_inventory_ads',
            'google_manufacturer_center',
            'bing_image',
            'rss',
        );
        self::$facebook_format = array(
            'instagram',
            'facebook',
            'snapchat'
        );
        self::$bestprice_format = array(
            'Bestprice'
        );
        self::$mirakl_format = array(
            'mirakl'
        );
        self::$DealsForU = array(
            'DealsForU'
        );
        self::$spartooFr = array(
            'spartooFr'
        );

        if ( in_array( $config['merchant'], self::$other_merchants ) ) {
            $className = 'Rex_Product_Feed_Other';
        }
        elseif (in_array( $config['merchant'], self::$google_format )) {
            $className = 'Rex_Product_Feed_Google';
        }
        elseif (in_array( $config['merchant'], self::$facebook_format )) {
            $className = 'Rex_Product_Feed_Facebook';
        }
        elseif (in_array( $config['merchant'], self::$mirakl_format )) {
            $className = 'Rex_Product_Feed_Mirakl';
        }
        elseif (in_array( $config['merchant'], self::$DealsForU )) {
            $className = 'Rex_Product_Feed_DealsForU';
        }
        elseif (in_array( $config['merchant'], self::$bestprice_format )) {
            $className = 'Rex_Product_Feed_Bestprice';
        }
        elseif (in_array( $config['merchant'], self::$spartooFr )) {
            $className = 'Rex_Product_Feed_SpartooFr';
        }
        elseif ( $config['merchant'] === 'admitad' || $config['merchant'] === 'ibud' ) {
            $className = 'Rex_Product_Feed_Yandex';
        }
        elseif ($config['merchant'] === 'pinterest') {
            $className = 'Rex_Product_Feed_Pinterest';
        }
        elseif ($config['merchant'] === 'gulog_gratis') {
            $className = 'Rex_Product_Feed_Gulog_gratis';
        }
        else{
            $className = 'Rex_Product_Feed_'. ucfirst( str_replace(' ', '', $config['merchant'] ) );
        }

        if( $config == '' || ! class_exists( $className ) ) {
            if(is_wpfm_logging_enabled()) {
                $log->critical(__( 'Invalid Merchant.', 'rex-product-feed' ), array('source' => 'WPFM-Critical'));
            }
            throw new Exception('Invalid Merchant.');
        } else {
            return new $className( $config, $bypass, $product_ids );
        }


        return false;
    }
}