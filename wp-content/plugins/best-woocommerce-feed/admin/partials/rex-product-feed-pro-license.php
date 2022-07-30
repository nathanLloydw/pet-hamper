<?php
$license = get_option( 'wpfm_pro_license_key' );
$status  = get_option( 'wpfm_pro_license_status' );
$license_data  = get_option( 'wpfm_pro_license_data', '');

if ( class_exists( 'Rex_Product_Feed_Pro_License_Security' ) ) {
    $license = (new Rex_Product_Feed_Pro_License_Security())->encrypt( $license );
}
?>

<div class="rex-licenes">
    <div class="rex-licenes__general-area">
        <div class="rex-licenes__general-wrap-area">

            <div class="rex-licenes__top-area">
                <div class="rex-licenes__licensekey-area">

                    <div class="rex-licenes__text-area">
                        <div class="rex-licenes__single-block">
                            <span class="rex-licenes__icon">
                                <svg width="14px" height="18px" viewBox="0 0 14 18" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round">
                                        <g id="Report--Copy" transform="translate(-1012.000000, -30.000000)" stroke="#1fb3fb" stroke-width="1.5">
                                            <g id="Group-6" transform="translate(279.000000, 31.000000)">
                                                <g id="1-copy-6" transform="translate(535.000000, 0.000000)">
                                                    <g id="Group-9" transform="translate(199.000000, 0.000000)">
                                                        <polyline id="Stroke-1" points="9 6 12 3 9 0"></polyline>
                                                        <path d="M0,9 L0,5.4725824 C0,4.10699022 1.04467307,3 2.3333903,3 L12,3" id="Stroke-3"></path>
                                                        <polyline id="Stroke-5" points="3 10 0 13.0002224 3 16"></polyline>
                                                        <path d="M12,7 L12,10.5274176 C12,11.8930098 10.9553269,13 9.66701986,13 L0,13" id="Stroke-7"></path>
                                                    </g>
                                                </g>
                                            </g>
                                        </g>
                                    </g>
                                </svg>
                            </span>
                            <!-- rex-licenes__icon -->

                            <h4><?php echo esc_html__('Stay Updated', 'rex-product-feed-pro'); ?></h4>
                            <p><?php echo esc_html__('Update the plugin right from your WordPress Dashboard.', 'rex-product-feed-pro'); ?></p>
                        </div>
                        <!-- rex-licenes__single-block -->

                        <div class="rex-licenes__single-block">
                            <span class="rex-licenes__icon">
                                <svg width="18px" height="17px" viewBox="0 0 18 17" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                                    <g id="Page-1" stroke="none" stroke-width="1" fill="none" fill-rule="evenodd" stroke-linecap="round" stroke-linejoin="round">
                                        <g id="Report--Copy" transform="translate(-972.000000, -31.000000)" stroke="#1fb3fb" stroke-width="1.5">
                                            <g id="Group-6" transform="translate(279.000000, 31.000000)">
                                                <g id="1-copy-6" transform="translate(535.000000, 0.000000)">
                                                    <polygon id="Stroke-1" points="167 13.1256696 171.635405 15.688 170.75 10.2610156 174.5 6.41730801 169.317703 5.62566961 167 0.688 164.682297 5.62566961 159.5 6.41730801 163.25 10.2610156 162.364595 15.688"></polygon>
                                                </g>
                                            </g>
                                        </g>
                                    </g>
                                </svg>
                            </span>
                            <!-- rex-licenes__icon -->
                            <h4><?php echo esc_html__('Premium Support', 'rex-product-feed-pro'); ?></h4>
                            <p><?php echo esc_html__('Supported by professional and courteous staff.', 'rex-product-feed-pro'); ?></p>
                        </div>
                        <!-- rex-licenes__single-block -->
                    </div>
                    <!-- license-text-area -->

                    <div class="rex-licenes__input-wrap-area">

                        <form method="post" action="options.php" >

                            <?php settings_fields('wpfm_pro_license'); ?>
                            <div class="rex-licenes__input-block-area">
                                <div class="rex-licenes__input-field">
                                    <input id="wpfm_pro_license_key" name="wpfm_pro_license_key" type="password" class="regular-text" value="<?php echo esc_attr( $license ); ?>" placeholder="<?php esc_html_e('Enter your license key', 'rex-product-feed-pro'); ?>" />
                                    <div class="wpfm-pro-license-data" style="margin-top: 5px;">
                                        <?php if(!empty($license_data)) {
                                            $license_data = json_decode($license_data);
                                            if(is_array($license_data)) {
                                                $license = json_decode(json_encode($license_data));
                                                $message = array();
                                                $html = '';
                                                if( ! empty( $license ) && is_object( $license ) ) {
                                                    if ( false === $license->success ) {
                                                        switch( $license->error ) {
                                                            case 'expired' :
                                                                $class = 'expired';
                                                                $messages[] = sprintf(
                                                                    esc_html__( 'Your license key expired on %s. Please <a href="%s" target="_blank">renew your license key</a>.', 'rex-product-feed-pro' ),
                                                                    date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ),
                                                                    'https://rextheme.com/your-account/'
                                                                );
                                                                $license_status = 'license-' . $class . '-notice';
                                                                break;
                                                            case 'revoked' :
                                                                $class = 'error';
                                                                $messages[] = sprintf(
                                                                    esc_html__( 'Your license key has been disabled. Please <a href="%s" target="_blank">contact support</a> for more information.', 'rex-product-feed-pro' ),
                                                                    'https://rextheme.com/your-account/'
                                                                );
                                                                $license_status = 'license-' . $class . '-notice';
                                                                break;
                                                            case 'missing' :
                                                                $class = 'error';
                                                                $messages[] = sprintf(
                                                                    esc_html__( 'Invalid license. Please <a href="%s" target="_blank">visit your account page</a> and verify it.', 'rex-product-feed-pro' ),
                                                                    'https://rextheme.com/your-account/'
                                                                );
                                                                $license_status = 'license-' . $class . '-notice';
                                                                break;
                                                            case 'invalid' :
                                                            case 'site_inactive' :
                                                                $class = 'error';
                                                                $messages[] = sprintf(
                                                                    esc_html__( 'Your %s is not active for this URL. Please <a href="%s" target="_blank">visit your account page</a> to manage your license key URLs.', 'rex-product-feed-pro' ),
                                                                    $args['name'],
                                                                    'https://rextheme.com/your-account/'
                                                                );
                                                                $license_status = 'license-' . $class . '-notice';
                                                                break;
                                                            case 'item_name_mismatch' :
                                                                $class = 'error';
                                                                $messages[] = sprintf( esc_html__( 'This appears to be an invalid license key for %s.', 'rex-product-feed-pro' ), $args['name'] );
                                                                $license_status = 'license-' . $class . '-notice';
                                                                break;
                                                            case 'no_activations_left':
                                                                $class = 'error';
                                                                $messages[] = sprintf( esc_html__( 'Your license key has reached its activation limit. <a href="%s">View possible upgrades</a> now.', 'rex-product-feed-pro' ),
                                                                    'https://rextheme.com/your-account/' );
                                                                $license_status = 'license-' . $class . '-notice';
                                                                break;
                                                            case 'license_not_activable':
                                                                $class = 'error';
                                                                $messages[] = esc_html__( 'The key you entered belongs to a bundle, please use the product specific license key.', 'rex-product-feed-pro' );
                                                                $license_status = 'license-' . $class . '-notice';
                                                                break;
                                                            default :
                                                                $class = 'error';
                                                                $error = ! empty(  $license->error ) ?  $license->error : esc_html__( 'unknown_error', 'rex-product-feed-pro' );
                                                                $messages[] = sprintf( esc_html__( 'There was an error with this license key: %s. Please <a href="%s">contact our support team</a>.', 'rex-product-feed-pro' ),
                                                                    $error,
                                                                    'https://rextheme.com/your-account/'
                                                                );
                                                                $license_status = 'license-' . $class . '-notice';
                                                                break;
                                                        }

                                                    }
                                                    else {
                                                        switch( $license->license ) {
                                                            case 'valid' :
                                                            default:
                                                                $class = 'valid';
                                                                $now        = current_time( 'timestamp' );
                                                                $expiration = strtotime( $license->expires, current_time( 'timestamp' ) );
                                                                if( 'lifetime' === $license->expires ) {
                                                                    $messages[] = esc_html__( 'License key never expires.', 'rex-product-feed-pro' );
                                                                    $license_status = 'license-lifetime-notice';
                                                                } elseif( $expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) {
                                                                    $messages[] = sprintf(
                                                                        esc_html__( 'Your license key expires soon! It expires on %s. <a href="%s" target="_blank">Renew your license key</a>.', 'rex-product-feed-pro' ),
                                                                        date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ),
                                                                        'https://rextheme.com/your-account/'
                                                                    );
                                                                    $license_status = 'license-expires-soon-notice';
                                                                } else {
                                                                    $messages[] = sprintf(
                                                                        esc_html__( 'Your license key expires on %s.', 'rex-product-feed-pro' ),
                                                                        date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) )
                                                                    );
                                                                    $license_status = 'license-expiration-date-notice';
                                                                }
                                                                break;
                                                        }
                                                    }
                                                }
                                                else {
                                                    $class = 'empty';
                                                    $messages[] = sprintf(
                                                        esc_html__( 'To receive updates, please enter your valid %s license key.', 'rex-product-feed-pro' ),
                                                        'WPFM PRO'
                                                    );
                                                    $license_status = null;
                                                }

                                                if ( ! empty( $messages ) ) {
                                                    foreach( $messages as $message ) {
                                                        $html .= '<div class="wpfm-license-data wpfm-license-' . esc_attr($class) . ' ' . esc_attr($license_status) . '">';
                                                        $html .= '<p><em>' . esc_html($message) . '</em></p>';
                                                        $html .= '</div>';
                                                    }
                                                    echo $html; // phpcs:ignore
                                                }
                                            }
                                        } ?>
                                    </div>
                                </div>


                                <div class="rex-licenes__btn-field">

                                    <?php if( false !== $license ) { ?>

                                        <?php if( $status !== false && $status == 'valid' ) { ?>
<!--                                            <span style="color:green;">--><?php //esc_html_e('active'); ?><!--</span>-->
                                            <?php wp_nonce_field( 'wpfm_pro_nonce', 'wpfm_pro_nonce' ); ?>
                                            <input type="submit" class="button-secondary" name="wpfm_pro_license_deactivate" value="<?php esc_html_e('Deactivate License', 'rex-product-feed-pro'); ?>"/>
                                        <?php } else {
                                            wp_nonce_field( 'wpfm_pro_nonce', 'wpfm_pro_nonce' ); ?>
                                            <input type="submit" class="button-secondary" name="wpfm_pro_license_activate" value="<?php esc_html_e('Activate License', 'rex-product-feed-pro'); ?>"/>
                                        <?php } ?>

                                    <?php } ?>

                                </div>
                            </div>
                            <!-- rex-licenes__input-block-area -->

                            <?php submit_button(); ?>

                        </form>
                    </div>
                    <!-- rex-licenes__input-block-area -->

                </div>
                <!-- rex-licenes__licensekey-area -->

                <div class="rex-licenes__logo-area">
                    <div class="rex-licenes__logo">
                        <img src="<?php echo esc_url( WPFM_PLUGIN_ASSETS_FOLDER . 'icon/wpfm_logo.png' )?>" class="title-icon" alt="wpfm-logo">
                    </div>

                    <div class="rex-licenes__btn-area">
                        <a class="btn-default" href="<?php echo esc_url( apply_filters('wpfm_license_link', 'https://rextheme.com/your-account/#purchase') ); ?>" target="_blank"><?php echo esc_html__('Manage License', 'rex-product-feed-pro')?></a>
                    </div>

                </div>
                <!-- rex-licenes__logo-area -->

            </div>
            <!-- rex-licenes__top-area -->


            <div class="rex-licenes__single-category">

                <div class="single-block">

                    <div class="header">
                        <img src="<?php echo esc_url( WPFM_PLUGIN_ASSETS_FOLDER . 'icon/document.png' )?>" class="title-icon" alt="bwf-documentation">
                        <h4><?php echo esc_html__('Documentations', 'rex-product-feed-pro')?></h4>
                    </div>

                    <div class="body">
                        <p>
                            <?php echo esc_html__('Get started by spending some time with the documentation and generate flawless product feed for major online marketplaces within minutes.', 'rex-product-feed-pro')?>
                        </p>

                        <a class="btn-default" href="<?php echo esc_url( apply_filters('wpfm_document_link', 'https://rextheme.com/docs-category/product-feed-manager/') ); ?>" target="_blank"><?php echo esc_html__('Documentation', 'rex-product-feed-pro')?></a>
                    </div>
                </div>
                <!-- single-block one-->

                <div class="single-block">
                    <div class="header">
                        <img src="<?php echo esc_url( WPFM_PLUGIN_ASSETS_FOLDER . 'icon/support.png')?>" class="title-icon" alt="bwf-documentation">
                        <h4><?php echo esc_html__('Support', 'rex-product-feed-pro')?></h4>

                    </div>

                    <div class="body">
                        <p>
                            <?php echo esc_html__('Can’t find solution with our documentation? Just post a ticket. Our professional team is here to solve your problems.', 'rex-product-feed-pro')?>
                        </p>

                        <a class="btn-default" href="<?php echo esc_url( apply_filters('wpfm_support_link', 'https://rextheme.com/your-account/?active_tab=support') ); ?>" target="_blank"><?php echo esc_html__('Post a Ticket', 'rex-product-feed-pro')?></a>
                    </div>
                </div>
                <!-- single-block two -->

                <div class="single-block popular">
                    <div class="header">
                        <img src="<?php echo esc_url( WPFM_PLUGIN_ASSETS_FOLDER . 'icon/rating.png' )?>" class="title-icon" alt="bwf-documentation">
                        <h4><?php echo esc_html__('Show Your Love', 'rex-product-feed-pro')?></h4>
                    </div>

                    <div class="body">
                        <p>
                            <?php echo esc_html__('We love to have you in Best WooCommerce feed family. Take your 2 minutes to review and speed the love to encourage us to keep it going.', 'rex-product-feed-pro')?>
                        </p>

                        <a class="btn-default" href="<?php echo esc_url( apply_filters('wpfm_review_link', 'https://wordpress.org/plugins/best-woocommerce-feed/#reviews ') ) ?>" target="_blank"><?php echo esc_html__('Leave A Review', 'rex-product-feed-pro')?> </a>
                    </div>
                </div>
                <!-- single-block three -->

            </div>
            <!-- single-block-category end -->

        </div>
        <!--rex-licenes__general-wrap-area -->
    </div>
    <!-- rex-licenes__general-area .end -->
</div>
<!-- rex-licenes-wrap -->
