<?php
/**
 * AJAX controller for the export module.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Export_Ajax' ) ) {

	/**
	 * Class UserFerry_Export_Ajax
	 */
	class UserFerry_Export_Ajax {

		use UserFerry_Ajax_Guard;

		/**
		 * Action => method map, registered by UserFerry_Plugin.
		 *
		 * @return array
		 */
		public static function actions() {
			return array(
				'userferry_count_users'   => 'count_users',
				'userferry_list_users'    => 'list_users',
				'userferry_scan_fields'   => 'scan_fields',
				'userferry_export_start'  => 'export_start',
				'userferry_export_step'   => 'export_step',
				'userferry_export_finish' => 'export_finish',
			);
		}

		/**
		 * Count users for a filter set.
		 */
		public function count_users() {
			$this->guard();
			$filters = UserFerry_Export_Config::sanitize_filters( $this->post_array( 'filters' ) );
			wp_send_json_success(
				array(
					'count'   => UserFerry_Export_Query::count( $filters ),
					'summary' => UserFerry_Export_Config::describe_filters( $filters ),
				)
			);
		}

		/**
		 * Paged user list for "Specific users".
		 */
		public function list_users() {
			$this->guard();

			$paged    = max( 1, $this->post_int( 'paged', 1 ) );
			$per_page = min( 100, max( 5, $this->post_int( 'per_page', 10 ) ) );
			$search   = $this->post_text( 'search' );
			$role     = $this->post_key( 'role' );

			$args = array(
				'number'      => $per_page,
				'paged'       => $paged,
				'count_total' => true,
				'orderby'     => 'display_name',
				'order'       => 'ASC',
			);
			if ( '' !== $search ) {
				$args['search']         = '*' . $search . '*';
				$args['search_columns'] = array( 'user_login', 'user_email', 'user_nicename', 'display_name' );
			}
			if ( '' !== $role ) {
				$args['role'] = $role;
			}

			$query = new WP_User_Query( $args );
			$names = wp_roles()->get_names();
			$users = array();

			foreach ( $query->get_results() as $user ) {
				$roles = array();
				foreach ( (array) $user->roles as $r ) {
					$roles[] = isset( $names[ $r ] ) ? translate_user_role( $names[ $r ] ) : $r;
				}
				$users[] = array(
					'id'    => (int) $user->ID,
					'name'  => $user->display_name,
					'login' => $user->user_login,
					'email' => $user->user_email,
					'role'  => implode( ', ', $roles ),
				);
			}

			wp_send_json_success(
				array(
					'users'    => $users,
					'total'    => (int) $query->get_total(),
					'paged'    => $paged,
					'per_page' => $per_page,
				)
			);
		}

		/**
		 * Scan the usermeta table for custom fields (bypasses the cache).
		 */
		public function scan_fields() {
			$this->guard();
			$scan               = UserFerry_Fields::scan( true );
			$scan['scanned_at'] = UserFerry_Fields::scanned_at_label();
			wp_send_json_success( $scan );
		}

		/**
		 * Build an export configuration from the request.
		 *
		 * @return array
		 */
		private function config_from_request() {
			$template_id   = $this->post_int( 'template_id' );
			$template_name = '';
			if ( $template_id ) {
				$template      = UserFerry_Templates::get( $template_id );
				$template_name = $template ? $template['name'] : '';
				$template_id   = $template ? $template_id : 0;
			}

			return array(
				'filters'       => UserFerry_Export_Config::sanitize_filters( $this->post_array( 'filters' ) ),
				'fields'        => UserFerry_Export_Config::sanitize_fields( $this->post_lines( 'fields' ) ),
				'format'        => UserFerry_Export_Config::sanitize_format( $this->post_key( 'format' ) ),
				'filename'      => UserFerry_Export_Config::sanitize_filename( $this->post_text( 'filename' ) ),
				'template_id'   => $template_id,
				'template_name' => $template_name,
			);
		}

		/**
		 * Start an export job (from the wizard, or straight from a template).
		 */
		public function export_start() {
			$this->guard();

			$template_id = $this->post_int( 'run_template' );
			if ( $template_id ) {
				$template = UserFerry_Templates::get( $template_id );
				if ( ! $template ) {
					$this->fail( new WP_Error( 'userferry_template_missing', __( 'That template no longer exists.', 'userferry' ) ) );
				}
				$config = UserFerry_Templates::to_config( $template );
			} else {
				$config = $this->config_from_request();
			}

			$this->respond( UserFerry_Export_Job::start( $config ) );
		}

		/**
		 * Process one batch.
		 */
		public function export_step() {
			$this->guard();
			$this->respond( UserFerry_Export_Job::step( $this->post_key( 'job' ) ) );
		}

		/**
		 * Write the final file.
		 */
		public function export_finish() {
			$this->guard();
			$this->respond( UserFerry_Export_Job::finish( $this->post_key( 'job' ) ) );
		}
	}
}
