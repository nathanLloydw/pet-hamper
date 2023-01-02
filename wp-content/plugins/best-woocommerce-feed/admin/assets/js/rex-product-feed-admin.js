(function ( $ ) {
    'use strict';

    var progressWidth = 0;
    var deferred = $.Deferred();
    var promise = deferred.promise();
    let config_btn = rex_wpfm_admin_translate_strings.google_cat_map_btn;
    let optimize_pr_title_btn = rex_wpfm_admin_translate_strings.optimize_pr_title_btn;

    $( function () {
        $( ".meter > span" ).each( function () {
            $( this )
                .data( "origWidth", $( this ).width() )
                .width( 0 )
                .animate( {
                    width: $( this ).data( "origWidth" )
                }, 1200 );
        } );
    } );

    /**
     * All of the code for your admin-facing JavaScript source
     * should reside in this file.
     *
     * Note: It has been assumed you will write jQuery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * This enables you to define handlers, for when the DOM is ready:
     *
     * $(function() {
     *
     * });
     *
     * When the window is loaded:
     *
     * $( window ).load(function() {
     *
     * });
     *
     * ...and/or other possibilities.
     *
     * Ideally, it is not considered best practise to attach more than a
     * single DOM-ready or window-load handler for a particular page.
     * Although scripts in the WordPress core, Plugins and Themes may be
     * practising this, we should strive to set a better example in our own work.
     */

    $( document ).on( 'ready', function ( event ) {
        if ( rex_wpfm_ajax.current_screen === 'rex_feed_edit' ) {
            rex_feed_niceselect( event );
            rex_feed_ebay_seller_fields();
            rex_feed_load_config_table( event );
            rex_feed_show_analytics_params( event );
            rex_feed_view_filter_metaboxes( event );
            rex_feed_manage_custom_cron_schedule_fields();
            rex_feed_custom_filter( event );
        }
        else if ( rex_wpfm_ajax.current_screen === 'add' ) {
            rex_feed_load_config_table( event );
            rex_feed_custom_filter( event );
        }
        else if ( rex_wpfm_ajax.current_screen === 'product-feed_page_wpfm_dashboard' ) {
            rex_feed_settings_tab( event );
            rex_feed_process_rollback_button();
        }
        rex_feed_show_review_request( event );
        rex_feed_merchant_list_select2( event );
        default_category_mapping( event );

        var publish_btn_txt = $( '#publish' ).val();
        $( '#rex-bottom-publish-btn' ).text( publish_btn_txt );

        rex_feed_hide_all_admin_notices();

        rex_feed_delete_publish_btn_id();
    } );

    /**
     * Add a new table-row and update it's
     */

    $( document ).on( 'click', '#rex-new-attr', function () {
        var rowId = $( this ).parent().parent().siblings( '#config-table' ).find( 'tbody tr' ).last().attr( 'data-row-id' );
        rowId = parseInt( rowId ) + 1;
        var lastrow = $( this ).parent().parent().siblings( '#config-table' ).find( 'tbody tr:last' );
        var parent = $( this ).parent().parent().siblings( '#config-table' ).parent();

        if ( parent.hasClass( 'rex-feed-config-filter' ) ) {
            var filter = true;
        } else {
            filter = false;
        }
        $( this ).parent().parent().siblings( '#config-table' ).find( 'tbody tr:first' )
            .clone()
            .insertAfter( lastrow )
            .attr( 'data-row-id', rowId )
            .show();

        let $row = $( this ).parent().parent().siblings( '#config-table' ).find( "[data-row-id='" + rowId + "']" );
        $row.find( 'ul.dropdown-content.select-dropdown, .caret, .select-dropdown ' ).remove();
        $row.find( 'select.default-sanitize-dropdown' ).attr( 'id', 'sanitize-dropdown-' + rowId );

        updateFormNameAtts( $row, rowId, filter );

        $( 'select#sanitize-dropdown-' + rowId ).select2({
            closeOnSelect: false,
        });

        $( 'select[name="fc[' + rowId + '][meta_key]"]' ).select2();
    } );

    /**
     * Add placeholder for dynamic pricing
     */

    $( document ).on( 'click', '.meta-dropdown', function () {
        var is_premium = rex_wpfm_ajax.is_premium;

        if ( is_premium == 1 ) {
            var rowId = $( this ).parent().parent().attr( 'data-row-id' );

            $( document ).on( 'change', 'select[name="fc[' + rowId + '][meta_key]"]', function () {
                var value_selected = $( this ).val();
                var prices = [
                    'price',
                    'current_price',
                    'sale_price',
                    'price_with_tax',
                    'current_price_with_tax',
                    'sale_price_with_tax',
                    'price_excl_tax',
                    'current_price_excl_tax',
                    'sale_price_excl_tax',
                    'price_db',
                    'current_price_db',
                    'sale_price_db'
                ];

                if ( $.inArray( value_selected, prices ) !== -1 ) {
                    $( 'input[name="fc[' + rowId + '][limit]"]' ).attr( 'placeholder', 'Update Price i.e. +25%' );
                    $( 'input[name="fc[' + rowId + '][limit]"]' ).addClass( 'dynamic-placeholder' );
                    $( this ).addClass( 'dynamic-placeholder' );
                } else {
                    $( 'input[name="fc[' + rowId + '][limit]"]' ).removeAttr( 'placeholder' );
                    $( 'input[name="fc[' + rowId + '][limit]"]' ).removeClass( 'dynamic-placeholder' );
                    $( this ).removeClass( 'dynamic-placeholder' );
                }
            } );
        }
    } );

    /**
     * add new custom attributes
     */
    $( document ).on( 'click', '#rex-new-custom-attr', function () {
        var rowId = $( this ).parent().parent().siblings( '#config-table' ).find( 'tbody tr' ).last().attr( 'data-row-id' );
        rowId = parseInt( rowId ) + 1;
        var lastrow = $( this ).parent().parent().siblings( '#config-table' ).find( 'tbody tr:last' );
        var parent = $( this ).parent().parent().siblings( '#config-table' ).parent();

        if ( parent.hasClass( 'rex-feed-config-filter' ) ) {
            var filter = true;
        } else {
            filter = false;
        }

        $( this ).parent().parent().siblings( '#config-table' ).find( 'tbody tr:first' )
            .clone()
            .insertAfter( lastrow )
            .attr( 'data-row-id', rowId )
            .show();


        var $row = $( this ).parent().parent().siblings( '#config-table' ).find( "[data-row-id='" + rowId + "']" );
        $row.find( 'ul.dropdown-content.select-dropdown, .caret, .select-dropdown ' ).remove();
        $row.find( 'select.default-sanitize-dropdown' ).attr( 'id', 'sanitize-dropdown-' + rowId );

        $row.find( 'td:eq(0)' ).empty();
        $row.find( 'td:eq(0)' ).append( '<input type="text" name="fc[0][cust_attr]" value="">' );

        updateFormNameAtts( $row, rowId, filter );

        $( 'select#sanitize-dropdown-' + rowId ).select2({
            closeOnSelect: false,
        });

        $( 'select[name="fc[' + rowId + '][meta_key]"]' ).select2();
    } );

    /**
     * add new custom filter
     */
    $( document ).on( 'click', '#rex-new-filter', function () {
        var rowId = $( this ).siblings( '#rex-feed-config-filter .rex__filter-table' ).children( '#config-table' ).find( 'tbody tr' ).last().attr( 'data-row-id' );
        rowId = parseInt( rowId ) + 1;
        var lastrow = $( this ).siblings( '#rex-feed-config-filter .rex__filter-table' ).children( '#config-table' ).find( 'tbody tr:last' );

        $( this ).siblings( '#rex-feed-config-filter .rex__filter-table' ).children( '#config-table' ).find( 'tbody tr:first' )
            .clone()
            .insertAfter( lastrow )
            .attr( 'data-row-id', rowId )
            .show();

        var $row = $( this ).siblings( '#rex-feed-config-filter .rex__filter-table' ).children( '#config-table' ).find( "[data-row-id='" + rowId + "']" );
        $row.find( '#rex-feed-config-filter ul.dropdown-content.select-dropdown, .caret, .select-dropdown ' ).remove();

        updateFormNameAtts( $row, rowId, true );
    } );

    /**
     * Delete a table-row and update all row-id
     * beneath it and their input attributes names.
     */
    $( document ).on( 'click', '#config-table .delete-row', function () {

        var $nextRows, rowId;

        var table = $( this ).closest( 'table' );
        var parent = table.parent();

        // delete row and get it's row-id
        rowId = $( this ).closest( 'tr' ).remove().data( 'row-id' );

        if ( parent.hasClass( 'rex-feed-config-filter' ) ) {
            var filter = true;
        } else {
            filter = false;
        }

        // Gell the next rows
        if ( rowId == 0 ) {
            $nextRows = $( '#config-table tbody' ).children();
        } else {
            $nextRows = $( '#config-table' ).find( "[data-row-id='" + (rowId - 1) + "']" ).nextAll( 'tr' );
        }

        // Update their row-id and name attributes
        $nextRows.each( function ( index, el ) {
            if ( !$( el ).css( 'display' ) == 'none' ) {
                $( el ).attr( 'data-row-id', rowId );
                updateFormNameAtts( $( el ), rowId, filter );
                rowId++;
            }

        } );
    } );

    $( document ).on( "click", ".rex-xml-popup__close-btn", function () {
        $( 'section.rex-xml-popup' ).hide();
    } );

    $( document ).on( "click", "#wpfm-clear-batch", wpfm_clear_batch );

    $( document ).on( "click", "#wpfm-log-copy", wpfm_copy_log );

    $( document ).on( 'click', '#publish, #rex-bottom-publish-btn, #rex-bottom-preview-btn', rex_feed_is_google_attribute_missing );

    $( document ).on( 'click', 'a#rex_google_missing_attr_okay_btn', get_product_number );

    $( document ).on( 'click', 'a#rex_google_missing_attr_cancel_btn, #rex_google_missing_attr_cross_btn', function () {
        $( 'section#rex_feed_google_req_attr_warning_popup' ).hide();
    } );

    $( document ).on( 'click', '#send-to-google', send_to_google );

    $( document ).on( 'click', '.rex-reset-btn', reset_form );

    $( document ).on( "click", "#wpfm-purge-cache", purge_transient_cache );

    $( document ).on( "click", "#btn_on_feed", purge_transient_cache_on_feed );

    $( document ).on( "click", "#rex_feed_abandoned_child_list_update_button", rex_feed_update_abandoned_child_list );

    // Trigger Based Review Request
    $( document ).on( 'click', '#rex_rate_now, #rex_rate_not_now, #rex_rated_already', function ( e ) {
        var btn_id = $( this ).attr( 'id' );
        var show = true;
        var frequency = '';

        if ( btn_id == 'rex_rate_now' || btn_id == 'rex_rated_already' ) {
            if ( btn_id == 'rex_rated_already' )
                e.preventDefault();

            var show = false;
            var frequency = 'never';
        } else if ( btn_id == 'rex_rate_not_now' ) {
            e.preventDefault();
            var feed_id = $( '#rex_feed_hidden_feed_id' ).val();
            var show = false;
            var frequency = 'one_week';
        }

        var payload = {
            show: show,
            frequency: frequency
        };

        wpAjaxHelperRequest( 'trigger-review-request', payload )
            .success( function ( response ) {
                $( '.rex-feed-review' ).fadeOut();
                console.log( 'Woohoo! Awesome!!' );
            } )
            .error( function ( response ) {
                console.log( 'Uh, oh! Not Awesome!!' );
                console.log( 'response.statusText' );
            } );
    } );
    // Trigger Based Review Request ENDS

    // New changes messages
    $( document ).on( 'click', '#view_changes_btn', function ( e ) {
        wpAjaxHelperRequest( 'new-changes-message' )
            .success( function ( response ) {
                $( '#rex_feed_new_changes_msg_content' ).fadeOut();
                console.log( 'Woohoo! Awesome!!' );
            } )
            .error( function ( response ) {
                console.log( 'Uh, oh! Not Awesome!!' );
                console.log( 'response.statusText' );
            } );
    } );
    // New changes messages ENDS

    $( document ).on( 'click', '#rex-feed-settings-btn', function () {
        $( '.post-type-product-feed #wpcontent .clear' ).remove();
        $( '.post-type-product-feed #wpcontent' ).append( '<div id="body-overlay"></div>' );
        $( '.post-type-product-feed #wpcontent' ).append( '<div class="clear"></div>' );
        $( '#rex_feed_product_settings' ).addClass( 'show-settings' );
    } );

    $( document ).on( 'click', '.rex-contnet-setting__close-icon', function ( e ) {
        e.preventDefault();
        $( '.post-type-product-feed #wpcontent #body-overlay' ).remove();
        $( '#rex_feed_product_settings' ).removeClass( 'show-settings' );
    } );

    $( document ).on( 'click', '#rex-pr-filter-btn', function () {
        $( '.post-type-product-feed #wpcontent .clear' ).remove();
        $( '.post-type-product-feed #wpcontent' ).append( '<div id="body-overlay"></div>' );
        $( '.post-type-product-feed #wpcontent' ).append( '<div class="clear"></div>' );
        $( '#rex_feed_product_filters' ).addClass( 'show-filters' );
    } );

    $( document ).on( 'click', '#rex_feed_filter_modal_close_btn', function ( e ) {
        e.preventDefault();
        $( '.post-type-product-feed #wpcontent #body-overlay' ).remove();
        $( '#rex_feed_product_filters' ).removeClass( 'show-filters' );
    } );

    $( document ).on( 'click', 'ul.rex-settings-tabs li', rex_feed_settings_tab);

    $( document ).on( 'click', '.rex-feed-rollback-button', rex_feed_rollback_confirmation );

    //   video setup wizard__video
    $( document ).on( 'click', '.box-video', function() {
        $('iframe',this)[0].src += "&amp;autoplay=1";
        $(this).addClass('open');
    });

    $( document ).on( 'click', '#rex_feed_custom_filter_button', rex_feed_custom_filter );

    $( document ).on( 'click', '#rex-feed-system-status-copy-btn', rex_feed_copy_system_status );

    $( document ).on( 'click', '#rex-feed-tour-guide-popup-no-thanks-btn, .rex-take-alert__close-btn', rex_feed_disable_tour_guide_popup );

    /**
     * Event listener for Analytics Parameter options functionality.
     */
    $( document ).on( 'change', '#rex_feed_analytics_params_options', rex_feed_show_analytics_params );

    /**
     * Event listener for Attribute type change functionality.
     */
    $( document ).on( 'change', 'select.type-dropdown', function () {
        var selected = $( this ).find( 'option:selected' ).val();
        if ( selected == 'static' ) {
            $( this ).closest( 'td' ).next( 'td' ).find( '.meta-dropdown' ).hide();
            $( this ).closest( 'td' ).next( 'td' ).find( '.static-input' ).show();
        } else {
            $( this ).closest( 'td' ).next( 'td' ).find( '.static-input' ).hide();
            $( this ).closest( 'td' ).next( 'td' ).find( '.meta-dropdown' ).show();
        }
    } );

    /**
     * Event listener for Filter Product.
     */
    $( document ).on( 'change', '#rex_feed_products', rex_feed_view_filter_metaboxes );

    /**
     * Event listener for Merchant change functionality.
     */
    $( document ).on( 'change', '#rex_feed_merchant', function () {
        rex_feed_load_config_table();
        rex_feed_ebay_seller_fields();
    } );

    $( document ).on( 'change', '#rex_feed_merchant', function () {
        let feed_merchant = $( this ).find( ':selected' ).val();

        if ( feed_merchant === 'custom' ) {
            if ( 'xml' === $( '#rex_feed_feed_format' ).find( ':selected' ).val() ) {
                $( '.rex_feed_custom_items_wrapper, .rex_feed_custom_wrapper, .rex_feed_custom_wrapper' ).fadeIn();
            }
        }
        else {
            $( '.rex_feed_custom_items_wrapper, .rex_feed_custom_wrapper, .rex_feed_custom_wrapper' ).fadeOut();
        }
    } );

    // Event listener on feed merchant option change
    // to hide/show yandex old price dropdown field
    $( document ).on( 'change', '#rex_feed_merchant', function () {
        let feed_merchant = $( this ).find( ':selected' ).val();

        if ( 'yandex' === feed_merchant ) {
            $( '.rex_feed_yandex_old_price, .rex_feed_yandex_company_name' ).fadeIn();
        }
        else {
            $( '.rex_feed_yandex_old_price, .rex_feed_yandex_company_name' ).fadeOut();
        }
    } );

    /**
     * Event listener for Google schedule change change functionality.
     */
    $( document ).on( 'change', '#rex_feed_google_schedule', function () {
        var schedule = $( '#rex_feed_google_schedule' ).find( ':selected' ).val();

        if ( schedule == 'monthly' ) {
            $( '#rex_feed_google_schedule_month__content' ).show();
            $( '#rex_feed_google_schedule_week_day__content' ).hide();
            $( '#rex_feed_google_schedule_time__content' ).hide();
        } else if ( schedule == 'weekly' ) {
            $( '#rex_feed_google_schedule_month__content' ).hide();
            $( '#rex_feed_google_schedule_week_day__content' ).show();
            $( '#rex_feed_google_schedule_time__content' ).hide();
        } else if ( schedule == 'hourly' ) {
            $( '#rex_feed_google_schedule_month__content' ).hide();
            $( '#rex_feed_google_schedule_week_day__content' ).hide();
            $( '#rex_feed_google_schedule_time__content' ).show();
        }
    } );

    /**
     * Event listener for Feed format change for CSV functionality.
     */
    $( document ).on( 'change', '#rex_feed_feed_format', function () {
        let feed_format = $( this ).find( ':selected' ).val();

        if ( feed_format === 'csv' ) {
            $( '.rex-feed-feed-separator' ).show();
        } else {
            $( '.rex-feed-feed-separator' ).hide();
        }
    } );

    $( document ).on( 'change', '#rex_feed_feed_format', function () {
        let feed_format = $( this ).find( ':selected' ).val();

        if ( feed_format === 'xml' ) {
            if ( 'custom' === $( '#rex_feed_merchant' ).find( ':selected' ).val() ) {
                $( '.rex_feed_custom_items_wrapper, .rex_feed_custom_wrapper, .rex_feed_custom_wrapper' ).fadeIn();
            }
        }
        else {
            $( '.rex_feed_custom_items_wrapper, .rex_feed_custom_wrapper, .rex_feed_custom_wrapper' ).fadeOut();
        }
    } );

    $( document ).on( 'change', '#wpfm_fb_pixel', enable_fb_pixel );

    $( document ).on( 'change', '#remove_plugin_data', remove_plugin_data );

    $( document ).on( 'change', '#wpfm_enable_log', wpfm_enable_log );

    $( document ).on( 'change', '#rex-product-allow-private', allow_private );

    $( document ).on( 'change', 'input[name="rex_feed_schedule"]', rex_feed_manage_custom_cron_schedule_fields );

    $( document ).on( 'change', '.attr-val-dropdown', render_custom_buttons_on_change );

    $( document ).on( 'change', 'select#wpfm_rollback_options', rex_feed_process_rollback_button ).trigger('change');

    $( document ).on( 'change', 'input#wpfm_hide_char', rex_feed_save_character_limit_option );

    $( document ).on( 'change', 'select.sanitize-dropdown', rex_feed_update_multiple_filter_counter );

    $( document ).on( 'change', '#rex_feed_cats_check_all_btn, #rex_feed_tags_check_all_btn', rex_feed_check_uncheck_all_tax );

    $( document ).on( 'change', 'select.attr-dropdown, select.attr-val-dropdown', rex_feed_auto_select_google_shipping_tax );

    $( document ).on( 'submit', '#rex-google-merchant', save_google_merchant_settings );

    $( document ).on( "submit", "#wpfm-per-batch", update_per_batch );

    $( document ).on( "submit", "#wpfm-frontend-fields", save_wpfm_custom_fields_data );

    $( document ).on( "submit", "#wpfm-error-log-form", show_wpfm_error_log );

    $( document ).on( "submit", "#wpfm-fb-pixel", save_fb_pixel_id );

    $( document ).on( "submit", "#wpfm-transient-settings", save_wpfm_transient );

    $( document ).on( "select2:open", rex_feed_focus_merchant_search_bar );


    // ==================================================================


    function rex_feed_niceselect( event ) {
        $( '#rex_feed_products select' ).niceSelect();
        if ( $( '#rex_feed_xml_file' ).val() === '' ) {
            $( '#rex_feed_file_link' ).slideUp( 'fast' );
        }


        //---------popup when click disabled input-------
        $( ".single-merchant.wpfm-pro .wpfm-pro-cta" ).on( "click", function ( e ) {
            e.preventDefault();
            $( ".premium-merchant-alert" ).addClass( "show-alert" );
        } );

        $( ".premium-merchant-alert .close, .premium-merchant-alert button.close, .premium-merchant-alert" ).on( "click", function () {
            $( ".premium-merchant-alert" ).removeClass( "show-alert" );
        } );

        $( ".premium-merchant-alert .alert-box" ).on( "click", function ( e ) {
            e.stopPropagation();
        } );
    }

    /**
     * Function for updating select and input box name
     * attribute under a table-row.
     */
    function updateFormNameAtts( $row, rowId, filter ) {
        var name, $el;
        $el = $row.find( 'input, select' );
        $el.each( function ( index, item ) {
            name = $( item ).attr( 'name' );
            if ( $( item ).parent().hasClass( 'static-input' ) ) {
                $( item ).parent().hide();
            }
            if ( name != undefined ) {
                // get new name via regex
                if ( filter ) {
                    name = name.replace( /^ff\[\d+\]/, 'ff[' + rowId + ']' );
                    name = name.replace( /^fr\[\d+\]/, 'fr[' + rowId + ']' );
                    $( item ).attr( 'name', name );
                } else {
                    name = name.replace( /^fc\[\d+\]/, 'fc[' + rowId + ']' );
                    $( item ).attr( 'name', name );
                }

            }
        } );
    }

    function rex_feed_show_analytics_params( event ) {
        var checked = $( '#rex_feed_analytics_params_options' ).prop( "checked" );

        if ( checked === true ) {
            $( '.rex_feed_analytics_params' ).show();
        } else {
            $( '.rex_feed_analytics_params' ).hide();
        }
    }

    function rex_feed_load_config_table( event ) {
        var $confBox = $( '#rex_feed_config_heading .inside' );
        var merchant_name = $( '#rex_feed_merchant' ).find( ':selected' ).val();

        if ( merchant_name !== '-1' ) {
            $confBox.find( '.rex-loading-spinner' ).css( 'display', 'flex' );
            var $payload = {
                merchant: $( '#rex_feed_merchant' ).find( ':selected' ).val(),
                post_id: $( '#post_ID' ).val(),
            };

            wpAjaxHelperRequest( 'merchant-change', $payload )
                .done( function ( response ) {
                    if ( response ) {
                        $( '.rex-feed-feed-format' ).find( '.rex_feed_feed-format option' ).each( function () {
                            var option_value = $( this ).val();
                            if ( jQuery.inArray( option_value, response.feed_format ) === -1 ) {
                                $( this ).removeAttr( 'selected' );
                                $( this ).attr( 'disabled', 'disabled' );
                            } else {
                                $( this ).removeAttr( 'disabled' );
                            }
                        } );

                        $( '.rex-feed-feed-separator' ).find( '#rex_feed_separator option' ).each( function () {
                            var option_value = $( this ).val();
                            if ( jQuery.inArray( option_value, response.feed_separator ) === -1 ) {
                                $( this ).removeAttr( 'selected' );
                                $( this ).attr( 'disabled', 'disabled' );
                            } else {
                                $( this ).removeAttr( 'disabled' );
                            }
                        } );

                        var selected = $( '.rex-feed-feed-format' ).find( '.rex_feed_feed-format' ).val();
                        var selected_sep = $( '.rex-feed-feed-separator' ).find( '#rex_feed_separator' ).val();

                        if ( !selected ) {
                            $( '.rex-feed-feed-format' ).find( '.rex_feed_feed-format' ).val( response.feed_format[ 0 ] );
                        }
                        if ( !selected_sep ) {
                            $( '.rex-feed-feed-separator' ).find( '#rex_feed_separator' ).val( response.feed_separator[ 0 ] );
                        }

                        if ( selected === 'csv' ) {
                            $( '.rex-feed-feed-separator' ).fadeIn();
                        }
                        else if ( merchant_name === 'trovino' || merchant_name === 'cercavino' ) {
                            $( '.rex-feed-feed-separator' ).fadeIn();
                        }
                        else {
                            $( '.rex-feed-feed-separator' ).fadeOut();
                        }
                    }

                    $confBox.fadeOut();
                    var configTable = document.getElementsByClassName( "wpfm-field-mappings" )[ 0 ];
                    configTable.innerHTML = response.html;

                    $confBox.fadeIn();
                    $( '#rex_feed_config_heading .inside #config-table' ).fadeIn();

                    $( '#rex_feed_config_heading .inside .rex-loading-spinner' ).css( 'display', 'none' );
                    $( '#rex_feed_config_heading #rex-feed-footer-btn' ).css( 'border-radius', '0 0 10px 10px' );

                    $( '#rex_feed_conf .rex-feed-config-heading' ).css( 'display', 'block' );
                    $( '#rex-new-attr, #rex-new-custom-attr' ).css( 'display', 'inline-block' );

                    rex_feed_hide_char_limit_col();
                    dynamic_pricing( event );
                    render_custom_buttons( event );
                    rex_feed_hide_separators_group( event );
                    $( '.sanitize-dropdown' ).select2({
                        closeOnSelect: false,
                    });
                    $( '.select2-attr-dropdown' ).select2();
                    rex_feed_render_multiple_filter_counter();
                } )
                .fail( function ( response ) {
                    $( '#rex_feed_config_heading .inside .rex-loading-spinner' ).css( 'display', 'none' );
                    console.log( 'Uh, oh! Merchant change returned error!' );
                    
                } );
        }
        else {
            $confBox.find( '#config-table' ).css( 'display', 'none' );
            $( '#rex_feed_config_heading #rex-feed-footer-btn' ).css( 'border-radius', '11px' );
        }
    }

    function rex_feed_view_filter_metaboxes( event ) {
        var $payload = {
            feed_id: rex_wpfm_ajax.feed_id
        }

        $( '#rex-feed-product-taxonomies' ).hide();
        $( '.rex-feed-tags-wrapper' ).hide();
        $( '.rex-feed-product-filter-ids__area' ).hide();
        $( "#rex_feed_product_filters .inside .rex-loading-spinner" ).fadeIn();

        wpAjaxHelperRequest( 'rex-feed-load-taxonomies', $payload )
            .done( function ( response ) {
                if ( response ) {
                    if ( response.success ) {
                        var selected = $( '#rex_feed_products' ).find( ':selected' ).val();

                        if ( 'all' === selected || 'featured' === selected ) {
                            $( "#rex_feed_product_filters .inside .rex-loading-spinner" ).hide();
                            $( '#rex-feed-product-taxonomies' ).hide();
                            $( '#rex-feed-product-taxonomies #rex-feed-product-taxonomies-contents' ).remove();
                            $( '.rex-feed-tags-wrapper' ).hide();
                            $( '.rex-feed-product-filter-ids__area' ).hide();
                            if ( 'all' === selected ) {
                                $( 'div#rex-feed-featured-product' ).hide();
                                $( 'div#rex-feed-published-product' ).show();
                            }
                            else {
                                $( 'div#rex-feed-published-product' ).hide();
                                $( 'div#rex-feed-featured-product' ).show();
                            }
                        }
                        else if ( selected === 'filter' ) {
                            $( "#rex_feed_product_filters .inside .rex-loading-spinner" ).hide();
                            $( '#rex-feed-product-taxonomies' ).hide();
                            $( '#rex-feed-product-taxonomies #rex-feed-product-taxonomies-contents' ).remove();
                            $( '.rex-feed-product-filter-ids__area' ).hide();
                            $( 'div#rex-feed-published-product' ).hide();
                            $( 'div#rex-feed-featured-product' ).hide();
                            $( '#rex-feed-config-rules' ).show();
                        }
                        else if ( selected === 'product_cat' || selected === 'product_tag' ) {
                            let tax_contents = $( '#rex-feed-product-taxonomies-contents' );
                            if ( tax_contents.length === 0 ) {
                                $( '#rex-feed-product-taxonomies' ).append( response.html_content );
                            }
                            $( "#rex_feed_product_filters .inside .rex-loading-spinner" ).hide();
                            $( '.rex-feed-product-filter-ids__area' ).hide();
                            $( 'div#rex-feed-published-product' ).hide();
                            $( 'div#rex-feed-featured-product' ).hide();
                            $( '#rex-feed-product-taxonomies' ).show();
                            if ( selected === 'product_cat' ) {
                                $( '#rex-feed-product-tags' ).hide();
                                $( '#rex-feed-product-cats' ).show();
                            } else {
                                $( '#rex-feed-product-cats' ).hide();
                                $( '#rex-feed-product-tags' ).show();
                            }
                        }
                        else if ( selected === 'product_filter' ) {
                            $( "#rex_feed_product_filters .inside .rex-loading-spinner" ).hide();
                            $( '#rex-feed-product-taxonomies' ).hide();
                            $( '#rex-feed-product-taxonomies #rex-feed-product-taxonomies-contents' ).remove();
                            $( '.select2-search__field' ).removeAttr('style');
                            $( 'div#rex-feed-published-product' ).hide();
                            $( 'div#rex-feed-featured-product' ).hide();
                            $( '.rex-feed-product-filter-ids__area' ).show();
                        }

                        $( "#rex_feed_product_filters .inside .rex-loading-spinner" ).fadeOut();
                    }
                }
            } )
            .fail( function ( response ) {
                $( "#rex_feed_product_filters .inside .rex-loading-spinner" ).fadeOut();

                $( "#rex_feed_product_filters .inside .rex-loading-spinner" ).hide();
                $( '#rex-feed-product-taxonomies' ).hide();
                $( '.rex-feed-tags-wrapper' ).hide();
                $( '.rex-feed-product-filter-ids__area' ).hide();

                console.log( 'Uh, oh!' );
                
            } );
    }

    /**
     * Dynamic pricing
     * @param event
     */
    function dynamic_pricing( event ) {
        var is_premium = rex_wpfm_ajax.is_premium;

        if ( is_premium ) {
            var meta_value_selects = $( 'div.meta-dropdown' ).children();
            var rows = meta_value_selects.length - 1;

            for ( var rowId = 0; rowId < rows; rowId++ ) {
                var selected_val = $( 'select[name="fc[' + rowId + '][meta_key]"]' ).val();
                var limit_row = $( 'input[name="fc[' + rowId + '][limit]"]' );
                var meta_row = $( 'select[name="fc[' + rowId + '][meta_key]"]' );
                var prices = [
                    'price',
                    'current_price',
                    'sale_price',
                    'price_with_tax',
                    'current_price_with_tax',
                    'sale_price_with_tax',
                    'price_excl_tax',
                    'current_price_excl_tax',
                    'sale_price_excl_tax',
                    'price_db',
                    'current_price_db',
                    'sale_price_db'
                ];

                if ( $.inArray( selected_val, prices ) !== -1 ) {
                    limit_row.attr( 'placeholder', 'Update Price i.e. +25%' );
                    limit_row.addClass( 'dynamic-placeholder' );
                    meta_row.addClass( 'dynamic-placeholder' );
                } else {
                    limit_row.removeAttr( 'placeholder' );
                    limit_row.removeClass( 'dynamic-placeholder' );
                    meta_row.removeClass( 'dynamic-placeholder' );
                }
            }
        }
        // Dynamic pricing
    }

    /**
     * Category mapping button
     * @param event
     */
    function render_custom_buttons( event ) {
        let attr_tr = $( 'div#rex_feed_config_heading' ).children( 'div.inside' ).children( 'table#config-table' ).children( 'tbody' ).children( 'tr' );

        attr_tr.each( function ( index, _element ) {
            if ( index ) {
                let row_id = $( this ).attr( 'data-row-id' );
                let opt_group_label = $( 'select[name="fc[' + row_id + '][meta_key]"] :selected' ).parent().attr('label');
                let meta_val = $( 'select[name="fc[' + row_id + '][meta_key]"]' ).val();

                if ( 'Category Map' === opt_group_label ) {
                    let url = rex_wpfm_ajax.category_mapping_url + '&wpfm-expand=' + meta_val;
                    $('select[name="fc[' + row_id + '][meta_key]"]').parent().append("<p style='margin-top: 10px; margin-left: 5px' class='rex_cat_map' id='rex_cat_map_"+row_id+"'><a style='font-size: 10px;' class='rex_cat_map' href='"+ url +"' target='_blank'>"+config_btn+"</a></p>");
                }

                if ( 'title' === meta_val ) {
                    let url = 'https://rextheme.com/docs/how-to-merge-multiple-attributes-values-together-with-the-combined-fields-feature/?utm_source=plugin&utm_medium=combined_attributes_link&utm_campaign=pfm_plugin\n';
                    $('select[name="fc[' + row_id + '][meta_key]"]').parent().append("<p style='margin-top: 10px; margin-left: 5px' class='rex_cat_map' id='rex_opt_title_btn_" + row_id + "'><a style='font-size: 10px;' class='rex_cat_map' href='"+ url +"' target='_blank'>"+optimize_pr_title_btn+"</a></p>");
                }
            }
        } );
        // Google category mapping button ENDS
    }


    /**
     * @desc Render custom button on attribute value change
     * @since 7.2.19
     */
    function render_custom_buttons_on_change() {
        let rowId = $(this).parent().parent().parent().attr('data-row-id');
        let selected_val = $( this ).val();
        let opt_group_label = $("option:selected", this).parent().attr( 'label' );

        if ( 'Category Map' === opt_group_label ) {
            let url = rex_wpfm_ajax.category_mapping_url + '&wpfm-expand=' + selected_val;

            if ( $( '#rex_cat_map_' + rowId ).length === 0 ) {
                $( this ).parent().append("<p style='margin-top: 10px; margin-left: 5px' class='rex_cat_map' id='rex_cat_map_"+rowId+"'><a style='font-size: 10px;' class='rex_cat_map' href='"+ url +"' target='_blank'>"+config_btn+"</a></p>");
            }
            else {
                $( '#rex_cat_map_' + rowId ).remove();
                $( this ).parent().append("<p style='margin-top: 10px; margin-left: 5px' class='rex_cat_map' id='rex_cat_map_"+rowId+"'><a style='font-size: 10px;' class='rex_cat_map' href='"+ url +"' target='_blank'>"+config_btn+"</a></p>");
            }
        }
        else {
            $( '#rex_cat_map_' + rowId ).remove();
        }

        if ( 'title' === selected_val ) {
            let url = 'https://rextheme.com/docs/how-to-merge-multiple-attributes-values-together-with-the-combined-fields-feature/?utm_source=plugin&utm_medium=combined_attributes_link&utm_campaign=pfm_plugin\n';

            if ( $( '#rex_opt_title_btn_' + rowId ).length === 0 ) {
                $( this ).parent().append("<p style='margin-top: 10px; margin-left: 5px' class='rex_cat_map' id='rex_opt_title_btn_" + rowId + "'><a style='font-size: 10px;' class='rex_cat_map' href='"+ url +"' target='_blank'>"+optimize_pr_title_btn+"</a></p>");
            }
            else {
                $( '#rex_opt_title_btn_' + rowId ).remove();
                $( this ).parent().append("<p style='margin-top: 10px; margin-left: 5px' class='rex_cat_map' id='rex_opt_title_btn_" + rowId + "'><a style='font-size: 10px;' class='rex_cat_map' href='"+ url +"' target='_blank'>"+optimize_pr_title_btn+"</a></p>");
            }
        }
        else {
            $( '#rex_opt_title_btn_'+rowId ).remove();
        }
    }

    /**
     * Event listener for Merchant change for eBay sellers functionality.
     */
    function rex_feed_ebay_seller_fields() {
        var merchant = $( '#rex_feed_merchant' ).find( ':selected' ).val();

        if ( merchant === 'ebay_seller' || merchant === 'ebay_seller_tickets' ) {
            $( '.rex_feed_ebay_seller_fields' ).fadeIn();
        } else {
            $( '.rex_feed_ebay_seller_fields' ).fadeOut();
        }
    }

    function get_checkbox_val( name ) {
        var items = 'input[name="rex_feed_' + name + '[]"]';
        var vals = [];

        $( items ).each( function () {
            if ( $( this ).prop( 'checked' ) == true ) {
                vals.push( $( this ).val() );
            }
        } );

        return vals;
    }

    /**
     * @desc Check if any required attribute(s) is/are missing
     * @since 7.2.19
     * @param event
     */
    function rex_feed_is_google_attribute_missing( event ) {
        let is_trusted_event = true;
        let is_attr_missing;

        try {
            is_trusted_event = event.originalEvent.isTrusted;
        }
        catch (e) {
            is_trusted_event = false;
            is_attr_missing = true;
        }

        if ( is_trusted_event ) {
            event.preventDefault();
            is_attr_missing = rex_feed_render_missing_attr_popup();
            if ( 'rex-bottom-preview-btn' === $(this).attr('id') ) {
                $( '#rex_google_missing_attr_okay_btn' ).addClass( 'bottom-preview-btn' )
            }
        }

        if ( !is_attr_missing ) {
            get_product_number( $( this ) );
        }
    }

    /**
     * Start the feed processing
     * @param event
     */
    function get_product_number( $this ) {
        $( 'section#rex_feed_google_req_attr_warning_popup' ).hide();
        let merchant_name = $( '#rex_feed_merchant' ).find( ':selected' ).val();
        let feed_title = $( '.post-type-product-feed input#title' ).val();
        let submit_button = '';
        let is_preview = '';
        
        try {
            submit_button = $this.attr( 'id' );
            is_preview = $this.hasClass( 'bottom-preview-btn' );
        }
        catch (e) {
            submit_button = $( this ).attr( 'id' );
            is_preview = $( this ).hasClass( 'bottom-preview-btn' );
        }
        
        if ( '-1' === merchant_name ) {
            alert( 'Please choose a merchant!' );
            return;
        }

        if ( $( '.wpfm-field-mappings' ).find( 'tbody tr:first' ).css( 'display' ) == 'none' ) {
            $( '.wpfm-field-mappings' ).find( 'tbody tr:first' ).remove();
        }

        $( '#wpfm-feed-clock' ).stopwatch().stopwatch( 'start' );

        if ( is_preview && ( 'rex_google_missing_attr_okay_btn' === submit_button ) ) {
            submit_button = 'rex-bottom-preview-btn';
        }

        let $payload = {
            feed_id: rex_wpfm_ajax.feed_id,
            feed_config: $( 'form' ).serialize(),
            button_id: submit_button,
            feed_title: feed_title
        };

        wpAjaxHelperRequest( 'my-handle', $payload )
            .done( function ( response ) {
                if ( 'duplicate' === response.feed_title ) {
                    $( '.post-type-product-feed input#title' ).css( 'border', '1px solid red' );
                    alert( 'Please set an unique feed title!' );
                }
                else {
                    $( '#publishing-action span.spinner' ).addClass( 'is-active' );
                    $( '.post-type-product-feed input#publish' ).addClass( 'disabled' );

                    $( '.rex-feed-publish-btn span.spinner' ).addClass( 'is-active' );

                    $( '#rex-bottom-publish-btn, #rex-bottom-preview-btn' ).css( 'cursor', 'not-allowed' );
                    $( '#rex-bottom-publish-btn, #rex-bottom-preview-btn' ).css( 'background-color', '#f6f7f7' );
                    $( '#rex-bottom-publish-btn, #rex-bottom-preview-btn' ).css( 'border', '1px solid #e9e9ea' );
                    $( '#rex-bottom-publish-btn, #rex-bottom-preview-btn' ).css( 'color', '#a7aaad' );

                    $( '.post-type-product-feed #rex_feed_progress_bar' ).fadeIn();
                    $( '.rex-feed-progressbar, .progress-msg' ).fadeIn();
                    $( '.progress-msg span' ).html( 'Calculating products.....' );

                    $( '.post-type-product-feed input#title' ).css( 'border', 'unset' );

                    let per_batch = 0;
                    if ( is_preview ) {
                        per_batch = 10;
                        generate_feed( response.products, 0, 1, per_batch, 1 );
                    }
                    else {
                        per_batch = response.per_batch ? parseInt( response.per_batch ) : 200;
                        generate_feed( response.products, 0, 1, per_batch, response.total_batch );
                    }
                }
            } )
            .fail( function ( response ) {
                $( '#publishing-action span.spinner' ).removeClass( 'is-active' );
                $( '#publish' ).removeClass( 'disabled' );
                $( '.rex-feed-publish-btn span.spinner' ).removeClass( 'is-active' );
                console.log( 'Uh, oh!' );
            } );
    }

    /**
     * Generate feed
     * @param product
     * @param offset
     * @param batch
     * @param per_batch
     * @param total_batch
     */
    function generate_feed( product, offset, batch, per_batch, total_batch ) {

        per_batch = typeof per_batch !== 'undefined' ? per_batch : 50;
        $( '#rex-feed-progress' ).show();
        var $payload = {
            merchant: $( '#rex_feed_merchant' ).find( ':selected' ).val(),
            feed_format: $( '#rex_feed_feed_format' ).find( ':selected' ).val(),
            localization: $( '#rex_feed_ebay_mip_localization' ).find( ':selected' ).val(),
            ebay_cat_id: $( '#rex_feed_ebay_seller_category' ).val(),

            info: {
                post_id: $( '#post_ID' ).val(),
                title: $( '#title' ).val(),
                desc: $( '#title' ).val(),
                offset: offset,
                batch: batch,
                total_batch: total_batch,
                per_batch: per_batch,
            },

            products: {
                products_scope: $( '#rex_feed_products' ).find( ':selected' ).val(),
                tags: get_checkbox_val( 'tags' ),
                cats: get_checkbox_val( 'cats' ),
                data: $( '#rex_feed_product_filter_ids' ).val(),
            },

            feed_config: $( 'form' ).serialize(),
        };

        var batches = total_batch;
        console.log( 'Total Batch: ' + batches );
        console.log( 'Total Product(s): ' + product );
        console.log( 'Processing Batch Number: ' + batch );
        console.log( 'Offset Number: ' + offset );

        var progressbar = 100 / batches;
        progressWidth = progressWidth + progressbar;
        if ( progressWidth > 100 ) {
            progressWidth = 100;
        }

        if ( progressWidth >= 100 ) {
            $( '.progress-msg span' ).html( 'Generating feed. Please wait....' );
        }
        else {
            $( '.progress-msg span' ).html( 'Processing feed....' );
        }


        wpAjaxHelperRequest( 'generate-feed', $payload )
            .done( function ( response ) {
                console.log( 'Woohoo!' );
                var msg = '<div id="message" class="error notice notice-error is-dismissible rex-feed-notice"><p>Your feed exceed the limit.Please <a href="edit.php?post_type=product-feed&page=best-woocommerce-feed-pricing">Upgrade!!!</a> </p><button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>';
                if ( response == 'false' || response == '' ) {
                    generate_feed( product, offset, batch, per_batch, total_batch );
                }
                else if ( response.msg == 'finish' ) {
                    rex_feed_feed_progressBar( progressWidth );
                    $( '#wpfm-feed-clock' ).stopwatch().stopwatch( 'stop' );
                    $( '#publish, #rex-bottom-publish-btn, #rex-bottom-preview-btn' ).removeClass( 'disabled' );
                    $( document ).off( 'click', '#publish, #rex-bottom-publish-btn, #rex-bottom-preview-btn', get_product_number );
                    $( '#publish' ).trigger( 'click' );


                }
                else if ( response.msg == 'failForInvalidEntry' ) {
                    alert( "Please set proper values for the mandatory field like Shipping Id, Who made, When made, Taxonomy Id." );
                    rex_feed_feed_generation_error_helper();
                    $( '.post-type-product-feed #rex_feed_progress_bar' ).fadeOut();
                    return false
                }
                else if ( response.msg == 'failToAuthorize' ) {
                    alert( "No Authorization detected, Need Authorization From Etsy first." );
                    rex_feed_feed_generation_error_helper();
                    $( '.post-type-product-feed #rex_feed_progress_bar' ).fadeOut();
                    return false
                }
                else if ( response.msg == 'failForAuthExpire' ) {
                    alert( "Expire you authorization with etsy, Need Authorization From Etsy first." );
                    rex_feed_feed_generation_error_helper();
                    $( '.post-type-product-feed #rex_feed_progress_bar' ).fadeOut();
                    return false
                }
                else if ( response.msg == 'failForEmptyProduct' ) {
                    alert( "Product sending failed - No available products" );
                    rex_feed_feed_generation_error_helper();
                    $( '.post-type-product-feed #rex_feed_progress_bar' ).fadeOut();
                    return false
                }
                else {
                    if ( batch < batches ) {
                        offset = offset + per_batch;
                        batch++;
                        rex_feed_feed_progressBar( progressWidth );
                        generate_feed( product, offset, batch, per_batch, total_batch );
                    }
                }
            } )
            .fail( function ( response ) {
                $( ".progressbar-bar" ).css( 'background', '#ff0000' );
                $( ".progressbar-bar" ).css( 'border-color', '#ff0000' );
                $( ".progress-msg span" ).css( 'color', '#ff0000' );
                $( ".progress-msg i" ).css( 'color', '#ff0000' );
                $( ".progress-msg span" ).html( response.statusText );
                $( '#publishing-action span.spinner' ).removeClass( 'is-active' );
                $( '#publish' ).removeClass( 'disabled' );
                $( '#wpfm-feed-clock' ).stopwatch().stopwatch( 'stop' );
                console.log( 'Uh, oh!' );
                
            } );
    }

    function rex_feed_feed_generation_error_helper() {
        $( '#publishing-action span.spinner' ).removeClass( 'is-active' );
        $( '#publish' ).removeClass( 'disabled' );
        $( '#wpfm-feed-clock' ).stopwatch().stopwatch( 'stop' );
        $( '#rex-feed-progress' ).hide();
    }

    function rex_feed_feed_progressBar( width ) {
        var deferred = $.Deferred();

        $( '.progressbar-bar' ).animate( {
            width: Math.ceil( width ) + '%'
        }, 500 );
        $( '.progressbar-bar-percent' ).html( Math.ceil( width ) + '%' );
        return deferred.promise();
    }

    /*
     * google merchant settings
     */
    function save_google_merchant_settings( event ) {
        event.preventDefault();
        $( '#rex_feed_config_heading .inside .rex-loading-spinner' ).css( 'display', 'flex' );
        var payload = {
            client_id: $( this ).find( '#client_id' ).val(),
            client_secret: $( this ).find( '#client_secret' ).val(),
            merchant_id: $( this ).find( '#merchant_id' ).val(),
            merchant_settings: true
        };
        wpAjaxHelperRequest( 'google-merchant-settings', payload )
            .success( function ( response ) {
                console.log( 'Woohoo!' );
                $( '.merchant-action' ).html( response.html );
                $( '#rex_feed_config_heading .inside .rex-loading-spinner' ).css( 'display', 'none' );
                location.reload();
            } )
            .error( function ( response ) {
                console.log( 'Uh, oh!' );
                $( '#rex_feed_config_heading .inside .rex-loading-spinner' ).css( 'display', 'none' );
                
            } );
    }

    /*
     * Send feed to Google
     * Merchant Center
     */
    function send_to_google( event ) {
        event.preventDefault();
        $( '#rex_feed_config_heading .inside .rex-loading-spinner' ).css( 'display', 'flex' );

        let payload = {
            feed_id: $( '#post_ID' ).val(),
            schedule: $( '#rex_feed_google_schedule option:selected' ).val(),
            hour: $( '#rex_feed_google_schedule_time option:selected' ).val(),
            country: $( '#rex_feed_google_target_country' ).val(),
            language: $( '#rex_feed_google_target_language' ).val()
        };

        if ( $( '#rex_feed_google_schedule option:selected' ).val() == 'monthly' ) {
            payload[ 'month' ] = $( '#rex_feed_google_schedule_month option:selected' ).val();
            payload[ 'day' ] = '';
        } else if ( $( '#rex_feed_google_schedule option:selected' ).val() == 'weekly' ) {
            payload[ 'day' ] = $( '#rex_feed_google_schedule_week_day option:selected' ).val();
            payload[ 'month' ] = '';
        } else {
            payload[ 'month' ] = '';
            payload[ 'day' ] = '';
        }
        $( '.rex-google-status' ).removeClass( 'info' );
        $( '.rex-google-status' ).removeClass( 'success' );
        $( '.rex-google-status' ).removeClass( 'warning' );
        $( '.rex-google-status' ).removeClass( 'error' );
        $( '.rex-google-status' ).addClass( 'info' );
        $( '.rex-google-status' ).show();
        $( '.rex-google-status' ).html( '<p>Feed is sending. Please wait...</p>' );
        wpAjaxHelperRequest( 'send-to-google', payload )
            .success( function ( response ) {
                if ( response.success ) {
                    $( '.rex-google-status' ).removeClass( 'info' );
                    $( '.rex-google-status' ).removeClass( 'success' );
                    $( '.rex-google-status' ).removeClass( 'warning' );
                    $( '.rex-google-status' ).removeClass( 'error' );
                    $( '.rex-google-status' ).addClass( 'success' );
                    $( '.rex-google-status' ).show();
                    $( '.rex-google-status' ).html( '<p>Feed sent to google successfully.</p>' );
                    console.log( 'Woohoo!' );
                    location.reload();
                } else {
                    $( '.rex-google-status' ).removeClass( 'info' );
                    $( '.rex-google-status' ).removeClass( 'success' );
                    $( '.rex-google-status' ).removeClass( 'warning' );
                    $( '.rex-google-status' ).removeClass( 'error' );
                    $( '.rex-google-status' ).addClass( 'warning' );
                    $( '.rex-google-status' ).show();
                    $( '.rex-google-status' ).html( '<p>Feed not sent to google. Please check.</p><p>' + response.reason + ': ' + response.message + '</p>' );
                    console.log( response )
                }
            } )
            .error( function ( response ) {
                $( '.rex-google-status' ).removeClass( 'info' );
                $( '.rex-google-status' ).removeClass( 'success' );
                $( '.rex-google-status' ).removeClass( 'warning' );
                $( '.rex-google-status' ).removeClass( 'error' );
                $( '.rex-google-status' ).addClass( 'error' );
                $( '.rex-google-status' ).show();
                $( '.rex-google-status' ).html( '<p>Something wrong happened. Please check.</p><p>' + response.reason + ': ' + response.message + '</p>' );
                console.log( 'Uh, oh!' );
                console.log( response );
                
            } );
    }

    function reset_form( event ) {
        event.preventDefault();
        $( this ).closest( 'form' ).find( "input[type=text]" ).not( ':disabled' ).val( "" );
        $( this ).closest( 'form' ).find( "button[type=submit]" ).prop( 'disabled', false );
    }

    /**
     * Change merchant status
     */
    function product_feed_change_merchant_status() {
        var payload = {};
        var $this = $( this );
        var key = $this.attr( 'data-value' );
        var name = $this.attr( 'data-name' );
        var isfree = $this.attr( 'data-is-free' );
        if ( $this.is( ":checked" ) ) {
            payload[ key ] = {
                status: 1,
                name: name,
                free: isfree,
            };
        } else {
            payload[ key ] = {
                status: 0,
                name: name,
                free: isfree,
            };
        }


        wpAjaxHelperRequest( 'rex-product-change-merchant-status', payload )
            .success( function ( response ) {

                console.log( 'woohoo!' );
            } )
            .error( function ( response ) {
                console.log( 'uh, oh!' );
                
            } );
    }

    /**
     * Update product per batch
     * @param e
     */
    function update_per_batch( e ) {
        e.preventDefault();
        var $form = $( this );
        $form.find( "button.save-batch span" ).text( "" );
        $form.find( "button.save-batch i" ).show();
        var per_batch = $form.find( '#wpfm_product_per_batch' ).val();
        wpAjaxHelperRequest( 'rex-product-update-batch-size', per_batch )
            .success( function ( response ) {
                $form.find( "button.save-batch i" ).hide();
                $form.find( "button.save-batch span" ).text( "saved" );
                setTimeout( function () {
                    $form.find( "button.save-batch span" ).text( "save" );
                }, 1000 );
                console.log( 'woohoo!' );
            } )
            .error( function ( response ) {
                $form.find( "button.save-batch i" ).hide();
                $form.find( "button.save-batch span" ).text( "failed" );
                setTimeout( function () {
                    $form.find( "button.save-batch span" ).text( "save" );
                }, 1000 );
                console.log( 'uh, oh!' );
                
            } );
    }


    /**
     * Save WPFM custom meta fields to show the values in the front end
     * @param e
     */
    function save_wpfm_custom_fields_data( e ) {
        e.preventDefault();
        var $form = $( this );
        $form.find( "button.save-wpfm-fields-show span" ).text( "" );
        $form.find( "button.save-wpfm-fields-show i" ).show();

        var fields_value = $.map($('input[name="wpfm_product_custom_fields_frontend[]"]:checked'), function(c){return c.value; });
        var payload = {
            security: rex_wpfm_ajax.ajax_nonce,
            fields_value: fields_value,
        };

        wpAjaxHelperRequest( 'rex-product-save-custom-fields-data', payload )
            .success( function ( response ) {
                $form.find( "button.save-wpfm-fields-show i" ).hide();
                $form.find( "button.save-wpfm-fields-show span" ).text( "saved" );
                setTimeout( function () {
                    $form.find( "button.save-wpfm-fields-show span" ).text( "save" );
                }, 1000 );
                console.log( 'woohoo!' );
            } )
            .error( function ( response ) {
                $form.find( "button.save-wpfm-fields-show i" ).hide();
                $form.find( "button.save-wpfm-fields-show span" ).text( "failed" );
                setTimeout( function () {
                    $form.find( "button.save-wpfm-fields-show span" ).text( "save" );
                }, 1000 );
                console.log( 'uh, oh!' );
                
            } );
    }

    /**
     *
     * @param e
     */
    function wpfm_clear_batch( e ) {
        e.preventDefault();
        let payload = {};
        $( this ).find( "span" ).hide();
        $( this ).find( "i" ).show();
        wpAjaxHelperRequest( 'rex-product-clear-batch', payload )
            .success( function ( response ) {
                $( "#wpfm-clear-batch" ).find( "i" ).hide();
                $( "#wpfm-clear-batch" ).find( "span" ).show();
            } )
            .error( function ( response ) {
                console.log( 'uh, oh!' );
                
            } );
    }

    //----------setting tab-------
    function rex_feed_settings_tab( event ) {
        var url = window.location.href;
        if ( $( this ).length > 0 ) {
            var tab_id = $( this ).attr( 'data-tab' );
            $( 'ul.rex-settings-tabs li' ).removeClass( 'active' );
            $( '.rex-settings-tab-content .tab-content' ).removeClass( 'active' );

            $( this ).addClass( 'active' );
            $( "#" + tab_id ).addClass( 'active' );
        }
        if ( $( this ).length === 0 && url.includes( 'page=wpfm_dashboard&tab=merchants' ) ) {
            $( 'ul.rex-settings-tabs li[data-tab=tab4]' ).removeClass( 'active' );
            $( '.rex-settings-tab-content #tab4' ).removeClass( 'active' );

            $( 'ul.rex-settings-tabs li[data-tab=tab2]' ).addClass( 'active' );
            $( '#tab2' ).addClass( 'active' );
        }
    }

    /**
     * WPFM error log
     */
    function show_wpfm_error_log( e ) {
        e.preventDefault();
        var $form = $( this );
        var log_key = $form.find( '#wpfm-error-log option:selected' ).val();
        var payload = {
            'logKey': log_key
        };
        if ( !log_key ) {
            $( "#wpfm-log-copy" ).hide();
            $( '#log-viewer pre' ).html( '' );
        } else {
            wpAjaxHelperRequest( 'rex-product-feed-show-log', payload )
                .success( function ( response ) {
                    console.log( 'woohoo!' );

                    $( '#log-viewer pre' ).html( response.content );
                    if ( log_key ) {
                        $( "#wpfm-log-copy" ).show();
                    }
                    $( '#log-download' ).attr( 'href', response.file_url );
                } )
                .error( function ( response ) {

                    console.log( 'uh, oh!' );
                    
                } );
        }

    }

    /**
     * copy wpfm logs data
     *
     * @param event
     */
    function wpfm_copy_log( event ) {
        event.preventDefault();
        var elm = document.getElementById( "wpfm-log-content" );
        if ( document.body.createTextRange ) {
            var range = document.body.createTextRange();
            range.moveToElementText( elm );
            range.select();
            document.execCommand( "Copy" );
            alert( "Copied div content to clipboard" );
        } else if ( window.getSelection ) {
            var selection = window.getSelection();
            var range = document.createRange();
            range.selectNodeContents( elm );
            selection.removeAllRanges();
            selection.addRange( range );
            document.execCommand( "Copy" );
            alert( "Copied div content to clipboard" );
        }
    }

    /**
     * Enable/disable facebook pixel
     * @param event
     */
    function enable_fb_pixel( event ) {
        event.preventDefault();
        var payload = {};
        if ( $( this ).is( ":checked" ) ) {
            payload = {
                wpfm_fb_pixel_enabled: 'yes',
            };
        } else {
            payload = {
                wpfm_fb_pixel_enabled: 'no',
            };
        }
        wpAjaxHelperRequest( 'wpfm-enable-fb-pixel', payload )
            .success( function ( response ) {
                if ( response.data == 'enabled' ) {
                    $( '.wpfm-fb-pixel-field' ).removeClass( 'is-hidden' );
                } else {
                    $( '.wpfm-fb-pixel-field' ).addClass( 'is-hidden' );
                }
            } )
            .error( function ( response ) {
                console.log( 'Uh, oh!' );
                
            } );
    }

    /**
     * Update option for plugin data removal
     * @param event
     */
    function remove_plugin_data( event ) {
        event.preventDefault();
        var payload = {};
        if ( $( this ).is( ":checked" ) ) {
            payload = {
                wpfm_remove_plugin_data: 'yes',
            };
        } else {
            payload = {
                wpfm_remove_plugin_data: 'no',
            };
        }
        wpAjaxHelperRequest( 'wpfm-remove-plugin-data', payload )
            .success( function ( response ) {
                console.log( 'Saved' );
                
            } )
            .error( function ( response ) {
                console.log( 'Uh, oh!' );
                
            } );
    }

    /**
     * Save FB pixel ID
     * @param e
     */
    function save_fb_pixel_id( e ) {
        e.preventDefault();
        var $form = $( this );
        $form.find( "button.save-fb-pixel span" ).text( "" );
        $form.find( "button.save-fb-pixel i" ).show();
        var value = $form.find( '#wpfm_fb_pixel' ).val();
        wpAjaxHelperRequest( 'save-fb-pixel-value', value )
            .success( function ( response ) {
                $form.find( "button.save-fb-pixel i" ).hide();
                $form.find( "button.save-fb-pixel span" ).text( "saved" );
                setTimeout( function () {
                    $form.find( "button.save-fb-pixel span" ).text( "save" );
                }, 1000 );
                console.log( 'woohoo!' );
            } )
            .error( function ( response ) {
                $form.find( "button.save-fb-pixel i" ).hide();
                $form.find( "button.save-fb-pixel span" ).text( "failed" );
                setTimeout( function () {
                    $form.find( "button.save-fb-pixel span" ).text( "save" );
                }, 1000 );
                console.log( 'uh, oh!' );
                
            } );
    }

    /**
     * Log settings
     */
    function wpfm_enable_log() {
        var payload = {};
        if ( $( this ).is( ":checked" ) ) {
            payload = {
                wpfm_enable_log: 'yes',
            };
        } else {
            payload = {
                wpfm_enable_log: 'no',
            };
        }
        wpAjaxHelperRequest( 'rex-enable-log', payload )
            .success( function ( response ) {
                console.log( 'Woohoo!' );
            } )
            .error( function ( response ) {
                console.log( 'Uh, oh!' );
                
            } );
    }

    /**
     * Save WPFM transient TTL
     * @param e
     */
    function save_wpfm_transient( e ) {
        e.preventDefault();
        var $form = $( this );
        $form.find( "button.save-transient-button span" ).text( "" );
        $form.find( "button.save-transient-button i" ).show();
        var value = $form.find( '#wpfm_cache_ttl' ).val();
        var payload = {
            value: value,
        };
        wpAjaxHelperRequest( 'save-wpfm-transient', payload )
            .success( function ( response ) {
                $form.find( "button.save-transient-button i" ).hide();
                $form.find( "button.save-transient-button span" ).text( "saved" );
                setTimeout( function () {
                    $form.find( "button.save-transient-button span" ).text( "save" );
                }, 1000 );
                console.log( 'woohoo!' );
            } )
            .error( function ( response ) {
                $form.find( "button.ssave-fb-pixel i" ).hide();
                $form.find( "button.save-fb-pixel span" ).text( "failed" );
                setTimeout( function () {
                    $form.find( "button.save-fb-pixel span" ).text( "save" );
                }, 1000 );
                console.log( 'uh, oh!' );
            } );
    }

    /**
     * purge WPFM cache
     *
     * @param e
     */
    function purge_transient_cache( e ) {
        e.preventDefault();
        var payload = {};
        var $el = $( this );
        $el.find( "span" ).hide();
        $el.find( "i" ).show();

        wpAjaxHelperRequest( 'purge-wpfm-transient-cache', payload )
            .success( function ( response ) {
                $el.find( "i" ).hide();
                $el.find( "span" ).show();
                console.log( 'woohoo!' );
            } )
            .error( function ( response ) {
                $el.find( "i" ).hide();
                console.log( 'uh, oh!' );
            } );
    }

    function purge_transient_cache_on_feed( e ) {
        e.preventDefault();
        var status = $( '#publish' ).val();

        if ( 'Publish' === status ) {
            var answer = window.confirm( "All data will be lost?" );
            if ( answer ) {
                var payload = {};
                var $el = $( this );
                $el.find( "i" ).show();

                wpAjaxHelperRequest( 'purge-wpfm-transient-cache', payload )
                    .success( function ( response ) {
                        $el.find( "i" ).hide();
                        console.log( 'woohoo!' );
                        location.reload();
                    } )
                    .error( function ( response ) {
                        $el.find( "i" ).hide();
                        console.log( 'uh, oh!' );
                        
                    } );
            }
        } else {
            var payload = {};
            var $el = $( this );
            $el.find( "i" ).show();

            wpAjaxHelperRequest( 'purge-wpfm-transient-cache', payload )
                .success( function ( response ) {
                    $el.find( "i" ).hide();
                    console.log( 'woohoo!' );
                    location.reload();
                } )
                .error( function ( response ) {
                    $el.find( "i" ).hide();
                    console.log( 'uh, oh!' );
                    
                } );
        }
    }

    /**
     * Enable private products
     */
    function allow_private() {
        var payload = {};
        if ( $( this ).is( ":checked" ) ) {
            payload = {
                allow_private: 'yes',
            };
        } else {
            payload = {
                allow_private: 'no',
            };
        }
        wpAjaxHelperRequest( 'allow-private-products', payload )
            .success( function ( response ) {
                console.log( 'Woohoo!' );
            } )
            .error( function ( response ) {
                console.log( 'Uh, oh!' );
                
            } );
    }

    /**
     * Manage fields for cron custom scheduling
     */
    function rex_feed_manage_custom_cron_schedule_fields() {
        let selected_cron = $('input[name="rex_feed_schedule"]:checked').val();

        if ( selected_cron === 'custom' ) {
            $( '.rex_feed_custom_time_fields' ).slideDown();
        }
        else {
            $( '.rex_feed_custom_time_fields' ).slideUp();
        }

        if ( 'no' === selected_cron ) {
            $( 'input#rex_feed_update_on_product_change' ).prop( 'disabled', true );
            $( 'input#rex_feed_update_on_product_change' ).css( {
                'border-color': '#c3c4cf',
                'cursor': 'not-allowed'
            } );
        }
        else {
            $( 'input#rex_feed_update_on_product_change' ).prop( 'disabled', false );
            $( 'input#rex_feed_update_on_product_change' ).css( {
                'border-color': '#1db2fb',
                'cursor': 'pointer'
            } );
        }
    }

    function rex_feed_show_review_request( e ) {
        var is_published = $( '#publish' ).val();
        if ( is_published !== 'Publish' ){
            $( '.rex-feed-review' ).fadeIn();
        }
    }

    function rex_feed_merchant_list_select2( e ) {
        var url = window.location.href;

        if ( url.includes('&rex_feed_merchant=') ) {
            url = new URL( url );
            var feed_merchant = url.searchParams.get( 'rex_feed_merchant' );
            $('.rex-merchant-list-select2').val( feed_merchant ).trigger( 'change' ).select2({
                placeholder: "Please Select your merchant",
            });
        }
        else {
            $( '.rex-merchant-list-select2, .rex-setup-wizard-merchant-select2' ).select2({
                placeholder: "Please Select your merchant",
            });
        }
    }

    /**
     * @desc Renders missing attributes warning popup
     * for Google Shopping Feed
     * @since 7.2.19
     * @returns {boolean}
     */
    function rex_feed_render_missing_attr_popup() {
        let merchant_name = $('#rex_feed_merchant').find(':selected').val();
        let status = false;

        if (merchant_name === 'google') {
            let missing_attr = [];
            let payload = {
                feed_config: $('form[id=post]').serialize()
            };

            $.ajax({
                type: "POST",
                url: rex_wpfm_ajax.ajax_url,
                data: {
                    action: 'rex_feed_check_for_missing_attributes',
                    security: rex_wpfm_ajax.ajax_nonce,
                    payload: payload
                },
                dataType: 'JSON',
                async: false,

                success: function (response) {
                    let attr_inx = 0;

                    let req_attr = response.data.req_attr;
                    let feed_attr = response.data.feed_attr;
                    let feed_config = response.data.feed_config;
                    let labels = response.data.labels;

                    for (let i = 0; i < req_attr.length; i++) {
                        if (!feed_attr.includes(req_attr[i])) {
                            if ((req_attr[i] === 'gtin' && !feed_attr.includes('mpn')) || (req_attr[i] === 'mpn' && !feed_attr.includes('gtin'))) {
                                missing_attr[attr_inx++] = labels[req_attr[i]];
                            }
                            else if ( req_attr[i] !== 'gtin' && req_attr[i] !== 'mpn' ) {
                                missing_attr[attr_inx++] = labels[req_attr[i]];
                            }
                        } else {
                            for (var j = 0; j < feed_config.length; j++) {
                                if (feed_config[j]['attr'] === req_attr[i]) {
                                    if (feed_config[j]['type'] === 'meta' && feed_config[j]['meta_key'] === '') {
                                        missing_attr[attr_inx++] = labels[req_attr[i]];
                                    } else if (feed_config[j]['type'] === 'static' && feed_config[j]['st_value'] === '') {
                                        missing_attr[attr_inx++] = labels[req_attr[i]];
                                    } else if (feed_config[j]['type'] === '') {
                                        missing_attr[attr_inx++] = labels[req_attr[i]];
                                    }
                                }
                            }
                        }
                    }

                    if ( missing_attr.length > 0 ) {
                        let html = '';
                        missing_attr.forEach( ( attribute ) => {
                            html += '<li class="rex-google-shopping__list">';
                            html += '<p>' + attribute + '</p>';
                            html += '<a href="https://rextheme.com/docs/google-shopping-product-feed-specification-attributes-list/" target="_blank">Learn more</a>';
                            html += '</li>';
                        })
                        if ( '' !== html ) {
                            $( 'ul.rex-google-shopping__lists-area li' ).remove();
                            $( 'ul.rex-google-shopping__lists-area' ).append( html );

                            $( 'section#rex_feed_google_req_attr_warning_popup' ).show();
                            status = true;
                        }
                    }
                },
                error: function (response) {
                    alert('Error occured');
                }
            });
        }
        return status;
    }

    function default_category_mapping( e ) {
        var default_name = 'Google Product Category [Default]';
        var default_value = "category-75=&category-134=&category-39=&category-142=&category-83=&category-82=&category-88=&category-118=&category-107=&category-222=&category-161=&category-56=&category-76=&category-51=&category-90=&category-183=&category-89=&category-207=&category-121=&category-52=&category-77=&category-108=&category-119=&category-63=&category-210=&category-64=&category-124=&category-32=&category-30=&category-91=&category-184=&category-78=&category-197=&category-38=&category-120=&category-55=&category-95=&category-117=&category-65=&category-139=&category-81=&category-153=&category-96=&category-86=&category-31=&category-116=&category-60=&category-62=&category-50=&category-181=&category-33=&category-57=&category-15=&category-61=&category-87=&category-138=&category-37=&category-16=&category-19=&category-18=&category-17=&category-21=&category-20=";
        var $payload = {
            map_name: default_name,
            cat_map: default_value,
            hash: 'wpfm_google_product_category_default'
        };

        wpAjaxHelperRequest( 'category-mapping', $payload )
            .success( function( response ) {
                if ( response === 'reload' ) {
                    location.reload()
                }
                console.log( 'Woohoo!' );
            })
            .error( function( response ) {
                console.log( 'Uh, oh!' );
                
            });
    }

    function rex_feed_focus_merchant_search_bar( e ) {
        let aria_controls = $('input.select2-search__field').attr( 'aria-controls' );
        if ( 'select2-rex_feed_merchant-results' === aria_controls ) {
            $( 'input.select2-search__field' ).get( 0 ).focus()
        }
        else {
            // reg expression that starts with `select2-fc[any 3 digits numbers]meta_key-`
            const regex = new RegExp('^select2-fc[0-9]|[0-9][0-9]|[0-9][0-9][0-9]meta_key-');
            if ( regex.test(aria_controls) ) {
                $( 'input.select2-search__field' ).get( 0 ).focus()
            }
        }
    }

    /**
     * rollback feature for WPF
     */
    function rex_feed_process_rollback_button() {
        var $this = $( 'select#wpfm_rollback_options' ),
            $rollbackButton = $this.next('.rex-feed-rollback-button'),
            placeholderText = $rollbackButton.data('placeholder-text'),
            placeholderUrl = $rollbackButton.data('placeholder-url');

        $rollbackButton.html(placeholderText.replace('{VERSION}', $this.val()));
        $rollbackButton.attr('href', placeholderUrl.replace('VERSION', $this.val()));
    }

    function rex_feed_rollback_confirmation(event) {
        event.preventDefault();
        var $this = $(this);
        if ( confirm("You might loose your previous data. Are you really sure that you want to rollback to previous version?") ) {
            $this.addClass('show-loader');
            $this.addClass('loading');
            location.href = $this.attr('href');
        }
    }

    function rex_feed_custom_filter( event ) {
        var feed_id = rex_feed_get_feed_id();
        var button_text = $( '#rex_feed_custom_filter_button' ).text();
        var payload = {};
        var button_event = 'on_load';

        if ( 'click' === event.type ) {
            button_event = 'click';
            if ( 'Add Custom Filter' === button_text ) {
                button_text = 'added';
            }
            else {
                button_text = 'removed';
            }
        }

        payload = {
            feed_id: feed_id,
            button_text: button_text,
            button_event: button_event
        }

        wpAjaxHelperRequest( 'rex-feed-custom-filters', payload )
            .success( function ( response ) {
                if ( 'added' === response.data.button_text ) {
                    $( '#rex_feed_custom_filter_button' ).text( 'Remove Custom Filter' );
                    $( '#rex-feed-config-filter' ).show();
                    $( 'div#rex-feed-published-product' ).hide();
                    $( 'div#rex-feed-featured-product' ).hide();
                }
                else {
                    $( '#rex_feed_custom_filter_button' ).text( 'Add Custom Filter' );
                    $( '#rex-feed-config-filter' ).hide();
                    let pr_filter = $( 'select#rex_feed_products' ).find( 'option:selected' ).val();
                    if ( 'all' === pr_filter ) {
                        $( 'div#rex-feed-featured-product' ).hide();
                        $( 'div#rex-feed-published-product' ).show();
                    }
                    else if ( 'featured' === pr_filter ) {
                        $( 'div#rex-feed-published-product' ).hide();
                        $( 'div#rex-feed-featured-product' ).show();
                    }
                }
                $( 'input[name=rex_feed_custom_filter_option_btn]' ).val( response.data.button_text )
            } )
            .error( function ( response ) {
                console.log( 'Uh, oh! Not Awesome!!' );
                console.log( 'response.statusText' );
            } );
    }


    /**
     * Gets feed id from URL parameter
     * @returns {number|*}
     */
    function rex_feed_get_feed_id() {
        var feed_id = 0;
        var url = window.location.href;

        if ( url.includes( 'post-new.php?post_type=product-feed' ) ) {
            return feed_id;
        }

        url = url.split( '?' );
        url = url[1].split( '&' );

        for (const key in url) {
            if( url[key].search( 'post' ) > -1 ) {
                feed_id = url[key].split( '=' );
                return feed_id[1];
            }
        }
        return feed_id;
    }

    function rex_feed_copy_system_status( event ) {
        event.preventDefault();

        let button = $('#rex-feed-system-status-copy-btn');
        let status_area = $( '#rex-feed-system-status-area' );

        button.text('Copied!');
        status_area.css('visibility','visible');
        status_area.select();
        document.execCommand('copy');

        setTimeout( function () {
            button.text('Copy Status');
            status_area.css('visibility','hidden');
        }, 2000 );
    }

    /**
     * @desc Removes separator dropdown group from regular
     * attribute options other than combined fields
     *
     * @since 7.2.8
     */
    function rex_feed_hide_separators_group( event ) {
        $( 'select.attr-val-dropdown' ).find( 'optgroup[label="Attributes Separator"]' ).remove();
    }

    /**
     * @desc Disable tour guide popup on on-boarding page
     * after clicking 'No, Thanks' button or Cross [X] button
     * @since 7.2.10
     */
    function rex_feed_disable_tour_guide_popup() {
        let url = window.location.href;

        if ( url.includes('plugin_activated') ) {
            window.history.pushState({}, '', url.replace( '&plugin_activated=1', '' ));
            location.reload();
        }
    }

    /**
     * @desc Hide all admin notices from WPFM pages [except our own notices]
     * @since 7.2.10
     */
    function rex_feed_hide_all_admin_notices() {
        $.each( $( '.notice' ), function( index, value ) {
            if ( false === $(this).hasClass( 'rex-feed-notice' ) && 'Product Feed updated.' !== $(this).find( 'p' ).text() ) {
                $(this).hide();
            }
        });
        $.each( $( '.updated' ), function( index, value ) {
            if ( false === $(this).hasClass( 'rex-feed-notice' ) && 'Product Feed updated.' !== $(this).find( 'p' ).text() ) {
                $(this).hide();
            }
        });
    }

    /**
     * @desc Increase/decrease the multiple output filter counter
     * on filter option change
     * @since 7.2.12
     */
    function rex_feed_update_multiple_filter_counter() {
        let $this = $( this );
        let selected = $this.find( 'option:selected' ).length;

        if ( 1 < selected ) {
            let is_def_selected = $this.children('option[value=default]').attr( 'selected' );
            if ( 'selected' === is_def_selected ) {
                $this.children('option[value=default]').removeAttr( 'selected' );
                $this.trigger( 'change.select2' );
                selected = selected - 1;
            }
            if ( 1 < selected ) {
                selected = selected - 1;
                $this.siblings('span.rex-product-picker-count').show();
                $this.siblings('span.rex-product-picker-count').html('+' + selected + '..');
            }
        }
        else {
            $this.siblings( 'span.rex-product-picker-count' ).hide();
        }
        if (0 == selected) {
            $this.find('option:eq(1)').prop( 'selected', true );
            $this.trigger( 'change.select2' );
        }
        else {
            $this.find('option:eq(1)').prop( 'selected', false );
            $this.trigger( 'change.select2' );
        }
    }

    /**
     * @desc Increase/decrease the multiple output filter counter
     * on document ready
     * @since 7.2.12
     */
    function rex_feed_render_multiple_filter_counter() {
        let output_filter = $( 'div#rex_feed_config_heading' ).children( 'div.inside' ).children( 'table#config-table' ).children( 'tbody' ).children( 'tr' );
        let $select_field = '';
        let selected = 0;

        output_filter.each( function ( index, _element ) {
            if ( index ) {
                let row_id = $( this ).attr( 'data-row-id' );

                $select_field = $( 'select[name="fc['+ row_id +'][escape][]"]' );
                selected = $select_field.find( 'option:selected' ).length;

                if ( 1 < selected ) {
                    selected = selected - 1;
                    $select_field.siblings( 'span.rex-product-picker-count' ).show();
                    $select_field.siblings( 'span.rex-product-picker-count' ).html( '+' + selected + '..' );
                }
            }
        } );
    }


    /**
     * @desc Helper function to save option
     * to hide/view character limit field
     */
    function rex_feed_save_character_limit_option() {
        let opt_val = $( this ).is( ':checked' );
        opt_val = opt_val ? 'on' : 'off';

        wpAjaxHelperRequest( 'rex-feed-save-char-limit-option', opt_val )
            .success( function ( response ) {
                console.log( 'Woohoo! Awesome!!' );
            } )
            .error( function ( response ) {
                console.log( 'Uh, oh! Not Awesome!!' );
            } );
    }


    /**
     * @desc Check/Uncheck All Categories/Tags
     * option in product filters section
     * @since 7.2.18
     */
    function rex_feed_check_uncheck_all_tax() {
        let button_id = $( this ).attr( 'id' );
        if ( 'rex_feed_cats_check_all_btn' === button_id ) {
            rex_feed_check_uncheck_all_cats( button_id );
        }
        else if ( 'rex_feed_tags_check_all_btn' === button_id ) {
            rex_feed_check_uncheck_all_tags( button_id );
        }
    }

    /**
     * @desc Check/Uncheck All Categories
     * option in product filters section
     * @since 7.2.18
     */
    function rex_feed_check_uncheck_all_cats( button_id ) {
        if ( 1 <= $( 'input#' + button_id + ':checked' ).length ) {
            $( 'input.rex_feed_cats' ).prop( 'checked', true )
        }
        else {
            $( 'input.rex_feed_cats' ).prop( 'checked', false )
        }
    }

    /**
     * @desc Check/Uncheck All Tags
     * option in product filters section
     * @since 7.2.18
     */
    function rex_feed_check_uncheck_all_tags( button_id ) {
        if ( 1 <= $( 'input#' + button_id + ':checked' ).length ) {
            $( 'input.rex_feed_tags' ).prop( 'checked', true )
        }
        else {
            $( 'input.rex_feed_tags' ).prop( 'checked', false )
        }
    }

    /**
     * @desc Deletes publish button id on document ready
     * @since 7.2.18
     */
    function rex_feed_delete_publish_btn_id() {
        let url = new URL( window.location.href );
        let feed_id = url.searchParams.get( 'post' );

        wpAjaxHelperRequest( 'rex-feed-delete-publish-btn-id', feed_id )
            .success( function ( response ) {
                console.log( 'Woohoo! Button id deleted!' );
            } )
            .error( function ( response ) {
                console.log( 'Uh, oh! Not Awesome!!' );
                console.log( 'response.statusText' );
            } );
    }

    /**
     * @desc hide character limit column
     * after mapping table load
     * @since 7.2.18
     */
    function rex_feed_hide_char_limit_col() {
        wpAjaxHelperRequest( 'rex-feed-hide-char-limit-col' )
            .success( function ( response ) {
                if ( 'on' === response.hide_char ) {
                    $( 'th#rex_feed_output_limit_head' ).hide();
                    $( 'td[data-title="Output Limit : "]' ).hide();
                }
            } )
            .error( function ( response ) {
                console.log( 'Uh, oh! Not Awesome!!' );
            } );
    }


    /**
     * @desc Update abandone child list ajax
     */
    function rex_feed_update_abandoned_child_list() {
        let $el = $( this );
        $el.find( "span" ).hide();
        $el.find( "i" ).show();

        wpAjaxHelperRequest( 'rex-feed-update-abandoned-child-list' )
            .success( function ( response ) {
                $el.find( "i" ).hide();
                $el.find( "span" ).show();
                console.log( 'woohoo!' );
            } )
            .error( function ( response ) {
                $el.find( "i" ).hide();
                $el.find( "span" ).show();
                console.log( 'uh, oh!' );
            } );
    }


    /**
     * @desc Auto select attribute/attribute value
     * if user select shipping/tax attributes for google format
     * @since 7.3.0
     */
    function rex_feed_auto_select_google_shipping_tax() {
        let $this = $( this );
        let selected_val = $this.val();

        if ( $this.hasClass( 'attr-dropdown' ) ) {
            if ( 'shipping' === selected_val || 'tax' === selected_val ) {
                $this.parent().siblings(':first').children().val('meta');
                $this.parent().siblings(':nth-child(3)').children().hide();
                $this.parent().siblings(':nth-child(3)').children(':first').show();
                $this.parent().siblings(':nth-child(3)').children(':first').children('select.attr-val-dropdown').val(selected_val).trigger('change');
            }
        }
        else {
            if ( 'shipping' === selected_val || 'tax' === selected_val ) {
                $this.parent().parent().siblings( ':first' ).children( 'select.attr-dropdown' ).val( selected_val )
            }
        }
    }

})( jQuery );
