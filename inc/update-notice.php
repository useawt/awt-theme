<?php
/**
 * One bar at the top of the admin that says where this site stands on AWT.
 *
 * AWT keeps itself up to date, which means the site's code can change without
 * anybody asking for it. The least this owes a site owner is a plain sentence
 * saying so, somewhere they will see it. That is what this is.
 *
 * **It reports; it does not warn in advance.** A site is told after an update
 * installed itself, not before. A heads-up only reaches whoever happens to log
 * in during the window, which on a quiet site is nobody, so it would be two
 * notifications where one of them half-fires.
 *
 * **Quiet when nothing needs doing.** "Everything is fine" appears on the
 * dashboard and on AWT's own screens and nowhere else. A permanent bar on
 * every admin page saying nothing is wrong is how people learn to stop reading
 * notices, which is a bad habit to teach on an accessibility product.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\UpdateNotice;

use AWT\Theme\Updates;
use AWT\Theme\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Who sees any of this: the people who could act on it. */
const CAPABILITY = 'update_themes';

/** What the last unattended run did, so the site can be told afterwards. */
const LAST_RUN = 'awt_update_last_automatic';

/** When the version now waiting was first offered, for the clock below. */
const WAITING_SINCE = 'awt_update_waiting_since';

/** How long "AWT updated itself" stays up. */
const REPORT_FOR = 5 * DAY_IN_SECONDS;

/** How long a waiting update stays quiet about how long it has waited. */
const PATIENCE = 14 * DAY_IN_SECONDS;

add_action( 'automatic_updates_complete', __NAMESPACE__ . '\\record_automatic_run' );
add_filter( 'auto_plugin_theme_update_email', __NAMESPACE__ . '\\rewrite_failure_email', 10, 4 );
add_action( 'admin_notices', __NAMESPACE__ . '\\render' );

/**
 * Remember what the unattended updater just did to AWT.
 *
 * The only record there is. WordPress does not keep one anywhere a site owner
 * could read, and without this "AWT updated itself" has nothing to stand on.
 *
 * @param array $results Per-type results, as core assembles them.
 */
function record_automatic_run( $results ): void {
	if ( ! is_array( $results ) ) {
		return;
	}

	$slug = Updates\slug();
	foreach ( array( 'theme', 'plugin' ) as $type ) {
		foreach ( (array) ( $results[ $type ] ?? array() ) as $result ) {
			$item = $result->item ?? null;
			$name = is_object( $item ) ? ( $item->theme ?? $item->slug ?? '' ) : '';
			if ( $name !== $slug && $name !== 'awt-blocks' ) {
				continue;
			}
			update_option(
				LAST_RUN,
				array(
					'at'      => time(),
					'version' => (string) ( $item->new_version ?? '' ),
					'ok'      => ! is_wp_error( $result->result ?? null ) && ! empty( $result->result ),
				),
				false
			);
			return;
		}
	}
}

/**
 * Replace the email WordPress sends when AWT fails to update itself.
 *
 * Core's version says: *"The following plugins failed to update. If there was
 * a fatal error in the update, the previously installed version has been
 * restored."* For AWT that is almost always untrue — there was no fatal error
 * and nothing was restored — and it arrives unprompted in a site owner's
 * inbox saying their site may be broken. Meanwhile AWT's own explanation goes
 * into the background upgrade log, which nobody reads.
 *
 * Only the failure email is touched. Core's success email is fine as it is,
 * and rewriting it would be effort spent on a message that already works.
 *
 * When something other than AWT failed in the same run, core's text is left
 * alone and ours is added to it: the sentence about restoring is accurate
 * enough for an ordinary plugin, and it is not this code's business to
 * rewrite what core says about somebody else's.
 *
 * @param array  $email  Subject, body, headers and recipient.
 * @param string $type   'success', 'fail', 'mixed' or 'critical'.
 * @param array  $ok     Items that updated.
 * @param array  $failed Items that did not.
 * @return array The email to send.
 */
