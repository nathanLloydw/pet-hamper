<?php
/**
 * Pet Hamper functions and definitions
 *
 * @link https://developer.wordpress.org/themes/basics/theme-functions/
 *
 * @package Pet_Hamper
 */

if ( ! defined( '_S_VERSION' ) ) {
    // Replace the version number of the theme on each release.
    define( '_S_VERSION', '1.0.0' );
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function pet_hamper_setup() {
    /*
        * Make theme available for translation.
        * Translations can be filed in the /languages/ directory.
        * If you're building a theme based on Pet Hamper, use a find and replace
        * to change 'pet-hamper' to the name of your theme in all the template files.
        */
    load_theme_textdomain( 'pet-hamper', get_template_directory() . '/languages' );

    // Add default posts and comments RSS feed links to head.
    add_theme_support( 'automatic-feed-links' );

    /*
        * Let WordPress manage the document title.
        * By adding theme support, we declare that this theme does not use a
        * hard-coded <title> tag in the document head, and expect WordPress to
        * provide it for us.
        */
    add_theme_support( 'title-tag' );

    /*
        * Enable support for Post Thumbnails on posts and pages.
        *
        * @link https://developer.wordpress.org/themes/functionality/featured-images-post-thumbnails/
        */
    add_theme_support( 'post-thumbnails' );

    // This theme uses wp_nav_menu() in one location.
    register_nav_menus(
        array(
            'menu-1' => esc_html__( 'Primary', 'pet-hamper' ),
            'mobile-menu' => esc_html__( 'Mobile Menu', 'pet-hamper' ),
        )
    );

    /*
        * Switch default core markup for search form, comment form, and comments
        * to output valid HTML5.
        */
    add_theme_support(
        'html5',
        array(
            'search-form',
            'comment-form',
            'comment-list',
            'gallery',
            'caption',
            'style',
            'script',
        )
    );

    // Set up the WordPress core custom background feature.
    add_theme_support(
        'custom-background',
        apply_filters(
            'pet_hamper_custom_background_args',
            array(
                'default-color' => 'ffffff',
                'default-image' => '',
            )
        )
    );

    // Add theme support for selective refresh for widgets.
    add_theme_support( 'customize-selective-refresh-widgets' );

    /**
     * Add support for core custom logo.
     *
     * @link https://codex.wordpress.org/Theme_Logo
     */
    add_theme_support(
        'custom-logo',
        array(
            'height'      => 250,
            'width'       => 250,
            'flex-width'  => true,
            'flex-height' => true,
        )
    );
}
add_action( 'after_setup_theme', 'pet_hamper_setup' );

/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function pet_hamper_content_width() {
    $GLOBALS['content_width'] = apply_filters( 'pet_hamper_content_width', 640 );
}
add_action( 'after_setup_theme', 'pet_hamper_content_width', 0 );

/**
 * Register widget area.
 *
 * @link https://developer.wordpress.org/themes/functionality/sidebars/#registering-a-sidebar
 */
function pet_hamper_widgets_init() {
    register_sidebar(
        array(
            'name'          => esc_html__( 'Sidebar', 'pet-hamper' ),
            'id'            => 'sidebar-1',
            'description'   => esc_html__( 'Add widgets here.', 'pet-hamper' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h2 class="widget-title">',
            'after_title'   => '</h2>',
        )
    );
    register_sidebar(
        array(
            'name'          => esc_html__( 'Footer Col 1', 'pet-hamper' ),
            'id'            => 'footercol1',
            'description'   => esc_html__( 'Add widgets here.', 'pet-hamper' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h4 class="footercol-title">',
            'after_title'   => '</h4>',
        )
    );
    register_sidebar(
        array(
            'name'          => esc_html__( 'Footer Col 2', 'pet-hamper' ),
            'id'            => 'footercol2',
            'description'   => esc_html__( 'Add widgets here.', 'pet-hamper' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h4 class="footercol-title">',
            'after_title'   => '</h4>',
        )
    );
    register_sidebar(
        array(
            'name'          => esc_html__( 'Footer Col 3', 'pet-hamper' ),
            'id'            => 'footercol3',
            'description'   => esc_html__( 'Add widgets here.', 'pet-hamper' ),
            'before_widget' => '<section id="%1$s" class="widget %2$s">',
            'after_widget'  => '</section>',
            'before_title'  => '<h4 class="footercol-title">',
            'after_title'   => '</h4>',
        )
    );
}
add_action( 'widgets_init', 'pet_hamper_widgets_init' );

/**
 * Enqueue scripts and styles.
 */
function pet_hamper_scripts() {
    wp_enqueue_style( 'pet-hamper-style', get_stylesheet_uri(), array(), _S_VERSION );
    wp_style_add_data( 'pet-hamper-style', 'rtl', 'replace' );

    wp_enqueue_script( 'pet-hamper-custom', get_template_directory_uri() . '/js/custom-scripts.js', array(), _S_VERSION, true );

    if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
        wp_enqueue_script( 'comment-reply' );
    }
}
add_action( 'wp_enqueue_scripts', 'pet_hamper_scripts' );

/**
 * Implement the Custom Header feature.
 */
require get_template_directory() . '/inc/custom-header.php';

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Functions which enhance the theme by hooking into WordPress.
 */
require get_template_directory() . '/inc/template-functions.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
if ( defined( 'JETPACK__VERSION' ) ) {
    require get_template_directory() . '/inc/jetpack.php';
}



// enable woocommerce support in theme
add_action( 'after_setup_theme', 'woocommerce_support' );
function woocommerce_support() {
   add_theme_support( 'woocommerce' );
}     

// disable default styling
// if (class_exists('Woocommerce')){
//     add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );
// }


add_filter( 'woocommerce_checkout_fields', 'misha_email_first' );

function misha_email_first( $checkout_fields ) {
    $checkout_fields['billing']['billing_email']['priority'] = 4;
    return $checkout_fields;
}


add_filter( 'woocommerce_checkout_fields', 'swap_billing_shipping_order' );

function swap_billing_shipping_order( $checkout_fields ) {
    $checkout_fields['shipping']['priority'] = 1;
    return $checkout_fields;
}

function disable_woo_commerce_sidebar() {
    remove_action( 'woocommerce_sidebar', 'woocommerce_get_sidebar', 10); 
}
add_action('init', 'disable_woo_commerce_sidebar');


// remove standard woocommerce product page tabs
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );

function woocommerce_template_product_description() {
    if (get_the_content()) {
        wc_get_template( 'single-product/tabs/description.php' );
    }
}
add_action( 'woocommerce_after_single_product_summary', 'woocommerce_template_product_description', 10 );

// move short description under cart buttons
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 ); add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 15 );



