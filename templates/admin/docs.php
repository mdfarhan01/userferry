<?php
/**
 * Documentation screen.
 *
 * @package UserFerry
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$userferry_kses = array(
	'strong' => array(),
	'em'     => array(),
	'code'   => array(),
	'a'      => array( 'href' => array() ),
);

/**
 * Echo a translated paragraph that may contain basic markup.
 *
 * @param string $text Translated text.
 */
$userferry_p = static function ( $text ) use ( $userferry_kses ) {
	echo '<p>' . wp_kses( $text, $userferry_kses ) . '</p>';
};

$userferry_video_id = 'TNJCre6AAvk';

$userferry_icons = array(
	'video'      => 'video-alt3',
	'start'      => 'flag',
	'export'     => 'upload',
	'fields'     => 'columns',
	'templates'  => 'portfolio',
	'import'     => 'download',
	'formats'    => 'media-spreadsheet',
	'history'    => 'backup',
	'settings'   => 'admin-generic',
	'security'   => 'shield',
	'faq'        => 'editor-help',
	'developers' => 'editor-code',
	'about'      => 'info-outline',
);

$userferry_toc = array(
	'video'      => __( 'Video guide', 'userferry' ),
	'start'      => __( 'Getting started', 'userferry' ),
	'export'     => __( 'Running an export', 'userferry' ),
	'fields'     => __( 'Choosing fields', 'userferry' ),
	'templates'  => __( 'Templates', 'userferry' ),
	'import'     => __( 'Importing users', 'userferry' ),
	'formats'    => __( 'File formats', 'userferry' ),
	'history'    => __( 'History & downloads', 'userferry' ),
	'settings'   => __( 'Settings reference', 'userferry' ),
	'security'   => __( 'Security & privacy', 'userferry' ),
	'faq'        => __( 'FAQ', 'userferry' ),
	'developers' => __( 'Developer hooks', 'userferry' ),
	'about'      => __( 'About', 'userferry' ),
);
?>

<div class="userferry-docshero">
	<div class="userferry-docshero__text">
		<h2><?php esc_html_e( 'How can we help?', 'userferry' ); ?></h2>
		<p><?php esc_html_e( 'Start with the guide that matches what you want to do, or browse every topic in the contents list.', 'userferry' ); ?></p>
	</div>
	<div class="userferry-docshero__links">
		<?php
		$userferry_quick = array(
			'export'    => __( 'Export users to a file', 'userferry' ),
			'import'    => __( 'Import users from a file', 'userferry' ),
			'templates' => __( 'Save and reuse templates', 'userferry' ),
			'faq'       => __( 'Common questions', 'userferry' ),
		);
		foreach ( $userferry_quick as $userferry_id => $userferry_text ) :
			?>
			<a class="userferry-docshero__link" href="#userferry-doc-<?php echo esc_attr( $userferry_id ); ?>">
				<span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-<?php echo esc_attr( $userferry_icons[ $userferry_id ] ); ?>"></span></span>
				<span><strong><?php echo esc_html( $userferry_toc[ $userferry_id ] ); ?></strong><span><?php echo esc_html( $userferry_text ); ?></span></span>
			</a>
		<?php endforeach; ?>
	</div>
</div>

