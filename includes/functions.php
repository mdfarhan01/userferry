<?php
/**
 * Global procedural helpers.
 * Every function name is prefixed with userferry_ to avoid any collision with
 * other plugins' global function names.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'userferry_get_settings' ) ) {
	/**
	 * Merged (saved + defaults) settings array.
	 *
	 * @return array
	 */
	function userferry_get_settings() {
		return UserFerry_Settings::get();
	}
}

if ( ! function_exists( 'userferry_get_option' ) ) {
	/**
	 * Read a single setting.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Fallback value.
	 * @return mixed
	 */
	function userferry_get_option( $key, $fallback = '' ) {
		$value = UserFerry_Settings::get( $key );
		return null === $value ? $fallback : $value;
	}
}

if ( ! function_exists( 'userferry_capability' ) ) {
	/**
	 * Capability required to use the exporter.
	 *
	 * @return string
	 */
	function userferry_capability() {
		/**
		 * Filters the capability required to use UserFerry.
		 *
		 * @param string $capability Default 'list_users'.
		 */
		return (string) apply_filters( 'userferry_capability', 'list_users' );
	}
}

if ( ! function_exists( 'userferry_can_import' ) ) {
	/**
	 * Whether the current user may import (create and edit) users.
	 *
	 * @return bool
	 */
	function userferry_can_import() {
		return current_user_can( 'create_users' ) && current_user_can( 'edit_users' );
	}
}

if ( ! function_exists( 'userferry_admin_url' ) ) {
	/**
	 * Admin URL of one of the plugin screens.
	 *
	 * @param string $slug Page slug.
	 * @param array  $args Extra query args.
	 * @return string
	 */
	function userferry_admin_url( $slug, array $args = array() ) {
		return add_query_arg( array_merge( array( 'page' => $slug ), $args ), admin_url( 'admin.php' ) );
	}
}

if ( ! function_exists( 'userferry_asset_url' ) ) {
	/**
	 * URL inside the plugin's /assets/ folder.
	 *
	 * @param string $path Relative path, e.g. 'css/admin.css'.
	 * @return string
	 */
	function userferry_asset_url( $path ) {
		return USERFERRY_ASSETS_URL . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'userferry_asset_version' ) ) {
	/**
	 * Cache-busting version for one asset: the file's modification time, so
	 * browsers pick up CSS/JS changes without a manual version bump.
	 *
	 * @param string $relative_path Path inside /assets/.
	 * @return string
	 */
	function userferry_asset_version( $relative_path ) {
		$file = USERFERRY_PATH . 'assets/' . ltrim( $relative_path, '/' );
		$time = file_exists( $file ) ? filemtime( $file ) : false;
		return $time ? (string) $time : USERFERRY_VERSION;
	}
}

if ( ! function_exists( 'userferry_get_template' ) ) {
	/**
	 * Load a template file from /templates/ in its own scope.
	 *
	 * @param string $template_name File name inside /templates/ (no extension).
	 * @param array  $args          Values available to the template as $args.
	 */
	function userferry_get_template( $template_name, $args = array() ) {
		$file = USERFERRY_PATH . 'templates/' . $template_name . '.php';
		if ( ! file_exists( $file ) ) {
			return;
		}
		$args = is_array( $args ) ? $args : array();
		include $file;
	}
}

if ( ! function_exists( 'userferry_admin_pages' ) ) {
	/**
	 * Registry of every admin screen: slug => label, icon, capability, template.
	 * Adding a screen only means adding one row here plus its template.
	 *
	 * @return array
	 */
	function userferry_admin_pages() {
		$cap = userferry_capability();
		return array(
			'userferry'           => array(
				'label'    => __( 'Dashboard', 'userferry' ),
				'title'    => __( 'Dashboard', 'userferry' ),
				'subtitle' => __( 'Overview of your users, templates and recent activity.', 'userferry' ),
				'icon'     => 'dashicons-dashboard',
				'cap'      => $cap,
				'template' => 'admin/dashboard',
			),
			'userferry-export'    => array(
				'label'    => __( 'Export users', 'userferry' ),
				'title'    => __( 'Export users', 'userferry' ),
				'subtitle' => __( 'Choose who to include, which fields become columns, and the file format.', 'userferry' ),
				'icon'     => 'dashicons-upload',
				'cap'      => $cap,
				'template' => 'admin/export',
			),
			'userferry-import'    => array(
				'label'    => __( 'Import users', 'userferry' ),
				'title'    => __( 'Import users', 'userferry' ),
				'subtitle' => __( 'Create or update users from a CSV, Excel, JSON or XML file — including files exported by this plugin.', 'userferry' ),
				'icon'     => 'dashicons-download',
				'cap'      => 'create_users',
				'template' => 'admin/import',
			),
			'userferry-templates' => array(
				'label'    => __( 'Templates', 'userferry' ),
				'title'    => __( 'Export templates', 'userferry' ),
				'subtitle' => __( 'Saved users, fields and format — run them again with one click.', 'userferry' ),
				'icon'     => 'dashicons-portfolio',
				'cap'      => $cap,
				'template' => 'admin/templates',
			),
			'userferry-history'   => array(
				'label'    => __( 'History', 'userferry' ),
				'title'    => __( 'History', 'userferry' ),
				'subtitle' => __( 'Every export and import, with downloads for export files.', 'userferry' ),
				'icon'     => 'dashicons-backup',
				'cap'      => $cap,
				'template' => 'admin/history',
			),
			'userferry-settings'  => array(
				'label'    => __( 'Settings', 'userferry' ),
				'title'    => __( 'Settings', 'userferry' ),
				'subtitle' => __( 'Defaults for exports and imports, file output, history and privacy.', 'userferry' ),
				'icon'     => 'dashicons-admin-generic',
				'cap'      => 'manage_options',
				'template' => 'admin/settings',
			),
			'userferry-docs'      => array(
				'label'    => __( 'Documentation', 'userferry' ),
				'title'    => __( 'Documentation', 'userferry' ),
				'subtitle' => __( 'Everything you need to export, import and keep user data safe.', 'userferry' ),
				'icon'     => 'dashicons-book-alt',
				'cap'      => $cap,
				'template' => 'admin/docs',
			),
		);
	}
}
