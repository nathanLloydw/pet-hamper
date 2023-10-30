<?php
/**
 * Admin View: Generator Queue List
 *
 * @package  WooCommerce Product Recommendations
 * @since    3.0.0
 * @version  3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap woocommerce prl-generator-queue-wrap">

	<h1><?php echo esc_html__( 'Recommendations Queue', 'woocommerce-product-recommendations' ); ?></h1>
	<p><?php
		// Translators: 1: Regenaration FAQ item 2: Queue/CLI FAQ item
		echo wp_kses_post( sprintf( __( 'To minimize server load and ensure a snappy customer experience, the Product Recommendations extension generates and refreshes recommendations <a href="%1$s" target="_blank">only when needed</a>. By default, re-generation tasks are queued and processed approximately every minute. However, it is also possible to process queued tasks manually using <a href="%2$s" target="_blank">WP-CLI</a>. Use the information provided on this page to diagnose problems with task processing.', 'woocommerce-product-recommendations' ), WC_PRL()->get_resource_url( 'regeneration' ), WC_PRL()->get_resource_url( 'cli' ) ) );
		?>
	</p>
	<?php
	if ( $stale_table->has_items() ) {
		?>
		<div class="prl-generator-queue-wrap--stale">
			<h2><?php echo esc_html( sprintf( __( 'Failed Tasks (%d)', 'woocommerce-product-recommendations' ), $stale_table->get_total_items_count() ) ); ?></h2>
			<?php
			$stale_table->display();
			?>
		</div>
	<?php
    }
    ?>
	<div class="prl-generator-queue-wrap--normal">
		<?php
		if ( $table->has_items() ) {
		?>
		<h2>
			<?php 
				// Translators: %s Number of total items in queue table.
				echo esc_html( sprintf( __( 'Pending Tasks (%d)', 'woocommerce-product-recommendations' ), $table->get_total_items_count() ) );
			?>
		</h2>
		<?php
		}
		?>
		<form id="generator-queue-table" method="GET">
			<?php wp_nonce_field( 'woocommerce-prl-generator-queue' ); ?>
			<input type="hidden" name="page" value="<?php echo ( ! empty( $_REQUEST[ 'page' ] ) ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST[ 'page' ] ) ) ) : 1; ?>"/>
			<input type="hidden" name="tab" value="<?php echo ( ! empty( $_REQUEST[ 'tab' ] ) ) ? esc_attr( sanitize_text_field( wp_unslash( $_REQUEST[ 'tab' ] ) ) ) : 1; ?>"/>
			<?php $table->display() ?>
		</form>

	</div>
</div>
