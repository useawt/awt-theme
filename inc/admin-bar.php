<?php
/**
 * The AWT menu in the WordPress toolbar.
 *
 * A wordmark and a coloured dot at the top level; hovering or focusing it
 * opens four rows — how the version stands, the header, the footer, and the
 * settings screen. It shows on the front end as well as in wp-admin, because
 * the two editing shortcuts are worth having from the page you are looking at.
 *
 * **The dot is never the only thing saying the state.** Its colour is
 * repeated as text in the first row of the menu, and as text for a screen
 * reader on the wordmark itself. That is what lets the colours be chosen for
 * the bar they usually sit on rather than for the worst case: no single fill
 * clears 3:1 against all nine admin colour schemes — the light, ocean, blue
 * and sunrise bars defeat any bright dot — so the fill is chosen for the five
 * dark schemes (4.7:1 at worst) and a dark ring keeps the dot defined on the
 * light ones (4.2:1 against the light bar).
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\AdminBar;

use AWT\Theme\Updates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Who sees the menu. The same capability the settings screen asks for, since
 * every row leads to something only that person can open.
 */
const CAPABILITY = 'edit_theme_options';

/**
 * How the installed version stands: 'current', 'update' or 'unknown'.
 *
 * Read straight from the manifest the update check has already cached, and
 * compared against the versions this site is running. Two other routes were
 * tried and are worse:
 *
 * - `Updates\manifest()` fetches when its cache is cold, which would put a
 *   network request in the way of a page load, and it answers `null` outside
 *   wp-admin by design.
 * - WordPress's own `update_themes` transient is filtered on every read by
 *   that same function, so off the admin it comes back without AWT in it at
 *   all — the menu then said "Update checks are off" on the front end of a
 *   site whose checks were on. Measured, not guessed.
 *
 * The cache is filled by any admin or cron visit and lasts 12 hours, so the
 * front end reads whatever the last check found. Before the first check there
 * is genuinely nothing to report, and 'unknown' says so rather than claiming
 * the site is current.
 *
 * Both halves are compared because they are released together: a site can be
 * running the current theme and a plugin one release behind.
 *
 * @return string One of: current | update | unknown.
 */
function update_state(): string {
	if ( function_exists( '\\AWT\\Theme\\Updates\\enabled' ) && ! Updates\enabled() ) {
		return 'unknown';
	}

	$cached = get_site_transient( Updates\CACHE_KEY );
	if ( ! is_array( $cached ) || ! isset( $cached['version'] ) ) {
		return 'unknown';
	}
	$latest = (string) $cached['version'];

	$installed = array( \AWT\Theme\AWT_THEME_VERSION );
	if ( defined( 'AWT\\Blocks\\AWT_BLOCKS_VERSION' ) ) {
		$installed[] = (string) constant( 'AWT\\Blocks\\AWT_BLOCKS_VERSION' );
	}

	foreach ( $installed as $version ) {
		if ( version_compare( $version, $latest, '<' ) ) {
			return 'update';
		}
	}

	return 'current';
}

/**
 * The words for each state. One place, so the menu row and the text a screen
 * reader hears on the wordmark cannot drift apart.
 *
 * @param string $state From update_state().
 * @return string Translated label.
 */
function state_label( string $state ): string {
	switch ( $state ) {
		case 'update':
			return __( 'Update available', 'awt' );
		case 'unknown':
			return __( 'Update checks are off', 'awt' );
		default:
			return __( 'Up to date', 'awt' );
	}
}

/**
 * Where the first row goes: the update screen when there is something to
 * install, the setting itself when checks are off, and nowhere when the site
 * is current — a link that only says what the row already says is a stop on
 * the way through the menu for no reason.
 *
 * @param string $state From update_state().
 * @return string URL, or an empty string for no link.
 */
function state_href( string $state ): string {
	if ( $state === 'update' ) {
		return admin_url( 'update-core.php' );
	}
	if ( $state === 'unknown' ) {
		return admin_url( 'themes.php?page=awt-settings&tab=tools' );
	}
	return '';
}

/**
 * Add the menu.
 *
 * @param \WP_Admin_Bar $bar The toolbar.
 */