<div class="userferry-docs">
	<nav class="userferry-docs__toc" aria-label="<?php esc_attr_e( 'Contents', 'userferry' ); ?>">
		<p class="userferry-docs__toctitle"><?php esc_html_e( 'Contents', 'userferry' ); ?></p>
		<ol>
			<?php foreach ( $userferry_toc as $userferry_id => $userferry_title ) : ?>
				<li><a href="#userferry-doc-<?php echo esc_attr( $userferry_id ); ?>"><span class="dashicons dashicons-<?php echo esc_attr( $userferry_icons[ $userferry_id ] ); ?>" aria-hidden="true"></span><?php echo esc_html( $userferry_title ); ?></a></li>
			<?php endforeach; ?>
		</ol>
	</nav>

	<div class="userferry-docs__body">

		<article class="userferry-doc" id="userferry-doc-video">
			<h2 class="userferry-doc__title"><span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-video-alt3"></span></span><?php echo esc_html( $userferry_toc['video'] ); ?></h2>
			<?php $userferry_p( __( 'Prefer to watch? This short video walks you through UserFerry.', 'userferry' ) ); ?>
			<div class="userferry-video__frame" data-userferry-video="<?php echo esc_attr( $userferry_video_id ); ?>" data-title="<?php esc_attr_e( 'UserFerry video guide', 'userferry' ); ?>">
				<button type="button" class="userferry-video__play" aria-label="<?php esc_attr_e( 'Play video guide', 'userferry' ); ?>">
					<img class="userferry-video__thumb" src="<?php echo esc_url( USERFERRY_ASSETS_URL . 'images/video-thumbnail.jpg' ); ?>" width="1280" height="720" alt="<?php esc_attr_e( 'UserFerry complete tutorial video thumbnail', 'userferry' ); ?>" loading="lazy" decoding="async">
					<span class="userferry-video__icon" aria-hidden="true"><span class="dashicons dashicons-controls-play"></span></span>
				</button>
			</div>
			<p class="userferry-video__note">
				<?php esc_html_e( 'Nothing is loaded from YouTube until you press play.', 'userferry' ); ?>
				<a href="<?php echo esc_url( 'https://youtu.be/' . $userferry_video_id ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open on YouTube', 'userferry' ); ?></a>
			</p>
		</article>

		<article class="userferry-doc" id="userferry-doc-start">
			<h2 class="userferry-doc__title"><span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-flag"></span></span><?php echo esc_html( $userferry_toc['start'] ); ?></h2>
			<?php
			$userferry_p( __( 'UserFerry exports the people registered on your site — including the custom profile fields that other plugins add — to CSV, Excel, JSON or XML. You decide exactly which users are included and which fields become columns.', 'userferry' ) );
			$userferry_p( __( 'After activation you will find <strong>UserFerry</strong> in the WordPress admin menu. It has seven screens, listed in the sidebar on the left of every plugin page:', 'userferry' ) );
			?>
			<dl class="userferry-doc__dl">
				<dt><?php esc_html_e( 'Dashboard', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Site totals, one-click template exports and your most recent files.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'Export users', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'A four-step wizard: users, fields, format, review.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'Import users', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Create or update users from a CSV, Excel, JSON or XML file.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'Templates', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Create, edit, duplicate, delete and run saved export setups.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'History', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Every export with a download link, who ran it and when.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'Settings', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Defaults, CSV options, history cleanup and privacy options (administrators only).', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'Documentation', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'This page.', 'userferry' ); ?></dd>
			</dl>
			<?php $userferry_p( __( '<strong>Requirements:</strong> WordPress 6.2 or newer and PHP 7.4 or newer. Excel export also needs the PHP <code>ZipArchive</code> extension, which almost every host provides.', 'userferry' ) ); ?>
		</article>

		<article class="userferry-doc" id="userferry-doc-export">
			<h2 class="userferry-doc__title"><span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-upload"></span></span><?php echo esc_html( $userferry_toc['export'] ); ?></h2>
			<?php $userferry_p( __( 'Open <strong>UserFerry → Export users</strong>. The wizard walks you through four steps; you can go back to any finished step from the step bar.', 'userferry' ) ); ?>
			<ol class="userferry-doc__steps">
				<li>
					<h3><?php esc_html_e( 'Users', 'userferry' ); ?></h3>
					<?php $userferry_p( __( 'Choose <strong>All users</strong>, <strong>By role</strong>, <strong>By registration date</strong> or <strong>Specific users</strong>. With specific users you search and tick people from a paged list; your ticks are kept while you search and change pages.', 'userferry' ) ); ?>
					<?php $userferry_p( __( 'Optionally narrow the result with a search term (it matches username, email, display name and profile slug) and choose how rows are sorted. The live counter under the options shows how many users will be exported.', 'userferry' ) ); ?>
				</li>
				<li>
					<h3><?php esc_html_e( 'Fields', 'userferry' ); ?></h3>
					<?php $userferry_p( __( 'Tick the fields you want. Each ticked field becomes a column, shown in the <strong>Column order</strong> strip at the bottom where you can reorder or remove columns. See “Choosing fields” below.', 'userferry' ) ); ?>
				</li>
				<li>
					<h3><?php esc_html_e( 'Format', 'userferry' ); ?></h3>
					<?php $userferry_p( __( 'Pick CSV, Excel (XLSX), JSON or XML and set the file name. The date and a short random code are appended automatically.', 'userferry' ) ); ?>
				</li>
				<li>
					<h3><?php esc_html_e( 'Review & export', 'userferry' ); ?></h3>
					<?php $userferry_p( __( 'Check the summary, optionally save everything as a template, then click <strong>Run export</strong>. A progress bar shows each batch; when the file is ready the download starts by itself and a <strong>Download file</strong> button stays on screen.', 'userferry' ) ); ?>
				</li>
			</ol>
			<div class="userferry-callout">
				<?php $userferry_p( __( '<strong>Large sites:</strong> exports run in batches (200 users per request by default), so even sites with hundreds of thousands of users finish without timeouts. Keep the tab open until the file is ready.', 'userferry' ) ); ?>
			</div>
		</article>

		<article class="userferry-doc" id="userferry-doc-fields">
			<h2 class="userferry-doc__title"><span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-columns"></span></span><?php echo esc_html( $userferry_toc['fields'] ); ?></h2>
			<?php $userferry_p( __( 'Fields are listed in three groups. Each field shows a readable name and, underneath, its real database key so two similar names are never confused.', 'userferry' ) ); ?>
			<dl class="userferry-doc__dl">
				<dt><?php esc_html_e( 'WordPress profile', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'User ID, username, email, first and last name, display name, nickname, role, website, registered date, biographical info, profile slug and status.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'Custom profile fields', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Every other meta key found in the user meta table — data saved by ACF, Ultimate Member, WooCommerce (billing and shipping), BuddyPress, Meta Box, JetEngine, registration form plugins and your own code.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'Hidden & plugin data', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Keys that begin with an underscore, admin screen preferences, dismissed notices and plugin housekeeping. This group is collapsed by default; click “Show” if you need something from it.', 'userferry' ); ?></dd>
			</dl>
			<h3><?php esc_html_e( 'Why each field appears only once', 'userferry' ); ?></h3>
			<?php
			$userferry_p( __( 'Plugins such as ACF save two meta keys for every field: the value (<code>district</code>) and a hidden reference (<code>_district</code>) that only stores the field’s internal ID. Listing both would show every field twice, so the scanner drops the reference key whenever the real value exists, and skips <code>first_name</code>, <code>last_name</code>, <code>nickname</code> and <code>description</code> because they are already in the WordPress profile group.', 'userferry' ) );
			$userferry_p( __( 'The field list is cached for 12 hours. After installing a plugin that adds user fields — or after the first user fills in a new form field — click <strong>Scan custom fields</strong>.', 'userferry' ) );
			?>
			<h3><?php esc_html_e( 'Column order', 'userferry' ); ?></h3>
			<?php $userferry_p( __( 'Columns are added in the order you tick them. Drag a column in the strip to move it, or focus it with the Tab key and use the left and right arrow keys. Press Delete or Backspace to remove it.', 'userferry' ) ); ?>
			<h3><?php esc_html_e( 'How values are written', 'userferry' ); ?></h3>
			<?php $userferry_p( __( 'Simple lists (for example checkbox values) are joined with commas. Complex values are written as JSON so no data is lost. Roles use their readable names.', 'userferry' ) ); ?>
		</article>

		<article class="userferry-doc" id="userferry-doc-templates">
			<h2 class="userferry-doc__title"><span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-portfolio"></span></span><?php echo esc_html( $userferry_toc['templates'] ); ?></h2>
			<?php $userferry_p( __( 'A template stores who to include, the fields in their column order, the format and the file name. Example: a template named “Subscribers — login & education” with role <em>Subscriber</em> and the fields Username, Email, Last name and Education.', 'userferry' ) ); ?>
			<h3><?php esc_html_e( 'Create a template', 'userferry' ); ?></h3>
			<?php $userferry_p( __( 'Either click <strong>New template</strong> on the Templates screen and fill in the editor, or build an export in the wizard and use <strong>Save as new template</strong> on the review step.', 'userferry' ) ); ?>
			<h3><?php esc_html_e( 'Use a template', 'userferry' ); ?></h3>
			<?php $userferry_p( __( '<strong>Export now</strong> runs the template immediately and downloads the file. <strong>Open in wizard</strong> (or “Start from a template” at the top of the wizard) loads it with every field already ticked, so you can adjust anything before exporting.', 'userferry' ) ); ?>
			<h3><?php esc_html_e( 'Edit, duplicate and delete', 'userferry' ); ?></h3>
			<?php $userferry_p( __( 'Use the pencil, copy and bin buttons on each template card. When you load a template into the wizard and change it, the review step also offers <strong>Update</strong> to overwrite the original. Deleting a template does not delete past exports.', 'userferry' ) ); ?>
		</article>

		<article class="userferry-doc" id="userferry-doc-import">
			<h2 class="userferry-doc__title"><span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-download"></span></span><?php echo esc_html( $userferry_toc['import'] ); ?></h2>
			<?php $userferry_p( __( 'Open <strong>Import users</strong> to create or update accounts from a file. Any file exported by this plugin — CSV, Excel, JSON or XML — can be imported again unchanged, so you can move users between sites, or export, edit in Excel and import the changes back.', 'userferry' ) ); ?>
			<ol class="userferry-doc__steps">
				<li>
					<h3><?php esc_html_e( 'Upload file', 'userferry' ); ?></h3>
					<?php $userferry_p( __( 'Drop a .csv, .xlsx, .json or .xml file onto the upload area. The first row (or the keys of each JSON/XML record) must contain the column names. Comma, semicolon and tab separated CSV files are detected automatically.', 'userferry' ) ); ?>
				</li>
				<li>
					<h3><?php esc_html_e( 'Map columns', 'userferry' ); ?></h3>
					<?php $userferry_p( __( 'Every column is matched to a field automatically — by its name, its meta key or common words such as “Email” or “Surname”. Change any row, choose “Don’t import” to ignore a column, or pick “New custom field…” to save it under a meta key of your choice. At least one column must be Email, Username or User ID.', 'userferry' ) ); ?>
				</li>
				<li>
					<h3><?php esc_html_e( 'Options', 'userferry' ); ?></h3>
					<?php $userferry_p( __( 'Decide how existing users are found (email, username or ID), whether they are updated or skipped, whether new users are created, the role for new users without a Role column, whether empty cells are ignored, and whether new users receive the WordPress welcome email.', 'userferry' ) ); ?>
				</li>
				<li>
					<h3><?php esc_html_e( 'Import', 'userferry' ); ?></h3>
					<?php $userferry_p( __( 'Rows are processed in small batches with a progress bar. The summary shows how many users were created, updated, skipped or failed, with the row number and reason for every problem.', 'userferry' ) ); ?>
				</li>
			</ol>
			<h3><?php esc_html_e( 'Good to know', 'userferry' ); ?></h3>
			<ul class="userferry-doc__list">
				<li><?php esc_html_e( 'Usernames and registration dates of existing users never change. The registration date is only used for new users.', 'userferry' ); ?></li>
				<li><?php esc_html_e( 'Roles may be written as names (Subscriber, Editor) or keys (subscriber). Several roles can be separated by commas. You can only assign roles you are allowed to assign, and your own role is never changed.', 'userferry' ); ?></li>
				<li><?php esc_html_e( 'Passwords: a “Password (plain text)” column sets the password. A “Password hash” column — available when “Allow exporting password hashes” is on — keeps users’ existing passwords when moving them between WordPress sites. Without either, new users get a strong random password.', 'userferry' ); ?></li>
				<li><?php esc_html_e( 'Values written as JSON by the export (complex custom fields) are restored as structured data. Simple lists exported as “a, b” are stored as text.', 'userferry' ); ?></li>
				<li><?php esc_html_e( 'Users are not emailed about profile or password changes made by an import.', 'userferry' ); ?></li>
				<li><?php esc_html_e( 'The uploaded file is never kept: its rows are stored in the protected plugin folder only while the import runs.', 'userferry' ); ?></li>
			</ul>
			<div class="userferry-callout userferry-callout--warn">
				<?php $userferry_p( __( '<strong>Tip:</strong> run an export of your users before a large import, so you can restore them if a column was mapped incorrectly.', 'userferry' ) ); ?>
			</div>
		</article>

		<article class="userferry-doc" id="userferry-doc-formats">
			<h2 class="userferry-doc__title"><span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-media-spreadsheet"></span></span><?php echo esc_html( $userferry_toc['formats'] ); ?></h2>
			<div class="userferry-tablewrap">
				<table class="userferry-table userferry-table--doc">
					<thead><tr><th><?php esc_html_e( 'Format', 'userferry' ); ?></th><th><?php esc_html_e( 'Best for', 'userferry' ); ?></th><th><?php esc_html_e( 'Notes', 'userferry' ); ?></th></tr></thead>
					<tbody>
						<tr><td><?php UserFerry_Helper::format_badge( 'csv' ); ?></td><td><?php esc_html_e( 'Excel, Google Sheets, mailing tools, imports', 'userferry' ); ?></td><td><?php esc_html_e( 'Separator and UTF-8 marker are configurable in Settings.', 'userferry' ); ?></td></tr>
						<tr><td><?php UserFerry_Helper::format_badge( 'xlsx' ); ?></td><td><?php esc_html_e( 'Sharing a ready-to-read spreadsheet', 'userferry' ); ?></td><td><?php esc_html_e( 'Styled header row, frozen headings, filter buttons, numeric IDs stored as numbers.', 'userferry' ); ?></td></tr>
						<tr><td><?php UserFerry_Helper::format_badge( 'json' ); ?></td><td><?php esc_html_e( 'Developers, scripts, APIs', 'userferry' ); ?></td><td><?php esc_html_e( 'An array of objects keyed by field key.', 'userferry' ); ?></td></tr>
						<tr><td><?php UserFerry_Helper::format_badge( 'xml' ); ?></td><td><?php esc_html_e( 'Reporting systems that require XML', 'userferry' ); ?></td><td><?php echo wp_kses( __( 'A <code>&lt;users&gt;</code> root with one <code>&lt;user&gt;</code> element per person.', 'userferry' ), $userferry_kses ); ?></td></tr>
					</tbody>
				</table>
			</div>
		</article>

		<article class="userferry-doc" id="userferry-doc-history">
			<h2 class="userferry-doc__title"><span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-backup"></span></span><?php echo esc_html( $userferry_toc['history'] ); ?></h2>
			<?php
			$userferry_p( __( 'Each export is listed with its file name, size, format, number of users and columns, the template used and who ran it. <strong>Download</strong> fetches the file again. Administrators can delete single entries or everything at once.', 'userferry' ) );
			$userferry_p( __( 'Files are removed automatically after the number of days set in Settings (30 by default). Set it to 0 to keep them forever.', 'userferry' ) );
			?>
		</article>

		<article class="userferry-doc" id="userferry-doc-settings">
			<h2 class="userferry-doc__title"><span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-admin-generic"></span></span><?php echo esc_html( $userferry_toc['settings'] ); ?></h2>
			<dl class="userferry-doc__dl">
				<dt><?php esc_html_e( 'Default format / file name', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Pre-filled in the wizard and the template editor.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'Users per batch', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'How many users each request processes. Lower it if exports stall on slow hosting.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'CSV separator', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Comma, semicolon or tab.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'Registered date format', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Raw database value in UTC, or your site’s date format and timezone.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'UTF-8 marker', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Helps Excel show non-English characters correctly.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'Formula protection', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Stops profile values from running as spreadsheet formulas.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'Keep export history / Delete exports after', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Whether exports are listed in History, and for how long files are kept.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'Allow exporting password hashes', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Adds the Password hash field for administrators. Off by default.', 'userferry' ); ?></dd>
				<dt><?php esc_html_e( 'Delete all plugin data on uninstall', 'userferry' ); ?></dt>
				<dd><?php esc_html_e( 'Off by default, so reinstalling keeps your templates.', 'userferry' ); ?></dd>
			</dl>
		</article>

		<article class="userferry-doc" id="userferry-doc-security">
			<h2 class="userferry-doc__title"><span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-shield"></span></span><?php echo esc_html( $userferry_toc['security'] ); ?></h2>
			<?php
			$userferry_p( __( 'Exports and imports touch personal data, so the plugin is strict about who can use them.', 'userferry' ) );
			?>
			<ul class="userferry-doc__list">
				<li><?php echo wp_kses( __( 'Only users with the <code>list_users</code> capability (administrators by default) can export. Importing needs <code>create_users</code> and <code>edit_users</code>; Settings and deleting history need <code>manage_options</code>.', 'userferry' ), $userferry_kses ); ?></li>
				<li><?php echo wp_kses( __( 'Files are saved in a folder inside <code>wp-content/uploads/</code> whose name ends in a random 24-character code, and every file name contains another random 24-character code. Apache, LiteSpeed and IIS block the folder automatically; downloads always go through WordPress with a security check.', 'userferry' ), $userferry_kses ); ?></li>
				<li><?php echo wp_kses( __( 'The plugin tests the folder with an anonymous request to your own site and shows the result under <strong>Settings → File storage security</strong> and in <strong>Tools → Site Health</strong>.', 'userferry' ), $userferry_kses ); ?></li>
				<li><?php echo wp_kses( __( 'To keep files outside the web root entirely, add <code>define( \'USERFERRY_EXPORT_DIR\', \'/path/outside/public_html/userferry\' );</code> to wp-config.php.', 'userferry' ), $userferry_kses ); ?></li>
				<li><?php esc_html_e( 'Session tokens and capability data are never exportable. Password hashes are only available when an administrator turns them on.', 'userferry' ); ?></li>
				<li><?php esc_html_e( 'Every request is protected by a WordPress nonce, and all input is sanitized.', 'userferry' ); ?></li>
				<li><?php esc_html_e( 'The plugin never contacts other websites and does not load Gravatar images — the user picker shows initials instead. The only HTTP request it makes is the folder check to your own site. The video guide on this screen is the one exception: it loads from YouTube only after you press play.', 'userferry' ); ?></li>
				<li><?php esc_html_e( 'Imports are limited to 100,000 rows, 250 columns, 64 KB per cell and 20 MB for JSON files. Every part of an Excel file is size-checked before it is read, and a file that breaks a limit — or has values in columns without a name — is rejected with a clear message instead of being cut off silently.', 'userferry' ); ?></li>
			</ul>
			<div class="userferry-callout userferry-callout--warn">
				<?php $userferry_p( __( '<strong>Nginx users:</strong> Nginx ignores .htaccess files. If the storage check reports the folder as publicly accessible, copy the rule shown in Settings into your server block and reload Nginx.', 'userferry' ) ); ?>
			</div>
		</article>

		<article class="userferry-doc" id="userferry-doc-faq">
			<h2 class="userferry-doc__title"><span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-editor-help"></span></span><?php echo esc_html( $userferry_toc['faq'] ); ?></h2>
			<details class="userferry-faq">
				<summary><?php esc_html_e( 'Can I export users’ passwords?', 'userferry' ); ?></summary>
				<?php $userferry_p( __( 'No plugin can export readable passwords: WordPress only stores a one-way hash. If you are moving users to another WordPress site, an administrator can enable “Allow exporting password hashes” in Settings; the hash keeps working on the new site. For anything else, ask users to reset their password.', 'userferry' ) ); ?>
			</details>
			<details class="userferry-faq">
				<summary><?php esc_html_e( 'A custom field I need is missing.', 'userferry' ); ?></summary>
				<?php $userferry_p( __( 'A meta key only exists once at least one user has a value for it. Make sure someone has filled it in, then click “Scan custom fields”. Also open the “Hidden & plugin data” group — keys beginning with an underscore are listed there.', 'userferry' ) ); ?>
			</details>
			<details class="userferry-faq">
				<summary><?php esc_html_e( 'Accents or Bangla characters look broken in Excel.', 'userferry' ); ?></summary>
				<?php $userferry_p( __( 'Turn on “Add UTF-8 marker to CSV” in Settings, or export as Excel (XLSX), which always stores text as UTF-8.', 'userferry' ) ); ?>
			</details>
			<details class="userferry-faq">
				<summary><?php esc_html_e( 'The export stops partway through.', 'userferry' ); ?></summary>
				<?php $userferry_p( __( 'Lower “Users per batch” in Settings (try 50) so each request does less work, and keep the browser tab open until the download starts.', 'userferry' ) ); ?>
			</details>
			<details class="userferry-faq">
				<summary><?php esc_html_e( 'Does it work on multisite?', 'userferry' ); ?></summary>
				<?php $userferry_p( __( 'Yes, per site. Each site exports the users that belong to it.', 'userferry' ) ); ?>
			</details>
		</article>

		<article class="userferry-doc" id="userferry-doc-developers">
			<h2 class="userferry-doc__title"><span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-editor-code"></span></span><?php echo esc_html( $userferry_toc['developers'] ); ?></h2>
			<?php $userferry_p( __( 'All hooks are prefixed with <code>userferry_</code>. All PHP classes are prefixed with <code>UserFerry_</code>.', 'userferry' ) ); ?>
			<div class="userferry-tablewrap">
				<table class="userferry-table userferry-table--doc">
					<thead><tr><th><?php esc_html_e( 'Hook', 'userferry' ); ?></th><th><?php esc_html_e( 'Type', 'userferry' ); ?></th><th><?php esc_html_e( 'Purpose', 'userferry' ); ?></th></tr></thead>
					<tbody>
						<tr><td><code>userferry_capability</code></td><td><?php esc_html_e( 'Filter', 'userferry' ); ?></td><td><?php esc_html_e( 'Capability required to export. Default list_users.', 'userferry' ); ?></td></tr>
						<tr><td><code>userferry_core_fields</code></td><td><?php esc_html_e( 'Filter', 'userferry' ); ?></td><td><?php esc_html_e( 'Add, rename or remove WordPress profile fields.', 'userferry' ); ?></td></tr>
						<tr><td><code>userferry_scanned_fields</code></td><td><?php esc_html_e( 'Filter', 'userferry' ); ?></td><td><?php esc_html_e( 'Adjust the scanned custom fields or their labels.', 'userferry' ); ?></td></tr>
						<tr><td><code>userferry_is_system_meta_key</code></td><td><?php esc_html_e( 'Filter', 'userferry' ); ?></td><td><?php esc_html_e( 'Move a meta key in or out of the Hidden & plugin data group.', 'userferry' ); ?></td></tr>
						<tr><td><code>userferry_export_dir</code></td><td><?php esc_html_e( 'Filter', 'userferry' ); ?></td><td><?php esc_html_e( 'Change where export files and temporary import data are stored (or use the USERFERRY_EXPORT_DIR constant).', 'userferry' ); ?></td></tr>
						<tr><td><code>userferry_import_limits</code></td><td><?php esc_html_e( 'Filter', 'userferry' ); ?></td><td><?php esc_html_e( 'Adjust import limits: rows, columns, cell size, JSON size and Excel sizes.', 'userferry' ); ?></td></tr>
						<tr><td><code>userferry_user_query_args</code></td><td><?php esc_html_e( 'Filter', 'userferry' ); ?></td><td><?php esc_html_e( 'Modify the WP_User_Query arguments of an export.', 'userferry' ); ?></td></tr>
						<tr><td><code>userferry_export_value</code></td><td><?php esc_html_e( 'Filter', 'userferry' ); ?></td><td><?php esc_html_e( 'Change any exported value (receives value, field key and WP_User).', 'userferry' ); ?></td></tr>
						<tr><td><code>userferry_import_completed</code></td><td><?php esc_html_e( 'Action', 'userferry' ); ?></td><td><?php esc_html_e( 'Runs after an import finishes (receives the created/updated/skipped/failed counts and job data).', 'userferry' ); ?></td></tr>
						<tr><td><code>userferry_export_completed</code></td><td><?php esc_html_e( 'Action', 'userferry' ); ?></td><td><?php esc_html_e( 'Runs after a file is written (receives path, config and user count).', 'userferry' ); ?></td></tr>
					</tbody>
				</table>
			</div>
			<pre class="userferry-code"><code>// <?php esc_html_e( 'Example: export a field with a friendly value.', 'userferry' ); ?>

add_filter( 'userferry_export_value', function ( $value, $key, $user ) {
	if ( 'account_status' === $key ) {
		return 'approved' === $value ? 'Active' : 'Pending';
	}
	return $value;
}, 10, 3 );</code></pre>
		</article>

		<article class="userferry-doc" id="userferry-doc-about">
			<h2 class="userferry-doc__title"><span class="userferry-doc__icon" aria-hidden="true"><span class="dashicons dashicons-info-outline"></span></span><?php echo esc_html( $userferry_toc['about'] ); ?></h2>
			<?php
			/* translators: 1: plugin version, 2: developer name */
			$userferry_p( sprintf( __( 'UserFerry %1$s is developed and maintained by <strong>%2$s</strong>. It is free software released under the GNU General Public License v2 or later.', 'userferry' ), esc_html( USERFERRY_VERSION ), 'Md. Farhan Sadik' ) );
			?>
		</article>
	</div>
</div>
