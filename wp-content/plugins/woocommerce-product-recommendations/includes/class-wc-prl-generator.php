<?php
/**
 * WC_PRL_Generator class
 *
 * @package  WooCommerce Product Recommendations
 * @since    3.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recommendations generator class.
 *
 * @class    WC_PRL_Generator
 * @version  3.0.0
 */
final class WC_PRL_Generator {

	/**
	 * Logging group name.
	 *
	 * @const string
	 */
	const LOG_GROUP_NAME = 'wc_prl_generator_tasks';

	/*
	|--------------------------------------------------------------------------
	| Recommendations generation task helpers.
	|--------------------------------------------------------------------------
	| 
	| Methods that assist in managing the state of the shared $data array.
	|
	*/

	/**
	 * Set up data for the next amplifier loop.
	 *
	 * @access protected
	 * 
	 * @param  array &$data
	 * @return void
	 */
	protected function next_amplifier( &$data ) {
		unset( $data[ 'current_amp_data' ] );
		unset( $data[ 'current_amp_substep' ] );
		unset( $data[ 'current_amp_substep_return' ] );
	}

	/*
	|--------------------------------------------------------------------------
	| Recommendations generation methods.
	|--------------------------------------------------------------------------
	*/

	/**
	 * Generates recommendations.
	 * 
	 * @since 1.0.0
	 * 
	 * This process is divided in three steps. Each step is connected to the previous with the $data array being the shared state.
	 * Each time this task returns a non-empty array the system will loop again into the next async request with the new data state. 
	 * 
	 * ///
	 * Step: 1 (or empty)
	 *
	 * In the first step we check for engine filters and apply them to the query_args array.
	 *
	 * /// 
	 * Step: 2
	 * 
	 * The second step is the amplifiers step. On this point, the system loop through the engine's amplifiers and run them all.
	 * Each amplifier and therefore the query will be handled in sequencial async requests. The process will move to step 3 when all amplifiers are calculated.
	 * 
	 * ///
	 * Step: 3
	 * 
	 * In case the engine has amplifiers, this stage involves a weighted merging process of the results obtained from each amplifier in step 2. 
	 * However, if there are no amplifiers, step 1 proceeds directly to this step and executes an un-amplified/simple query to retrieve the filtered data from step 1. Finally, the results, whether from the weighted merge or the un-amplified query, are stored in the deployments metadata table.
	 * 
	 * The entire step loop is repeated for each deployment ID included in the original $data. To ensure performance and safety, there is a bridge that restricts the process to 30 consecutive asynchronous requests and terminates the loop.
	 * 
	 *
	 * @param  array  $data  {
	 *     @type  array  $id                   Deployment ID.
	 *     @type  array  $source               The deployments source data.
	 *     @type  array  $filtered_query_args  Indicates whether the filters have been applied to the current deployment.
	 *     @type  array  $amplifiers           The amplifiers queue for the current deployment.
	 *     @type  array  $results              Keeps the progress data of the amplifiers.
	 *     @type  string $item_key             The key that represents the queue item (Optional.)
	 *     @type  bool   $force                Force the regeneration of the deployment batch.
	 *     @type  int    $step                 Enum( 1, 2, 3 ). Used for keeping track of what needs to run.
	 *
	 *         - 1: Apply filters and cache amplifiers.
	 *         - 2: Run amplifiers one by one.
	 *         - 3: Combine results.
	 *
	 * }
	 * @return mixed
	 */
	public function run( $data ) {

		////////////////////////////////////////////////
		// Continue running with caution...
		////////////////////////////////////////////////
		if ( empty( $data[ 'safety_bridge' ] ) || ! is_numeric( $data[ 'safety_bridge' ] ) ) {
			$data[ 'safety_bridge' ] = 0;
		}
		
		if ( ! empty( $data[ 'item_key' ] ) ) {
			WC_PRL()->deployments->queue->increment_iterations( $data[ 'item_key' ] );
		}

		$data[ 'safety_bridge' ]++;
		$max_repeats_per_task = 3 * 10; // Hint: 3 steps * 10 deployments at time.

		if ( $data[ 'safety_bridge' ] > $max_repeats_per_task ) {
			// Terminate.
			return false;
		}

		if ( empty( $data[ 'id' ] ) ) {
			// Terminate.
			return false;
		}
		
		////////////////////////////////////////////////
		// Step 0: Fetch deployment.
		////////////////////////////////////////////////
		$deployment_id = $data['id'];
		if ( empty( $data[ 'step' ] ) ) {
			$data[ 'step' ] = '1';
			WC_PRL()->log( sprintf( 'Generating recommendations for deployment `%d`.', $deployment_id ), 'info', self::LOG_GROUP_NAME );
		}

		$force      = isset( $data[ 'force' ] ) ? $data[ 'force' ] : false;
		$deployment = new WC_PRL_Deployment( $deployment_id );

		if ( $deployment->get_id() ) {

			// Engine instance.
			$engine = new WC_PRL_Engine( $deployment->get_engine_id() );
			if ( ! $engine ) {
				WC_PRL()->log( 'Engine not found for deployment `#' . $deployment_id . '`. Moving on...', 'info', self::LOG_GROUP_NAME );
				return $this->next_deployment( $data );
			}

			// Set deployment source.
			$deployment->set_contextual_engine_state( $engine->has_contextual_filters() || $engine->has_contextual_amplifiers() );
			$deployment->set_source_data( $data[ 'source' ] );

			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::log( sprintf( 'Step: %d - Generating for deployment #%d and source data: %s.', empty($data['step']) ? 1 : $data['step'], $deployment->get_id(), $deployment->get_source_data_string() ) );
			}


			// Check for valid cache data.
			$cached_products = $deployment->data->get_meta( $deployment->get_cache_key() );
			if ( ! empty( $cached_products ) ) {
				if ( ! $force && time() < absint( $cached_products[ 'created_at' ] ) + $engine->refresh_interval_in_seconds ) {
					if ( wc_prl_debug_enabled() ) {
						WC_PRL()->log( 'Newly created deployment. Moving on...', 'info', self::LOG_GROUP_NAME );
					}
					
					// Terminate.
					return false;
				}
			}

			switch ( $data[ 'step' ] ) {

				////////////////////////////////////////////////
				// Step 1: Filter arguments.
				////////////////////////////////////////////////
				case '1':
					if ( wc_prl_debug_enabled() ) {
						WC_PRL()->log( sprintf( 'Filtering deployment `#%d`.', $deployment_id ), 'info', self::LOG_GROUP_NAME );
					}

					if ( empty( $data[ 'filtered_query_args' ] ) ) {

						$data[ 'filtered_query_args' ] = $engine->get_filtered_args( $deployment );
						$data[ 'amplifiers' ]          = $engine->get_amplifiers_data();
						$data[ 'step' ]                = '2';

						if ( empty( $data[ 'amplifiers' ] ) ) {
							$data[ 'step' ] = '3';
						}
					}

					// Silent Fail, proceed to step 3.
					if ( isset( $data[ 'filtered_query_args' ][ 'force_empty_set' ] ) ) {
						$data[ 'step' ] = '3';
					}

					break;

				////////////////////////////////////////////////
				// Step 2: Loop $data[ 'amplifiers' ].
				////////////////////////////////////////////////
				case '2':
					// Is current amp set to be multistep?
					if ( isset( $data[ 'current_amp_data' ] ) ) {

						$amp = WC_PRL()->amplifiers->get_amplifier( $data[ 'current_amp_data' ][ 'id' ] );

						if ( ! isset( $data[ 'current_amp_substep' ] ) ) {
							$data[ 'current_amp_substep' ] = 1;
						}

						if ( ! isset( $data[ 'current_amp_substep_return' ] ) ) {
							$data[ 'current_amp_substep_return' ] = array();
						}

						// Run substep.
						$substep_return = $amp->run_step( $data[ 'current_amp_substep' ], $deployment, $data[ 'current_amp_substep_return' ] );

						if ( is_null( $substep_return ) ) {
							// Force an empty set on this amp only.
							$data[ 'results' ][ $amp->get_id() ][ 'products' ] = array();
							$data[ 'results' ][ $amp->get_id() ][ 'weight' ]   = isset( $data[ 'current_amp_data' ][ 'weight' ] ) ? absint( $data[ 'current_amp_data' ][ 'weight' ] ) : 1;
							$this->next_amplifier( $data );
							break;
						}

						// Save substep return value...
						if ( ! is_object( $substep_return ) || ( is_array( $substep_return ) && ! is_object( $substep_return[ 0 ] ) ) ) {
							$data[ 'current_amp_substep_return' ][ (int) $data[ 'current_amp_substep' ] ] = $substep_return;
						}

						if ( $data[ 'current_amp_substep' ] == $amp->get_steps_count() ) {

							// Before applying filters make sure that the last step has limited the products that need to be included.
							// Last step should always return a set of products to be included in filters.
							if ( ! empty( $data[ 'current_amp_substep_return' ][ $amp->get_steps_count() ] ) ) {

								$products_from_amp = $data[ 'current_amp_substep_return' ][ $amp->get_steps_count() ];

								// If set exclude array_diff it.
								if ( ! empty( $data[ 'filtered_query_args' ][ 'exclude' ] ) ) {
									$products_from_amp = array_diff( $products_from_amp, $data[ 'filtered_query_args' ][ 'exclude' ] );
								}

								// If set include array_intersect it.
								if ( ! empty( $data[ 'filtered_query_args' ][ 'include' ] ) ) {
									$data[ 'filtered_query_args' ][ 'include' ] = array_intersect( $products_from_amp, $data[ 'filtered_query_args' ][ 'include' ] );

									// Intersection emptied the include array?
									if ( empty( $data[ 'filtered_query_args' ][ 'include' ] ) ) {
										// Force an empty set on this amp only.
										$data[ 'results' ][ $amp->get_id() ][ 'products' ] = array();
										$data[ 'results' ][ $amp->get_id() ][ 'weight' ]   = isset( $data[ 'current_amp_data' ][ 'weight' ] ) ? absint( $data[ 'current_amp_data' ][ 'weight' ] ) : 1;
										// Move on to the next amp...
										$this->next_amplifier( $data );
										break;
									}

								} else {
									$data[ 'filtered_query_args' ][ 'include' ] = $products_from_amp;
								}

								// Finish it off...
								$amp_args = $amp->amplify( $data[ 'filtered_query_args' ], $deployment, $data[ 'current_amp_data' ] );

								// Do the query.
								$data[ 'results' ][ $amp->get_id() ][ 'products' ] = $amp->query( $amp_args );
								$data[ 'results' ][ $amp->get_id() ][ 'weight' ]   = isset( $data[ 'current_amp_data' ][ 'weight' ] ) ? absint( $data[ 'current_amp_data' ][ 'weight' ] ) : 1;

							} else {
								// Force an empty set on this amp only.
								$data[ 'results' ][ $amp->get_id() ][ 'products' ] = array();
								$data[ 'results' ][ $amp->get_id() ][ 'weight' ]   = isset( $data[ 'current_amp_data' ][ 'weight' ] ) ? absint( $data[ 'current_amp_data' ][ 'weight' ] ) : 1;
							}

							// Move on to the next amp...
							$this->next_amplifier( $data );
						} else {

							// Next step...
							$data[ 'current_amp_substep' ]++;
							break;
						}
					}

					if ( ! empty( $data[ 'amplifiers' ] ) ) {

						$amp_data = array_pop( $data[ 'amplifiers' ] );
						$amp      = WC_PRL()->amplifiers->get_amplifier( $amp_data[ 'id' ] );

						if ( $amp ) {

							if ( 1 < $amp->get_steps_count() && ! isset( $data[ 'current_amp_data' ] ) ) {
								// Mark the amp request as `current` and re-run to start calculating multisteps.
								$data[ 'current_amp_data' ] = $amp_data;
								if ( wc_prl_debug_enabled() ) {
									WC_PRL()->log( sprintf( 'Marking amplifier `%s` as multistep.', $amp_data[ 'id' ] ), 'info', self::LOG_GROUP_NAME );
								}
								break;
							}

							if ( wc_prl_debug_enabled() ) {
								WC_PRL()->log( sprintf( 'Generating amplifier `%s` for deployment `#%d`.', $amp_data[ 'id' ], $deployment_id ), 'info', self::LOG_GROUP_NAME );
							}

							$amp_args = $amp->amplify( $data[ 'filtered_query_args' ], $deployment, $amp_data );

							// Do the query.
							$data[ 'results' ][ $amp->get_id() ][ 'products' ] = $amp->query( $amp_args );
							$data[ 'results' ][ $amp->get_id() ][ 'weight' ]   = isset( $amp_data[ 'weight' ] ) ? absint( $amp_data[ 'weight' ] ) : 1;
						}
					}

					// If no more, continue.
					if ( empty( $data[ 'amplifiers' ] ) ) {
						$data[ 'step' ] = '3';
					}

					break;

				////////////////////////////////////////////////
				// Step 3: Combine results and save in cache.
				////////////////////////////////////////////////
				case '3':
					// Engine results.
					$products = array();

					if ( isset( $data[ 'filtered_query_args' ][ 'force_empty_set' ] ) ) {
						// Silent fail, write empty products.
						$products = array();
					} elseif ( empty( $data[ 'results' ] ) ) {
						// By default the "None" amplifier.
						$products = $engine->query( $data[ 'filtered_query_args' ] );
					} elseif ( 1 === count( $data[ 'results' ] ) ) {
						// No need for weight merging here.
						$single_results = array_pop( $data[ 'results' ] );
						$products       = $single_results[ 'products' ];
					} else {
						// Do a weight merge.
						$products = $engine->weight_merge( $data[ 'results' ], $engine->sampling_max_index );
					}

					// Limit products before saving.
					$limit    = min( $engine->caching_max_index, $engine->sampling_max_index );
					$products = array_slice( $products, 0, $limit );

					// Add to cache.
					$cache = array(
						'products'   => $products,
						'created_at' => time(),
						'expired'    => false
					);

					$deployment->data->update_meta( $deployment->get_cache_key(), $cache );
					$deployment->data->save();

					if ( wc_prl_debug_enabled() ) {
						WC_PRL()->log( sprintf( 'Combine and save for deployment `#%d` products `%s` in `%s` meta key.', $deployment_id, print_r( $products, true ), $deployment->get_cache_key() ), 'info', self::LOG_GROUP_NAME );
					}

					/**
					 * `woocommerce_prl_deployment_generation` hook.
					 *
					 * Used for third parties to handle deployment regenerations.
					 *
					 * @since 1.4.15
					 *
					 * @param array  $value
					 * @param int    $deployment_id
					 */
					do_action( 'woocommerce_prl_deployment_generation', $cache, $deployment );

					////////////////////////////////////////////////
					// Terminate.
					////////////////////////////////////////////////
					return false;
			}
		}

		////////////////////////////////////////////////
		// Continue.
		////////////////////////////////////////////////
		return $data;
	}
}
