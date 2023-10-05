<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


$methods   = $this->order->get_meta( '_shipping_methods' );
$shipments = $this->order->get_meta( '_wcms_packages' );

$packing_slips = [];


if(!empty($shipments))
{
    foreach ($shipments as $x => $shipment)
    {

        $method = $methods[$x]['label'];
        $destination = wcms_get_formatted_address($shipment['destination']);
        $products = $shipment['contents'];
        $gift_message = $shipment['note'];
        $product_list = [];

        foreach($products as $product)
        {
            $quantity = $product['quantity'];
            $id       = empty( $product['variation_id'] ) ? $product['product_id'] : $product['variation_id'];
            $name     = apply_filters( 'wcms_product_title', get_the_title( $id ), $product );

            $product = array('quantity' => $quantity, 'name' => $name);
            $product_list[] = $product;
        }

        $packing_slip = array('shipping_address' => $destination, 'products' => $product_list,'shipping_method' => $method,'gift_message'=>$gift_message);

        $packing_slips[] = $packing_slip;
    }
}
else
{
    $packing_slips = [array('shipping_address' => 'default','shipping_method'=>'default','products' => 'default')];
}

foreach($packing_slips as $packing_slip)
{

    ?>

    <?php do_action( 'wpo_wcpdf_before_document', $this->get_type(), $this->order ); ?>
    <div class="page">
        <table class="head container">
            <tr>
                <td class="header">
                    <?php
                    if ( $this->has_header_logo() ) {
                        $this->header_logo();
                    } else {
                        echo $this->get_title();
                    }
                    ?>
                </td>
                <td class="shop-info">
                    <?php do_action( 'wpo_wcpdf_before_shop_name', $this->get_type(), $this->order ); ?>
                    <div class="shop-name"><h3><?php $this->shop_name(); ?></h3></div>
                    <?php do_action( 'wpo_wcpdf_after_shop_name', $this->get_type(), $this->order ); ?>
                    <?php do_action( 'wpo_wcpdf_before_shop_address', $this->get_type(), $this->order ); ?>
                    <div class="shop-address"><?php $this->shop_address(); ?></div>
                    <?php do_action( 'wpo_wcpdf_after_shop_address', $this->get_type(), $this->order ); ?>
                </td>
            </tr>
        </table>

        <?php do_action( 'wpo_wcpdf_before_document_label', $this->get_type(), $this->order ); ?>

        <h1 class="document-type-label">
            <?php if ( $this->has_header_logo() ) echo $this->get_title(); ?>
        </h1>

        <?php do_action( 'wpo_wcpdf_after_document_label', $this->get_type(), $this->order ); ?>

        <table class="order-data-addresses">
            <tr>
                <td class="address shipping-address">
                    <!-- <h3><?php _e( 'Shipping Address:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3> -->
                    <?php do_action( 'wpo_wcpdf_before_shipping_address', $this->get_type(), $this->order ); ?>
                    <?php
                    if($packing_slip['shipping_address'] == 'default')
                    { $this->shipping_address(); }
                    else
                    { echo $packing_slip['shipping_address']; }
                    ?>
                    <?php do_action( 'wpo_wcpdf_after_shipping_address', $this->get_type(), $this->order ); ?>
                    <?php if ( isset( $this->settings['display_email'] ) ) : ?>
                        <div class="billing-email"><?php $this->billing_email(); ?></div>
                    <?php endif; ?>
                    <?php if ( isset( $this->settings['display_phone'] ) ) : ?>
                        <div class="shipping-phone"><?php $this->shipping_phone( ! $this->show_billing_address() ); ?></div>
                    <?php endif; ?>
                </td>
                <td class="address billing-address">

                    <h3><?php _e( 'Billing Address:', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
                    <?php do_action( 'wpo_wcpdf_before_billing_address', $this->get_type(), $this->order ); ?>
                    <?php $this->billing_address(); ?>
                    <?php do_action( 'wpo_wcpdf_after_billing_address', $this->get_type(), $this->order ); ?>
                    <?php if ( isset( $this->settings['display_phone'] ) && ! empty( $this->get_billing_phone() ) ) : ?>
                        <div class="billing-phone"><?php $this->billing_phone(); ?></div>
                    <?php endif; ?>

                </td>
                <td class="order-data">
                    <table>
                        <?php do_action( 'wpo_wcpdf_before_order_data', $this->get_type(), $this->order ); ?>
                        <tr class="order-number">
                            <th><?php _e( 'Order Number:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
                            <td><?php $this->order_number(); ?></td>
                        </tr>
                        <tr class="order-date">
                            <th><?php _e( 'Order Date:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
                            <td><?php $this->order_date(); ?></td>
                        </tr>
                        <?php
                        if($packing_slip['shipping_method'] == 'default')
                        { $shipping_method = $this->get_shipping_method(); }
                        else
                        { $shipping_method = $packing_slip['shipping_method']; }

                        if ( $shipping_method) : ?>
                            <tr class="shipping-method">
                                <th><?php _e( 'Shipping Method:', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
                                <td><?php echo $shipping_method; ?></td>
                            </tr>
                        <?php endif; ?>
                        <?php do_action( 'wpo_wcpdf_after_order_data', $this->get_type(), $this->order ); ?>
                    </table>
                </td>
            </tr>
        </table>

        <?php do_action( 'wpo_wcpdf_before_order_details', $this->get_type(), $this->order ); ?>

        <table class="order-details">
            <thead>
            <tr>
                <th class="product"><?php _e( 'Product', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
                <th class="quantity"><?php _e( 'Quantity', 'woocommerce-pdf-invoices-packing-slips' ); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if($packing_slip['products'] == 'default') : ?>
                <?php foreach ( $this->get_order_items() as $item_id => $item ) : ?>
                    <tr class="<?php echo apply_filters( 'wpo_wcpdf_item_row_class', 'item-'.$item_id, $this->get_type(), $this->order, $item_id ); ?>">
                        <td class="product">
                            <?php $description_label = __( 'Description', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
                            <span class="item-name"><?php echo $item['name']; ?></span>
                            <?php do_action( 'wpo_wcpdf_before_item_meta', $this->get_type(), $item, $this->order  ); ?>
                            <span class="item-meta"><?php echo $item['meta']; ?></span>
                            <dl class="meta">
                                <?php $description_label = __( 'SKU', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
                                <?php if ( ! empty( $item['sku'] ) ) : ?><dt class="sku"><?php _e( 'SKU:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt><dd class="sku"><?php echo $item['sku']; ?></dd><?php endif; ?>
                                <?php if ( ! empty( $item['weight'] ) ) : ?><dt class="weight"><?php _e( 'Weight:', 'woocommerce-pdf-invoices-packing-slips' ); ?></dt><dd class="weight"><?php echo $item['weight']; ?><?php echo get_option( 'woocommerce_weight_unit' ); ?></dd><?php endif; ?>
                            </dl>
                            <?php do_action( 'wpo_wcpdf_after_item_meta', $this->get_type(), $item, $this->order  ); ?>
                        </td>
                        <td class="quantity"><?php echo $item['quantity']; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <?php foreach ( $packing_slip['products'] as $item_id => $item ) : ?>
                    <tr class="<?php echo apply_filters( 'wpo_wcpdf_item_row_class', 'item-'.$item_id, $this->get_type(), $this->order, $item_id ); ?>">
                        <td class="product">
                            <?php $description_label = __( 'Description', 'woocommerce-pdf-invoices-packing-slips' ); // registering alternate label translation ?>
                            <span class="item-name"><?php echo $item['name']; ?></span>
                        </td>
                        <td class="quantity"><?php echo $item['quantity']; ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>

        <div class="bottom-spacer"></div>

        <?php do_action( 'wpo_wcpdf_after_order_details', $this->get_type(), $this->order ); ?>

        <?php do_action( 'wpo_wcpdf_before_customer_notes', $this->get_type(), $this->order ); ?>

        <div class="customer-notes">
            <?php if ( $this->get_shipping_notes() || $packing_slip['gift_message']) : ?>
                <h3><?php _e( 'Customer Notes', 'woocommerce-pdf-invoices-packing-slips' ); ?></h3>
                <?php echo $packing_slip['gift_message'] ?? $this->shipping_notes(); ?>
            <?php endif; ?>
        </div>

        <?php do_action( 'wpo_wcpdf_after_customer_notes', $this->get_type(), $this->order ); ?>

        <?php if ( $this->get_footer() ) : ?>
            <div id="footer">
                <!-- hook available: wpo_wcpdf_before_footer -->
                <?php $this->footer(); ?>
                <!-- hook available: wpo_wcpdf_after_footer -->
            </div><!-- #letter-footer -->
        <?php endif; ?>

        <?php do_action( 'wpo_wcpdf_after_document', $this->get_type(), $this->order ); ?>
    </div>

    <?php
}
?>

<style>
    .page { height:100%; }
</style>