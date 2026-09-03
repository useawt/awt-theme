<?php
/**
 * Telling a site there is a newer AWT.
 *
 * AWT is not in the WordPress.org directory, so nothing tells an installed
 * site when a new version comes out. This reads one small file —
 * `https://useawt.com/updates/v1/awt.json`, written by
 * `marketing/update-manifest.js` — and hands the answer to WordPress's own
 * update machinery. From there the site owner sees the ordinary "new version
 * available" notice on Dashboard → Updates and on the Themes screen.
 *
 * Three decisions are worth knowing about, because each is load-bearing:
 *
 * **The request says nothing about the site.** `wp_remote_get()` normally
 * sends a User-Agent of `WordPress/6.8; https://example.com` — the site's own
 * address, on every check. That is overridden below. There is no query
 * string, no POST body, no cookies and no site data of any kind: the request
 * is a plain GET of a file that is byte-identical for everyone. `useawt.com`
 * says "No telemetry" in its footer, and this is what makes that true rather
 * than nearly true.
 *
 * **The free tier is told, not served.** The response carries a version
 * number and a link; it carries no package URL. WordPress reads a missing
 * package as "automatic update is unavailable" and prints exactly that,
 * next to a link to the release. `awt_theme_update_package` is the one seam
 * an AWT Premium licence fills in, at which point one-click and background
 * auto-updates start working with no other change here.
 *
 * **One version for the pair.** The manifest names a single version for the
 * theme and the plugin together, and the plugin half reads the same file, so
 * the two can never point a site at different versions.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\Updates;

use AWT\Theme\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The published manifest. The schema version is in the path, not just inside
 * the file: an installed site reads this URL for as long as it exists, so the
 * shape at this address can never change. A different shape gets `/v2/`.
 */
const MANIFEST_URL = 'https://useawt.com/updates/v1/awt.json';

/**
 * Cache key for the parsed manifest.
 *
 * Deliberately shared with the AWT Blocks plugin, which caches under the same
 * name: the two halves ask the same question of the same file, so one answer
 * serves both and a site makes one request per half-day rather than two. If
 * the names ever drift the only cost is a second request, so this coupling
 * cannot break a site — but keep them the same.
 */
const CACHE_KEY = 'awt_update_manifest';

/** How long a good answer is kept. */
const CACHE_TTL = 12 * HOUR_IN_SECONDS;

/**
 * How long a failure is kept.
 *
 * Without this, a site whose network cannot reach useawt.com would pay the
 * timeout below on every single admin page load. Short enough that a blip
 * costs one hour of not knowing.
 */
const CACHE_TTL_FAILED = HOUR_IN_SECONDS;

/** Seconds to wait for the manifest before giving up. */
const TIMEOUT = 5;

add_filter( 'site_transient_update_themes', __NAMESPACE__ . '\\offer_update' );
add_filter( 'themes_api', __NAMESPACE__ . '\\details', 10, 3 );
add_action( 'in_theme_update_message-awt', __NAMESPACE__ . '\\pair_note', 10, 2 ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores -- core names this hook after the theme directory.
add_filter( 'upgrader_pre_download', __NAMESPACE__ . '\\explain_manual_update', 10, 4 );
add_filter( 'wp_prepare_themes_for_js', __NAMESPACE__ . '\\fix_themes_screen_notice' );

/**
 * Whether this site checks for updates at all.
 *
 * Off is a real answer: some sites are required to make no outbound requests,
 * and AWT Settings → Tools carries the switch.
 */
function enabled(): bool {
	return (bool) apply_filters( 'awt_update_check_enabled', (bool) Settings\get( 'updates.check' ) );
}

/**
 * Whether the current request is one that should spend time on a network call.
 *
 * Front-end page loads never check. Nobody is there to read the answer, and a
 * theme that adds a possible five seconds to a visitor's page load to find out
 * about itself has its priorities wrong. Admin screens, WP-Cron (which is what
 * runs background updates) and WP-CLI do check.
 */
function should_check(): bool {
	return is_admin() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI );
}

/**
 * The published manifest, or null when it cannot be read.
 *
 * @return array|null Decoded manifest.
 */
