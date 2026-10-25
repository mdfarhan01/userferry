<?php
/**
 * Verifies that export files cannot be downloaded without logging in.
 *
 * .htaccess and web.config rules only work on Apache, LiteSpeed and IIS.
 * Nginx ignores them, so this class makes a real, unauthenticated HTTP
 * request (a loopback request to this same site — nothing is sent anywhere
 * else) for a probe file in the storage folder and reports the result in
 * Settings and in Tools > Site Health.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Storage_Check' ) ) {

	/**
	 * Class UserFerry_Storage_Check
	 */
	class UserFerry_Storage_Check {

		const TRANSIENT = 'userferry_storage_check';
		const PROBE     = 'userferry-access-check.txt';

		/**
		 * Result of the last check (runs it when nothing is cached).
		 *
		 * @param bool $force Ignore the cached result.
		 * @return array { status: protected|outside|exposed|unknown, checked: int, detail: string }
		 */
		public static function result( $force = false ) {
			if ( ! $force ) {
				$cached = get_transient( self::TRANSIENT );
				if ( is_array( $cached ) && isset( $cached['status'] ) ) {
					return $cached;
				}
			}

			$result = self::run();
			set_transient( self::TRANSIENT, $result, DAY_IN_SECONDS );
			return $result;
		}

		/**
		 * Perform the check.
		 *
		 * @return array
		 */
		private static function run() {
			$result = array(
				'status'  => 'unknown',
				'checked' => time(),
				'detail'  => '',
			);

			$url = UserFerry_Install::export_dir_url();
			if ( '' === $url ) {
				$result['status'] = 'outside';
				return $result;
			}

			if ( ! UserFerry_Install::ensure_export_dir() ) {
				$result['detail'] = __( 'The storage folder could not be created.', 'userferry' );
				return $result;
			}

			$token = wp_generate_password( 32, false, false );
			$file  = UserFerry_Install::export_dir() . '/' . self::PROBE;
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Short-lived probe file inside the plugin's own folder.
			if ( false === file_put_contents( $file, $token ) ) {
				$result['detail'] = __( 'A test file could not be written to the storage folder.', 'userferry' );
				return $result;
			}

			// Loopback request to this site only, with no cookies: exactly what an anonymous visitor would get.
			$response = wp_remote_get(
				$url . '/' . self::PROBE,
				array(
					'timeout'     => 8,
					'redirection' => 0,
					'cookies'     => array(),
					/** This filter is documented in wp-includes/class-wp-http-streams.php */
					'sslverify'   => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core filter.
				)
			);
			wp_delete_file( $file );

			if ( is_wp_error( $response ) ) {
				$result['detail'] = $response->get_error_message();
				return $result;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = (string) wp_remote_retrieve_body( $response );

			if ( 200 === $code && false !== strpos( $body, $token ) ) {
				$result['status'] = 'exposed';
				return $result;
			}

			// Only an explicit "access denied" proves the folder is blocked.
			if ( 401 === $code || 403 === $code ) {
				$result['status'] = 'protected';
				/* translators: %d: HTTP status code */
				$result['detail'] = sprintf( __( 'An anonymous request was refused (HTTP %d).', 'userferry' ), $code );
				return $result;
			}

			// Anything else is inconclusive: a redirect may lead to the file, a 404 may
			// mean files are served from another address (CDN / offload), 5xx is an error.
			if ( $code >= 300 && $code < 400 ) {
				/* translators: %d: HTTP status code */
				$result['detail'] = sprintf( __( 'The request was redirected (HTTP %d), so protection could not be confirmed.', 'userferry' ), $code );
			} elseif ( 404 === $code || 410 === $code ) {
				/* translators: %d: HTTP status code */
				$result['detail'] = sprintf( __( 'The test file was not found at its expected address (HTTP %d). Uploads may be served from another address, so protection could not be confirmed.', 'userferry' ), $code );
			} elseif ( 200 === $code ) {
				$result['detail'] = __( 'The server answered, but not with the test file, so protection could not be confirmed.', 'userferry' );
			} else {
				/* translators: %d: HTTP status code */
				$result['detail'] = sprintf( __( 'The server returned an unexpected response (HTTP %d), so protection could not be confirmed.', 'userferry' ), $code );
			}
			return $result;
		}

		/**
		 * Nginx rule that blocks the folder.
		 *
		 * @return string
		 */
		public static function nginx_rule() {
			$upload = wp_upload_dir( null, false );
			$path   = wp_parse_url( $upload['baseurl'], PHP_URL_PATH );
			return 'location ^~ ' . untrailingslashit( (string) $path ) . '/' . UserFerry_Install::EXPORT_DIRNAME . '- {' . "\n\tdeny all;\n\treturn 403;\n}";
		}

		/**
		 * Re-run the check from the Settings screen (admin-post.php).
		 */
		public static function handle_recheck() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to do that.', 'userferry' ), '', array( 'response' => 403 ) );
			}
			check_admin_referer( 'userferry_storage_recheck' );
			self::result( true );
			wp_safe_redirect( userferry_admin_url( 'userferry-settings', array( 'userferry_notice' => 'storage-checked' ) ) . '#userferry-storage' );
			exit;
		}

		/**
		 * Register the Site Health test.
		 *
		 * @param array $tests Tests.
		 * @return array
		 */
		public static function site_health_tests( $tests ) {
			$tests['direct']['userferry_export_storage'] = array(
				'label' => __( 'User export files are protected', 'userferry' ),
				'test'  => array( __CLASS__, 'site_health_test' ),
			);
			return $tests;
		}

		/**
		 * Site Health test callback.
		 *
		 * @return array
		 */
		public static function site_health_test() {
			$result = self::result( true );
			$badge  = array(
				'label' => __( 'Security', 'userferry' ),
				'color' => 'blue',
			);
			$action = sprintf( '<p><a href="%s">%s</a></p>', esc_url( userferry_admin_url( 'userferry-settings' ) . '#userferry-storage' ), esc_html__( 'Open UserFerry settings', 'userferry' ) );

			if ( 'exposed' === $result['status'] ) {
				return array(
					'label'       => __( 'User export files can be downloaded without logging in', 'userferry' ),
					'status'      => 'critical',
					'badge'       => array_merge( $badge, array( 'color' => 'red' ) ),
					'description' => '<p>' . esc_html__( 'Your web server (usually Nginx) ignores the protection rules in the export folder. Add the rule shown in the plugin settings to your server configuration, or store exports outside the web root with the USERFERRY_EXPORT_DIR constant.', 'userferry' ) . '</p>',
					'actions'     => $action,
					'test'        => 'userferry_export_storage',
				);
			}

			if ( 'unknown' === $result['status'] ) {
				return array(
					'label'       => __( 'User export folder protection could not be verified', 'userferry' ),
					'status'      => 'recommended',
					'badge'       => $badge,
					'description' => '<p>' . esc_html__( 'The site could not request its own export folder to confirm it is blocked. If you use Nginx, add the rule shown in the plugin settings.', 'userferry' ) . '</p>',
					'actions'     => $action,
					'test'        => 'userferry_export_storage',
				);
			}

			return array(
				'label'       => __( 'User export files are protected', 'userferry' ),
				'status'      => 'good',
				'badge'       => $badge,
				'description' => '<p>' . esc_html__( 'Export files cannot be downloaded without logging in.', 'userferry' ) . '</p>',
				'test'        => 'userferry_export_storage',
			);
		}
	}
}
