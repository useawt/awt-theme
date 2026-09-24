<?php
/**
 * AWT's box on the dashboard.
 *
 * Since AWT installs its own updates, the dashboard is where somebody finds
 * out their site changed — it is the first screen after logging in, and for
 * many site owners the only one they look at. The notice at the top of the
 * admin says what needs doing; this says **what changed**, which is a
 * different question and deserves more than one line.
 *
 * It is the only place release notes reach somebody who never opens AWT
 * Settings. That is the whole reason it exists, and the reason it stays on
 * screen rather than being dismissible.
 *
 * Later it carries more than this — a short feed of posts from `useawt.com`,
 * titles and links only. Titles and links only is not a simplification: an
 * image in here would be fetched by the administrator's own browser, which
 * would hand `useawt.com` the IP address of every logged-in admin on every
 * AWT site. The version check deliberately gives away nothing, and this must
 * not be the thing that undoes that.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\DashboardWidget;

use AWT\Theme\WhatsNew;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Who sees it: the people the What's new screen is already for. */
const CAPABILITY = 'edit_theme_options';

/** How many releases to list before sending people to the full notes. */
const SHOW_RELEASES = 2;

add_action( 'wp_dashboard_setup', __NAMESPACE__ . '\\register' );

/**
 * Put the box on the dashboard, and ask for the right-hand column.
 *
 * WordPress remembers each user's own arrangement, so this only decides where
 * the box lands for somebody who has never moved anything. Anyone who has
 * keeps their layout, and Screen Options can hide it — both correct, and
 * neither is something to work around.
 */
function register(): void {
	if ( ! current_user_can( CAPABILITY ) ) {
		return;
	}

	// `add_meta_box()` rather than `wp_add_dashboard_widget()`: the wrapper
	// always appends to the main column, and the only way to move it
	// afterwards is to rewrite WordPress's meta box registry by hand. This is
	// the same call the wrapper makes, with the column asked for up front.
	add_meta_box(
		'awt_dashboard',
		__( 'AWT', 'awt' ),
		__NAMESPACE__ . '\\render',
		'dashboard',
		'side',
		'core'
	);
}

/** Draw the box. */
function render(): void {
	style();

	$version = \AWT\Theme\AWT_THEME_VERSION;
	$data    = function_exists( '\\AWT\\Theme\\WhatsNew\\changelog' ) ? WhatsNew\changelog() : null;

	printf(
		'<p class="awt-dash__version">%s</p>',
		sprintf(
			/* translators: %s: the installed version, e.g. 2026.09.29. */
			esc_html__( 'You are on AWT %s.', 'awt' ),
			'<strong>' . esc_html( $version ) . '</strong>'
		)
	);

	$releases = is_array( $data ) ? array_slice( (array) ( $data['releases'] ?? array() ), 0, SHOW_RELEASES ) : array();
	if ( ! $releases ) {
		echo '<p>' . esc_html__( 'This copy of AWT has no release notes.', 'awt' ) . '</p>';
		return;
	}

	foreach ( $releases as $release ) {
		printf(
			'<h3 class="awt-dash__release">%s</h3>',
			esc_html(
				sprintf(
					/* translators: 1: version number. 2: release date. */
					__( '%1$s, %2$s', 'awt' ),
					(string) ( $release['version'] ?? '' ),
					(string) ( $release['date'] ?? '' )
				)
			)
		);
		WhatsNew\render_entries( $release );
	}

	printf(
		'<p class="awt-dash__more"><a href="%1$s">%2$s</a></p>',
		esc_url( admin_url( 'themes.php?page=' . \AWT\Theme\AdminPage\MENU_SLUG . '&tab=whats-new' ) ),
		esc_html__( 'All release notes', 'awt' )
	);
}

/**
 * The box's own styling.
 *
 * Scoped to this widget and printed with it. Nothing here may reach
 * WordPress's own UI: AWT's front-end CSS carries global resets, and loading
 * any of it into wp-admin rewrites the line-height of every screen.
 */
function style(): void {
	static $printed = false;
	if ( $printed ) {
		return;
	}
	$printed = true;
	?>
	<style>
		#awt_dashboard .awt-dash__version { margin-block-start: 0; }
		#awt_dashboard .awt-dash__release {
			font-size: 12px;
			text-transform: uppercase;
			letter-spacing: .04em;
			color: #646970;
			margin-block: 1.2em .4em;
		}
		#awt_dashboard .awt-whats-new-entries { margin: 0; }
		#awt_dashboard .awt-whats-new-entry { margin-block-end: .6em; }
		#awt_dashboard .awt-whats-new-badge { font-weight: 600; }
		#awt_dashboard .awt-dash__more { margin-block-end: 0; }
	</style>
	<?php
}
