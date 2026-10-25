<?php
/**
 * Export wizard.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$userferry_templates = UserFerry_Templates::all();
$userferry_steps     = array(
	1 => array( __( 'Users', 'userferry' ), __( 'Who to include', 'userferry' ), 'dashicons-groups', __( 'Who should be included?', 'userferry' ), __( 'Export everyone, a role, a registration period or hand-picked people. The counter below updates as you choose.', 'userferry' ) ),
	2 => array( __( 'Fields', 'userferry' ), __( 'Choose columns', 'userferry' ), 'dashicons-columns', __( 'Which fields become columns?', 'userferry' ), __( 'Tick the profile and custom fields you need. Each ticked field becomes a column, in the order shown at the bottom.', 'userferry' ) ),
	3 => array( __( 'Format', 'userferry' ), __( 'File type & name', 'userferry' ), 'dashicons-media-spreadsheet', __( 'Choose a file format', 'userferry' ), __( 'Pick the format that suits where the file is going, and name it.', 'userferry' ) ),
	4 => array( __( 'Review & export', 'userferry' ), __( 'Check and download', 'userferry' ), 'dashicons-yes-alt', __( 'Review and export', 'userferry' ), __( 'Check everything, optionally save it as a template, then run the export.', 'userferry' ) ),
);

/**
 * Print the header of a wizard step.
 *
 * @param int   $n     Step number.
 * @param array $steps Step definitions.
 */
$userferry_step_head = static function ( $n, $steps ) {
	?>
	<div class="userferry-step__head">
		<span class="userferry-step__icon" aria-hidden="true"><span class="dashicons <?php echo esc_attr( $steps[ $n ][2] ); ?>"></span></span>
		<div>
			<p class="userferry-step__kicker">
				<?php
				/* translators: 1: current step number, 2: number of steps */
				echo esc_html( sprintf( __( 'Step %1$s of %2$s', 'userferry' ), $n, count( $steps ) ) );
				?>
			</p>
			<h2><?php echo esc_html( $steps[ $n ][3] ); ?></h2>
			<p class="userferry-step__desc"><?php echo esc_html( $steps[ $n ][4] ); ?></p>
		</div>
	</div>
	<?php
};
?>

<div class="userferry-banner" id="userferry-template-banner" hidden>
	<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
	<p data-banner-text></p>
	<button type="button" class="userferry-btn userferry-btn--primary userferry-btn--sm" data-banner-go><?php esc_html_e( 'Go to export', 'userferry' ); ?></button>
	<button type="button" class="userferry-link" data-banner-clear><?php esc_html_e( 'Start blank instead', 'userferry' ); ?></button>
</div>

