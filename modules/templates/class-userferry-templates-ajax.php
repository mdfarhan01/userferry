<?php
/**
 * AJAX controller for export templates.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Templates_Ajax' ) ) {

	/**
	 * Class UserFerry_Templates_Ajax
	 */
	class UserFerry_Templates_Ajax {

		use UserFerry_Ajax_Guard;

		/**
		 * Action => method map.
		 *
		 * @return array
		 */
		public static function actions() {
			return array(
				'userferry_template_get'       => 'get',
				'userferry_template_save'      => 'save',
				'userferry_template_delete'    => 'delete',
				'userferry_template_duplicate' => 'duplicate',
			);
		}

		/**
		 * Get one template.
		 */
		public function get() {
			$this->guard();
			$template = UserFerry_Templates::get( $this->post_int( 'id' ) );
			if ( ! $template ) {
				$this->fail( new WP_Error( 'userferry_template_missing', __( 'That template no longer exists.', 'userferry' ) ) );
			}
			wp_send_json_success( $template );
		}

		/**
		 * Create or update a template.
		 */
		public function save() {
			$this->guard();

			$data = UserFerry_Templates::prepare(
				array(
					'id'          => $this->post_int( 'id' ),
					'name'        => $this->post_text( 'name' ),
					'description' => $this->post_textarea( 'description' ),
					'fields'      => $this->post_lines( 'fields' ),
					'filters'     => $this->post_array( 'filters' ),
					'format'      => $this->post_key( 'format' ),
					'filename'    => $this->post_text( 'filename' ),
				)
			);
			if ( is_wp_error( $data ) ) {
				$this->fail( $data );
			}

			$id = UserFerry_Templates::save( $data );
			if ( is_wp_error( $id ) ) {
				$this->fail( $id );
			}
			wp_send_json_success( UserFerry_Templates::get( $id ) );
		}

		/**
		 * Delete a template.
		 */
		public function delete() {
			$this->guard();
			UserFerry_Templates::delete( $this->post_int( 'id' ) );
			wp_send_json_success( array( 'count' => UserFerry_Templates::count() ) );
		}

		/**
		 * Duplicate a template.
		 */
		public function duplicate() {
			$this->guard();
			$id = UserFerry_Templates::duplicate( $this->post_int( 'id' ) );
			if ( is_wp_error( $id ) ) {
				$this->fail( $id );
			}
			wp_send_json_success( UserFerry_Templates::get( $id ) );
		}
	}
}
