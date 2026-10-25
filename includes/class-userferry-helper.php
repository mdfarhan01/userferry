<?php
/**
 * Small static helper utilities shared across admin screens and modules.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Helper' ) ) {

	/**
	 * Class UserFerry_Helper
	 */
	class UserFerry_Helper {

		/**
		 * Version compare helper.
		 *
		 * @param string $version    Version to check.
		 * @param string $compare_to Minimum required version.
		 * @return bool
		 */
		public static function version_ok( $version, $compare_to ) {
			return version_compare( $version, $compare_to, '>=' );
		}

		/**
		 * Current plugin admin page slug, or ''.
		 *
		 * @return string
		 */
		public static function current_page() {
			// WordPress core resolves the requested admin page into $plugin_page.
			global $plugin_page;
			$page = is_string( $plugin_page ) ? sanitize_key( $plugin_page ) : '';
			return array_key_exists( $page, userferry_admin_pages() ) ? $page : '';
		}

		/**
		 * Echo one field checkbox for the field picker.
		 *
		 * @param string $key     Field key.
		 * @param string $label   Label.
		 * @param bool   $checked Checked.
		 */
		public static function field_item( $key, $label, $checked = false ) {
			?>
			<label class="userferry-fp__item" data-key="<?php echo esc_attr( $key ); ?>" data-search="<?php echo esc_attr( strtolower( $label . ' ' . $key ) ); ?>">
				<input type="checkbox" value="<?php echo esc_attr( $key ); ?>" data-label="<?php echo esc_attr( $label ); ?>" <?php checked( $checked ); ?>>
				<span class="userferry-fp__text">
					<span class="userferry-fp__label"><?php echo esc_html( $label ); ?></span>
					<code class="userferry-fp__key"><?php echo esc_html( $key ); ?></code>
				</span>
			</label>
			<?php
		}

		/**
		 * Echo a file-format badge.
		 *
		 * @param string $format Format key.
		 */
		public static function format_badge( $format ) {
			$format = sanitize_key( $format );
			printf( '<span class="userferry-badge userferry-badge--%1$s">%2$s</span>', esc_attr( $format ), esc_html( strtoupper( $format ) ) );
		}

		/**
		 * Human date from a MySQL datetime in site time.
		 *
		 * @param string $mysql Datetime.
		 * @return string
		 */
		public static function date( $mysql ) {
			return date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $mysql ) );
		}
	}
}