function rewrite_failure_email( $email, $type, $ok, $failed ) {
	if ( ! is_array( $email ) || ! in_array( $type, array( 'fail', 'mixed', 'critical' ), true ) ) {
		return $email;
	}

	$failed = (array) $failed;
	$ours   = array();
	foreach ( $failed as $item ) {
		$name = (string) ( $item->item->theme ?? $item->item->slug ?? '' );
		if ( $name === Updates\slug() || $name === 'awt-blocks' ) {
			$ours[] = (string) ( $item->item->new_version ?? '' );
		}
	}
	if ( ! $ours ) {
		return $email;
	}

	$version   = (string) reset( $ours );
	$installed = \AWT\Theme\AWT_THEME_VERSION;
	$updates   = admin_url( 'update-core.php' );

	$ours_said = sprintf(
		/* translators: 1: version AWT tried to install. 2: version still running. */
		__( 'AWT tried to install version %1$s and could not. Your site is still on %2$s and is working normally — nothing was changed and nothing was lost.', 'awt' ),
		$version,
		$installed
	) . "\n\n" . sprintf(
		/* translators: %s: URL of the Updates screen. */
		__( 'You can install it yourself here: %s', 'awt' ),
		$updates
	) . "\n\n" . __( 'If it keeps failing, your host may not allow WordPress to install files. Your hosting control panel is the place to check.', 'awt' );

	// Something else failed too, so core still has accurate things to say.
	if ( count( $failed ) > count( $ours ) ) {
		$email['body'] = (string) ( $email['body'] ?? '' ) . "\n\n" . $ours_said;
		return $email;
	}

	$email['subject'] = sprintf(
		/* translators: %s: site name. */
		__( '[%s] AWT could not update itself', 'awt' ),
		wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES )
	);
	$email['body'] = $ours_said;

	return $email;
}

/**
 * Whether this site could install an update even if it wanted to.
 *
 * Some hosts switch off file changes altogether. On those, every setting in
 * AWT about installing updates is a claim the site cannot honour, and saying
 * so plainly is better than a switch that silently does nothing.
 *
 * @return bool True when WordPress is allowed to write files here.
 */
function host_allows_updates(): bool {
	if ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
		return false;
	}
	if ( defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED ) {
		return false;
	}
	return (bool) apply_filters( 'automatic_updater_disabled', false ) === false;
}

/**
 * The installed version of each half, and whether they agree.
 *
 * @return array{theme:string,plugin:string,match:bool}
 */
function versions(): array {
	$theme  = \AWT\Theme\AWT_THEME_VERSION;
	$plugin = function_exists( '\\AWT\\Theme\\AdminPage\\blocks_version' ) ? (string) \AWT\Theme\AdminPage\blocks_version() : '';
	return array(
		'theme'  => $theme,
		'plugin' => $plugin,
		'match'  => $plugin === '' || $plugin === $theme,
	);
}

/**
 * What this site should be told, as an id and whatever that id needs.
 *
 * Ordered by what a person would want to hear first: something broke, then
 * something needs you, then something happened, then nothing is wrong.
 *
 * @return array{id:string}|array Empty when there is nothing to say.
 */
