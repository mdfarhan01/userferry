<?php
/**
 * Batched user import. The uploaded file is normalised by
 * UserFerry_Import_Reader, the admin maps columns to fields, then rows are
 * created/updated a small batch per AJAX request (no timeouts on big files).
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.WP.AlternativeFunctions -- Reading the job's temporary file from a stored byte offset needs native file handles.

if ( ! class_exists( 'UserFerry_Import_Job' ) ) {

	/**
	 * Class UserFerry_Import_Job
	 */
	class UserFerry_Import_Job {

		const OPTION     = 'userferry_import_jobs';
		const MAX_ERRORS = 200;

		/**
		 * All stored jobs.
		 *
		 * @return array
		 */
		private static function all() {
			$jobs = get_option( self::OPTION, array() );
			return is_array( $jobs ) ? $jobs : array();
		}

		/**
		 * Save one job.
		 *
		 * @param string $id  Job id.
		 * @param array  $job Job.
		 */
		private static function put( $id, array $job ) {
			$jobs        = self::all();
			$jobs[ $id ] = $job;
			update_option( self::OPTION, $jobs, false );
		}

		/**
		 * Forget a job and delete its file.
		 *
		 * @param string $id Job id.
		 */
		private static function forget( $id ) {
			$jobs = self::all();
			unset( $jobs[ $id ] );
			update_option( self::OPTION, $jobs, false );
			wp_delete_file( self::part_path( $id ) );
		}

		/**
		 * Temporary data file of a job.
		 *
		 * @param string $id Job id.
		 * @return string
		 */
		private static function part_path( $id ) {
			return UserFerry_Install::export_dir() . '/import-' . $id . '.part';
		}

		/**
		 * Load a job belonging to the current user.
		 *
		 * @param string $id Job id.
		 * @return array|WP_Error
		 */
		private static function load( $id ) {
			$id   = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $id ) );
			$jobs = self::all();
			if ( '' === $id || ! isset( $jobs[ $id ] ) || get_current_user_id() !== (int) $jobs[ $id ]['user'] ) {
				return new WP_Error( 'userferry_import_missing', __( 'This import is no longer available. Upload the file again.', 'userferry' ) );
			}
			$jobs[ $id ]['id'] = $id;
			return $jobs[ $id ];
		}

		/**
		 * Fields a column can be imported into, grouped for the mapping screen.
		 *
		 * @return array[] Each: { key, label, group }.
		 */
		public static function targets() {
			$targets = array(
				array( 'ID', __( 'User ID (match only)', 'userferry' ), 'identify' ),
				array( 'user_email', __( 'Email', 'userferry' ), 'identify' ),
				array( 'user_login', __( 'Username', 'userferry' ), 'identify' ),
				array( 'first_name', __( 'First name', 'userferry' ), 'profile' ),
				array( 'last_name', __( 'Last name', 'userferry' ), 'profile' ),
				array( 'display_name', __( 'Display name', 'userferry' ), 'profile' ),
				array( 'nickname', __( 'Nickname', 'userferry' ), 'profile' ),
				array( 'roles', __( 'Role', 'userferry' ), 'profile' ),
				array( 'user_url', __( 'Website', 'userferry' ), 'profile' ),
				array( 'user_registered', __( 'Registered date', 'userferry' ), 'profile' ),
				array( 'description', __( 'Biographical info', 'userferry' ), 'profile' ),
				array( 'user_nicename', __( 'Profile slug', 'userferry' ), 'profile' ),
				array( 'plain_password', __( 'Password (plain text)', 'userferry' ), 'password' ),
			);
			if ( UserFerry_Fields::password_hash_allowed() ) {
				$targets[] = array( 'user_pass', __( 'Password hash', 'userferry' ), 'password' );
			}

			$scan = UserFerry_Fields::scan();
			foreach ( array(
				'profile' => 'custom',
				'system'  => 'hidden',
			) as $group => $label ) {
				foreach ( $scan[ $group ] as $key => $field_label ) {
					$targets[] = array( 'meta:' . $key, $field_label . ' (' . $key . ')', $label );
				}
			}

			$out = array();
			foreach ( $targets as $t ) {
				$out[] = array(
					'key'   => $t[0],
					'label' => $t[1],
					'group' => $t[2],
				);
			}
			return $out;
		}

		/**
		 * Suggest a target for every column (matches keys, labels and common synonyms).
		 *
		 * @param string[] $headers Column names.
		 * @return array column => target key ('' = skip)
		 */
		public static function suggest( array $headers ) {
			$lookup = array();
			$norm   = static function ( $text ) {
				return trim( preg_replace( '/[^a-z0-9]+/', ' ', strtolower( (string) $text ) ) );
			};

			foreach ( self::targets() as $t ) {
				$key                     = 0 === strpos( $t['key'], 'meta:' ) ? substr( $t['key'], 5 ) : $t['key'];
				$lookup[ $norm( $key ) ] = $t['key'];
			}
			foreach ( UserFerry_Fields::all_labels() as $key => $label ) {
				$target = in_array( $key, array( 'ID', 'user_email', 'user_login', 'first_name', 'last_name', 'display_name', 'nickname', 'roles', 'user_url', 'user_registered', 'description', 'user_nicename', 'user_pass' ), true ) ? $key : 'meta:' . $key;
				if ( 'user_pass' === $target && ! UserFerry_Fields::password_hash_allowed() ) {
					continue;
				}
				if ( ! isset( $lookup[ $norm( $label ) ] ) ) {
					$lookup[ $norm( $label ) ] = $target;
				}
			}
			$synonyms = array(
				'id'            => 'ID',
				'user id'       => 'ID',
				'email'         => 'user_email',
				'e mail'        => 'user_email',
				'email address' => 'user_email',
				'username'      => 'user_login',
				'user name'     => 'user_login',
				'login'         => 'user_login',
				'role'          => 'roles',
				'user role'     => 'roles',
				'website'       => 'user_url',
				'url'           => 'user_url',
				'first'         => 'first_name',
				'firstname'     => 'first_name',
				'last'          => 'last_name',
				'lastname'      => 'last_name',
				'surname'       => 'last_name',
				'bio'           => 'description',
				'password'      => 'plain_password',
			);
			foreach ( $synonyms as $word => $target ) {
				if ( ! isset( $lookup[ $word ] ) ) {
					$lookup[ $word ] = $target;
				}
			}

			$out  = array();
			$used = array();
			foreach ( $headers as $header ) {
				$target = isset( $lookup[ $norm( $header ) ] ) ? $lookup[ $norm( $header ) ] : '';
				if ( 'user_status' === $target || isset( $used[ $target ] ) ) {
					$target = '';
				}
				if ( '' !== $target ) {
					$used[ $target ] = true;
				}
				$out[ $header ] = $target;
			}
			return $out;
		}

		/**
		 * Start a job from an uploaded file.
		 *
		 * @param string $tmp_file  Uploaded temp file.
		 * @param string $file_name Original file name.
		 * @return array|WP_Error
		 */
		public static function start( $tmp_file, $file_name ) {
			$ext = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
			if ( 'tsv' === $ext || 'txt' === $ext ) {
				$ext = 'csv';
			}
			if ( ! in_array( $ext, UserFerry_Import_Reader::extensions(), true ) ) {
				return new WP_Error( 'userferry_import_type', __( 'Use a .csv, .xlsx, .json or .xml file.', 'userferry' ) );
			}
			if ( ! UserFerry_Install::ensure_export_dir() ) {
				return new WP_Error( 'userferry_no_dir', __( 'The plugin folder inside wp-content/uploads could not be created. Check that the uploads folder is writable.', 'userferry' ) );
			}

			$id     = strtolower( wp_generate_password( 20, false, false ) );
			$parsed = UserFerry_Import_Reader::parse( $tmp_file, $ext, self::part_path( $id ) );
			if ( is_wp_error( $parsed ) ) {
				wp_delete_file( self::part_path( $id ) );
				return $parsed;
			}

			self::put(
				$id,
				array(
					'user'      => get_current_user_id(),
					'file_name' => $file_name,
					'format'    => $ext,
					'headers'   => $parsed['headers'],
					'total'     => $parsed['total'],
					'created'   => time(),
					'mapping'   => array(),
					'options'   => array(),
					'position'  => 0,
					'row'       => 1,
					'stats'     => array(
						'created' => 0,
						'updated' => 0,
						'skipped' => 0,
						'failed'  => 0,
					),
					'errors'    => array(),
				)
			);

			return array(
				'id'         => $id,
				'file_name'  => $file_name,
				'format'     => strtoupper( $ext ),
				'headers'    => $parsed['headers'],
				'total'      => $parsed['total'],
				'sample'     => $parsed['sample'],
				'suggestion' => self::suggest( $parsed['headers'] ),
			);
		}

		/**
		 * Save the column mapping and options.
		 *
		 * @param string $id      Job id.
		 * @param array  $mapping column => target.
		 * @param array  $options Raw options.
		 * @return array|WP_Error
		 */
		public static function configure( $id, array $mapping, array $options ) {
			$job = self::load( $id );
			if ( is_wp_error( $job ) ) {
				return $job;
			}

			$valid = array();
			foreach ( self::targets() as $t ) {
				$valid[ $t['key'] ] = true;
			}

			$clean = array();
			$used  = array();
			foreach ( $job['headers'] as $header ) {
				$target = isset( $mapping[ $header ] ) ? trim( (string) $mapping[ $header ] ) : '';
				if ( '' === $target ) {
					continue;
				}
				if ( 0 === strpos( $target, 'meta:' ) ) {
					$meta_key = substr( $target, 5 );
					$meta_key = preg_replace( '/[^A-Za-z0-9_\-]/', '', $meta_key );
					if ( '' === $meta_key || ! UserFerry_Fields::is_exportable_meta_key( $meta_key ) ) {
						/* translators: %s: column name */
						return new WP_Error( 'userferry_import_meta', sprintf( __( 'Column “%s” has an invalid or protected meta key.', 'userferry' ), $header ) );
					}
					$target = 'meta:' . $meta_key;
				} elseif ( ! isset( $valid[ $target ] ) ) {
					continue;
				}
				if ( isset( $used[ $target ] ) ) {
					return new WP_Error( 'userferry_import_dup', __( 'Two columns are mapped to the same field. Each field can be imported only once.', 'userferry' ) );
				}
				$used[ $target ]  = true;
				$clean[ $header ] = $target;
			}

			if ( ! isset( $used['user_email'] ) && ! isset( $used['user_login'] ) && ! isset( $used['ID'] ) ) {
				return new WP_Error( 'userferry_import_identifier', __( 'Map at least one column to Email, Username or User ID so users can be matched or created.', 'userferry' ) );
			}

			$roles        = array_keys( get_editable_roles() );
			$default_role = isset( $options['default_role'] ) ? sanitize_key( $options['default_role'] ) : get_option( 'default_role', 'subscriber' );
			if ( ! in_array( $default_role, $roles, true ) ) {
				$default_role = in_array( 'subscriber', $roles, true ) ? 'subscriber' : (string) reset( $roles );
			}

			$match_by = isset( $options['match_by'] ) ? sanitize_key( $options['match_by'] ) : 'email';

			$job['mapping'] = $clean;
			$job['options'] = array(
				'match_by'     => in_array( $match_by, array( 'email', 'login', 'id' ), true ) ? $match_by : 'email',
				'existing'     => ( isset( $options['existing'] ) && 'skip' === $options['existing'] ) ? 'skip' : 'update',
				'create'       => ! empty( $options['create'] ),
				'default_role' => $default_role,
				'skip_empty'   => ! empty( $options['skip_empty'] ),
				'send_email'   => ! empty( $options['send_email'] ),
			);
			$id             = $job['id'];
			unset( $job['id'] );
			self::put( $id, $job );

			return array( 'total' => (int) $job['total'] );
		}

		/**
		 * Process the next batch of rows.
		 *
		 * @param string $id Job id.
		 * @return array|WP_Error
		 */
		public static function step( $id ) {
			$job = self::load( $id );
			if ( is_wp_error( $job ) ) {
				return $job;
			}
			if ( empty( $job['mapping'] ) ) {
				return new WP_Error( 'userferry_import_unmapped', __( 'Map the columns before starting the import.', 'userferry' ) );
			}

			$handle = fopen( self::part_path( $job['id'] ), 'rb' );
			if ( ! $handle ) {
				return new WP_Error( 'userferry_import_missing', __( 'This import is no longer available. Upload the file again.', 'userferry' ) );
			}
			fseek( $handle, (int) $job['position'] );

			// Don't email users about changes made by a bulk import.
			add_filter( 'send_email_change_email', '__return_false', 99 );
			add_filter( 'send_password_change_email', '__return_false', 99 );

			$batch = max( 10, min( 100, (int) ( userferry_get_option( 'batch_size', 200 ) / 4 ) ) );
			$done  = false;

			for ( $i = 0; $i < $batch; $i++ ) {
				$line = fgets( $handle );
				if ( false === $line ) {
					$done = true;
					break;
				}
				++$job['row'];
				$row = json_decode( $line, true );
				if ( ! is_array( $row ) ) {
					continue;
				}
				$result = self::import_row( $row, $job['mapping'], $job['options'] );
				if ( is_wp_error( $result ) ) {
					++$job['stats']['failed'];
					if ( count( $job['errors'] ) < self::MAX_ERRORS ) {
						$job['errors'][] = array(
							'row'     => (int) $job['row'],
							'message' => $result->get_error_message(),
						);
					}
				} else {
					++$job['stats'][ $result ];
				}
			}

			$job['position'] = (int) ftell( $handle );
			if ( ! $done && false === fgets( $handle ) ) {
				$done = true; // Nothing left after this batch.
			}
			fclose( $handle );

			remove_filter( 'send_email_change_email', '__return_false', 99 );
			remove_filter( 'send_password_change_email', '__return_false', 99 );

			$processed = array_sum( $job['stats'] );
			$job_id    = $job['id'];
			unset( $job['id'] );

			if ( $done ) {
				return self::finish( $job_id, $job );
			}

			self::put( $job_id, $job );

			return array(
				'done'      => false,
				'processed' => $processed,
				'total'     => (int) $job['total'],
			);
		}

		/**
		 * Wrap up: log to history, clean up, return the summary.
		 *
		 * @param string $id  Job id.
		 * @param array  $job Job.
		 * @return array
		 */
		private static function finish( $id, array $job ) {
			self::forget( $id );

			if ( userferry_get_option( 'enable_logs', 1 ) ) {
				UserFerry_History::log(
					array(
						'type'          => 'import',
						'template_id'   => 0,
						'template_name' => '',
						'user_count'    => $job['stats']['created'] + $job['stats']['updated'],
						'field_count'   => count( $job['mapping'] ),
						'file_name'     => $job['file_name'],
						'file_size'     => 0,
						'format'        => $job['format'],
					)
				);
			}

			/**
			 * Fires after an import has finished.
			 *
			 * @param array $stats  { created, updated, skipped, failed }.
			 * @param array $job    Job data (mapping, options, file name).
			 */
			do_action( 'userferry_import_completed', $job['stats'], $job );

			return array(
				'done'      => true,
				'processed' => array_sum( $job['stats'] ),
				'total'     => (int) $job['total'],
				'stats'     => $job['stats'],
				'errors'    => $job['errors'],
				'more'      => max( 0, $job['stats']['failed'] - count( $job['errors'] ) ),
			);
		}

		/**
		 * Map a "Subscriber, Editor" style value to editable role keys.
		 *
		 * @param string $value Raw value.
		 * @return string[]
		 */
		private static function parse_roles( $value ) {
			static $map = null;
			if ( null === $map ) {
				$map = array();
				foreach ( get_editable_roles() as $key => $role ) {
					$map[ strtolower( $key ) ]                                 = $key;
					$map[ strtolower( $role['name'] ) ]                        = $key;
					$map[ strtolower( translate_user_role( $role['name'] ) ) ] = $key;
				}
			}
			$roles = array();
			foreach ( preg_split( '/[,;|]/', (string) $value ) as $part ) {
				$part = strtolower( trim( $part ) );
				if ( '' !== $part && isset( $map[ $part ] ) ) {
					$roles[] = $map[ $part ];
				}
			}
			return array_values( array_unique( $roles ) );
		}

		/**
		 * Convert an exported date to a UTC MySQL datetime.
		 *
		 * @param string $value Date text.
		 * @return string '' when unparseable.
		 */
		private static function parse_date( $value ) {
			$value = trim( (string) $value );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value ) ) {
				return $value; // Raw export format is already UTC.
			}
			if ( is_numeric( $value ) && (float) $value > 20000 && (float) $value < 80000 ) {
				// Excel serial date.
				return gmdate( 'Y-m-d H:i:s', (int) round( ( (float) $value - 25569 ) * DAY_IN_SECONDS ) );
			}
			try {
				$date = new DateTimeImmutable( $value, wp_timezone() );
				return $date->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
			} catch ( Exception $e ) {
				return '';
			}
		}

		/**
		 * Turn a cell into a meta value (JSON arrays/objects come back as arrays).
		 *
		 * @param string $value Cell.
		 * @return mixed
		 */
		private static function meta_value( $value ) {
			$trim = ltrim( $value );
			if ( '' !== $trim && ( '{' === $trim[0] || '[' === $trim[0] ) ) {
				$decoded = json_decode( $value, true );
				if ( is_array( $decoded ) ) {
					return map_deep( $decoded, 'sanitize_text_field' );
				}
			}
			return sanitize_textarea_field( $value );
		}

		/**
		 * Unique username derived from an email address.
		 *
		 * @param string $email Email.
		 * @return string
		 */
		private static function login_from_email( $email ) {
			$base  = sanitize_user( strstr( $email, '@', true ), true );
			$base  = '' === $base ? 'user' : $base;
			$login = $base;
			$n     = 2;
			while ( username_exists( $login ) ) {
				$login = $base . $n;
				++$n;
			}
			return $login;
		}

		/**
		 * Create or update one user.
		 *
		 * @param array $row     column => value.
		 * @param array $mapping column => target.
		 * @param array $options Options.
		 * @return string|WP_Error 'created' | 'updated' | 'skipped'
		 */
		private static function import_row( array $row, array $mapping, array $options ) {
			$core     = array();
			$meta     = array();
			$roles    = null;
			$hash     = '';
			$password = '';
			$match_id = 0;

			foreach ( $mapping as $column => $target ) {
				$value = isset( $row[ $column ] ) ? trim( (string) $row[ $column ] ) : '';
				if ( '' === $value && $options['skip_empty'] ) {
					continue;
				}
				if ( 0 === strpos( $target, 'meta:' ) ) {
					$meta[ substr( $target, 5 ) ] = $value;
					continue;
				}
				switch ( $target ) {
					case 'ID':
						$match_id = absint( $value );
						break;
					case 'roles':
						$roles = self::parse_roles( $value );
						if ( '' !== $value && empty( $roles ) ) {
							/* translators: %s: role text from the file */
							return new WP_Error( 'userferry_import_role', sprintf( __( 'Unknown role “%s”, or a role you are not allowed to assign.', 'userferry' ), $value ) );
						}
						break;
					case 'user_pass':
						$hash = $value;
						break;
					case 'plain_password':
						$password = $value;
						break;
					case 'user_email':
						$core['user_email'] = sanitize_email( $value );
						if ( '' !== $value && ! is_email( $core['user_email'] ) ) {
							/* translators: %s: email address */
							return new WP_Error( 'userferry_import_email', sprintf( __( '“%s” is not a valid email address.', 'userferry' ), $value ) );
						}
						break;
					case 'user_login':
						$core['user_login'] = sanitize_user( $value, true );
						break;
					case 'user_url':
						$core['user_url'] = esc_url_raw( $value );
						break;
					case 'user_nicename':
						$core['user_nicename'] = sanitize_title( $value );
						break;
					case 'description':
						$core['description'] = sanitize_textarea_field( $value );
						break;
					case 'user_registered':
						$core['user_registered'] = self::parse_date( $value );
						break;
					default:
						$core[ $target ] = sanitize_text_field( $value );
				}
			}

			// Find an existing user: chosen identifier first, then the others.
			$existing = false;
			$order    = array( $options['match_by'], 'email', 'login', 'id' );
			foreach ( array_unique( $order ) as $by ) {
				if ( 'email' === $by && ! empty( $core['user_email'] ) ) {
					$existing = get_user_by( 'email', $core['user_email'] );
				} elseif ( 'login' === $by && ! empty( $core['user_login'] ) ) {
					$existing = get_user_by( 'login', $core['user_login'] );
				} elseif ( 'id' === $by && $match_id ) {
					$existing = get_user_by( 'id', $match_id );
				}
				if ( $existing ) {
					break;
				}
			}

			if ( $existing ) {
				if ( 'skip' === $options['existing'] ) {
					return 'skipped';
				}
				if ( ! current_user_can( 'edit_user', $existing->ID ) ) {
					/* translators: %s: username */
					return new WP_Error( 'userferry_import_cap', sprintf( __( 'You are not allowed to edit user “%s”.', 'userferry' ), $existing->user_login ) );
				}
				$is_self = get_current_user_id() === (int) $existing->ID;

				$args = array_diff_key( $core, array_flip( array( 'user_login', 'user_registered' ) ) );
				if ( ! empty( $args['user_email'] ) ) {
					$owner = email_exists( $args['user_email'] );
					if ( $owner && (int) $owner !== (int) $existing->ID ) {
						/* translators: %s: email address */
						return new WP_Error( 'userferry_import_email_taken', sprintf( __( 'The email %s already belongs to another user.', 'userferry' ), $args['user_email'] ) );
					}
				}
				if ( '' !== $password && ! $is_self ) {
					$args['user_pass'] = $password;
				}
				if ( ! empty( $args ) || '' !== $password ) {
					$args['ID'] = $existing->ID;
					$result     = wp_update_user( $args );
					if ( is_wp_error( $result ) ) {
						return $result;
					}
				}
				$user_id = (int) $existing->ID;
				if ( null !== $roles && ! empty( $roles ) && ! $is_self ) {
					self::apply_roles( $user_id, $roles );
				}
				$status = 'updated';
			} else {
				if ( ! $options['create'] ) {
					return 'skipped';
				}
				if ( empty( $core['user_login'] ) ) {
					if ( empty( $core['user_email'] ) ) {
						return new WP_Error( 'userferry_import_no_id', __( 'A new user needs a username or an email address.', 'userferry' ) );
					}
					$core['user_login'] = self::login_from_email( $core['user_email'] );
				}
				if ( username_exists( $core['user_login'] ) ) {
					/* translators: %s: username */
					return new WP_Error( 'userferry_import_login_taken', sprintf( __( 'The username %s is already taken by a different user.', 'userferry' ), $core['user_login'] ) );
				}
				if ( ! empty( $core['user_email'] ) && email_exists( $core['user_email'] ) ) {
					/* translators: %s: email address */
					return new WP_Error( 'userferry_import_email_taken', sprintf( __( 'The email %s already belongs to another user.', 'userferry' ), $core['user_email'] ) );
				}
				if ( isset( $core['user_registered'] ) && '' === $core['user_registered'] ) {
					unset( $core['user_registered'] );
				}

				$args              = $core;
				$args['user_pass'] = '' !== $password ? $password : wp_generate_password( 24 );
				$args['role']      = ! empty( $roles ) ? $roles[0] : $options['default_role'];

				$user_id = wp_insert_user( $args );
				if ( is_wp_error( $user_id ) ) {
					return $user_id;
				}
				if ( ! empty( $roles ) && count( $roles ) > 1 ) {
					self::apply_roles( $user_id, $roles );
				}
				if ( $options['send_email'] ) {
					wp_new_user_notification( $user_id, null, 'user' );
				}
				$status = 'created';
			}

			// Password hash (moving users between WordPress sites).
			if ( '' !== $hash && UserFerry_Fields::password_hash_allowed() && get_current_user_id() !== (int) $user_id && preg_match( '/^\$(P|H|2y|wp|argon2)/', $hash ) ) {
				global $wpdb;
				$wpdb->update( $wpdb->users, array( 'user_pass' => $hash ), array( 'ID' => $user_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Core has no API to store an existing hash; cache is cleared below.
				clean_user_cache( $user_id );
			}

			foreach ( $meta as $key => $value ) {
				if ( '' === $value ) {
					delete_user_meta( $user_id, $key );
				} else {
					update_user_meta( $user_id, $key, self::meta_value( $value ) );
				}
			}

			return $status;
		}

		/**
		 * Replace a user's roles.
		 *
		 * @param int      $user_id User id.
		 * @param string[] $roles   Role keys.
		 */
		private static function apply_roles( $user_id, array $roles ) {
			$user = get_userdata( $user_id );
			if ( ! $user ) {
				return;
			}
			$user->set_role( array_shift( $roles ) );
			foreach ( $roles as $role ) {
				$user->add_role( $role );
			}
		}

		/**
		 * Delete abandoned jobs (older than a day).
		 */
		public static function purge_stale() {
			foreach ( self::all() as $id => $job ) {
				if ( empty( $job['created'] ) || $job['created'] < time() - DAY_IN_SECONDS ) {
					self::forget( $id );
				}
			}
		}
	}
}
