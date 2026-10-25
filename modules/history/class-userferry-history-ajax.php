<?php
/**
 * AJAX controller for the history module.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_History_Ajax' ) ) {

	/**
	 * Class UserFerry_History_Ajax
	 */
	class UserFerry_History_Ajax {

		use UserFerry_Ajax_Guard;

		/**
		 * Action => method map.
		 *
		 * @return array
		 */
		public static function actions() {
			return array(
				'userferry_history_delete' => 'delete',
				'userferry_history_clear'  => 'clear',
			);
		}

		/**
		 * Delete one history entry and its file.
		 */
		public function delete() {
			$this->guard( 'manage_options' );
			UserFerry_History::delete( $this->post_int( 'id' ) );
			wp_send_json_success();
		}

		/**
		 * Delete all history entries and files.
		 */
		public function clear() {
			$this->guard( 'manage_options' );
			UserFerry_History::clear();
			wp_send_json_success();
		}
	}
}
