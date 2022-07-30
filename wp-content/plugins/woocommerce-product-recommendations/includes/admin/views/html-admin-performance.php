<?php
/**
 * Admin View: Performance
 *
 * @package  WooCommerce Product Recommendations
 * @since    1.0.0
 * @version  2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap woocommerce prl-performance-wrap">

	<h1>
		<?php esc_html_e( 'Performance', 'woocommerce-product-recommendations' ); ?>
		<div class="range_container">
			<?php echo sprintf( esc_html__( '%1$s (%2$s &mdash; %3$s) vs. previous week', 'woocommerce-product-recommendations' ), '<span class="current_period">' . esc_html__( 'Last 7 days', 'woocommerce-product-recommendations' ) . '</span>', date_i18n( 'M j', $range[ 'start_date' ] ), date_i18n( 'M j', strtotime( '-1 day', $range[ 'end_date' ] ) ) ); ?>
		</div>
	</h1>

	<br class="clear">

	<div class="wc-prl-perf">

		<?php if ( isset( $glance_data[ 'gross' ] ) ) : ?>
			<a class="wc-prl-perf__tab" href="<?php echo self::get_analytics_link( [ 'chart'=>'gross_sales', 'orderby'=>'gross_sales' ] ) ?>">
				<div class="title"><?php esc_html_e( 'Gross Revenue', 'woocommerce-product-recommendations' ) ?></div>
				<div class="data">
					<h4><?php echo wc_prl_print_currency_amount( $glance_data[ 'gross' ][ 'current' ] ) ?></h4>
					<?php self::print_difference( $glance_data[ 'gross' ][ 'current' ], $glance_data[ 'gross' ][ 'previous' ] ) ?>
				</div>
			</a>
		<?php endif; ?>

		<?php if ( isset( $glance_data[ 'net' ] ) ) : ?>
			<a class="wc-prl-perf__tab" href="<?php echo self::get_analytics_link( [ 'chart'=>'net_sales', 'orderby'=>'net_sales' ] ) ?>">
				<div class="title"><?php esc_html_e( 'Net Revenue', 'woocommerce-product-recommendations' ) ?></div>
				<div class="data">
					<h4><?php echo wc_prl_print_currency_amount( $glance_data[ 'net' ][ 'current' ] ) ?></h4>
					<?php self::print_difference( $glance_data[ 'net' ][ 'current' ], $glance_data[ 'net' ][ 'previous' ] ) ?>
				</div>
			</a>
		<?php endif; ?>

		<?php if ( isset( $glance_data[ 'conversions' ] ) ) : ?>
			<a class="wc-prl-perf__tab" href="<?php echo self::get_analytics_link( [ 'chart'=>'items_sold', 'orderby'=>'items_sold' ] ) ?>">

				<div class="title"><?php esc_html_e( 'Conversions', 'woocommerce-product-recommendations' ) ?></div>
				<div class="data">
					<h4><?php echo $glance_data[ 'conversions' ][ 'current' ] ?></h4>
					<?php self::print_difference( $glance_data[ 'conversions' ][ 'current' ], $glance_data[ 'conversions' ][ 'previous' ] ) ?>
				</div>
			</a>
		<?php endif; ?>

	</div>

	<div class="wc-prl-leaderboards-wrap">
		<h2><?php esc_html_e( 'Leaderboards', 'woocommerce-product-recommendations' ); ?></h2>
		<hr role="presentation">
	</div>

	<div class="wc-prl-perf-top">
		<table>
			<thead>
				<tr class="head">
					<th colspan="2"><?php esc_html_e( 'Top Products', 'woocommerce-product-recommendations' ); ?></th>
				</tr>
				<tr class="column-headers">
					<th><?php esc_html_e( 'Product', 'woocommerce-product-recommendations' ); ?></th>
					<th><?php esc_html_e( 'Revenue', 'woocommerce-product-recommendations' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( ! empty( $top_products[ 'top_grossing' ] ) ) {
					foreach ( $top_products[ 'top_grossing' ] as $index => $data ) {
						$product = self::get_product( absint( $data[ 'product_id' ] ) );
						if ( ! ( $product instanceof WC_Product ) ) {
							continue;
						}
						echo '<tr>';
						echo '<td><a href="' . self::get_analytics_link( [ 'filter' => 'single_product', 'products' => $product->get_id() ] ) . '">' . $product->get_title() . '</a></td>';
						echo '<td>' . wc_prl_print_currency_amount( $data[ 'rate' ] ) . '</td>';
						echo '</tr>';
					}
				} else {
					echo '<tr><td colspan="2" class="empty">' . esc_html__( 'No data recorded in the last 7 days.', 'woocommerce-product-recommendations' ) . '</td></tr>';
				}
				?>
			</tbody>
		</table>
		<table>
			<thead>
				<tr class="head">
					<th colspan="2"><?php esc_html_e( 'Top Locations', 'woocommerce-product-recommendations' ); ?></th>
				</tr>
				<tr class="column-headers">
					<th><?php esc_html_e( 'Location', 'woocommerce-product-recommendations' ); ?></th>
					<th><?php esc_html_e( 'Revenue', 'woocommerce-product-recommendations' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( ! empty( $top_locations[ 'top_grossing' ] ) ) {
					foreach ( $top_locations[ 'top_grossing' ] as $index => $data ) {
						$location_data = self::get_location_by_hash( $data[ 'location_hash' ] );
						echo '<tr>';
						if ( ! empty( $location_data[ 'link' ] ) ) {
							echo '<td><a href="' . str_replace( '%%type%%', 'sales', $location_data[ 'link' ] ) . '">' . $location_data[ 'title' ] . ' - ' . $location_data[ 'label' ] . '</a></td>';
						} else {
							echo '<td>' . $location_data[ 'title' ] . ' - ' . $location_data[ 'label' ] . '</a></td>';
						}
						echo '<td>' . wc_prl_print_currency_amount( $data[ 'rate' ] ) . '</td>';
						echo '</tr>';
					}

				} else {
					echo '<tr><td colspan="2" class="empty">' . esc_html__( 'No data recorded in the last 7 days.', 'woocommerce-product-recommendations' ) . '</td></tr>';
				}
				?>
			</tbody>
		</table>
	</div>
</div>