function state(): array {
	if ( ! current_user_can( CAPABILITY ) ) {
		return array();
	}

	$versions = versions();
	$last     = (array) get_option( LAST_RUN, array() );
	$mode     = Updates\mode();

	// Something went wrong installing, and the site is still running.
	if ( ! empty( $last ) && empty( $last['ok'] ) && ( time() - (int) $last['at'] ) < REPORT_FOR ) {
		return array(
			'id'        => 'failed',
			'version'   => (string) $last['version'],
			'installed' => $versions['theme'],
		);
	}

	// The pair came apart. Visible and fixable, so it outranks the rest.
	if ( ! $versions['match'] ) {
		return array( 'id' => 'mismatch' ) + $versions;
	}

	if ( $mode === 'off' ) {
		return array( 'id' => 'checks-off' );
	}

	$data   = Updates\manifest();
	$latest = is_array( $data ) ? (string) ( $data['version'] ?? '' ) : '';
	$newer  = $latest !== '' && version_compare( $versions['theme'], $latest, '<' );

	// Before anything about installing: does this site install at all? A site
	// whose files arrive by a deploy is not going to be told how to upload a
	// zip, and it is not misconfigured for refusing to.
	if ( Updates\deployed_from_source() ) {
		return array(
			'id'      => 'deployed',
			'version' => $versions['theme'],
			'newest'  => $newer ? $latest : '',
		);
	}

	// Not only when the site installs its own updates. A site set to be told
	// about them is told, and then sent to a screen with nothing on it —
	// the package is withheld here whatever the setting says.
	if ( ! Updates\package_folder_matches() ) {
		return array(
			'id'       => 'wrong-folder',
			'folder'   => Updates\slug(),
			'expected' => Updates\expected_folder(),
			'version'  => $newer ? $latest : '',
		);
	}

	if ( $mode === 'auto' && ! host_allows_updates() ) {
		return array( 'id' => 'host-blocked' );
	}

	if ( $newer ) {
		// Will it arrive on its own? Then say nothing now and report after.
		$target   = Updates\automatic_allowed() ? Updates\auto_install_target( $data, $versions['theme'] ) : null;
		$arriving = is_array( $target ) && (string) $target['version'] === $latest;
		if ( ! $arriving ) {
			return array(
				'id'       => 'needs-you',
				'version'  => $latest,
				'breaking' => is_breaking( $data, $versions['theme'], $latest ),
				'waiting'  => waiting_days( $latest ),
			);
		}
	} else {
		forget_waiting();
	}

	// It installed itself, and the owner did not ask for that.
	if ( ! empty( $last ) && ! empty( $last['ok'] ) && ( time() - (int) $last['at'] ) < REPORT_FOR ) {
		return array(
			'id'      => 'just-updated',
			'version' => $versions['theme'],
		);
	}

	return array(
		'id'      => 'current',
		'version' => $versions['theme'],
	);
}

/**
 * Whether anything between here and there is marked breaking.
 *
 * @param array  $data      Decoded manifest.
 * @param string $installed Version running here.
 * @param string $latest    Newest published version.
 * @return bool True when a wall stands in the way.
 */
function is_breaking( array $data, string $installed, string $latest ): bool {
	foreach ( (array) ( $data['releases'] ?? array() ) as $release ) {
		if ( empty( $release['breaking'] ) ) {
			continue;
		}
		$version = (string) ( $release['version'] ?? '' );
		if ( $version === '' ) {
			continue;
		}
		if ( version_compare( $version, $installed, '>' ) && version_compare( $version, $latest, '<=' ) ) {
			return true;
		}
	}
	return false;
}

/**
 * How many days this version has been waiting, or 0 while that is not worth
 * saying.
 *
 * An honest clock, not a nag: the bar reads the same for a fortnight and only
 * then starts counting, because a site owner who checks in monthly should not
 * be shouted at on day two.
 *
 * @param string $version The version now waiting.
 * @return int Whole days, or 0.
 */
function waiting_days( string $version ): int {
	$since = (array) get_option( WAITING_SINCE, array() );
	if ( ( $since['version'] ?? '' ) !== $version ) {
		$since = array(
			'version' => $version,
			'at'      => time(),
		);
		update_option( WAITING_SINCE, $since, false );
	}
	$waited = time() - (int) $since['at'];
	return $waited >= PATIENCE ? (int) floor( $waited / DAY_IN_SECONDS ) : 0;
}

/** Nothing is waiting any more. */
function forget_waiting(): void {
	if ( get_option( WAITING_SINCE, false ) !== false ) {
		delete_option( WAITING_SINCE );
	}
}

