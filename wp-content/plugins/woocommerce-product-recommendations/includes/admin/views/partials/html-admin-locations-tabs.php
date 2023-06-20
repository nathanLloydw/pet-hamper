<?php
// Parse GET for selecting current tab.
$section     = isset( $_GET[ 'section' ] ) ? wc_clean( $_GET[ 'section' ] ) : 'locations_overview';
$location_id = isset( $_GET[ 'location' ] ) ? wc_clean( $_GET[ 'location' ] ) : false;
?>
<ul class="subsubsub">

	<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=prl_locations' ) ); ?>" <?php echo 'locations_overview' === $section ? 'class="current"' : ''; ?>><?php esc_html_e( 'Overview', 'woocommerce-product-recommendations' ); ?></a> | </li>

	<?php foreach ( $locations as $id => $location ) {

		$selected = 'hooks' === $section && $location->get_location_id() === $location_id ? 'class="current"' : ''; ?>

		<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<li><a href="<?php echo esc_url( admin_url( 'admin.php?page=prl_locations&section=hooks&location=' . $location->get_location_id() ) ); ?>" <?php echo $selected; ?>><?php echo esc_html( $location->get_title() ); ?></a> <span>|</span> </li>

	<?php } ?>

</ul>
