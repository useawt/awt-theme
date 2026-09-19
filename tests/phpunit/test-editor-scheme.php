<?php
/**
 * Which colour scheme the editor canvas previews.
 *
 * The canvas is a preview of the page, so it has to answer the same question
 * the page answers for the person looking at it. The site can answer three
 * ways — a scheme pinned for everyone, a scheme this author chose on the site
 * with its own toggle, or neither, in which case the desktop decides.
 *
 * Only the first and third were read. An author who had set the site to light
 * while their desktop was dark authored in dark against a light site: two
 * preferences, and the editor honoured the one that was not about the site
 * (reported on a live site, 2026-09-19).
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

/**
 * The three answers, and which one the canvas takes.
 *
 * @covers \AWT\Theme\editor_scheme
 */
class Test_Editor_Scheme extends WP_UnitTestCase {

	/**
	 * Put the site's own setting where the resolver reads it.
	 *
	 * @param string $value 'light', 'dark' or 'default'.
	 */
	private function site_scheme( string $value ): void {
		\AWT\Theme\Settings\set( 'site.colorScheme', $value );
	}

	/**
	 * Leave the cookie and the setting as they were found.
	 */
	public function tear_down(): void {
		unset( $_COOKIE['awt_color_scheme'] );
		\AWT\Theme\Settings\set( 'site.colorScheme', 'default' );
		parent::tear_down();
	}

	/**
	 * A site that pins a scheme is the whole answer: an author's own choice
	 * cannot apply on a site that does not offer the toggle.
	 */
	public function test_a_pinned_site_wins_over_a_cookie(): void {
		$this->site_scheme( 'light' );
		$_COOKIE['awt_color_scheme'] = 'dark';

		$this->assertSame( 'light', \AWT\Theme\editor_scheme() );
	}

	/**
	 * The author's own choice on the site, which is the case that was missing.
	 */
	public function test_the_author_choice_is_taken(): void {
		$this->site_scheme( 'default' );
		$_COOKIE['awt_color_scheme'] = 'light';

		$this->assertSame( 'light', \AWT\Theme\editor_scheme() );
	}

	/**
	 * With nothing chosen, the desktop decides — as it did before.
	 */
	public function test_no_choice_follows_the_desktop(): void {
		$this->site_scheme( 'default' );

		$this->assertSame( 'default', \AWT\Theme\editor_scheme() );
	}

	/**
	 * `auto` is a real value of that cookie and means "follow the desktop",
	 * so it must not be mistaken for a chosen scheme.
	 */
	public function test_auto_means_the_desktop(): void {
		$this->site_scheme( 'default' );
		$_COOKIE['awt_color_scheme'] = 'auto';

		$this->assertSame( 'default', \AWT\Theme\editor_scheme() );
	}

	/**
	 * The canvas CSS follows the answer, not the site setting: a chosen light
	 * scheme emits the light scope alone, with no desktop media query to
	 * override it.
	 */
	public function test_the_canvas_css_follows_the_choice(): void {
		$this->site_scheme( 'default' );
		$_COOKIE['awt_color_scheme'] = 'light';

		$css = \AWT\Theme\editor_scope_css();

		$this->assertNotSame( '', $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}

	/**
	 * And with nothing chosen it still carries both, so the canvas moves with
	 * the desktop the way the page would.
	 */
	public function test_the_canvas_css_still_offers_both_when_nothing_is_chosen(): void {
		$this->site_scheme( 'default' );

		$this->assertStringContainsString( 'prefers-color-scheme', \AWT\Theme\editor_scope_css() );
	}
}
