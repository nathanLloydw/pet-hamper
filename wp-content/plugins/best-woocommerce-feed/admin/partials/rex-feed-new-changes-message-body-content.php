<?php
/**
 * This file is responsible for displaying new changes notice in new ui
 *
 * @link       https://rextheme.com
 * @since      1.0.0
 *
 * @package    Rex_Product_Feed
 * @subpackage Rex_Product_Feed/admin/partials
 */

?>
<div  id="rex_feed_new_changes_msg_content">
	<div class="new_changes_msg_contnent">
		<div class="new_changes_msg_heading">
            <h2><?php _e( 'Promising new user interaface of the Product Feed Manager plugin - <strong>Changes you may want to know</strong>.', 'rex-product-feed' ); // phpcs:ignore ?></h2>
			<p><span style="color: red">*</span><?php esc_html_e( 'Please clean your browser cache for improved performance.', 'rex-product-feed' ); ?></p>
		</div>
	</div>
	<div id="rex_feed_new_changes_msg_btn">
		<a id="view_changes_btn" href="https://rextheme.com/new-interface-of-product-feed-manager/" target="_blank">
			<?php esc_html_e( 'View Changes', 'rex-product-feed' ); ?></a>
	</div>
</div>
