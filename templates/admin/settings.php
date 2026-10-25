<?php
/**
 * Settings screen.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$userferry_s = UserFerry_Settings::get();

/**
 * Print a switch row.
 *
 * @param string $key   Setting key.
 * @param string $label Label.
 * @param string $help  Help text.
 * @param array  $s     Settings.
 */
$userferry_switch = static function ( $key, $label, $help, $s ) {
	?>
	<div class="userferry-setting">
		<div class="userferry-setting__text">
			<label for="userferry-set-<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
			<p><?php echo esc_html( $help ); ?></p>
		</div>
		<span class="userferry-switch">
			<input type="checkbox" id="userferry-set-<?php echo esc_attr( $key ); ?>" name="userferry[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( 1, (int) $s[ $key ] ); ?>>
			<span aria-hidden="true"></span>
		</span>
	</div>
	<?php
};
?>

<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="userferry-settings">
	<input type="hidden" name="action" value="userferry_save_settings">
	<?php wp_nonce_field( 'userferry_save_settings', 'userferry_settings_nonce' ); ?>

	<section class="userferry-panel">
		<div class="userferry-panel__head"><h2><?php esc_html_e( 'Export defaults', 'userferry' ); ?></h2></div>
		<div class="userferry-setting">
			<div class="userferry-setting__text">
				<label for="userferry-set-format"><?php esc_html_e( 'Default format', 'userferry' ); ?></label>
				<p><?php esc_html_e( 'Pre-selected in the wizard and for new templates.', 'userferry' ); ?></p>
			</div>
			<select id="userferry-set-format" name="userferry[default_format]" class="userferry-select">
				<?php foreach ( UserFerry_Export_Writer::formats() as $userferry_key => $userferry_f ) : ?>
					<option value="<?php echo esc_attr( $userferry_key ); ?>" <?php selected( $userferry_s['default_format'], $userferry_key ); ?>><?php echo esc_html( $userferry_f['label'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="userferry-setting">
			<div class="userferry-setting__text">
				<label for="userferry-set-filename"><?php esc_html_e( 'Default file name', 'userferry' ); ?></label>
				<p><?php esc_html_e( 'Letters, numbers, dots, dashes and underscores.', 'userferry' ); ?></p>
			</div>
			<input type="text" id="userferry-set-filename" name="userferry[default_filename]" class="userferry-input" value="<?php echo esc_attr( $userferry_s['default_filename'] ); ?>" maxlength="80">
		</div>
		<div class="userferry-setting">
			<div class="userferry-setting__text">
				<label for="userferry-set-batch"><?php esc_html_e( 'Users per batch', 'userferry' ); ?></label>
				<p><?php esc_html_e( 'Exports run in batches so large sites never time out. Lower this on slow hosting; raise it on fast servers. 25–2000.', 'userferry' ); ?></p>
			</div>
			<input type="number" id="userferry-set-batch" name="userferry[batch_size]" class="userferry-input userferry-input--short" value="<?php echo esc_attr( $userferry_s['batch_size'] ); ?>" min="25" max="2000" step="25">
		</div>
	</section>

	<section class="userferry-panel">
		<div class="userferry-panel__head"><h2><?php esc_html_e( 'File output', 'userferry' ); ?></h2></div>
		<div class="userferry-setting">
			<div class="userferry-setting__text">
				<label for="userferry-set-delimiter"><?php esc_html_e( 'CSV separator', 'userferry' ); ?></label>
				<p><?php esc_html_e( 'Many European versions of Excel expect a semicolon.', 'userferry' ); ?></p>
			</div>
			<select id="userferry-set-delimiter" name="userferry[csv_delimiter]" class="userferry-select">
				<option value="comma" <?php selected( $userferry_s['csv_delimiter'], 'comma' ); ?>><?php esc_html_e( 'Comma ( , )', 'userferry' ); ?></option>
				<option value="semicolon" <?php selected( $userferry_s['csv_delimiter'], 'semicolon' ); ?>><?php esc_html_e( 'Semicolon ( ; )', 'userferry' ); ?></option>
				<option value="tab" <?php selected( $userferry_s['csv_delimiter'], 'tab' ); ?>><?php esc_html_e( 'Tab', 'userferry' ); ?></option>
			</select>
		</div>
		<div class="userferry-setting">
			<div class="userferry-setting__text">
				<label for="userferry-set-date"><?php esc_html_e( 'Registered date format', 'userferry' ); ?></label>
				<p><?php esc_html_e( 'Raw keeps the database value (UTC, sortable). Site format uses your WordPress date and time settings and timezone.', 'userferry' ); ?></p>
			</div>
			<select id="userferry-set-date" name="userferry[date_format]" class="userferry-select">
				<option value="raw" <?php selected( $userferry_s['date_format'], 'raw' ); ?>><?php esc_html_e( 'Raw — 2026-03-14 09:30:00', 'userferry' ); ?></option>
				<option value="site" <?php selected( $userferry_s['date_format'], 'site' ); ?>><?php esc_html_e( 'Site date & time format', 'userferry' ); ?></option>
			</select>
		</div>
		<?php
		$userferry_switch( 'csv_bom', __( 'Add UTF-8 marker to CSV', 'userferry' ), __( 'Makes Excel display accented and non-Latin characters (e.g. বাংলা, é, ü) correctly.', 'userferry' ), $userferry_s );
		$userferry_switch( 'formula_protection', __( 'Protect against spreadsheet formulas', 'userferry' ), __( 'Values that start with =, +, - or @ are prefixed with an apostrophe so a malicious profile value cannot run as a formula in Excel.', 'userferry' ), $userferry_s );
		?>
	</section>

	<section class="userferry-panel">
		<div class="userferry-panel__head"><h2><?php esc_html_e( 'History', 'userferry' ); ?></h2></div>
		<?php $userferry_switch( 'enable_logs', __( 'Keep export history', 'userferry' ), __( 'Record each export on the History screen so the file can be downloaded again.', 'userferry' ), $userferry_s ); ?>
		<div class="userferry-setting">
			<div class="userferry-setting__text">
				<label for="userferry-set-retention"><?php esc_html_e( 'Delete exports after', 'userferry' ); ?></label>
				<p><?php esc_html_e( 'Old files and history entries are removed once a day. Use 0 to keep them forever.', 'userferry' ); ?></p>
			</div>
			<span class="userferry-inline">
				<input type="number" id="userferry-set-retention" name="userferry[retention_days]" class="userferry-input userferry-input--short" value="<?php echo esc_attr( $userferry_s['retention_days'] ); ?>" min="0" max="3650">
				<span><?php esc_html_e( 'days', 'userferry' ); ?></span>
			</span>
		</div>
	</section>

	<section class="userferry-panel">
		<div class="userferry-panel__head"><h2><?php esc_html_e( 'Import defaults', 'userferry' ); ?></h2></div>
		<div class="userferry-setting">
			<div class="userferry-setting__text">
				<label for="userferry-set-match"><?php esc_html_e( 'Find existing users by', 'userferry' ); ?></label>
				<p><?php esc_html_e( 'Pre-selected on the Import screen.', 'userferry' ); ?></p>
			</div>
			<select id="userferry-set-match" name="userferry[import_match_by]" class="userferry-select">
				<option value="email" <?php selected( $userferry_s['import_match_by'], 'email' ); ?>><?php esc_html_e( 'Email', 'userferry' ); ?></option>
				<option value="login" <?php selected( $userferry_s['import_match_by'], 'login' ); ?>><?php esc_html_e( 'Username', 'userferry' ); ?></option>
				<option value="id" <?php selected( $userferry_s['import_match_by'], 'id' ); ?>><?php esc_html_e( 'User ID', 'userferry' ); ?></option>
			</select>
		</div>
		<div class="userferry-setting">
			<div class="userferry-setting__text">
				<label for="userferry-set-existing"><?php esc_html_e( 'When a user already exists', 'userferry' ); ?></label>
				<p><?php esc_html_e( 'Default choice for rows that match an existing account.', 'userferry' ); ?></p>
			</div>
			<select id="userferry-set-existing" name="userferry[import_existing]" class="userferry-select">
				<option value="update" <?php selected( $userferry_s['import_existing'], 'update' ); ?>><?php esc_html_e( 'Update them', 'userferry' ); ?></option>
				<option value="skip" <?php selected( $userferry_s['import_existing'], 'skip' ); ?>><?php esc_html_e( 'Skip them', 'userferry' ); ?></option>
			</select>
		</div>
		<?php $userferry_switch( 'import_send_email', __( 'Email new users their login details', 'userferry' ), __( 'Default for the matching option on the Import screen.', 'userferry' ), $userferry_s ); ?>
	</section>

	<section class="userferry-panel">
		<div class="userferry-panel__head"><h2><?php esc_html_e( 'Privacy & data', 'userferry' ); ?></h2></div>
		<?php
		$userferry_switch( 'allow_password_hash', __( 'Allow exporting password hashes', 'userferry' ), __( 'Adds a “Password hash” field to exports and imports for administrators. WordPress never stores readable passwords — the hash lets users keep their password when you move them to another WordPress site. Keep this off unless you need it.', 'userferry' ), $userferry_s );
		$userferry_switch( 'delete_data_on_uninstall', __( 'Delete all plugin data on uninstall', 'userferry' ), __( 'Removes templates, history, export files and settings when the plugin is deleted from the Plugins screen.', 'userferry' ), $userferry_s );
		?>
	</section>

	<?php
	$userferry_storage = UserFerry_Storage_Check::result();
	$userferry_states  = array(
		'protected' => array( 'yes-alt', 'ok', __( 'Protected', 'userferry' ), __( 'An anonymous request for a file in the storage folder was refused, so export files cannot be downloaded without logging in.', 'userferry' ) ),
		'outside'   => array( 'yes-alt', 'ok', __( 'Outside the web root', 'userferry' ), __( 'Files are stored in a folder the web server does not serve. This is the safest setup.', 'userferry' ) ),
		'exposed'   => array( 'warning', 'danger', __( 'Publicly accessible', 'userferry' ), __( 'Your web server ignores the folder’s .htaccess / web.config rules (this is normal on Nginx). The folder name and file names are long random codes, but you should block the folder with the rule below, or move storage outside the web root.', 'userferry' ) ),
		'unknown'   => array( 'info-outline', 'warn', __( 'Could not be verified', 'userferry' ), __( 'The check could not prove that the folder is blocked — the reason is shown below. Add the rule below if you use Nginx, or keep files outside the web root, then click “Check again”.', 'userferry' ) ),
	);
	$userferry_state   = isset( $userferry_states[ $userferry_storage['status'] ] ) ? $userferry_states[ $userferry_storage['status'] ] : $userferry_states['unknown'];
	?>
	<section class="userferry-panel" id="userferry-storage">
		<div class="userferry-panel__head"><h2><?php esc_html_e( 'File storage security', 'userferry' ); ?></h2></div>
		<div class="userferry-storage userferry-storage--<?php echo esc_attr( $userferry_state[1] ); ?>">
			<span class="userferry-storage__icon dashicons dashicons-<?php echo esc_attr( $userferry_state[0] ); ?>" aria-hidden="true"></span>
			<div class="userferry-storage__text">
				<strong><?php echo esc_html( $userferry_state[2] ); ?></strong>
				<p><?php echo esc_html( $userferry_state[3] ); ?></p>
				<?php if ( '' !== $userferry_storage['detail'] && 'protected' !== $userferry_storage['status'] ) : ?>
					<p class="userferry-muted"><?php echo esc_html( $userferry_storage['detail'] ); ?></p>
				<?php endif; ?>
			</div>
			<a class="userferry-btn userferry-btn--ghost userferry-btn--sm" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=userferry_storage_recheck' ), 'userferry_storage_recheck' ) ); ?>"><?php esc_html_e( 'Check again', 'userferry' ); ?></a>
		</div>
		<dl class="userferry-storage__facts">
			<dt><?php esc_html_e( 'Folder', 'userferry' ); ?></dt>
			<dd><code><?php echo esc_html( UserFerry_Install::export_dir() ); ?></code></dd>
			<dt><?php esc_html_e( 'Last checked', 'userferry' ); ?></dt>
			<dd>
				<?php
				/* translators: %s: human time difference, e.g. "5 mins" */
				echo esc_html( sprintf( __( '%s ago', 'userferry' ), human_time_diff( (int) $userferry_storage['checked'], time() ) ) );
				?>
			</dd>
		</dl>
		<?php if ( in_array( $userferry_storage['status'], array( 'exposed', 'unknown' ), true ) ) : ?>
			<p class="userferry-storage__label"><?php esc_html_e( 'Nginx: add this to your server block, then reload Nginx', 'userferry' ); ?></p>
			<pre class="userferry-code"><code><?php echo esc_html( UserFerry_Storage_Check::nginx_rule() ); ?></code></pre>
		<?php endif; ?>
		<?php if ( 'outside' !== $userferry_storage['status'] ) : ?>
			<p class="userferry-storage__label"><?php esc_html_e( 'Or keep files outside the web root — add this to wp-config.php (the folder must be writable by PHP):', 'userferry' ); ?></p>
			<pre class="userferry-code"><code>define( 'USERFERRY_EXPORT_DIR', '<?php echo esc_html( wp_normalize_path( dirname( untrailingslashit( ABSPATH ) ) ) ); ?>/userferry-private' );</code></pre>
		<?php endif; ?>
	</section>

	<div class="userferry-settings__foot">
		<button type="submit" class="userferry-btn userferry-btn--primary"><?php esc_html_e( 'Save settings', 'userferry' ); ?></button>
	</div>
</form>
