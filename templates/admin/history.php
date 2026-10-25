<?php
/**
 * Export history screen.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Pagination only (no data is changed).
$userferry_paged    = max( 1, absint( filter_input( INPUT_GET, 'paged', FILTER_SANITIZE_NUMBER_INT ) ) );
$userferry_per_page = 20;
$userferry_total    = UserFerry_History::count();
$userferry_rows     = UserFerry_History::page( $userferry_paged, $userferry_per_page );
$userferry_pages    = max( 1, (int) ceil( $userferry_total / $userferry_per_page ) );
$userferry_can_del  = current_user_can( 'manage_options' );
$userferry_keep     = (int) UserFerry_Settings::get( 'retention_days' );
?>

<p class="userferry-infobar">
	<span class="dashicons dashicons-info-outline" aria-hidden="true"></span>
	<?php
	if ( $userferry_keep > 0 ) {
		/* translators: %s: number of days */
		echo esc_html( sprintf( _n( 'Export files are deleted automatically after %s day. Change this in Settings.', 'Export files are deleted automatically after %s days. Change this in Settings.', $userferry_keep, 'userferry' ), number_format_i18n( $userferry_keep ) ) );
	} else {
		esc_html_e( 'Automatic cleanup is turned off — export files are kept until you delete them.', 'userferry' );
	}
	?>
</p>

<div class="userferry-panel userferry-panel--flush">
	<?php if ( empty( $userferry_rows ) ) : ?>
		<div class="userferry-empty userferry-empty--large">
			<h2><?php esc_html_e( 'Nothing here yet', 'userferry' ); ?></h2>
			<p><?php esc_html_e( 'Exports are kept here so you can download them again; imports are listed with their results.', 'userferry' ); ?></p>
			<a class="userferry-btn userferry-btn--primary" href="<?php echo esc_url( userferry_admin_url( 'userferry-export' ) ); ?>"><?php esc_html_e( 'Run an export', 'userferry' ); ?></a>
		</div>
	<?php else : ?>
		<div class="userferry-tablewrap">
			<table class="userferry-table">
				<thead>
					<tr>
						<th><?php esc_html_e( 'File', 'userferry' ); ?></th>
						<th><?php esc_html_e( 'Type', 'userferry' ); ?></th>
						<th><?php esc_html_e( 'Format', 'userferry' ); ?></th>
						<th class="is-num"><?php esc_html_e( 'Users', 'userferry' ); ?></th>
						<th class="is-num"><?php esc_html_e( 'Columns', 'userferry' ); ?></th>
						<th><?php esc_html_e( 'Template', 'userferry' ); ?></th>
						<th><?php esc_html_e( 'Run by', 'userferry' ); ?></th>
						<th><?php esc_html_e( 'Date', 'userferry' ); ?></th>
						<th><span class="screen-reader-text"><?php esc_html_e( 'Actions', 'userferry' ); ?></span></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $userferry_rows as $userferry_row ) : ?>
						<?php
						$userferry_admin     = get_userdata( $userferry_row->admin_id );
						$userferry_is_import = isset( $userferry_row->entry_type ) && 'import' === $userferry_row->entry_type;
						$userferry_exists    = ! $userferry_is_import && file_exists( UserFerry_History::file_path( $userferry_row ) );
						?>
						<tr data-history-id="<?php echo esc_attr( $userferry_row->id ); ?>">
							<td>
								<span class="userferry-filename-text"><?php echo esc_html( $userferry_row->file_name ); ?></span>
								<?php if ( ! $userferry_is_import ) : ?>
									<span class="userferry-muted"><?php echo esc_html( $userferry_exists ? size_format( (int) $userferry_row->file_size ) : __( 'File removed', 'userferry' ) ); ?></span>
								<?php endif; ?>
							</td>
							<td>
								<span class="userferry-tag userferry-tag--<?php echo $userferry_is_import ? 'import' : 'export'; ?>"><?php echo $userferry_is_import ? esc_html__( 'Import', 'userferry' ) : esc_html__( 'Export', 'userferry' ); ?></span>
							</td>
							<td><?php UserFerry_Helper::format_badge( $userferry_row->format ); ?></td>
							<td class="is-num"><?php echo esc_html( number_format_i18n( $userferry_row->user_count ) ); ?></td>
							<td class="is-num"><?php echo esc_html( number_format_i18n( $userferry_row->field_count ) ); ?></td>
							<td><?php echo esc_html( $userferry_row->template_name ? $userferry_row->template_name : '—' ); ?></td>
							<td><?php echo esc_html( $userferry_admin ? $userferry_admin->display_name : '—' ); ?></td>
							<td><?php echo esc_html( UserFerry_Helper::date( $userferry_row->created_at ) ); ?></td>
							<td class="userferry-table__actions">
								<?php if ( $userferry_exists ) : ?>
									<a class="userferry-btn userferry-btn--ghost userferry-btn--sm" href="<?php echo esc_url( UserFerry_Export_Download::url( $userferry_row->file_name, $userferry_row->id ) ); ?>"><?php esc_html_e( 'Download', 'userferry' ); ?></a>
								<?php endif; ?>
								<?php if ( $userferry_can_del ) : ?>
									<button type="button" class="userferry-iconbtn userferry-iconbtn--danger" data-history-delete="<?php echo esc_attr( $userferry_row->id ); ?>" title="<?php esc_attr_e( 'Delete', 'userferry' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Delete', 'userferry' ); ?></span></button>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php if ( $userferry_pages > 1 ) : ?>
			<nav class="userferry-pagination" aria-label="<?php esc_attr_e( 'History pages', 'userferry' ); ?>">
				<?php
				echo wp_kses_post(
					paginate_links(
						array(
							'base'      => add_query_arg( 'paged', '%#%', userferry_admin_url( 'userferry-history' ) ),
							'format'    => '',
							'current'   => $userferry_paged,
							'total'     => $userferry_pages,
							'prev_text' => '‹',
							'next_text' => '›',
						)
					)
				);
				?>
			</nav>
		<?php endif; ?>
	<?php endif; ?>
</div>
