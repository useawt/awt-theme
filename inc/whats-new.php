<?php
/**
 * What's new — release notes inside AWT Settings (Stage 1 spec,
 * "Changelog communication").
 *
 * Reads the changelog JSON bundled with the awt-blocks plugin
 * (build/changelog.json, written by the release script) — no network
 * calls, no telemetry. Shows the last 10 releases; tracks read state
 * per user; carries a menu indicator:
 *
 *   - nothing        — everything read
 *   - neutral dot    — unread releases, none of high severity
 *   - red count      — unread releases with [Security] or [Breaking]
 *
 * High-severity entries stay pinned at the top of the panel until the
 * user dismisses them explicitly; ordinary releases are marked read
 * simply by opening the tab.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\WhatsNew;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const SEEN_META = 'awt_whats_new_seen';
const ACK_META  = 'awt_whats_new_ack';

add_filter( 'awt_admin_settings_tabs', __NAMESPACE__ . '\\register_tab', 20 );
add_action( 'admin_menu', __NAMESPACE__ . '\\decorate_menu_item', 999 );
add_action( 'admin_init', __NAMESPACE__ . '\\handle_ack' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_indicator_style' );

/**
 * The parsed changelog bundled with the awt-blocks plugin, or null when
 * the plugin (or its changelog) isn't available. Cached per request.
 *
 * @return array|null { currentVersion: string, releases: array } or null.
 */
function changelog(): ?array {
	static $cache   = null;
	static $checked = false;
	if ( $checked ) {
		return $cache;
	}
	$checked = true;

	$file = WP_PLUGIN_DIR . '/awt-blocks/build/changelog.json';
	if ( ! is_readable( $file ) ) {
		return null;
	}
	$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin-bundled file.
	if ( ! is_array( $data ) || 1 !== (int) ( $data['schemaVersion'] ?? 0 ) || empty( $data['releases'] ) ) {
		return null;
	}
	$cache = $data;
	return $cache;
}

/**
 * Whether a release carries [Security] or [Breaking] entries.
 *
 * @param array $release One release from the changelog JSON.
 */
