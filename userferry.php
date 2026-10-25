<?php
/**
 * Plugin Name:       UserFerry – Export & Import Users
 * Description:       Export and import WordPress users with their custom profile fields — CSV, Excel (XLSX), JSON or XML. Reusable export templates and a downloadable history.
 * Version:           1.0.0
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Md. Farhan Sadik
 * Text Domain:       userferry
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package UserFerry
 */

// Block direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All constants are prefixed with USERFERRY_ so they can never collide with
 * any other plugin or theme.
 */
define( 'USERFERRY_VERSION', '1.0.0' );
define( 'USERFERRY_FILE', __FILE__ );
define( 'USERFERRY_PATH', plugin_dir_path( __FILE__ ) );
define( 'USERFERRY_URL', plugin_dir_url( __FILE__ ) );
define( 'USERFERRY_BASENAME', plugin_basename( __FILE__ ) );
define( 'USERFERRY_ASSETS_URL', USERFERRY_URL . 'assets/' );
define( 'USERFERRY_MODULES_PATH', USERFERRY_PATH . 'modules/' );
define( 'USERFERRY_MIN_PHP_VERSION', '7.4' );
define( 'USERFERRY_TEXT_DOMAIN', 'userferry' );
define( 'USERFERRY_OPTION_KEY', 'userferry_settings' );

/**
 * Self-contained includes — explicit, no autoloader and no namespaces, so the
 * plugin has zero dependency collisions with other plugins.
 */
require_once USERFERRY_PATH . 'includes/functions.php';
require_once USERFERRY_PATH . 'includes/class-userferry-helper.php';
require_once USERFERRY_PATH . 'includes/class-userferry-loader.php';
require_once USERFERRY_PATH . 'includes/class-userferry-settings.php';
require_once USERFERRY_PATH . 'includes/class-userferry-install.php';
require_once USERFERRY_PATH . 'includes/class-userferry-storage-check.php';
require_once USERFERRY_PATH . 'includes/class-userferry-cron.php';
require_once USERFERRY_PATH . 'includes/class-userferry-fields.php';
require_once USERFERRY_PATH . 'includes/class-userferry-assets.php';
require_once USERFERRY_PATH . 'includes/class-userferry-admin.php';
require_once USERFERRY_PATH . 'traits/trait-userferry-ajax-guard.php';

// Feature modules.
require_once USERFERRY_MODULES_PATH . 'export/class-userferry-export-config.php';
require_once USERFERRY_MODULES_PATH . 'export/class-userferry-export-query.php';
require_once USERFERRY_MODULES_PATH . 'export/class-userferry-export-writer.php';
require_once USERFERRY_MODULES_PATH . 'export/class-userferry-export-job.php';
require_once USERFERRY_MODULES_PATH . 'export/class-userferry-export-download.php';
require_once USERFERRY_MODULES_PATH . 'export/class-userferry-export-ajax.php';
require_once USERFERRY_MODULES_PATH . 'import/class-userferry-import-reader.php';
require_once USERFERRY_MODULES_PATH . 'import/class-userferry-import-job.php';
require_once USERFERRY_MODULES_PATH . 'import/class-userferry-import-ajax.php';
require_once USERFERRY_MODULES_PATH . 'templates/class-userferry-templates.php';
require_once USERFERRY_MODULES_PATH . 'templates/class-userferry-templates-ajax.php';
require_once USERFERRY_MODULES_PATH . 'history/class-userferry-history.php';
require_once USERFERRY_MODULES_PATH . 'history/class-userferry-history-ajax.php';

require_once USERFERRY_PATH . 'includes/class-userferry-plugin.php';

/**
 * Boot the plugin on `plugins_loaded`.
 *
 * @return UserFerry_Plugin
 */
function userferry() {
	return UserFerry_Plugin::instance();
}

add_action( 'plugins_loaded', 'userferry' );

/**
 * Activation hook — create tables, the protected exports folder and defaults.
 */
function userferry_activate() {
	UserFerry_Install::install();
	update_option( 'userferry_activation_redirect', true, false );
}
register_activation_hook( __FILE__, 'userferry_activate' );

/**
 * Deactivation hook — keep data, just stop the scheduled cleanup.
 */
function userferry_deactivate() {
	UserFerry_Cron::unschedule();
}
register_deactivation_hook( __FILE__, 'userferry_deactivate' );
