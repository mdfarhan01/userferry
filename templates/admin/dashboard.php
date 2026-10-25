<?php
/**
 * Dashboard screen.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$userferry_counts    = count_users();
$userferry_templates = UserFerry_Templates::all();
$userferry_recent    = UserFerry_History::page( 1, 6 );
$userferry_labels    = UserFerry_Fields::all_labels();
$userferry_stats     = array(
	array( number_format_i18n( $userferry_counts['total_users'] ), __( 'Users on this site', 'userferry' ), 'dashicons-groups', 'indigo' ),
	array( number_format_i18n( UserFerry_Fields::count_custom() ), __( 'Custom fields detected', 'userferry' ), 'dashicons-database', 'teal' ),
	array( number_format_i18n( count( $userferry_templates ) ), __( 'Saved templates', 'userferry' ), 'dashicons-portfolio', 'amber' ),
	array( number_format_i18n( UserFerry_History::count() ), __( 'History entries', 'userferry' ), 'dashicons-backup', 'violet' ),
);
?>

<div class="userferry-stats">
	<?php foreach ( $userferry_stats as $userferry_stat ) : ?>
		<div class="userferry-stat userferry-stat--<?php echo esc_attr( $userferry_stat[3] ); ?>">
			<span class="userferry-stat__icon" aria-hidden="true"><span class="dashicons <?php echo esc_attr( $userferry_stat[2] ); ?>"></span></span>
			<span class="userferry-stat__text">
				<span class="userferry-stat__value"><?php echo esc_html( $userferry_stat[0] ); ?></span>
				<span class="userferry-stat__label"><?php echo esc_html( $userferry_stat[1] ); ?></span>
			</span>
		</div>
	<?php endforeach; ?>
</div>

<div class="userferry-quick">
	<a class="userferry-quick__item" href="<?php echo esc_url( userferry_admin_url( 'userferry-export' ) ); ?>">
		<span class="userferry-quick__icon userferry-quick__icon--indigo" aria-hidden="true"><span class="dashicons dashicons-upload"></span></span>
		<span><strong><?php esc_html_e( 'Export users', 'userferry' ); ?></strong><span><?php esc_html_e( 'Pick users and fields, download CSV, Excel, JSON or XML.', 'userferry' ); ?></span></span>
	</a>
	<?php if ( userferry_can_import() ) : ?>
		<a class="userferry-quick__item" href="<?php echo esc_url( userferry_admin_url( 'userferry-import' ) ); ?>">
			<span class="userferry-quick__icon userferry-quick__icon--teal" aria-hidden="true"><span class="dashicons dashicons-download"></span></span>
			<span><strong><?php esc_html_e( 'Import users', 'userferry' ); ?></strong><span><?php esc_html_e( 'Create or update users from an exported file.', 'userferry' ); ?></span></span>
		</a>
	<?php endif; ?>
	<a class="userferry-quick__item" href="<?php echo esc_url( userferry_admin_url( 'userferry-templates', array( 'new' => 1 ) ) ); ?>">
		<span class="userferry-quick__icon userferry-quick__icon--amber" aria-hidden="true"><span class="dashicons dashicons-plus-alt2"></span></span>
		<span><strong><?php esc_html_e( 'New template', 'userferry' ); ?></strong><span><?php esc_html_e( 'Save a set of users and fields for one-click exports.', 'userferry' ); ?></span></span>
	</a>
</div>

<div class="userferry-split">
	<section class="userferry-panel">
		<div class="userferry-panel__head">
			<h2><?php esc_html_e( 'Run a template', 'userferry' ); ?></h2>
			<a href="<?php echo esc_url( userferry_admin_url( 'userferry-templates' ) ); ?>"><?php esc_html_e( 'Manage templates', 'userferry' ); ?></a>
		</div>
		<?php if ( empty( $userferry_templates ) ) : ?>
			<div class="userferry-empty">
				<p><?php esc_html_e( 'A template remembers who to include, which fields to export and the file format, so a repeat export is a single click.', 'userferry' ); ?></p>
				<a class="userferry-btn userferry-btn--ghost" href="<?php echo esc_url( userferry_admin_url( 'userferry-templates', array( 'new' => 1 ) ) ); ?>"><?php esc_html_e( 'Create your first template', 'userferry' ); ?></a>
			</div>
		<?php else : ?>
			<ul class="userferry-list">
				<?php foreach ( array_slice( $userferry_templates, 0, 6 ) as $userferry_t ) : ?>
					<li class="userferry-list__row">
						<div class="userferry-list__main">
							<strong><?php echo esc_html( $userferry_t['name'] ); ?></strong>
							<span>
								<?php
								/* translators: %s: number of fields */
								echo esc_html( sprintf( _n( '%s field', '%s fields', count( $userferry_t['fields'] ), 'userferry' ), number_format_i18n( count( $userferry_t['fields'] ) ) ) . ' · ' . $userferry_t['summary'] );
								?>
							</span>
						</div>
						<?php UserFerry_Helper::format_badge( $userferry_t['format'] ); ?>
						<div class="userferry-list__actions">
							<a class="userferry-btn userferry-btn--ghost userferry-btn--sm" href="<?php echo esc_url( userferry_admin_url( 'userferry-export', array( 'template' => $userferry_t['id'] ) ) ); ?>"><?php esc_html_e( 'Open', 'userferry' ); ?></a>
							<button type="button" class="userferry-btn userferry-btn--primary userferry-btn--sm" data-userferry-quick="<?php echo esc_attr( $userferry_t['id'] ); ?>"><?php esc_html_e( 'Export now', 'userferry' ); ?></button>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="userferry-panel">
		<div class="userferry-panel__head">
			<h2><?php esc_html_e( 'Recent activity', 'userferry' ); ?></h2>
			<a href="<?php echo esc_url( userferry_admin_url( 'userferry-history' ) ); ?>"><?php esc_html_e( 'View history', 'userferry' ); ?></a>
		</div>
		<?php if ( empty( $userferry_recent ) ) : ?>
			<div class="userferry-empty">
				<p><?php esc_html_e( 'Finished exports and imports appear here.', 'userferry' ); ?></p>
			</div>
		<?php else : ?>
			<ul class="userferry-list">
				<?php foreach ( $userferry_recent as $userferry_row ) : ?>
					<?php
					$userferry_is_import = isset( $userferry_row->entry_type ) && 'import' === $userferry_row->entry_type;
					$userferry_exists    = ! $userferry_is_import && file_exists( UserFerry_History::file_path( $userferry_row ) );
					?>
					<li class="userferry-list__row">
						<div class="userferry-list__main">
							<strong class="userferry-filename-text"><?php echo esc_html( $userferry_row->file_name ); ?></strong>
							<span>
								<?php
								echo esc_html(
									$userferry_is_import
										/* translators: 1: number of users, 2: date */
										? sprintf( __( 'Imported %1$s users · %2$s', 'userferry' ), number_format_i18n( $userferry_row->user_count ), UserFerry_Helper::date( $userferry_row->created_at ) )
										/* translators: 1: number of users, 2: date */
										: sprintf( __( 'Exported %1$s users · %2$s', 'userferry' ), number_format_i18n( $userferry_row->user_count ), UserFerry_Helper::date( $userferry_row->created_at ) )
								);
								?>
							</span>
						</div>
						<?php UserFerry_Helper::format_badge( $userferry_row->format ); ?>
						<div class="userferry-list__actions">
							<?php if ( $userferry_exists ) : ?>
								<a class="userferry-btn userferry-btn--ghost userferry-btn--sm" href="<?php echo esc_url( UserFerry_Export_Download::url( $userferry_row->file_name, $userferry_row->id ) ); ?>"><?php esc_html_e( 'Download', 'userferry' ); ?></a>
							<?php elseif ( $userferry_is_import ) : ?>
								<span class="userferry-tag userferry-tag--import"><?php esc_html_e( 'Import', 'userferry' ); ?></span>
							<?php else : ?>
								<span class="userferry-muted"><?php esc_html_e( 'File removed', 'userferry' ); ?></span>
							<?php endif; ?>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
</div>
