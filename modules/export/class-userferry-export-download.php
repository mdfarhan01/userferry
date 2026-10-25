<?php
/**
 * Secure file downloads.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Export_Download' ) ) {

	/**
	 * Streams export files through admin-post.php after nonce + capability checks.
	 * Export files are never linked directly.
	 */
	class UserFerry_Export_Download {

		/**
		 * Build a signed download URL.
		 *
		 * @param string $file_name  File name inside the exports folder.
		 * @param int    $history_id Optional history id.
		 * @return string
		 */
		public static function url( $file_name, $history_id = 0 ) {
			// Built with add_query_arg() (not wp_nonce_url(), which HTML-encodes "&")
			// so the URL works both in JavaScript and, via esc_url(), in markup.
			$args = array(
				'action'   => 'userferry_download',
				'_wpnonce' => wp_create_nonce( 'userferry_download' ),
			);
			if ( $history_id ) {
				$args['id'] = (int) $history_id;
			} else {
				$args['file'] = $file_name;
			}
			return add_query_arg( $args, admin_url( 'admin-post.php' ) );
		}

		/**
		 * Download handler.
		 */
		public function handle() {
			if ( ! current_user_can( userferry_capability() ) ) {
				wp_die( esc_html__( 'You do not have permission to download exports.', 'userferry' ), '', array( 'response' => 403 ) );
			}

			// Verify the nonce before reading any request parameter.
			check_admin_referer( 'userferry_download' );

			$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
			$file = isset( $_GET['file'] ) ? sanitize_file_name( wp_unslash( $_GET['file'] ) ) : '';

			if ( $id ) {
				$row = UserFerry_History::get( $id );
				if ( ! $row ) {
					wp_die( esc_html__( 'This export record no longer exists.', 'userferry' ), '', array( 'response' => 404 ) );
				}
				$file = basename( $row->file_name );
			}

			$this->stream( $file );
		}

		/**
		 * Stream a file from the exports folder.
		 *
		 * @param string $file File name.
		 */
		private function stream( $file ) {
			$formats = UserFerry_Export_Writer::formats();
			$ext     = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
			$base    = realpath( UserFerry_Install::export_dir() );
			$path    = $base ? realpath( $base . '/' . $file ) : false;

			if ( ! preg_match( '/^[A-Za-z0-9._-]+$/', $file ) || ! isset( $formats[ $ext ] ) || ! $path || 0 !== strpos( $path, $base . DIRECTORY_SEPARATOR ) || ! is_file( $path ) ) {
				wp_die( esc_html__( 'The export file was not found. It may have been removed by the automatic cleanup — run the export again.', 'userferry' ), '', array( 'response' => 404 ) );
			}

			while ( ob_get_level() ) {
				ob_end_clean();
			}

			nocache_headers();
			header( 'Content-Type: ' . $formats[ $ext ]['mime'] . '; charset=utf-8' );
			header( 'Content-Disposition: attachment; filename="' . $file . '"' );
			header( 'Content-Length: ' . filesize( $path ) );
			header( 'X-Content-Type-Options: nosniff' );

			readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- Streaming a large binary file to the browser.
			exit;
		}
	}
}
