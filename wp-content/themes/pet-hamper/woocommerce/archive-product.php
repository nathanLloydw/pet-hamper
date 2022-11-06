<?php
/**
 * The Template for displaying product archives, including the main shop page which is a post type archive
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/archive-product.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.4.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

	$parent_term = get_queried_object();
?>
<?php
/**
 * Hook: woocommerce_before_main_content.
 *
 * @hooked woocommerce_output_content_wrapper - 10 (outputs opening divs for the content)
 * @hooked woocommerce_breadcrumb - 20
 * @hooked WC_Structured_Data::generate_website_data() - 30
 */
do_action( 'woocommerce_before_main_content' );

?>
<header class="woocommerce-products-header">
	<?php if ( apply_filters( 'woocommerce_show_page_title', true ) ) : ?>

		<?php if (is_product_category( array( 'hampers', 'example-cat')) ) : ?>

		<h1 class="linetitle center"><?php woocommerce_page_title(); ?></h1>

		<?php else : ?>

		<h1 class="woocommerce-products-header__title page-title"><?php woocommerce_page_title(); ?></h1>

		<?php endif; ?>

	<?php endif; ?>

	<?php
	/**
	 * Hook: woocommerce_archive_description.
	 *
	 * @hooked woocommerce_taxonomy_archive_description - 10
	 * @hooked woocommerce_product_archive_description - 10
	 */
	do_action( 'woocommerce_archive_description' );
	?>
</header>

<?php if( have_rows('category_grid', $parent_term) ): ?>

<div class="catgrid archive">

	<div class="griditem">

		<div class="inner">
    	
		    <a onclick="scroll_to_content();" class="pointer">

		    	<div class="image">
		    	
			    	<?php echo wp_get_attachment_image( get_term_meta( get_queried_object_id(), 'thumbnail_id', 1 ), 'full' ); ?>
			    </div> 

		    	 <div class="content">
	            	<p class="title">All <?php woocommerce_page_title(); ?></p>
	        	</div>
		    </a>	

		</div>

	</div>

<?php while( have_rows('category_grid', $parent_term) ): the_row(); 

	$link = get_sub_field('link');

	?>

	<div class="griditem test">

		<div class="inner">

			<?php if( $link ): 
		    $link_url = $link['url']; // str_replace('brand','tag',$link['url']);
		    $link_title = $link['title'];
		    $link_target = $link['target'] ? $link['target'] : '_self';
		    ?>

			<a href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>">

			<div class="image"><img class="bannerimg" <?php awesome_acf_responsive_image(get_sub_field( 'image' ),'thumb-640','768px'); ?>  alt="" /></div>
            
            <div class="content">
            	<p class="title"><?php the_sub_field('title'); ?></p>					
        	</div>

        	</a>

        	<?php endif; ?>

        </div>
		
	</div>
	
<?php endwhile; ?>
</div>
<?php endif; ?>


<?php if( have_rows('collections_grid', $parent_term) ): ?>

		<?php if (is_product_category( 'hampers')) : ?>

		<h2 class="linetitle">Hamper Collections</h2>

		<?php elseif ( is_product_category( 'collections' ) || is_product_category( 'seasonal' ) || term_is_ancestor_of( 2208, get_queried_object_id(), 'product_cat' ) ) : ?>

		<!-- <h2 class="linetitle"><?php woocommerce_page_title(); ?> Picks</h2> -->

		<?php endif; ?>

	    <ul class="collections-grid archive">

	    <?php while( have_rows('collections_grid', $parent_term) ): the_row(); 
	        ?>
	        <li>

	        	<div class="inner">
	        	<?php 
				$link = get_sub_field('link');
				if( $link ): 
				    $link_url = $link['url'];
				    $link_title = $link['title'];
				    $link_target = $link['target'] ? $link['target'] : '_self';
				    ?>
				    <a href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>">
				    	<div class="image"><img class="bannerimg" <?php awesome_acf_responsive_image(get_sub_field( 'image' ),'thumb-640','768px'); ?>  alt="" /></div>
				    	 <div class="content">
			            	<p class="title"><?php echo $link_title; ?></p>
			        	</div>
				    </a>
				    
				<?php endif; ?>

				</div>
	            
	        </li>
	    <?php endwhile; ?>
	    </ul>
	<?php endif; ?>

