<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Pet_Hamper
 */

?>


<?php if ( !is_home() || is_product_category( 'hampers' ) || term_is_ancestor_of( 83, get_queried_object_id(), 'product_cat' ) ): ?>

<?php else : ?>

<div id="hamperfooter">

	<div class="wrap">

		<div class="text">

			<p class="title">Looking for hampers?</p>
			<a href="<?php bloginfo('url') ?>/hampers/" class="button">Shop Hampers</a>

		</div>

		<div class="image">
			<img src="<?php bloginfo('url') ?>/wp-content/themes/pet-hamper/images/hamper-illustration.svg" alt="hampers">
		</div>


	</div>

</div>

<?php endif; ?>




	<footer id="footer" class="site-footer">

		<div id="topfooter">

			<div class="wrap">

			<img class="footer-illustration" src="<?php bloginfo('url') ?>/wp-content/themes/pet-hamper/images/footer-illustration.svg" alt="dog illustration">

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

			</div>

		</div>

		<div id="mainfooter">

			<div class="wrap row">

				<div class="col col1">
					<div class="social" style="display: flex;">
						<a href="https://www.facebook.com/pethamper" aria-label="Facebook" target="_blank" rel="noopener"><i class="fab fa-facebook"></i></a>
						<a href="https://www.instagram.com/pethamper/" aria-label="Instagram" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a>
						<a href="https://www.twitter.com/pet_hamper/" aria-label="Twitter" target="_blank" rel="noopener"><i class="fab fa-twitter"></i></a>
					</div>
					<span>Copyright &copy; <?php echo date('Y'); ?> <a href="<?php bloginfo('url') ?>" title="Pet Hamper" rel="home">Pet Hamper </a></br><a href="https://ribbledigital.co.uk/" rel="noopener">Web Design By Ribble Digital</a> | <a href="<?php bloginfo('url') ?>/sitemap/">Sitemap</a></span>
				</div>

				<div class="col col2">
					<?php
					if(is_active_sidebar('footercol1')){
					dynamic_sidebar('footercol1');
					}
					?>
				</div>
				<div class="col col3">
					<?php
					if(is_active_sidebar('footercol2')){
					dynamic_sidebar('footercol2');
					}
					?>
				</div>
				<div class="col col4">
					<?php
					if(is_active_sidebar('footercol3')){
					dynamic_sidebar('footercol3');
					}
					?>
				</div>

			</div>

		</div>


	</footer><!-- #colophon -->

</div><!-- #page -->




<?php wp_footer(); ?>


</body>
</html>
