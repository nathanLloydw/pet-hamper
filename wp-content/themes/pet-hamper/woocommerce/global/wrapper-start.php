<?php
/**
 * Content wrappers
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/global/wrapper-start.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see         https://docs.woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$template = wc_get_theme_slug_for_templates();

switch ( $template ) {
	case 'twentyten':
		echo '<div id="container"><div id="content" role="main">';
		break;
	case 'twentyeleven':
		echo '<div id="primary"><div id="content" role="main" class="twentyeleven">';
		break;
	case 'twentytwelve':
		echo '<div id="primary" class="site-content"><div id="content" role="main" class="twentytwelve">';
		break;
	case 'twentythirteen':
		echo '<div id="primary" class="site-content"><div id="content" role="main" class="entry-content twentythirteen">';
		break;
	case 'twentyfourteen':
		echo '<div id="primary" class="content-area"><div id="content" role="main" class="site-content twentyfourteen"><div class="tfwc">';
		break;
	case 'twentyfifteen':
		echo '<div id="primary" role="main" class="content-area twentyfifteen"><div id="main" class="site-main t15wc">';
		break;
	case 'twentysixteen':
		echo '<div id="primary" class="content-area twentysixteen"><main id="main" class="site-main" role="main">';
		break;
	default:
		echo '<div id="primary" class="content-area"><main id="main" class="site-main" role="main">';

		global $product;

		 if ( $product && $product->is_type( 'assorted_product' ) && !is_product_category('hampers') ): ?>

		 	<h2 class="linetitle">Create your own hamper</h2>
		
			<div class="embed-container">
    		 <?php the_field('video'); ?>
			</div>

			<style>

				.embed-container iframe,
				.embed-container object,
				.embed-container embed { 
				    width: 100%;

				}
				.woocommerce-breadcrumb,
				.product_title h1,
				.related.products {
					display: none;
				}

			
			</style>

			<div class="grid3">
				<div class="col">
					<span class="number title">1.</span>
					<span class="item title">Choose Your Packaging</span>
				</div>
				<div class="col">
					<span class="number title">2.</span>
					<span class="item title">Select your Products</span>
				</div>
				<div class="col">
					<span class="number title">3.</span>
					<span class="item title">Add your Gift Message</span>
				</div>
			</div>		
			
		<?php endif; break;

		
}