<div class="userferry-wizard" id="userferry-wizard">
	<ol class="userferry-stepper userferry-stepper--rail">
		<?php foreach ( $userferry_steps as $userferry_n => $userferry_step ) : ?>
			<li>
				<button type="button" class="userferry-stepper__btn <?php echo 1 === $userferry_n ? 'is-current' : ''; ?>" data-goto="<?php echo esc_attr( $userferry_n ); ?>" <?php echo 1 === $userferry_n ? 'aria-current="step"' : 'disabled'; ?>>
					<span class="userferry-stepper__num"><span><?php echo esc_html( $userferry_n ); ?></span></span>
					<span class="userferry-stepper__text">
						<span class="userferry-stepper__label"><?php echo esc_html( $userferry_step[0] ); ?></span>
						<span class="userferry-stepper__meta" data-step-meta="<?php echo esc_attr( $userferry_n ); ?>"><?php echo esc_html( $userferry_step[1] ); ?></span>
					</span>
				</button>
			</li>
		<?php endforeach; ?>
	</ol>

	<section class="userferry-panel userferry-step" data-step="1">
		<?php $userferry_step_head( 1, $userferry_steps ); ?>
		<?php
		$userferry_scope_id    = 'userferry-wiz';
		$userferry_users_table = true;
		require USERFERRY_PATH . 'templates/admin/partials/scope.php';
		?>
		<div class="userferry-step__foot">
			<span class="userferry-matchcount" data-match-count aria-live="polite"></span>
			<span class="userferry-step__error" data-step-error role="alert"></span>
			<button type="button" class="userferry-btn userferry-btn--primary" data-next><?php esc_html_e( 'Continue to fields', 'userferry' ); ?></button>
		</div>
	</section>

	<section class="userferry-panel userferry-step" data-step="2" hidden>
		<?php $userferry_step_head( 2, $userferry_steps ); ?>
		<?php
		$userferry_picker_id = 'userferry-wiz-fields';
		$userferry_selected  = UserFerry_Fields::default_selection();
		require USERFERRY_PATH . 'templates/admin/partials/field-picker.php';
		?>
		<div class="userferry-step__foot">
			<button type="button" class="userferry-btn userferry-btn--ghost" data-prev><?php esc_html_e( 'Back', 'userferry' ); ?></button>
			<span class="userferry-step__error" data-step-error role="alert"></span>
			<button type="button" class="userferry-btn userferry-btn--primary" data-next><?php esc_html_e( 'Continue to format', 'userferry' ); ?></button>
		</div>
	</section>

	<section class="userferry-panel userferry-step" data-step="3" hidden>
		<?php $userferry_step_head( 3, $userferry_steps ); ?>
		<?php
		$userferry_format_id = 'userferry-wiz-format';
		require USERFERRY_PATH . 'templates/admin/partials/format.php';
		?>
		<div class="userferry-step__foot">
			<button type="button" class="userferry-btn userferry-btn--ghost" data-prev><?php esc_html_e( 'Back', 'userferry' ); ?></button>
			<span class="userferry-step__error" data-step-error role="alert"></span>
			<button type="button" class="userferry-btn userferry-btn--primary" data-next><?php esc_html_e( 'Review export', 'userferry' ); ?></button>
		</div>
	</section>

	<section class="userferry-panel userferry-step" data-step="4" hidden>
		<?php $userferry_step_head( 4, $userferry_steps ); ?>

		<div class="userferry-reviewwrap" data-review></div>

		<div class="userferry-savebox" data-savebox>
			<div class="userferry-savebox__text">
				<h3><?php esc_html_e( 'Save these settings as a template', 'userferry' ); ?></h3>
				<p><?php esc_html_e( 'Next time, run the same users, fields and format with one click.', 'userferry' ); ?></p>
			</div>
			<div class="userferry-savebox__actions" data-savebox-actions>
				<button type="button" class="userferry-btn userferry-btn--ghost" data-tpl-update hidden></button>
				<button type="button" class="userferry-btn userferry-btn--ghost" data-tpl-new><?php esc_html_e( 'Save as new template', 'userferry' ); ?></button>
			</div>
			<div class="userferry-savebox__form" data-savebox-form hidden>
				<label class="screen-reader-text" for="userferry-wiz-tplname"><?php esc_html_e( 'Template name', 'userferry' ); ?></label>
				<input type="text" id="userferry-wiz-tplname" class="userferry-input" maxlength="191" placeholder="<?php esc_attr_e( 'Template name, e.g. Subscribers — login & education', 'userferry' ); ?>">
				<button type="button" class="userferry-btn userferry-btn--primary" data-tpl-save><?php esc_html_e( 'Save template', 'userferry' ); ?></button>
				<button type="button" class="userferry-link" data-tpl-cancel><?php esc_html_e( 'Cancel', 'userferry' ); ?></button>
			</div>
		</div>

		<div class="userferry-progress" data-progress hidden></div>

		<div class="userferry-step__foot">
			<button type="button" class="userferry-btn userferry-btn--ghost" data-prev><?php esc_html_e( 'Back', 'userferry' ); ?></button>
			<span class="userferry-step__error" data-step-error role="alert"></span>
			<button type="button" class="userferry-btn userferry-btn--primary userferry-btn--lg" data-run><span class="dashicons dashicons-download" aria-hidden="true"></span><span data-run-label><?php esc_html_e( 'Run export', 'userferry' ); ?></span></button>
		</div>
	</section>
</div>