// display an 'Out of Stock' label on archive pages
add_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_stock', 10 );
function woocommerce_template_loop_stock() {
    global $product;
    if ( ! $product->is_in_stock() && ! $product->backorders_allowed() )
        echo '<p class="stock out-of-stock">Sold Out</p>';
}


// Change the Product Description Title
add_filter('woocommerce_product_description_heading', 'hjs_product_description_heading');
function hjs_product_description_heading() {
 return __('More Information', 'woocommerce');
}


 /**
 * Sorting out of stock WooCommerce products - Order product collections by stock status, in-stock products first.
 */
// class iWC_Orderby_Stock_Status
// {
//     public function __construct()
//     {
//         // Check if WooCommerce is active
//         if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
//         {
//            add_filter('posts_clauses', array($this, 'order_by_stock_status'), 9999);
//         }
//     }
//
//     public function order_by_stock_status($posts_clauses)
//     {
//         global $wpdb;
//         // only change query on WooCommerce loops
//         if (is_woocommerce() && is_product_category('modern-pup')) {
//         $posts_clauses['join'] .= " INNER JOIN $wpdb->postmeta istockstatus ON ($wpdb->posts.ID = istockstatus.post_id) ";
//         $posts_clauses['orderby'] = " istockstatus.meta_value ASC, " . $posts_clauses['orderby'];
//         $posts_clauses['where'] = " AND istockstatus.meta_key = '_stock_status' AND istockstatus.meta_value <> '' " . $posts_clauses['where'];
//         }
//         return $posts_clauses;
//     }
// }
// new iWC_Orderby_Stock_Status;
 /**
 * END - Order product collections by stock status, instock products first.
 */

// remove the cart buttons from out of stock buttons
function ace_remove_out_of_stock_product_button( $html, $product, $args ) {
    if ( ! $product->is_in_stock() && ! $product->backorders_allowed() ) {
        return '';
    }

    return $html;
}
add_filter( 'woocommerce_loop_add_to_cart_link', 'ace_remove_out_of_stock_product_button', 10, 3 );


// QTY Plus Minus
add_action( 'woocommerce_before_add_to_cart_quantity', 'mystore_display_quantity_plus' );
 
function mystore_display_quantity_plus() {
   echo '<button type="button" class="plus" >+</button>';
}
 
add_action( 'woocommerce_after_add_to_cart_quantity', 'mystore_display_quantity_minus' );
 
function mystore_display_quantity_minus() {
   echo '<span class="label">Quanitity</span><button type="button" class="minus" >-</button>';
}

add_action( 'wp_footer', 'mystore_add_cart_quantity_plus_minus' );
 
