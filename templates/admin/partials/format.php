<?php
/**
 * Format + filename picker.
 *
 * @package UserFerry
 *
 * @var string $userferry_format_id Unique id prefix.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$userferry_settings = UserFerry_Settings::get();
$userferry_has_zip  = class_exists( 'ZipArchive' );
$userferry_descs    = array(
	'csv'  => __( 'Opens in Excel, Google Sheets and Numbers. Best for imports into other tools.', 'userferry' ),
	'xlsx' => __( 'A real Excel workbook with a styled header row, filters and frozen headings.', 'userferry' ),
	'json' => __( 'An array of user objects for developers, scripts and APIs.', 'userferry' ),
	'xml'  => __( 'Structured markup for reporting systems that expect XML.', 'userferry' ),
);
?>
<div class="userferry-formatpick" data-userferry-format data-format-id="<?php echo esc_attr( $userferry_format_id ); ?>">
	<fieldset class="userferry-formats">
		<legend class="screen-reader-text"><?php esc_html_e( 'File format', 'userferry' ); ?></legend>
		<?php foreach ( UserFerry_Export_Writer::formats() as $userferry_key => $userferry_format ) : ?>
			<?php $userferry_disabled = ( 'xlsx' === $userferry_key && ! $userferry_has_zip ); ?>
			<label class="userferry-format userferry-format--<?php echo esc_attr( $userferry_key ); ?> <?php echo $userferry_disabled ? 'is-disabled' : ''; ?>">
				<input type="radio" name="<?php echo esc_attr( $userferry_format_id ); ?>-format" value="<?php echo esc_attr( $userferry_key ); ?>" <?php checked( $userferry_settings['default_format'], $userferry_key ); ?> <?php disabled( $userferry_disabled ); ?>>
				<span class="userferry-format__ext">.<?php echo esc_html( $userferry_key ); ?></span>
				<strong><?php echo esc_html( $userferry_format['label'] ); ?></strong>
				<span class="userferry-format__desc">
					<?php
					echo esc_html( $userferry_disabled ? __( 'Unavailable: the PHP ZipArchive extension is not installed on this server.', 'userferry' ) : $userferry_descs[ $userferry_key ] );
					?>
				</span>
			</label>
		<?php endforeach; ?>
	</fieldset>

	<div class="userferry-field">
		<label for="<?php echo esc_attr( $userferry_format_id ); ?>-filename"><?php esc_html_e( 'File name', 'userferry' ); ?></label>
		<div class="userferry-filename">
			<input type="text" id="<?php echo esc_attr( $userferry_format_id ); ?>-filename" class="userferry-input" data-format-filename value="<?php echo esc_attr( $userferry_settings['default_filename'] ); ?>" maxlength="80" spellcheck="false">
			<span class="userferry-filename__suffix" data-format-suffix>-<?php echo esc_html( wp_date( 'Ymd' ) ); ?>-….<?php echo esc_html( $userferry_settings['default_format'] ); ?></span>
		</div>
		<p class="userferry-help"><?php esc_html_e( 'The date and a short random code are added automatically, so exports never overwrite each other.', 'userferry' ); ?></p>
	</div>
</div>
