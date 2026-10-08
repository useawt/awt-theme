<?php
/**
 * The ask to tell others about AWT, on What's new and the dashboard box.
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

	/** Leave the filter as it was. */
	public function tear_down(): void {
		remove_all_filters( 'awt_spread_the_word' );
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

		$this->assertStringContainsString( '<h3>Help more WordPress sites become accessible</h3>', $html );
		$this->assertStringContainsString( 'href="https://github.com/useawt/awt-theme"', $html );
		$this->assertStringContainsString( 'href="https://useawt.com/spread-the-word/"', $html );
		$this->assertStringContainsString( 'aria-hidden="true"', $html, 'the heart is decoration' );
	}

	/** The dashboard line carries the same two links. */
	public function test_the_line_links_to_github_and_the_page(): void {
		$html = $this->capture( '\\AWT\\Theme\\SpreadTheWord\\render_line' );

		$this->assertStringContainsString( '<a href="https://useawt.com/spread-the-word/">tell others about it</a>', $html );
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
	 * On What's new it sits between the heading and the release notes.
	 *
	 * The notes come from `build/changelog.json`, written at release time and
	 * absent on CI, and the tab says so instead of drawing the panel. Both
	 * branches are asserted rather than one assumed.
	 */
	public function test_whats_new_shows_it_above_the_notes(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$html = $this->capture( '\\AWT\\Theme\\WhatsNew\\render_tab' );

		if ( ! \AWT\Theme\WhatsNew\changelog() ) {
			$this->assertStringNotContainsString( 'awt-share-ask', $html );
			return;
		}

		$heading = strpos( $html, '<h2>' );
		$ask     = strpos( $html, 'class="awt-share-ask"' );
		$notes   = strpos( $html, '<details class="awt-whats-new-release"' );
		$this->assertIsInt( $ask );
		$this->assertLessThan( $ask, $heading );
		$this->assertLessThan( $notes, $ask );

		$pinned = strpos( $html, 'awt-whats-new-pinned' );
		if ( false !== $pinned ) {
			$this->assertLessThan( $ask, $pinned, 'notes that need action stay above the ask' );
		}
	}
}
