<?php
/**
 * Field discovery.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Fields' ) ) {

	/**
	 * Knows which columns can be exported: WordPress core user fields plus every
	 * meta key found in the usermeta table, de-duplicated and grouped.
	 */
	class UserFerry_Fields {

		const CACHE_KEY  = 'userferry_meta_keys';
		const SCAN_LIMIT = 2000;

		/**
		 * Core WordPress user fields.
		 *
		 * @return array key => label
		 */
		public static function core_fields() {
			$fields = array(
				'ID'              => __( 'User ID', 'userferry' ),
				'user_login'      => __( 'Username', 'userferry' ),
				'user_email'      => __( 'Email', 'userferry' ),
				'first_name'      => __( 'First name', 'userferry' ),
				'last_name'       => __( 'Last name', 'userferry' ),
				'display_name'    => __( 'Display name', 'userferry' ),
				'nickname'        => __( 'Nickname', 'userferry' ),
				'roles'           => __( 'Role', 'userferry' ),
				'user_url'        => __( 'Website', 'userferry' ),
				'user_registered' => __( 'Registered date', 'userferry' ),
				'description'     => __( 'Biographical info', 'userferry' ),
				'user_nicename'   => __( 'Profile slug', 'userferry' ),
				'user_status'     => __( 'User status', 'userferry' ),
			);

			if ( self::password_hash_allowed() ) {
				$fields['user_pass'] = __( 'Password hash', 'userferry' );
			}

			/**
			 * Filters the core fields offered for export.
			 *
			 * @param array $fields key => label.
			 */
			return (array) apply_filters( 'userferry_core_fields', $fields );
		}

		/**
		 * Fields pre-selected for a brand new export.
		 *
		 * @return string[]
		 */
		public static function default_selection() {
			return array( 'ID', 'user_login', 'user_email', 'first_name', 'last_name', 'display_name', 'roles', 'user_registered' );
		}

		/**
		 * Whether the current user may export password hashes.
		 *
		 * @return bool
		 */
		public static function password_hash_allowed() {
			return (bool) UserFerry_Settings::get( 'allow_password_hash' ) && current_user_can( 'manage_options' );
		}

		/**
		 * Meta keys that are never exportable (secrets or duplicated by core fields).
		 *
		 * @return string[]
		 */
		private static function blocked_keys() {
			global $wpdb;
			return array(
				'session_tokens',
				'user_pass',
				$wpdb->prefix . 'capabilities',
				$wpdb->prefix . 'user_level',
				// Already provided as core fields.
				'first_name',
				'last_name',
				'nickname',
				'description',
			);
		}

		/**
		 * Is this meta key allowed in an export?
		 *
		 * @param string $key Meta key.
		 * @return bool
		 */
		public static function is_exportable_meta_key( $key ) {
			$key = (string) $key;
			if ( '' === $key || strlen( $key ) > 255 ) {
				return false;
			}
			if ( in_array( $key, self::blocked_keys(), true ) ) {
				return false;
			}
			// Capabilities / user level for other sites in a multisite network.
			if ( preg_match( '/_(capabilities|user_level)$/', $key ) ) {
				return false;
			}
			return true;
		}

		/**
		 * Scan the usermeta table and return de-duplicated meta keys in two groups:
		 * "profile" (real profile data) and "system" (hidden keys, admin preferences
		 * and plugin housekeeping).
		 *
		 * Hidden reference keys such as ACF's "_district" (which only stores the
		 * field id for "district") are dropped so every field appears exactly once.
		 *
		 * @param bool $force Bypass the cache.
		 * @return array { profile: array, system: array }
		 */
		public static function scan( $force = false ) {
			if ( ! $force ) {
				$cached = get_transient( self::CACHE_KEY );
				if ( is_array( $cached ) && isset( $cached['profile'], $cached['system'] ) ) {
					return $cached;
				}
			}

			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Distinct meta keys are not available through a core API; result is cached in a transient.
			$keys = $wpdb->get_col( $wpdb->prepare( "SELECT DISTINCT meta_key FROM {$wpdb->usermeta} WHERE meta_key <> '' ORDER BY meta_key ASC LIMIT %d", self::SCAN_LIMIT ) );

			$keys   = array_values( array_unique( array_map( 'strval', (array) $keys ) ) );
			$lookup = array_flip( $keys );
			$core   = self::core_fields();

			$profile = array();
			$system  = array();

			foreach ( $keys as $key ) {
				if ( isset( $core[ $key ] ) || ! self::is_exportable_meta_key( $key ) ) {
					continue;
				}

				// "_field" is a reference key when "field" also exists (ACF, Meta Box, Pods...).
				if ( '_' === $key[0] && isset( $lookup[ substr( $key, 1 ) ] ) ) {
					continue;
				}

				$label = self::label_for( $key );
				if ( self::is_system_key( $key ) ) {
					$system[ $key ] = $label;
				} else {
					$profile[ $key ] = $label;
				}
			}

			$result = array(
				'profile' => $profile,
				'system'  => $system,
			);

			/**
			 * Filters the scanned meta fields.
			 *
			 * @param array $result { profile: key => label, system: key => label }.
			 */
			$result         = (array) apply_filters( 'userferry_scanned_fields', $result );
			$result['time'] = time();

			set_transient( self::CACHE_KEY, $result, 12 * HOUR_IN_SECONDS );
			return $result;
		}

		/**
		 * "5 minutes ago" label for the last scan.
		 *
		 * @return string
		 */
		public static function scanned_at_label() {
			$scan = self::scan();
			$time = isset( $scan['time'] ) ? (int) $scan['time'] : time();
			/* translators: %s: human time difference, e.g. "5 mins" */
			return sprintf( __( '%s ago', 'userferry' ), human_time_diff( $time, time() ) );
		}

		/**
		 * Decide whether a meta key is internal / housekeeping data.
		 *
		 * @param string $key Meta key.
		 * @return bool
		 */
		public static function is_system_key( $key ) {
			global $wpdb;

			$exact = array(
				'rich_editing',
				'syntax_highlighting',
				'comment_shortcuts',
				'admin_color',
				'use_ssl',
				'show_admin_bar_front',
				'locale',
				'dismissed_wp_pointers',
				'show_welcome_panel',
				'default_password_nag',
				'community-events-location',
				'nav_menu_recently_edited',
				'enable_custom_fields',
				'acf_user_settings',
				'infinite_scrolling',
				'um_member_directory_data',
				'um_account_secure_fields',
			);

			$is_system = in_array( $key, $exact, true );

			if ( ! $is_system ) {
				$prefixes = array_unique( array( $wpdb->prefix, $wpdb->base_prefix ) );
				foreach ( $prefixes as $prefix ) {
					if ( '' !== $prefix && 0 === strpos( $key, $prefix ) ) {
						$is_system = true;
						break;
					}
				}
			}

			if ( ! $is_system ) {
				$patterns = array(
					'/^_/',
					'/^(closedpostboxes|metaboxhidden|meta-box-order|screen_layout)[_-]/',
					'/columnshidden$/',
					'/_per_page$/',
					'/dismiss/i',
					'/notice/i',
					'/pointer/i',
					'/promo/i',
					'/preferences?$/',
					'/^(elementor|wpcode|ea11y|wdt_|wpr_|jetpack|wpseo|rank_math|aioseo|itsec|wfls|wordfence|litespeed|w3tc)/i',
				);
				foreach ( $patterns as $pattern ) {
					if ( preg_match( $pattern, $key ) ) {
						$is_system = true;
						break;
					}
				}
			}

			/**
			 * Filters whether a meta key is grouped under "Hidden & plugin data".
			 *
			 * @param bool   $is_system Whether the key is treated as system data.
			 * @param string $key       Meta key.
			 */
			return (bool) apply_filters( 'userferry_is_system_meta_key', $is_system, $key );
		}

		/**
		 * Human label for a meta key: "_um_last_login" -> "Um last login".
		 *
		 * @param string $key Meta key.
		 * @return string
		 */
		public static function label_for( $key ) {
			$label = trim( str_replace( array( '_', '-' ), ' ', ltrim( (string) $key, '_' ) ) );
			$label = preg_replace( '/\s+/', ' ', $label );
			if ( '' === $label ) {
				return (string) $key;
			}
			return ucfirst( $label );
		}

		/**
		 * Every known field label (core + scanned meta).
		 *
		 * @return array key => label
		 */
		public static function all_labels() {
			$scan = self::scan();
			return array_merge( $scan['system'], $scan['profile'], self::core_fields() );
		}

		/**
		 * Number of distinct, de-duplicated custom fields.
		 *
		 * @return int
		 */
		public static function count_custom() {
			$scan = self::scan();
			return count( $scan['profile'] );
		}
	}
}
