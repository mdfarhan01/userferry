<?php
/**
 * Chunked export jobs.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Export_Job' ) ) {

	/**
	 * Runs an export in small batches over several AJAX requests so large sites
	 * never hit PHP time or memory limits. Rows are appended to a temporary
	 * ".part" file and converted to the final format at the end.
	 */
	class UserFerry_Export_Job {

		const OPTION = 'userferry_jobs';

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
		 * Persist jobs.
		 *
		 * @param array $jobs Jobs.
		 */
		private static function save_all( array $jobs ) {
			update_option( self::OPTION, $jobs, false );
		}

		/**
		 * Path of a job's temporary file.
		 *
		 * @param string $id Job id.
		 * @return string
		 */
		private static function part_path( $id ) {
			return UserFerry_Install::export_dir() . '/job-' . $id . '.part';
		}

		/**
		 * Load a job that belongs to the current user.
		 *
		 * @param string $id Job id.
		 * @return array|\WP_Error
		 */
		private static function load( $id ) {
			$id   = preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $id ) );
			$jobs = self::all();
			if ( '' === $id || ! isset( $jobs[ $id ] ) || get_current_user_id() !== (int) $jobs[ $id ]['user'] ) {
				return new \WP_Error( 'userferry_job_missing', __( 'This export is no longer available. Please start it again.', 'userferry' ) );
			}
			$jobs[ $id ]['id'] = $id;
			return $jobs[ $id ];
		}

		/**
		 * Start a job.
		 *
		 * @param array $config { filters, fields, format, filename, template_id, template_name }.
		 * @return array|\WP_Error
		 */
		public static function start( array $config ) {
			if ( empty( $config['fields'] ) ) {
				return new \WP_Error( 'userferry_no_fields', __( 'Choose at least one field to export.', 'userferry' ) );
			}
			if ( 'xlsx' === $config['format'] && ! class_exists( 'ZipArchive' ) ) {
				return new \WP_Error( 'userferry_no_zip', __( 'Excel export needs the PHP ZipArchive extension, which is not installed on this server. Choose CSV instead — it opens in Excel too.', 'userferry' ) );
			}
			if ( ! UserFerry_Install::ensure_export_dir() ) {
				return new \WP_Error( 'userferry_no_dir', __( 'The exports folder inside wp-content/uploads could not be created. Check that the uploads folder is writable.', 'userferry' ) );
			}

			$total = UserFerry_Export_Query::count( $config['filters'] );
			if ( $total < 1 ) {
				return new \WP_Error( 'userferry_no_users', __( 'No users match these filters. Change who is included and try again.', 'userferry' ) );
			}

			$id = strtolower( wp_generate_password( 20, false, false ) );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Temporary data file inside our protected uploads sub-folder.
			if ( false === file_put_contents( self::part_path( $id ), '' ) ) {
				return new \WP_Error( 'userferry_write_failed', __( 'Could not write to the exports folder.', 'userferry' ) );
			}

			$jobs        = self::all();
			$jobs[ $id ] = array(
				'user'    => get_current_user_id(),
				'config'  => $config,
				'total'   => $total,
				'offset'  => 0,
				'written' => 0,
				'created' => time(),
			);
			self::save_all( $jobs );

			return array(
				'id'    => $id,
				'total' => $total,
			);
		}

		/**
		 * Process the next batch.
		 *
		 * @param string $id Job id.
		 * @return array|\WP_Error
		 */
		public static function step( $id ) {
			$job = self::load( $id );
			if ( is_wp_error( $job ) ) {
				return $job;
			}

			$batch  = max( 25, (int) UserFerry_Settings::get( 'batch_size' ) );
			$users  = UserFerry_Export_Query::users( $job['config']['filters'], $job['offset'], $batch );
			$fields = $job['config']['fields'];

			if ( ! empty( $users ) ) {
				$lines = '';
				foreach ( $users as $user ) {
					$lines .= wp_json_encode( UserFerry_Export_Query::row( $user, $fields ) ) . "\n";
				}
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- Appending rows to the job's temporary file.
				if ( false === file_put_contents( self::part_path( $job['id'] ), $lines, FILE_APPEND | LOCK_EX ) ) {
					return new \WP_Error( 'userferry_write_failed', __( 'Could not write to the exports folder.', 'userferry' ) );
				}
			}

			$job['offset']  += $batch;
			$job['written'] += count( $users );
			$done            = empty( $users ) || $job['offset'] >= $job['total'];

			$jobs               = self::all();
			$jobs[ $job['id'] ] = array_diff_key( $job, array( 'id' => true ) );
			self::save_all( $jobs );

			return array(
				'processed' => min( $job['written'], $job['total'] ),
				'total'     => $job['total'],
				'done'      => $done,
			);
		}

		/**
		 * Write the final file, log it and clean up.
		 *
		 * @param string $id Job id.
		 * @return array|\WP_Error
		 */
		public static function finish( $id ) {
			$job = self::load( $id );
			if ( is_wp_error( $job ) ) {
				return $job;
			}

			$config  = $job['config'];
			$labels  = UserFerry_Fields::all_labels();
			$headers = array();
			foreach ( $config['fields'] as $key ) {
				$headers[ $key ] = isset( $labels[ $key ] ) ? $labels[ $key ] : $key;
			}

			$base = $config['filename'] . '-' . wp_date( 'Ymd-His' ) . '-' . strtolower( wp_generate_password( 24, false, false ) );
			$part = self::part_path( $job['id'] );
			$file = UserFerry_Export_Writer::write( $config['format'], $part, UserFerry_Install::export_dir() . '/' . $base, $headers );

			wp_delete_file( $part );
			self::forget( $job['id'] );

			if ( is_wp_error( $file ) ) {
				return $file;
			}

			$file_name  = basename( $file );
			$history_id = 0;

			if ( UserFerry_Settings::get( 'enable_logs' ) ) {
				$history_id = UserFerry_History::log(
					array(
						'template_id'   => (int) $config['template_id'],
						'template_name' => (string) $config['template_name'],
						'user_count'    => (int) $job['written'],
						'field_count'   => count( $config['fields'] ),
						'file_name'     => $file_name,
						'file_size'     => (int) filesize( $file ),
						'format'        => $config['format'],
					)
				);
			}

			/**
			 * Fires after an export file has been written.
			 *
			 * @param string $file   Absolute file path.
			 * @param array  $config Export configuration.
			 * @param int    $count  Number of users exported.
			 */
			do_action( 'userferry_export_completed', $file, $config, (int) $job['written'] );

			return array(
				'user_count'   => (int) $job['written'],
				'file_name'    => $file_name,
				'file_size'    => size_format( (int) filesize( $file ) ),
				'download_url' => UserFerry_Export_Download::url( $file_name, $history_id ),
			);
		}

		/**
		 * Remove a job record.
		 *
		 * @param string $id Job id.
		 */
		private static function forget( $id ) {
			$jobs = self::all();
			unset( $jobs[ $id ] );
			self::save_all( $jobs );
		}

		/**
		 * Delete jobs (and their temp files) abandoned for more than a day.
		 */
		public static function purge_stale() {
			$jobs    = self::all();
			$changed = false;
			foreach ( $jobs as $id => $job ) {
				if ( empty( $job['created'] ) || $job['created'] < time() - DAY_IN_SECONDS ) {
					wp_delete_file( self::part_path( $id ) );
					unset( $jobs[ $id ] );
					$changed = true;
				}
			}
			if ( $changed ) {
				self::save_all( $jobs );
			}
		}
	}
}
