<?php
/**
 * Plugin settings.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Settings' ) ) {

	/**
	 * Stores and validates plugin settings in a single option.
	 */
	class UserFerry_Settings {

		const OPTION = USERFERRY_OPTION_KEY;

		/**
		 * Default values.
		 *
		 * @return array
		 */
		public static function defaults() {
			return array(
				'default_format'           => 'csv',
				'default_filename'         => 'users-export',
				'batch_size'               => 200,
				'csv_delimiter'            => 'comma',
				'csv_bom'                  => 1,
				'formula_protection'       => 1,
				'date_format'              => 'raw',
				'enable_logs'              => 1,
				'retention_days'           => 30,
				'allow_password_hash'      => 0,
				'delete_data_on_uninstall' => 0,
				'import_match_by'          => 'email',
				'import_existing'          => 'update',
				'import_send_email'        => 0,
			);
		}

		/**
		 * Get all settings, or a single one.
		 *
		 * @param string|null $key Optional setting key.
		 * @return mixed
		 */
		public static function get( $key = null ) {
			$settings = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
			if ( null === $key ) {
				return $settings;
			}
			return isset( $settings[ $key ] ) ? $settings[ $key ] : null;
		}

		/**
		 * Validate a raw settings array.
		 *
		 * @param array $raw Raw input.
		 * @return array
		 */
		public static function sanitize( array $raw ) {
			$defaults = self::defaults();

			$format = isset( $raw['default_format'] ) ? sanitize_key( $raw['default_format'] ) : $defaults['default_format'];
			if ( ! array_key_exists( $format, UserFerry_Export_Writer::formats() ) ) {
				$format = $defaults['default_format'];
			}

			$filename = isset( $raw['default_filename'] ) ? UserFerry_Export_Config::sanitize_filename( $raw['default_filename'] ) : $defaults['default_filename'];

			$delimiter = isset( $raw['csv_delimiter'] ) ? sanitize_key( $raw['csv_delimiter'] ) : 'comma';
			if ( ! in_array( $delimiter, array( 'comma', 'semicolon', 'tab' ), true ) ) {
				$delimiter = 'comma';
			}

			$date_format = isset( $raw['date_format'] ) ? sanitize_key( $raw['date_format'] ) : 'raw';
			if ( ! in_array( $date_format, array( 'raw', 'site' ), true ) ) {
				$date_format = 'raw';
			}

			$batch = isset( $raw['batch_size'] ) ? absint( $raw['batch_size'] ) : $defaults['batch_size'];

			return array(
				'default_format'           => $format,
				'default_filename'         => $filename,
				'batch_size'               => min( 2000, max( 25, $batch ) ),
				'csv_delimiter'            => $delimiter,
				'csv_bom'                  => empty( $raw['csv_bom'] ) ? 0 : 1,
				'formula_protection'       => empty( $raw['formula_protection'] ) ? 0 : 1,
				'date_format'              => $date_format,
				'enable_logs'              => empty( $raw['enable_logs'] ) ? 0 : 1,
				'retention_days'           => isset( $raw['retention_days'] ) ? min( 3650, absint( $raw['retention_days'] ) ) : $defaults['retention_days'],
				'allow_password_hash'      => empty( $raw['allow_password_hash'] ) ? 0 : 1,
				'delete_data_on_uninstall' => empty( $raw['delete_data_on_uninstall'] ) ? 0 : 1,
				'import_match_by'          => ( isset( $raw['import_match_by'] ) && in_array( $raw['import_match_by'], array( 'email', 'login', 'id' ), true ) ) ? $raw['import_match_by'] : 'email',
				'import_existing'          => ( isset( $raw['import_existing'] ) && 'skip' === $raw['import_existing'] ) ? 'skip' : 'update',
				'import_send_email'        => empty( $raw['import_send_email'] ) ? 0 : 1,
			);
		}

		/**
		 * Handle the settings form (admin-post.php).
		 */
		public static function handle_save() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have permission to change these settings.', 'userferry' ), '', array( 'response' => 403 ) );
			}
			check_admin_referer( 'userferry_save_settings', 'userferry_settings_nonce' );

			$raw = isset( $_POST['userferry'] ) && is_array( $_POST['userferry'] ) ? map_deep( wp_unslash( $_POST['userferry'] ), 'sanitize_text_field' ) : array();

			update_option( self::OPTION, self::sanitize( $raw ), false );
			delete_transient( UserFerry_Fields::CACHE_KEY );

			wp_safe_redirect(
				add_query_arg(
					array(
						'page'             => 'userferry-settings',
						'userferry_notice' => 'settings-saved',
					),
					admin_url( 'admin.php' )
				)
			);
			exit;
		}
	}
}
