<?php
/**
 * The ask to tell others about AWT, under every AWT Settings tab and in the
 * dashboard box.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

/**
 * Where the ask shows, what it links to, and that it can be turned off.
 *
 * @covers \AWT\Theme\SpreadTheWord
 */
class Test_Spread_The_Word extends WP_UnitTestCase {

	/** Leave the filter and the query string as they were. */
	public function tear_down(): void {
		remove_all_filters( 'awt_spread_the_word' );
		unset( $_GET['tab'] );
		parent::tear_down();
	}

	/**
	 * Capture what a function prints.
	 *
	 * @param callable $render The function.
	 * @return string Its output.
	 */
	private function capture( callable $render ): string {
		ob_start();
		$render();
		return (string) ob_get_clean();
	}

	/** The card names what it asks for and links to both places. */
	public function test_the_card_links_to_github_and_the_page(): void {
		$html = $this->capture( '\\AWT\\Theme\\SpreadTheWord\\render_card' );

		$this->assertStringContainsString( '<h2>Help more WordPress sites become accessible</h2>', $html );
		$this->assertStringContainsString( 'href="https://github.com/useawt/awt-theme"', $html );
		$this->assertStringContainsString( 'href="https://useawt.com/spread-the-word/"', $html );
		$this->assertStringContainsString( 'aria-hidden="true"', $html, 'the heart is decoration' );
	}

	/** The dashboard line carries the same two links. */
	public function test_the_line_links_to_github_and_the_page(): void {
		$html = $this->capture( '\\AWT\\Theme\\SpreadTheWord\\render_line' );

		$this->assertStringContainsString( '<a href="https://useawt.com/spread-the-word/">tell others</a>', $html );
		$this->assertStringContainsString( '<a href="https://github.com/useawt/awt-theme">star it on GitHub</a>', $html );
	}

	/** A build that is not the free one can turn both off. */
	public function test_the_filter_turns_it_off(): void {
		add_filter( 'awt_spread_the_word', '__return_false' );

		$this->assertSame( '', $this->capture( '\\AWT\\Theme\\SpreadTheWord\\render_card' ) );
		$this->assertSame( '', $this->capture( '\\AWT\\Theme\\SpreadTheWord\\render_line' ) );
	}

	/** In the dashboard box the ask comes first, above the version. */
	public function test_the_dashboard_box_asks_above_the_version(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$html = $this->capture( '\\AWT\\Theme\\DashboardWidget\\render' );

		$ask     = strpos( $html, 'class="awt-dash__ask"' );
		$version = strpos( $html, 'class="awt-dash__version"' );
		$this->assertIsInt( $ask );
		$this->assertIsInt( $version );
		$this->assertLessThan( $version, $ask );
	}

	/** Turned off, the box starts with the version again. */
	public function test_the_dashboard_box_without_the_ask(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		add_filter( 'awt_spread_the_word', '__return_false' );

		$html = $this->capture( '\\AWT\\Theme\\DashboardWidget\\render' );

		$this->assertStringNotContainsString( 'awt-dash__ask', $html );
		$this->assertStringContainsString( 'awt-dash__version', $html );
	}

	/**
	 * Every AWT Settings tab ends with it, after the tab's own content.
	 */
	public function test_every_settings_tab_ends_with_it(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		foreach ( array_keys( \AWT\Theme\AdminPage\tabs() ) as $tab ) {
			$_GET['tab'] = $tab;
			$html        = $this->capture( '\\AWT\\Theme\\AdminPage\\render_page' );

			$ask = strpos( $html, 'class="awt-share-ask"' );
			$this->assertIsInt( $ask, "the {$tab} tab shows the ask" );
			$this->assertSame( 1, substr_count( $html, 'class="awt-share-ask"' ), "the {$tab} tab shows it once" );

			$last_form = strrpos( $html, '</form>' );
			if ( false !== $last_form ) {
				$this->assertLessThan( $ask, $last_form, "on the {$tab} tab it follows the tab's forms" );
			}
		}
	}

	/** Turned off, no tab shows it. */
	public function test_no_settings_tab_shows_it_when_off(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		add_filter( 'awt_spread_the_word', '__return_false' );

		foreach ( array_keys( \AWT\Theme\AdminPage\tabs() ) as $tab ) {
			$_GET['tab'] = $tab;
			$this->assertStringNotContainsString( 'awt-share-ask', $this->capture( '\\AWT\\Theme\\AdminPage\\render_page' ), $tab );
		}
	}

	/** What's new no longer carries its own copy. */
	public function test_whats_new_does_not_show_it_itself(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$this->assertStringNotContainsString( 'awt-share-ask', $this->capture( '\\AWT\\Theme\\WhatsNew\\render_tab' ) );
	}
}