function is_high_severity( array $release ): bool {
	foreach ( (array) ( $release['entries'] ?? array() ) as $entry ) {
		if ( in_array( $entry['severity'] ?? '', array( 'Security', 'Breaking' ), true ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Releases the current user has not read yet.
 *
 * @return array[] Unread releases, newest first.
 */
function unread_releases(): array {
	$data = changelog();
	if ( ! $data ) {
		return array();
	}
	$seen = (string) get_user_meta( get_current_user_id(), SEEN_META, true );
	return array_values(
		array_filter(
			$data['releases'],
			static fn( array $r ): bool => '' === $seen || version_compare( (string) $r['version'], $seen, '>' )
		)
	);
}

/**
 * High-severity releases the current user has not dismissed yet.
 *
 * @return array[] Pinned releases, newest first.
 */
function pinned_releases(): array {
	$data = changelog();
	if ( ! $data ) {
		return array();
	}
	$ack = (string) get_user_meta( get_current_user_id(), ACK_META, true );
	return array_values(
		array_filter(
			$data['releases'],
			static fn( array $r ): bool => is_high_severity( $r ) && ( '' === $ack || version_compare( (string) $r['version'], $ack, '>' ) )
		)
	);
}

/**
 * Add the "What's new" tab to AWT Settings (last position).
 *
 * @param array $tabs Registered tabs.
 * @return array Tabs including this one.
 */
function register_tab( array $tabs ): array {
	if ( ! changelog() ) {
		return $tabs; // No plugin changelog — nothing to show.
	}
	$tabs['whats-new'] = array(
		'label'  => __( 'What\'s new', 'awt' ),
		'render' => __NAMESPACE__ . '\\render_tab',
	);
	return $tabs;
}

/**
 * Small, class-scoped style for the menu dot — loads on every admin page
 * because the Settings menu is global chrome. (The red counter reuses WP
 * core's native bubble classes and needs no CSS of ours.)
 */
function enqueue_indicator_style(): void {
	wp_register_style( 'awt-whats-new-indicator', false, array(), \AWT\Theme\AWT_THEME_VERSION );
	wp_enqueue_style( 'awt-whats-new-indicator' );
	wp_add_inline_style(
		'awt-whats-new-indicator',
		'.awt-whats-new-dot{display:inline-block;width:8px;height:8px;margin-left:6px;border-radius:50%;background:currentColor;opacity:.55;vertical-align:middle;}'
	);
}

/**
 * Append the unread indicator to the Settings → AWT menu label.
 */
function decorate_menu_item(): void {
	if ( ! changelog() ) {
		return;
	}
	$unread = unread_releases();
	$pinned = pinned_releases();
	if ( ! $unread && ! $pinned ) {
		return;
	}

	if ( $pinned ) {
		// WP core's update bubble — same look as plugin-update counts.
		$badge = sprintf(
			' <span class="update-plugins count-%1$d"><span class="update-count">%1$d</span></span><span class="screen-reader-text">%2$s</span>',
			count( $pinned ),
			__( 'important release notes need your attention', 'awt' )
		);
	} else {
		$badge = ' <span class="awt-whats-new-dot" aria-hidden="true"></span><span class="screen-reader-text">' . __( 'unread release notes', 'awt' ) . '</span>';
	}

	global $submenu;
	if ( empty( $submenu['options-general.php'] ) ) {
		return;
	}
	foreach ( $submenu['options-general.php'] as $i => $item ) {
		if ( 'awt-settings' === ( $item[2] ?? '' ) ) {
			$submenu['options-general.php'][ $i ][0] .= $badge; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- decorating our own menu label is the documented way to add an indicator.
			break;
		}
	}
}

/**
 * Handle the "Got it" dismissal of pinned high-severity notes.
 */
function handle_ack(): void {
	if ( empty( $_POST['awt_whats_new_ack'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$nonce = sanitize_text_field( wp_unslash( $_POST['_awt_whats_new_nonce'] ?? '' ) );
	if ( ! wp_verify_nonce( $nonce, 'awt_whats_new_ack' ) ) {
		return;
	}
	$data = changelog();
	if ( $data ) {
		update_user_meta( get_current_user_id(), ACK_META, (string) $data['currentVersion'] );
	}
	wp_safe_redirect( admin_url( 'options-general.php?page=awt-settings&tab=whats-new' ) );
	exit;
}

/**
 * Render one release's entries as a definition-style list.
 *
 * @param array $release One release from the changelog JSON.
 */
function render_entries( array $release ): void {
	echo '<ul class="awt-whats-new-entries">';
	foreach ( (array) ( $release['entries'] ?? array() ) as $entry ) {
		$severity = (string) ( $entry['severity'] ?? 'Improvement' );
		printf(
			'<li class="awt-whats-new-entry awt-whats-new-entry--%1$s"><strong class="awt-whats-new-badge">[%2$s]</strong> %3$s</li>',
			esc_attr( strtolower( $severity ) ),
			esc_html( $severity ),
			format_entry_text( trim( ( $entry['summary'] ?? '' ) . ' ' . ( $entry['details'] ?? '' ) ) ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside format_entry_text() before any markup is added.
		);
	}
	echo '</ul>';
}

/**
 * Turn one changelog entry into safe HTML.
 *
 * Entries are authored in CHANGELOG.md, so they carry Markdown emphasis and
 * code spans. Printing them raw showed people literal `**` and backticks in the
 * admin. Escape first, then convert the two markers we actually use — nothing
 * else is interpreted, so no markup can arrive from the text itself.
 *
 * @param string $text Entry text as written in the changelog.
 * @return string Escaped HTML.
 */
function format_entry_text( string $text ): string {
	$out = esc_html( $text );
	$out = (string) preg_replace( '/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $out );
	$out = (string) preg_replace( '/`([^`]+)`/', '<code>$1</code>', $out );
	return $out;
}

/**
 * The What's new tab.
 */
function render_tab(): void {
	$data = changelog();
	if ( ! $data ) {
		echo '<p>' . esc_html__( 'No release notes are available yet.', 'awt' ) . '</p>';
		return;
	}

	$pinned = pinned_releases();
	$unread = unread_releases();

	// Opening the tab marks everything as read for this user (the pinned
	// high-severity notes below keep their own explicit dismissal).
	update_user_meta( get_current_user_id(), SEEN_META, (string) $data['currentVersion'] );

	echo '<h2>' . esc_html__( 'What\'s new in AWT', 'awt' ) . '</h2>';

	if ( $pinned ) {
		echo '<div class="awt-whats-new-pinned" role="region" aria-label="' . esc_attr__( 'Important release notes', 'awt' ) . '">';
		echo '<h3>' . esc_html__( 'Needs your attention', 'awt' ) . '</h3>';
		echo '<p>' . esc_html__( 'These releases contain security fixes or changes that may need action on your site.', 'awt' ) . '</p>';
		foreach ( $pinned as $release ) {
			printf(
				'<h4>%s — %s</h4>',
				esc_html( (string) $release['version'] ),
				esc_html( (string) $release['date'] )
			);
			render_entries( $release );
		}
		echo '<form method="post">';
		wp_nonce_field( 'awt_whats_new_ack', '_awt_whats_new_nonce' );
		echo '<button type="submit" name="awt_whats_new_ack" value="1" class="button button-secondary">' . esc_html__( 'Got it — dismiss these notes', 'awt' ) . '</button>';
		echo '</form>';
		echo '</div>';
	}

	$unread_versions = array_column( $unread, 'version' );

	foreach ( array_slice( (array) $data['releases'], 0, 10 ) as $i => $release ) {
		$is_unread = in_array( $release['version'], $unread_versions, true );
		printf( '<details class="awt-whats-new-release"%s>', 0 === $i ? ' open' : '' );
		printf(
			'<summary><strong>%s</strong> — %s%s</summary>',
			esc_html( (string) $release['version'] ),
			esc_html( (string) $release['date'] ),
			$is_unread ? ' <em class="awt-whats-new-unread">' . esc_html__( '(new)', 'awt' ) . '</em>' : ''
		);
		render_entries( $release );
		echo '</details>';
	}

	echo '<p class="awt-field-help">' . esc_html__( 'Release notes come from the AWT Blocks plugin bundled with each update. Nothing is fetched from the internet.', 'awt' ) . '</p>';
}
