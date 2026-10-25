<?php
/**
 * Core orchestrator. Singleton on purpose — exactly one instance ever runs.
 * Every action and filter is declared here and registered via the Loader.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Plugin' ) ) {

	/**
	 * Class UserFerry_Plugin
	 */
	final class UserFerry_Plugin {

		/**
		 * Singleton instance.
		 *
		 * @var UserFerry_Plugin|null
		 */
		private static $instance = null;

		/**
		 * Hook loader.
		 *
		 * @var UserFerry_Loader
		 */
		public $loader;

		/**
		 * Assets manager.
		 *
		 * @var UserFerry_Assets
		 */
		public $assets;

		/**
		 * Admin screens manager.
		 *
		 * @var UserFerry_Admin
		 */
		public $admin;

		/**
		 * Get (and lazily create) the singleton.
		 *
		 * @return UserFerry_Plugin
		 */
		public static function instance() {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Constructor — private, use ::instance().
		 */
		private function __construct() {
			if ( ! $this->environment_ok() ) {
				return;
			}

			UserFerry_Install::maybe_upgrade();

			$this->loader = new UserFerry_Loader();
			$this->assets = new UserFerry_Assets();
			$this->admin  = new UserFerry_Admin();

			$this->define_common_hooks();
			$this->define_admin_hooks();
			$this->define_module_hooks();

			$this->loader->run();
		}

		/**
		 * Bail with an admin notice if PHP is too old, instead of a fatal error.
		 *
		 * @return bool
		 */
		private function environment_ok() {
			if ( ! UserFerry_Helper::version_ok( PHP_VERSION, USERFERRY_MIN_PHP_VERSION ) ) {
				add_action( 'admin_notices', array( $this, 'notice_min_php' ) );
				return false;
			}
			return true;
		}

		/**
		 * Hooks that run on every request (cron runs outside wp-admin).
		 */
		private function define_common_hooks() {
			// Translations are loaded automatically by WordPress.org (WP 4.6+).
			$this->loader->add_action( UserFerry_Cron::HOOK, 'UserFerry_Cron', 'run' );
		}

		/**
		 * Admin screens, assets, settings form and plugin row links.
		 */
		private function define_admin_hooks() {
			if ( ! is_admin() ) {
				return;
			}
			$this->loader->add_action( 'admin_menu', $this->admin, 'register_menu' );
			$this->loader->add_action( 'admin_enqueue_scripts', $this->assets, 'admin_assets' );
			$this->loader->add_action( 'admin_init', $this->admin, 'maybe_activation_redirect' );
			$this->loader->add_action( 'admin_post_userferry_save_settings', 'UserFerry_Settings', 'handle_save' );
			$this->loader->add_action( 'admin_post_userferry_storage_recheck', 'UserFerry_Storage_Check', 'handle_recheck' );
			$this->loader->add_filter( 'site_status_tests', 'UserFerry_Storage_Check', 'site_health_tests' );
			$this->loader->add_filter( 'admin_body_class', $this->admin, 'body_class' );
			$this->loader->add_filter( 'admin_footer_text', $this->admin, 'footer_text' );
			$this->loader->add_filter( 'plugin_action_links_' . USERFERRY_BASENAME, $this->admin, 'plugin_action_links' );
			$this->loader->add_filter( 'plugin_row_meta', $this->admin, 'plugin_row_meta', 10, 2 );
		}

		/**
		 * AJAX endpoints and handlers of every feature module.
		 */
		private function define_module_hooks() {
			if ( ! is_admin() ) {
				return;
			}

			$download = new UserFerry_Export_Download();
			$this->loader->add_action( 'admin_post_userferry_download', $download, 'handle' );

			$controllers = array( 'UserFerry_Export_Ajax', 'UserFerry_Import_Ajax', 'UserFerry_Templates_Ajax', 'UserFerry_History_Ajax' );
			foreach ( $controllers as $class ) {
				$controller = new $class();
				foreach ( $class::actions() as $action => $method ) {
					$this->loader->add_action( 'wp_ajax_' . $action, $controller, $method );
				}
			}
		}

		/**
		 * Notice: PHP version too old.
		 */
		public function notice_min_php() {
			$message = sprintf(
				/* translators: 1: Required PHP version 2: Current PHP version */
				esc_html__( 'UserFerry requires PHP %1$s or higher. Your server is running %2$s.', 'userferry' ),
				USERFERRY_MIN_PHP_VERSION,
				PHP_VERSION
			);
			printf( '<div class="notice notice-error"><p>%s</p></div>', wp_kses_post( $message ) );
		}

		/**
		 * Prevent cloning of the singleton.
		 */
		private function __clone() {}

		/**
		 * Prevent unserializing of the singleton.
		 */
		public function __wakeup() {
			_doing_it_wrong( __FUNCTION__, esc_html__( 'Cannot unserialize singleton.', 'userferry' ), '1.0.0' );
		}
	}
}
