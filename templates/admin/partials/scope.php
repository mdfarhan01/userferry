<?php
/**
 * "Which users" picker (wizard + template editor).
 *
 * @package UserFerry
 *
 * @var string $userferry_scope_id    Unique id prefix.
 * @var bool   $userferry_users_table Whether to offer the "specific users" table.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$userferry_counts = count_users();
$userferry_roles  = wp_roles()->get_names();
$userferry_avail  = isset( $userferry_counts['avail_roles'] ) ? $userferry_counts['avail_roles'] : array();
$userferry_sid    = $userferry_scope_id;

$userferry_choices = array(
	'all'      => array( __( 'All users', 'userferry' ), __( 'Everyone registered on this site.', 'userferry' ), 'dashicons-groups' ),
	'role'     => array( __( 'By role', 'userferry' ), __( 'Subscribers, customers, authors…', 'userferry' ), 'dashicons-id-alt' ),
	'date'     => array( __( 'By registration date', 'userferry' ), __( 'Today, last week, last month or a custom range.', 'userferry' ), 'dashicons-calendar-alt' ),
	'selected' => array( __( 'Specific users', 'userferry' ), __( 'Pick people one by one from a searchable list.', 'userferry' ), 'dashicons-admin-users' ),
);
?>
<div class="userferry-scope" data-userferry-scope data-scope-id="<?php echo esc_attr( $userferry_sid ); ?>">
	<fieldset class="userferry-choices">
		<legend class="screen-reader-text"><?php esc_html_e( 'Which users', 'userferry' ); ?></legend>
		<?php foreach ( $userferry_choices as $userferry_value => $userferry_choice ) : ?>
			<label class="userferry-choice" <?php echo ( 'selected' === $userferry_value && ! $userferry_users_table ) ? 'hidden data-scope-selected-only' : ''; ?>>
				<input type="radio" name="<?php echo esc_attr( $userferry_sid ); ?>-scope" value="<?php echo esc_attr( $userferry_value ); ?>" <?php checked( 'all', $userferry_value ); ?>>
				<span class="userferry-choice__icon" aria-hidden="true"><span class="dashicons <?php echo esc_attr( $userferry_choice[2] ); ?>"></span></span>
				<span class="userferry-choice__body">
					<strong><?php echo esc_html( $userferry_choice[0] ); ?></strong>
					<span data-choice-desc><?php echo esc_html( $userferry_choice[1] ); ?></span>
				</span>
			</label>
		<?php endforeach; ?>
	</fieldset>

	<div class="userferry-scope__panel" data-scope-panel="role" hidden>
		<p class="userferry-scope__label"><?php esc_html_e( 'Include users with any of these roles', 'userferry' ); ?></p>
		<div class="userferry-roles">
			<?php foreach ( $userferry_roles as $userferry_role => $userferry_name ) : ?>
				<label class="userferry-check">
					<input type="checkbox" value="<?php echo esc_attr( $userferry_role ); ?>" data-scope-role>
					<span><?php echo esc_html( translate_user_role( $userferry_name ) ); ?></span>
					<span class="userferry-check__meta"><?php echo esc_html( number_format_i18n( isset( $userferry_avail[ $userferry_role ] ) ? $userferry_avail[ $userferry_role ] : 0 ) ); ?></span>
				</label>
			<?php endforeach; ?>
		</div>
	</div>

	<div class="userferry-scope__panel" data-scope-panel="date" hidden>
		<p class="userferry-scope__label"><?php esc_html_e( 'Registered', 'userferry' ); ?></p>
		<div class="userferry-segmented" role="radiogroup">
			<?php
			$userferry_ranges = array(
				'today'  => __( 'Today', 'userferry' ),
				'7days'  => __( 'Last 7 days', 'userferry' ),
				'30days' => __( 'Last 30 days', 'userferry' ),
				'custom' => __( 'Custom range', 'userferry' ),
			);
			foreach ( $userferry_ranges as $userferry_value => $userferry_label ) :
				?>
				<label><input type="radio" name="<?php echo esc_attr( $userferry_sid ); ?>-range" value="<?php echo esc_attr( $userferry_value ); ?>" <?php checked( '30days', $userferry_value ); ?>><span><?php echo esc_html( $userferry_label ); ?></span></label>
			<?php endforeach; ?>
		</div>
		<div class="userferry-daterange" data-scope-custom hidden>
			<label><span><?php esc_html_e( 'From', 'userferry' ); ?></span><input type="date" class="userferry-input" data-scope-from></label>
			<label><span><?php esc_html_e( 'To', 'userferry' ); ?></span><input type="date" class="userferry-input" data-scope-to></label>
		</div>
	</div>

	<?php if ( $userferry_users_table ) : ?>
		<div class="userferry-scope__panel" data-scope-panel="selected" hidden>
			<div class="userferry-userpick">
				<div class="userferry-userpick__bar">
					<span class="userferry-fp__searchwrap">
						<span class="dashicons dashicons-search" aria-hidden="true"></span>
						<input type="search" class="userferry-input" data-users-search placeholder="<?php esc_attr_e( 'Search name, username or email', 'userferry' ); ?>" aria-label="<?php esc_attr_e( 'Search users', 'userferry' ); ?>">
					</span>
					<select class="userferry-select" data-users-role aria-label="<?php esc_attr_e( 'Filter by role', 'userferry' ); ?>">
						<option value=""><?php esc_html_e( 'All roles', 'userferry' ); ?></option>
						<?php foreach ( $userferry_roles as $userferry_role => $userferry_name ) : ?>
							<option value="<?php echo esc_attr( $userferry_role ); ?>"><?php echo esc_html( translate_user_role( $userferry_name ) ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="userferry-tablewrap">
					<table class="userferry-table userferry-table--users">
						<thead>
							<tr>
								<th class="userferry-table__check"><input type="checkbox" data-users-all aria-label="<?php esc_attr_e( 'Select every user on this page', 'userferry' ); ?>"></th>
								<th><?php esc_html_e( 'User', 'userferry' ); ?></th>
								<th><?php esc_html_e( 'Email', 'userferry' ); ?></th>
								<th><?php esc_html_e( 'Role', 'userferry' ); ?></th>
							</tr>
						</thead>
						<tbody data-users-body></tbody>
					</table>
				</div>
				<div class="userferry-userpick__foot">
					<span><strong data-users-selected>0</strong> <?php esc_html_e( 'selected', 'userferry' ); ?> <button type="button" class="userferry-link" data-users-clear><?php esc_html_e( 'Clear selection', 'userferry' ); ?></button></span>
					<span class="userferry-pager">
						<span data-users-pageinfo></span>
						<button type="button" class="userferry-btn userferry-btn--ghost userferry-btn--sm" data-users-prev aria-label="<?php esc_attr_e( 'Previous page', 'userferry' ); ?>">‹</button>
						<button type="button" class="userferry-btn userferry-btn--ghost userferry-btn--sm" data-users-next aria-label="<?php esc_attr_e( 'Next page', 'userferry' ); ?>">›</button>
					</span>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<div class="userferry-scope__extra">
		<div class="userferry-field">
			<label for="<?php echo esc_attr( $userferry_sid ); ?>-search"><?php esc_html_e( 'Only users matching (optional)', 'userferry' ); ?></label>
			<input type="text" id="<?php echo esc_attr( $userferry_sid ); ?>-search" class="userferry-input" data-scope-search placeholder="<?php esc_attr_e( 'e.g. @gmail.com or a username', 'userferry' ); ?>">
		</div>
		<div class="userferry-field">
			<label for="<?php echo esc_attr( $userferry_sid ); ?>-orderby"><?php esc_html_e( 'Sort rows by', 'userferry' ); ?></label>
			<div class="userferry-inline">
				<select id="<?php echo esc_attr( $userferry_sid ); ?>-orderby" class="userferry-select" data-scope-orderby>
					<?php foreach ( UserFerry_Export_Config::sort_options() as $userferry_value => $userferry_label ) : ?>
						<option value="<?php echo esc_attr( $userferry_value ); ?>"><?php echo esc_html( $userferry_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<select class="userferry-select" data-scope-order aria-label="<?php esc_attr_e( 'Sort direction', 'userferry' ); ?>">
					<option value="ASC"><?php esc_html_e( 'Ascending', 'userferry' ); ?></option>
					<option value="DESC"><?php esc_html_e( 'Descending', 'userferry' ); ?></option>
				</select>
			</div>
		</div>
	</div>
</div>
