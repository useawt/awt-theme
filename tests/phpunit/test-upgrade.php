<?php
/**
 * The one-time rename that runs when a site updates AWT.
 *
 * `awt_` was a three-letter prefix in the public namespace; 2026.08.0 renamed
 * the option and four meta keys to `awt_theme_`. A site that already had
 * settings would otherwise open to a theme that looks freshly installed, with
 * its per-page language choices gone.
 *
 * This was verified once, by hand, on a throwaway install (see the Stage 1
 * spec, "Zip re-upload and the settings migrations"). These tests are the
 * repeatable version, and they matter more than most: the code runs exactly
 * once per site, so a defect in it is a defect nobody can re-run to observe.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\Settings;
use AWT\Theme\Upgrade;

/**
 * The one-time `awt_` to `awt_theme_` rename.
 *
 * @covers \AWT\Theme\Upgrade
 */
class Test_Upgrade extends WP_UnitTestCase {

	/**
	 * Remove both the legacy and the current rows between tests.
	 */
	public function tear_down(): void {
		foreach ( array_keys( Upgrade\LEGACY_OPTIONS ) as $old ) {
			delete_option( $old );
		}
		delete_option( Settings\OPTION_KEY );
		Settings\flush_cache();
		parent::tear_down();
	}

	/* ----------------------------------------------------------- the option */

	/** The stored payload moves to the new name and the old row is removed. */
	public function test_the_option_moves_to_its_new_name(): void {
		$payload = wp_json_encode(
			array(
				'schemaVersion' => 1,
				'site'          => array( 'colorScheme' => 'dark' ),
			)
		);
		update_option( 'awt_settings', $payload );
		delete_option( Settings\OPTION_KEY );

		Upgrade\move_option();

		$this->assertSame( $payload, get_option( Settings\OPTION_KEY ) );
		$this->assertFalse( get_option( 'awt_settings', false ) );
	}

	/**
	 * A site that has already saved under the new name must never be
	 * overwritten by a stale row left behind under the old one. This is the
	 * dangerous direction: it would silently roll a site's settings back.
	 */
	public function test_an_existing_new_option_is_never_overwritten(): void {
		update_option( 'awt_settings', wp_json_encode( array( 'site' => array( 'colorScheme' => 'dark' ) ) ) );
		update_option( Settings\OPTION_KEY, wp_json_encode( array( 'site' => array( 'colorScheme' => 'light' ) ) ) );

		Upgrade\move_option();
		Settings\flush_cache();

		$this->assertSame( 'light', Settings\get( 'site.colorScheme' ) );
	}

	/** Running twice is a no-op, which is what `admin_init` guarantees will happen. */
	public function test_running_the_move_twice_changes_nothing(): void {
		update_option( 'awt_settings', wp_json_encode( array( 'schemaVersion' => 1 ) ) );
		delete_option( Settings\OPTION_KEY );

		Upgrade\move_option();
		$after_first = get_option( Settings\OPTION_KEY );
		Upgrade\move_option();

		$this->assertSame( $after_first, get_option( Settings\OPTION_KEY ) );
	}

	/** A fresh install has nothing to move and must not invent an option. */
	public function test_a_fresh_install_gains_nothing(): void {
		delete_option( 'awt_settings' );
		delete_option( Settings\OPTION_KEY );

		Upgrade\move_option();

		$this->assertFalse( get_option( Settings\OPTION_KEY, false ) );
	}

	/* ------------------------------------------------------------- the meta */

	/** Post meta keeps its value under the new key. */
	public function test_post_meta_keys_are_renamed_with_their_values(): void {
		$post = self::factory()->post->create();
		foreach ( Upgrade\LEGACY_POST_META as $old => $new ) {
			update_post_meta( $post, $old, 'kept-' . $old );
		}

		Upgrade\move_meta();

		foreach ( Upgrade\LEGACY_POST_META as $old => $new ) {
			$this->assertSame( 'kept-' . $old, get_post_meta( $post, $new, true ), $new );
			$this->assertSame( '', get_post_meta( $post, $old, true ), $old );
		}
	}

	/**
	 * User meta too — and this is the half most likely to be missed, because
	 * `wp_usermeta` names its primary key `umeta_id` while `wp_postmeta` uses
	 * `meta_id`. Asking for the wrong one returns null, which looks exactly
	 * like "there was nothing to move".
	 */
	public function test_user_meta_keys_are_renamed_with_their_values(): void {
		$user = self::factory()->user->create();
		foreach ( Upgrade\LEGACY_USER_META as $old => $new ) {
			update_user_meta( $user, $old, 'kept-' . $old );
		}

		Upgrade\move_meta();

		foreach ( Upgrade\LEGACY_USER_META as $old => $new ) {
			$this->assertSame( 'kept-' . $old, get_user_meta( $user, $new, true ), $new );
			$this->assertSame( '', get_user_meta( $user, $old, true ), $old );
		}
	}

	/** Every post carrying the old key moves, not just the first one found. */
	public function test_the_rename_reaches_every_post_not_only_the_first(): void {
		$posts = self::factory()->post->create_many( 3 );
		foreach ( $posts as $post ) {
			update_post_meta( $post, 'awt_page_lang', 'fr' );
		}

		Upgrade\move_meta();

		foreach ( $posts as $post ) {
			$this->assertSame( 'fr', get_post_meta( $post, 'awt_theme_page_lang', true ) );
		}
	}

