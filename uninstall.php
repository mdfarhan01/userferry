<?php
/**
 * Fires when the plugin is deleted from wp-admin > Plugins.
 * Data is removed only if "Delete all plugin data on uninstall" is enabled,
 * and only data that belongs to UserFerry is touched.
 *
 * @package UserFerry
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove plugin data for the current site.
 */
function userferry_uninstall_site() {
	global $wpdb;

	wp_clear_scheduled_hook( 'userferry_daily_cleanup' );
	delete_option( 'userferry_activation_redirect' );

	$settings = get_option( 'userferry_settings', array() );
	if ( empty( $settings['delete_data_on_uninstall'] ) ) {
		return;
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery,WordPress.DB.DirectDatabaseQuery.SchemaChange -- Dropping this plugin's own tables on uninstall.
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'userferry_templates' ) );
	$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $wpdb->prefix . 'userferry_history' ) );
	// phpcs:enable

	delete_option( 'userferry_settings' );
	delete_option( 'userferry_db_version' );
	delete_option( 'userferry_jobs' );
	delete_option( 'userferry_import_jobs' );
	delete_transient( 'userferry_meta_keys' );

	delete_transient( 'userferry_storage_check' );

	$upload = wp_upload_dir( null, false );
	$dirs   = (array) glob( trailingslashit( $upload['basedir'] ) . 'userferry-exports*', GLOB_ONLYDIR );
	if ( defined( 'USERFERRY_EXPORT_DIR' ) && is_string( USERFERRY_EXPORT_DIR ) && is_dir( USERFERRY_EXPORT_DIR ) ) {
		$dirs[] = USERFERRY_EXPORT_DIR;
	}
	foreach ( $dirs as $dir ) {
		$files = array_merge( (array) glob( $dir . '/*' ), (array) glob( $dir . '/.htaccess' ) );
		foreach ( $files as $file ) {
			if ( is_file( $file ) ) {
				wp_delete_file( $file );
			}
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- Removing the plugin's own, now empty, folder.
		rmdir( $dir );
	}
	delete_option( 'userferry_storage_key' );
}

if ( is_multisite() ) {
	foreach ( get_sites( array( 'fields' => 'ids' ) ) as $userferry_site_id ) {
		switch_to_blog( $userferry_site_id );
		userferry_uninstall_site();
		restore_current_blog();
	}
} else {
	userferry_uninstall_site();
}
