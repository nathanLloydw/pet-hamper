<?php
/**
 * Includes feed preview-popup markup
 * if preview button is triggered
 */
$get = rex_feed_get_sanitized_get_post();
if ( isset( $get[ 'get' ][ 'post' ] ) ) {
    $feed_id = $get[ 'get' ][ 'post' ];
    $publish_btn_id = get_post_meta( $feed_id, 'rex_feed_publish_btn', true );
    delete_post_meta( $feed_id, 'rex_feed_publish_btn' );
    if ( 'rex-bottom-preview-btn' === $publish_btn_id ) {
        $format = get_post_meta( $feed_id, 'rex_feed_feed_format', true );
        $feed_url = get_post_meta( $feed_id, 'rex_feed_xml_file', true );

        $request  = wp_remote_get( $feed_url, array( 'sslverify' => FALSE ) );
        if( is_wp_error( $request ) ) {
            return 'false';
        }
        $feed_string = wp_remote_retrieve_body( $request );
        if ( 'xml' === $format ) {
            $feed = new DOMDocument;
            $feed->preserveWhiteSpace = FALSE;
            $feed->loadXML( $feed_string );
            $feed->formatOutput = TRUE;
            $feed_string = $feed->saveXML();
        }
        include_once plugin_dir_path(__FILE__) . 'rex-product-feed-xml-preview-popup.php';
    }
}
?>

<table id="config-table" class="responsive-table wpfm-field-mappings">
    <?php require_once plugin_dir_path( __FILE__ ) . '/loading-spinner.php';?>
</table>

<div id="rex-feed-footer-btn" class="rex-feed-footer-btn">
    <div class="rex-feed-attr-btn-area">
        <a id="rex-new-attr" class="rex-new-custom-btn">
            <?php include WPFM_PLUGIN_ASSETS_FOLDER_PATH . 'icon/icon-svg/icon-plus.php';?>
            <?php echo esc_attr__( 'Add New Attribute', 'rex-product-feed' ) ?>
        </a>
        <a id="rex-new-custom-attr" class="rex-new-custom-attr">
            <?php include WPFM_PLUGIN_ASSETS_FOLDER_PATH . 'icon/icon-svg/icon-plus.php';?>
            <?php echo esc_attr__( 'Add New Custom Attribute', 'rex-product-feed' ) ?>
        </a>
    </div>

    <div class="rex-feed-publish-btn">
        <span class="spinner"></span>
        <a id="rex-bottom-preview-btn" class="bottom-preview-btn">
            <?php echo esc_attr__( 'Preview Feed', 'rex-product-feed' ) ?>
        </a>
        <a id="rex-bottom-publish-btn" class="rex-new-custom-btn bottom-publish-btn">
            <?php echo esc_attr__( 'Publish', 'rex-product-feed' ) ?>
        </a>
    </div>
</div>