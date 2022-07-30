<?php $icon = '../assets/icon/icon-svg/icon-question.php'; ?>

<?php
$value = get_post_meta( get_the_ID(), 'rex_feed_google_target_country', true );
$value = $value == '' ? 'US' : $value;
?>


<div class="<?php echo esc_attr( $this->prefix ) . 'google_merchant_content__area'; ?>">

	<div class="<?php echo esc_attr( $this->prefix ) . 'google_desc__area'; ?>">
		<p>
			Please note that Google has fixed abbreviations for Location and Language. For example, the abbreviation for target location, United States is US and the abbreviation for language, English is en.  
		</p>

		<div class="<?php echo esc_attr( $this->prefix ) . 'google_desc__link';?>">
			<a href="<?php echo esc_url( 'https://rextheme.com/google-country-codes-list/' )?>" target="_blank"><?php esc_html_e('Check Abbreviation Lists','rex-product-feed')?></a>
			<a href="<?php echo esc_url( 'https://rextheme.com/docs/how-to-auto-sync-product-feed-to-google-merchant-shop/' )?>" target="_blank"><?php esc_html_e('Auto-sync Product Feed', 'rex-product-feed')?></a>
			<a href="<?php echo esc_url( 'https://rextheme.com/wp-content/uploads/2020/08/WPFM-New-Feed-Direct-Auto-sync-to-Google.pdf' )?>" target="_blank"><?php esc_html_e('Reverse Method (Direct Upload)', 'rex-product-feed')?></a>
		</div>

	</div>

	<div class="<?php echo esc_attr( $this->prefix ) . 'google_target__area'; ?>">
		<div class="<?php echo esc_attr( $this->prefix ) . 'google_target__content'; ?>">
			<div id="<?php echo esc_attr( $this->prefix ) . 'google_target_country__content'; ?>" class="<?php echo esc_attr( $this->prefix ) . 'google_target_country__content'; ?>">

				<label for="<?php echo esc_attr( $this->prefix ) . 'google_target_country';?>"><?php esc_html_e('Target Country', 'rex-product-feed')?>
					<span class="rex_feed-tooltip">
								<?php include plugin_dir_path(__FILE__) . $icon;?>
								<p><?php esc_html_e('Target Country', 'rex-product-feed')?></p>
							</span>
				</label>

				<input type="text" id="<?php echo esc_attr( $this->prefix ) . 'google_target_country';?>" value="<?php echo esc_attr($value)?>" name="<?php echo esc_attr( $this->prefix ) . 'google_target_country'?>" required>
			</div>

			<?php
			$value = get_post_meta( get_the_ID(), 'rex_feed_google_target_language', true );
			$value = $value == '' ? 'en' : $value;
			
			?>
			<div id="<?php echo esc_attr( $this->prefix ) . 'google_target_language__content'; ?>" class="<?php echo esc_attr( $this->prefix ) . 'google_target_language__content'; ?>">
				<label for="<?php echo esc_attr( $this->prefix ) . 'google_target_language';?>"><?php esc_html_e('Target Language', 'rex-product-feed')?>
					<span class="rex_feed-tooltip">
								<?php include plugin_dir_path(__FILE__) . $icon;?>
								<p><?php esc_html_e('Target Language', 'rex-product-feed')?></p>
							</span>
				</label>
				<input type="text" id="<?php echo esc_attr( $this->prefix ) . 'google_target_language';?>" value="<?php echo esc_attr($value)?>" name="<?php echo esc_attr( $this->prefix ) . 'google_target_language'?>" required>
			</div>

			<div id="<?php echo esc_attr( $this->prefix ) . 'google_schedule__content'; ?>" class="<?php echo esc_attr( $this->prefix ) . 'google_schedule__content'; ?>">
				<label for="<?php echo esc_attr( $this->prefix ) . 'google_schedule';?>"><?php esc_html_e('Schedule', 'rex-product-feed')?>
					<span class="rex_feed-tooltip">
								<?php include plugin_dir_path(__FILE__) . $icon;?>
								<p><?php esc_html_e('Schedule', 'rex-product-feed')?></p>
							</span>
				</label>
				<select name="<?php echo esc_attr( $this->prefix ) . 'google_schedule'; ?>" id="<?php echo esc_attr( $this->prefix ) . 'google_schedule'; ?>">
					<?php
					$prev_value = get_post_meta( get_the_ID(), 'rex_feed_google_schedule', true );
					$prev_value = $prev_value !== '' ? $prev_value : 'monthly';
					foreach ( $schedules as $key => $value ) {
						$selected = $key == $prev_value ? ' selected' : '';
						echo '<option value="'.esc_attr($key).'" ' .esc_attr($selected). '>'.esc_attr($value).'</option>';
					}
					?>
				</select>
			</div>
		</div>
		
		<?php 
			$feed_merchant = get_post_meta( get_the_ID(), 'rex_feed_merchant', true );
			
			if ( $feed_merchant === 'google' ) {
				$rex_google_merchant = new Rex_Google_Merchant_Settings_Api();

                if ( $rex_google_merchant::$client_id && $rex_google_merchant::$client_secret && $rex_google_merchant::$merchant_id ) {
                    $message = __('Oops!! Access token has expired. Please, authenticate again if you want to submit a new fresh feed to Google Merchant Center.', 'rex-product-feed');
                    $button = __( 'Authenticate', 'rex-product-feed' );
                }
                else {
                    $message = __('Use Google Auto-sync to send data to your Google Merchant Center at fixed intervals. Configure and Authenticate Auto-sync with Google to be able to use this feature.', 'rex-product-feed');
                    $button = __( 'Configure', 'rex-product-feed' );
                }
				
				if ( !( $rex_google_merchant->is_authenticate() ) ) {
					echo '<div class="google-status-area">';
					echo sprintf(
						'<p class="google-status">%s</p>',
						esc_html( $message ) );

					echo sprintf(
						'<a href="%s" class="btn-default">' . esc_html( $button ) . '</a>',
						esc_url( admin_url( 'admin.php?page=merchant_settings' ) ) );

						echo '</div>';
				}
				else {
					echo '<a class="btn waves-effect waves-light" id="send-to-google" href="#">
							' . esc_attr__( 'Send to google merchant', 'rex-product-feed' ) . '
						</a> ';
				}
				echo '<div class="rex-google-status"></div>';
			}
		?>

	</div>

