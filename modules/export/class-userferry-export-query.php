<?php
/**
 * User querying and row building.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Export_Query' ) ) {

	/**
	 * Translates a filter set into WP_User_Query arguments and turns users into rows.
	 */
	class UserFerry_Export_Query {

		/**
		 * Build WP_User_Query args.
		 *
		 * @param array $filters Sanitized filters.
		 * @return array
		 */
		public static function args( array $filters ) {
			$args = array(
				'fields'  => 'ID',
				'orderby' => $filters['orderby'],
				'order'   => $filters['order'],
			);

			switch ( $filters['scope'] ) {
				case 'selected':
					// An empty include would match everyone, so force "no match".
					$args['include'] = ! empty( $filters['user_ids'] ) ? $filters['user_ids'] : array( 0 );
					break;

				case 'role':
					$args['role__in'] = ! empty( $filters['roles'] ) ? $filters['roles'] : array( 'userferry-no-role' );
					break;

				case 'date':
					$range = self::date_range( $filters );
					if ( $range ) {
						$args['date_query'] = array( array_merge( $range, array( 'inclusive' => true ) ) );
					}
					break;
			}

			if ( '' !== $filters['search'] ) {
				$args['search']         = '*' . $filters['search'] . '*';
				$args['search_columns'] = array( 'user_login', 'user_email', 'user_nicename', 'display_name' );
			}

			/**
			 * Filters the WP_User_Query arguments used for an export.
			 *
			 * @param array $args    Query args.
			 * @param array $filters Sanitized filters.
			 */
			return (array) apply_filters( 'userferry_user_query_args', $args, $filters );
		}

		/**
		 * Convert a date filter into UTC 'after'/'before' boundaries
		 * (user_registered is stored in UTC; ranges are in the site's timezone).
		 *
		 * @param array $filters Filters.
		 * @return array
		 */
		private static function date_range( array $filters ) {
			$tz  = wp_timezone();
			$utc = new \DateTimeZone( 'UTC' );
			$out = array();

			try {
				switch ( $filters['date_range'] ) {
					case 'today':
						$start = new \DateTimeImmutable( 'today', $tz );
						break;
					case '7days':
						$start = new \DateTimeImmutable( 'today -6 days', $tz );
						break;
					case '30days':
						$start = new \DateTimeImmutable( 'today -29 days', $tz );
						break;
					case 'custom':
						$start = $filters['date_from'] ? new \DateTimeImmutable( $filters['date_from'] . ' 00:00:00', $tz ) : null;
						if ( $filters['date_to'] ) {
							$end           = new \DateTimeImmutable( $filters['date_to'] . ' 23:59:59', $tz );
							$out['before'] = $end->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
						}
						break;
					default:
						$start = null;
				}
			} catch ( \Exception $e ) {
				return array();
			}

			if ( ! empty( $start ) ) {
				$out['after'] = $start->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
			}

			return $out;
		}

		/**
		 * Count matching users.
		 *
		 * @param array $filters Sanitized filters.
		 * @return int
		 */
		public static function count( array $filters ) {
			$args                = self::args( $filters );
			$args['number']      = 1;
			$args['count_total'] = true;
			$query               = new \WP_User_Query( $args );
			return (int) $query->get_total();
		}

		/**
		 * Fetch one page of users with their meta primed.
		 *
		 * @param array $filters Sanitized filters.
		 * @param int   $offset  Offset.
		 * @param int   $limit   Limit.
		 * @return \WP_User[]
		 */
		public static function users( array $filters, $offset, $limit ) {
			$args                = self::args( $filters );
			$args['number']      = (int) $limit;
			$args['offset']      = (int) $offset;
			$args['count_total'] = false;

			$ids = ( new \WP_User_Query( $args ) )->get_results();
			if ( empty( $ids ) ) {
				return array();
			}

			return get_users(
				array(
					'include' => array_map( 'intval', $ids ),
					'orderby' => 'include',
					'fields'  => 'all_with_meta',
					'blog_id' => 0,
				)
			);
		}

		/**
		 * Build one export row.
		 *
		 * @param \WP_User $user   User.
		 * @param string[] $fields Field keys, in column order.
		 * @return array key => string
		 */
		public static function row( \WP_User $user, array $fields ) {
			static $role_names = null;
			if ( null === $role_names ) {
				$role_names = wp_roles()->get_names();
			}

			$date_format = UserFerry_Settings::get( 'date_format' );
			$row         = array();

			foreach ( $fields as $key ) {
				switch ( $key ) {
					case 'ID':
						$value = (string) $user->ID;
						break;
					case 'roles':
						$names = array();
						foreach ( (array) $user->roles as $role ) {
							$names[] = isset( $role_names[ $role ] ) ? translate_user_role( $role_names[ $role ] ) : $role;
						}
						$value = implode( ', ', $names );
						break;
					case 'user_status':
						$value = 0 === (int) $user->user_status ? __( 'Active', 'userferry' ) : __( 'Inactive', 'userferry' );
						break;
					case 'user_registered':
						$value = $user->user_registered;
						if ( 'site' === $date_format && $value ) {
							$timestamp = strtotime( $value . ' UTC' );
							$value     = $timestamp ? wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ) : $value;
						}
						break;
					case 'user_pass':
						$value = UserFerry_Fields::password_hash_allowed() ? $user->user_pass : '';
						break;
					case 'user_login':
					case 'user_email':
					case 'user_url':
					case 'user_nicename':
					case 'display_name':
						$value = (string) $user->{$key};
						break;
					default:
						$value = self::format_meta( get_user_meta( $user->ID, $key, true ) );
				}

				/**
				 * Filters a single exported value.
				 *
				 * @param string   $value Value.
				 * @param string   $key   Field key.
				 * @param \WP_User $user  User.
				 */
				$row[ $key ] = (string) apply_filters( 'userferry_export_value', $value, $key, $user );
			}

			return $row;
		}

		/**
		 * Flatten a meta value to a string.
		 *
		 * @param mixed $value Meta value.
		 * @return string
		 */
		private static function format_meta( $value ) {
			if ( is_bool( $value ) ) {
				return $value ? '1' : '0';
			}
			if ( is_scalar( $value ) || null === $value ) {
				return (string) $value;
			}
			if ( is_array( $value ) && empty( $value ) ) {
				return '';
			}
			if ( is_array( $value ) ) {
				$flat = true;
				foreach ( $value as $item ) {
					if ( ! is_scalar( $item ) ) {
						$flat = false;
						break;
					}
				}
				if ( $flat && array_keys( $value ) === range( 0, count( $value ) - 1 ) ) {
					return implode( ', ', array_map( 'strval', $value ) );
				}
			}
			return (string) wp_json_encode( $value );
		}
	}
}