function manifest(): ?array {
	if ( ! enabled() || ! should_check() ) {
		return null;
	}

	$cached = get_site_transient( CACHE_KEY );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	if ( $cached === 'failed' ) {
		return null;
	}

	$url = (string) apply_filters( 'awt_update_manifest_url', MANIFEST_URL );

	$response = wp_remote_get(
		$url,
		array(
			'timeout'    => TIMEOUT,
			// Not the default, which is `WordPress/6.8; https://example.com` —
			// this site's own address, in the User-Agent header of every
			// check. Set as the documented argument rather than as a header:
			// both reach the wire, and a header alone leaves the default
			// sitting in the request arguments where a plugin filtering
			// `http_request_args` could put it back. See the file docblock —
			// this line is the "no telemetry" promise.
			'user-agent' => 'AWT',
		)
	);

	$data = parse( $response );
	if ( $data === null ) {
		set_site_transient( CACHE_KEY, 'failed', CACHE_TTL_FAILED );
		return null;
	}

	set_site_transient( CACHE_KEY, $data, CACHE_TTL );
	return $data;
}

/**
 * Turn an HTTP response into a manifest, or null if it is not one.
 *
 * Split out from `manifest()` so it can be tested without a network, and
 * strict on purpose: a half-read or redirected-to-a-login-page response must
 * not be able to announce a version.
 *
 * @param array|\WP_Error $response Result of wp_remote_get().
 * @return array|null Manifest, or null.
 */
function parse( $response ): ?array {
	if ( is_wp_error( $response ) || (int) wp_remote_retrieve_response_code( $response ) !== 200 ) {
		return null;
	}
	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	if ( ! is_array( $data ) ) {
		return null;
	}
	if ( (int) ( $data['schemaVersion'] ?? 0 ) !== 1 ) {
		return null;
	}
	$version = (string) ( $data['version'] ?? '' );
	if ( ! preg_match( '/^\d{4}\.\d{2}\.\d+$/', $version ) ) {
		return null;
	}
	return $data;
}

/**
 * The directory this theme is installed in — which is the key WordPress uses
 * for it everywhere, and is not necessarily "awt" on a site where someone
 * renamed the folder.
 */
function slug(): string {
	return basename( dirname( __DIR__ ) );
}

/**
 * Add AWT to WordPress's list of themes with an update available.
 *
 * @param mixed $transient The update_themes site transient.
 * @return mixed The same, with AWT's answer filled in.
 */
function offer_update( $transient ) {
	if ( ! is_object( $transient ) ) {
		return $transient;
	}

	$data = manifest();
	if ( $data === null ) {
		return $transient;
	}

	$slug      = slug();
	$installed = \AWT\Theme\AWT_THEME_VERSION;
	$latest    = (string) $data['version'];

	$entry = array(
		'theme'        => $slug,
		'new_version'  => $latest,
		'url'          => (string) ( $data['theme']['releaseUrl'] ?? '' ),
		// Empty on the free tier, which is what makes WordPress say
		// "Automatic update is unavailable for this theme" instead of
		// offering a button that could not work. An AWT Premium licence
		// fills this in and one-click starts working.
		'package'      => (string) apply_filters( 'awt_theme_update_package', '', $data ),
		'requires'     => (string) ( $data['requiresWp'] ?? '' ),
		'requires_php' => (string) ( $data['requiresPhp'] ?? '' ),
	);

	if ( version_compare( $installed, $latest, '<' ) ) {
		$transient->response[ $slug ] = $entry;
		unset( $transient->no_update[ $slug ] );
	} else {
		// Core's auto-update UI reads no_update to know a theme is checked
		// and current. Without this the Themes screen has nothing to say
		// about AWT at all.
		$entry['new_version']          = $installed;
		$transient->no_update[ $slug ] = $entry;
		unset( $transient->response[ $slug ] );
	}

	return $transient;
}

/**
 * Answer the "View version details" link locally.
 *
 * That link opens WordPress's own details window, which normally asks
 * WordPress.org about the theme. WordPress.org has never heard of AWT, so
 * without this the window shows an error. The changelog it needs is already
 * on disk — `build/changelog.json`, written at release — so the window opens
 * with no network call at all.
 *
 * @param mixed  $result Whatever an earlier filter returned.
 * @param string $action The themes_api action being performed.
 * @param object $args   Its arguments.
 * @return mixed An info object for AWT, or $result untouched.
 */
