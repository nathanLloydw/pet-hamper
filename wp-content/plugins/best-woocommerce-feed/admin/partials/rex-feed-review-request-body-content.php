<div  id="rex_feed_review_request_body_content">
	<div class="request_rating_contnent">
		<div class="request_rating_heading">
			<h2><?php esc_html_e( 'Awesome, you\'ve generated the feed successfully!', 'rex-product-feed' ); ?></h2>
			<a href="https://wordpress.org/support/plugin/best-woocommerce-feed/reviews/#new-post" target="_blank">
				<img src="<?php echo esc_url( WPFM_PLUGIN_ASSETS_FOLDER . 'icon/star-rating.png' )?>"  alt="star-rating">
			</a>
		</div>
		
		<p><?php _e( 'Please do give us a <a href="https://wordpress.org/support/plugin/best-woocommerce-feed/reviews/#new-post" target="_blank">rating</a> if you like using our plugin. It will only takes 2 minutes.', 'rex-product-feed' ); // phpcs:ignore ?></p>
	</div>
	<div id="rex_feed_review_request_btn">
	    <a id="rex_rated_already">
			<?php esc_html_e( 'Already Rated', 'rex-product-feed' ) ?></a>
		<a id="rex_rate_now" href="https://wordpress.org/support/plugin/best-woocommerce-feed/reviews/#new-post" target="_blank">
			<?php esc_html_e( 'Rate Now', 'rex-product-feed' ) ?></a>
		<a id="rex_rate_not_now">
			<?php esc_html_e( 'Not Now', 'rex-product-feed' ) ?></a>
	</div>
</div>