<?php if ( is_product_category( 'collections' ) || is_product_category( 'seasonal' ) || term_is_ancestor_of( 2208, get_queried_object_id(), 'product_cat' ) ) : ?>


	<?php if( have_rows('products_loop', $parent_term) ):  ?>

	<!-- 	<h2 class="linetitle"><?php woocommerce_page_title(); ?> Picks</h2> -->

		<?php while( have_rows('products_loop', $parent_term) ): the_row(); ?>

	                <?php
	                
	                $products = get_sub_field('spec_products_loop');
	                if( $products ): ?>
	                    <ul class="productgrid four"> 
	                    <?php foreach( $products as $product ): 

	                      $permalink = get_permalink( $product->ID );
	                      $title = get_the_title( $product->ID );
	                      $price = get_post_meta( $product->ID, '_regular_price', true);
	                      $currency = get_woocommerce_currency_symbol();
	                    ?>

	                        <li class="product col center" style="margin-bottom: 2rem;">
	                        <a href="<?php echo esc_url( $permalink ); ?>"><?php echo get_the_post_thumbnail($product,'full');?></a>
	                        <div class="detail">
	                         <a href="<?php echo esc_url( $permalink ); ?>"> <h3 style="margin-bottom: 0;"><?php echo esc_html( $title ); ?></h3></a>
	                           <span class="price" style="margin: 0;"><?php echo $currency; echo $price; ?> Exl VAT</span>
	                        </div>
	                        </a>

	                      </li>
	                    <?php endforeach; ?>
	                    </ul>
	                    <?php 
	                    // Reset the global post object so that the rest of the page works correctly.
	                    wp_reset_postdata(); ?>
	                <?php endif; ?> 

	   	<?php endwhile; ?>
		
	<?php endif; ?>

<?php endif; ?>

<?php if ( !is_product_category(array( 'collections', 'seasonal' )) ) : ?>




<?php
if ( woocommerce_product_loop() ) {

	/**
	 * Hook: woocommerce_before_shop_loop.
	 *
	 * @hooked woocommerce_output_all_notices - 10
	 * @hooked woocommerce_result_count - 20
	 * @hooked woocommerce_catalog_ordering - 30
	 */
	do_action( 'woocommerce_before_shop_loop' );

	woocommerce_product_loop_start();

	if ( wc_get_loop_prop( 'total' ) ) {
		while ( have_posts() ) {
			the_post();

			/**
			 * Hook: woocommerce_shop_loop.
			 */
			do_action( 'woocommerce_shop_loop' );

			wc_get_template_part( 'content', 'product' );
		}
	}

	woocommerce_product_loop_end();

	/**
	 * Hook: woocommerce_after_shop_loop.
	 *
	 * @hooked woocommerce_pagination - 10
	 */
	do_action( 'woocommerce_after_shop_loop' );
} else {
	/**
	 * Hook: woocommerce_no_products_found.
	 *
	 * @hooked wc_no_products_found - 10
	 */
	do_action( 'woocommerce_no_products_found' );
}
    ?>
<?php endif;



/**
 * Hook: woocommerce_after_main_content.
 *
 * @hooked woocommerce_output_content_wrapper_end - 10 (outputs closing divs for the content)
 */
do_action( 'woocommerce_after_main_content' );

/**
 * Hook: woocommerce_sidebar.
 *
 * @hooked woocommerce_get_sidebar - 10
 */
do_action( 'woocommerce_sidebar' );

get_footer( 'shop' );

?>