function mystore_add_cart_quantity_plus_minus() {
   // Only run this on the single product page
   if ( ! is_product() ) return;
?>
<script type="text/javascript">
 
jQuery(document).ready(function($)
{
$('form.cart').on( 'click', 'button.plus, button.minus', function()
{
// Get current quantity values
   var qty = $( this ).closest( 'form.cart' ).find( '.qty' );
   if(qty.length > 1)
   {
       qty = $( this ).closest( 'form.cart' ).find( '.input-text.qty' );
   }
   var val = parseFloat(qty.val());
   var max = parseFloat(qty.attr( 'max' ));
   var min = parseFloat(qty.attr( 'min' ));
   var step = parseFloat(qty.attr( 'step' ));
 
// Change the value if plus or minus
   if ( $( this ).is( '.plus' ) ) {
      if ( max && ( max <= val ) ) {
         qty.val( max );
      } else {
         qty.val( val + step );
      }
   } else {
      if ( min && ( min >= val ) ) {
         qty.val( min );
      } else if ( val > 1 ) {
         qty.val( val - step );
      }
   }
});
 
});
 
</script>
<?php
}


// add_action('woocommerce_before_quantity_input_field', 'wc_text_before_quantity');

// function wc_text_before_quantity() {
//     if ( is_product()) {
//         echo 'Quantity';
//     }
// }


// move description to under products on archive pages
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
add_action( 'woocommerce_after_shop_loop', 'woocommerce_taxonomy_archive_description', 100 );





add_filter( 'gettext', 'bt_rename_coupon_field_on_cart', 10, 3 );
add_filter( 'woocommerce_coupon_error', 'bt_rename_coupon_label', 10, 3 );
add_filter( 'woocommerce_coupon_message', 'bt_rename_coupon_label', 10, 3 );
add_filter( 'woocommerce_cart_totals_coupon_label', 'bt_rename_coupon_label',10, 1 );
add_filter( 'woocommerce_checkout_coupon_message', 'bt_rename_coupon_message_on_checkout' );


function bt_rename_coupon_field_on_cart( $translated_text, $text, $text_domain ) {
    // bail if not modifying frontend woocommerce text
    if ( is_admin() || 'woocommerce' !== $text_domain ) {
        return $translated_text;
    }
    if ( 'Coupon:' === $text ) {
        $translated_text = 'Discount Code:';
    }

    if ('Coupon has been removed.' === $text){
        $translated_text = 'Discount code has been removed.';
    }

    if ( 'Apply coupon' === $text ) {
        $translated_text = 'Apply';
    }

    if ( 'Coupon code' === $text ) {
        $translated_text = 'Discount Code';
    
    } 

    return $translated_text;
}


// Rename the "Have a Coupon?" message on the checkout page
function bt_rename_coupon_message_on_checkout() {
    return 'Have a coupon code?' . ' ' . __( 'Click here to enter your code', 'woocommerce' ) . '';
}


function bt_rename_coupon_label( $err, $err_code=null, $something=null ){
    $err = str_ireplace("Coupon","Discount Code ",$err);
    return $err;
}





add_filter('get_the_archive_title', function ($title) {
    if (is_category()) {
        $title = single_cat_title('', false);
    } elseif (is_tag()) {
        $title = single_tag_title('', false);
    } elseif (is_author()) {
        $title = '<span class="vcard">' . get_the_author() . '</span>';
    } elseif (is_tax()) { //for custom post types
        $title = sprintf(__('%1$s'), single_term_title('', false));
    } elseif (is_post_type_archive()) {
        $title = post_type_archive_title('', false);
    }
    return $title;
});




// rename the "Have a Coupon?" message on the checkout page
function woocommerce_rename_coupon_message_on_checkout() {

    return 'Have a Discount Code?' . ' <a href="#" class="showcoupon">' . __( 'Click here to enter your code', 'woocommerce' ) . '</a>';
}
add_filter( 'woocommerce_checkout_coupon_message', 'woocommerce_rename_coupon_message_on_checkout' );




// Auto update cart items after add to basket
add_filter( 'woocommerce_add_to_cart_fragments', 'iconic_cart_count_fragments', 10, 1 );

function iconic_cart_count_fragments( $fragments ) {
    
    $fragments['div.cart-number'] = '<div class="cart-number number"><span>' . WC()->cart->get_cart_contents_count() . '</span></div>';
    
    return $fragments;
    
}












