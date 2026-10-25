<?php
/**
 * Scheduled housekeeping.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Cron' ) ) {

	/**
	 * Daily cleanup of old exports and abandoned export jobs.
	 */
	class UserFerry_Cron {

		const HOOK = 'userferry_daily_cleanup';

		/**
		 * Schedule the daily event if missing.
		 */
		public static function schedule() {
			if ( ! wp_next_scheduled( self::HOOK ) ) {
				wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
			}
		}

		/**
		 * Remove the scheduled event.
		 */
		public static function unschedule() {
			wp_clear_scheduled_hook( self::HOOK );
		}

		/**
		 * Cleanup routine.
		 */
		public static function run() {
			$days = (int) UserFerry_Settings::get( 'retention_days' );
			if ( $days > 0 ) {
				UserFerry_History::purge_older_than( $days );
				self::delete_orphan_files( $days );
			}
			UserFerry_Export_Job::purge_stale();
			UserFerry_Import_Job::purge_stale();
			UserFerry_Storage_Check::result( true );
		}

		/**
		 * Delete export files (not tracked in history) older than N days.
		 *
		 * @param int $days Age in days.
		 */
		private static function delete_orphan_files( $days ) {
			$dir = UserFerry_Install::export_dir();
			if ( ! is_dir( $dir ) ) {
				return;
			}
			$cutoff = time() - ( $days * DAY_IN_SECONDS );
			foreach ( (array) glob( $dir . '/*' ) as $file ) {
				$ext = strtolower( pathinfo( (string) $file, PATHINFO_EXTENSION ) );
				if ( ! in_array( $ext, array( 'csv', 'xlsx', 'json', 'xml' ), true ) ) {
					continue;
				}
				if ( filemtime( $file ) < $cutoff ) {
					wp_delete_file( $file );
				}
			}
		}
	}
}