function details( $result, $action, $args ) {
	if ( $action !== 'theme_information' || ( $args->slug ?? '' ) !== slug() ) {
		return $result;
	}

	$data = manifest();

	return (object) array(
		'name'          => 'AWT',
		'slug'          => slug(),
		'version'       => (string) ( $data['version'] ?? \AWT\Theme\AWT_THEME_VERSION ),
		'author'        => '<a href="https://useawt.com">AWT</a>',
		'requires'      => (string) ( $data['requiresWp'] ?? '' ),
		'requires_php'  => (string) ( $data['requiresPhp'] ?? '' ),
		'tested'        => (string) ( $data['testedWp'] ?? '' ),
		'homepage'      => 'https://useawt.com',
		'download_link' => '',
		'sections'      => array(
			'changelog' => changelog_html(),
		),
		'external'      => true,
	);
}

/**
 * The bundled changelog as HTML for the details window.
 *
 * Reads the same `build/changelog.json` the What's new panel reads.
 */
function changelog_html(): string {
	$file = get_template_directory() . '/build/changelog.json';
	if ( ! is_readable( $file ) ) {
		return '<p>' . esc_html__( 'No changelog is bundled with this copy of AWT.', 'awt' ) . '</p>';
	}
	$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
	if ( ! is_array( $data ) || empty( $data['releases'] ) ) {
		return '<p>' . esc_html__( 'No changelog is bundled with this copy of AWT.', 'awt' ) . '</p>';
	}

	$html = '';
	foreach ( array_slice( (array) $data['releases'], 0, 10 ) as $release ) {
		$html .= '<h4>' . esc_html( (string) ( $release['version'] ?? '' ) );
		if ( ! empty( $release['date'] ) ) {
			$html .= ' — ' . esc_html( (string) $release['date'] );
		}
		$html .= '</h4><ul>';
		foreach ( (array) ( $release['entries'] ?? array() ) as $entry ) {
			$text  = trim( (string) ( $entry['summary'] ?? '' ) . ' ' . (string) ( $entry['details'] ?? '' ) );
			$html .= '<li>';
			if ( ! empty( $entry['severity'] ) ) {
				$html .= '<strong>[' . esc_html( (string) $entry['severity'] ) . ']</strong> ';
			}
			// The changelog is written in a little Markdown — bold and code
			// spans. The What's new panel already turns that into markup, and
			// escapes first; reuse it rather than printing people literal
			// asterisks.
			$html .= \AWT\Theme\WhatsNew\format_entry_text( $text ) . '</li>';
		}
		$html .= '</ul>';
	}
	return $html;
}

/**
 * The pair reminder.
 *
 * The theme and the plugin are one product in two halves, and a site that
 * updates one and not the other is running a combination nobody tested.
 */
function pair_message(): string {
	return __( 'Update the AWT theme and the AWT Blocks plugin together — they are built as a pair.', 'awt' );
}

/**
 * Append the pair reminder on a Multisite network's Themes screen.
 *
 * A plain link to the release notes goes with it, because the "View version
 * details" link core prints on that screen cannot work here — see
 * `fix_themes_screen_notice()` for why. There is no filter on that screen's
 * markup, so the broken link stays and this adds a working one beside it.
 *
 * @param mixed $theme    The theme core is printing the row for.
 * @param array $response Its entry in the update transient.
 */
function pair_note( $theme = null, $response = array() ): void {
	echo ' <strong>' . esc_html( pair_message() ) . '</strong>';

	$url = (string) ( $response['url'] ?? '' );
	if ( $url !== '' ) {
		printf(
			' <a href="%s" target="_blank" rel="noopener">%s<span class="screen-reader-text"> %s</span></a>',
			esc_url( $url ),
			esc_html__( 'Release notes', 'awt' ),
			esc_html__( '(opens in a new tab)', 'awt' )
		);
	}
}