	/** Nothing to move is not an error. */
	public function test_moving_meta_on_a_clean_site_is_harmless(): void {
		Upgrade\move_meta();

		$this->assertTrue( true );
	}

	/* ------------------------------------------------------------- together */

	/**
	 * End to end, the shape a real update takes: legacy names in place, one
	 * admin page load, everything readable under the new names — including the
	 * v1 -> v2 schema migration that happens on read.
	 */
	public function test_a_full_upgrade_keeps_every_setting(): void {
		$post = self::factory()->post->create();
		update_post_meta( $post, 'awt_page_lang', 'fr' );
		update_option(
			'awt_settings',
			wp_json_encode(
				array(
					'schemaVersion' => 1,
					'identity'      => array(
						'brandMode' => 'text-only',
						'logoUrl'   => 'https://example.com/logo.png',
					),
				)
			)
		);
		delete_option( Settings\OPTION_KEY );

		Upgrade\run();
		Settings\flush_cache();

		$this->assertSame( 'fr', get_post_meta( $post, 'awt_theme_page_lang', true ) );
		$this->assertSame( 'https://example.com/logo.png', Settings\get( 'identity.logoUrl' ) );
		$this->assertSame( 'auto', Settings\get( 'identity.brandMode' ), 'the v1 -> v2 migration still applies on read' );
		$this->assertSame( 3, Settings\get( 'schemaVersion' ) );
	}

	/* ------------------------------------ before the rename has had a chance */

	/**
	 * The gap these cover.
	 *
	 * `run()` is on `admin_init`. An automatic update lands with nobody logged
	 * in, so until somebody opens wp-admin the rename has not happened — and
	 * every assertion below describes what a *visitor* is served in that
	 * window. Before 2026-09-21 each one returned the default instead.
	 */

	/** Settings are read from the old option name until the rename runs. */
	public function test_settings_are_served_from_the_legacy_option_before_the_rename(): void {
		update_option(
			'awt_settings',
			wp_json_encode(
				array(
					'schemaVersion' => 2,
					'identity'      => array( 'logoUrl' => 'https://example.com/logo.png' ),
				)
			)
		);
		delete_option( Settings\OPTION_KEY );
		Settings\flush_cache();

		$this->assertSame( 'https://example.com/logo.png', Settings\get( 'identity.logoUrl' ) );
	}

	/** A saved row under the current name always wins over a stale legacy one. */
	public function test_the_current_option_wins_over_a_stale_legacy_row(): void {
		update_option( 'awt_settings', wp_json_encode( array( 'identity' => array( 'logoUrl' => 'https://example.com/old.png' ) ) ) );
		update_option( Settings\OPTION_KEY, wp_json_encode( array( 'identity' => array( 'logoUrl' => 'https://example.com/new.png' ) ) ) );
		Settings\flush_cache();

		$this->assertSame( 'https://example.com/new.png', Settings\get( 'identity.logoUrl' ) );
	}

	/** A page's language choice survives the window. */
	public function test_legacy_post_meta_is_read_before_the_rename(): void {
		$post = self::factory()->post->create();
		update_post_meta( $post, 'awt_page_lang', 'fr' );

		$this->assertSame( 'fr', Upgrade\legacy_post_meta( $post, 'awt_theme_page_lang' ) );
		$this->assertSame( 'fr', Upgrade\legacy_post_meta( $post, AWT\Theme\PageLanguage\META_KEY ) );
	}

	/** So does a hidden breadcrumb. */
	public function test_legacy_hide_breadcrumb_meta_is_read_before_the_rename(): void {
		$post = self::factory()->post->create();
		update_post_meta( $post, 'awt_hide_breadcrumb', '1' );

		$this->assertSame( '1', Upgrade\legacy_post_meta( $post, AWT\Theme\Breadcrumb\META_HIDE ) );
	}

	/** A key with no legacy name answers empty rather than guessing one. */
	public function test_a_key_with_no_legacy_name_reads_as_empty(): void {
		$post = self::factory()->post->create();

		$this->assertSame( '', Upgrade\legacy_post_meta( $post, 'awt_theme_invented_key' ) );
		$this->assertFalse( Upgrade\legacy_option( 'awt_theme_invented_option' ) );
	}

	/** And once the rename has run, the fallback finds nothing to do. */
	public function test_the_fallback_is_inert_after_the_rename(): void {
		$post = self::factory()->post->create();
		update_post_meta( $post, 'awt_page_lang', 'fr' );
		update_option( 'awt_settings', wp_json_encode( array( 'identity' => array( 'logoUrl' => 'https://example.com/logo.png' ) ) ) );
		delete_option( Settings\OPTION_KEY );

		Upgrade\run();
		Settings\flush_cache();

		$this->assertFalse( Upgrade\legacy_option( Settings\OPTION_KEY ) );
		$this->assertSame( '', Upgrade\legacy_post_meta( $post, AWT\Theme\PageLanguage\META_KEY ) );
		$this->assertSame( 'https://example.com/logo.png', Settings\get( 'identity.logoUrl' ), 'still readable, now from the new name' );
	}
}
