<?php
$system_status = Rex_Feed_System_Status::get_all_system_status();
?>

<div id="tab5" class="tab-content block-wrapper">

    <!-- `rex-system-status`  block -->
    <div class="system-status rex-system-status">

        <!-- `system-status__platform` element in the `rex-system-status` block  -->
        <div class="rex-system-status__platform">
            <h3 class="rex-system-status__heading">
                <?php echo esc_html__('System Status', 'rex-product-feed'); ?>
            </h3>
            <button type="button" class="rex-system-status__button" id="rex-feed-system-status-copy-btn">
                <i class="fa fa-files-o"></i>
                <?php esc_html_e( 'Copy Status', 'rex-product-feed' );?>
            </button>
        </div>

        <!-- `rex-system-status__content` element in the `rex-system-status` block  -->
        <div class="rex-system-status__content">

            <?php
                foreach ( $system_status as $status ) {
                    if ( isset( $status[ 'label' ] ) && $status[ 'label' ] !== '' && isset( $status[ 'message' ] ) && $status[ 'message' ] !== '' ) {
            ?>
                <!-- `rex-system-status__info` element in the `rex-system-status` block  -->
                <div class="rex-system-status__info">

                    <!-- `rex-system-status__label` element in the `rex-system-status` block  -->
                    <div class="rex-system-status__ground">
                        <h6 class="rex-system-status__label">
                            <?php echo esc_html__( $status[ 'label' ], 'rex-product-feed' ); ?>
                        </h6>
                    </div>

                    <div class="rex-system-status__lists">

                        <span class="rex-system-status__list">
                            <?php
                                $message = $status[ 'message' ];  //phpcs:ignore
                                $classes = 'dashicons dashicons-yes';
                                if ( $status[ 'label' ] === 'Product Types' || $status[ 'label' ] === 'Total Products by Types' ) {
                                    $classes = '';
                                }
                                if ( isset( $status[ 'status' ] ) && $status[ 'status' ] === 'error' || isset( $status[ 'is_writable' ] ) && $status[ 'is_writable' ] === 'False' ) {
                                    echo '<mark class="error"><span class="dashicons dashicons-warning"></span>' . $message . '</mark> ';
                                }
                                else {
                                    echo '<mark class="yes"><span class="' . $classes . '"></span>' . $message . '</mark> ';
                                }
                                ?>
                        </span>
                    </div>

                 </div>

            <?php
                }
            }?>
        
        </div>

    </div>

    <textarea name="" id="rex-feed-system-status-area" style="visibility: hidden; margin-top: 10px" cols="100" rows="30"><?php echo Rex_Feed_System_Status::get_system_status_text(); //phpcs:ignore?></textarea>
</div>