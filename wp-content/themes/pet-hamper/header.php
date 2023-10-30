<?php
/**
 * The header for our theme
 *
 * This is the template that displays all of the <head> section and everything up until <div id="content">
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package Pet_Hamper
 */

?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <link rel="preload" href="/wp-content/themes/pet-hamper/fonts/lemonade.woff2" as="font" type="font/woff2" crossorigin>

    <script src="https://kit.fontawesome.com/6fbd7ca2dd.js" ></script>
    <script src="https://kit.fontawesome.com/713e0a7437.js" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="/wp-content/themes/pet-hamper/css/hc-offcanvas-nav.css">
    <script defer src="/wp-content/themes/pet-hamper/js/hc-offcanvas-nav.min.js"></script>
    <script defer src="/wp-content/themes/pet-hamper/js/navigation.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/cookieconsent@3/build/cookieconsent.min.css" />
    <meta name="google-site-verification" content="3x-9FYFFkK_1jqpY8RDE-r2kzJWF_26c0GEAj6WMV8s" />

    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<?php wp_body_open(); ?>
<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e( 'Skip to content', 'pet-hamper' ); ?></a>

    <div id="topbar">

        <div class="wrap dflex">

            <div class="col"><?php the_field('top_bar', 'option'); ?></div>

        </div>

    </div>

    <header id="masthead" role="banner">

        <div class="wrap mainheader">

            <div class="naigationwrapper mobile-show">

                <div class="hamburger mobile-show"><i style="font-size:30px" class="fas fa-bars"></i></div> <!--  .hamburger -->


                <div class="search mobile-show">
                    <span><?php echo do_shortcode('[fibosearch]'); ?></span>
                </div>

                <nav id="site-navigation" class="main-navigation mobile-show" style="display: none !important;">
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'mobile-menu',
                        'menu_id'        => 'mobile-menu',
                    )
                );
                ?>
                </nav><!-- #site-navigation -->

            </div> <!--  .naigationwrapper -->

            <div class="logo" itemscope itemtype="http://schema.org/Organization" style="text-align: center;">
                <span class="screen-reader-text">Pet Hamper</span>
                <a href="<?php bloginfo('url') ?>">
                    <img style="width:180px;" src="<?php bloginfo('url') ?>/wp-content/themes/pet-hamper/images/white-logo-bone-large.png">
                </a>

            </div>

            <div class="search mobile-hide">
                <span><?php echo do_shortcode('[fibosearch]'); ?></span>
            </div>

            <div class="links">

                <a class="my-account" href="/my-account/" title="My Account"><i class="fal fa-user"></i></a>

                <?php if (function_exists('WC')) {
                $cart_items = WC()->cart->get_cart_contents_count(); ?>
                <a class="cart-header" href="<?php bloginfo('url') ?>/basket/" title="Basket"><i class="fal fa-shopping-basket"></i>
                    <div class="cart-number number"><span><?php echo $cart_items ?></span></div>
                </a>
            <?php } ?>

            </div>

        </div>

        <nav id="site-navigation" class="main-navigation mobile-hide">

            <div class="wrap">
                <?php
                wp_nav_menu(
                    array(
                        'theme_location' => 'menu-1',
                        'menu_id'        => 'primary-menu',
                    )
                );
                ?>
            </div>
        </nav><!-- #site-navigation -->


    </header><!-- #masthead -->

    <?php if( get_field('announcement_bar_text', 'option') ): ?>

    <div id="offerbar">

        <div class="wrap dflex">

            <div class="col"><?php the_field('announcement_bar_text', 'option'); ?></div>

        </div>

    </div>

    <?php endif; ?>