/**
 * Repair the update notice on an ordinary site's Themes screen.
 *
 * Two things are wrong with what WordPress builds there, and neither has a
 * filter of its own:
 *
 * **The details link opens in a modal that cannot load.** For a theme — unlike
 * a plugin — core takes the `url` we supply and puts it in a lightbox iframe.
 * Ours is the GitHub release page, and GitHub refuses to be framed, so the
 * modal opens empty. The link is rewritten here to open the release notes in a
 * new tab, which is what it was always trying to show.
 *
 * **The pair reminder is missing.** That screen is a JavaScript grid and
 * builds its own notice rather than firing `in_theme_update_message-*`, which
 * only runs on a Multisite network's list.
 *
 * @param array $prepared Theme data on its way to the browser.
 * @return array The same, repaired for AWT.
 */
function fix_themes_screen_notice( $prepared ) {
	if ( ! is_array( $prepared ) ) {
		return $prepared;
	}

	foreach ( $prepared as $key => $theme ) {
		if ( ( $theme['id'] ?? '' ) !== slug() || empty( $theme['update'] ) ) {
			continue;
		}

		$html = (string) $theme['update'];

		// Drop the lightbox: its classes, and the size arguments core appends
		// to the URL for it.
		$html = str_replace( ' class="thickbox open-plugin-details-modal"', '', $html );
		$html = (string) preg_replace(
			'/([?&])TB_iframe=true(&#038;|&amp;|&)width=\d+(&#038;|&amp;|&)height=\d+/',
			'',
			$html
		);
		$html = (string) preg_replace( '/(<a href="[^"]*)[?&]"/', '$1"', $html );

		// Say that it leaves the site. Core has already put an aria-label on
		// the link, and an aria-label replaces the link's text for a screen
		// reader — so a hidden "(opens in a new tab)" span inside the link
		// would never be read. The name is rewritten instead, and it keeps the
		// visible words in it, which is what WCAG 2.5.3 (Label in Name) asks
		// for and core's own name does not do.
		$html    = (string) preg_replace(
			'/<a href="(https?:[^"]*)"/',
			'<a href="$1" target="_blank" rel="noopener"',
			$html,
			1
		);
		$visible = '';
		if ( preg_match( '/<a [^>]*>(.*?)<\/a>/s', $html, $match ) ) {
			$visible = wp_strip_all_tags( $match[1] );
		}
		if ( $visible !== '' ) {
			$html = (string) preg_replace(
				'/ aria-label="[^"]*"/',
				' aria-label="' . esc_attr(
					sprintf(
						/* translators: 1: link text, e.g. "View version 2026.09.0 details". 2: theme name. */
						__( '%1$s for %2$s (opens in a new tab)', 'awt' ),
						$visible,
						'AWT'
					)
				) . '"',
				$html,
				1
			);
		}

		$prepared[ $key ]['update'] = str_replace(
			'</p>',
			' <strong>' . esc_html( pair_message() ) . '</strong></p>',
			$html
		);
	}

	return $prepared;
}

/**
 * Say what to do when someone presses "Update" anyway.
 *
 * Dashboard → Updates puts a checkbox beside every theme with an update,
 * whether or not a package came with it. Ticking AWT's and pressing the button
 * would otherwise end at WordPress's own "Update package not available." —
 * true, and no help at all. This replaces it with the next step.
 *
 * A licensed AWT Premium site never reaches here: it has a package, so
 * WordPress downloads it and this filter passes the request straight through.
 *
 * @param mixed  $reply      False to carry on downloading.
 * @param string $package    The package URL, empty on the free tier.
 * @param object $upgrader   The upgrader running.
 * @param array  $hook_extra What is being updated.
 * @return mixed False, or a WP_Error explaining the manual step.
 */
function explain_manual_update( $reply, $package, $upgrader, $hook_extra = array() ) {
	if ( $package !== '' || ( $hook_extra['theme'] ?? '' ) !== slug() ) {
		return $reply;
	}

	return new \WP_Error(
		'awt_manual_update',
		sprintf(
			/* translators: %s: URL of the update instructions. */
			__( 'AWT does not install its own updates. Download the new version and upload it in Appearance, Themes, Add New Theme, Upload Theme, choosing "Replace current with uploaded". Step-by-step instructions: %s', 'awt' ),
			'https://useawt.com/faq/#updating'
		)
	);
}
