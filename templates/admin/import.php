<?php
/**
 * Import users screen (upload → map columns → options → run).
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$userferry_settings = userferry_get_settings();
$userferry_roles    = get_editable_roles();
$userferry_default  = get_option( 'default_role', 'subscriber' );
$userferry_steps    = array(
	1 => array( __( 'Upload file', 'userferry' ), __( 'CSV, Excel, JSON or XML', 'userferry' ) ),
	2 => array( __( 'Map columns', 'userferry' ), __( 'Match columns to fields', 'userferry' ) ),
	3 => array( __( 'Options', 'userferry' ), __( 'Create, update or skip', 'userferry' ) ),
	4 => array( __( 'Import', 'userferry' ), __( 'Run and see results', 'userferry' ) ),
);
?>
<div class="userferry-wizard userferry-import" id="userferry-import">
	<ol class="userferry-stepper userferry-stepper--rail">
		<?php foreach ( $userferry_steps as $userferry_n => $userferry_step ) : ?>
			<li>
				<button type="button" class="userferry-stepper__btn <?php echo 1 === $userferry_n ? 'is-current' : ''; ?>" data-goto="<?php echo esc_attr( $userferry_n ); ?>" <?php echo 1 === $userferry_n ? 'aria-current="step"' : 'disabled'; ?>>
					<span class="userferry-stepper__num"><span><?php echo esc_html( $userferry_n ); ?></span></span>
					<span class="userferry-stepper__text">
						<span class="userferry-stepper__label"><?php echo esc_html( $userferry_step[0] ); ?></span>
						<span class="userferry-stepper__meta"><?php echo esc_html( $userferry_step[1] ); ?></span>
					</span>
				</button>
			</li>
		<?php endforeach; ?>
	</ol>

	<section class="userferry-panel userferry-step" data-step="1">
		<div class="userferry-step__head">
			<h2><?php esc_html_e( 'Upload a file', 'userferry' ); ?></h2>
		</div>
		<div class="userferry-step__body">
			<label class="userferry-drop" data-drop>
				<input type="file" class="screen-reader-text" data-file accept=".csv,.tsv,.txt,.xlsx,.json,.xml,text/csv,application/json,application/xml,text/xml,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
				<span class="userferry-drop__icon" aria-hidden="true"><span class="dashicons dashicons-media-spreadsheet"></span></span>
				<strong data-drop-title><?php esc_html_e( 'Drop your file here, or click to browse', 'userferry' ); ?></strong>
				<span class="userferry-drop__hint">
					<?php
					/* translators: %s: maximum upload size, e.g. "64 MB" */
					echo esc_html( sprintf( __( 'CSV, Excel (.xlsx), JSON or XML · up to %s', 'userferry' ), size_format( wp_max_upload_size() ) ) );
					?>
				</span>
				<span class="userferry-drop__formats" aria-hidden="true">
					<?php
					foreach ( array( 'csv', 'xlsx', 'json', 'xml' ) as $userferry_f ) {
						UserFerry_Helper::format_badge( $userferry_f );
					}
					?>
				</span>
			</label>

			<div class="userferry-callout">
				<p><strong><?php esc_html_e( 'Files exported by this plugin import as-is.', 'userferry' ); ?></strong> <?php esc_html_e( 'Their columns are matched to the right fields automatically. Files from other tools work too — the first row must contain column names.', 'userferry' ); ?></p>
			</div>
		</div>
		<div class="userferry-step__foot">
			<span class="userferry-step__error" data-step-error role="alert"></span>
			<span class="userferry-upload-status" data-upload-status aria-live="polite"></span>
		</div>
	</section>

	<section class="userferry-panel userferry-step" data-step="2" hidden>
		<div class="userferry-step__head userferry-step__head--split">
			<h2><?php esc_html_e( 'Match columns to fields', 'userferry' ); ?></h2>
			<span class="userferry-filechip" data-file-chip></span>
		</div>
		<div class="userferry-step__body">
			<p class="userferry-help userferry-help--lead"><?php esc_html_e( 'Each column in your file is imported into the field on the right. Columns set to “Don’t import” are ignored.', 'userferry' ); ?></p>
			<div class="userferry-tablewrap">
				<table class="userferry-table userferry-table--map">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Column in your file', 'userferry' ); ?></th>
							<th><?php esc_html_e( 'Example values', 'userferry' ); ?></th>
							<th><?php esc_html_e( 'Import into', 'userferry' ); ?></th>
						</tr>
					</thead>
					<tbody data-map-body></tbody>
				</table>
			</div>
		</div>
		<div class="userferry-step__foot">
			<button type="button" class="userferry-btn userferry-btn--ghost" data-restart><?php esc_html_e( 'Choose a different file', 'userferry' ); ?></button>
			<span class="userferry-matchcount" data-mapped-count></span>
			<span class="userferry-step__error" data-step-error role="alert"></span>
			<button type="button" class="userferry-btn userferry-btn--primary" data-next><?php esc_html_e( 'Continue to options', 'userferry' ); ?></button>
		</div>
	</section>

	<section class="userferry-panel userferry-step" data-step="3" hidden>
		<div class="userferry-step__head">
			<h2><?php esc_html_e( 'Import options', 'userferry' ); ?></h2>
		</div>
		<div class="userferry-step__body userferry-settings">
			<div class="userferry-setting">
				<div class="userferry-setting__text">
					<span class="userferry-setting__label"><?php esc_html_e( 'Find existing users by', 'userferry' ); ?></span>
					<p><?php esc_html_e( 'If no match is found by this field, email and username are tried next.', 'userferry' ); ?></p>
				</div>
				<div class="userferry-segmented" role="radiogroup">
					<?php
					foreach ( array(
						'email' => __( 'Email', 'userferry' ),
						'login' => __( 'Username', 'userferry' ),
						'id'    => __( 'User ID', 'userferry' ),
					) as $userferry_value => $userferry_label ) :
						?>
						<label><input type="radio" name="userferry-imp-match" value="<?php echo esc_attr( $userferry_value ); ?>" <?php checked( $userferry_settings['import_match_by'], $userferry_value ); ?>><span><?php echo esc_html( $userferry_label ); ?></span></label>
					<?php endforeach; ?>
				</div>
			</div>
			<div class="userferry-setting">
				<div class="userferry-setting__text">
					<span class="userferry-setting__label"><?php esc_html_e( 'When a user already exists', 'userferry' ); ?></span>
					<p><?php esc_html_e( 'Update overwrites their profile with values from the file. Usernames and registration dates never change.', 'userferry' ); ?></p>
				</div>
				<div class="userferry-segmented" role="radiogroup">
					<label><input type="radio" name="userferry-imp-existing" value="update" <?php checked( $userferry_settings['import_existing'], 'update' ); ?>><span><?php esc_html_e( 'Update them', 'userferry' ); ?></span></label>
					<label><input type="radio" name="userferry-imp-existing" value="skip" <?php checked( $userferry_settings['import_existing'], 'skip' ); ?>><span><?php esc_html_e( 'Skip them', 'userferry' ); ?></span></label>
				</div>
			</div>
			<div class="userferry-setting">
				<div class="userferry-setting__text">
					<label for="userferry-imp-create"><?php esc_html_e( 'Create new users', 'userferry' ); ?></label>
					<p><?php esc_html_e( 'Rows that don’t match anyone become new accounts. Without a password column a strong random password is set.', 'userferry' ); ?></p>
				</div>
				<span class="userferry-switch"><input type="checkbox" id="userferry-imp-create" data-opt-create checked><span aria-hidden="true"></span></span>
			</div>
			<div class="userferry-setting">
				<div class="userferry-setting__text">
					<label for="userferry-imp-role"><?php esc_html_e( 'Role for new users', 'userferry' ); ?></label>
					<p><?php esc_html_e( 'Used when the file has no Role column or the cell is empty.', 'userferry' ); ?></p>
				</div>
				<select id="userferry-imp-role" class="userferry-select" data-opt-role>
					<?php foreach ( $userferry_roles as $userferry_key => $userferry_role ) : ?>
						<option value="<?php echo esc_attr( $userferry_key ); ?>" <?php selected( $userferry_default, $userferry_key ); ?>><?php echo esc_html( translate_user_role( $userferry_role['name'] ) ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="userferry-setting">
				<div class="userferry-setting__text">
					<label for="userferry-imp-empty"><?php esc_html_e( 'Ignore empty cells', 'userferry' ); ?></label>
					<p><?php esc_html_e( 'Recommended. When off, an empty cell clears that field for existing users.', 'userferry' ); ?></p>
				</div>
				<span class="userferry-switch"><input type="checkbox" id="userferry-imp-empty" data-opt-empty checked><span aria-hidden="true"></span></span>
			</div>
			<div class="userferry-setting">
				<div class="userferry-setting__text">
					<label for="userferry-imp-email"><?php esc_html_e( 'Email new users their login details', 'userferry' ); ?></label>
					<p><?php esc_html_e( 'Sends the standard WordPress “set your password” email to each new account.', 'userferry' ); ?></p>
				</div>
				<span class="userferry-switch"><input type="checkbox" id="userferry-imp-email" data-opt-email <?php checked( 1, (int) $userferry_settings['import_send_email'] ); ?>><span aria-hidden="true"></span></span>
			</div>
		</div>
		<div class="userferry-step__foot">
			<button type="button" class="userferry-btn userferry-btn--ghost" data-prev><?php esc_html_e( 'Back', 'userferry' ); ?></button>
			<span class="userferry-step__error" data-step-error role="alert"></span>
			<button type="button" class="userferry-btn userferry-btn--primary" data-next><?php esc_html_e( 'Review import', 'userferry' ); ?></button>
		</div>
	</section>

	<section class="userferry-panel userferry-step" data-step="4" hidden>
		<div class="userferry-step__head">
			<h2><?php esc_html_e( 'Review and import', 'userferry' ); ?></h2>
		</div>
		<div class="userferry-step__body">
			<dl class="userferry-review" data-review></dl>
			<div class="userferry-callout userferry-callout--warn">
				<p><?php esc_html_e( 'Importing changes user accounts and cannot be undone. Export your users first if you might need to roll back.', 'userferry' ); ?></p>
			</div>
			<div class="userferry-progress" data-progress hidden></div>
			<div class="userferry-importresult" data-result hidden></div>
		</div>
		<div class="userferry-step__foot">
			<button type="button" class="userferry-btn userferry-btn--ghost" data-prev><?php esc_html_e( 'Back', 'userferry' ); ?></button>
			<span class="userferry-step__error" data-step-error role="alert"></span>
			<button type="button" class="userferry-btn userferry-btn--primary userferry-btn--lg" data-run><span class="dashicons dashicons-download" aria-hidden="true"></span><span data-run-label><?php esc_html_e( 'Start import', 'userferry' ); ?></span></button>
		</div>
	</section>
</div>
