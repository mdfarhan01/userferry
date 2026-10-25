<?php
/**
 * Builds the wp-admin menu and renders every screen inside the shared
 * app shell (left sidebar + top bar). Menu slugs, DOM ids and classes are
 * all prefixed with `userferry` so the screens never clash with other plugins.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'UserFerry_Admin' ) ) {

	/**
	 * Class UserFerry_Admin
	 */
	class UserFerry_Admin {

		/**
		 * Top-level menu slug.
		 *
		 * @var string
		 */
		const MENU_SLUG = 'userferry';

		/**
		 * Register the top-level admin menu and its submenus.
		 */
		public function register_menu() {
			add_menu_page(
				__( 'UserFerry', 'userferry' ),
				__( 'UserFerry', 'userferry' ),
				userferry_capability(),
				self::MENU_SLUG,
				array( $this, 'render_page' ),
				'dashicons-database-export',
				71
			);

			foreach ( userferry_admin_pages() as $slug => $page ) {
				add_submenu_page(
					self::MENU_SLUG,
					$page['title'] . ' ‹ ' . __( 'UserFerry', 'userferry' ),
					$page['label'],
					$page['cap'],
					$slug,
					array( $this, 'render_page' )
				);
			}
		}

		/**
		 * Render the active screen inside the shared shell.
		 */
		public function render_page() {
			$active = UserFerry_Helper::current_page();
			$pages  = userferry_admin_pages();

			if ( ! $active || ! current_user_can( $pages[ $active ]['cap'] ) ) {
				wp_die( esc_html__( 'You do not have permission to view this page.', 'userferry' ) );
			}

			$page = $pages[ $active ];

			userferry_get_template(
				'admin/header',
				array(
					'active'   => $active,
					'title'    => $page['title'],
					'subtitle' => $page['subtitle'],
				)
			);
			userferry_get_template( $page['template'], array( 'active' => $active ) );
			userferry_get_template( 'admin/footer' );
		}

		/**
		 * Add a body class on the plugin's screens (used to tune wp-admin chrome).
		 *
		 * @param string $classes Classes.
		 * @return string
		 */
		public function body_class( $classes ) {
			return UserFerry_Helper::current_page() ? $classes . ' userferry-screen' : $classes;
		}

		/**
		 * Footer credit on the plugin's screens.
		 *
		 * @param string $text Footer text.
		 * @return string
		 */
		public function footer_text( $text ) {
			if ( ! UserFerry_Helper::current_page() ) {
				return $text;
			}
			/* translators: %s: developer name */
			return esc_html( sprintf( __( 'UserFerry is developed by %s.', 'userferry' ), 'Md. Farhan Sadik' ) );
		}

		/**
		 * Redirect to the dashboard once, right after activation.
		 *
		 * The flag is only consumed by a real wp-admin page load, so a background
		 * request (Heartbeat, admin-ajax, admin-post, cron, REST) that happens to
		 * run first cannot swallow it.
		 */
		public function maybe_activation_redirect() {
			if ( ! get_option( 'userferry_activation_redirect' ) ) {
				return;
			}

			global $pagenow;
			$background_pages = array( 'admin-ajax.php', 'admin-post.php', 'async-upload.php', 'admin-footer.php' );
			if (
				wp_doing_ajax()
				|| wp_doing_cron()
				|| ( defined( 'REST_REQUEST' ) && REST_REQUEST )
				|| ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST )
				|| in_array( $pagenow, $background_pages, true )
			) {
				return;
			}

			delete_option( 'userferry_activation_redirect' );

			// Skip on network admin and when several plugins were activated at once (core flag, read-only).
			if ( is_network_admin() || null !== filter_input( INPUT_GET, 'activate-multi' ) || ! current_user_can( userferry_capability() ) ) {
				return;
			}

			wp_safe_redirect( userferry_admin_url( self::MENU_SLUG ) );
			exit;
		}

		/**
		 * Action links on the Plugins screen.
		 *
		 * @param array $links Links.
		 * @return array
		 */
		public function plugin_action_links( $links ) {
			array_unshift(
				$links,
				sprintf( '<a href="%s">%s</a>', esc_url( userferry_admin_url( 'userferry-export' ) ), esc_html__( 'Export', 'userferry' ) ),
				sprintf( '<a href="%s">%s</a>', esc_url( userferry_admin_url( 'userferry-import' ) ), esc_html__( 'Import', 'userferry' ) )
			);
			return $links;
		}

		/**
		 * Row meta on the Plugins screen.
		 *
		 * @param array  $meta Meta links.
		 * @param string $file Plugin file.
		 * @return array
		 */
		public function plugin_row_meta( $meta, $file ) {
			if ( USERFERRY_BASENAME === $file ) {
				$meta[] = sprintf( '<a href="%s">%s</a>', esc_url( userferry_admin_url( 'userferry-docs' ) ), esc_html__( 'Documentation', 'userferry' ) );
			}
			return $meta;
		}
	}
}
