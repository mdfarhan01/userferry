<?php
/**
 * Field picker (used by the export wizard and the template editor).
 *
 * @package UserFerry
 *
 * @var string $userferry_picker_id Unique id prefix.
 * @var array  $userferry_selected  Pre-selected keys.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$userferry_core = UserFerry_Fields::core_fields();
$userferry_scan = UserFerry_Fields::scan();
$userferry_sel  = isset( $userferry_selected ) ? (array) $userferry_selected : array();
?>
<div class="userferry-fp" data-userferry-picker id="<?php echo esc_attr( $userferry_picker_id ); ?>">
	<div class="userferry-fp__bar">
		<label class="screen-reader-text" for="<?php echo esc_attr( $userferry_picker_id ); ?>-search"><?php esc_html_e( 'Filter fields', 'userferry' ); ?></label>
		<span class="userferry-fp__searchwrap">
			<span class="dashicons dashicons-search" aria-hidden="true"></span>
			<input type="search" id="<?php echo esc_attr( $userferry_picker_id ); ?>-search" class="userferry-input userferry-fp__search" placeholder="<?php esc_attr_e( 'Filter by field name or meta key', 'userferry' ); ?>" autocomplete="off">
		</span>
		<span class="userferry-fp__count" data-fp-count aria-live="polite"></span>
		<span class="userferry-fp__scanned" data-fp-scanned>
			<?php
			/* translators: %s: time of the last scan, e.g. "5 mins ago" */
			echo esc_html( sprintf( __( 'Fields detected automatically · last scanned %s', 'userferry' ), UserFerry_Fields::scanned_at_label() ) );
			?>
		</span>
		<button type="button" class="userferry-btn userferry-btn--primary-soft userferry-btn--sm" data-fp-scan>
			<span class="dashicons dashicons-update" aria-hidden="true"></span>
			<span data-fp-scan-label><?php esc_html_e( 'Scan custom fields', 'userferry' ); ?></span>
		</button>
	</div>

	<section class="userferry-fp__group" data-group="core">
		<div class="userferry-fp__ghead">
			<div>
				<h3><?php esc_html_e( 'WordPress profile', 'userferry' ); ?> <span class="userferry-fp__gcount" data-fp-gcount></span></h3>
			</div>
			<div class="userferry-fp__gactions">
				<button type="button" class="userferry-link" data-fp-all><?php esc_html_e( 'Select all', 'userferry' ); ?></button>
				<button type="button" class="userferry-link" data-fp-none><?php esc_html_e( 'Clear', 'userferry' ); ?></button>
			</div>
		</div>
		<div class="userferry-fp__grid">
			<?php
			foreach ( $userferry_core as $userferry_key => $userferry_label ) {
				UserFerry_Helper::field_item( $userferry_key, $userferry_label, in_array( $userferry_key, $userferry_sel, true ) );
			}
			?>
		</div>
	</section>

	<section class="userferry-fp__group" data-group="profile">
		<div class="userferry-fp__ghead">
			<div>
				<h3><?php esc_html_e( 'Custom profile fields', 'userferry' ); ?> <span class="userferry-fp__gcount" data-fp-gcount></span></h3>
				<p><?php esc_html_e( 'Found in your user meta — ACF, Ultimate Member, WooCommerce, BuddyPress, registration forms and other plugins.', 'userferry' ); ?></p>
			</div>
			<div class="userferry-fp__gactions">
				<button type="button" class="userferry-link" data-fp-all><?php esc_html_e( 'Select all', 'userferry' ); ?></button>
				<button type="button" class="userferry-link" data-fp-none><?php esc_html_e( 'Clear', 'userferry' ); ?></button>
			</div>
		</div>
		<div class="userferry-fp__grid" data-fp-grid="profile">
			<?php
			foreach ( $userferry_scan['profile'] as $userferry_key => $userferry_label ) {
				UserFerry_Helper::field_item( $userferry_key, $userferry_label, in_array( $userferry_key, $userferry_sel, true ) );
			}
			?>
		</div>
		<p class="userferry-fp__empty" data-fp-empty <?php echo empty( $userferry_scan['profile'] ) ? '' : 'hidden'; ?>><?php esc_html_e( 'No custom profile fields were found. If you just added a plugin that stores user data, click “Scan custom fields”.', 'userferry' ); ?></p>
	</section>

	<section class="userferry-fp__group is-collapsed" data-group="system">
		<div class="userferry-fp__ghead">
			<div>
				<h3><?php esc_html_e( 'Hidden & plugin data', 'userferry' ); ?> <span class="userferry-fp__gcount" data-fp-gcount></span></h3>
				<p><?php esc_html_e( 'Internal keys, admin screen preferences and plugin housekeeping. Rarely needed in an export.', 'userferry' ); ?></p>
			</div>
			<div class="userferry-fp__gactions">
				<button type="button" class="userferry-link" data-fp-none><?php esc_html_e( 'Clear', 'userferry' ); ?></button>
				<button type="button" class="userferry-btn userferry-btn--ghost userferry-btn--sm" data-fp-toggle aria-expanded="false"><?php esc_html_e( 'Show', 'userferry' ); ?></button>
			</div>
		</div>
		<div class="userferry-fp__grid" data-fp-grid="system">
			<?php
			foreach ( $userferry_scan['system'] as $userferry_key => $userferry_label ) {
				UserFerry_Helper::field_item( $userferry_key, $userferry_label, in_array( $userferry_key, $userferry_sel, true ) );
			}
			?>
		</div>
	</section>

	<p class="userferry-fp__nomatch" data-fp-nomatch hidden><?php esc_html_e( 'No fields match that filter.', 'userferry' ); ?></p>

	<div class="userferry-cols">
		<div class="userferry-cols__head">
			<h3><?php esc_html_e( 'Column order', 'userferry' ); ?></h3>
			<p><?php esc_html_e( 'Drag to reorder, or focus a column and use the arrow keys. Delete removes it.', 'userferry' ); ?></p>
		</div>
		<ol class="userferry-cols__list" data-fp-columns></ol>
	</div>
</div>
