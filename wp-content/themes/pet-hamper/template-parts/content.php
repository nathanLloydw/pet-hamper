<?php
/**
 * Template part for displaying posts
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package Pet_Hamper
 */

?>

<article class="article" id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

	<?php pet_hamper_post_thumbnail(); ?>
	
	<header class="entry-header">
		<?php
		if ( is_singular() ) :

		else :
			the_title( '<h2 class="linetitle center"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h2>' );
		endif;

		if ( 'post' === get_post_type() ) :
			?>
			<div class="entry-meta">
				<?php
				pet_hamper_posted_on();
				pet_hamper_posted_by();
				?>
			</div><!-- .entry-meta -->
		<?php endif; ?>
	</header><!-- .entry-header -->


	<div class="entry-content">
		<?php

        global $wp;

        $page = home_url( $wp->request );
        $content = null;

        if(str_contains($page,'/blog'))
        {
            $content = get_the_excerpt();
        }
        else
        {
            $content = get_the_content();
        }

        echo $content;

		wp_link_pages(
			array(
				'before' => '<div class="page-links">' . esc_html__( 'Pages:', 'pet-hamper' ),
				'after'  => '</div>',
			)
		);
		?>
	</div><!-- .entry-content -->



</article><!-- #post-<?php the_ID(); ?> -->
