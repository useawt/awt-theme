<?php
/**
 * The bar that tells a site owner where they stand on AWT.
 *
 * AWT installs its own updates, so a site's code can change without anybody
 * asking. These cover the sentence that says so — and, as much as the wording,
 * the two rules about when it is allowed to speak at all: quiet states only on
 * the dashboard and AWT's own screens, and nothing said *before* an update
 * that is going to arrive on its own.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\Settings;
use AWT\Theme\UpdateNotice;
use AWT\Theme\Updates;

/**
 * The admin bar that reports on AWT's own updates.
 *
 * @covers \AWT\Theme\UpdateNotice
 */
class Test_Update_Notice extends WP_UnitTestCase {

	/**
	 * Somebody who could act on what the bar says.
	 */
	public function set_up(): void {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		add_filter( 'awt_update_environment', static fn () => 'production' );
		// WordPress's own test bootstrap switches the automatic updater off,
		// so without this every test here would land on 'host-blocked' — and
		// that is the right answer for a site in that state, which is why the
		// state exists. Put back at a later priority, not removed, so the
		// test for 'host-blocked' can still win.
		add_filter( 'automatic_updater_disabled', '__return_false', 99 );
		// The bar renders on `admin_notices`, and the update check only reads
		// on an admin screen, in cron or in WP-CLI. Without a screen these
		// tests would ask about a manifest that is never fetched.
		set_current_screen( 'dashboard' );
		// And with a screen and a cold cache it fetches the real one, from
		// the real useawt.com — so a test asking "is this site current?" was
		// really asking "is this checkout the newest published release?", and
		// CI went red on every commit that was not. Measured 2026-09-22, on
		// the commit underneath 2026.09.32. Nothing here may leave the box.
		add_filter( 'pre_http_request', array( $this, 'no_network' ), 10, 3 );
	}

	/**
	 * Refuse every outbound request, so a cold cache reads as "cannot say".
	 *
	 * @return \WP_Error Always.
	 */
	public function no_network() {
		return new \WP_Error( 'awt_test_no_network', 'Tests do not reach the network.' );
	}

	/**
	 * Leave no state behind: these options outlive a test otherwise.
	 */
	public function tear_down(): void {
		delete_option( UpdateNotice\LAST_RUN );
		delete_option( UpdateNotice\WAITING_SINCE );
		delete_site_transient( Updates\CACHE_KEY );
		remove_all_filters( 'pre_http_request' );
		remove_all_filters( 'awt_update_environment' );
		remove_all_filters( 'automatic_updater_disabled' );
		remove_all_filters( 'awt_deployed_from_source' );
		Settings\set( 'updates.mode', 'auto' );
		parent::tear_down();
	}

	/**
	 * Cache a manifest announcing $version.
	 *
	 * @param string $version  Version to announce.
	 * @param bool   $breaking Whether it is behind a wall.
	 */
	private function announce( string $version, bool $breaking = false ): void {
		set_site_transient(
			Updates\CACHE_KEY,
			array(
				'schemaVersion' => 1,
				'version'       => $version,
				'theme'         => array(
					'slug'    => Updates\slug(),
					'package' => 'https://github.com/useawt/awt-theme/releases/download/v1/awt.zip',
				),
				'plugin'        => array( 'package' => 'https://github.com/useawt/awt-blocks/releases/download/v1/awt-blocks.zip' ),
				// Newest first, and reaching back to the release this site is
				// running: the published list always contains it, and a list
				// that stops above it means the site has fallen below the
				// window, where nothing installs itself.
				'releases'      => array(
					array(
						'version'     => $version,
						'breaking'    => $breaking,
						'autoInstall' => ! $breaking,
						'theme'       => array( 'package' => 'https://github.com/useawt/awt-theme/releases/download/v1/awt.zip' ),
						'plugin'      => array( 'package' => 'https://github.com/useawt/awt-blocks/releases/download/v1/awt-blocks.zip' ),
					),
					array(
						'version'     => \AWT\Theme\AWT_THEME_VERSION,
						'breaking'    => false,
						'autoInstall' => true,
						'theme'       => array( 'package' => 'https://github.com/useawt/awt-theme/releases/download/v1/awt.zip' ),
						'plugin'      => array( 'package' => 'https://github.com/useawt/awt-blocks/releases/download/v1/awt-blocks.zip' ),
					),
				),
			),
			HOUR_IN_SECONDS
		);
	}

	/* --------------------------------------------------------- who is told */

	/** Somebody who cannot install an update is not told about one. */
	public function test_a_subscriber_is_told_nothing(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$this->assertSame( array(), UpdateNotice\state() );
	}

	/* ------------------------------------------------------------- states */

