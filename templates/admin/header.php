<?php
/**
 * Shared shell (left sidebar + top bar) for every plugin screen.
 *
 * @var array $args {
 *     @type string $active   Current page slug.
 *     @type string $title    Page title shown in the top bar.
 *     @type string $subtitle Short description under the title.
 * }
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$userferry_active   = isset( $args['active'] ) ? (string) $args['active'] : '';
$userferry_title    = isset( $args['title'] ) ? (string) $args['title'] : '';
$userferry_subtitle = isset( $args['subtitle'] ) ? (string) $args['subtitle'] : '';

$userferry_nav_groups = array(
	array(
		'label' => __( 'Overview', 'userferry' ),
		'items' => array( 'userferry' ),
	),
	array(
		'label' => __( 'Users', 'userferry' ),
		'items' => array( 'userferry-export', 'userferry-import', 'userferry-templates', 'userferry-history' ),
	),
	array(
		'label' => __( 'Plugin', 'userferry' ),
		'items' => array( 'userferry-settings', 'userferry-docs' ),
	),
);
$userferry_pages      = userferry_admin_pages();

$userferry_notices = array(
	'settings-saved'  => __( 'Settings saved.', 'userferry' ),
	'template-saved'  => __( 'Template saved.', 'userferry' ),
	'storage-checked' => __( 'File storage check finished.', 'userferry' ),
);
// Display-only flag set by the plugin's own redirects (no data is changed).
$userferry_notice = sanitize_key( (string) filter_input( INPUT_GET, 'userferry_notice', FILTER_UNSAFE_RAW ) );
?>
<div class="userferry-app userferry-wrap">

	<aside class="userferry-sidebar" aria-label="<?php esc_attr_e( 'UserFerry', 'userferry' ); ?>">
		<a class="userferry-sidebar__brand" href="<?php echo esc_url( userferry_admin_url( 'userferry' ) ); ?>">
			<img class="userferry-sidebar__logo" src="<?php echo esc_url( USERFERRY_ASSETS_URL . 'images/icon-128x128.gif' ); ?>" srcset="<?php echo esc_url( USERFERRY_ASSETS_URL . 'images/icon-128x128.gif' ); ?> 1x, <?php echo esc_url( USERFERRY_ASSETS_URL . 'images/icon-256x256.gif' ); ?> 2x" width="36" height="36" alt="" aria-hidden="true">
			<span class="userferry-sidebar__brand-text">
				<strong><?php esc_html_e( 'UserFerry', 'userferry' ); ?></strong>
				<span><?php esc_html_e( 'Export & Import Users', 'userferry' ); ?></span>
			</span>
		</a>

		<nav class="userferry-sidebar__nav">
			<?php foreach ( $userferry_nav_groups as $userferry_group ) : ?>
				<?php
				$userferry_links = array();
				foreach ( $userferry_group['items'] as $userferry_slug ) {
					if ( isset( $userferry_pages[ $userferry_slug ] ) && current_user_can( $userferry_pages[ $userferry_slug ]['cap'] ) ) {
						$userferry_links[ $userferry_slug ] = $userferry_pages[ $userferry_slug ];
					}
				}
				if ( empty( $userferry_links ) ) {
					continue;
				}
				?>
				<p class="userferry-sidebar__group"><?php echo esc_html( $userferry_group['label'] ); ?></p>
				<?php foreach ( $userferry_links as $userferry_slug => $userferry_item ) : ?>
					<a href="<?php echo esc_url( userferry_admin_url( $userferry_slug ) ); ?>" class="userferry-sidebar__link<?php echo $userferry_active === $userferry_slug ? ' is-active' : ''; ?>"<?php echo $userferry_active === $userferry_slug ? ' aria-current="page"' : ''; ?>>
						<span class="dashicons <?php echo esc_attr( $userferry_item['icon'] ); ?>" aria-hidden="true"></span>
						<span><?php echo esc_html( $userferry_item['label'] ); ?></span>
					</a>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</nav>

		<div class="userferry-sidebar__footer">
			<div class="userferry-sidebar__card">
				<p><?php esc_html_e( 'Developed by', 'userferry' ); ?></p>
				<strong>Md. Farhan Sadik</strong>
			</div>
			<span class="userferry-sidebar__version">
				<?php
				/* translators: %s: plugin version */
				echo esc_html( sprintf( __( 'Version %s', 'userferry' ), USERFERRY_VERSION ) );
				?>
			</span>
		</div>
	</aside>

	<div class="userferry-main">
		<header class="userferry-topbar">
			<div class="userferry-topbar__title">
				<h1><?php echo esc_html( $userferry_title ); ?></h1>
				<p><?php echo esc_html( $userferry_subtitle ); ?></p>
			</div>
			<div class="userferry-topbar__actions">
				<?php userferry_get_template( 'admin/partials/actions', array( 'active' => $userferry_active ) ); ?>
			</div>
		</header>
		<hr class="wp-header-end">

		<main class="userferry-content">
			<?php
			$userferry_storage = get_transient( UserFerry_Storage_Check::TRANSIENT );
			if ( 'userferry-settings' !== $userferry_active && current_user_can( 'manage_options' ) && is_array( $userferry_storage ) && 'exposed' === $userferry_storage['status'] ) :
				?>
				<div class="notice notice-error">
					<p>
						<strong><?php esc_html_e( 'Export files can be downloaded without logging in.', 'userferry' ); ?></strong>
						<?php esc_html_e( 'Your web server ignores the folder’s protection rules (common on Nginx).', 'userferry' ); ?>
						<a href="<?php echo esc_url( userferry_admin_url( 'userferry-settings' ) . '#userferry-storage' ); ?>"><?php esc_html_e( 'See how to fix it', 'userferry' ); ?></a>
					</p>
				</div>
			<?php endif; ?>
			<?php if ( isset( $userferry_notices[ $userferry_notice ] ) ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $userferry_notices[ $userferry_notice ] ); ?></p></div>
			<?php endif; ?>
