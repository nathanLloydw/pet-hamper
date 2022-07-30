<?php

/***
* Template Name: Create Hamper Main
*/

get_header();
?>


	<main id="primary">

		<div id="content">

	<!-- 		<div class="intro-banner center">

				<?php the_post_thumbnail(); ?>

				<div class="content">
					<h1 class="entry-title"><?php the_title(); ?></h1>
				</div>
				
			</div>
			
			<br> -->

			<h1 class="entry-title center"><?php the_title(); ?></h1>

			<p class="center"><?php the_field('intro_paragraph'); ?></p>

			<?php if( have_rows('hampergrid') ): ?>

			<h2 class="linetitle">Choose who you are shopping for</h2>
		    <div class="catgrid half">
		    <?php while( have_rows('hampergrid') ): the_row(); 

		    	$link = get_sub_field('link');

		    	?>

				<div class="griditem">

					<div class="inner">

						<?php if( $link ): 
					    $link_url = $link['url'];
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

		




		</div><!-- content -->


	</main><!-- #main -->


<?php
get_footer();
