<?php
/**
 * Say so when the AWT Blocks plugin is missing or turned off.
 *
 * The theme and the plugin are one product in two files. Without the plugin
 * the theme still activates and the site still loads, but every AWT block in
 * every page becomes "This block contains unexpected or invalid content" in
 * the editor and disappears from the page — so a site that looks installed is
 * quietly broken, and nothing says why.
 *
 * Two states, two different things to do about them, so they get two
 * different messages: the plugin can be absent, or present and switched off.
 * The second has a button that switches it on; the first has the file to
 * download and the screen to upload it on.
 *
 * Only shown to someone who can act on it, and not dismissible — the site
 * is not working until this is fixed, and a notice that can be waved away is
 * a notice that gets waved away.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\BlocksRequired;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The plugin's own file, as WordPress addresses it. */
const PLUGIN_FILE = 'awt-blocks/awt-blocks.php';

/** Where the plugin is published. */
const RELEASES_URL = 'https://github.com/useawt/awt-blocks/releases';

add_action( 'admin_notices', __NAMESPACE__ . '\\render_notice' );

/**
 * Is the plugin loaded right now?
 *
 * The constant exists only once the plugin's own file has run, which is the
 * question worth asking: a plugin that is present but switched off gives the
 * theme nothing.
 */
function is_active(): bool {
	return defined( 'AWT\\Blocks\\AWT_BLOCKS_VERSION' );
}

/**
 * Is the plugin on disk, whether or not it is switched on?
 *
 * Read from the file rather than from the active-plugins list, so a
 * deactivated copy still reports honestly.
 */
function is_installed(): bool {
	return is_readable( WP_PLUGIN_DIR . '/' . PLUGIN_FILE );
}

/**
 * Print the notice for the state the site is actually in.
 */
function render_notice(): void {
	echo notice_html( is_installed(), is_active() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built below with every dynamic part escaped.
}

/**
 * The notice's markup for a given state, or '' when there is nothing to say.
 *
 * The two facts are arguments rather than read here so the wording for each
 * state can be checked without a plugin to switch on and off — a plugin that
 * is loaded cannot be unloaded mid-request, so a test could only ever see the
 * one state the suite happens to run in.
 *
 * @param bool $installed Whether the plugin's file is on disk.
 * @param bool $active    Whether the plugin is running.
 */
function notice_html( bool $installed, bool $active ): string {
	if ( $active ) {
		return '';
	}

	// Nothing to show someone who cannot fix it. Installing and activating are
	// separate capabilities, so each state asks for its own.
	$can_act = $installed
		? current_user_can( 'activate_plugins' )
		: current_user_can( 'install_plugins' );
	if ( ! $can_act ) {
		return '';
	}

	$out  = '<div class="notice notice-error">';
	$out .= sprintf( '<p><strong>%s</strong></p>', esc_html__( 'AWT is not working yet.', 'awt' ) );

	if ( $installed ) {
		$out .= sprintf(
			'<p>%s</p>',
			esc_html__( 'Turn on the AWT Blocks plugin. Until you do, AWT blocks won\'t show on your pages or in the editor.', 'awt' )
		);
		$out .= sprintf(
			'<p><a class="button button-primary" href="%1$s">%2$s</a></p>',
			esc_url( activate_url() ),
			esc_html__( 'Turn on AWT Blocks', 'awt' )
		);
	} else {
		$out .= sprintf(
			'<p>%s</p>',
			esc_html__( 'Install the AWT Blocks plugin. Until you do, AWT blocks won\'t show on your pages or in the editor.', 'awt' )
		);
		$out .= sprintf(
			'<p><a class="button button-primary" href="%1$s" target="_blank" rel="noopener">%2$s</a> <a class="button" href="%3$s">%4$s</a></p>',
			esc_url( RELEASES_URL ),
			esc_html__( 'Download AWT Blocks (opens in a new tab)', 'awt' ),
			esc_url( self_admin_url( 'plugin-install.php?tab=upload' ) ),
			esc_html__( 'Upload a plugin', 'awt' )
		);
	}

	return $out . '</div>';
}

/**
 * A one-click activation link for the plugin, nonced the way WordPress's own
 * Plugins screen nonces it.
 */
function activate_url(): string {
	return wp_nonce_url(
		self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( PLUGIN_FILE ) ),
		'activate-plugin_' . PLUGIN_FILE
	);
}
