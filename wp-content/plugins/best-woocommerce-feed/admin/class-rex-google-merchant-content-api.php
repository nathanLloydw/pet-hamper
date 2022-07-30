<?php
use RexFeed\Google_Client;

class Rex_Google_Merchant_Settings_Api {

    static $client_id;

    static $client_secret;

    static $merchant_id;

    static $access_token;

    protected $client;

    protected static $_instance = null;

    public function __construct(){
        self::$client_id        = get_option('rex_google_client_id') ? get_option('rex_google_client_id') : '';
        self::$client_secret    = get_option('rex_google_client_secret') ? get_option('rex_google_client_secret') : '';
        self::$merchant_id      = get_option('rex_google_merchant_id') ? get_option('rex_google_merchant_id') : '';
    }

    /**
     * @return Rex_Google_Merchant_Settings_Api|null
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * @return Google_Client
     */
    public function init_client() {
        $redirect_uri = admin_url( 'admin.php?page=merchant_settings' );
        $this->client = self::get_client();
        $this->client->setClientId(self::$client_id);
        $this->client->setClientSecret(self::$client_secret);
        $this->client->setRedirectUri($redirect_uri);
        $this->client->setScopes( 'https://www.googleapis.com/auth/content' );
        return $this->client;
    }

    /**
     * @return \RexFeed\Google\Client
     */
    public static function get_client() {
        return new RexFeed\Google\Client();
    }

    /**
     * @return false|mixed|void
     */
    public function get_access_token() {
        return get_option('rex_google_access_token');
    }

    /**
     * @return bool
     */
    public function is_authenticate() {
        $access_token = get_option('rex_google_access_token');

        if($access_token == 'null') {
            return false;
        }

        if(!isset($access_token)) {
            return false;
        }

        if ( empty( $access_token ) ) {
            return false;
        }
        if(!$access_token) {
            return false;
        }
        $client = self::get_client();

        if(is_array($access_token)) {
            $client->setAccessToken($access_token);
        }else {
            $client->setAccessToken(json_decode($access_token, true));
        }

        if ( $client->isAccessTokenExpired() ) {
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public function get_access_token_html() {
        $client = self::get_client();
        $redirect_uri = admin_url( 'admin.php?page=merchant_settings' );
        $client->setClientId(self::$client_id);
        $client->setClientSecret(self::$client_secret);
        $client->setRedirectUri($redirect_uri);
        $client->setScopes( 'https://www.googleapis.com/auth/content' );
        $loginUrl = $client->createAuthurl();
        $btn_html = '<a class="btn-default" href="'.$loginUrl.'" target="_blank">'.__('Authenticate', 'rex-product-feed').'</a>';
        $html = '<div class="single-merchant-area authorized">
                <div class="single-merchant-block">
                    <header>
                        <h2 class="title">'. __("You are not authorized.", "rex-product-feed") .'</h2>
                        <img src="'. WPFM_PLUGIN_ASSETS_FOLDER . "/icon/danger.png" . '" class="title-icon" alt="bwf-documentation">
                    </header>
                    <div class="body">
                        <p>'.  __('Your access token has expired. This application uses OAuth 2.0 to Access Google APIs. Please insert the information below and authenticate token for Google Merchant Shop. Generated access token expires after 3600 sec.', 'rex-product-feed').'</p>
                        <p class="single-merchant-bold">'.  __('This session expiration is set by Google. You only need to authorize while submitting a new feed.', 'rex-product-feed').'</p>
                        <p class="single-merchant-bold">'.  __('You can ignore this if you\'ve already submitted your feed to Google.', 'rex-product-feed').'</p>
                        '.$btn_html.'
                    </div>
                </div>
            </div>';
        return $html;
    }

    /**
     * @return string
     */
    public function authorization_success_html() {
        return '<div id="card-alert" class="single-merchant-area authorized">
                  <div class="single-merchant-block">
                    <span class="card-title rex-card-title">'. __('You are authorized.', 'rex-product-feed') .'</span>
                    <p class="rex-p">'.  __('You are now ready to send feed from Product Feed Manager for WooCommerce to your Google Merchant Center. ', 'rex-product-feed').'🚀 </p>
                  </div>              
                </div>';
    }

    /**
     * @param $payload
     * @return string[]
     */
    public static function save_settings($payload) {

        if($payload['merchant_settings']) {
            update_option('rex_google_client_id', $payload['client_id']);
            update_option('rex_google_client_secret', $payload['client_secret']);
            update_option('rex_google_merchant_id', $payload['merchant_id']);
        }

        self::instance();
        $client = self::get_client();
        $redirect_uri = admin_url( 'admin.php?page=merchant_settings' );
        $client->setClientId(self::$client_id);
        $client->setClientSecret(self::$client_secret);
        $client->setRedirectUri($redirect_uri);
        $client->setScopes( 'https://www.googleapis.com/auth/content' );
        $loginUrl = $client->createAuthurl();
        $btn_html = '<a class="btn waves-effect waves-light" href="'.$loginUrl.'">Authenticate</a>';
        return array(
            'html'  => '<div class="col s12 merchant-action">
                    <div id="card-alert" class="card rex-card">
                        <div class="card-content">
                            <span class="card-title rex-card-title">'. __('You are not authorized.', 'rex-product-feed') .' <i class="fa fa-exclamation-triangle"></i></span>
                            <p class="rex-p">'.  __('Your access token has expired. This application uses OAuth 2.0 to Access Google APIs. Please insert the information below and authenticate token for Google Merchant Shop. Generated access token expires after 3600 sec.', 'rex-product-feed').'</p>
                        </div>
                        <div class="card-action">'.$btn_html.'</div>
                    </div>
                </div>',
        );
    }

    /**
     * @param $code
     */
    public function save_access_token($code) {
        $redirect_uri = admin_url( 'admin.php?page=merchant_settings' );
        $client = self::get_client();
        $client->setClientId(self::$client_id);
        $client->setClientSecret(self::$client_secret);
        $client->setRedirectUri($redirect_uri);
        $client->setScopes( 'https://www.googleapis.com/auth/content' );

        if ( !$this->is_authenticate() ) {
            $client->authenticate( $code );
            $access_token = $client->getAccessToken();
            if($access_token)
                update_option('rex_google_access_token', json_encode($access_token));
        }
    }

    /**
     * @param $feed_id
     * @return bool
     */
    public function feed_exists($feed_id) {
        $client = $this->init_client();
        $service = new RexFeed\Google\Service\ShoppingContent( $client );
        if(get_post_meta($feed_id, 'rex_feed_google_data_feed_id', true)) {
            try {
                $feed = $service->datafeeds->get(self::$merchant_id, get_post_meta($feed_id, 'rex_feed_google_data_feed_id', true));
                return true;
            }
            catch(Exception $e) {
                return false;
            }
        }
        return false;
    }
}