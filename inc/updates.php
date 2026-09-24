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
 * **Every site is served, free included** *(2026-09-21; this used to be the
 * AWT Premium boundary)*. The response carries the package, so one-click and
 * background updates work, and a site on the default setting keeps itself up
 * to date without being asked. The single exception is a release the
 * changelog marks `[Breaking]`: it never installs itself, and it holds every
 * later release behind it until somebody presses Update now — one click,
 * both halves, no download and no upload.
 *
 * **A release has to sit in the field before it may install itself.** Three
 * days, worked out in the manifest rather than here, so no site's clock can
 * bring one forward and a release going badly is stopped in one place. AWT
 * collects nothing, so nobody can watch a rollout; the soak is what stands in
 * for that.
 *
 * **Which release a site may reach depends on where it is**, because a wall
 * sits between some sites and the newest version and not others. So the
 * manifest lists releases and `auto_install_target()` below walks forward
 * from the installed version. The walk is four lines on purpose: it runs
 * unattended, on other people's sites.
 *
 * **One version for the pair.** The manifest names a single version for the
 * theme and the plugin together, and the plugin half reads the same file, so
 * the two can never point a site at different versions. The plugin carries
 * the same walk, because it has to work when AWT is not the active theme.
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

/**
 * Where a package may come from.
 *
 * Everything a site installs by itself is named by one JSON file on one
 * server, and nothing signs it. That is the whole trust in this channel, so
 * the one thing worth pinning down is the destination: a package URL that is
 * not an AWT release on GitHub is refused, whatever the manifest says. It
 * does not make a tampered manifest harmless — it does stop the simplest
 * version of it, which is pointing a site somewhere else entirely.
 *
 * Checked before the `awt_theme_update_package` filter, not after: a filter
 * is code already running on the site and has nothing to gain by lying.
 */
const PACKAGE_HOST = 'github.com';

/** And under whose releases. GitHub hands out paths of this shape. */
const PACKAGE_PATH = '/useawt/';

add_filter( 'site_transient_update_themes', __NAMESPACE__ . '\\offer_update' );
add_filter( 'auto_update_theme', __NAMESPACE__ . '\\should_auto_update', 10, 2 );
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
	return (bool) apply_filters( 'awt_update_check_enabled', mode() !== 'off' );
}

/**
 * How this site handles a new AWT: 'auto', 'notify' or 'off'.
 *
 * @return string One of: auto | notify | off.
 */
function mode(): string {
	$mode = (string) Settings\get( 'updates.mode' );
	return in_array( $mode, array( 'auto', 'notify', 'off' ), true ) ? $mode : 'auto';
}

/**
 * Whether this site may install an update without being asked.
 *
 * Three things can say no, and each is a different kind of no:
 *
 * - The owner chose to be told rather than served.
 * - The site is not production. A staging copy that updates itself while
 *   somebody is working on it is how people learn to turn the feature off.
 * - The site is deployed by something else. `useawt.com` and `clsdir.com`
 *   receive AWT by rsync from a checkout, so installing a release there
 *   writes the same directory the deploy writes, from a different set of
 *   files, and whichever ran last wins with nothing to say so. WordPress
 *   will not catch this on its own: it refuses to auto-update a directory
 *   under version control, and a deploy of that kind copies no `.git`.
 *
 * @return bool True when AWT may install its own updates here.
 */
function automatic_allowed(): bool {
	if ( deployed_from_source() ) {
		return false;
	}
	if ( mode() !== 'auto' ) {
		return false;
	}
	if ( environment() !== 'production' ) {
		return false;
	}
	return package_folder_matches();
}

/**
 * Whether an update would land in the folder this theme actually lives in.
 *
 * A release zip extracts to a folder of its own name — `awt/`. A site is free
 * to have the theme somewhere else: unpacking a renamed zip, or a clone,
 * leaves it in `awt-theme/` or anything at all, and WordPress is happy with
 * that until the day it updates. Then the package unpacks *beside* the theme
 * rather than over it, and the site carries on running the copy it already
 * had while the update notice never goes away.
 *
 * **The folder name is not cosmetic.** Every template part, template and set
 * of global styles the owner has edited is filed against it — that is how
 * WordPress knows whose header it is. A theme arriving under a different
 * name has none of them.
 *
 * So when the names disagree AWT withholds the package and asks for a person.
 * Installing by hand goes through WordPress's "Replace current with
 * uploaded", which puts the files where the theme already is.
 *
 * The expected name comes from the manifest, not a constant here: it is the
 * publisher that knows what its own zip unpacks to.
 *
 * @param array|null $data Decoded manifest, or null to read the cached one.
 * @return bool True when an update would replace this theme rather than sit
 *              beside it.
 */
function package_folder_matches( ?array $data = null ): bool {
	$expected = expected_folder( $data );
	return $expected === '' || $expected === slug();
}

