<?php
/**
 * The template for displaying 404 pages (not found)
 *
 * @link https://codex.wordpress.org/Creating_an_Error_404_Page
 *
 * @package Pet_Hamper
 */

get_header();
?>

	<main id="primary" class="site-main center wrap">

		<section class="error-404 not-found">
			<header class="page-header">
				<p class="linetitle">404</p>
				<h1 class="page-title"><span class="title">Oops!</span> Looks like we chewed up the powercord</h1>
			</header><!-- .page-header -->

			<div class="page-content">

				<img src="<?php bloginfo('url') ?>/wp-content/themes/pet-hamper/images/404.png" alt="404 image">

				<p>Go back to the homepage to continue your visit</p>
				<a href="<?php bloginfo('url') ?>" class="button ib">Back to Homepage</a>
				
			</div><!-- .page-content -->
		</section><!-- .error-404 -->

	</main><!-- #main -->

<?php
get_footer();
