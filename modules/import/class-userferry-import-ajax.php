<?php
/**
 * AJAX controller for the import module.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Import_Ajax' ) ) {

	/**
	 * Class UserFerry_Import_Ajax
	 */
	class UserFerry_Import_Ajax {

		use UserFerry_Ajax_Guard;

		/**
		 * Action => method map.
		 *
		 * @return array
		 */
		public static function actions() {
			return array(
				'userferry_import_upload'    => 'upload',
				'userferry_import_configure' => 'configure',
				'userferry_import_step'      => 'step',
			);
		}

		/**
		 * Capabilities needed to import users.
		 *
		 * @return string[]
		 */
		private function caps() {
			return array( 'create_users', 'edit_users' );
		}

		/**
		 * Receive the file, parse it and suggest a column mapping.
		 * The uploaded file itself is never stored — only its parsed rows,
		 * in the plugin's protected folder, until the import finishes.
		 */
		public function upload() {
			$this->guard( $this->caps() );

			if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'userferry_nonce' ) ) {
				$this->fail( new WP_Error( 'userferry_nonce', __( 'Your session has expired. Reload the page and try again.', 'userferry' ) ) );
			}
			if ( ! isset( $_FILES['file']['tmp_name'], $_FILES['file']['name'], $_FILES['file']['error'], $_FILES['file']['size'] ) ) {
				$this->fail( new WP_Error( 'userferry_import_nofile', __( 'Choose a file to import.', 'userferry' ) ) );
			}

			$tmp_name = sanitize_text_field( $_FILES['file']['tmp_name'] );
			$name     = sanitize_file_name( wp_unslash( $_FILES['file']['name'] ) );
			$error    = absint( $_FILES['file']['error'] );
			$size     = absint( $_FILES['file']['size'] );

			if ( UPLOAD_ERR_OK !== $error || '' === $tmp_name || ! is_uploaded_file( $tmp_name ) ) {
				$this->fail( new WP_Error( 'userferry_import_upload', __( 'The file did not upload correctly. It may be larger than the server allows.', 'userferry' ) ) );
			}
			if ( $size > wp_max_upload_size() ) {
				/* translators: %s: maximum upload size */
				$this->fail( new WP_Error( 'userferry_import_size', sprintf( __( 'That file is larger than the %s upload limit.', 'userferry' ), size_format( wp_max_upload_size() ) ) ) );
			}

			$this->respond( UserFerry_Import_Job::start( $tmp_name, $name ) );
		}
		/**
		 * Save mapping + options.
		 */
		public function configure() {
			$this->guard( $this->caps() );

			// Mapping arrives as a list of { column, target } pairs so that any column
			// name (spaces, dots, brackets) survives PHP's request parsing intact.
			$mapping = array();
			foreach ( $this->post_array( 'mapping' ) as $pair ) {
				if ( is_array( $pair ) && isset( $pair['column'], $pair['target'] ) && is_string( $pair['column'] ) && is_string( $pair['target'] ) ) {
					$mapping[ $pair['column'] ] = $pair['target'];
				}
			}
			$options = $this->post_array( 'options' );

			$this->respond( UserFerry_Import_Job::configure( $this->post_key( 'job' ), $mapping, $options ) );
		}

		/**
		 * Process one batch of rows.
		 */
		public function step() {
			$this->guard( $this->caps() );
			$this->respond( UserFerry_Import_Job::step( $this->post_key( 'job' ) ) );
		}
	}
}
