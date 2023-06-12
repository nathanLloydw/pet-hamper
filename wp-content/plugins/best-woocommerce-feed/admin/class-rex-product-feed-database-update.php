<?php
/**
 * Class Rex_Product_Feed_Database_Update
 *
 * @link       https://rextheme.com
 * @since      2.0.0
 *
 * @package    Rex_Product_Feed_Database_Update
 * @subpackage Rex_Product_Feed/admin
 */

/**
 * The Rex_Product_Feed_Database_Update class file that
 * handle database update process
 *
 * @link       https://rextheme.com
 * @since      2.0.0
 *
 * @package    Rex_Product_Feed_Database_Update
 * @subpackage Rex_Product_Feed/admin
 */
class Rex_Product_Feed_Database_Update extends WP_Background_Process {

	/**
	 * Scheduler name
	 *
	 * @var string $action Scheduler name.
	 */
	protected $action = 'rex_product_feed_database_update';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $database_update_callback Queue item to iterate over.
	 *
	 * @return mixed
	 */
	protected function task( $database_update_callback ) {
		sleep( 5 );
		foreach ( $database_update_callback as $version ) {
			foreach ( $version as $callback ) {
				if ( 'wpfm_update_category_mapping' === $callback ) {
					$this->wpfm_update_category_mapping();
					$this->wpfm_update_feeds();
				}
			}
		}
		return false;
	}

	/**
	 * Updating category mapping
	 *
	 * @return void
	 */
	protected function wpfm_update_category_mapping() {
		$all_options     = wp_load_alloptions();
		$cat_map_options = array();
		$file            = dirname( __FILE__ ) . '/partials/google_category_list.txt';

		foreach ( $all_options as $name => $value ) {
			if ( stristr( $name, 'rex_cat_map_' ) ) {
				$unserialize = json_decode( $value );
				$map_name    = sanitize_title( $unserialize['map-name'] );
				$map_config  = $unserialize['map-config'];
				foreach ( $map_config as $key =>$config ) {
					$map_key     = $config['map-key'];
					$product_cat = get_term_by( 'id', $map_key, 'product_cat' );
					$map_value   = $config['map-value'];
					$handle      = @fopen( $file, "r" ); //phpcs:ignore
					if ( $handle ) {
						while ( !feof( $handle ) ) {
							$cat   = fgets( $handle );
							$match = explode( '-', $cat );
							$match = array_pop( $match );
							if ( trim( $map_value ) === trim( $match ) ) {
								$config['map-value'] = $cat;
							}
						}
					}
					fclose( $handle ); //phpcs:ignore

					if ( $product_cat ) {
						$category_name      = $product_cat->name;
						$config['cat-name'] = $category_name;
					}
					$map_config[ $key ] = $config;
				}
				$unserialize['map-config']                    = $map_config;
				$cat_map_options[ md5( $map_name . time() ) ] = $unserialize;
			}
		}

		update_option( 'rex-wpfm-category-mapping', $cat_map_options );
	}

	/**
	 * Updating existing feeds
	 *
	 * @return void
	 */
	protected function wpfm_update_feeds() {
		$args     = array(
			'posts_per_page' => -1,
			'post_type'      => 'product-feed',
			'post_status'    => 'publish',
			'fields'         => 'ids',
		);
		$feed_ids = get_posts( $args );
		if ( $feed_ids ) {
			foreach ( $feed_ids as $feed_id ) {
				$feed_config = get_post_meta( $feed_id, '_rex_feed_feed_config', true ) ?: get_post_meta( $feed_id, 'rex_feed_feed_config', true );
				foreach ( $feed_config as $key => $config ) {
					if ( is_array( $config ) && isset( $config[ 'attr' ] ) && 'google_product_category' === $config[ 'attr' ] ) {
						$map_option = get_option( $config['meta_key'] );
						if ( $map_option ) {
							$map_name             = md5( sanitize_title( $map_option['map-name'] ) . time() );
                            $config[ 'meta_key' ] = $map_name; //phpcs:ignore
						}
					}
					$feed_config[ $key ] = $config;
				}
				update_post_meta( $feed_id, '_rex_feed_feed_config', $feed_config );
			}
		}
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		parent::complete();
		delete_transient( 'rex-wpfm-database-update-running' );
	}
}
