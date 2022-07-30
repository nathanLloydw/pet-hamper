<?php
$troubleshoot_url = 'https://rextheme.com/docs/wpfm-troubleshooting-for-common-issues/';
$documentation_url = 'https://rextheme.com/docs/product-feed-manager-documentation/';
?>

<div class="rex-feed-cofig-settings">

    <a id="rex-feed-documentation-btn" class="rex-fill-button mr-5" href="<?php echo esc_url($documentation_url);?>" role="button" target="_blank">
        <?php include WPFM_PLUGIN_ASSETS_FOLDER_PATH . 'icon/icon-svg/documentation.php';?>
        <?php echo esc_html__( 'Documentation', 'rex-product-feed' )?>
    </a>
    <a id="rex-feed-troubleshoot-btn" class="rex-fill-button mr-5" href="<?php echo esc_url($troubleshoot_url);?>" role = "button" target="_blank">
        <?php include WPFM_PLUGIN_ASSETS_FOLDER_PATH . 'icon/icon-svg/troubleshoot.php';?>
        <?php echo esc_html__( 'Troubleshoot', 'rex-product-feed' )?>
    </a>
    <a id="rex-pr-filter-btn" class="rex-fill-button mr-5" role="button">
        <?php include WPFM_PLUGIN_ASSETS_FOLDER_PATH . 'icon/icon-svg/productfilter.php';?>
        <?php echo esc_html__( 'Product Filter', 'rex-product-feed' )?>
    </a>
    <a id="rex-feed-settings-btn" class="rex-fill-button" role="button">
        <?php include WPFM_PLUGIN_ASSETS_FOLDER_PATH . 'icon/icon-svg/settings.php';?>
        <?php echo esc_html__( 'Settings', 'rex-product-feed')?>
    </a>

</div>

<!-- .rex-feed-cofig-settings end -->
