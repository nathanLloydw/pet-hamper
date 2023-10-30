<?php

/***
* Template Name: Homepage
*/

get_header();

$total_rows = 0;
?>


	<main id="primary">

		<div class="hp_banner">

			<div>

			<?php if( have_rows('slides') ): ?>

			    <ul class="splide__list">
			    <?php while( have_rows('slides') ): the_row(); ?>

                <?php $total_rows = get_row_index() ?>
			        <li data-row="<?php echo $total_rows - 1; ?>" style="position: relative">
			        	<div class="image mobile-hide"><img class="skip-lazy" src="<?php echo get_sub_field('image') ?>"></div>
			        	<div class="image mobile-show"><img class="skip-lazy" src="<?php echo get_sub_field('mobile_image') ?>"></div>

			            <div class="content">
			            	<p class="title"><?php the_sub_field('title'); ?></p>
			            	<?php 
							$link = get_sub_field('link');
							if( $link ): 
							    $link_url = $link['url'];
							    $link_title = $link['title'];
							    $link_target = $link['target'] ? $link['target'] : '_self';
							    ?>
							    <a class="button ib" href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>"><?php echo $link_title; ?></a>
							<?php endif; ?>
			        	</div>

			        </li>
			    <?php endwhile; ?>

                    <?php if($total_rows > 1): ?>
                    <!-- slider controls -->
                    <div class="slider-controls">
                        <div class="scroll-left disabled" style="margin-left:20px;"><</div>
                        <div class="scroll-right" style="margin-right:20px;">></div>
                    </div>
                    <!-- slider controls -->
                    <?php endif; ?>

			    </ul>
			<?php endif; ?>

			</div><!-- slider -->
		
		</div><!-- slider -->


		<div id="content">

			<div class="featured-logo">
				
				<p>Featured in</p>

				<div class="image"><img <?php awesome_acf_responsive_image(get_field( 'featured_logo' ),'thumb-640','768px'); ?>  alt="featured in Tatler and Vogue" /></div>

			</div>

			<!-- Begin Mailchimp Signup Form -->
				<div id="mc_embed_signup">
				<form action="https://pethamper.us19.list-manage.com/subscribe/post?u=4a3d1da4f810f2b45d4df21f4&amp;id=9a6ed2604c" method="post" id="mc-embedded-subscribe-form" name="mc-embedded-subscribe-form" class="validate" target="_blank" novalidate>
					<p style="color: #d0c5a5;font-size: 1.5em;">Get 10% off</p>
					<p>your first order. Subscribe now!</p>
					<div class="inputs">
						<input style="text-transform: uppercase;" type="email" value="" name="EMAIL" class="email" id="mce-EMAIL" placeholder="email" required>
				    	<div style="position: absolute; left: -5000px;" aria-hidden="true"><input type="text" name="b_4a3d1da4f810f2b45d4df21f4_9a6ed2604c" tabindex="-1" value=""></div>
				    	<input type="submit" value="Subscribe" name="subscribe" id="mc-embedded-subscribe" color="chino" class="button">

				    </div>
				</form>
				</div>

				<!--End mc_embed_signup-->

		

