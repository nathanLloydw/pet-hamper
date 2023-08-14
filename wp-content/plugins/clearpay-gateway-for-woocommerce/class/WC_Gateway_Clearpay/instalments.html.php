<?php
/**
 * Clearpay Checkout Instalments Display
 * @var WC_Gateway_Clearpay $this
 */

if ($this->settings['testmode'] != 'production') {
    ?><p class="clearpay-test-mode-warning-text"><?php _e( 'TEST MODE ENABLED', 'woo_clearpay' ); ?></p><?php
}

if ($this->get_country_code() != 'GB') {
?>
    <div class="instalment-info-container" id="clearpay-checkout-instalment-info-container">
        <p class="header-text">
            <?php
                $numberInstalmentsText = $currency === 'EUR' ? __('Three', 'woo_clearpay') : __('Four', 'woo_clearpay');
                $totallingText = __( '%s interest-free payments totalling', 'woo_clearpay' );
                printf($totallingText, $numberInstalmentsText);
            ?>
            <strong><?php echo wc_price($order_total); ?></strong>
        </p>
        <div class="instalment-wrapper">
            <afterpay-price-table
                data-amount="<?php echo esc_attr($order_total); ?>"
                data-locale="<?php echo esc_attr($this->get_js_locale()); ?>"
                data-currency="<?php echo esc_attr($currency); ?>"
                data-price-table-theme="white"
            ></afterpay-price-table>
        </div>
    </div>
<?php
    wp_enqueue_script('clearpay_js_lib');
} else {
?>
    <div
        id="afterpay-widget-container"
        data-locale="<?php echo esc_attr($locale); ?>"
        data-amount="<?php echo esc_attr($order_total); ?>"
        data-currency="<?php echo esc_attr($currency); ?>">
    </div>
<?php
}
