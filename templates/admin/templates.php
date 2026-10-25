<?php
/**
 * Templates screen with create / edit / delete.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$userferry_templates = UserFerry_Templates::all();
$userferry_labels    = UserFerry_Fields::all_labels();
?>

<div class="userferry-panel userferry-empty userferry-empty--large" data-tpl-empty <?php echo empty( $userferry_templates ) ? '' : 'hidden'; ?>>
	<h2><?php esc_html_e( 'No templates yet', 'userferry' ); ?></h2>
	<p><?php esc_html_e( 'For example: “Subscribers — username, email, last name and education”. Create it once, then export it whenever you need a fresh copy.', 'userferry' ); ?></p>
	<button type="button" class="userferry-btn userferry-btn--primary" data-tpl-create><?php esc_html_e( 'Create a template', 'userferry' ); ?></button>
</div>

<div class="userferry-tplgrid" data-tpl-grid>
	<?php foreach ( $userferry_templates as $userferry_t ) : ?>
		<?php
		$userferry_field_count = count( $userferry_t['fields'] );
		$userferry_preview     = array_slice( $userferry_t['fields'], 0, 6 );
		?>
		<article class="userferry-tpl" data-tpl-id="<?php echo esc_attr( $userferry_t['id'] ); ?>">
			<header class="userferry-tpl__head">
				<h2><?php echo esc_html( $userferry_t['name'] ); ?></h2>
				<?php UserFerry_Helper::format_badge( $userferry_t['format'] ); ?>
			</header>
			<?php if ( '' !== $userferry_t['description'] ) : ?>
				<p class="userferry-tpl__desc"><?php echo esc_html( $userferry_t['description'] ); ?></p>
			<?php endif; ?>
			<p class="userferry-tpl__scope"><span class="dashicons dashicons-groups" aria-hidden="true"></span><?php echo esc_html( $userferry_t['summary'] ); ?></p>
			<ul class="userferry-tpl__fields" aria-label="<?php esc_attr_e( 'Columns', 'userferry' ); ?>">
				<?php foreach ( $userferry_preview as $userferry_key ) : ?>
					<li><?php echo esc_html( isset( $userferry_labels[ $userferry_key ] ) ? $userferry_labels[ $userferry_key ] : $userferry_key ); ?></li>
				<?php endforeach; ?>
				<?php if ( $userferry_field_count > count( $userferry_preview ) ) : ?>
					<li class="is-more">
						<?php
						/* translators: %s: number of extra fields */
						echo esc_html( sprintf( __( '+%s more', 'userferry' ), number_format_i18n( $userferry_field_count - count( $userferry_preview ) ) ) );
						?>
					</li>
				<?php endif; ?>
			</ul>
			<footer class="userferry-tpl__foot">
				<button type="button" class="userferry-btn userferry-btn--primary userferry-btn--sm" data-userferry-quick="<?php echo esc_attr( $userferry_t['id'] ); ?>"><?php esc_html_e( 'Export now', 'userferry' ); ?></button>
				<a class="userferry-btn userferry-btn--ghost userferry-btn--sm" href="<?php echo esc_url( userferry_admin_url( 'userferry-export', array( 'template' => $userferry_t['id'] ) ) ); ?>"><?php esc_html_e( 'Open in wizard', 'userferry' ); ?></a>
				<span class="userferry-tpl__tools">
					<button type="button" class="userferry-iconbtn" data-tpl-edit="<?php echo esc_attr( $userferry_t['id'] ); ?>" title="<?php esc_attr_e( 'Edit', 'userferry' ); ?>"><span class="dashicons dashicons-edit" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Edit', 'userferry' ); ?></span></button>
					<button type="button" class="userferry-iconbtn" data-tpl-duplicate="<?php echo esc_attr( $userferry_t['id'] ); ?>" title="<?php esc_attr_e( 'Duplicate', 'userferry' ); ?>"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Duplicate', 'userferry' ); ?></span></button>
					<button type="button" class="userferry-iconbtn userferry-iconbtn--danger" data-tpl-delete="<?php echo esc_attr( $userferry_t['id'] ); ?>" title="<?php esc_attr_e( 'Delete', 'userferry' ); ?>"><span class="dashicons dashicons-trash" aria-hidden="true"></span><span class="screen-reader-text"><?php esc_html_e( 'Delete', 'userferry' ); ?></span></button>
				</span>
			</footer>
		</article>
	<?php endforeach; ?>
</div>

<div class="userferry-modal" id="userferry-tpl-editor" hidden>
	<div class="userferry-modal__backdrop" data-modal-close></div>
	<div class="userferry-modal__dialog userferry-modal__dialog--wide" role="dialog" aria-modal="true" aria-labelledby="userferry-tpl-editor-title">
		<header class="userferry-modal__head">
			<h2 id="userferry-tpl-editor-title" data-editor-title><?php esc_html_e( 'New template', 'userferry' ); ?></h2>
			<button type="button" class="userferry-iconbtn" data-modal-close aria-label="<?php esc_attr_e( 'Close', 'userferry' ); ?>"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button>
		</header>
		<div class="userferry-modal__body">
			<section class="userferry-editor__section">
				<h3><?php esc_html_e( 'Name', 'userferry' ); ?></h3>
				<div class="userferry-editor__row">
					<div class="userferry-field">
						<label for="userferry-tpl-name"><?php esc_html_e( 'Template name', 'userferry' ); ?></label>
						<input type="text" id="userferry-tpl-name" class="userferry-input" maxlength="191" data-editor-name placeholder="<?php esc_attr_e( 'e.g. Subscribers — login & education', 'userferry' ); ?>">
					</div>
					<div class="userferry-field">
						<label for="userferry-tpl-desc"><?php esc_html_e( 'Note (optional)', 'userferry' ); ?></label>
						<input type="text" id="userferry-tpl-desc" class="userferry-input" data-editor-desc placeholder="<?php esc_attr_e( 'Who uses this export and why', 'userferry' ); ?>">
					</div>
				</div>
			</section>
			<section class="userferry-editor__section">
				<h3><?php esc_html_e( 'Users', 'userferry' ); ?></h3>
				<?php
				$userferry_scope_id    = 'userferry-tpl';
				$userferry_users_table = false;
				require USERFERRY_PATH . 'templates/admin/partials/scope.php';
				?>
			</section>
			<section class="userferry-editor__section">
				<h3><?php esc_html_e( 'Fields', 'userferry' ); ?></h3>
				<?php
				$userferry_picker_id = 'userferry-tpl-fields';
				$userferry_selected  = array();
				require USERFERRY_PATH . 'templates/admin/partials/field-picker.php';
				?>
			</section>
			<section class="userferry-editor__section">
				<h3><?php esc_html_e( 'File', 'userferry' ); ?></h3>
				<?php
				$userferry_format_id = 'userferry-tpl-format';
				require USERFERRY_PATH . 'templates/admin/partials/format.php';
				?>
			</section>
		</div>
		<footer class="userferry-modal__foot">
			<span class="userferry-step__error" data-editor-error role="alert"></span>
			<button type="button" class="userferry-btn userferry-btn--ghost" data-modal-close><?php esc_html_e( 'Cancel', 'userferry' ); ?></button>
			<button type="button" class="userferry-btn userferry-btn--primary" data-editor-save><?php esc_html_e( 'Save template', 'userferry' ); ?></button>
		</footer>
	</div>
</div>
