<?php

get_header();
?>

	<main id="primary" class="site-main wrap">

		<?php
		if ( have_posts() ) :?>

				<header>
					<h1 class="page-title screen-reader-text"><?php single_post_title(); ?></h1>
				</header>

		<?php
	

			/* Start the Loop */
			while ( have_posts() ) :
				the_post();

				/*
				 * Include the Post-Type-specific template for the content.
				 * If you want to override this in a child theme, then include a file
				 * called content-___.php (where ___ is the Post Type name) and that will be used instead.
				 */
				get_template_part( 'template-parts/content', get_post_type() );

			endwhile;

			the_posts_navigation();

		else :

			get_template_part( 'template-parts/content', 'none' );

		endif;
		?>


		<?php if( have_rows('flexible_layout') ): ?>

        <?php while( have_rows('flexible_layout') ): the_row(); ?>

            <?php if( get_row_layout() == 'video_fw' ): ?>
              
	         <div class="embed-container wrap">
			    <?php the_sub_field('video'); ?>
			</div>

			<?php elseif( get_row_layout() == 'text_area_fw' ):?>

              <div class="fw-section wrap"> 
           
                 <?php the_sub_field('text_area'); ?>

              </div> 


              <?php elseif( get_row_layout() == 'featured_right_block' ):
              
               $link = get_sub_field('link');
			        ?>
		        <div class="wrap">

			        <section class="section featured-service right">

			        	<div class="col content">

			        		<div class="inner">

				            	<?php the_sub_field('content'); ?>

				           		<?php if( $link ): 
			                          $link_url = $link['url'];
			                          $link_title = $link['title'];
			                          $link_target = $link['target'] ? $link['target'] : '_self';
			                          ?>
			                           <a class="button ib" href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_attr( $link['title'] ); ?></a>
			                   									                          
			                      <?php endif; ?>
				           	</div>
				        		
			        	</div>

			        	<div class="col image">

			        		<?php 
							$image = get_sub_field('image');
							$size = 'large'; // (thumbnail, medium, large, full or custom size)
							if( $image ) {
							    echo wp_get_attachment_image( $image, $size );
							}; ?>

			        	</div>

			        </section>

			   </div>


		<?php elseif( get_row_layout() == 'featured_left_block' ):
              
               $link = get_sub_field('link');
			        ?>
		        <div class="wrap">

			        <section class="section featured-service left">

			        	<div class="col image">

			        		<?php 
							$image = get_sub_field('image');
							if( !empty( $image ) ): ?>
							    <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" />
							<?php endif; ?>

			        		
			        	</div>

			        	<div class="col content">

			        		<div class="inner">

				            	<?php the_sub_field('content'); ?>

				           		<?php if( $link ): 
			                          $link_url = $link['url'];
			                          $link_title = $link['title'];
			                          $link_target = $link['target'] ? $link['target'] : '_self';
			                          ?>
			                           <a class="button ib" href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_attr( $link['title'] ); ?></a>
			                   									                          
			                      <?php endif; ?>
				           	</div>
				        		
			        	</div>


			        </section>

			   </div>

			   <?php elseif( get_row_layout() == 'collections_grid' ):?>

			   	<h2 class="linetitle"><?php the_sub_field('title'); ?></h2>


				<?php if( have_rows('collections_grid') ): ?>


				    <ul class="collections-grid archive">

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






             <?php elseif( get_row_layout() == 'category_grid' ):?>

             	<h2 class="linetitle"><?php the_sub_field('title'); ?></h2>
          
               
				<?php if( have_rows('category_grid') ): ?>



				<div class="catgrid archive">

				<?php while( have_rows('category_grid') ): the_row(); 

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


                <?php elseif( get_row_layout() == 'grid_images' ): ?>


		            <div class="wrap">

			            <section class="section grid-links">

				            <h2><?php the_sub_field('heading'); ?></h2>

										<?php if( have_rows('rows') ): ?>
										    <ul class="grid">
										    <?php while( have_rows('rows') ): the_row(); 
										    	 $link = get_sub_field('link');
										    	 ?>
										        <li>
										        	<?php 
													$image = get_sub_field('image');
													if( !empty( $image ) ): ?>
													    <img src="<?php echo esc_url($image['url']); ?>" alt="<?php echo esc_attr($image['alt']); ?>" />
													<?php endif; ?>
										        	<div class="inner">
												        <p class="sub small"><?php the_sub_field('subtitle'); ?></p>
												        <p class="title"><?php the_sub_field('title'); ?></p>
												   		<?php the_sub_field('paragraph'); ?>
												   		<?php if( $link ): 
									                          $link_url = $link['url'];
									                          $link_title = $link['title'];
									                          $link_target = $link['target'] ? $link['target'] : '_self';
									                          ?>
									                           <a class="button ib" href="<?php echo esc_url( $link['url'] ); ?>"><?php echo esc_attr( $link['title'] ); ?></a>
									                   									                          
									                      <?php endif; ?>
												   		
											   		</div>
										    	</li>
										    <?php endwhile; ?>
										    </ul>
										<?php endif; ?>

				        </section>

				    </div>

                
              <?php endif; ?>

          <?php endwhile; ?>

      <?php endif; ?>

	</main><!-- #main -->

<?php
get_footer();