/**
 * The folder the published package unpacks to, as the manifest states it.
 *
 * Empty when the manifest does not say, which is read as "no reason to
 * doubt it" rather than as a mismatch.
 *
 * @param array|null $data Decoded manifest, or null to read the cached one.
 * @return string A folder name, or ''.
 */
function expected_folder( ?array $data = null ): string {
	if ( $data === null ) {
		$data = manifest() ?? cached();
	}
	return is_array( $data ) ? (string) ( $data['theme']['slug'] ?? '' ) : '';
}

/**
 * The last manifest this site read, without going and reading one.
 *
 * `manifest()` answers null on the front end by design — a visitor's page
 * load is no place for a network request. But the toolbar renders on the
 * front end and has to say the same thing there as it does in wp-admin, and
 * for a while it did not: the folder check quietly passed on every front-end
 * page, so a site that cannot update itself was told it was updating
 * automatically. Measured, not guessed.
 *
 * @return array|null The cached manifest, or null before the first check.
 */
function cached(): ?array {
	$cached = get_site_transient( CACHE_KEY );
	return is_array( $cached ) ? $cached : null;
}

/**
 * Whether something other than WordPress puts AWT's files on this site.
 *
 * `AWT_DEPLOYED_FROM_SOURCE` in `wp-config.php` is the documented way to say
 * so, and a constant rather than a setting on purpose: it belongs to whoever
 * set the deployment up, not to whoever is editing pages. The filter is the
 * same answer for a host or an mu-plugin that knows without being told.
 *
 * @return bool True when AWT must not write over its own directory here.
 */
function deployed_from_source(): bool {
	$deployed = defined( 'AWT_DEPLOYED_FROM_SOURCE' ) && AWT_DEPLOYED_FROM_SOURCE;
	return (bool) apply_filters( 'awt_deployed_from_source', $deployed );
}

/**
 * What kind of site this is: production, staging, development or local.
 *
 * Wrapped because WordPress offers no filter of its own and caches its answer
 * for the request, which leaves a site whose host reports the wrong thing —
 * and a test — with no way to say otherwise.
 *
 * @return string An environment type.
 */
function environment(): string {
	$type = function_exists( 'wp_get_environment_type' ) ? wp_get_environment_type() : 'production';
	return (string) apply_filters( 'awt_update_environment', $type );
}

/**
 * The newest release this site may install by itself right now, or null.
 *
 * Walks forward from the installed version through the manifest's release
 * list, and stops at the first of two things: a release marked breaking, or
 * one that has not soaked. Neither can be stepped over — a version is
 * cumulative, so reaching a later release means installing everything in
 * between, including whatever the wall was put there for.
 *
 * @param array  $data      Decoded manifest.
 * @param string $installed The version running here.
 * @return array|null The release entry to install, or null for none.
 */
