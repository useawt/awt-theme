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
 * A build of the theme can pair with another plugin carrying the same blocks
 * (`awt_blocks_plugin`, below). Then AWT Blocks itself can be the one running,
 * and the site works but without what the paired plugin adds. That is said
 * too, as a warning rather than an error, with the same two things to do.
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

/**
 * Defined by AWT Blocks' code once it runs, whichever plugin it came in. It
 * exists only once the plugin's own file has run, which is the question worth
 * asking: a plugin that is present but switched off gives the theme nothing.
 */
const BLOCKS_LOADED = 'AWT\\Blocks\\AWT_BLOCKS_VERSION';

add_action( 'admin_notices', __NAMESPACE__ . '\\render_notice' );

/**
 * The blocks plugin this theme pairs with: its main file as WordPress
 * addresses it, the name the notice gives it, where to download it, and a
 * constant it defines once its code is running.
 *
 * Filtered through `awt_blocks_plugin`, so a build of the theme that pairs
 * with another plugin carrying the same blocks can name that one. An empty
 * download URL means there is no public download: the notice then offers the
 * upload screen alone. A filter that returns something unusable falls back to
 * AWT Blocks, field by field.
 *
 * @return array{file: string, name: string, download_url: string, constant: string}
 */
function plugin(): array {
	$default = array(
		'file'         => PLUGIN_FILE,
		'name'         => 'AWT Blocks',
		'download_url' => RELEASES_URL,
		'constant'     => BLOCKS_LOADED,
	);
	$plugin  = apply_filters( 'awt_blocks_plugin', $default );
	if ( ! is_array( $plugin ) ) {
		return $default;
	}
	$text = static function ( string $key, bool $may_be_empty ) use ( $plugin, $default ): string {
		$value = $plugin[ $key ] ?? null;
		return is_string( $value ) && ( $may_be_empty || '' !== $value ) ? $value : $default[ $key ];
	};
	return array(
		'file'         => $text( 'file', false ),
		'name'         => $text( 'name', false ),
		'download_url' => $text( 'download_url', true ),
		'constant'     => $text( 'constant', false ),
	);
}

/**
 * Is the plugin this theme pairs with loaded right now?
 */
function is_active(): bool {
	return defined( plugin()['constant'] );
}

/**
 * Is AWT Blocks running in place of the plugin this theme pairs with?
 */
function is_other_running(): bool {
	return ! is_active() && defined( BLOCKS_LOADED );
}

/**
 * Is the plugin on disk, whether or not it is switched on?
 *
 * Read from the file rather than from the active-plugins list, so a
 * deactivated copy still reports honestly.
 */
function is_installed(): bool {
	return is_readable( WP_PLUGIN_DIR . '/' . plugin()['file'] );
}

/**
 * Is the plugin switched on in the Plugins screen, whether or not it runs?
 */
function is_switched_on(): bool {
	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
	return is_plugin_active( plugin()['file'] );
}

/**
 * Print the notice for the state the site is actually in.
 */
function render_notice(): void {
	// Switched on but not running: the plugin stepped aside for another copy
	// of the same blocks, and says why in its own notice. A second notice
	// offering to switch it on would contradict that one.
	if ( ! is_active() && is_switched_on() ) {
		return;
	}
	echo notice_html( is_installed(), is_active(), is_other_running() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built below with every dynamic part escaped.
}

/**
 * The notice's markup for a given state, or '' when there is nothing to say.
 *
 * The facts are arguments rather than read here so the wording for each
 * state can be checked without a plugin to switch on and off — a plugin that
 * is loaded cannot be unloaded mid-request, so a test could only ever see the
 * one state the suite happens to run in.
 *
 * @param bool $installed     Whether the plugin's file is on disk.
 * @param bool $active        Whether the plugin is running.
 * @param bool $other_running Whether AWT Blocks is running in its place.
 */
function notice_html( bool $installed, bool $active, bool $other_running = false ): string {
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

	$plugin = plugin();
	if ( $other_running ) {
		$out  = '<div class="notice notice-warning">';
		$out .= sprintf( '<p><strong>%s</strong></p>', esc_html__( 'Some AWT features are missing.', 'awt' ) );
		if ( $installed ) {
			/* translators: %1$s: plugin name, such as "AWT Blocks". */
			$text = __( 'This theme is made for the %1$s plugin, but AWT Blocks is running instead. Turn on %1$s to get them.', 'awt' );
		} else {
			/* translators: %1$s: plugin name, such as "AWT Blocks". */
			$text = __( 'This theme is made for the %1$s plugin, but AWT Blocks is running instead. Install %1$s to get them.', 'awt' );
		}
	} else {
		$out  = '<div class="notice notice-error">';
		$out .= sprintf( '<p><strong>%s</strong></p>', esc_html__( 'AWT is not working yet.', 'awt' ) );
		if ( $installed ) {
			/* translators: %s: plugin name, such as "AWT Blocks". */
			$text = __( 'Turn on the %s plugin. Until you do, AWT blocks won\'t show on your pages or in the editor.', 'awt' );
		} else {
			/* translators: %s: plugin name, such as "AWT Blocks". */
			$text = __( 'Install the %s plugin. Until you do, AWT blocks won\'t show on your pages or in the editor.', 'awt' );
		}
	}
	$out .= sprintf( '<p>%s</p>', esc_html( sprintf( $text, $plugin['name'] ) ) );

	if ( $installed ) {
		$out .= sprintf(
			'<p><a class="button button-primary" href="%1$s">%2$s</a></p>',
			esc_url( activate_url() ),
			/* translators: %s: plugin name, such as "AWT Blocks". */
			esc_html( sprintf( __( 'Turn on %s', 'awt' ), $plugin['name'] ) )
		);
	} else {
		$upload = esc_url( self_admin_url( 'plugin-install.php?tab=upload' ) );
		if ( '' === $plugin['download_url'] ) {
			$out .= sprintf(
				'<p><a class="button button-primary" href="%1$s">%2$s</a></p>',
				$upload,
				esc_html__( 'Upload a plugin', 'awt' )
			);
		} else {
			$out .= sprintf(
				'<p><a class="button button-primary" href="%1$s" target="_blank" rel="noopener">%2$s</a> <a class="button" href="%3$s">%4$s</a></p>',
				esc_url( $plugin['download_url'] ),
				/* translators: %s: plugin name, such as "AWT Blocks". */
				esc_html( sprintf( __( 'Download %s (opens in a new tab)', 'awt' ), $plugin['name'] ) ),
				$upload,
				esc_html__( 'Upload a plugin', 'awt' )
			);
		}
	}

	return $out . '</div>';
}

/**
 * A one-click activation link for the plugin, nonced the way WordPress's own
 * Plugins screen nonces it.
 */
function activate_url(): string {
	$file = plugin()['file'];
	return wp_nonce_url(
		self_admin_url( 'plugins.php?action=activate&plugin=' . rawurlencode( $file ) ),
		'activate-plugin_' . $file
	);
}
