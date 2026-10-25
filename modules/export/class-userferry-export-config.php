<?php
/**
 * Export configuration sanitizing.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Export_Config' ) ) {

	/**
	 * Normalises everything that describes an export (used by the wizard,
	 * templates and the exporter so all three agree on one shape).
	 */
	class UserFerry_Export_Config {

		/**
		 * Allowed sort columns.
		 *
		 * @return array
		 */
		public static function sort_options() {
			return array(
				'ID'              => __( 'User ID', 'userferry' ),
				'user_registered' => __( 'Registered date', 'userferry' ),
				'user_login'      => __( 'Username', 'userferry' ),
				'user_email'      => __( 'Email', 'userferry' ),
				'display_name'    => __( 'Display name', 'userferry' ),
			);
		}

		/**
		 * Default filter set.
		 *
		 * @return array
		 */
		public static function default_filters() {
			return array(
				'scope'      => 'all',
				'user_ids'   => array(),
				'roles'      => array(),
				'date_range' => '30days',
				'date_from'  => '',
				'date_to'    => '',
				'search'     => '',
				'orderby'    => 'ID',
				'order'      => 'ASC',
			);
		}

		/**
		 * Decode a JSON string coming from a request (already unslashed).
		 *
		 * @param mixed $value Raw value.
		 * @return array
		 */
		public static function decode( $value ) {
			if ( is_array( $value ) ) {
				return $value;
			}
			$decoded = json_decode( (string) $value, true );
			return is_array( $decoded ) ? $decoded : array();
		}

		/**
		 * Sanitize the "which users" filter set.
		 *
		 * @param array $raw Raw filters.
		 * @return array
		 */
		public static function sanitize_filters( $raw ) {
			$raw = is_array( $raw ) ? $raw : array();
			$out = self::default_filters();

			$scope = isset( $raw['scope'] ) ? sanitize_key( $raw['scope'] ) : 'all';
			// Back-compat with v1.0 templates.
			if ( isset( $raw['selection'] ) && ! isset( $raw['scope'] ) ) {
				$scope = sanitize_key( $raw['selection'] );
			}
			$out['scope'] = in_array( $scope, array( 'all', 'selected', 'role', 'date' ), true ) ? $scope : 'all';

			if ( ! empty( $raw['user_ids'] ) && is_string( $raw['user_ids'] ) ) {
				$raw['user_ids'] = explode( ',', $raw['user_ids'] );
			}
			if ( ! empty( $raw['user_ids'] ) && is_array( $raw['user_ids'] ) ) {
				$out['user_ids'] = array_values( array_unique( array_filter( array_map( 'absint', $raw['user_ids'] ) ) ) );
			}

			if ( ! empty( $raw['roles'] ) && is_array( $raw['roles'] ) ) {
				$valid        = array_keys( wp_roles()->get_names() );
				$out['roles'] = array_values( array_intersect( array_map( 'sanitize_key', $raw['roles'] ), $valid ) );
			}

			$range             = isset( $raw['date_range'] ) ? sanitize_key( $raw['date_range'] ) : '30days';
			$out['date_range'] = in_array( $range, array( 'today', '7days', '30days', 'custom' ), true ) ? $range : '30days';

			foreach ( array( 'date_from', 'date_to' ) as $key ) {
				$value       = isset( $raw[ $key ] ) ? sanitize_text_field( $raw[ $key ] ) : '';
				$out[ $key ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
			}

			$out['search'] = isset( $raw['search'] ) ? substr( sanitize_text_field( $raw['search'] ), 0, 100 ) : '';

			$orderby        = isset( $raw['orderby'] ) ? sanitize_text_field( $raw['orderby'] ) : 'ID';
			$out['orderby'] = array_key_exists( $orderby, self::sort_options() ) ? $orderby : 'ID';
			$out['order']   = ( isset( $raw['order'] ) && 'DESC' === strtoupper( (string) $raw['order'] ) ) ? 'DESC' : 'ASC';

			return $out;
		}

		/**
		 * Sanitize an ordered list of field keys.
		 *
		 * @param array $raw Raw keys.
		 * @return string[]
		 */
		public static function sanitize_fields( $raw ) {
			$raw  = is_array( $raw ) ? $raw : array();
			$core = UserFerry_Fields::core_fields();
			$out  = array();

			foreach ( $raw as $key ) {
				if ( ! is_scalar( $key ) ) {
					continue;
				}
				$key = trim( wp_strip_all_tags( (string) $key ) );
				if ( isset( $core[ $key ] ) || UserFerry_Fields::is_exportable_meta_key( $key ) ) {
					$out[ $key ] = true;
				}
			}

			return array_keys( $out );
		}

		/**
		 * Sanitize a format.
		 *
		 * @param string $format Raw format.
		 * @return string
		 */
		public static function sanitize_format( $format ) {
			$format = sanitize_key( (string) $format );
			return array_key_exists( $format, UserFerry_Export_Writer::formats() ) ? $format : 'csv';
		}

		/**
		 * Sanitize a file name base (without extension).
		 *
		 * @param string $name Raw name.
		 * @return string
		 */
		public static function sanitize_filename( $name ) {
			$name = sanitize_file_name( (string) $name );
			$name = preg_replace( '/\.(csv|xlsx|json|xml)$/i', '', $name );
			$name = preg_replace( '/[^A-Za-z0-9._-]/', '', $name );
			$name = trim( substr( $name, 0, 80 ), '.-_' );
			return '' === $name ? 'users-export' : $name;
		}

		/**
		 * Human summary of a filter set.
		 *
		 * @param array $filters Sanitized filters.
		 * @return string
		 */
		public static function describe_filters( array $filters ) {
			switch ( $filters['scope'] ) {
				case 'selected':
					/* translators: %s: number of users */
					$text = sprintf( _n( '%s selected user', '%s selected users', count( $filters['user_ids'] ), 'userferry' ), number_format_i18n( count( $filters['user_ids'] ) ) );
					break;
				case 'role':
					$names = wp_roles()->get_names();
					$roles = array();
					foreach ( $filters['roles'] as $role ) {
						$roles[] = isset( $names[ $role ] ) ? translate_user_role( $names[ $role ] ) : $role;
					}
					/* translators: %s: comma separated list of roles */
					$text = sprintf( __( 'Role: %s', 'userferry' ), implode( ', ', $roles ) );
					break;
				case 'date':
					$ranges = array(
						'today'  => __( 'Registered today', 'userferry' ),
						'7days'  => __( 'Registered in the last 7 days', 'userferry' ),
						'30days' => __( 'Registered in the last 30 days', 'userferry' ),
					);
					if ( isset( $ranges[ $filters['date_range'] ] ) ) {
						$text = $ranges[ $filters['date_range'] ];
					} else {
						/* translators: 1: start date, 2: end date */
						$text = sprintf( __( 'Registered %1$s to %2$s', 'userferry' ), $filters['date_from'] ? $filters['date_from'] : '…', $filters['date_to'] ? $filters['date_to'] : '…' );
					}
					break;
				default:
					$text = __( 'All users', 'userferry' );
			}

			if ( '' !== $filters['search'] ) {
				/* translators: %s: search term */
				$text .= ' ' . sprintf( __( 'matching “%s”', 'userferry' ), $filters['search'] );
			}

			return $text;
		}
	}
}