</div>



<div id="<?php echo esc_attr( $this->prefix ) . 'google_schedule_month__content'; ?>" class="<?php echo esc_attr( $this->prefix ) . 'google_schedule_month__content'; ?>" style="display: none">
	<label for="<?php echo esc_attr( $this->prefix ) . 'google_schedule_month';?>"><?php esc_html_e('Select Day of Month', 'rex-product-feed')?>
		<span class="rex_feed-tooltip">
                    <?php include plugin_dir_path(__FILE__) . $icon;?>
                    <p><?php esc_html_e('Select Day of Month', 'rex-product-feed')?></p>
                </span>
	</label>
	<select name="<?php echo esc_attr( $this->prefix ) . 'google_schedule_month'; ?>"
	        id="<?php echo esc_attr( $this->prefix ) . 'google_schedule_month'; ?>"
	        data-conditional-value="monthly"
	        data-conditional-id="<?php echo esc_attr( $this->prefix ) . 'google_schedule'; ?>">
		<?php
		$prev_value = get_post_meta( get_the_ID(), 'rex_feed_google_schedule_month', true );
		$prev_value = $prev_value !== '' ? $prev_value : '1';
		foreach ( $month_array as $key => $value ) {
			$selected = $key == $prev_value ? ' selected' : '';
			echo '<option value="'.esc_attr($key).'" ' .esc_attr($selected). '>'.esc_attr($value).'</option>';
		}
		?>
	</select>
</div>

<div id="<?php echo esc_attr( $this->prefix ) . 'google_schedule_week_day__content'; ?>" class="<?php echo esc_attr( $this->prefix ) . 'google_schedule_week_day__content'; ?>" style="display: none">
	<label for="<?php echo esc_attr( $this->prefix ) . 'google_schedule_month';?>"><?php esc_html_e('Select Day of Week', 'rex-product-feed')?>
		<span class="rex_feed-tooltip">
                    <?php include plugin_dir_path(__FILE__) . $icon;?>
                    <p><?php esc_html_e('Select Day of Week', 'rex-product-feed')?></p>
                </span>
	</label>
	<select name="<?php echo esc_attr( $this->prefix ) . 'google_schedule_week_day'; ?>"
	        id="<?php echo esc_attr( $this->prefix ) . 'google_schedule_week_day'; ?>"
	        data-conditional-value="weekly"
	        data-conditional-id="<?php echo esc_attr( $this->prefix ) . 'google_schedule'; ?>">
		<?php
		$prev_value = get_post_meta( get_the_ID(), 'rex_feed_google_schedule_week_day', true );
		$prev_value = $prev_value !== '' ? $prev_value : 'monday';
		foreach ( $weeks as $key => $value ) {
			$selected = $key == $prev_value ? ' selected' : '';
			echo '<option value="'.esc_attr($key).'" ' .esc_attr($selected). '>'.esc_attr($value).'</option>';
		}
		?>
	</select>
</div style="display: none">

<div id="<?php echo esc_attr( $this->prefix ) . 'google_schedule_time__content'; ?>" class="<?php echo esc_attr( $this->prefix ) . 'google_schedule_time__content'; ?>" style="display: none">
	<label for="<?php echo esc_attr( $this->prefix ) . 'google_schedule_time';?>"><?php esc_html_e('Select Hour', 'rex-product-feed')?>
		<span class="rex_feed-tooltip">
                    <?php include plugin_dir_path(__FILE__) . $icon;?>
                    <p><?php esc_html_e('Select Hour', 'rex-product-feed')?></p>
                </span>
	</label>
	<select name="<?php echo esc_attr( $this->prefix ) . 'google_schedule_time'; ?>"
	        id="<?php echo esc_attr( $this->prefix ) . 'google_schedule_time'; ?>">
		<?php
		$prev_value = get_post_meta( get_the_ID(), 'rex_feed_google_schedule_time', true );
		$prev_value = $prev_value !== '' ? $prev_value : '1';
		foreach ( range( 0, 23 ) as $key => $value ) {
			$selected = $key == $prev_value ? ' selected' : '';
			echo '<option value="'.esc_attr($value).'" ' .esc_attr($selected). '>'.esc_attr($value).'</option>';
		}
		?>
	</select>
</div>