/**
 * Whether this screen is one the quiet states may appear on.
 *
 * @return bool True on the dashboard and on AWT's own screens.
 */
function on_a_home_screen(): bool {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen ) {
		return false;
	}
	return $screen->id === 'dashboard' || strpos( (string) $screen->id, \AWT\Theme\AdminPage\MENU_SLUG ) !== false;
}

/** Print the bar. */
function render(): void {
	$state = state();
	if ( ! $state ) {
		return;
	}

	$quiet = in_array( $state['id'], array( 'current', 'checks-off', 'deployed' ), true );
	if ( $quiet && ! on_a_home_screen() ) {
		return;
	}

	$notice = message( $state );
	if ( ! $notice ) {
		return;
	}

	printf(
		'<div class="notice notice-%s"><p>%s</p></div>',
		esc_attr( $notice['level'] ),
		wp_kses_post( $notice['text'] )
	);
}

/**
 * The sentence for a state, and how loudly to say it.
 *
 * @param array $state From state().
 * @return array{level:string,text:string}|null
 */
function message( array $state ): ?array {
	$settings = admin_url( 'themes.php?page=' . \AWT\Theme\AdminPage\MENU_SLUG . '&tab=tools' );
	$updates  = admin_url( 'update-core.php' );

	switch ( $state['id'] ) {
		case 'failed':
			return array(
				'level' => 'warning',
				'text'  => sprintf(
					/* translators: 1: version that failed, e.g. 2026.10.0. 2: version still running. 3: link to the Updates screen. */
					esc_html__( 'AWT could not install %1$s automatically. Your site is still on %2$s and working normally. %3$s', 'awt' ),
					'<strong>' . esc_html( $state['version'] ) . '</strong>',
					esc_html( $state['installed'] ),
					'<a href="' . esc_url( $updates ) . '">' . esc_html__( 'Install it now', 'awt' ) . '</a>'
				),
			);

		case 'mismatch':
			return array(
				'level' => 'warning',
				'text'  => sprintf(
					/* translators: 1: theme version. 2: plugin version. 3: link to the Updates screen. */
					esc_html__( 'The AWT theme and AWT Blocks are on different versions. They are built as a pair and should match: the theme is on %1$s and the plugin is on %2$s. %3$s', 'awt' ),
					'<strong>' . esc_html( $state['theme'] ) . '</strong>',
					'<strong>' . esc_html( $state['plugin'] ) . '</strong>',
					'<a href="' . esc_url( $updates ) . '">' . esc_html__( 'Update whichever is behind', 'awt' ) . '</a>'
				),
			);

		case 'checks-off':
			return array(
				'level' => 'info',
				'text'  => sprintf(
					/* translators: %s: link to the settings screen. */
					esc_html__( 'AWT is not checking for updates. You will not be told when a new version is out, including security and accessibility fixes. %s', 'awt' ),
					'<a href="' . esc_url( $settings ) . '">' . esc_html__( 'Turn checks on', 'awt' ) . '</a>'
				),
			);

		case 'deployed':
			if ( $state['newest'] !== '' ) {
				return array(
					'level' => 'info',
					'text'  => sprintf(
						/* translators: 1: the version this site runs. 2: the newest published version. */
						esc_html__( 'This site is on AWT %1$s and is updated by its own deployment, not by WordPress. %2$s has been released.', 'awt' ),
						'<strong>' . esc_html( (string) $state['version'] ) . '</strong>',
						'<strong>' . esc_html( (string) $state['newest'] ) . '</strong>'
					),
				);
			}
			return array(
				'level' => 'info',
				'text'  => sprintf(
					/* translators: %s: the version this site runs. */
					esc_html__( 'This site is on AWT %s and is updated by its own deployment, not by WordPress.', 'awt' ),
					'<strong>' . esc_html( (string) $state['version'] ) . '</strong>'
				),
			);

		case 'wrong-folder':
			$text = sprintf(
				/* translators: 1: the folder the theme is installed in, e.g. awt-theme. 2: the folder AWT updates install into, always "awt". */
				esc_html__( 'AWT is installed in a folder called %1$s, but its updates install into %2$s, so AWT cannot install them for you. Renaming the folder would lose any header, footer or template you have edited, so do not.', 'awt' ),
				'<code>' . esc_html( (string) $state['folder'] ) . '</code>',
				'<code>' . esc_html( (string) ( $state['expected'] ?? '' ) ) . '</code>'
			);
			$text .= ' ';
			if ( ! empty( $state['version'] ) ) {
				$text .= sprintf(
					/* translators: %s: the new version number. */
					esc_html__( 'AWT %s is out — install it yourself, choosing "Replace current with uploaded", and it will go to the right place.', 'awt' ),
					'<strong>' . esc_html( (string) $state['version'] ) . '</strong>'
				);
			} else {
				$text .= esc_html__( 'When a new version is out, install it yourself, choosing "Replace current with uploaded", and it will go to the right place.', 'awt' );
			}
			return array(
				'level' => 'warning',
				'text'  => $text,
			);

		case 'host-blocked':
			return array(
				'level' => 'info',
				'text'  => esc_html__( 'Your host does not let WordPress install updates. AWT will tell you when a new version is out, but you or your host must install it. You may be able to turn on automatic updates in your hosting control panel.', 'awt' ),
			);

		case 'needs-you':
			$text = sprintf(
				/* translators: %s: the new version number. */
				esc_html__( 'AWT %s is ready to install.', 'awt' ),
				'<strong>' . esc_html( $state['version'] ) . '</strong>'
			);
			if ( ! empty( $state['breaking'] ) ) {
				$text .= ' ' . esc_html__( 'It comes with changes that could affect your site, so AWT did not automatically update itself.', 'awt' );
			}
			if ( ! empty( $state['waiting'] ) ) {
				$text .= ' ' . sprintf(
					/* translators: %d: whole days. */
					esc_html( _n( 'It has been waiting %d day.', 'It has been waiting %d days.', (int) $state['waiting'], 'awt' ) ),
					(int) $state['waiting']
				);
				if ( ! empty( $state['breaking'] ) ) {
					$text .= ' ' . esc_html__( 'Every version since is waiting behind it, fixes included.', 'awt' );
				}
			}
			$text .= ' <a href="' . esc_url( $updates ) . '">' . esc_html__( 'Update now', 'awt' ) . '</a>';
			return array(
				'level' => 'info',
				'text'  => $text,
			);

		case 'just-updated':
			return array(
				'level' => 'success',
				'text'  => sprintf(
					/* translators: 1: version now installed. 2: link to the What's new panel. */
					esc_html__( 'AWT updated itself to %1$s. %2$s', 'awt' ),
					'<strong>' . esc_html( $state['version'] ) . '</strong>',
					'<a href="' . esc_url( admin_url( 'themes.php?page=' . \AWT\Theme\AdminPage\MENU_SLUG . '&tab=whats-new' ) ) . '">' . esc_html__( 'See what changed', 'awt' ) . '</a>'
				),
			);

		case 'current':
			return array(
				'level' => 'info',
				'text'  => sprintf(
					/* translators: 1: installed version. 2: link to the What's new panel. */
					esc_html__( 'AWT is up to date, on %1$s. %2$s', 'awt' ),
					'<strong>' . esc_html( $state['version'] ) . '</strong>',
					'<a href="' . esc_url( admin_url( 'themes.php?page=' . \AWT\Theme\AdminPage\MENU_SLUG . '&tab=whats-new' ) ) . '">' . esc_html__( "See what's new", 'awt' ) . '</a>'
				),
			);
	}

	return null;
}