	/** A site with nothing to do says so. */
	public function test_a_current_site_says_so(): void {
		$state = UpdateNotice\state();

		$this->assertSame( 'current', $state['id'] );
		$this->assertSame( \AWT\Theme\AWT_THEME_VERSION, $state['version'] );
	}

	/**
	 * An update that will arrive on its own is not announced beforehand.
	 *
	 * The decision this encodes: report after, never warn before. A heads-up
	 * only reaches whoever logs in during the window, which on a quiet site is
	 * nobody — so it would be two notifications, one of them half-firing.
	 */
	public function test_an_update_that_is_coming_anyway_is_not_announced(): void {
		$this->announce( '2099.01.0' );

		$this->assertSame( 'current', UpdateNotice\state()['id'] );
	}

	/** An update that will not install itself is announced, and says why. */
	public function test_an_update_behind_a_wall_asks_for_a_person(): void {
		$this->announce( '2099.01.0', true );

		$state = UpdateNotice\state();

		$this->assertSame( 'needs-you', $state['id'] );
		$this->assertSame( '2099.01.0', $state['version'] );
		$this->assertTrue( $state['breaking'] );
	}

	/** So is one on a site that asked to be told rather than served. */
	public function test_notify_mode_asks_for_a_person_too(): void {
		Settings\set( 'updates.mode', 'notify' );
		$this->announce( '2099.01.0' );

		$state = UpdateNotice\state();

		$this->assertSame( 'needs-you', $state['id'] );
		$this->assertFalse( $state['breaking'] );
	}

	/** Checking turned off is worth saying out loud. */
	public function test_checks_off_is_said_plainly(): void {
		Settings\set( 'updates.mode', 'off' );

		$this->assertSame( 'checks-off', UpdateNotice\state()['id'] );
	}

	/**
	 * A site whose files arrive by a deploy is told that, and nothing else.
	 *
	 * All three AWT sites are deployed from source, and the first version of
	 * this ordering checked the folder name first — so `accessibilitycloud.com`
	 * was told to download a zip and upload it, on a site where nobody ever
	 * does that and where the folder name is a deliberate line in its deploy
	 * script. "Does this site install updates at all?" has to come before
	 * "would an update land in the right place?".
	 */
	public function test_a_site_deployed_from_source_is_told_only_that(): void {
		add_filter( 'awt_deployed_from_source', '__return_true' );

		$state = UpdateNotice\state();

		$this->assertSame( 'deployed', $state['id'] );
		$this->assertStringContainsString( 'own deployment', UpdateNotice\message( $state )['text'] );
	}

	/** Even when the folder would not match, which is not its problem. */
	public function test_a_deployed_site_is_not_nagged_about_its_folder(): void {
		add_filter( 'awt_deployed_from_source', '__return_true' );
		$this->announce( '2099.01.0' );
		$data                  = get_site_transient( Updates\CACHE_KEY );
		$data['theme']['slug'] = 'somewhere-else';
		set_site_transient( Updates\CACHE_KEY, $data, HOUR_IN_SECONDS );

		$state = UpdateNotice\state();

		$this->assertSame( 'deployed', $state['id'] );
		$this->assertNotSame( 'wrong-folder', $state['id'] );
	}

	/** But it is still told when a newer version exists, so somebody deploys it. */
	public function test_a_deployed_site_hears_about_a_new_version(): void {
		add_filter( 'awt_deployed_from_source', '__return_true' );
		$this->announce( '2099.01.0' );

		$state = UpdateNotice\state();

		$this->assertSame( '2099.01.0', $state['newest'] );
		$this->assertStringContainsString( '2099.01.0', UpdateNotice\message( $state )['text'] );
	}

	/**
	 * A theme installed under a different folder name is told why, and told
	 * not to "fix" it by renaming.
	 *
	 * Renaming would be the obvious move and the wrong one: every edited
	 * header, footer and template is filed against the old name.
	 */
	public function test_a_mismatched_folder_is_explained(): void {
		$this->announce( '2099.01.0' );
		$data                  = get_site_transient( Updates\CACHE_KEY );
		$data['theme']['slug'] = 'somewhere-else';
		set_site_transient( Updates\CACHE_KEY, $data, HOUR_IN_SECONDS );

		$state = UpdateNotice\state();

		$this->assertSame( 'wrong-folder', $state['id'] );
		$this->assertSame( Updates\slug(), $state['folder'] );

		$text = UpdateNotice\message( $state )['text'];
		$this->assertStringContainsString( Updates\slug(), $text );
		$this->assertStringContainsString( 'Replace current with uploaded', $text );
		$this->assertStringContainsString( 'do not', $text, 'it must warn against renaming' );
	}