<!-- 
		<?php 
		$logos = get_field('featured_logo_gallery');
		$size = 'full'; // (thumbnail, medium, large, full or custom size)
		if( $logos ): ?>
		    <ul class="featured-logos">
		        <?php foreach( $logos as $logo_id ): ?>
		            <li>
		                <?php echo wp_get_attachment_image( $logo_id, $size ); ?>
		            </li>
		        <?php endforeach; ?>
		    </ul>
		<?php endif; ?> -->



			<?php if( have_rows('seasonal_block') ): ?>

			<p class="linetitle">Featured</p>

		    <div class="catgrid half seasonal">
		    <?php while( have_rows('seasonal_block') ): the_row();
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
					    	<div class="image mobile-hide"><img class="bannerimg" <?php awesome_acf_responsive_image(get_sub_field( 'image' ),'full','1200px'); ?>  alt="" /></div>
					    	<div class="image mobile-show"><img class="bannerimg" <?php awesome_acf_responsive_image(get_sub_field( 'mobile_image' ),'thumb-640','768px'); ?>  alt="" /></div>
					    	 <div class="content">
				            	<p class="title"><?php echo $link_title; ?></p>
				        	</div>
					    </a>

			        	<?php endif; ?>

			        </div>
					
				</div>
				
			<?php endwhile; ?>
			</div>
			<?php endif; ?>


			<h1 class="linetitle"><?php the_title(); ?></h1>


			<?php if( have_rows('catgrid') ): ?>

		    <div class="catgrid half">

		    <?php while( have_rows('catgrid') ): the_row(); 

		    	$link = get_sub_field('link');

		    	?>

				<div class="griditem fw category-grid-desktop">

					<div class="inner">

						<?php if( $link ): 
					    $link_url = $link['url'];
					    $link_title = $link['title'];
					    $link_target = $link['target'] ? $link['target'] : '_self';
					    ?>

						<a href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>">
					    	<div class="image mobile-hide"><img class="bannerimg" <?php awesome_acf_responsive_image(get_sub_field( 'image' ),'full','1200px'); ?>  alt="" /></div>
					    	<div class="image mobile-show"><img class="bannerimg" <?php awesome_acf_responsive_image(get_sub_field( 'mobile_image' ),'thumb-640','768px'); ?>  alt="" /></div>
					    	 <div class="content">
				            	<p class="title"><?php echo $link_title; ?></p>
				        	</div>
					    </a>
			            
			          <!--   <div class="content">
			            	<p class="title"><?php the_sub_field('title'); ?></p>
			            	<p><?php the_sub_field('paragraph'); ?></p>
			            	
							<a class="button ib" href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>"><?php echo $link_title; ?></a>
							
			        	</div>
 -->



			        	<?php endif; ?>

			        </div>
					
				</div>
				
			<?php endwhile; ?>
			</div>

            <div class="category-grid-mobile">
                <?php while( have_rows('catgrid') ): the_row();

                    $link = get_sub_field('link');

                    ?>

                    <div>

                        <?php if( $link ):
                            $link_url = $link['url'];
                            $link_title = $link['title'];
                            $link_target = $link['target'] ? $link['target'] : '_self';
                            ?>

                            <a style="position:relative;display:block;height:100%;width:100%;" href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>">
                                <img <?php awesome_acf_responsive_image(get_sub_field( 'mobile_image' ),'thumb-640','768px'); ?>  style="width:100%; object-fit:cover;display:flex;" alt="" />
                                <div style="position:absolute;top:0;left:0;display:flex;justify-content:center;align-items:center;width:100%;height:100%;max-height:767px;background-color:#00000040;">
                                    <p style="color:white;text-transform:uppercase;"><?php echo $link_title; ?></p>
                                </div>
                            </a>

                        <?php endif; ?>

                    </div>

                <?php endwhile; ?>
            </div>

            <style>
                #tns1-mw { margin: 0 -20px; }

                .category-grid-mobile { overflow:visible; }

                .category-grid-desktop { display:block; }
                .category-grid-mobile { display: none; }

                @media (max-width: 767px)
                {
                    .category-grid-mobile { display: block; }
                    .category-grid-desktop { display:none; }
                }

            </style>

            <script type="module">

                var slider = tns({
                    container: '.category-grid-mobile',
                    items: 2,
                    center:true,
                    loop:true,
                    mouseDrag:true,
                    startIndex:2,
                    controls:false,
                    nav:false,
                    autoplay: false,
                    preventScrollOnTouch:"auto"
                });

            </script>

			<?php endif; ?>


			<div class="welcometext center">
				<?php the_field('welcome_text'); ?>
			</div>


			


			<?php if( have_rows('collections_grid') ): ?>
			<h2 class="linetitle">Collections</h2>
		    <ul class="collections-grid">
		    <?php while( have_rows('collections_grid') ): the_row(); 
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
			
			<h1 class="linetitle">Bespoke Hampers</h1>


			<div class="bespoke-hampers">
				<div class="image"><img class="bannerimg" <?php awesome_acf_responsive_image(get_field( 'bespoke_hamper_image' ),'thumb-640','1200px'); ?>  alt="create your own hamper" /></div>
				<a href="<?php bloginfo('url') ?>/create-your-own-hamper/" class="button ib">Create your hamper now</a>
			</div>

		


			<!-- <div class="collection-slider splide">

			<div class="splide__track">

			<?php if( have_rows('collections_slider') ): ?>
			    <ul class="splide__list">
			    <?php while( have_rows('collections_slider') ): the_row(); 
			        ?>
			        <li class="splide__slide">

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

			</div>
		
		</div> -->

		<?php 
		$corplogos = get_field('corporate_logo_gallery');
		$size = 'full'; // (thumbnail, medium, large, full or custom size)
		if( $corplogos ): ?>
			<p class="linetitle"><?php the_field('corporate_title'); ?></p>
		    <ul class="corporate-logos">
		        <?php foreach( $corplogos as $corplogo_id ): ?>
		            <li>
		                <?php echo wp_get_attachment_image( $corplogo_id, $size ); ?>
		            </li>
		        <?php endforeach; ?>
		    </ul>
		    <div class="more center">
		    	<p><?php the_field('corporate_text'); ?></p> 

		    	<?php 
					$link = get_field('link');
					if( $link ): 
					    $link_url = $link['url'];
					    $link_title = $link['title'];
					    $link_target = $link['target'] ? $link['target'] : '_self';
					    ?>
					    <a class="button ib" href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>"><?php echo $link_title ?></a>
					    
					<?php endif; ?>
		    </div>

		<?php endif; ?>


		
			<div class="about-section">

				<p class="linetitle"><?php the_field('about_title'); ?></p>

				<div class="row">

					<div class="col1">

						<div class="image"><img <?php awesome_acf_responsive_image(get_field( 'about_image' ),'thumb-640','768px'); ?>  alt="our product tester" /></div>
							
					</div>

					<div class="col2">

						<div class="content">
							<span class="quote"><?php the_field('quote'); ?></span>
			            	<p class="cta"><?php the_field('cta_text'); ?></p>
			            	<?php 
							$link = get_field('cta_link');
							if( $link ): 
							    $link_url = $link['url'];
							    $link_title = $link['title'];
							    $link_target = $link['target'] ? $link['target'] : '_self';
							    ?>
							    <a class="button ib" href="<?php echo esc_url( $link_url ); ?>" target="<?php echo esc_attr( $link_target ); ?>"><?php echo $link_title; ?></a>
							<?php endif; ?>
			        	</div>
						
					</div>	

				</div>	
				  		
			</div><!-- about section -->



		</div><!-- content -->

		
		<p class="linetitle">On Instagram</p>

		<?php echo do_shortcode('[instagram-feed feed=1]'); ?>


	</main><!-- #main -->


<?php
get_footer();
