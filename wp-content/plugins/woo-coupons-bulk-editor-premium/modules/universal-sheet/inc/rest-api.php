<?php

// Exit early if the rest api is not enabled
$wpse_options = get_option( 'vg_sheet_editor' );
if ( empty( $wpse_options['be_enable_rest_api'] ) ) {
	return;
}
// If the wp rest api is not available, exit
if ( ! class_exists( 'WP_REST_Controller' ) ) {
	return;
}

if ( ! class_exists( 'WPSE_REST_API' ) ) {

	class WPSE_REST_API extends WP_REST_Controller {

		public function _validate_string( $param, $request, $key ) {
			return is_string( $param ) && ! empty( $param );
		}

		public function _validate_int( $param, $request, $key ) {
			return intval( $param ) && ! empty( $param );
		}
		public function _validate_json_string( $param, $request, $key ) {
			return is_string( $param ) && strpos($param, '{') === 0;
		}

		public static function get_route_namespace() {
			$version   = '1';
			$namespace = 'sheet-editor/v' . $version;
			return $namespace;
		}

		/**
		 * Register the routes for the objects of the controller.
		 */
		public function register_routes() {
			$namespace = self::get_route_namespace();
			register_rest_route(
				$namespace,
				'/settings',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_settings' ),
						'permission_callback' => array( $this, 'get_general_settings_permissions_check' ),
					),
				)
			);
			register_rest_route(
				$namespace,
				'/sheet/settings',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_sheet_settings' ),
						'permission_callback' => array( $this, 'get_items_permissions_check' ),
						'args'                => array(
							'sheet_key' => array(
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => array( $this, '_validate_string' ),
								'required'          => true,
							),
						),
					),
				)
			);
			$load_rows_args = array(
				'sheet_key'                 => array(
					'type'              => 'string',
					'sanitize_callback' => function ( $param ) {
						return VGSE()->helpers->sanitize_table_key( $param );
					},
					'validate_callback' => array( $this, '_validate_string' ),
					'required'          => true,
				),
				'page'                      => array(
					'type'     => 'integer',
					'required' => true,
					'minimum'  => 1,
				),
				'custom_enabled_columns'    => array(
					'sanitize_callback' => 'sanitize_text_field',
					'type'              => 'string',
					'required'          => false,
				),
				'wpse_source_suffix'        => array(
					'sanitize_callback' => 'sanitize_text_field',
					'type'              => 'string',
					'required'          => false,
				),
				'wpse_source'               => array(
					'sanitize_callback' => 'sanitize_text_field',
					'type'              => 'string',
					'required'          => false,
				),
				'filters'                   => array(
					'validate_callback' => array( $this, '_validate_json_string'),
					'sanitize_callback' => 'wp_kses_post',
					'type'              => 'string',
					'required'          => false,
				),
				'wpse_job_id'               => array(
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => array( $this, '_validate_string' ),
					'required'          => false,
				),
				'wpse_reset_posts_per_page' => array(
					'type'     => 'boolean',
					'required' => false,
				),
				'posts_per_page'            => array(
					'required' => false,
					'type'     => 'integer',
					'minimum'  => 1,
					'maximum'  => ! empty( VGSE()->options['be_posts_per_page'] ) ? (int) VGSE()->options['be_posts_per_page'] : 20,
				),
			);
			register_rest_route(
				$namespace,
				'/sheet/rows',
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_sheet_rows' ),
						'permission_callback' => array( $this, 'get_items_permissions_check' ),
						'args'                => $load_rows_args,
					),
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'update_sheet_rows' ),
						'permission_callback' => array( $this, 'update_items_permissions_check' ),
						'args'                => array(
							'sheet_key'           => array(
								'type'              => 'string',
								'sanitize_callback' => function ( $param ) {
									return VGSE()->helpers->sanitize_table_key( $param );
								},
								'validate_callback' => array( $this, '_validate_string' ),
								'required'          => true,
							),
							'data'                => array(
								'type'     => 'array',
								'minItems' => 1,
								'required' => true,
								'items'    => array(
									'type' => 'object',
								),
							),
							'allow_to_create_new' => array(
								'type'     => 'boolean',
								'required' => false,
							),
							'wpse_source'         => array(
								'sanitize_callback' => 'sanitize_text_field',
								'type'              => 'string',
								'required'          => false,
							),
							'filters'             => array(
								'validate_callback' => array( $this, '_validate_json_string'),
								'sanitize_callback' => 'wp_kses_post',
								'type'              => 'string',
								'required'          => false,
							),

						),
					),
				)
			);

			register_rest_route(
				$namespace,
				'/sheet/export-rows',
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'get_rows_for_export' ),
						'permission_callback' => array( $this, 'get_items_permissions_check' ),
						'args'                => apply_filters(
							'vg_sheet_editor/rest/export_rows_args',
							array_merge(
								$load_rows_args,
								array(
									'posts_per_page'      => array(
										'required' => false,
										'type'     => 'integer',
										'minimum'  => 1,
										'maximum'  => ! empty( VGSE()->options['export_page_size'] ) ? (int) VGSE()->options['export_page_size'] : 100,
									),
									'wpse_job_id'         => array(
										'sanitize_callback' => 'sanitize_text_field',
										'validate_callback' => array( $this, '_validate_string' ),
										'required' => true,
										'type'     => 'string',
									),
									'custom_enabled_columns' => array(
										'sanitize_callback' => 'sanitize_text_field',
										'type'     => 'string',
										'required' => true,
									),
									'line_items_separate_rows' => array(
										'type'     => 'boolean',
										'required' => true,
									),
									'add_excel_separator_flag' => array(
										'type'     => 'boolean',
										'required' => true,
									),
									'save_for_later_name' => array(
										'sanitize_callback' => 'sanitize_text_field',
										'type'     => 'string',
										'required' => true,
									),
								)
							)
						),
					),
				)
			);
			register_rest_route(
				$namespace,
				'/sheet/bulk-edit',
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'execute_bulk_edit' ),
						'permission_callback' => array( $this, 'update_items_permissions_check' ),
						'args'                => array(
							'column'               => array(
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => array( $this, '_validate_string' ),
								'required'          => true,
								'type'              => 'string',
							),
							'formula'              => array(
								'sanitize_callback' => array( $this, 'sanitize_bulk_edit_formula' ),
								'validate_callback' => array( $this, '_validate_string' ),
								'required'          => true,
								'type'              => 'string',
							),
							'formula_data'         => array(
								'type'     => 'array',
								'required' => true,
								'items'    => array(
									'type' => 'string',
								),
							),
							'sheet_key'            => array(
								'type'              => 'string',
								'sanitize_callback' => function ( $param ) {
									return VGSE()->helpers->sanitize_table_key( $param );
								},
								'validate_callback' => array( $this, '_validate_string' ),
								'required'          => true,
							),
							'page'                 => array(
								'type'     => 'integer',
								'minimum'  => 1,
								'required' => true,
							),
							'wpse_job_id'          => array(
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => array( $this, '_validate_string' ),
								'required'          => true,
								'type'              => 'string',
							),
							// @todo Use enum to only accept registered actions
							'action_name'          => array(
								'sanitize_callback' => array( $this, 'sanitize_bulk_edit_formula' ),
								'validate_callback' => array( $this, '_validate_string' ),
								'required'          => true,
								'type'              => 'string',
							),
							'filters'              => array(
								'validate_callback' => array( $this, '_validate_json_string'),
								'sanitize_callback' => 'wp_kses_post',
								'type'              => 'string',
								'required'          => false,
							),
							'use_slower_execution' => array(
								'type'     => 'boolean',
								'required' => false,
							),
						),
					),
				)
			);
			register_rest_route(
				$namespace,
				'/sheet/import-rows',
				array(
					array(
						'methods'             => WP_REST_Server::CREATABLE,
						'callback'            => array( $this, 'import_rows' ),
						'permission_callback' => array( $this, 'update_items_permissions_check' ),
						'args'                => array(
							'sheet_key'                    => array(
								'type'              => 'string',
								'sanitize_callback' => function ( $param ) {
									return VGSE()->helpers->sanitize_table_key( $param );
								},
								'validate_callback' => array( $this, '_validate_string' ),
								'required'          => true,
							),
							'wpse_job_id'                  => array(
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => array( $this, '_validate_string' ),
								'required'          => true,
							),
							'page'                         => array(
								'type'     => 'integer',
								'minimum'  => 1,
								'required' => true,
							),
							'total_rows'                   => array(
								'type'     => 'integer',
								'minimum'  => 1,
								'required' => true,
							),
							'sheet_editor_column'          => array(
								'type'     => 'array',
								'minItems' => 1,
								'required' => true,
								'items'    => array(
									'type' => 'string',
								),
							),
							'source_column'                => array(
								'type'     => 'array',
								'minItems' => 1,
								'required' => true,
								'items'    => array(
									'type' => 'string',
								),
							),
							'writing_type'                 => array(
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => array( $this, '_validate_string' ),
								'required'          => true,
								'enum'              => array( 'both', 'all_new', 'only_new', 'only_update' ),
							),
							'import_type'                  => array(
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => array( $this, '_validate_string' ),
								'required'          => true,
								'enum'              => array( 'csv', 'json' ),
							),
							'per_page'                     => array(
								'type'     => 'integer',
								'minimum'  => 1,
								'maximum'  => ! empty( VGSE()->options['be_posts_per_page_save'] ) ? (int) VGSE()->options['be_posts_per_page_save'] : 10,
								'required' => false,
							),
							'start_row'                    => array(
								'type'     => 'integer',
								'minimum'  => 0,
								'required' => false,
							),
							'decode_quotes'                => array(
								'type'     => 'boolean',
								'required' => false,
							),
							'remember_column_mapping'      => array(
								'type'     => 'boolean',
								'required' => false,
							),
							'pending_post_if_image_failed' => array(
								'type'     => 'boolean',
								'required' => false,
							),
							'skip_broken_images'           => array(
								'type'     => 'boolean',
								'required' => false,
							),
							'separator'                    => array(
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => array( $this, '_validate_string' ),
							),
							'existing_check_csv_field'     => array(
								'type'     => 'array',
								'minItems' => 1,
								'required' => false,
								'items'    => array(
									'type' => 'string',
								),
							),
							'existing_check_wp_field'      => array(
								'type'     => 'array',
								'minItems' => 1,
								'required' => false,
								'items'    => array(
									'type' => 'string',
								),
							),
							'import_file'                  => array(
								'type'              => 'string',
								'sanitize_callback' => 'sanitize_text_field',
								'validate_callback' => array( $this, '_validate_string' ),
								'required'          => true,
							),
							'file_position'                => array(
								'type'     => 'integer',
								'minimum'  => 0,
								'required' => true,
							),
							'wpse_source_suffix'           => array(
								'sanitize_callback' => 'sanitize_text_field',
								'type'              => 'string',
								'required'          => false,
							),
						),
					),
				)
			);
		}

		public function sanitize_bulk_edit_formula( $value, $request, $param ) {
			$value = strpos( $request['formula'], WP_Sheet_Editor_Formulas::$regex_flag ) !== false ? strval( $request['formula'] ) : wp_kses_post( wp_unslash( $request['formula'] ) );
			return $value;
		}

		public function execute_bulk_edit( $request ) {
			$settings = $request->get_params();
			$params   = array(
				'column'        => $settings['column'],
				'formula'       => $settings['formula'],
				'post_type'     => $settings['sheet_key'],
				'page'          => $settings['page'],
				'wpse_job_id'   => $settings['wpse_job_id'],
				'nonce'         => wp_create_nonce( 'bep-nonce' ),
				'raw_form_data' => array(
					'columns'              => array( $settings['column'] ),
					'formula_data'         => $settings['formula_data'],
					'action_name'          => $settings['action_name'],
					'use_slower_execution' => ! empty( $settings['use_slower_execution'] ),
				),
				'filters'       => vgse_filters_init()->_get_raw_filters( isset( $settings['filters'] ) ? $settings['filters'] : '' ),
			);
			if ( isset( $settings['filters'] ) ) {
				$_REQUEST['filters'] = $settings['filters'];
			}
			// Important. $_REQUEST['sheet_key'] is used in many places to detect the current sheet and used in many modules
			$_REQUEST['sheet_key'] = $settings['sheet_key'];

			$out = vgse_formulas_init()->bulk_execute_formula( $params );

			if ( is_wp_error( $out ) ) {
				return new WP_Error( 'wpse', $out->get_error_message(), array( 'status' => 400 ) );
			}

			return $out;
		}
		public function import_rows( $request ) {

			$settings = $request->get_params();
			$params   = array(
				'nonce'                        => wp_create_nonce( 'bep-nonce' ),
				'post_type'                    => $settings['sheet_key'],
				'page'                         => $settings['page'],
				'sheet_editor_column'          => $settings['sheet_editor_column'],
				'source_column'                => $settings['source_column'],
				'writing_type'                 => $settings['writing_type'],
				'import_type'                  => $settings['import_type'],
				'total_rows'                   => $settings['total_rows'],
				'wpse_job_id'                  => $settings['wpse_job_id'],
				'import_file'                  => wp_unslash( $settings['import_file'] ),
				'file_position'                => $settings['file_position'],
				'vgse_plain_mode'              => 'yes',
				'vgse_import'                  => 'yes',
				'wpse_source_suffix'           => isset( $settings['wpse_source_suffix'] ) ? $settings['wpse_source_suffix'] : '',
				'per_page'                     => ! empty( $settings['per_page'] ) ? $settings['per_page'] : 0,
				'start_row'                    => ! empty( $settings['start_row'] ) ? $settings['start_row'] : 0,
				'decode_quotes'                => ! empty( $settings['decode_quotes'] ),
				'remember_column_mapping'      => ! empty( $settings['remember_column_mapping'] ),
				'pending_post_if_image_failed' => ! empty( $settings['pending_post_if_image_failed'] ),
				'skip_broken_images'           => ! empty( $settings['skip_broken_images'] ),
				'separator'                    => ! empty( $settings['separator'] ) ? $settings['separator'] : '',
				'existing_check_csv_field'     => isset( $settings['existing_check_csv_field'] ) ? $settings['existing_check_csv_field'] : array(),
				'existing_check_wp_field'      => isset( $settings['existing_check_wp_field'] ) ? $settings['existing_check_wp_field'] : array(),
			);
			// Important. $_REQUEST['sheet_key'] is used in many places to detect the current sheet and used in many modules
			$_REQUEST['sheet_key'] = $settings['sheet_key'];

			$out = WPSE_CSV_API_Obj()->import_data( $params );

			if ( is_wp_error( $out ) ) {
				return new WP_Error( 'wpse', $out->get_error_message(), array( 'status' => 400 ) );
			}

			return $out;
		}

		public function update_sheet_rows( $request ) {

			$settings = $request->get_params();
			$params   = array(
				'nonce'               => wp_create_nonce( 'bep-nonce' ),
				'post_type'           => $settings['sheet_key'],
				'data'                => VGSE()->helpers->sanitize_data_for_db( $settings['data'], $settings['sheet_key'] ),
				'allow_to_create_new' => ! empty( $settings['allow_to_create_new'] ),
				'wpse_source'         => isset( $settings['wpse_source'] ) ? $settings['wpse_source'] : null,
				'filters'             => vgse_filters_init()->_get_raw_filters( isset( $settings['filters'] ) ? $settings['filters'] : '' ),
			);
			if ( isset( $settings['filters'] ) ) {
				$_REQUEST['filters'] = $settings['filters'];
			}
			// Important. $_REQUEST['sheet_key'] is used in many places to detect the current sheet and used in many modules
			$_REQUEST['sheet_key'] = $settings['sheet_key'];

			$result = VGSE()->helpers->save_rows( $params );

			if ( is_wp_error( $result ) ) {
				return new WP_Error( 'wpse', $result->get_error_message(), array( 'status' => 400 ) );
			}

			$out = array(
				'message' => __( 'Changes saved successfully', 'vg_sheet_editor' ),
				'deleted' => array_unique( VGSE()->deleted_rows_ids ),
			);
			return $out;
		}

		public function _get_rows_params( $settings ) {

			$request_data = array(
				'nonce'              => wp_create_nonce( 'bep-nonce' ),
				'post_type'          => $settings['sheet_key'],
				'paged'              => $settings['page'],
				'posts_per_page'     => isset( $settings['posts_per_page'] ) ? $settings['posts_per_page'] : 0,
				'wpse_source_suffix' => isset( $settings['wpse_source_suffix'] ) ? $settings['wpse_source_suffix'] : '',
				'wpse_source'        => isset( $settings['wpse_source'] ) ? $settings['wpse_source'] : '',
				'filters'            => vgse_filters_init()->_get_raw_filters( isset( $settings['filters'] ) ? $settings['filters'] : '' ),
			);
			if ( isset( $settings['filters'] ) ) {
				$_REQUEST['filters'] = $settings['filters'];
			}
			if ( isset( $settings['custom_enabled_columns'] ) ) {
				$_REQUEST['custom_enabled_columns']     = $settings['custom_enabled_columns'];
				$request_data['custom_enabled_columns'] = $settings['custom_enabled_columns'];
			}
			if ( isset( $settings['wpse_job_id'] ) ) {
				$_REQUEST['wpse_job_id']     = $settings['wpse_job_id'];
				$request_data['wpse_job_id'] = $settings['wpse_job_id'];
			}
			// Important. $_REQUEST['sheet_key'] is used in many places to detect the current sheet and used in many modules
			$_REQUEST['sheet_key'] = $settings['sheet_key'];

			// Reset the number of rows per page, we receive this parameter from the client when
			// the current rows per page > 300 and the request failed
			if ( ! empty( $settings['wpse_reset_posts_per_page'] ) ) {
				VGSE()->update_option('be_posts_per_page', 10);
			}

			$source_prefix               = ( ! empty( $request_data['wpse_source_suffix'] ) ) ? (string) $request_data['wpse_source_suffix'] : '';
			$request_data['wpse_source'] = 'load_rows' . $source_prefix;
			return $request_data;
		}
		public function get_rows_for_export( $request ) {

			$settings     = $request->get_params();
			$request_data = $this->_get_rows_params( $settings );

			$request_data['vgse_csv_export'] = 'yes';
			if ( empty( $request_data['wpse_source'] ) ) {
				$request_data['wpse_source'] = 'rest_export';
			}
			$request_data['wpse_job_id']              = $settings['wpse_job_id'];
			$request_data['line_items_separate_rows'] = ! empty( $settings['line_items_separate_rows'] );
			$request_data['add_excel_separator_flag'] = ! empty( $settings['add_excel_separator_flag'] );
			if ( ! empty( $settings['save_for_later_name'] ) && VGSE()->helpers->user_can_manage_options() ) {
				$request_data['save_for_later'] = array(
					'name'                     => $settings['save_for_later_name'],
					'columns'                  => $request_data['custom_enabled_columns'],
					'add_excel_separator_flag' => $request_data['add_excel_separator_flag'],
					'filters'                  => $request_data['filters'],
					'post_type'                => $request_data['post_type'],
				);
			}
			$_REQUEST['vgse_plain_mode'] = 'yes';

			$early_response = apply_filters( 'vg_sheet_editor/rest/export_rows_early_response', null, $request_data, $request );
			if ( ! is_null( $early_response ) ) {
				return $early_response;
			}

			$rows = VGSE()->helpers->get_rows( $request_data );

			if ( is_wp_error( $rows ) ) {
				return new WP_Error( 'wpse', $rows->get_error_message(), array( 'status' => 400 ) );
			}

			$rows['rows'] = array_values( $rows['rows'] );
			return $rows;
		}
		public function get_sheet_rows( $request ) {

			$settings     = $request->get_params();
			$request_data = $this->_get_rows_params( $settings );
			$rows         = VGSE()->helpers->get_rows( $request_data );

			if ( is_wp_error( $rows ) ) {
				return new WP_Error( 'wpse', $rows->get_error_message(), array( 'status' => 400 ) );
			}

			$rows['rows'] = array_values( $rows['rows'] );
			return $rows;
		}

		/**
		 * Get sheet settings
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_sheet_settings( $request ) {
			// Important. $_REQUEST['sheet_key'] is used in many places to detect the current sheet and used in many modules
			$_REQUEST['sheet_key'] = $request['sheet_key'];
			$editor                = VGSE()->helpers->get_provider_editor( $request['sheet_key'] );
			$out                   = $editor->get_editor_settings( $request['sheet_key'] );

			if ( function_exists( 'vgse_universal_sheet' ) ) {
				$out['export_columns'] = wp_list_pluck( vgse_universal_sheet()->get_export_options( $request['sheet_key'] ), 'title', 'key' );
				$out['import_columns'] = wp_list_pluck( vgse_universal_sheet()->get_import_options( $request['sheet_key'] ), 'title', 'key' );
			}

			return $out;
		}

		/**
		 * Get sheet settings
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_settings( $request ) {

			$enabled_post_types = VGSE()->helpers->get_enabled_post_types();
			$user               = get_userdata( get_current_user_id() );
			$sheets             = VGSE()->helpers->get_prepared_post_types();
			if ( ! WP_Sheet_Editor_Helpers::current_user_can( 'activate_plugins' ) ) {
				foreach ( $sheets as $index => $sheet ) {
					if ( ! in_array( $sheet['key'], $enabled_post_types, true ) ) {
						unset( $sheets[ $index ] );
					}
				}
			}
			$out = array(
				'sheets'                            => $sheets,
				'active_sheets'                     => $enabled_post_types,
				'rest_base'                         => rest_url(),
				'current_user_id'                   => get_current_user_id(),
				'current_user_email'                => $user->user_email,
				'current_user_first_name'           => $user->first_name,
				'current_user_last_name'            => $user->last_name,
				'current_user_role'                 => current( $user->roles ),
				'woocommerce_product_post_type_key' => apply_filters( 'vg_sheet_editor/woocommerce/product_post_type_key', 'product' ),
			);

			return $out;
		}

		/**
		 * Check if the user is logged in and has permissions to edit at least one spreadsheet
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_Error|bool
		 */
		public function get_general_settings_permissions_check( $request ) {
			return is_user_logged_in() && VGSE()->helpers->get_enabled_post_types();
		}

		public function get_items_permissions_check( $request ) {
			return is_user_logged_in() && VGSE()->helpers->user_can_view_post_type( $request['sheet_key'] ) || wp_doing_cron();
		}

		public function update_items_permissions_check( $request ) {
			return is_user_logged_in() && VGSE()->helpers->user_can_edit_post_type( $request['sheet_key'] ) || wp_doing_cron();
		}

		public function register_hooks() {
			add_filter( 'vg_sheet_editor/allowed_on_frontend', array( $this, 'allow_core_on_frontend' ) );
			add_filter( 'rest_authentication_errors', array( $this, 'init_wpse_after_rest_authentication' ), 9999 );
		}

		public function init_wpse_after_rest_authentication( $result ) {
			if ( is_user_logged_in() ) {
				vgse_init();
			}
			return $result;
		}

		public function allow_core_on_frontend( $allow ) {
			global $wp;
			if ( strpos( home_url( $wp->request ), rest_url() ) === 0 ) {
				$allow = true;
			}
			return $allow;
		}

	}

}

if ( ! function_exists( 'wpse_init_rest_api' ) ) {
	//Load this only for REST API requests
	add_action( 'rest_api_init', 'wpse_init_rest_api' );

	function wpse_init_rest_api() {
		$GLOBALS['wpse_rest_api'] = new WPSE_REST_API();
		$GLOBALS['wpse_rest_api']->register_hooks();
		$GLOBALS['wpse_rest_api']->register_routes();

		return $GLOBALS['wpse_rest_api'];
	}
}
