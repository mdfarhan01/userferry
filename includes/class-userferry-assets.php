<?php
/**
 * Registers/enqueues all CSS + JS. Every handle is prefixed with `userferry-`
 * and assets load only on this plugin's own screens.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Assets' ) ) {

	/**
	 * Class UserFerry_Assets
	 */
	class UserFerry_Assets {

		/**
		 * Enqueue assets for the plugin's admin screens only.
		 */
		public function admin_assets() {
			$page = UserFerry_Helper::current_page();
			if ( ! $page ) {
				return;
			}

			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style( 'userferry-admin', userferry_asset_url( 'css/admin.css' ), array(), userferry_asset_version( 'css/admin.css' ) );
			wp_enqueue_script( 'userferry-admin', userferry_asset_url( 'js/admin.js' ), array( 'jquery' ), userferry_asset_version( 'js/admin.js' ), true );

			$settings  = userferry_get_settings();
			$templates = in_array( $page, array( 'userferry', 'userferry-export', 'userferry-templates' ), true ) ? UserFerry_Templates::all() : array();
			$indexed   = array();
			foreach ( $templates as $template ) {
				$indexed[ $template['id'] ] = $template;
			}

			$preload = 0;
			if ( 'userferry-export' === $page ) {
				// Read-only: which saved template to pre-fill (no data is changed).
				$preload = absint( filter_input( INPUT_GET, 'template', FILTER_SANITIZE_NUMBER_INT ) );
			}

			wp_localize_script(
				'userferry-admin',
				'USERFERRY',
				array(
					'ajaxUrl'         => admin_url( 'admin-ajax.php' ),
					'nonce'           => wp_create_nonce( 'userferry_nonce' ),
					'page'            => $page,
					'templates'       => (object) $indexed,
					'preloadTemplate' => isset( $indexed[ $preload ] ) ? $preload : 0,
					'defaultFields'   => UserFerry_Fields::default_selection(),
					'defaultFormat'   => $settings['default_format'],
					'defaultFilename' => $settings['default_filename'],
					'roles'           => (object) array_map( 'translate_user_role', wp_roles()->get_names() ),
					'importTargets'   => 'userferry-import' === $page ? UserFerry_Import_Job::targets() : array(),
					'maxUpload'       => wp_max_upload_size(),
					'maxUploadLabel'  => size_format( wp_max_upload_size() ),
					'urls'            => array(
						'export'    => userferry_admin_url( 'userferry-export' ),
						'import'    => userferry_admin_url( 'userferry-import' ),
						'templates' => userferry_admin_url( 'userferry-templates' ),
						'history'   => userferry_admin_url( 'userferry-history' ),
						'users'     => admin_url( 'users.php' ),
					),
					'i18n'            => self::js_strings(),
				)
			);
		}

		/**
		 * Translatable strings used by admin.js.
		 *
		 * @return array
		 */
		private static function js_strings() {
			return array(
				'requestFailed'     => __( 'The server did not respond. Check your connection and try again.', 'userferry' ),
				'genericError'      => __( 'Something went wrong. Reload the page and try again.', 'userferry' ),
				'scanning'          => __( 'Scanning…', 'userferry' ),
				'rescan'            => __( 'Scan custom fields', 'userferry' ),
				/* translators: %s: number of custom fields */
				'scanDone'          => __( 'Scan complete — %s custom fields found.', 'userferry' ),
				/* translators: %s: time of the last scan */
				'scannedAt'         => __( 'Fields detected automatically · last scanned %s', 'userferry' ),
				/* translators: 1: number selected, 2: total number */
				'fieldsSelected'    => __( '%1$s of %2$s fields selected', 'userferry' ),
				'noFieldMatch'      => __( 'No fields match that filter.', 'userferry' ),
				'noColumns'         => __( 'Tick fields above. Each one becomes a column here, in this order.', 'userferry' ),
				'removeColumn'      => __( 'Remove column', 'userferry' ),
				'notOnSite'         => __( 'not found on this site', 'userferry' ),
				'show'              => __( 'Show', 'userferry' ),
				'hide'              => __( 'Hide', 'userferry' ),
				'loadingUsers'      => __( 'Loading users…', 'userferry' ),
				'noUsers'           => __( 'No users match this search.', 'userferry' ),
				/* translators: 1: current page, 2: total pages, 3: total users */
				'pageOf'            => __( 'Page %1$s of %2$s · %3$s users', 'userferry' ),
				/* translators: %s: number of users */
				'usersMatch'        => __( '%s users match', 'userferry' ),
				'oneUserMatch'      => __( '1 user matches', 'userferry' ),
				'counting'          => __( 'Counting users…', 'userferry' ),
				'pickUsers'         => __( 'Select at least one user, or choose a different option.', 'userferry' ),
				'pickRoles'         => __( 'Select at least one role.', 'userferry' ),
				'pickDates'         => __( 'Enter a start or end date for the custom range.', 'userferry' ),
				'pickFields'        => __( 'Choose at least one field to export.', 'userferry' ),
				'nameRequired'      => __( 'Give the template a name.', 'userferry' ),
				'templateSaved'     => __( 'Template saved.', 'userferry' ),
				'templateUpdated'   => __( 'Template updated.', 'userferry' ),
				'templateDeleted'   => __( 'Template deleted.', 'userferry' ),
				'templateCopied'    => __( 'Template duplicated.', 'userferry' ),
				/* translators: %s: template name */
				'templateLoaded'    => __( 'Loaded template “%s”. Its fields are ticked below — adjust anything you like, then export.', 'userferry' ),
				/* translators: %s: template name */
				'updateTemplate'    => __( 'Update “%s”', 'userferry' ),
				'newTemplate'       => __( 'New template', 'userferry' ),
				'editTemplate'      => __( 'Edit template', 'userferry' ),
				'saveTemplate'      => __( 'Save template', 'userferry' ),
				'saveChanges'       => __( 'Save changes', 'userferry' ),
				'saving'            => __( 'Saving…', 'userferry' ),
				'deleteTemplateQ'   => __( 'Delete this template?', 'userferry' ),
				/* translators: %s: template name */
				'deleteTemplateMsg' => __( '“%s” will be removed. Past exports in History are not affected.', 'userferry' ),
				'delete'            => __( 'Delete', 'userferry' ),
				'cancel'            => __( 'Cancel', 'userferry' ),
				'close'             => __( 'Close', 'userferry' ),
				'deleteExportQ'     => __( 'Delete this export?', 'userferry' ),
				'deleteExportMsg'   => __( 'The history entry and its file will be permanently removed.', 'userferry' ),
				'clearHistoryQ'     => __( 'Delete all exports?', 'userferry' ),
				'clearHistoryMsg'   => __( 'Every history entry and every export file will be permanently removed. Templates are kept.', 'userferry' ),
				'deleteAll'         => __( 'Delete everything', 'userferry' ),
				'historyCleared'    => __( 'All exports deleted.', 'userferry' ),
				'exportDeleted'     => __( 'Export deleted.', 'userferry' ),
				'preparing'         => __( 'Preparing export…', 'userferry' ),
				/* translators: 1: processed users, 2: total users */
				'progress'          => __( 'Exported %1$s of %2$s users', 'userferry' ),
				'writing'           => __( 'Writing file…', 'userferry' ),
				'exportDone'        => __( 'Export ready', 'userferry' ),
				/* translators: 1: number of users, 2: file size */
				'exportDoneMsg'     => __( '%1$s users exported · %2$s. Your download should start automatically.', 'userferry' ),
				'download'          => __( 'Download file', 'userferry' ),
				'exportFailed'      => __( 'Export failed', 'userferry' ),
				'runExport'         => __( 'Run export', 'userferry' ),
				'exporting'         => __( 'Exporting…', 'userferry' ),
				/* translators: %s: template name */
				'exportingTemplate' => __( 'Exporting “%s”', 'userferry' ),
				'viewHistory'       => __( 'View history', 'userferry' ),
				'allUsers'          => __( 'All users', 'userferry' ),
				/* translators: %s: number of users */
				'selectedUsers'     => __( '%s selected users', 'userferry' ),
				/* translators: %s: role list */
				'roleSummary'       => __( 'Role: %s', 'userferry' ),
				'today'             => __( 'Registered today', 'userferry' ),
				'last7'             => __( 'Registered in the last 7 days', 'userferry' ),
				'last30'            => __( 'Registered in the last 30 days', 'userferry' ),
				/* translators: 1: start date, 2: end date */
				'customRange'       => __( 'Registered %1$s to %2$s', 'userferry' ),
				/* translators: %s: search term */
				'matching'          => __( 'matching “%s”', 'userferry' ),
				'ascending'         => __( 'ascending', 'userferry' ),
				'descending'        => __( 'descending', 'userferry' ),
				/* translators: %s: number of fields */
				'nFields'           => __( '%s fields', 'userferry' ),
				'scanCustom'        => __( 'Scan custom fields', 'userferry' ),
				/* translators: %s: time of the last scan */
				'lastScan'          => __( 'Last scanned %s', 'userferry' ),
				'uploadPick'        => __( 'Choose a file to import.', 'userferry' ),
				'uploading'         => __( 'Reading file…', 'userferry' ),
				'badType'           => __( 'Use a .csv, .xlsx, .json or .xml file.', 'userferry' ),
				/* translators: %s: maximum upload size */
				'tooBig'            => __( 'That file is larger than the %s upload limit.', 'userferry' ),
				/* translators: %s: number of rows */
				'rowsCount'         => __( '%s rows', 'userferry' ),
				/* translators: %s: sort column and direction, e.g. "User ID, ascending" */
				'sortedBy'          => __( 'Sorted by %s', 'userferry' ),
				'edit'              => __( 'Edit', 'userferry' ),
				'columnsInFile'     => __( 'Columns in the file, in this order', 'userferry' ),
				'matched'           => __( 'Auto-matched', 'userferry' ),
				'skipColumn'        => __( 'Don’t import', 'userferry' ),
				'newMeta'           => __( 'New custom field…', 'userferry' ),
				'metaKeyPh'         => __( 'meta_key', 'userferry' ),
				'groupIdentify'     => __( 'Identify users', 'userferry' ),
				'groupProfile'      => __( 'WordPress profile', 'userferry' ),
				'groupPassword'     => __( 'Password', 'userferry' ),
				'groupCustom'       => __( 'Custom fields', 'userferry' ),
				'groupHidden'       => __( 'Hidden & plugin data', 'userferry' ),
				'needIdentifier'    => __( 'Map at least one column to Email, Username or User ID so users can be matched or created.', 'userferry' ),
				'dupTarget'         => __( 'Two columns are mapped to the same field. Each field can be imported only once.', 'userferry' ),
				'badMetaKey'        => __( 'Enter a meta key (letters, numbers, underscores and dashes) for every “New custom field” column.', 'userferry' ),
				/* translators: 1: number of rows, 2: file format */
				'fileSummary'       => __( '%1$s rows · %2$s', 'userferry' ),
				/* translators: 1: number of mapped columns, 2: total columns */
				'mappedCount'       => __( '%1$s of %2$s columns will be imported', 'userferry' ),
				'runImport'         => __( 'Start import', 'userferry' ),
				'importing'         => __( 'Importing…', 'userferry' ),
				/* translators: 1: processed rows, 2: total rows */
				'importProgress'    => __( 'Processed %1$s of %2$s rows', 'userferry' ),
				'importDone'        => __( 'Import complete', 'userferry' ),
				'importFailed'      => __( 'Import stopped', 'userferry' ),
				'created'           => __( 'Created', 'userferry' ),
				'updated'           => __( 'Updated', 'userferry' ),
				'skipped'           => __( 'Skipped', 'userferry' ),
				'failed'            => __( 'Failed', 'userferry' ),
				'row'               => __( 'Row', 'userferry' ),
				'problem'           => __( 'Problem', 'userferry' ),
				/* translators: %s: number of further problems not listed */
				'moreProblems'      => __( '…and %s more rows with problems.', 'userferry' ),
				'importAnother'     => __( 'Import another file', 'userferry' ),
				'viewUsers'         => __( 'View users', 'userferry' ),
			);
		}
	}
}
