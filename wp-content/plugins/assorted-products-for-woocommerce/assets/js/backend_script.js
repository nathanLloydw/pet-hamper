(function($) {
	"use strict";

	jQuery(document).ready(function(){
		if (jQuery('.options_group.pricing').length>0) {
			jQuery('.options_group.pricing' ).addClass( 'show_if_assorted_product' );
			jQuery('.options_group ._tax_status_field').parent().addClass( 'show_if_assorted_product');
			jQuery('.inventory_options.inventory_tab').addClass( 'show_if_assorted_product' );
			jQuery('.options_group ._manage_stock_field').addClass( 'show_if_assorted_product' );
		}
		if ( jQuery('.options_group.subscription_pricing').length>0 ) {
			jQuery('.options_group.subscription_pricing').addClass('show_if_assorted_product');
		}
		jQuery(document).on('click', 'span.abp_remove_cat_discount', function(e) {
			e.preventDefault();
			if ( jQuery(this).closest('tbody').find('tr').length>1 ) {
				jQuery(this).closest('tr').remove();
			}
		});
		jQuery(document).on('click', 'span.abp_add_cat_discount', function(e) {
			e.preventDefault();
			var tr = jQuery(this).closest('tr').clone();
			jQuery(this).closest('tbody').append(tr);
		});
		jQuery(document).on('click', '.abp_submit_edit_subscription', function(e){
			e.preventDefault();
			var btn = jQuery(this);
			btn.closest('td').find('.abp_submit_subscription_msg').hide();
			btn.attr('disabled', 'disabled');
			btn.closest('td').find('span').show();
			var assorted = jQuery('#abp_assorted_product_subsc').val();
			var oldItem = jQuery('#abp_assorted_item_old').val();
			var newItem = jQuery('#abp_assorted_item_new').val();
			if ( assorted && oldItem && newItem ) {
				var data = {
					'action': 'abp_assorted_edit_subscriptions',
					'product_id' : assorted,
					'old_item' : oldItem,
					'new_item' : newItem,
					'security': abpAssorted.ajax_nonce,
				};
				jQuery.post(abpAssorted.ajaxurl, data, function(response) {
					if(response.success){
						btn.removeAttr('disabled');
						btn.closest('td').find('span').hide();
					}
					jQuery('p.abp_update_subscription_msg').text(response.data);
				});
			} else {
				btn.closest('td').find('.abp_submit_subscription_msg').show();
				btn.removeAttr('disabled');
				btn.closest('td').find('span').hide();
			}
		});
	});

})(jQuery);