function auto_install_target( array $data, string $installed ): ?array {
	$releases = $data['releases'] ?? null;
	if ( ! is_array( $releases ) || ! $releases ) {
		return null;
	}

	/*
	 * The list is capped, so a site can be behind the whole of it. Then the
	 * walk starts at the oldest entry and cannot see whatever was tagged
	 * breaking in the range that fell off the end — it would step over walls
	 * it never knew about. A site this far back installs by hand.
	 */
	$oldest = '';
	foreach ( $releases as $release ) {
		$version = (string) ( $release['version'] ?? '' );
		if ( $version !== '' && ( $oldest === '' || version_compare( $version, $oldest, '<' ) ) ) {
			$oldest = $version;
		}
	}
	if ( $oldest === '' || version_compare( $installed, $oldest, '<' ) ) {
		return null;
	}

	// The manifest is newest first; walk it oldest first from just above here.
	$ordered = array_reverse( $releases );
	$target  = null;
	foreach ( $ordered as $release ) {
		$version = (string) ( $release['version'] ?? '' );
		if ( $version === '' || version_compare( $version, $installed, '<=' ) ) {
			continue;
		}
		if ( ! empty( $release['breaking'] ) || empty( $release['autoInstall'] ) ) {
			break;
		}
		$target = $release;
	}
	return $target;
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
 * The package URL, or '' when it is not one of ours.
 *
 * See PACKAGE_HOST for what this is and is not worth.
 *
 * @param string $url Whatever the manifest named.
 * @return string The same URL, or '' to install nothing.
 */
function trusted_package( string $url ): string {
	if ( $url === '' ) {
		return '';
	}
	$parts = wp_parse_url( $url );
	if ( ! is_array( $parts ) ) {
		return '';
	}
	if ( ( $parts['scheme'] ?? '' ) !== 'https' ) {
		return '';
	}
	if ( strtolower( (string) ( $parts['host'] ?? '' ) ) !== PACKAGE_HOST ) {
		return '';
	}
	if ( strpos( (string) ( $parts['path'] ?? '' ), PACKAGE_PATH ) !== 0 ) {
		return '';
	}
	return $url;
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

	$offer   = $latest;
	$package = trusted_package( (string) ( $data['theme']['package'] ?? '' ) );

	// An update that would unpack beside this theme instead of over it is
	// worse than none: it leaves a second copy and changes nothing.
	if ( ! package_folder_matches( $data ) ) {
		$package = '';
	}

	if ( wp_doing_cron() ) {
		/*
		 * The unattended path, and the only place the breaking hold can be
		 * enforced. Core installs whatever this entry names, without asking
		 * anybody, so during cron it must name only what this site is
		 * allowed to install by itself — or nothing at all. Offering the
		 * newest version here and relying on the `auto_update_theme` filter
		 * to say no would work until one filter somewhere returned true.
		 *
		 * Every other context (the Themes screen, Dashboard -> Updates, the
		 * toolbar) still sees the newest version below, because this filter
		 * runs on each read rather than on the stored value.
		 */
		$target  = automatic_allowed() ? auto_install_target( $data, $installed ) : null;
		$package = null === $target ? '' : trusted_package( (string) ( $target['theme']['package'] ?? '' ) );
		if ( null === $target || '' === $package ) {
			$transient->no_update[ $slug ] = current_entry( $slug, $installed, $data );
			unset( $transient->response[ $slug ] );
			return $transient;
		}
		$offer = (string) $target['version'];
	}

	$entry = array(
		'theme'        => $slug,
		'new_version'  => $offer,
		'url'          => (string) ( $data['theme']['releaseUrl'] ?? '' ),

		/*
		 * Served to everybody since 2026-09-21. It used to be empty on the
		 * free tier, which made WordPress print "Automatic update is
		 * unavailable for this theme"; the filter stays as the seam an AWT
		 * Premium build can still reach, but it no longer decides whether a
		 * free site can update itself.
		 */
		'package'      => (string) apply_filters( 'awt_theme_update_package', $package, $data ),
		'requires'     => (string) ( $data['requiresWp'] ?? '' ),
		'requires_php' => (string) ( $data['requiresPhp'] ?? '' ),
	);

	if ( version_compare( $installed, $offer, '<' ) ) {
		$transient->response[ $slug ] = $entry;
		unset( $transient->no_update[ $slug ] );
	} else {
		$transient->no_update[ $slug ] = current_entry( $slug, $installed, $data );
		unset( $transient->response[ $slug ] );
	}

	return $transient;
}

/**
 * The "checked, and up to date" entry.
 *
 * Core's auto-update UI reads `no_update` to know a theme is looked after at
 * all. Without it the Themes screen has nothing to say about AWT.
 *
 * @param string $slug      Theme directory.
 * @param string $installed Version running here.
 * @param array  $data      Decoded manifest.
 * @return array Entry for the no_update list.
 */
function current_entry( string $slug, string $installed, array $data ): array {
	return array(
		'theme'        => $slug,
		'new_version'  => $installed,
		'url'          => (string) ( $data['theme']['releaseUrl'] ?? '' ),
		'package'      => '',
		'requires'     => (string) ( $data['requiresWp'] ?? '' ),
		'requires_php' => (string) ( $data['requiresPhp'] ?? '' ),
	);
}

/**
 * Answer WordPress's "should this update itself?" question for AWT.
 *
 * Always a boolean rather than null, which takes the per-theme toggle off the
 * Themes screen and replaces it with plain text. That is deliberate: AWT
 * Settings is then the only place the answer can be changed, instead of two
 * controls that can disagree about the same site.
 *
 * @param bool|null $update Core's answer so far.
 * @param mixed     $item   The update offer.
 * @return bool|null Ours for AWT, core's for everything else.
 */
function should_auto_update( $update, $item ) {
	$theme = is_object( $item ) ? ( $item->theme ?? '' ) : ( is_array( $item ) ? ( $item['theme'] ?? '' ) : '' );
	if ( $theme !== slug() ) {
		return $update;
	}
	return automatic_allowed();
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
		return '<p>' . esc_html__( 'This copy of AWT has no release notes.', 'awt' ) . '</p>';
	}
	$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme file.
	if ( ! is_array( $data ) || empty( $data['releases'] ) ) {
		return '<p>' . esc_html__( 'This copy of AWT has no release notes.', 'awt' ) . '</p>';
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
	return __( 'Update the AWT theme and AWT Blocks together. They work as a pair.', 'awt' );
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
 * **Nearly unreachable since 2026-09-21**, because every site now gets a
 * package. What is left is the case where the manifest names a version with
 * no zip attached to its release — which the publisher refuses to do, so it
 * would take a half-published release to get here. The message has to be
 * true in that case rather than describing the old free tier.
 *
 * @param mixed  $reply      False to carry on downloading.
 * @param string $package    The package URL, empty when there is none to get.
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
			__( 'This version can\'t be downloaded automatically. Download it from the AWT website, then go to Appearance → Themes → Add New Theme → Upload Theme and choose "Replace current with uploaded". Your settings and content are kept. %s', 'awt' ),
			'https://useawt.com/faq/#updating'
		)
	);
}
