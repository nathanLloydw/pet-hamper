(function($)
{
"use strict";

	var abp_add_to_cart=[];
	var abp_search_filters={};

	function abp_load_products() {

		var data = {
			'action': 'abp_assorted_search_products',
			'product_id' : jQuery('.abp_assorted_products').attr('data-product-id'),
			'search_filters' : abp_search_filters,
			'security': abpAssorted.ajax_nonce
		};

		jQuery.post(abpAssorted.ajaxurl, data, function(response) {
			if(response.data.success){

				jQuery('.abp_assorted_products .apb_products_items .abp_loader').hide();
				jQuery('.abp_assorted_products .apb_products_items .apb_products_items_container').html(response.data.html).show();
			}
		});
	}

	function abp_thousand_separator( price, thousand_sep, decimal_sep ) {
		price += '';
		var x = price.split('.');
		var x1 = x[0];
		var x2 = x.length > 1 ? decimal_sep + x[1] : '';
		var rgx = /(\d+)(\d{3})/;
		while (rgx.test(x1)) {
			x1 = x1.replace(rgx, '$1' + thousand_sep + '$2');
		}
		return x1 + x2;
	}

	function abp_check_if_item_exists(prod_id) {
		if(jQuery('#abp-assorted-add-to-cart').val()){
			var items=jQuery('#abp-assorted-add-to-cart').val().split(",");
			if(items.length){
				if(items.includes(prod_id.toString())) {
					return 1;
				} else {
					return 0;
				}
			}
		}
		return 0;
	}

	function abp_get_item_quantity_added( prod_id ) {
		if(jQuery('#abp-assorted-add-to-cart').val()){
			var items=jQuery('#abp-assorted-add-to-cart').val().split(",");
			if ( items.length && items.includes(prod_id.toString())) {
				return parseInt(jQuery('.abp_bundle_itmes_content input[name="qty_'+prod_id+'"').val());
			}
		}
		return 0;
	}

	function abp_add_item_to_selected(prod_id){
		var items=jQuery('#abp-assorted-add-to-cart').val();
		if(items.length===0){
			jQuery('#abp-assorted-add-to-cart').val(prod_id);
		}else{
			jQuery('#abp-assorted-add-to-cart').val(items+','+prod_id);
		}
	}

	function abp_apply_category_discounts( price ) {
		if ( abpAssorted.enable_discounts == 'yes' && abpAssorted.discounts.length > 0 ) {
			var discounts = [];
			abpAssorted.discounts.forEach(function(dis) {
				if ( dis.cats ) {
					var cats = dis.cats.split(',');
					cats.forEach(function(cat) {
						jQuery('.abp_bundle_itmes_content tr').each(function(index, tr) {
							var tr_obj = jQuery(this);
							var item_cats = tr_obj.attr('data-cats');
							if ( item_cats ) {
								item_cats = item_cats.split(',');
								item_cats.forEach(function( item_cat ) {
									if ( parseInt(item_cat) == parseInt(cat) ) {
										discounts[cat] = {
											'qty': ( discounts[cat] && discounts[cat].qty ) ? parseInt(discounts[cat].qty) + parseInt(tr_obj.find('input[type="number"]').val()) : parseInt(tr_obj.find('input[type="number"]').val()),
											'items': dis.items,
											'amount': dis.amount,
											'type': dis.type,
											'cat': cat
										};
									}
								});
							}
						});
					});
				}
			});
			discounts.forEach(function(k, v) {
				if ( parseInt(k.qty) >= parseInt(k.items) ) {
					if ( k.type == 'fixed' ) {
						price = price - parseFloat(k.amount);
					} else {
						price = price - price / 100 * parseFloat(k.amount);
					}
				}
			});
		}
		return price;
	}

	function abp_update_prices(){
		var price = parseFloat(abpAssorted.price);
		if ( abpAssorted.price_type !== 'regular' ) {
			if ( abpAssorted.price_type === 'per_product_items' ) {
				price = parseFloat(0);
			}
			if ( abpAssorted.price_type === 'per_product_items' || abpAssorted.price_type === 'per_product_and_items' ) {
				if ( jQuery('#abp-assorted-add-to-cart').val() ) {
					var items=jQuery('#abp-assorted-add-to-cart').val().split(",");
					if(items[0] && items.length){
						jQuery.each(items, function(key,item) {
							price= parseFloat(price) + (parseFloat(abp_add_to_cart[item])*parseInt(jQuery('tr.abp-product-'+item).find('input[name="qty_'+item+'"]').val()));
						});
					}
				}
			}
		}
		var discount = 0;
		if ( abpAssorted.enable_discounts == 'yes' && abpAssorted.discounts.length > 0 ) {
			var discounts = [];
			abpAssorted.discounts.forEach(function(dis) {
				if ( dis.cats ) {
					var cats = dis.cats.split(',');
					cats.forEach(function(cat) {
						jQuery('.abp_bundle_itmes_content tr').each(function(index, tr) {
							var tr_obj = jQuery(this);
							var item_cats = tr_obj.attr('data-cats');
							if ( item_cats ) {
								item_cats = item_cats.split(',');
								item_cats.forEach(function( item_cat ) {
									if ( parseInt(item_cat) == parseInt(cat) ) {
										discounts[cat] = {
											'qty': ( discounts[cat] && discounts[cat].qty ) ? parseInt(discounts[cat].qty) + parseInt(tr_obj.find('input[type="number"]').val()) : parseInt(tr_obj.find('input[type="number"]').val()),
											'items': dis.items,
											'amount': dis.amount,
											'type': dis.type,
											'cat': cat
										};
									}
								});
							}
						});
					});
				}
			});
			discounts.forEach(function(k, v) {
				if ( parseInt(k.qty) >= parseInt(k.items) ) {
					if ( k.type == 'fixed' ) {
						price = price - parseFloat(k.amount);
						discount = parseFloat(k.amount);
					} else {
						discount = price - price / 100 * parseFloat(k.amount);
						price = price - price / 100 * parseFloat(k.amount);
					}
				}
			});
		}
		// quantities based discount
		if ( abpAssorted.enable_qty_discounts == 'yes' && parseInt(abpAssorted.qty_discounts.items) > 0 ) {
			var qties = abp_count_items();
			if ( qties ) {
				if ( parseInt(qties) >= parseInt(abpAssorted.qty_discounts.items) ) {
					if ( abpAssorted.qty_discounts.type == 'fixed' ) {
						price = price - parseFloat(abpAssorted.qty_discounts.amount);
						discount += parseFloat(abpAssorted.qty_discounts.amount);
					} else {
						discount += price - price / 100 * parseFloat(abpAssorted.qty_discounts.amount);
						price = price - price / 100 * parseFloat(abpAssorted.qty_discounts.amount);
					}
				}
			}
		}
		if ( discount > 0 ) {
			var temp = discount;
			discount = abp_thousand_separator( discount.toFixed(abpAssorted.no_of_decimal), abpAssorted.thousand_sep, abpAssorted.decimal_sep );
			if( abpAssorted.currency_pos == 'left' ) {
				discount = abpAssorted.currency_symbol + discount;
			}else if( abpAssorted.currency_pos == 'right' ) {
				discount = discount + abpAssorted.currency_symbol;
			}else if( abpAssorted.currency_pos == 'left_space' ) {
				discount = abpAssorted.currency_symbol + ' ' + discount;
			}else if( abpAssorted.currency_pos == 'right_space' ) {
				discount = discount + ' ' + abpAssorted.currency_symbol;
			} else {
				discount = abpAssorted.currency_symbol + discount;
			}
			if ( 'yes' == abpAssorted.show_discount ) {
				jQuery('.abp_assorted_bundle_discount').html('<span class="abp_assorted_discount_label">'+abpAssorted.discount_label+'</span><span class="abp_assorted_discount_amount">'+discount+'</span>').show();
				price += temp;
			}
		} else {
			jQuery('.abp_assorted_bundle_discount').html('').hide();
		}
		price = abp_thousand_separator( price.toFixed(abpAssorted.no_of_decimal), abpAssorted.thousand_sep, abpAssorted.decimal_sep );
		if( abpAssorted.currency_pos == 'left' ) {
			price = abpAssorted.currency_symbol + price;
		}else if( abpAssorted.currency_pos == 'right' ) {
			price = price + abpAssorted.currency_symbol;
		}else if( abpAssorted.currency_pos == 'left_space' ) {
			price = abpAssorted.currency_symbol + ' ' + price;
		}else if( abpAssorted.currency_pos == 'right_space' ) {
			price = price + ' ' + abpAssorted.currency_symbol;
		} else {
			price = abpAssorted.currency_symbol + price;
		}
		jQuery('.abp_assorted_bundle_price .assorted_price').html(price);
	}

	function abp_count_items(){
		var qty=0;
		if(jQuery('#abp-assorted-add-to-cart').val()){
			var items=jQuery('#abp-assorted-add-to-cart').val().split(",");
			if(items[0] && items.length){
				jQuery.each(items, function(key,item) {
				    qty= parseInt(qty) + (parseInt(jQuery('tr.abp-product-'+item).find('input[name="qty_'+item+'"]').val()));
				});
			}
		}
		return qty;
	}

	function abp_set_filters() {
		if(jQuery('.bundle_search').find('input[name="search"]').val())
		{
			abp_search_filters['search']=jQuery('.bundle_search').find('input[name="search"]').val();
		}
		else
		{
			abp_search_filters['search']='';
		}

		if (jQuery('.abp-filter-content .abp-filter-cats').find('.abp_products_filter_type_radio').length>0)
		{
			abp_search_filters['category'] = jQuery('.abp-filter-content .abp-filter-cats').find('.abp_products_filter_type_radio input[name="filter-category"]:checked').val();
		}
		else if (jQuery('.abp-filter-content .abp-filter-cats').find('.abp_products_filter_type_checkbox').length>0)
		{
			abp_search_filters['category'] = jQuery('.abp-filter-content .abp-filter-cats').find('.abp_products_filter_type_checkbox input:checked').val();
			var cats = [];
			jQuery('.abp-filter-content .abp-filter-cats .abp_products_filter_type_checkbox input:checked').each(function()
			{
				cats.push(jQuery(this).val());
			});
			abp_search_filters['category'] = cats;
		}
		else
		{
			if(jQuery('.abp-filter-content .abp-filter-cats').find('select[name="filter-category"]').val())
			{
				abp_search_filters['category']=jQuery('.abp-filter-content .abp-filter-cats').find('select[name="filter-category"]').val();
			}
			else
			{
				abp_search_filters['category']='';
			}
		}

		if (jQuery('.abp-filter-content .abp-filter-tags').find('.abp_products_filter_type_radio').length>0)
		{
			abp_search_filters['tag'] = jQuery('.abp-filter-content .abp-filter-tags').find('.abp_products_filter_type_radio input[name="filter-tag"]:checked').val();
		}
		else if (jQuery('.abp-filter-content .abp-filter-tags').find('.abp_products_filter_type_checkbox').length>0)
		{
			abp_search_filters['tag'] = jQuery('.abp-filter-content .abp-filter-tags').find('.abp_products_filter_type_checkbox input:checked').val();
			var tag = [];
			jQuery('.abp-filter-content .abp-filter-tags .abp_products_filter_type_checkbox input:checked').each(function()
			{
				tag.push(jQuery(this).val());
			});
			abp_search_filters['tag'] = tag;
		}
		else
		{
			if(jQuery('.abp-filter-content .abp-filter-tags').find('select[name="filter-tag"]').val())
			{
				abp_search_filters['tag']=jQuery('.abp-filter-content .abp-filter-tags').find('select[name="filter-tag"]').val();
			}
			else
			{
				abp_search_filters['tag']='';
			}
		}
	}

	function abp_show_msg(str){
		jQuery('#abp-max-success').show();
			document.getElementById('abp-max-success').innerHTML=str;
			setTimeout(function() {
	        $('#abp-max-success').fadeOut(1000);
	    }, 1000);
	}

	function abp_show_error(str){
		jQuery('#abp-max-error').show();
			document.getElementById('abp-max-error').innerHTML=str;
			setTimeout(function() {
	        $('#abp-max-error').fadeOut(1000);
	    }, 1000);
	}

	function abp_assorted_quick_view(product_id){
		jQuery('.abp_product_quick_view, .abp_product_boxes_layer').show();
		jQuery('.abp_product_quick_view .abp_product_quick_view_footer, .abp_product_quick_view .abp_loader').show();
		var data = {
			'action': 'abp_assorted_quick_view',
			'product_id' : product_id,
			'security': abpAssorted.ajax_nonce,
		};
		jQuery.post(abpAssorted.ajaxurl, data, function(response) {
			if(response.data.success){
				jQuery('.abp_product_quick_view .abp_product_quick_view_content').html(response.data.html);
				if ( 'yes' == abpAssorted.removebtn ) {
					jQuery('.abp_product_quick_view .abp_product_quick_view_content form').remove();
				}
				jQuery('.abp_product_quick_view .abp_product_quick_view_footer').hide();
				jQuery('.abp-assorted-carousel').slick({
					dots: false,
				});
			} else {
				jQuery('.abp_product_quick_view, .abp_product_boxes_layer').hide();
			}
		});
	}

	jQuery(document).ready(function() {

		// set default category
		abp_search_filters['category'] = ["239"];

		// load products
		abp_load_products();
		//quick view
		if ( abpAssorted.box_item_click == 'quickview' || abpAssorted.box_item_click == 'noaction' || abpAssorted.box_item_click == 'addtobundle' ) {
			jQuery(document).on('click','.apb-title a, .abp-figure img',function(e){
				e.preventDefault();
				var me = jQuery(this);
				if ( abpAssorted.box_item_click == 'quickview' ) {
					var product_id = jQuery(this).closest('li').attr('data-product-id');
					jQuery('.abp-assorted-quick-view').trigger('click');
					jQuery('.abp-assorted-quick-view .abp-quick-loader').show();
					jQuery('.abp-assorted-quick-view .ap-quick-view-content').hide();
					abp_assorted_quick_view(product_id);
				}
				if ( abpAssorted.box_item_click == 'addtobundle' ) {
					if(abp_count_items()>=abpAssorted.max){
						abp_show_error(abpAssorted.max_error);
						return;
					}
					var item = me.closest('.abp-inner').find('.abp_bundle_item_meta').val();
					item=jQuery.parseJSON(item);
					if(abp_check_if_item_exists(parseInt(item.id))===0){
						var image = me.closest('.abp-inner').find('img').clone();
						jQuery('.abp_review_before_cart .abp_bundle_itmes_content').find('table tbody').append('<tr class="abp-product-'+item.id+'" data-id="'+item.id+'"><td><span class="dashicons dashicons-no abp_remove"></span>'+image[0].outerHTML+'</td><td>'+item.title+'</td><td><input type="number" name="qty_'+item.id+'" min="1" value="1"></td></tr>');
						abp_add_to_cart[item.id]=item.price;
						abp_add_item_to_selected(item.id);
					}else{
						jQuery('.abp_review_before_cart .abp_bundle_itmes_content table tbody .abp-product-'+item.id).find('input[type="number"]').get(0).value++;
					}
					abp_show_msg(abpAssorted.msg_success);
					abp_update_prices();
					me.removeAttr('disabled');
					jQuery(document).trigger('abp_bundle_product_changed');
				}
			});
		}

		jQuery(document).on('click', '.abp_product_quick_view_close, .abp_product_boxes_layer', function() {
			jQuery('.abp_product_quick_view .abp_product_quick_view_content').html('');
			jQuery('.abp_product_quick_view, .abp_product_boxes_layer').hide();
		});

		// sidebar
		jQuery(document).on('change', '.abp_bundle_itmes_content table td input[type="number"]', function() {
			var me = jQuery(this);
			var item_id = me.closest('tr').attr('data-id');
			var item_qty = me.closest('tr').attr('data-qty');
			if ( item_qty != null && abp_get_item_quantity_added(parseInt(item_id)) > parseInt(item_qty) ) {
				abp_show_error(abpAssorted.max_error);
				me.get(0).value--;
				return;
			}
			if(abp_count_items()>abpAssorted.max){
				abp_show_error(abpAssorted.max_error);
				me.get(0).value--;
				return;
			}
			abp_update_prices();
			jQuery(document).trigger('abp_bundle_product_changed');
		});

		// filter search
		jQuery(document).on('click','.abp-search-filter-btn',function(e)
		{
			abp_search_filters['category'] = '';

			const categories_label = document.querySelectorAll('.abp_filter_item.abp-search-filter-cat-btn.active');
			const categories_input = document.querySelectorAll('.abp_filter_item.abp-search-filter-cat-btn.active input');
			const categories_section = document.querySelectorAll('.filter-field.abp-filter-cats')[0];

			categories_label.forEach(category => {
				category.classList.remove('active');
			});

			categories_input.forEach(category => {
				category.checked = false;
			});

			e.preventDefault();
			abp_set_filters();
			jQuery('.abp_assorted_products .apb_products_items .apb_products_items_container').hide();
			jQuery('.abp_assorted_products .apb_products_items .abp_loader').show();
			abp_load_products();
		});

		jQuery(document).on('keydown','.bundle_search input',function(e)
		{
			if(e.keyCode == 13)
			{
				abp_search_filters['category'] = '';

				const categories_label = document.querySelectorAll('.abp_filter_item.abp-search-filter-cat-btn.active');
				const categories_input = document.querySelectorAll('.abp_filter_item.abp-search-filter-cat-btn.active input');
				const categories_section = document.querySelectorAll('.filter-field.abp-filter-cats')[0];

				categories_label.forEach(category => {
					category.classList.remove('active');
				});

				categories_input.forEach(category => {
					category.checked = false;
				});

				e.preventDefault();
				abp_set_filters();
				jQuery('.abp_assorted_products .apb_products_items .apb_products_items_container').hide();
				jQuery('.abp_assorted_products .apb_products_items .abp_loader').show();
				abp_load_products();
			}
		});

		jQuery(document).on('click','.desktop-filters .abp-search-filter-cat-btn',function(e) {

			const categories_input = document.querySelectorAll('.abp_products_filter_type_checkbox input');

			categories_input.forEach(category => {
				category.checked = false;
			});

			if(this.classList.contains('active'))
			{
				this.classList.remove('active');
				this.children[0].children[0].checked = false;
			}
			else
			{
				const categories_label = document.querySelectorAll('.abp-search-filter-cat-btn');

				categories_label.forEach(category => {
					category.classList.remove('active');
				});

				this.classList.add('active');
				this.children[0].children[0].checked = true;
			}

			e.preventDefault();
			abp_set_filters();
			jQuery('.abp_assorted_products .apb_products_items .apb_products_items_container').hide();
			jQuery('.abp_assorted_products .apb_products_items .abp_loader').show();
			abp_load_products();
		});

		jQuery(document).on('click','.mobile-cat-filters .abp-search-filter-cat-btn',function(e) {

			const categories_input = document.querySelectorAll('.desktop-filters .abp_products_filter_type_checkbox input');

			categories_input.forEach(category => {
				category.checked = false;
			});

			if(this.classList.contains('active'))
			{
				this.classList.remove('active');
				this.children[0].children[0].checked = false;
			}
			else
			{
				const categories_label = document.querySelectorAll('.abp-search-filter-cat-btn');

				this.classList.add('active');
				this.children[0].children[0].checked = true;
			}

			e.preventDefault();
			abp_set_filters();
			jQuery('.abp_assorted_products .apb_products_items .apb_products_items_container').hide();
			jQuery('.abp_assorted_products .apb_products_items .abp_loader').show();
			abp_load_products();
		});

		jQuery(document).on('click','.abp-search-sort-cat-btn',function(e) {
			let sort_val = this.children[0].children[0].value.split(" ")[0];
			let sort_dir = this.children[0].children[0].value.split(" ")[1];

			abp_search_filters['sort_val'] = sort_val;
			abp_search_filters['sort_dir'] = sort_dir;

			const sort_input = document.querySelectorAll('.abp_products_sort_type_checkbox input');

			sort_input.forEach(sort => {
				sort.checked = false;
			});

			if(this.classList.contains('active'))
			{
				this.classList.remove('active');
				this.children[0].children[0].checked = false;
			}
			else
			{
				const sort_label = document.querySelectorAll('.abp-search-sort-cat-btn');

				sort_label.forEach(sort => {
					sort.classList.remove('active');
				});

				this.classList.add('active');
				this.children[0].children[0].checked = true;
			}

			e.preventDefault();
			abp_set_filters();
			jQuery('.abp_assorted_products .apb_products_items .apb_products_items_container').hide();
			jQuery('.abp_assorted_products .apb_products_items .abp_loader').show();
			abp_load_products();
		});

		jQuery(document).on('click','.cat-filter-clear-btn',function () {
			const categories_input = document.querySelectorAll('.abp_products_sort_type_checkbox input');

			categories_input.forEach(category => {
				category.checked = false;
			});

			const categories_label = document.querySelectorAll('.abp-search-sort-cat');

			categories_label.forEach(category => {
				category.classList.remove('active');
			});

			abp_load_products();
		});

		jQuery(document).on('click','.cat-sort-clear-btn',function () {
			const categories_input = document.querySelectorAll('.abp_products_filter_type_checkbox input');

			categories_input.forEach(category => {
				category.checked = false;
			});

			const categories_label = document.querySelectorAll('.abp-search-filter-cat-btn');

			categories_label.forEach(category => {
				category.classList.remove('active');
			});

			abp_load_products();
		});

		jQuery(document).on('click','.cat-filter-done-btn',function() {
			document.getElementsByClassName('mobile-cat-filters')[0].style.display = 'none';
		});

		jQuery(document).on('click','.cat-sort-done-btn',function() {
			document.getElementsByClassName('mobile-sort-filters')[0].style.display = 'none';
		});

		jQuery(document).on('click','.cat-filter-btn',function() {
			document.getElementsByClassName('mobile-cat-filters')[0].style.display = 'flex';
		});

		jQuery(document).on('click','.mobile-filters .cat-sort-btn',function() {
			document.getElementsByClassName('mobile-sort-filters')[0].style.display = 'flex';
		});

		jQuery(document).on('click','.desktop-sort-filters .cat-sort-btn',function()
		{
			if($(".desktop-sort-filters .content")[0].style.display == 'none')
			{
				$(".desktop-sort-filters .content")[0].style.display = 'block';
			}
			else
			{
				$(".desktop-sort-filters .content")[0].style.display = 'none';
			}
		});

		jQuery(document).on('click','.desktop-sort-filters .abp-search-sort-cat-btn',function()
		{
			this.classList.add('active');
		});

		jQuery(document).on('click','.close-modal-cat',function() {
			document.getElementsByClassName('mobile-cat-filters')[0].style.display = 'none';
		});

		jQuery(document).on('click','.close-modal-sort',function() {
			document.getElementsByClassName('mobile-sort-filters')[0].style.display = 'none';
		});

		jQuery(document).on('click','.abp_remove',function(){
			var prod_id=jQuery(this).closest('tr').attr('data-id');
			var product_listing = document.querySelector('[data-product-id="'+prod_id+'"] button');
			product_listing.classList.remove('active_hamper_prod');
			product_listing.innerText = 'add to hamper';

			var items=jQuery('#abp-assorted-add-to-cart').val().split(",");
			if(items.length){
				items = jQuery.grep(items, function(value) {
				  return value != prod_id;
				});
				jQuery('#abp-assorted-add-to-cart').val(items.join(','));
			}
			jQuery(this).closest('tr').remove();
			abp_update_prices();
			jQuery(document).trigger('abp_bundle_product_changed');
		});

		jQuery(document).on('click', '.abp_assorted_clear_wrap a.abp_assorted_clear', function(e){
			e.preventDefault();
			jQuery('.abp_bundle_itmes_content table tbody').html('');
			jQuery('#abp-assorted-add-to-cart').val('');
			if ( jQuery('.abp_bundle_counter .abp_bundle_count').length>0 ) {
				jQuery('.abp_bundle_counter .abp_bundle_count').html('0/'+abpAssorted.max);
			}
			jQuery('.abp-search-reset-btn').trigger('click');
			abp_update_prices();
			setTimeout(function(){
			jQuery(document).trigger('abp_bundle_product_changed');
			},300);
		});

		jQuery(document).on('abp_bundle_product_changed', function(){
			if(abp_count_items()>=abpAssorted.min){
				jQuery('button.single_add_to_cart_button.abp_assorted_bundle').removeAttr('disabled');
			}else{
				jQuery('button.single_add_to_cart_button.abp_assorted_bundle').attr('disabled','disabled');
			}
			var qty = abp_count_items();
			if ( qty && jQuery('.abp_bundle_counter .abp_bundle_count').length>0 ) {
				jQuery('.abp_bundle_counter .abp_bundle_count').html(qty+'/'+abpAssorted.max);
			}
		});

		// add item to bundle
		jQuery(document).on('click','.add-product-to-assorted',function(e){
			e.preventDefault();
			var me = jQuery(this);
			me.attr('disabled','disabled');
			if(abp_count_items()>=abpAssorted.max){
				abp_show_error(abpAssorted.max_error);
				me.removeAttr('disabled');
				return;
			}
			var item = me.closest('.abp-captions').find('.abp_bundle_item_meta').val();
			item = jQuery.parseJSON(item);
			if ( item.qty != null && abp_get_item_quantity_added(parseInt(item.id)) >= parseInt(item.qty) ) {
				abp_show_error(abpAssorted.max_error);
				me.removeAttr('disabled');
				return;
			}
			if( abp_check_if_item_exists(parseInt(item.id)) === 0 ) {
				var image=me.closest('li').find('img').clone();
				jQuery('.abp_review_before_cart .abp_bundle_itmes_content').find('table tbody').append('<tr class="abp-product-'+item.id+'" data-id="'+item.id+'" data-cats="'+item.cats+'" data-qty="'+item.qty+'"><td><span class="dashicons dashicons-no abp_remove"></span>'+image[0].outerHTML+'</td><td>'+item.title+'</td><td><input type="number" name="qty_'+item.id+'" min="1" value="1"></td></tr>');
				abp_add_to_cart[item.id]=item.price;
				abp_add_item_to_selected(item.id);
			} else {
				jQuery('.abp_review_before_cart .abp_bundle_itmes_content table tbody .abp-product-'+item.id).find('input[type="number"]').get(0).value++;
			}
			abp_show_msg(abpAssorted.msg_success);
			abp_update_prices();
			me.removeAttr('disabled');
			jQuery(document).trigger('abp_bundle_product_changed');
		});

		// reset button
		jQuery(document).on('click', '.abp-search-reset-btn', function(e){
			e.preventDefault();
			abp_search_filters = {};
			jQuery('.abp_assorted_products .apb_products_items .apb_products_items_container').hide();
			jQuery('.abp_assorted_products .apb_products_items .abp_loader').show();
			if( jQuery('.abp_products_filter_type_checkbox').length>0 ) {
				jQuery('.abp_products_filter_type_checkbox input').prop('checked', false);
			}
			if( jQuery('.abp_products_filter_type_radio').length>0 ) {
				jQuery('.abp_products_filter_type_radio input').prop('checked', false);
			}
			jQuery('.abp-filter-content').find('input[name="search"], select').val('');
			abp_load_products();
		});

		jQuery(document).on('click','.show_all_hamper_products',function(e)
		{
			const index = abp_search_filters['category'].indexOf('239');
			if (index > -1)
			{
				abp_search_filters['category'].splice(index, 1); // 2nd parameter means remove one item only
			}

			jQuery('.abp_assorted_products .apb_products_items .apb_products_items_container').hide();
			jQuery('.abp_assorted_products .apb_products_items .abp_loader').show();

			const categories_label = document.querySelectorAll('.abp_filter_item.abp-search-filter-cat-btn.active');
			const categories_input = document.querySelectorAll('.abp_filter_item.abp-search-filter-cat-btn.active input');
			const categories_section = document.querySelectorAll('.filter-field.abp-filter-cats')[0];

			categories_label.forEach(category => {
				category.classList.remove('active');
			});

			categories_input.forEach(category => {
				category.checked = false;
			});

			abp_load_products();

			this.style.display = 'none';
			categories_section.scrollIntoView();
		});

		//load more
		jQuery(document).on('click', '#abp-load-more-btn', function(e){
			e.preventDefault();
			var me=jQuery(this);
			abp_set_filters();
			var paged =(typeof me.attr('paged')!== typeof undefined && me.attr('paged')!== false) ? parseInt(me.attr('paged')) : parseInt(me.attr('data-paged'));
			var max = parseInt(me.attr('data-max'));
			if(paged<max){
				paged=paged+1;
			}

			var data = {
				'action': 'abp_assorted_search_products',
				'product_id' : jQuery('.abp_assorted_products').attr('data-product-id'),
				'security': abpAssorted.ajax_nonce,
				'search_filters' : abp_search_filters,
				'paged' : paged,
			};

	
			jQuery('.abp_assorted_products .apb_products_items .abp_loader').show();
			jQuery.post(abpAssorted.ajaxurl, data, function(response) {
				if(response.data.success){
					jQuery('.abp_assorted_products .apb_products_items .abp_loader').hide();
					jQuery('.abp_assorted_products .apb_products_items .apb_products_items_container ul').append(response.data.html).show();
					if(response.data.paged){
						if(response.data.paged===max){
							me.attr('paged',response.data.paged);
							me.closest('.abp_products_footer').hide();
						}
						else{
							me.attr('paged',response.data.paged);
						}
					}
				}
			});
		});
	});
})(jQuery);
