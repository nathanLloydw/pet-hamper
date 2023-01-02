/* global WC_PRL */

( function ( $, window ) {
	// Namespace.
	WC_PRL = window.WC_PRL || {};

	// Caches.
	WC_PRL.$window = $( window );
	WC_PRL.params = window.wc_prl_params;

	// Modules.
	/* global WC_PRL */

	WC_PRL.cookies = WC_PRL.cookies || {};

	WC_PRL.cookies.model = ( function () {
		var session_timeout = WC_PRL.params.shopping_session_seconds * 1000; // Transform to ms.

		function set( cname, cvalue, timeout ) {
			var d, expires;

			if ( typeof timeout === 'undefined' ) {
				timeout = session_timeout;
			}

			// Calc expiration.
			d = new Date();
			d.setTime( d.getTime() + timeout );
			expires = 'expires=' + d.toUTCString();

			// Set cookie.
			document.cookie = cname + '=' + cvalue + '; ' + expires + '; path=/';
		}

		function remove( cname ) {
			document.cookie =
				cname + '=; expires=Thu, Jan 01 1970 00:00:00 UTC;path=/';
		}

		function get( cname ) {
			var name = cname + '=',
				decodedCookie = decodeURIComponent( document.cookie ),
				ca = decodedCookie.split( ';' ),
				i;

			for ( i = 0; i < ca.length; i++ ) {
				var c = ca[ i ];

				while ( ' ' === c.charAt( 0 ) ) {
					c = c.substring( 1 );
				}

				if ( 0 === c.indexOf( name ) ) {
					return c.substring( name.length, c.length );
				}
			}

			return '';
		}

		function refresh( cname ) {
			var value = get( cname );
			set( cname, value );
		}

		return {
			set: set,
			get: get,
			remove: remove,
			refresh: refresh,
		};
	} )();

	WC_PRL.cookies.clicks = ( function ( model ) {
		// Cached instance.
		var instance,
			limit = WC_PRL.params.clicks_max_cookie_num;

		/**
		 * Deployment Clicks singleton.
		 *
		 * Holds a static instance that returns the same object.
		 */
		function WC_PRL_Deployment_Clicks_Cookie() {
			// Make sure is called as a constructor.
			if ( ! ( this instanceof WC_PRL_Deployment_Clicks_Cookie ) ) {
				return new WC_PRL_Deployment_Clicks_Cookie();
			}

			if ( instance ) {
				return instance;
			}

			// The instance.
			instance = this;

			// Constants.
			this.COOKIE_NAME = 'wc_prl_deployments_clicked';

			// The properties.
			this.cookie_raw = '';
			this.clicks = [];
		}

		WC_PRL_Deployment_Clicks_Cookie.prototype.init = function () {
			// Copy value to memory.
			this.cookie_raw = model.get( this.COOKIE_NAME );

			// Refresh the shopping session. TODO: PERFORMANCE?
			if ( '' !== this.cookie_raw ) {
				this.clicks = this.cookie_raw.split( ',' );
				model.set( this.COOKIE_NAME, this.clicks.join( ',' ) );
			}
		};

		WC_PRL_Deployment_Clicks_Cookie.prototype.is_clicked = function ( value ) {
			return -1 !== this.clicks.indexOf( value );
		};

		WC_PRL_Deployment_Clicks_Cookie.prototype.add = function ( value ) {
			if ( this.is_clicked( value ) ) {
				return;
			}

			if ( this.clicks.length > limit - 1 ) {
				this.clicks.splice( 0, 1 );
			}

			// If it's new, add to memory and update cookie.
			this.clicks.push( value );
			model.set( this.COOKIE_NAME, this.clicks.join( ',' ) );
		};

		return WC_PRL_Deployment_Clicks_Cookie;
	} )( WC_PRL.cookies.model );

	WC_PRL.cookies.recently_viewed = ( function ( model ) {
		// Cached instance.
		var instance,
			limit = WC_PRL.params.recently_views_max_cookie_num;

		/**
		 * Recently Viewed Cookie singleton.
		 *
		 * Holds a static instance that returns the same object.
		 */
		function WC_PRL_Recently_Viewed_Cookie() {
			// Make sure is called as a constructor.
			if ( ! ( this instanceof WC_PRL_Recently_Viewed_Cookie ) ) {
				return new WC_PRL_Recently_Viewed_Cookie();
			}

			if ( instance ) {
				return instance;
			}

			// The instance.
			instance = this;

			// Constants.
			this.COOKIE_NAME = 'wc_prl_recently_viewed';

			// The properties.
			this.cookie_raw = '';
			this.viewed_ids = [];
			this.viewed_cat_ids = [];
			this.viewed_tag_ids = [];
		}

		WC_PRL_Recently_Viewed_Cookie.prototype.init = function () {
			// Copy value to memory.
			this.cookie_raw = model.get( this.COOKIE_NAME );

			if ( '' !== this.cookie_raw ) {
				// De-construct cookie.
				// product_ids|...,cat_ids|...,tag_ids|...
				var parts = this.cookie_raw.split( ',' );

				if ( parts.length ) {
					this.viewed_ids = $.map(
						parts[ 0 ].split( '|' ),
						function ( value ) {
							return parseInt( value, 10 );
						}
					);

					// If categories.
					if ( parts.length > 1 ) {
						this.viewed_cat_ids = $.map(
							parts[ 1 ].split( '|' ),
							function ( value ) {
								return parseInt( value, 10 );
							}
						);
					}

					// If tags.
					if ( parts.length > 2 ) {
						this.viewed_tag_ids = $.map(
							parts[ 2 ].split( '|' ),
							function ( value ) {
								return parseInt( value, 10 );
							}
						);
					}
				}

				// Refresh the shopping session.
				this.save();
			}
		};

		WC_PRL_Recently_Viewed_Cookie.prototype.add_product_id = function ( id ) {
			if ( this.viewed_ids.length > limit ) {
				this.viewed_ids.shift();
			}

			id = parseInt( id, 10 );

			// Search index.
			var index = this.viewed_ids.indexOf( id );

			if ( index > -1 ) {
				// Remove item.
				this.viewed_ids.splice( index, 1 );
			}

			this.viewed_ids.push( id );
		};

		WC_PRL_Recently_Viewed_Cookie.prototype.add_category_id = function ( id ) {
			if ( this.viewed_cat_ids.length > limit ) {
				this.viewed_cat_ids.shift();
			}

			id = parseInt( id, 10 );

			// Search index.
			var index = this.viewed_cat_ids.indexOf( id );

			if ( index > -1 ) {
				// Remove item.
				this.viewed_cat_ids.splice( index, 1 );
			}

			this.viewed_cat_ids.push( id );
		};

		WC_PRL_Recently_Viewed_Cookie.prototype.add_tag_id = function ( id ) {
			if ( this.viewed_tag_ids.length > limit ) {
				this.viewed_tag_ids.shift();
			}

			id = parseInt( id, 10 );

			// Search index.
			var index = this.viewed_tag_ids.indexOf( id );

			if ( index > -1 ) {
				// Remove item.
				this.viewed_tag_ids.splice( index, 1 );
			}

			this.viewed_tag_ids.push( id );
		};

		WC_PRL_Recently_Viewed_Cookie.prototype.save = function () {
			var viewed_ids = this.viewed_ids.join( '|' ),
				cat_ids = this.viewed_cat_ids.join( '|' ),
				tag_ids = this.viewed_tag_ids.join( '|' );

			this.cookie_raw = viewed_ids;
			if ( cat_ids ) {
				this.cookie_raw += ',' + cat_ids;
			}
			if ( tag_ids ) {
				this.cookie_raw += ',' + tag_ids;
			}

			model.set( this.COOKIE_NAME, this.cookie_raw );
		};

		return WC_PRL_Recently_Viewed_Cookie;
	} )( WC_PRL.cookies.model );

	/* global WC_PRL */

	WC_PRL.Deployment = ( function () {
		// Localize scope.
		var params = WC_PRL.params;

		/**
		 * Deployment View Controller.
		 *
		 * @param jQuery $deployment
		 */
		function Deployment( $deployment ) {
			// Make sure is called as a constructor.
			if ( ! ( this instanceof Deployment ) ) {
				return new Deployment( $deployment );
			}

			if ( ! $deployment.length ) {
				return false;
			}

			// Base Properties.
			this.id = null;
			this.engine_id = null;
			this.location_hash = null;
			this.source_hash = null;

			// Holds the jQuery instance.
			this.$deployment = $deployment;
		}

		/*----------------------------------------------------------------*/
		/*  Controller methods.                                            */
		/*-----------------------------------------------------------------*/

		/**
		 * Getters.
		 */
		Deployment.prototype.get_id = function () {
			if ( ! this.id ) {
				var dom_id = this.$deployment.attr( 'id' ),
					reg_id = /([0-9]+)$/g,
					id = parseInt( dom_id.match( reg_id ), 10 );
				this.id = id;
			}

			return this.id;
		};

		Deployment.prototype.get_engine_id = function () {
			if ( ! this.engine_id ) {
				var engine_id = parseInt( this.$deployment.data( 'engine' ), 10 );
				this.engine_id = engine_id;
			}

			return this.engine_id;
		};

		Deployment.prototype.get_location_hash = function () {
			if ( ! this.location_hash ) {
				var location_hash = this.$deployment.data( 'location-hash' );
				// Cache it.
				this.location_hash = location_hash;
			}

			return this.location_hash;
		};

		Deployment.prototype.get_source_hash = function () {
			if ( ! this.source_hash ) {
				var source_hash = this.$deployment.data( 'source-hash' );
				this.source_hash = source_hash;
			}

			return this.source_hash;
		};

		/**
		 * Setup click event.
		 */
		Deployment.prototype.setup_events = function () {
			var self = this;
			var cookie = WC_PRL.cookies.clicks();

			this.$deployment.on( 'click', 'a', function () {
				var $link = $( this );
				if ( ! $link.attr( 'href' ) ) {
					return;
				}

				var $class = $link.parent().attr( 'class' ),
					match = $class.match( /post-([0-9-]+)\s?/g ),
					product_id =
						match instanceof Array
							? parseInt(
									match.pop().replace( 'post-', '' ).trim(),
									10
							  )
							: 0;

				if ( ! product_id ) {
					return;
				}

				var cookie_parts = [ self.get_id(), product_id ];

				if ( self.get_source_hash() ) {
					cookie_parts.push( self.get_source_hash() );
				}

				var cookie_value = cookie_parts.join( '_' );
				cookie.add( cookie_value );
			} );
		};

		return Deployment;
	} )();

	/* global WC_PRL */

	WC_PRL.tracking = ( function () {
		var rv_cookie = WC_PRL.cookies.recently_viewed();

		/**
		 * Function to attach event handlers to all deployments on the page.
		 *
		 * @param {Array} $deployments An array of DOM elements to attach the events (Optional)
		 * @return {void}
		 */
		function add_deployment_events( $deployments = undefined ) {
			if ( 'no' === WC_PRL.params.tracking_enabled ) {
				return;
			}

			$deployments =
				$deployments && $deployments.length
					? $deployments
					: $( '.wc-prl-recommendations:not(.placeholder)' );
			if ( $deployments.length ) {
				// Localized lookups.
				var is_ajax_add_to_cart = 'yes' !== WC_PRL.params.ajax_add_to_cart;
				var is_checkout_page = $( document.body ).hasClass(
					'woocommerce-checkout'
				);

				$deployments.each( function () {
					var $deployment = $( this ),
						deployment = new WC_PRL.Deployment( $deployment );

					// Write click in cookie.
					deployment.setup_events();

					// Handle legacy checkout deployments.
					if ( is_ajax_add_to_cart && is_checkout_page ) {
						// For each deployment add-to-cart in Checkout update the table.
						$deployment.on(
							'click',
							'a.add_to_cart_button',
							function () {
								$( document.body ).one(
									'added_to_cart',
									function () {
										$( document.body ).trigger(
											'update_checkout'
										);
									}
								);
							}
						);
					}
				} );
			}
		}

		/**
		 * Function to track the single product view in the customer's session.
		 *
		 * @param {Array} $deployments An array of DOM elements to attach the events (Optional)
		 * @return {void}
		 */
		function maybe_track_product_view() {
			// Check if is product single.
			var $product_container = $( 'body.single-product' );
			if ( ! $product_container.length ) {
				return;
			}

			var product_id = 0;

			// Try to fetch product ID from cart form.
			var $cart_form = $( 'form.cart' ).first();
			if ( $cart_form.length ) {
				var $cart_btn = $cart_form.find( '[name="add-to-cart"]' );

				product_id = parseInt( $cart_btn.val(), 10 );
				if ( ! product_id ) {
					window.console.warn(
						'Could not parse the product id using cart form. Tracking bypassed...'
					);
					return;
				}
			} else {
				// Try to fetch from container.
				var container_class = $product_container.attr( 'class' );
				var product_id_regex = /postid-([0-9-]+)\s?/g;
				var product_id_match = container_class.match( product_id_regex );
				if ( product_id_match && product_id_match instanceof Array ) {
					product_id = parseInt(
						product_id_match.pop().replace( 'postid-', '' ).trim(),
						10
					);
				}
			}

			// Parse classes for recent terms.
			var $product_wrap = $( '.product.type-product' );
			if ( $product_wrap.length ) {
				rv_cookie.init();

				// Get container class.
				var product_class = $product_wrap.attr( 'class' );

				// Log categories.
				var cat_regex = /wc-prl-cat-([0-9-]+)\s?/g;
				var cat_ids_match = product_class.match( cat_regex );
				if ( cat_ids_match && cat_ids_match instanceof Array ) {
					var cat_ids = cat_ids_match
						.pop()
						.replace( 'wc-prl-cat-', '' )
						.trim()
						.split( '-' );
					for ( i in cat_ids ) {
						rv_cookie.add_category_id( cat_ids[ i ] );
					}
				}

				// Log tags.
				var tag_regex = /wc-prl-tag-([0-9-]+)\s?/g;
				var tag_ids_match = product_class.match( tag_regex );
				if ( tag_ids_match && tag_ids_match instanceof Array ) {
					var tag_ids = tag_ids_match
						.pop()
						.replace( 'wc-prl-tag-', '' )
						.trim()
						.split( '-' );
					for ( i in tag_ids ) {
						rv_cookie.add_tag_id( tag_ids[ i ] );
					}
				}

				rv_cookie.add_product_id( product_id );
				rv_cookie.save();
			}
		}

		// WC_PRL.tracking.add_deployment_events()
		// WC_PRL.tracking.maybe_track_product_view()
		return {
			add_deployment_events,
			maybe_track_product_view,
		};
	} )();

	/* global WC_PRL */

	WC_PRL.template = ( function () {
		/**
		 * Renders all deployment placeholders that echoed from PHP when using AJAX rendering.
		 *
		 * @param {Array} $placeholders An array of DOM elements to render the HTML chunks from AJAX (Optional)
		 * @return {void}
		 */
		function render_placeholders( $placeholders ) {
			$placeholders = $placeholders
				? $placeholders
				: $( '.wc-prl-ajax-placeholder' );

			if ( $placeholders.length ) {
				// Gather all hooks in the template.
				var locations = ( function () {
					var hooks = [];

					$placeholders.each( function () {
						var hook = $( this ).attr( 'id' );
						hooks.push( hook );
					} );

					return hooks;
				} )();

				// Grab environment/context data.
				var env = ( function () {
					var env;

					$placeholders.each( function () {
						var e;
						e = $( this ).attr( 'data-env' );

						// Try to parse JSON...
						try {
							env = JSON.parse( e );
						} catch ( e ) {
							env = false;
						}

						// Grap just the first one.
						if ( env ) {
							return false;
						}
					} );

					return env;
				} )();

				// Setup request data.
				var data = {
					locations: locations.join( ',' ),
					product: env.product ? env.product : '',
					archive: env.archive ? env.archive : '',
					order: env.order ? env.order : '',
					current_url: window.location.href,
				};

				WC_PRL.$window.trigger( 'wc_prl_deployments_before_render' );

				$.post(
					woocommerce_params.wc_ajax_url
						.toString()
						.replace(
							'%%endpoint%%',
							'woocommerce_prl_print_location'
						),
					data,
					function ( response ) {
						if ( 'failure' === response.result ) {
							window.console.error(
								'PRL Deployment Render Error: ',
								response
							);
						}

						var i;
						for ( i in response.html ) {
							if ( ! response.html[ i ] ) {
								continue;
							}

							// Replace with HTML chunks.
							$placeholders.each( function () {
								var $this = $( this );
								if ( i == $this.attr( 'id' ) ) {
									$this.replaceWith( response.html[ i ] );
									return false;
								}
							} );
						}

						WC_PRL.$window.trigger( 'wc_prl_deployments_after_render' );
						if ( 'yes' === WC_PRL.params.tracking_enabled ) {
							WC_PRL.tracking.add_deployment_events();
						}
					}
				);
			}
		}

		// WC_PRL.template.render_placeholders()
		return {
			render_placeholders: render_placeholders,
		};
	} )();


	if ( 'yes' === WC_PRL.params.tracking_enabled ) {
		WC_PRL.cookies.clicks().init();
	}

	// DOM ready.
	$( function () {
		// Keep track of browsing history.
		WC_PRL.tracking.maybe_track_product_view();

		// Render deployment placeholders if using AJAX rendering.
		var $placeholders = $( '.wc-prl-ajax-placeholder' );
		if ( $placeholders.length ) {
			WC_PRL.template.render_placeholders( $placeholders );
		} else if ( 'yes' === WC_PRL.params.tracking_enabled ) {
			WC_PRL.tracking.add_deployment_events();
		}
	} );
} )( jQuery, window );