function add_menu( \WP_Admin_Bar $bar ): void {
	if ( ! current_user_can( CAPABILITY ) ) {
		return;
	}

	$state    = update_state();
	$label    = state_label( $state );
	$settings = admin_url( 'themes.php?page=awt-settings' );

	$dot = sprintf(
		'<span class="awt-toolbar__dot awt-toolbar__dot--%s" aria-hidden="true"></span>',
		esc_attr( $state )
	);

	$bar->add_node(
		array(
			'id'    => 'awt',
			// The visible "AWT" stays part of the name a screen reader hears —
			// it is the visible label (WCAG 2.5.3) — and the state is appended
			// rather than repeated, so the whole reads "AWT: Up to date".
			'title' => sprintf(
				// The whole phrase is one string, so a translator sees a
				// sentence rather than a colon and a placeholder — which means
				// the visible wordmark is inside it and would otherwise be
				// announced twice. Hiding the visible copy is safe here
				// because the text it carries is repeated verbatim in the
				// name, which is what WCAG 2.5.3 asks for.
				'<span class="awt-toolbar__mark" aria-hidden="true">AWT</span><span class="awt-toolbar__sr">%2$s</span>%1$s',
				$dot,
				/* translators: %s: the update state, e.g. "Up to date". */
				esc_html( sprintf( __( 'AWT: %s', 'awt' ), $label ) )
			),
			'href'  => $settings,
		)
	);

	$bar->add_node(
		array(
			'id'     => 'awt-status',
			'parent' => 'awt',
			'title'  => $dot . '<span class="awt-toolbar__state">' . esc_html( $label ) . '</span>',
			'href'   => state_href( $state ),
		)
	);

	// Straight into the two template parts an author actually edits. Built
	// from get_stylesheet() so they resolve on any AWT site.
	foreach ( array(
		'header' => __( 'Header', 'awt' ),
		'footer' => __( 'Footer', 'awt' ),
	) as $part => $title ) {
		$bar->add_node(
			array(
				'id'     => 'awt-' . $part,
				'parent' => 'awt',
				'title'  => esc_html( $title ),
				'href'   => add_query_arg(
					array(
						'p'      => '/wp_template_part/' . get_stylesheet() . '//' . $part,
						'canvas' => 'edit',
					),
					admin_url( 'site-editor.php' )
				),
			)
		);
	}

	$bar->add_node(
		array(
			'id'     => 'awt-settings',
			'parent' => 'awt',
			'title'  => esc_html__( 'AWT settings', 'awt' ),
			'href'   => $settings,
		)
	);
}
add_action( 'admin_bar_menu', __NAMESPACE__ . '\\add_menu', 80 );

/**
 * The menu's own CSS.
 *
 * Attached to core's `admin-bar` stylesheet, which is registered on the front
 * end and in wp-admin alike, so it lands after the rules it adjusts and
 * nowhere else. Everything is scoped to `#wpadminbar` — see the rule about
 * keeping this theme's CSS off WordPress's own UI.
 *
 * The wordmark is the marketing site's: IBM Plex Mono at 700, which the theme
 * bundles at 400 and the browser thickens. No `@font-face` is added here —
 * WordPress prints the theme's font faces in wp-admin as well as on the front
 * end, so the family is already declared in both. Worth knowing what that
 * costs: a face is only fetched when something uses it, and these three
 * letters are what makes wp-admin fetch this one — 45 KB, once, then cached.
 *
 * @return string CSS.
 */
function styles(): string {
	return <<<'CSS'
#wpadminbar #wp-admin-bar-awt > .ab-item,
#wpadminbar #wp-admin-bar-awt-status > .ab-item,
#wpadminbar #wp-admin-bar-awt-status > .ab-empty-item {
	display: flex;
	align-items: center;
	gap: 7px;
}
#wpadminbar .awt-toolbar__mark {
	font-family: "IBM Plex Mono", ui-monospace, SFMono-Regular, Menlo, monospace;
	font-weight: 700;
	letter-spacing: 0.02em;
}
#wpadminbar .awt-toolbar__dot {
	display: inline-block;
	inline-size: 10px;
	block-size: 10px;
	flex: 0 0 auto;
	border-radius: 50%;
	/* Keeps the dot defined on the light, ocean, blue and sunrise bars, where
	   no bright fill can reach 3:1. Invisible on the dark schemes. */
	box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.55);
}
#wpadminbar .awt-toolbar__dot--current { background-color: #6fcf5f; }
#wpadminbar .awt-toolbar__dot--update  { background-color: #ff6b63; }
#wpadminbar .awt-toolbar__dot--unknown { background-color: #a7aaad; }
#wpadminbar .awt-toolbar__sr {
	position: absolute;
	inline-size: 1px;
	block-size: 1px;
	padding: 0;
	margin: -1px;
	overflow: hidden;
	clip-path: inset(50%);
	white-space: nowrap;
	border: 0;
}
CSS;
}

/**
 * Print it wherever the toolbar is.
 */
function enqueue_styles(): void {
	if ( ! is_admin_bar_showing() || ! current_user_can( CAPABILITY ) ) {
		return;
	}
	wp_add_inline_style( 'admin-bar', styles() );
}
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_styles', 20 );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_styles', 20 );
