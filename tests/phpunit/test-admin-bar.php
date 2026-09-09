<?php
/**
 * The AWT menu in the toolbar.
 *
 * Two things are worth holding still. The state has to be readable without a
 * network request and without wp-admin, because the menu renders on the front
 * end too — the first version read WordPress's own update transient and said
 * "Update checks are off" on the front end of a site whose checks were on.
 * And the colour of the dot must never be the only thing that says the state.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\Settings;
use AWT\Theme\Updates;
use AWT\Theme\AdminBar;

/**
 * The toolbar menu: what it says about the version, and what it offers.
 *
 * @covers \AWT\Theme\AdminBar\update_state
 * @covers \AWT\Theme\AdminBar\add_menu
 */
class Test_Admin_Bar extends WP_UnitTestCase {

	/**
	 * Start from a clean settings row and no cached manifest.
	 */
	public function set_up(): void {
		parent::set_up();
		Settings\flush_cache();
		delete_site_transient( Updates\CACHE_KEY );
	}

	/**
	 * Leave nothing behind.
	 */
	public function tear_down(): void {
		delete_option( Settings\OPTION_KEY );
		delete_site_transient( Updates\CACHE_KEY );
		Settings\flush_cache();
		parent::tear_down();
	}

	/**
	 * Put a manifest in the cache the update check fills.
	 *
	 * @param string $version Version the manifest announces.
	 */
	private function cache_manifest( string $version ): void {
		set_site_transient(
			Updates\CACHE_KEY,
			array(
				'version' => $version,
				'theme'   => array(
					'slug'       => 'awt',
					'releaseUrl' => 'https://example.org/',
				),
			),
			HOUR_IN_SECONDS
		);
	}

	/**
	 * Build the menu as an administrator and hand back the toolbar.
	 */
	private function menu(): WP_Admin_Bar {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		$bar = new WP_Admin_Bar();
		AdminBar\add_menu( $bar );
		return $bar;
	}

	/**
	 * Nothing has been checked yet, so nothing can be claimed.
	 */
	public function test_no_cached_manifest_is_unknown(): void {
		Settings\set( 'updates.check', true );

		$this->assertSame( 'unknown', AdminBar\update_state() );
	}

	/**
	 * With the check switched off, "Up to date" would be a claim this site
	 * has no way to make.
	 */
	public function test_checks_off_is_unknown(): void {
		$this->cache_manifest( '2999.01.0' );
		Settings\set( 'updates.check', false );

		$this->assertSame( 'unknown', AdminBar\update_state() );
	}

	/**
	 * The version the site runs is the one the manifest names.
	 */
	public function test_matching_version_is_current(): void {
		Settings\set( 'updates.check', true );
		$this->cache_manifest( \AWT\Theme\AWT_THEME_VERSION );

		$this->assertSame( 'current', AdminBar\update_state() );
	}

	/**
	 * And a newer one out there is an update.
	 */
	public function test_newer_version_is_an_update(): void {
		Settings\set( 'updates.check', true );
		$this->cache_manifest( '2999.01.0' );

		$this->assertSame( 'update', AdminBar\update_state() );
	}

	/**
	 * Read from the cache, not from the update transient WordPress keeps —
	 * that one is rewritten on every read by a function that answers nothing
	 * outside wp-admin, which is how the front end came to show the wrong
	 * state. Emptying it must change nothing here.
	 */
	public function test_the_state_does_not_depend_on_the_update_transient(): void {
		Settings\set( 'updates.check', true );
		$this->cache_manifest( '2999.01.0' );
		delete_site_transient( 'update_themes' );
		delete_site_transient( 'update_plugins' );

		$this->assertSame( 'update', AdminBar\update_state() );
	}

	/**
	 * The rows, in the order they were asked for.
	 */
	public function test_the_menu_has_its_four_rows(): void {
		Settings\set( 'updates.check', true );
		$this->cache_manifest( \AWT\Theme\AWT_THEME_VERSION );

		$bar = $this->menu();

		$this->assertNotNull( $bar->get_node( 'awt' ) );
		foreach ( array( 'awt-status', 'awt-header', 'awt-footer', 'awt-settings' ) as $id ) {
			$node = $bar->get_node( $id );
			$this->assertNotNull( $node, "$id should be in the menu" );
			$this->assertSame( 'awt', $node->parent );
		}
	}

	/**
	 * Colour is never the only carrier: the state is written out in the first
	 * row, and again for a screen reader on the wordmark itself.
	 */
	public function test_the_state_is_in_words_not_only_in_colour(): void {
		Settings\set( 'updates.check', true );
		$this->cache_manifest( '2999.01.0' );

		$bar = $this->menu();

		// The visible wordmark stays part of the name (WCAG 2.5.3) and the
		// state reads on from it, rather than the two being announced twice.
		$this->assertStringContainsString(
			'>AWT: Update available<',
			$bar->get_node( 'awt' )->title
		);
		$this->assertStringContainsString( 'Update available', $bar->get_node( 'awt-status' )->title );
	}

	/**
	 * The first row leads to the update screen only when there is something
	 * to install.
	 */
	public function test_the_status_row_links_only_when_there_is_an_update(): void {
		Settings\set( 'updates.check', true );

		$this->cache_manifest( '2999.01.0' );
		$this->assertStringContainsString( 'update-core.php', $this->menu()->get_node( 'awt-status' )->href );

		$this->cache_manifest( \AWT\Theme\AWT_THEME_VERSION );
		$this->assertSame( '', $this->menu()->get_node( 'awt-status' )->href );
	}

	/**
	 * Every row leads somewhere only a theme editor can open, so someone who
	 * cannot open them is not shown the menu.
	 */
	public function test_a_subscriber_does_not_see_the_menu(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		$bar = new WP_Admin_Bar();
		AdminBar\add_menu( $bar );

		$this->assertNull( $bar->get_node( 'awt' ) );
	}

	/**
	 * The two shortcuts point at this theme's own parts, not a hard-coded slug.
	 */
	public function test_the_shortcuts_open_this_theme_s_parts(): void {
		Settings\set( 'updates.check', true );
		$this->cache_manifest( \AWT\Theme\AWT_THEME_VERSION );

		$bar  = $this->menu();
		$slug = get_stylesheet();

		$this->assertStringContainsString( '/wp_template_part/' . $slug . '//header', $bar->get_node( 'awt-header' )->href );
		$this->assertStringContainsString( 'canvas=edit', $bar->get_node( 'awt-header' )->href );
		$this->assertStringContainsString( '/wp_template_part/' . $slug . '//footer', $bar->get_node( 'awt-footer' )->href );
	}
}
