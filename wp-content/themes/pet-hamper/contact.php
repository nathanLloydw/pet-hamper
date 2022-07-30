<?php

/***
* Template Name: Contact
*/

get_header();
?>


	<main id="primary" class="site-main center wrap">

		<section>

			<img style="width: 700px;" src="<?php bloginfo('url') ?>/wp-content/themes/pet-hamper/images/contact.png" alt="contact image">
			
			<header class="page-header">
				<h1 class="entry-title">We're here to help!</h1>
			</header><!-- .page-header -->

			<div class="page-content">

				<div class="col">

					<h2><i class="fab fa-wpforms"></i>Contact Form</h2>

					<?php echo do_shortcode('[gravityform id="1" title="false"]') ?>
					
				</div>


				<div class="col">

					<div class="el">

						<h2><i class="fal fa-envelope"></i>Email</h2>

						<a href="mailto:<?php the_field('email'); ?>"><p class="title"><?php the_field('email'); ?></p></a>

					</div>

					<div class="el">

						<h2><i class="fal fa-mobile"></i>Phone</h2>

						<a href="tel:<?php the_field('phone'); ?>"><p class="title"><?php the_field('phone'); ?></p></a>

					</div>

					<div class="el">

						<h2><i class="fal fa-clock"></i>Opening Times</h2>

						<p class="title"><?php the_field('opening_times'); ?></p>

						<p><?php the_field('additional_text'); ?></p>

					</div>

				</div>
				
			</div><!-- .page-content -->

			<h2 style="text-transform: none;"><i style="margin-right: 5px;" class="fal fa-map-marker"></i><?php the_field('address'); ?></h2>
			<p><?php the_field('trading_text'); ?></p>

		</section><!-- .error-404 -->

	</main><!-- #main -->

<?php
get_footer();