// Register Custom Taxonomy
function product_brand() {
    $labels = array(
      'name'                       => _x( 'Product Brands', 'Taxonomy General Name', 'brand-mu-plugin' ),
      'singular_name'              => _x( 'Product Brand', 'Taxonomy Singular Name', 'brand-mu-plugin' ),
      'menu_name'                  => __( 'Brands', 'brand-mu-plugin' ),
      'all_items'                  => __( 'All Brands', 'brand-mu-plugin' ),
      'parent_item'                => __( 'Parent Brand', 'brand-mu-plugin' ),
      'parent_item_colon'          => __( 'Parent Brand:', 'brand-mu-plugin' ),
      'new_item_name'              => __( 'New Brand Name', 'brand-mu-plugin' ),
      'add_new_item'               => __( 'Add New Brand', 'brand-mu-plugin' ),
      'edit_item'                  => __( 'Edit Brand', 'brand-mu-plugin' ),
      'update_item'                => __( 'Update Brand', 'brand-mu-plugin' ),
      'view_item'                  => __( 'View Brand', 'brand-mu-plugin' ),
      'separate_items_with_commas' => __( 'Separate brands with commas', 'brand-mu-plugin' ),
      'add_or_remove_items'        => __( 'Add or remove brands', 'brand-mu-plugin' ),
      'choose_from_most_used'      => __( 'Choose from the most used', 'brand-mu-plugin' ),
      'popular_items'              => __( 'Popular Brands', 'brand-mu-plugin' ),
      'search_items'               => __( 'Search Brands', 'brand-mu-plugin' ),
      'not_found'                  => __( 'Not Found', 'brand-mu-plugin' ),
    );

    $rewrite = array(
      'slug'         => _x( 'product-brand', 'Taxonomy slug', 'brand-mu-plugin' ),
      'with_front'   => true,
      'hierarchical' => false,
    );

    $capabilities = array(
      'manage_terms' => 'manage_product_terms',
      'edit_terms'   => 'edit_product_terms',
      'delete_terms' => 'delete_product_terms',
      'assign_terms' => 'assign_product_terms',
    );

    $args = array(
      'labels'            => $labels,
      'hierarchical'      => true,
      'public'            => true,
      'show_ui'           => true,
      'query_var'         => true,
      'show_admin_column' => true,
      'show_in_nav_menus' => true,
      'show_tagcloud'     => true,
      'rewrite'           => $rewrite,
      'capabilities'      => $capabilities,
    );

    register_taxonomy( 'product-brand', array( 'product' ), $args );
  }


add_action( 'init', 'product_brand');



/**
 * Remove related products output
 */
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );




if( function_exists('acf_add_options_page') ) {
    
    acf_add_options_page(array(
        'page_title'    => 'Announcement Bar',
        'menu_title'    => 'Announcement Bar',
        'menu_slug'     => 'announcement-bar',
        'capability'    => 'edit_posts',
        'redirect'      => false
    ));
    
}

// // add AWIN tracking
 add_action( 'woocommerce_thankyou', 'my_custom_tracking' );

 function my_custom_tracking( $order_id ) {

     // Lets grab the order
     $order = wc_get_order( $order_id );

     echo "<script>
     dataLayer = [{
     'transactionTotal': '".$order->get_total()."',
     'transactionCurrency': '".$order->get_currency()."',
     'transactionID': '".$order_id."',
     'transactionPromoCode': '".implode(",",$order->get_coupon_codes())."'
     }];
     </script>";

     echo "<script>
     dataLayer = [{
     'transactionTotal': '".$order->get_total()."',
     'transactionCurrency': '".$order->get_currency()."',
     'transactionID': '".$order_id."',
     'transactionPromoCode': '".implode(",",$order->get_coupon_codes())."',
     'event': 'awin.dl.ready'
     }];
     </script>";
     
     // This is the order total
     $order->get_total();
 
     // This is how to grab line items from the order
     $line_items = $order->get_items();

     // This loops over line items
     foreach ( $line_items as $item ) {
         // This will be a product

         $product = $item->get_product();
  
         // This is the products SKU
         $sku = $product->get_sku();
        
         // This is the qty purchased
         $qty = $item['qty'];
        
         // Line item total cost including taxes and rounded
         $total = $order->get_line_total( $item, true, true );
        
         // Line item subtotal (before discounts)
         $subtotal = $order->get_line_subtotal( $item, true, true );
     }
 }


// Responsive Image Helper Function
function awesome_acf_responsive_image($image_id,$image_size,$max_width){

    // check the image ID is not blank
    if($image_id != '') {

        // set the default src image size
        $image_src = wp_get_attachment_image_url( $image_id, $image_size );

        // set the srcset with various image sizes
        $image_srcset = wp_get_attachment_image_srcset( $image_id, $image_size );

        // generate the markup for the responsive image
        echo 'src="'.$image_src.'" srcset="'.$image_srcset.'" sizes="(max-width: '.$max_width.') 100vw, '.$max_width.'"';

    }
}

?>