	/**
	 * And told the same thing when it is only set to check for updates.
	 *
	 * This used to be said only to a site that installs its own updates. A
	 * site set to be told about them was told — "AWT 2099.01.0 is ready to
	 * install. Update now" — and sent to a screen with no AWT on it, because
	 * the package is withheld whatever the setting says. Measured 2026-09-22.
	 */
	public function test_a_mismatched_folder_is_explained_in_notify_mode_too(): void {
		Settings\set( 'updates.mode', 'notify' );
		$this->announce( '2099.01.0' );
		$data                  = get_site_transient( Updates\CACHE_KEY );
		$data['theme']['slug'] = 'somewhere-else';
		set_site_transient( Updates\CACHE_KEY, $data, HOUR_IN_SECONDS );

		$state = UpdateNotice\state();

		$this->assertSame( 'wrong-folder', $state['id'] );

		// And it names the release that is waiting, rather than promising to
		// mention it later and then standing in the way of the state that does.
		$this->assertSame( '2099.01.0', $state['version'] );
		$this->assertStringContainsString( '2099.01.0', UpdateNotice\message( $state )['text'] );
	}

	/** A host that forbids file changes is named, rather than silently winning. */
	public function test_a_host_that_forbids_updates_is_named(): void {
		add_filter( 'automatic_updater_disabled', '__return_true', 100 );

		$this->assertSame( 'host-blocked', UpdateNotice\state()['id'] );
	}

	/** After an unattended update, the site says what happened. */
	public function test_an_update_that_installed_itself_is_reported(): void {
		update_option(
			UpdateNotice\LAST_RUN,
			array(
				'at'      => time(),
				'version' => \AWT\Theme\AWT_THEME_VERSION,
				'ok'      => true,
			),
			false
		);

		$this->assertSame( 'just-updated', UpdateNotice\state()['id'] );
	}

	/** And it stops saying it once the news is old. */
	public function test_the_report_does_not_stay_up_forever(): void {
		update_option(
			UpdateNotice\LAST_RUN,
			array(
				'at'      => time() - ( UpdateNotice\REPORT_FOR + HOUR_IN_SECONDS ),
				'version' => \AWT\Theme\AWT_THEME_VERSION,
				'ok'      => true,
			),
			false
		);

		$this->assertSame( 'current', UpdateNotice\state()['id'] );
	}

	/**
	 * A failed install outranks everything, and says the site still works.
	 *
	 * This is the state that replaces WordPress's own email, which tells a
	 * site owner "if there was a fatal error the previous version has been
	 * restored" whether or not anything of the kind happened.
	 */
	public function test_a_failed_install_is_reported_first(): void {
		update_option(
			UpdateNotice\LAST_RUN,
			array(
				'at'      => time(),
				'version' => '2099.01.0',
				'ok'      => false,
			),
			false
		);

		$state = UpdateNotice\state();

		$this->assertSame( 'failed', $state['id'] );
		$this->assertSame( '2099.01.0', $state['version'] );
		$this->assertStringContainsString( 'still on', UpdateNotice\message( $state )['text'] );
	}

	/* -------------------------------------------------------- the clock */

	/** A waiting update says nothing about how long, at first. */
	public function test_a_waiting_update_is_patient_at_first(): void {
		$this->announce( '2099.01.0', true );

		$this->assertSame( 0, UpdateNotice\state()['waiting'] );
	}

	/** Once it has waited a fortnight, it starts counting. */
	public function test_a_long_wait_is_counted(): void {
		$this->announce( '2099.01.0', true );
		update_option(
			UpdateNotice\WAITING_SINCE,
			array(
				'version' => '2099.01.0',
				'at'      => time() - ( UpdateNotice\PATIENCE + ( 3 * DAY_IN_SECONDS ) ),
			),
			false
		);

		$state = UpdateNotice\state();

		$this->assertSame( 17, $state['waiting'] );
		$this->assertStringContainsString( '17 days', UpdateNotice\message( $state )['text'] );
		$this->assertStringContainsString( 'waiting behind it', UpdateNotice\message( $state )['text'] );
	}

	/** The clock restarts when a different version starts waiting. */
	public function test_the_clock_restarts_for_a_new_version(): void {
		update_option(
			UpdateNotice\WAITING_SINCE,
			array(
				'version' => '2099.01.0',
				'at'      => time() - ( 40 * DAY_IN_SECONDS ),
			),
			false
		);
		$this->announce( '2099.01.9', true );

		$this->assertSame( 0, UpdateNotice\state()['waiting'] );
	}

	/* ------------------------------------------------------- the wording */

