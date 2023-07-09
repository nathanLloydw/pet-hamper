
(function( $ ) {
    'use strict';

    /**
     * All the code for your admin-facing javascript source
     * should reside in this file.
     *
     * note: it has been assumed you will write jquery code here, so the
     * $ function reference has been prepared for usage within the scope
     * of this function.
     *
     * this enables you to define handlers, for when the dom is ready:
     *
     * $(function() {
	 *
	 * });
     *
     * when the window is loaded:
     *
     * $( window ).load(function() {
	 *
	 * });
     *
     * ...and/or other possibilities.
     *
     * ideally, it is not considered best practise to attach more than a
     * single dom-ready or window-load handler for a particular page.
     * although scripts in the wordpress core, plugins and themes may be
     * practising this, we should strive to set a better example in our own work.
     */



    /*
     ** database update
     */

    function wpfm_update_database(event) {
        event.preventDefault();
        var payload = {};
        var $this = $(this);

        $('.wpfm-db-update-loader').fadeIn();


        $.ajax({
            type : "post",
            dataType : "json",
            url : rex_wpfm_ajax.ajax_url,
            data: {
                action   : 'rex_wpfm_database_update',
                security : rex_wpfm_ajax.ajax_nonce,
            },
            success: function(response) {
                console.log('woohoo!');
                setTimeout(function(){
                    location.reload();
                }, 1000);
            },
            error: function(){
                console.log( 'uh, oh!' );
            }
        });
    }
    $(document).on('click', '#rex-wpfm-update-db', wpfm_update_database);

    function wpfm_bf_notice_dismiss(event) {
        event.preventDefault();
        var $payload = {};
        var link = $(this).attr('href');
        var cls = $(this).attr('class');
        $("#wpfm-black-friday-notice").hide();
        wpAjaxHelperRequest( 'black-friday-notice-dismiss', $payload )
            .success( function( response ) {
                console.log( 'Woohoo!' );
                console.log( response );
            })
            .error( function( response ) {});

    }
    $(document).on('click', '#wpfm-black-friday-notice .notice-dismiss', wpfm_bf_notice_dismiss);


    /**
     * black friday notice
     * removal
     * @param event
     * @since 6.1.0
     */
    function rextheme_bf_notice_dismiss(event) {
        event.preventDefault();
        var that = $(this);
        var payload = {};
        wpAjaxHelperRequest('bf-notice-dismiss', payload)
            .success(function (response) {
                if(response.success) {
                    that.fadeOut('slow');
                    console.log(response)
                }
                else {
                }
            })
            .error(function (response) {
                console.log('Uh, oh!');
                console.log(response.statusText);
            });
    }

    $(document).on('click', '.rextheme-black-friday-offer .notice-dismiss', rextheme_bf_notice_dismiss);

    $(document).on('click', '.best-woocommerce-feed-deactivate-link', function ( e ) {
        $( '.wd-dr-modal-footer a.dont-bother-me' ).hide();

        var $payload = {
            security: rex_wpfm_ajax.ajax_nonce
        };

        wpAjaxHelperRequest( 'rex-feed-get-appsero-options', $payload )
            .success( function( response ) {
                if (response.success) {
                    $( 'ul.wd-de-reasons' ).empty();
                    $( 'ul.wd-de-reasons' ).append( response.data.html );
                }
            })
            .error( function( response ) {
            });

        if ( !$( '#appsero_new_assistance' ).length && !$( '#appsero_required' ).length ) {
            $( '.wd-dr-modal-body' ).append( '<p id="appsero_new_assistance">Need Support/Assistance? <a href="https://rextheme.com/support/?utm_source=plugin&utm_medium=support_link&utm_campaign=pfm_plugin" target="_blank">Click Here!</a></p>' );
            $( '.wd-dr-modal-body' ).append( '<p id="appsero_required"><span style="color: red">*</span>Please, select one reason and submit.</p>' );
        }
    });


    $( document ).on( 'click', '.best-woocommerce-feed-insights-data-we-collect', function () {
        let desc = $( this ).parents( '.updated' ).find( 'p.description' ).html();
        desc = desc.split( '. ' );
        if ( -1 === desc[ 0 ].indexOf( ', Feed merchant lists, Feed title lists' ) ) {
            desc[0] = desc[0] + ', Feed merchant lists, Feed title lists';
            $(this).parents('.updated').find('p.description').html(desc.join('. '));
        }
    } );

    // Ajax function to update single feed.
    $( document ).on( 'click', '.rex-feed-update-single-feed', function ( e ) {
        e.preventDefault();
        let $this = $( this );
        let feed_id = $this.data( 'feed-id' );

        wpAjaxHelperRequest('rex-feed-update-single-feed', feed_id)
            .success(function (response) {
                $( 'tr#post-' + feed_id + ' td.feed_status' ).text( 'In queue' );
                $this.attr( 'disabled', 'true' );
                $this.css( 'pointer-events', 'none' );
                $this.siblings().attr( 'disabled', true );
                $this.parent().siblings( 'td.view_feed' ).children().attr( 'disabled', true );
                $this.parent().siblings( 'td.view_feed' ).children().css( 'pointer-events', 'none' );
                console.log('Success');
            })
            .error(function (response) {
                console.log('Failed');
            });
    } )

    $( document ).ready( function ( e ) {
        if ( window.location.href.includes('edit.php') ) {
            $( '#rex_feed_new_changes_msg_content' ).hide();
        }
        $( '#rex-feed-support-submenu, #rex-feed-gopro-submenu' ).parent().attr( 'target', '_blank' );

        if ( $( 'section#rex_deal_notification' ).length ) {
            rexfeed_deal_countdown_handler();
        }
    } );

    /**
     * Handles count down on deal notice
     *
     * @since 7.3.2
     */
    function rexfeed_deal_countdown_handler() {
        const second = 1000,
            minute = second * 60,
            hour = minute * 60,
            day = hour * 24;

        let now = new Date( rex_wpfm_ajax.current_date ).getTime();
        const countDown = new Date( '06/26/2023' ).getTime(),
            x = setInterval(function() {
                const distance = countDown - now;
                now = now + 1000;

                let formattedDay = Math.floor(distance / (day)),
                    formattedHours = Math.floor((distance % (day)) / (hour)),
                    formattedMin =  Math.floor((distance % (hour)) / (minute));

                $("#rex-feed-tb__days").text( formattedDay.toString().length > 1 ? formattedDay : '0' + formattedDay.toString() );
                $("#rex-feed-tb__hours").text( formattedHours.toString().length > 1 ? formattedHours : '0' + formattedHours.toString() );
                $("#rex-feed-tb__mins").text( formattedMin.toString().length > 1 ? formattedMin : '0' + formattedMin.toString() );

                // do something later when date is reached
                if ( 0 >= distance ) {
                    $("#rex_deal_notification").hide();
                    rexfeed_hide_deal_notice();
                    clearInterval(x);
                }
                //seconds
            }, 1000);
    }

    $( document ).on( 'click', '#rex_deal_close', rexfeed_hide_deal_notice );

    /**
     * Hide deal notice and save parameter to keep it hidden for future
     *
     * @since 7.3.2
     */
    function rexfeed_hide_deal_notice() {
        $( '#rex_deal_notification' ).fadeOut();

        wpAjaxHelperRequest( 'rex-feed-hide-deal-notice' );
    }

})( jQuery );



