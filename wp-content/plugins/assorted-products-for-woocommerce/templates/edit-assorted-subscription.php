<?php get_header(); ?>

<?php if ( is_user_logged_in() ) { ?>
	<?php 
	do_action('wc_abp_edit_assorted_subscription_before_form');
	global $woocommerce;
	$obj = new ABP_Assorted_Products_Subscription();
	$user_id = get_current_user_id();
	$product = $obj::get_product();
	$subscription = $obj::get_subscription();
	
	if ( $subscription && !$subscription->has_status( 'trash' ) && $user_id == $subscription->get_user_id() && $product && apply_filters('woocommerce_is_subscription', false, $product->get_id(), $product) && $product->is_type('assorted_product') ) {	
		?>
	<div class="edit_assorted_subscription abp_subscription_<?php echo esc_attr($subscription->get_id()); ?>" id="abp-change-order-<?php echo esc_attr($subscription->get_id()); ?>">
	<div class="woocommerce">
		<div class="woocommerce-message" role="alert">
			<?php esc_html_e('In order to update your subscription add products to the box.', 'wc-abp'); ?>
		</div>
	</div>
		<div id="product-<?php get_the_ID(); ?>" <?php wc_product_class(); ?>>
			<form method="post" name="" action="" class="edit-assorted-subscription">
			<?php
				wp_nonce_field('abp_assorted_edit_subscription_nonce', 'abp_assorted_edit_subscription_nonce' );
				/**
				 * Hook: cpb_custom_box_product_layout
				 *
				 */
				do_action( 'abp_assorted_products_layout', $product->get_id() );
			?>
			</form>
			<div class="summary entry-summary">
			</div>
		</div>
	</div>
	<?php } else { ?>
	<div class=""><p><?php esc_html_e('Sorry! you are not allowed to access this page. There are some issue, kindly contact admin.', 'wc-abp'); ?></p>
	<?php } ?>
<?php } else { ?>
	<div class=""><p><?php esc_html_e('Sorry! you are not allowed to access this page. You must log In.', 'wc-abp'); ?></p>
	</div>
<?php } ?>

<?php 
get_footer();
