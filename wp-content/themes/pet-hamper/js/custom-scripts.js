
function set_add_to_hamper_text_change()
{
    var add_to_hamper_buttons = document.querySelectorAll('.add-product-to-assorted');

    add_to_hamper_buttons.forEach(function(add_to_hamper_button)
    {
        add_to_hamper_button.addEventListener("click",function(event)
        {
            this.innerText = 'Added to hamper';
            this.classList.add('active_hamper_prod');
        });
    });
}

function set_auto_scroll_to_hamper_onclicks()
{
    var auto_scroll_to_edit_hamper = document.querySelectorAll('#mobile_view_hamper_button')[0];
    var edit_hamper_1 = document.querySelectorAll('#abp-load-more-btn')[0];
    var edit_hamper_2 = document.querySelectorAll('.show_all_hamper_products')[0];

    if(auto_scroll_to_edit_hamper)
    {
        auto_scroll_to_edit_hamper.addEventListener("click",function(event)
        {
            if(edit_hamper_1)
            {
                edit_hamper_1.scrollIntoView({behavior: "smooth"});
            }
            else if(edit_hamper_2)
            {
                edit_hamper_2.scrollIntoView({behavior: "smooth"});
            }
        });
    }
}

function mount_splide_banners()
{
    var hp_banner = document.getElementsByClassName('hp_banner');

    if (hp_banner.length > 0)
    {
        var splide1 = new Splide( '.hp_banner', {
            arrows: false,
            pagination: false,
        } );

        splide1.mount();
    }

    var collection_slider = document.getElementsByClassName('collection-slider');

    if (collection_slider.length > 0)
    {
        var splide2 = new Splide( '.collection-slider', {
            pagination: false,
            classes: {
                prev  : 'splide__arrow--prev arrow-prev',
                next  : 'splide__arrow--next arrow-next',
            },
            perPage: 3,
            breakpoints: {
                768: {
                    perPage: 1,
                },
            }
        } );

        splide2.mount();
    }
}

function image_zoom_feature()
{
    var custom_image_holder = document.querySelectorAll('.woocommerce-product-gallery__image')[0];

    var custom_image = document.querySelectorAll('.woocommerce-product-gallery__image picture.wp-post-image img')[0];

    if(!custom_image)
    {
        var custom_image = document.querySelectorAll('.woocommerce-product-gallery__image .wp-post-image')[0];
    }

    if(custom_image_holder && custom_image)
    {
        custom_image_holder.addEventListener("mouseover", function(event)
        {
            custom_image.classList.add('scaled');
        });

        custom_image_holder.addEventListener("mouseout", function(event)
        {
            if(custom_image.classList.contains('scaled'))
            {
                custom_image.classList.remove('scaled');
            }
        });

        custom_image_holder.addEventListener("click",function (event)
        {
            var P1 = ((event.pageX - this.getBoundingClientRect().x) / custom_image.width) * 100 + "%";
            var P2 = ((event.pageY - this.getBoundingClientRect().y) / custom_image.height) * 100 + "%";

            custom_image.classList.add('scaled');
            custom_image.style = 'transform-origin: '+P1+' '+P2+';';
        });

        custom_image_holder.addEventListener("mousemove", function(event)
        {
            var P1 = ((event.pageX - this.getBoundingClientRect().x) / custom_image.width) * 100 + "%";
            var P2 = ((event.pageY - this.getBoundingClientRect().y) / custom_image.height) * 100 + "%";

            custom_image.style = 'transform-origin: '+P1+' '+P2+';';
        });
    }
}

function set_secondary_image_switch_to_main_image()
{
    var secondary_images = document.querySelectorAll('picture.wp-secondary-image img');
    if(secondary_images.length == 0)
    {
        secondary_images = document.querySelectorAll('.wp-secondary-image');
    }
    var primary_image = document.querySelectorAll('.wp-post-image source')[0];
    if(!primary_image)
    {
        primary_image = document.querySelectorAll('.wp-post-image')[0];
    }

    secondary_images.forEach(function (img)
    {
        img.addEventListener("click", function (event)
        {
            var primary_image_src = JSON.parse(JSON.stringify(primary_image.getAttribute('srcset')));
            var secondary_img_src = JSON.parse(JSON.stringify(img.parentNode.children[0].getAttribute('srcset')));

            img.parentNode.children[0].setAttribute('srcset',primary_image_src);
            primary_image.setAttribute('srcset',secondary_img_src);
        });
    });
}

function scroll_to_product_content()
{
    var auto_scroll_to_product_content = document.querySelectorAll('.product-scroll')[0];

    if(auto_scroll_to_product_content && window.location.href.indexOf("#products") > -1)
    {
        auto_scroll_to_product_content.scrollIntoView({behavior: "smooth",block: "start"})
    }
}

function redirect_to_complete_cart()
{
    var disbaled_button = document.querySelectorAll('.bundle_add_to_cart_button.disabled')[0];

    if(disbaled_button)
    {
        disbaled_button.addEventListener("click",function ()
        {
            var remaining_products = document.querySelectorAll('.bundled_product select');
            var remaining_unselected_products = [];

            remaining_products.forEach(function (prod)
            {
                if(prod.value == '')
                {
                    remaining_unselected_products.push(prod);
                }
            });

            remaining_products = remaining_products[0];

            remaining_products = remaining_products.parentElement.parentElement.parentElement.parentElement.parentElement.parentElement.parentElement;
            remaining_products.scrollIntoView({behavior: "smooth"});
        });
    }
}


document.addEventListener( 'DOMContentLoaded', function()
{
    mount_splide_banners();
    image_zoom_feature();
    set_secondary_image_switch_to_main_image();
    scroll_to_product_content();
    redirect_to_complete_cart();
});

jQuery(".abp_assorted_row").bind("DOMSubtreeModified", function()
{
    set_add_to_hamper_text_change();
    set_auto_scroll_to_hamper_onclicks();
});





