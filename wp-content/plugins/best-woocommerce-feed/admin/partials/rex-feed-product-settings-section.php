<?php
/**
 * This file is responsible for displaying feed settings section
 *
 * @link       https://rextheme.com
 * @since      1.0.0
 *
 * @package    Rex_Product_Feed
 * @subpackage Rex_Product_Feed/admin/partials
 */

$icon_question = 'icon/icon-svg/icon-question.php';
?>

<div class="rex-contnet-setting-area">

	<div class="rex-contnet-setting__header">
		<div class="rex-contnet-setting__header-text">
			<div class="rex-contnet-setting__icon rex-contnet__header-text">
				<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . 'icon/icon-svg/icon-setting.php'; ?>
				<?php echo '<h2>' . esc_html__( 'Settings', 'rex-product-feed' ) . '</h2>'; ?>
			</div>
		</div>

		<span class="rex-contnet-setting__close-icon close-btn" id="rex-contnet-setting__close-icon">
			<?php echo esc_html__( 'Close', 'rex-product-feed' ); ?>
		</span>
	</div>

	<div class="rex-contnet-setting-content-area">

		<div class="<?php echo esc_attr( $this->prefix ) . 'schedule'; ?>">
			<label for="<?php echo esc_attr( $this->prefix ) . 'schedule_label'; ?>"><?php esc_html_e( 'Auto-Generate Your Feed', 'rex-product-feed' ); ?>
				<span class="rex_feed-tooltip">
					<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
					<p><?php esc_html_e( 'Set auto-update to keep your feed in sync with WooCommerce ', 'rex-product-feed' ); ?><a href="<?php echo esc_url( 'https://rextheme.com/docs/wpfm-schedule-auto-update-of-feed-on-intervals/?utm_source=plugin&utm_medium=auto_update_link&utm_campaign=pfm_plugin' ); ?>" target="_blank"><?php esc_html_e( 'Learn How', 'rex-product-feed' ); ?></a></p>
				</span>
			</label>

			<ul id="<?php echo esc_html( $this->prefix ) . 'schedule'; ?>">
				<?php
				$index      = 1;
				$prev_value = get_post_meta( get_the_ID(), '_rex_feed_schedule', true );
				$prev_value = $prev_value ?: get_post_meta( get_the_ID(), 'rex_feed_schedule', true );
				$prev_value = $prev_value ?: 'no';
				foreach ( $schedules as $key => $value ) {
					$checked = $key === $prev_value ? ' checked="checked"' : '';
					echo '<li>';
					echo '<input type="radio" id="' . esc_attr( $this->prefix ) . 'schedule' . esc_attr( $index ) . '" name="' . esc_attr( $this->prefix ) . 'schedule' . '" value="' . esc_attr( $key ) . '" ' . esc_html( $checked ) . '>';
					echo '<label for="' . esc_attr( $this->prefix ) . 'schedule' . esc_attr( $index++ ) . '">' . esc_html( $value ) . '</label>';
					echo '</li>';
				}
				echo '<li class="' . esc_attr( $this->prefix ) . 'custom_time_fields">';

				$selected_hour = get_post_meta( get_the_ID(), '_rex_feed_custom_time', true );
				$selected_hour = $selected_hour ?: get_post_meta( get_the_ID(), 'rex_feed_custom_time', true );

				echo '<select id="' . esc_attr( $this->prefix ) . 'custom_time " name="' . esc_attr( $this->prefix ) . 'custom_time">';
				for ( $i =1; $i <=24; $i++ ) {
					if ( 12 === (int) $i ) {
						$twelve_hr_format = '12 PM';
					}
					elseif ( 24 === (int) $i ) {
						$twelve_hr_format = '12 AM';
					}
					elseif ( (int) $i > 12 ) {
						$hr               = (int) $i - 12;
						$twelve_hr_format = $hr . ' PM';
					}
					else {
						$twelve_hr_format = $i . ' AM';
					}
					$selected = (int) $selected_hour === $i ? ' selected' : '';
					echo '<option value="' . esc_attr( $i ) . '" ' . esc_html( $selected ) . '>' . esc_attr( $twelve_hr_format ) . '</option>';
				}
				echo '</select>';
				echo '<label for="' . esc_attr( $this->prefix ) . 'custom_time' . '">' . esc_html__( 'Every Day', 'rex-product-feed' ) . '</label>';
				echo '</li>';
				?>
			</ul>

			<?php do_action( 'rex_feed_after_autogenerate_options_field' ); ?>
		</div>

		<div class="<?php echo esc_attr( $this->prefix ) . 'country_list_area'; ?>">
			<div class="<?php echo esc_attr( $this->prefix ) . 'country_list_content'; ?> pl-10">
				<label for="<?php echo esc_attr( $this->prefix ) . 'feed_country_label'; ?>"><?php esc_html_e( 'Select Country', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p>
							<?php esc_html_e( 'Select a Country for the Shipping value. ', 'rex-product-feed' ); ?><a href="<?php echo esc_url( 'https://rextheme.com/docs/product-feed-manager-documentation/?utm_source=plugin&utm_medium=shipping_link&utm_campaign=pfm_plugin#tax-shipping' ); ?>" target="_blank"><?php esc_html_e( 'Learn How', 'rex-product-feed' ); ?></a>
						</p>
					</span>
				</label>

				<select name="<?php echo esc_attr( $this->prefix ) . 'feed_country'; ?>" id="<?php echo esc_attr( $this->prefix ) . 'feed_country'; ?>" class="">
					<?php
					$saved_country = get_post_meta( get_the_ID(), '_' . esc_attr( $this->prefix ) . 'feed_country', true );
					$saved_country = $saved_country ?: get_post_meta( get_the_ID(), esc_attr( $this->prefix ) . 'feed_country', true );

					if ( !$saved_country ) {
						$countries           = new WC_Countries();
						$base_country_code   = $countries->get_base_country();
						$base_continent_code = $countries->get_continent_code_for_country( $base_country_code );
						$saved_country       = ':' . $base_country_code . ':' . $base_continent_code;
					}

					$filtered_locations = wpfm_get_cached_data( 'filtered_country_list' );

					if ( !is_array( $filtered_locations ) || empty( $filtered_locations ) ) {
						$shipping_country             = WC()->countries->get_shipping_countries();
						$available_shipping_locations = rex_feed_get_wc_shipping_state_country();
						$countries                    = array();
						$continents                   = array();
						$filtered_locations           = array();

						if ( is_array( $available_shipping_locations ) && !empty( $available_shipping_locations ) ) {
							foreach ( $available_shipping_locations as $location ) {
								if ( isset( $location[ 'location_code' ] ) ) {
									$country_code   = '';
									$continent_code = '';
									$state_code     = '';
									$location_label = '';
									if ( isset( $location[ 'location_type' ] ) ) {
										if ( 'state' === $location[ 'location_type' ] ) {
											$location_code = explode( ':', $location[ 'location_code' ] );
											$country_code  = !empty( $location_code[ 0 ] ) ? $location_code[ 0 ] : '';
											$state_code    = !empty( $location_code[ 1 ] ) ? $location_code[ 1 ] : '';
										}
										elseif ( 'continent' === $location[ 'location_type' ] ) {
											$continent_code = $location[ 'location_code' ];
										}
										else {
											$country_code = $location[ 'location_code' ];
										}
									}

									$states        = WC()->countries->get_states( $country_code );
									$state_label   = !empty( $states[ $state_code ] ) ? $states[ $state_code ] : '';
									$country_label = !empty( $shipping_country[ $country_code ] ) ? $shipping_country[ $country_code ] : '';

									if ( $state_label && $country_label ) {
										$location_label = $state_label . ', ' . $country_label;
									}
									elseif ( $country_label ) {
										$location_label = $country_label;
									}

									if ( !in_array( $country_code, $countries ) ) {
										$countries[] = $country_code;
										if ( $location_label ) {
											$filtered_locations[ $state_code . ':' . $country_code . ':' . $continent_code ] = $location_label;
										}
									}

									if ( !in_array( $continent_code, $continents ) ) {
										$continents[]        = $continent_code;
										$continents          = WC()->countries->get_continents();
										$continent_countries = !empty( $continents[ $continent_code ][ 'countries' ] ) ? $continents[ $continent_code ][ 'countries' ] : array();

										if ( !is_wp_error( $continent_countries ) && is_array( $continent_countries ) && !empty( $continent_countries ) ) {
											$continent_countries = array_merge( array_diff( $continent_countries, array( $country_code ) ), array_diff( array( $country_code ), $continent_countries ) );

											foreach ( $continent_countries as $cont_country_code ) {
												if ( !in_array( $cont_country_code, $countries ) ) {
													$countries[]   = $cont_country_code;
													$country_label = !empty( $shipping_country[ $cont_country_code ] ) ? $shipping_country[ $cont_country_code ] : '';
													if ( $country_label ) {
														$filtered_locations[ $state_code . ':' . $cont_country_code . ':' . $continent_code ] = $country_label;
													}
												}
											}
										}
									}
								}
							}
						}
						asort( $filtered_locations );
						wpfm_set_cached_data( 'filtered_country_list', $filtered_locations );
					}

					if ( is_array( $filtered_locations ) && !empty( $filtered_locations ) ) {
						foreach ( $filtered_locations as $value => $label ) {
							$selected = $saved_country === $value ? ' selected' : '';
							echo '<option value="' . esc_attr( $value ) . '" ' . esc_attr( $selected ) . '>' . esc_attr( $label ) . '</option>';
						}
					}
					?>
				</select>
			</div>

			<div class="<?php echo esc_attr( $this->prefix ) . 'zip_code'; ?> pl-10">
				<label for="<?php echo esc_attr( $this->prefix ) . 'zip_codes_label'; ?>"><?php esc_html_e( 'Zip Code', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'Provides zip codes to be more specific that matches with the WooCommerce shipping zones', 'rex-product-feed' ); ?></p>
					</span>
				</label>
				<?php $saved_zip_codes = get_post_meta( get_the_ID(), '_rex_feed_zip_codes', true ); ?>
				<input type="text" class="<?php echo esc_attr( $this->prefix ) . 'zip_codes'; ?>" id="<?php echo esc_attr( $this->prefix ) . 'zip_codes'; ?>" name="<?php echo esc_attr( $this->prefix ) . 'zip_codes'; ?>" value="<?php echo esc_attr( $saved_zip_codes ); ?>">
			</div>
		</div>

		<div class="<?php echo esc_attr( $this->prefix ) . 'include_out_of_stock'; ?> ">
			<div class="<?php echo esc_attr( $this->prefix ) . 'include_out_of_stock_content'; ?> pl-10">
				<label for="<?php echo esc_attr( $this->prefix ) . 'include_out_of_stock_label'; ?>">
					<?php esc_html_e( 'Include Out of Stock Products', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'This option will include/exclude out of stock products from feed', 'rex-product-feed' ); ?></p>
					</span>
				</label>

				<div class="switch">
					<div class="wpfm-switcher">
						<?php
						$saved_value = get_post_meta( get_the_ID(), '_rex_feed_include_out_of_stock', true );
						$saved_value = $saved_value ?: get_post_meta( get_the_ID(), 'rex_feed_include_out_of_stock', true );
						$saved_value = $saved_value ?: 'yes';
						$checked     = 'yes' === $saved_value ? ' checked' : '';
						?>
						<input class="switch-input" type="checkbox" name="<?php echo esc_attr( $this->prefix ) . 'include_out_of_stock'; ?>" value="yes" id="<?php echo esc_attr( $this->prefix ) . 'include_out_of_stock'; ?>" <?php echo esc_attr( $checked ); ?>>
						<label class="lever" for="<?php echo esc_attr( $this->prefix ) . 'include_out_of_stock'; ?>"></label>
					</div>
				</div>
			</div>

			<div class="<?php echo esc_attr( $this->prefix ) . 'include_zero_price_products_content'; ?> pr-10">
				<label for="<?php echo esc_attr( $this->prefix ) . 'include_zero_price_products_label'; ?>">
					<?php esc_html_e( 'Include Product with No Price', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'This option will include/exclude products with no regular price set or with regular price zero (0)', 'rex-product-feed' ); ?></p>
					</span>
				</label>

				<div class="switch">
					<div class="wpfm-switcher">
						<?php
						$saved_value = get_post_meta( get_the_ID(), '_rex_feed_include_zero_price_products', true );
						$saved_value = $saved_value ?: get_post_meta( get_the_ID(), 'rex_feed_include_zero_price_products', true );
						$saved_value = $saved_value ?: 'yes';
						$checked     = 'yes' === $saved_value ? ' checked' : '';
						?>
						<input class="switch-input" type="checkbox" name="<?php echo esc_attr( $this->prefix ) . 'include_zero_price_products'; ?>" value="yes" id="<?php echo esc_attr( $this->prefix ) . 'include_zero_price_products'; ?>" <?php echo esc_attr( $checked ); ?>>
						<label class="lever" for="<?php echo esc_attr( $this->prefix ) . 'include_zero_price_products'; ?>"></label>
					</div>
				</div>
			</div>
		</div>
		
		<div class="<?php echo esc_attr( $this->prefix ) . 'variable_product_area'; ?> ">

			<div class="<?php echo esc_attr( $this->prefix ) . 'variable_product'; ?> pl-10">
				<label for="<?php echo esc_attr( $this->prefix ) . 'variable_product_label'; ?>">
					<?php esc_html_e( 'Include Variable Parent Product (No Variations)', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'Include Variable Parent Product (No Variations)', 'rex-product-feed' ); ?></p>
					</span>
				</label>

				<div class="switch">
					<div class="wpfm-switcher">
						<?php
						$saved_value = get_post_meta( get_the_ID(), '_rex_feed_variable_product', true );
						$saved_value = $saved_value ?: get_post_meta( get_the_ID(), 'rex_feed_variable_product', true );
						$saved_value = $saved_value ?: 'no';
						$checked     = 'yes' === $saved_value ? ' checked' : '';
						?>
						<input class="switch-input" type="checkbox" name="<?php echo esc_attr( $this->prefix ) . 'variable_product'; ?>" value="yes" id="<?php echo esc_attr( $this->prefix ) . 'variable_product'; ?>" <?php echo esc_attr( $checked ); ?>>
						<label class="lever" for="<?php echo esc_attr( $this->prefix ) . 'variable_product'; ?>"></label>
					</div>
				</div>
			</div>

			<div class="<?php echo esc_attr( $this->prefix ) . 'hidden_products'; ?> pr-10">
				<label for="<?php echo esc_attr( $this->prefix ) . 'hidden_products_label'; ?>"><?php esc_html_e( 'Exclude Invisible/Hidden Products', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'Enable this option to exclude invisible/hidden products from your feed ', 'rex-product-feed' ); ?><a href="<?php echo esc_url( 'https://rextheme.com/docs/wpfm-exclude-invisible-products-hidden-products/?utm_source=plugin&utm_medium=exclude_invisible_products_link&utm_campaign=pfm_plugin' ); ?>" target="_blank"><?php esc_html_e( 'Learn How', 'rex-product-feed' ); ?></a>
					</p>
					</span>
				</label>
				<div class="switch">
					<div class="wpfm-switcher">
						<?php
						$saved_value = get_post_meta( get_the_ID(), '_rex_feed_hidden_products', true );
						$saved_value = $saved_value ?: get_post_meta( get_the_ID(), 'rex_feed_hidden_products', true );
						$saved_value = $saved_value ?: 'no';
						$checked     = 'yes' === $saved_value ? ' checked' : '';
						?>
						<input class="switch-input" type="checkbox" name="<?php echo esc_attr( $this->prefix ) . 'hidden_products'; ?>" value="yes" id="<?php echo esc_attr( $this->prefix ) . 'hidden_products'; ?>" <?php echo esc_attr( $checked ); ?>>
						<label class="lever" for="<?php echo esc_attr( $this->prefix ) . 'hidden_products'; ?>"></label>
					</div>
				</div>
			</div>

			<div class="<?php echo esc_attr( $this->prefix ) . 'variation_product_name'; ?> pl-10">
				<label for="<?php echo esc_attr( $this->prefix ) . 'variation_product_name_label'; ?>"><?php esc_html_e( 'Include Variation Name In The Product Title', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p>
							<?php
							esc_html_e( 'Include the variation name in the product title', 'rex-product-feed' );
							echo "<br>";
							esc_html_e( 'Example:', 'rex-product-feed' );
							echo "<br>";
							esc_html_e( '<g:title>', 'rex-product-feed' );
							echo "<br>";
							esc_html_e( '<![CDATA[ V-Neck T-Shirt - Red ]]>', 'rex-product-feed' );
							echo "<br>";
							esc_html_e( '</g:title>', 'rex-product-feed' );
							?>
							<a href="<?php echo esc_url( 'https://rextheme.com/docs/how-to-include-product-variation-term-to-the-product-name/?utm_source=plugin&utm_medium=exclude_variation_name_link&utm_campaign=pfm_plugin' ); ?>" target="_blank"><?php esc_html_e( 'Learn How', 'rex-product-feed' ); ?></a>
						</p>
					</span>
				</label>

				<div class="switch">
					<div class="wpfm-switcher">
						<?php
						$saved_value = get_post_meta( get_the_ID(), '_rex_feed_variation_product_name', true );
						$saved_value = $saved_value ?: get_post_meta( get_the_ID(), 'rex_feed_variation_product_name', true );
						$saved_value = $saved_value ?: 'no';
						$checked     = 'yes' === $saved_value ? ' checked' : '';
						?>
						<input class="switch-input" type="checkbox" name="<?php echo esc_attr( $this->prefix ) . 'variation_product_name'; ?>" value="yes" id="<?php echo esc_attr( $this->prefix ) . 'variation_product_name'; ?>" <?php echo esc_attr( $checked ); ?>>
						<label class="lever" for="<?php echo esc_attr( $this->prefix ) . 'variation_product_name'; ?>"></label>
					</div>
				</div>
			</div>

			<div class="<?php echo esc_attr( $this->prefix ) . 'parent_product'; ?> pr-10">
				<label for="<?php echo esc_attr( $this->prefix ) . 'parent_product_label'; ?>"><?php esc_html_e( 'Include Grouped Products', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'Enable this option to include grouped products in your feed', 'rex-product-feed' ); ?></p>
					</span>
				</label>

				<div class="switch">
					<div class="wpfm-switcher">
						<?php
						$saved_value = get_post_meta( get_the_ID(), '_rex_feed_parent_product', true );
						$saved_value = $saved_value ?: get_post_meta( get_the_ID(), 'rex_feed_parent_product', true );
						$saved_value = $saved_value ?: 'yes';
						$checked     = 'yes' === $saved_value ? ' checked' : '';
						?>
						<input class="switch-input" type="checkbox" name="<?php echo esc_attr( $this->prefix ) . 'parent_product'; ?>" value="yes" id="<?php echo esc_attr( $this->prefix ) . 'parent_product'; ?>" <?php echo esc_attr( $checked ); ?>>
						<label class="lever" for="<?php echo esc_attr( $this->prefix ) . 'parent_product'; ?>"></label>
					</div>
				</div>
			</div>

			<div class="<?php echo esc_attr( $this->prefix ) . 'variations'; ?> pl-10">
				<label for="<?php echo esc_attr( $this->prefix ) . 'variations_label'; ?>"><?php esc_html_e( 'Include All Variable Products Variations', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p>
							<?php
							esc_html_e( 'Include all the Variable Products Variations in your feed (these are only the product variations)', 'rex-product-feed' );
							echo "<br>";
							esc_html_e( 'Example:', 'rex-product-feed' );
							echo "<br>";
							esc_html_e( '<g:title>', 'rex-product-feed' );
							echo "<br>";
							esc_html_e( '<![CDATA[ V-Neck T-Shirt]]>', 'rex-product-feed' );
							echo "<br>";
							esc_html_e( '</g:title>', 'rex-product-feed' );
							echo "<br>";
							esc_html_e( '<g:link>', 'rex-product-feed' );
							echo "<br>";
							esc_html_e( '<![CDATA[ http://URL/]]>', 'rex-product-feed' );
							echo "<br>";
							esc_html_e( '</g:link>', 'rex-product-feed' );
							echo "<br>";
							?>
						</p>
					</span>
				</label>

				<div class="switch">
					<div class="wpfm-switcher">
						<?php
						$saved_value = get_post_meta( get_the_ID(), '_rex_feed_variations', true );
						$saved_value = $saved_value ?: get_post_meta( get_the_ID(), 'rex_feed_variations', true );
						$saved_value = $saved_value ?: 'yes';
						$checked     = 'yes' === $saved_value ? ' checked' : '';
						?>
						<input class="switch-input" type="checkbox" name="<?php echo esc_attr( $this->prefix ) . 'variations'; ?>" value="yes" id="<?php echo esc_attr( $this->prefix ) . 'variations'; ?>" <?php echo esc_attr( $checked ); ?>>
						<label class="lever" for="<?php echo esc_attr( $this->prefix ) . 'variations'; ?>"></label>
					</div>
				</div>
			</div>

		</div>

		<div class="<?php echo esc_attr( $this->prefix ) . 'skip_product_area'; ?> ">
			<div class="<?php echo esc_attr( $this->prefix ) . 'skip_product'; ?> pl-10">
				<label for="<?php echo esc_attr( $this->prefix ) . 'skip_product_label'; ?>"><?php esc_html_e( 'Skip products with empty value', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'This option will remove products if there is a single attribute with empty value', 'rex-product-feed' ); ?></p>
					</span>
				</label>

				<div class="switch">
					<div class="wpfm-switcher">
						<?php
						$saved_value = get_post_meta( get_the_ID(), '_rex_feed_skip_product', true );
						$saved_value = $saved_value ?: get_post_meta( get_the_ID(), 'rex_feed_skip_product', true );
						$saved_value = $saved_value ?: 'no';
						$checked     = 'yes' === $saved_value ? ' checked' : '';
						?>
						<input class="switch-input" type="checkbox" name="<?php echo esc_attr( $this->prefix ) . 'skip_product'; ?>" value="yes" id="<?php echo esc_attr( $this->prefix ) . 'skip_product'; ?>" <?php echo esc_attr( $checked ); ?>>
						<label class="lever" for="<?php echo esc_attr( $this->prefix ) . 'skip_product'; ?>"></label>
					</div>
				</div>
				
			</div>

			<div class="<?php echo esc_attr( $this->prefix ) . 'skip_row'; ?> pr-10">
				<label for="<?php echo esc_attr( $this->prefix ) . 'skip_row_label'; ?>"><?php esc_html_e( 'Skip attributes with empty value', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'This option will remove any attribute with empty value (XML feed format only)', 'rex-product-feed' ); ?></p>
					</span>
				</label>

				<div class="switch">
					<div class="wpfm-switcher">
						<?php
						$saved_value = get_post_meta( get_the_ID(), '_rex_feed_skip_row', true );
						$saved_value = $saved_value ?: get_post_meta( get_the_ID(), 'rex_feed_skip_row', true );
						$saved_value = $saved_value ?: 'no';
						$checked     = 'yes' === $saved_value ? ' checked' : '';
						?>
						<input class="switch-input" type="checkbox" name="<?php echo esc_attr( $this->prefix ) . 'skip_row'; ?>" value="yes" id="<?php echo esc_attr( $this->prefix ) . 'skip_row'; ?>" <?php echo esc_attr( $checked ); ?>>
						<label class="lever" for="<?php echo esc_attr( $this->prefix ) . 'skip_row'; ?>"></label>
					</div>
				</div>
				
			</div>

		</div>

		<!-- .rex_feed_skip_product_area end -->
		<div class="<?php echo esc_attr( $this->prefix ) . 'wcml_currency_area'; ?>">

			<?php
			if ( in_array( 'woocommerce-multilingual/wpml-woocommerce.php', get_option( 'active_plugins', array() ) ) ) {
				global $sitepress, $woocommerce_wpml;
				$wcml_settings   = get_option( '_wcml_settings' );
				$wcml_currencies = !empty( $wcml_settings[ 'currency_options' ] ) ? $wcml_settings[ 'currency_options' ] : array();
				$currencies      = array();

				foreach ( $wcml_currencies as $key => $value ) {
					$currencies[ $key ] = $key;
				}

				if ( is_array( $currencies ) ) {
					reset( $currencies );
				}
				?>

			<div class="<?php echo esc_attr( $this->prefix ) . 'wcml_currency'; ?>">
				<label for="<?php echo esc_attr( $this->prefix ) . 'wcml_currency'; ?>"><?php esc_html_e( 'WCML Currency', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php include WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'This option will convert all your product prices using WooCommerce Multilingual & Multicurrency', 'rex-product-feed' ); ?></p>
					</span>
				</label>
				<select name="<?php echo esc_html( $this->prefix ) . 'wcml_currency'; ?>" id="<?php echo esc_html( $this->prefix ) . 'wcml_currency'; ?>" class="">
					<?php
					$selected_price = get_post_meta( get_the_ID(), '_rex_feed_wcml_currency', true );
					$selected_price = $selected_price ?: get_post_meta( get_the_ID(), 'rex_feed_wcml_currency', true );
					foreach ( $currencies as $key => $value ) {
						$selected = $selected_price === $key ? ' selected' : '';
						echo '<option value="' . esc_attr( $key ) . '" ' . esc_html( $selected ) . '>' . esc_attr( $value ) . '</option>';
					}
					?>
				</select>
			</div>
			<?php } ?>

			<?php
			if ( wpfm_is_aelia_active() ) {
				$aelia_settings       = get_option( 'wc_aelia_currency_switcher' );
				$enabled_currency     = is_array( $aelia_settings ) && isset( $aelia_settings[ 'enabled_currencies' ] )
					? $aelia_settings[ 'enabled_currencies' ] : '';
				$aelia_world_currency = get_woocommerce_currencies();
				$aelia_world_currency = is_array( $aelia_world_currency ) ? $aelia_world_currency : array();
				$currency_options     = array();

				if ( is_array( $enabled_currency ) && !empty( $enabled_currency ) ) {
					foreach ( $enabled_currency as $currency ) {
						if ( array_key_exists( $currency, $aelia_world_currency ) ) {
							$currency_options[ $currency ] = $aelia_world_currency[ $currency ];
						}
					}
				}
				else {
					$currency_options = array( 'Please configure Aelia Currency Switcher!' );
				}
				?>
				<div class="<?php echo esc_attr( $this->prefix ) . 'aelia_currency'; ?>">
					<label for="<?php echo esc_attr( $this->prefix ) . 'aelia_currency'; ?>"><?php esc_html_e( 'Aelia Currency', 'rex-product-feed' ); ?>
						<i class="fa fa-question-circle" aria-hidden="true"></i>
					</label>
					<select name="<?php echo esc_html( $this->prefix ) . 'aelia_currency'; ?>" id="<?php echo esc_html( $this->prefix ) . 'aelia_currency'; ?>" class="">
						<?php
						$selected_price = get_post_meta( get_the_ID(), '_rex_feed_aelia_currency', true );
						$selected_price = $selected_price ?: get_post_meta( get_the_ID(), 'rex_feed_aelia_currency', true );
						foreach ( $currency_options as $key => $value ) {
							$selected = $selected_price === $key ? ' selected' : '';
							echo '<option value="' . esc_attr( $key ) . '" ' . esc_html( $selected ) . '>' . esc_attr( $value ) . '</option>';
						}
						?>
					</select>
				</div>

			<?php } ?>

			<?php
			if ( wpfm_is_wmc_active() ) {
				$wmc_settings         = class_exists( 'WOOMULTI_CURRENCY_Data' ) ? WOOMULTI_CURRENCY_Data::get_ins() : array();
				$wmc_default_currency = !empty( $wmc_settings ) ? $wmc_settings->get_default_currency() : 'USD';
				$wmc_currency_list    = !empty( $wmc_settings ) ? $wmc_settings->currencies_list : array();
				$wmc_world_currency   = get_woocommerce_currencies();
				$wmc_world_currency   = is_array( $wmc_world_currency ) ? $wmc_world_currency : array();
				$currency_options     = array();

				if ( is_array( $wmc_currency_list ) && !empty( $wmc_currency_list ) ) {
					foreach ( $wmc_currency_list as $key => $value ) {
						if ( array_key_exists( $key, $wmc_world_currency ) ) {
							$currency_options[ $key ] = $wmc_world_currency[ $key ];
						}
					}
				}
				else {
					$currency_options = array( 'Please configure WooCommerce Multi-Currency Switcher!' );
				}
				?>
				
				<div class="<?php echo esc_attr( $this->prefix ) . 'wmc_currency'; ?>">
					<label for="<?php echo esc_attr( $this->prefix ) . 'wmc_currency'; ?>"><?php esc_html_e( 'WooCommerce Multi-Currency', 'rex-product-feed' ); ?>
						<span class="rex_feed-tooltip">
						<?php include WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'This option will convert all your product prices using WooCommerce Multi-Currency Switcher', 'rex-product-feed' ); ?></p>
					</span>
					</label>
					<select name="<?php echo esc_html( $this->prefix ) . 'wmc_currency'; ?>" id="<?php echo esc_html( $this->prefix ) . 'wmc_currency'; ?>" class="">
						<?php
						$selected_price = get_post_meta( get_the_ID(), '_rex_feed_wmc_currency', true );
						$selected_price = $selected_price ?: get_post_meta( get_the_ID(), 'rex_feed_wmc_currency', true );
						$selected_price = $selected_price ?: $wmc_default_currency;
						foreach ( $currency_options as $key => $value ) {
							$selected = $selected_price === $key ? ' selected' : '';
							echo '<option value="' . esc_attr( $key ) . '" ' . esc_html( $selected ) . '>' . esc_attr( $value ) . '</option>';
						}
						?>
					</select>
				</div>

			<?php } ?>

		</div>

		<div class="<?php echo esc_attr( $this->prefix ) . 'analytics_params_options'; ?>">
			<div class="<?php echo esc_attr( $this->prefix ) . 'analytics_params_content'; ?>">
				<label for="<?php echo esc_attr( $this->prefix ) . 'analytics_params_options_content'; ?>"><?php esc_html_e( 'Track Your Campaign', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'Analytics Parameters ', 'rex-product-feed' ); ?><a href="<?php echo esc_url( 'https://rextheme.com/docs/how-to-add-utm-parameters-to-product-urls/?utm_source=plugin&utm_medium=analytics_parameters_link&utm_campaign=pfm_plugin' ); ?>" target="_blank"><?php esc_html_e( 'Learn How', 'rex-product-feed' ); ?></a>
					</p>
					</span>
				</label>

				<div class="switch">
					<div class="wpfm-switcher">
						<?php
						$saved_value = get_post_meta( get_the_ID(), '_rex_feed_analytics_params_options', true );
						$saved_value = $saved_value ?: get_post_meta( get_the_ID(), 'rex_feed_analytics_params_options', true );
						$saved_value = $saved_value ?: 'no';
						$checked     = 'yes' === $saved_value ? ' checked' : '';
						?>
						<input class="switch-input" type="checkbox" name="<?php echo esc_attr( $this->prefix ) . 'analytics_params_options'; ?>" value="yes" id="<?php echo esc_attr( $this->prefix ) . 'analytics_params_options'; ?>" <?php echo esc_attr( $checked ); ?>>
						<label class="lever" for="<?php echo esc_attr( $this->prefix ) . 'analytics_params_options'; ?>"></label>
					</div>
				</div>
			</div>

			<span class="<?php echo esc_attr( $this->prefix ) . 'toggle_utm'; ?>"><?php esc_html_e( 'On Toggle to activate UTM Params', 'rex-product-feed' ); ?></span>

		</div>

		<div class="<?php echo esc_attr( $this->prefix ) . 'analytics_params'; ?>" style="display: none">
			<label for="<?php echo esc_attr( $this->prefix ) . 'analytics_params'; ?>"><?php esc_html_e( 'UTM Parameters', 'rex-product-feed' ); ?></label>
			<ul id="<?php echo esc_html( $this->prefix ) . 'analytics_params'; ?>">
				<?php
				$analytics_params = get_post_meta( get_the_ID(), '_rex_feed_analytics_params', true );
				$analytics_params = $analytics_params ?: get_post_meta( get_the_ID(), 'rex_feed_analytics_params', true );
				$utm_source       = !empty( $analytics_params[ 'utm_source' ] ) ? $analytics_params[ 'utm_source' ] : '';
				$utm_medium       = !empty( $analytics_params[ 'utm_medium' ] ) ? $analytics_params[ 'utm_medium' ] : '';
				$utm_campaign     = !empty( $analytics_params[ 'utm_campaign' ] ) ? $analytics_params[ 'utm_campaign' ] : '';
				$utm_term         = !empty( $analytics_params[ 'utm_term' ] ) ? $analytics_params[ 'utm_term' ] : '';
				$utm_content      = !empty( $analytics_params[ 'utm_content' ] ) ? $analytics_params[ 'utm_content' ] : '';

				echo '<li>';
				?>
				<label for="<?php echo esc_attr( $this->prefix ) . 'analytics_params_utm_source'; ?>"><?php esc_html_e( 'Referrer', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
							<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
							<p><?php esc_html_e( 'The referrer: (e.g. google, newsletter)', 'rex-product-feed' ); ?></p>
							</span>
				</label>

				<?php
				echo '<input type="text" name="' . esc_html( $this->prefix ) . 'analytics_params[utm_source]' . '" value="' . esc_attr( $utm_source ) . '" id="' . esc_attr( $this->prefix ) . 'analytics_params_utm_source' . '">';
				echo '</li>';

				echo '<li>';
				?>
				<label for="<?php echo esc_attr( $this->prefix ) . 'analytics_params_utm_medium'; ?>"><?php esc_html_e( 'Medium', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'Marketing medium: (e.g. cpc, banner, email)', 'rex-product-feed' ); ?></p>
					</span>
				</label>

				<?php
				echo '<input type="text" name="' . esc_html( $this->prefix ) . 'analytics_params[utm_medium]' . '" value="' . esc_attr( $utm_medium ) . '" id="' . esc_attr( $this->prefix ) . 'analytics_params_utm_medium' . '">';
				echo '</li>';

				echo '<li>';
				?>
				<label for="<?php echo esc_attr( $this->prefix ) . 'analytics_params_utm_campaign'; ?>"><?php esc_html_e( 'Campaign', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'Product, promo code, or slogan (e.g. spring_sale)', 'rex-product-feed' ); ?></p>
					</span>
				</label>

				<?php
				echo '<input type="text" name="' . esc_html( $this->prefix ) . 'analytics_params[utm_campaign]' . '" value="' . esc_attr( $utm_campaign ) . '" id="' . esc_attr( $this->prefix ) . 'analytics_params_utm_campaign' . '">';
				echo '</li>';


				echo '<li>';
				?>
				<label for="<?php echo esc_attr( $this->prefix ) . 'analytics_params_utm_term'; ?>"><?php esc_html_e( 'Campaign Term', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'Identify the paid keywords', 'rex-product-feed' ); ?></p>
					</span>
				</label>

				<?php
				echo '<input type="text" name="' . esc_html( $this->prefix ) . 'analytics_params[utm_term]' . '" value="' . esc_attr( $utm_term ) . '" id="' . esc_attr( $this->prefix ) . 'analytics_params_utm_term' . '">';
				echo '</li>';

				echo '<li>';
				?>
				<label for="<?php echo esc_attr( $this->prefix ) . 'analytics_params_utm_content'; ?>"><?php esc_html_e( 'Campaign Content', 'rex-product-feed' ); ?>
					<span class="rex_feed-tooltip">
						<?php require WPFM_PLUGIN_ASSETS_FOLDER_PATH . $icon_question; ?>
						<p><?php esc_html_e( 'Use to differentiate ads', 'rex-product-feed' ); ?></p>
					</span>
				</label>

				<?php
				echo '<input type="text" name="' . esc_html( $this->prefix ) . 'analytics_params[utm_content]' . '" value="' . esc_attr( $utm_content ) . '" id="' . esc_attr( $this->prefix ) . 'analytics_params_utm_content' . '">';
				echo '</li>';
				?>
			</ul>
		</div>
	</div>

</div>