	/**
	 * Every state has something to say, and says it with a link out.
	 *
	 * `mismatch` is here because it is the one state the live walkthrough
	 * could not produce: it needs the two halves on different versions, and
	 * the plugin's version comes from its own file header.
	 */
	public function test_every_state_has_a_message(): void {
		$states = array(
			array(
				'id'        => 'failed',
				'version'   => '2099.01.0',
				'installed' => '2026.09.29',
			),
			array(
				'id'     => 'mismatch',
				'theme'  => '2026.09.29',
				'plugin' => '2026.09.28',
				'match'  => false,
			),
			array( 'id' => 'checks-off' ),
			array( 'id' => 'host-blocked' ),
			array(
				'id'       => 'needs-you',
				'version'  => '2099.01.0',
				'breaking' => false,
				'waiting'  => 0,
			),
			array(
				'id'      => 'just-updated',
				'version' => '2026.09.29',
			),
			array(
				'id'      => 'current',
				'version' => '2026.09.29',
			),
		);

		foreach ( $states as $state ) {
			$message = UpdateNotice\message( $state );

			$this->assertIsArray( $message, $state['id'] );
			$this->assertNotSame( '', trim( wp_strip_all_tags( $message['text'] ) ), $state['id'] );
			$this->assertContains( $message['level'], array( 'info', 'warning', 'success', 'error' ), $state['id'] );
		}
	}

	/** The mismatch message names both versions, so it is actionable. */
	public function test_the_mismatch_message_names_both_versions(): void {
		$text = UpdateNotice\message(
			array(
				'id'     => 'mismatch',
				'theme'  => '2026.09.29',
				'plugin' => '2026.09.28',
				'match'  => false,
			)
		)['text'];

		$this->assertStringContainsString( '2026.09.29', $text );
		$this->assertStringContainsString( '2026.09.28', $text );
	}

	/* --------------------------------------------- the email core would send */

	/**
	 * A failed AWT update produces our email, not core's.
	 *
	 * Core's says: *"If there was a fatal error in the update, the previously
	 * installed version has been restored."* For AWT that is almost always
	 * untrue, and it lands unprompted in an inbox telling somebody their site
	 * may be broken.
	 */
	public function test_our_words_replace_core_s_when_only_awt_failed(): void {
		$email = UpdateNotice\rewrite_failure_email(
			array(
				'subject' => 'Some plugins and themes have failed to update',
				'body'    => 'If there was a fatal error in the update, the previously installed version has been restored.',
			),
			'fail',
			array(),
			array( $this->failure( 'awt', '2099.01.0' ) )
		);

		$this->assertStringContainsString( 'AWT could not update itself', $email['subject'] );
		$this->assertStringNotContainsString( 'fatal error', $email['body'] );
		$this->assertStringContainsString( 'working normally', $email['body'] );
		$this->assertStringContainsString( '2099.01.0', $email['body'] );
	}

	/**
	 * When something else failed too, core keeps its say and we add ours.
	 *
	 * Core's sentence is accurate enough for an ordinary plugin, and it is
	 * not our business to rewrite what it says about somebody else's.
	 */
	public function test_core_keeps_its_say_when_another_plugin_failed_too(): void {
		$email = UpdateNotice\rewrite_failure_email(
			array(
				'subject' => 'Some plugins and themes have failed to update',
				'body'    => 'CORE TEXT',
			),
			'fail',
			array(),
			array( $this->failure( 'awt', '2099.01.0' ), $this->failure( 'akismet', '5.0' ) )
		);

		$this->assertSame( 'Some plugins and themes have failed to update', $email['subject'] );
		$this->assertStringContainsString( 'CORE TEXT', $email['body'] );
		$this->assertStringContainsString( 'AWT tried to install', $email['body'] );
	}

	/** A run that did not involve AWT is left entirely alone. */
	public function test_someone_else_s_failure_is_not_ours_to_rewrite(): void {
		$original = array(
			'subject' => 'Some plugins and themes have failed to update',
			'body'    => 'CORE TEXT',
		);

		$this->assertSame(
			$original,
			UpdateNotice\rewrite_failure_email( $original, 'fail', array(), array( $this->failure( 'akismet', '5.0' ) ) )
		);
	}

	/** The success email is core's, and stays core's. */
	public function test_the_success_email_is_left_alone(): void {
		$original = array(
			'subject' => 'Some plugins and themes were automatically updated',
			'body'    => 'CORE TEXT',
		);

		$this->assertSame(
			$original,
			UpdateNotice\rewrite_failure_email( $original, 'success', array( $this->failure( 'awt', '1' ) ), array() )
		);
	}

	/**
	 * One entry in the shape core hands to the email filter.
	 *
	 * @param string $slug    Theme directory or plugin slug.
	 * @param string $version Version it was trying to reach.
	 * @return object A result row.
	 */
	private function failure( string $slug, string $version ): object {
		return (object) array(
			'item' => (object) array(
				'theme'       => $slug,
				'slug'        => $slug,
				'new_version' => $version,
			),
		);
	}
}
