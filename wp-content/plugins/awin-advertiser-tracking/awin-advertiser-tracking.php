<?php

/**
 * Plugin Name: Awin - Advertiser Tracking
 * Plugin URI: https://wordpress.org/plugins/awin-advertiser-tracking
 * Description: The Awin Advertiser Tracking plugin allows for seamless integration of our core Advertiser Tracking Suite within WooCommerce.
 * Version: 1.1.1
 * Author: awinglobal
 * Author URI: https://profiles.wordpress.org/awinglobal/
 * Text Domain:  awin-advertiser-tracking
 * Domain Path: /languages
 *
 * Copyright: © 2019 AWIN LTD.
 * License: ModifiedBSD
 */

define('AWIN_ADVERTISER_TRACKING_VERSION', '1.1.1');
define('AWIN_SLUG', 'awin_advertiser_tracking');
define('AWIN_TEXT_DOMAIN', 'awin-advertiser-tracking');
define('AWIN_SETTINGS_KEY', 'awin_settings');
define('AWIN_SETTINGS_ADVERTISER_ID_KEY', 'awin_advertiser_id');
define('AWIN_SOURCE_COOKIE_NAME', 'source');
define('AWIN_AWC_COOKIE_NAME', 'adv_awc');

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    add_action('admin_menu', 'awin_add_admin_menu');
    add_action('admin_init', 'awin_settings_init');
    add_action('wp_enqueue_scripts', 'awin_enqueue_journey_tag_script');
    add_action('init', 'awin_process_url_params');
    add_action('woocommerce_thankyou', 'awin_thank_you', 10);
    add_action("plugins_loaded", "awin_load_textdomain");

    add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'awin_add_plugin_page_settings_link');


    register_deactivation_hook(__FILE__, 'awin_deactivate_plugin');

    function awin_add_plugin_page_settings_link($links)
    {
        $links[] = '<a href="' . admin_url('options-general.php?page=' . AWIN_SLUG) . '">' . __('Settings') . '</a>';
        return $links;
    }

    function awin_load_textdomain()
    {
        load_plugin_textdomain(
            AWIN_TEXT_DOMAIN,
            false,
            basename(dirname(__FILE__)) . '/lang/'
        );
    }

    function awin_add_admin_menu()
    {
        add_options_page(__('Awin Advertiser Tracking', AWIN_TEXT_DOMAIN), __('Awin Advertiser Tracking', AWIN_TEXT_DOMAIN), 'manage_options', AWIN_SLUG, 'awin_render_options_page');
    }

    function awin_settings_init()
    {
        register_setting('awin-plugin-page', AWIN_SETTINGS_KEY);

        add_settings_section(
            'awin_plugin-page_section',
            __('Tracking settings', AWIN_TEXT_DOMAIN),
            'awin_settings_section_callback',
            'awin-plugin-page'
        );

        add_settings_field(
            'awin_advertiser_id',
            __('Advertiser ID', AWIN_TEXT_DOMAIN),
            'awin_advertiser_id_render',
            'awin-plugin-page',
            'awin_plugin-page_section'
        );
    }

    function awin_advertiser_id_render()
    {
        $options = get_option(AWIN_SETTINGS_KEY);
?>
        <?php wp_nonce_field('awin-plugin-page-action', 'awin_settings[' . AWIN_SETTINGS_ADVERTISER_ID_KEY . '-check]'); ?>
        <input type='number' name='awin_settings[<?= AWIN_SETTINGS_ADVERTISER_ID_KEY ?>]' value='<?php echo sanitize_text_field($options[AWIN_SETTINGS_ADVERTISER_ID_KEY]); ?>' required oninput='this.setCustomValidity("")' oninvalid="this.setCustomValidity('<?= __('You can\\\'t go to the next step until you enter your advertiser ID.', AWIN_TEXT_DOMAIN) ?>')">
        <?php
    }

    function awin_settings_section_callback()
    {
        echo __('<p>By entering your advertiser ID and click <b>Save Changes</b>, your WooCommerce store will be automatically set up for Awin Tracking.</p><p>If you don\'t have an advertiser ID please sign up to the Awin network first to receive your advertiser ID, via <a href="https://www.awin.com" target="_blank">www.awin.com</a>.</p>', AWIN_TEXT_DOMAIN);
    }

    function awin_render_options_page()
    {
        if (current_user_can('manage_options')) {
            $isPost = !empty($_POST);
            if ($isPost && (!isset($_POST['awin_settings[' . AWIN_SETTINGS_ADVERTISER_ID_KEY . '-check]']) || !wp_verify_nonce($_POST['awin_settings[' . AWIN_SETTINGS_ADVERTISER_ID_KEY . '-check]'], 'awin-plugin-page-action'))) {
                print 'Sorry, your nonce did not verify.';
                exit;
            } else {
        ?>
                <div class="wrap">
                    <h1><?= __('Awin Advertiser Tracking', AWIN_TEXT_DOMAIN) ?></h1>

                    <form action='options.php' method='post'>
                        <?php
                        settings_fields('awin-plugin-page');
                        do_settings_sections('awin-plugin-page');
                        submit_button(__('Save Changes', AWIN_TEXT_DOMAIN));
                        ?>

                    </form>
                </div>
<?php
            }
        }
    }

    function awin_enqueue_journey_tag_script()
    {
        if (!is_admin() && !is_checkout()) {
            $advertiserId = awin_get_advertiser_id_from_settings();
            if ($advertiserId > 0) {
                wp_enqueue_script('awin-journey-tag', 'https://www.dwin1.com/' . $advertiserId . '.js', array(), AWIN_ADVERTISER_TRACKING_VERSION, true);
            }
        }
    }

    function awin_process_url_params()
    {
        $urlparts = parse_url(home_url());
        $domain = $urlparts['host'];

        if (isset($_GET["awc"])) {
            // store awc from url if possible
            $sanitized_awc = sanitize_key($_GET["awc"]);
            if (strlen($sanitized_awc) > 0) {
                setcookie(AWIN_AWC_COOKIE_NAME, $sanitized_awc, time() + (86400 * 30), COOKIEPATH, $domain, is_ssl(), true);
            }
        }

        if (isset($_GET[AWIN_SOURCE_COOKIE_NAME])) {
            // store source from url if possible
            $sanitized_cookie_name = sanitize_key($_GET[AWIN_SOURCE_COOKIE_NAME]);
            if (strlen($sanitized_cookie_name) > 0) {
                setcookie(AWIN_SOURCE_COOKIE_NAME, $sanitized_cookie_name, time() + (86400 * 30), COOKIEPATH, $domain, is_ssl(), true);
            }
        }
    }

    function awin_get_advertiser_id_from_settings()
    {
        $options = get_option(AWIN_SETTINGS_KEY);
        return $options[AWIN_SETTINGS_ADVERTISER_ID_KEY];
    }


    function awin_thank_you($order_id)
    {
        $advertiserId = awin_get_advertiser_id_from_settings();

        if (strlen($order_id)  > 0  && strlen($advertiserId) > 0) {
            // the order
            $order = wc_get_order($order_id);
            $voucher = '';
            $coupons = $order->get_used_coupons();

            if (count($coupons) > 0) {
                $voucher = $coupons[0];
            }

            // Getting an instance of the order object
            $order_number = $order->get_order_number();
            $currency = $order->get_currency();
            $NUMBER_DECIMALS = 2;
            $totalPrice = number_format((float) $order->get_total() - $order->get_total_tax() - $order->get_total_shipping(), $NUMBER_DECIMALS, '.', '');
            $source = $_COOKIE[AWIN_SOURCE_COOKIE_NAME];
            $channel = strlen($source) > 0 ? $source : 'aw';

            $imgUrl = 'https://www.awin1.com/sread.img?tt=ns&tv=2&merchant=' . $advertiserId . '&amount=' . $totalPrice . '&ch=' . $channel . '&cr=' . $currency . '&ref=' . $order_number . '&parts=DEFAULT:' . $totalPrice . '&p1=wooCommercePlugin_' . AWIN_ADVERTISER_TRACKING_VERSION;
            if (strlen($voucher) > 0) {
                $imgUrl .= '&vc=' . $voucher;
            }
            echo '<img src="' . $imgUrl . '" border="0" height="0" width="0" style="display: none;">';
            echo '<form style="display: none;" name="aw_basket_form">' . "\n";
            echo '<textarea wrap="physical" id="aw_basket">' . "\n";
            $items = '';
            foreach ($order->get_items() as $item_id => $item) {
                $product = $item->get_product();
                $singlePrice = number_format(((float)$item['total'] / (float)$item['quantity']), $NUMBER_DECIMALS, '.', '');
                echo "\n" . "AW:P|{$advertiserId}|{$order->get_order_number()}|{$item['product_id']}|{$item['name']}|{$singlePrice}|{$item['quantity']}|{$product->get_sku()}|DEFAULT|";
            }
            echo "\n" . '</textarea>';
            echo "\n" . '</form>';

            $masterTag = '//<![CDATA[' . "\n";
            $masterTag .= 'var AWIN = {};' . "\n";
            $masterTag .= 'AWIN.Tracking = {};' . "\n";
            $masterTag .= 'AWIN.Tracking.Sale = {};' . "\n";
            $masterTag .= 'AWIN.Tracking.Sale.test = 0;' . "\n";
            $masterTag .= 'AWIN.Tracking.Sale.amount = "' . $totalPrice . '";' . "\n";
            $masterTag .= 'AWIN.Tracking.Sale.channel = "' . $channel . '";' . "\n";
            $masterTag .= 'AWIN.Tracking.Sale.currency = "' . $currency . '";' . "\n";
            $masterTag .= 'AWIN.Tracking.Sale.orderRef = "' . $order_number . '";' . "\n";
            $masterTag .= 'AWIN.Tracking.Sale.parts = "DEFAULT:' . $totalPrice . '";' . "\n";
            $masterTag .= 'AWIN.Tracking.Sale.voucher = "' . $voucher . '";' . "\n";
            $masterTag .= 'AWIN.Tracking.Sale.custom = ["wooCommercePlugin_' . AWIN_ADVERTISER_TRACKING_VERSION . '"];' . "\n";
            $masterTag .= '//]]>' . "\n";

            // register and add variables
            wp_register_script('awin-mastertag-params', '');
            wp_enqueue_script('awin-mastertag-params');
            wp_add_inline_script('awin-mastertag-params', $masterTag);

            // add dwin1 script tag after variables
            wp_enqueue_script('awin-mastertag', 'https://www.dwin1.com/' . $advertiserId . '.js', array('awin-mastertag-params'), AWIN_ADVERTISER_TRACKING_VERSION, true);

            // s2s
            awin_perform_server_to_server_call($_COOKIE[AWIN_AWC_COOKIE_NAME], $_COOKIE[AWIN_SOURCE_COOKIE_NAME], $order, $advertiserId, $voucher);
        }
    }

    function awin_deactivate_plugin()
    {
        delete_option('awin_settings');
    }

    function awin_perform_server_to_server_call($awc, $source, $order, $advertiserId, $voucher)
    {
        if (strlen($awc) == 0) {
            $awc = "";
        }
        $totalPrice = number_format((float) $order->get_total() - $order->get_total_tax() - $order->get_total_shipping(), 2, '.', '');

        $channel = strlen($source) > 0 ? $source : 'aw';

        $query = array(
            "tt" => "ss",
            "tv" => "2",
            "ch" => $channel,
            "cks" => $awc,
            "merchant" => $advertiserId,
            "cr" => $order->get_currency(),
            "amount" => $totalPrice,
            "parts" => "DEFAULT:" . $totalPrice,
            "ref" => $order->get_order_number(),
            "p1" => "wooCommercePlugin_" . AWIN_ADVERTISER_TRACKING_VERSION
        );

        if (strlen($voucher) > 0) {
            $query["vc"] = $voucher;
        }

        wp_remote_get("https://www.awin1.com/sread.php?" . http_build_query($query));
    }
}
