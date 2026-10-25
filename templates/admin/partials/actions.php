<?php
/**
 * Contextual actions shown on the right of the top bar.
 *
 * @var array $args { @type string $active Current page slug. }
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$userferry_active = isset( $args['active'] ) ? (string) $args['active'] : '';

switch ( $userferry_active ) {
	case 'userferry':
		if ( userferry_can_import() ) :
			?>
			<a class="userferry-btn userferry-btn--ghost" href="<?php echo esc_url( userferry_admin_url( 'userferry-import' ) ); ?>"><span class="dashicons dashicons-download" aria-hidden="true"></span><?php esc_html_e( 'Import users', 'userferry' ); ?></a>
			<?php
		endif;
		?>
		<a class="userferry-btn userferry-btn--primary" href="<?php echo esc_url( userferry_admin_url( 'userferry-export' ) ); ?>"><span class="dashicons dashicons-upload" aria-hidden="true"></span><?php esc_html_e( 'New export', 'userferry' ); ?></a>
		<?php
		break;

	case 'userferry-export':
		$userferry_templates = UserFerry_Templates::all();
		if ( ! empty( $userferry_templates ) ) :
			?>
			<label class="userferry-tplselect">
				<span><?php esc_html_e( 'Start from a template', 'userferry' ); ?></span>
				<select class="userferry-select" id="userferry-template-select">
					<option value=""><?php esc_html_e( 'None — start blank', 'userferry' ); ?></option>
					<?php foreach ( $userferry_templates as $userferry_t ) : ?>
						<option value="<?php echo esc_attr( $userferry_t['id'] ); ?>"><?php echo esc_html( $userferry_t['name'] ); ?></option>
					<?php endforeach; ?>
				</select>
			</label>
			<?php
		endif;
		break;

	case 'userferry-templates':
		?>
		<button type="button" class="userferry-btn userferry-btn--primary" data-tpl-create><span class="dashicons dashicons-plus-alt2" aria-hidden="true"></span><?php esc_html_e( 'New template', 'userferry' ); ?></button>
		<?php
		break;

	case 'userferry-history':
		if ( current_user_can( 'manage_options' ) && UserFerry_History::count() ) :
			?>
			<button type="button" class="userferry-btn userferry-btn--danger-ghost" data-history-clear><?php esc_html_e( 'Delete all history', 'userferry' ); ?></button>
			<?php
		endif;
		break;

	case 'userferry-import':
		?>
		<a class="userferry-btn userferry-btn--ghost" href="<?php echo esc_url( userferry_admin_url( 'userferry-docs' ) . '#userferry-doc-import' ); ?>"><span class="dashicons dashicons-editor-help" aria-hidden="true"></span><?php esc_html_e( 'How importing works', 'userferry' ); ?></a>
		<?php
		break;
}
