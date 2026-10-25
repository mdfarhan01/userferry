<?php
/**
 * Export history.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery -- Custom table owned by this plugin; values are prepared.

if ( ! class_exists( 'UserFerry_History' ) ) {

	/**
	 * Log of every export with its file.
	 */
	class UserFerry_History {

		/**
		 * Insert a log row.
		 *
		 * @param array $data Data.
		 * @return int
		 */
		public static function log( array $data ) {
			global $wpdb;
			$wpdb->insert(
				UserFerry_Install::history_table(),
				array(
					'entry_type'    => ( isset( $data['type'] ) && 'import' === $data['type'] ) ? 'import' : 'export',
					'template_id'   => absint( $data['template_id'] ),
					'template_name' => sanitize_text_field( $data['template_name'] ),
					'admin_id'      => get_current_user_id(),
					'user_count'    => absint( $data['user_count'] ),
					'field_count'   => absint( $data['field_count'] ),
					'file_name'     => sanitize_file_name( $data['file_name'] ),
					'file_size'     => absint( $data['file_size'] ),
					'format'        => sanitize_key( $data['format'] ),
					'created_at'    => current_time( 'mysql' ),
				)
			);
			return (int) $wpdb->insert_id;
		}

		/**
		 * Paged rows.
		 *
		 * @param int $page     Page.
		 * @param int $per_page Per page.
		 * @return array
		 */
		public static function page( $page = 1, $per_page = 20 ) {
			global $wpdb;
			$offset = ( max( 1, (int) $page ) - 1 ) * $per_page;
			return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d', UserFerry_Install::history_table(), $per_page, $offset ) );
		}

		/**
		 * Count rows.
		 *
		 * @return int
		 */
		public static function count() {
			global $wpdb;
			return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', UserFerry_Install::history_table() ) );
		}

		/**
		 * One row.
		 *
		 * @param int $id Id.
		 * @return object|null
		 */
		public static function get( $id ) {
			global $wpdb;
			return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', UserFerry_Install::history_table(), absint( $id ) ) );
		}

		/**
		 * Absolute path of a row's file.
		 *
		 * @param object $row Row.
		 * @return string
		 */
		public static function file_path( $row ) {
			return UserFerry_Install::export_dir() . '/' . basename( (string) $row->file_name );
		}

		/**
		 * Delete a row and its file.
		 *
		 * @param int $id Id.
		 * @return bool
		 */
		public static function delete( $id ) {
			global $wpdb;
			$row = self::get( $id );
			if ( ! $row ) {
				return false;
			}
			$path = self::file_path( $row );
			if ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
			return (bool) $wpdb->delete( UserFerry_Install::history_table(), array( 'id' => (int) $row->id ) );
		}

		/**
		 * Delete every row and every export file.
		 */
		public static function clear() {
			global $wpdb;
			$wpdb->query( $wpdb->prepare( 'TRUNCATE TABLE %i', UserFerry_Install::history_table() ) );

			foreach ( (array) glob( UserFerry_Install::export_dir() . '/*' ) as $file ) {
				$ext = strtolower( pathinfo( (string) $file, PATHINFO_EXTENSION ) );
				if ( in_array( $ext, array( 'csv', 'xlsx', 'json', 'xml', 'tmp' ), true ) ) {
					wp_delete_file( $file );
				}
			}
		}

		/**
		 * Delete rows and files older than N days.
		 *
		 * @param int $days Days.
		 */
		public static function purge_older_than( $days ) {
			global $wpdb;
			$cutoff = wp_date( 'Y-m-d H:i:s', time() - ( absint( $days ) * DAY_IN_SECONDS ) );
			$rows   = (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM %i WHERE created_at < %s', UserFerry_Install::history_table(), $cutoff ) );
			foreach ( $rows as $row ) {
				self::delete( $row->id );
			}
		}
	}
}
