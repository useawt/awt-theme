<?php
/**
 * The site's Custom CSS, as the editor canvas receives it.
 *
 * The canvas body carries no `cds--*` scope class, so the active family's
 * selectors are re-rooted onto `body.editor-styles-wrapper`. Which family is
 * active follows the site's colour-scheme setting — and when that setting is
 * `default` the site follows each visitor, the author included, so both
 * families have to reach the canvas with the dark one behind
 * `prefers-color-scheme`.
 *
 * That last case shipped wrong: only the light family was re-rooted, while the
 * scope tokens did honour the media query. An author on a dark desktop got the
 * site's light backgrounds under dark-mode text — near-white on near-white,
 * found while authoring at night on a live site (2026-09-18).
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

/**
 * The Custom CSS the editor canvas is handed.
 *
 * @covers \AWT\Theme\editor_custom_css
 */
class Test_Editor_Custom_Css extends WP_UnitTestCase {

	/** A stand-in for the shape real Custom CSS has. */
	private const CSS = '.cds--white,
.cds--g10 { --cds-background: #fcfdff; }
.cds--g90,
.cds--g100 { --cds-background: #11161f; }
.cds--g100 .awt-band { background: #16202b; }';

	/**
	 * A site pinned to light previews the light family alone.
	 */
	public function test_a_pinned_light_site_gets_only_the_light_family(): void {
		$out = \AWT\Theme\editor_custom_css( self::CSS, 'light' );

		$this->assertStringContainsString( 'body.editor-styles-wrapper { --cds-background: #fcfdff; }', $out );
		$this->assertStringNotContainsString( '@media (prefers-color-scheme: dark)', $out );
		// The dark family is left alone; those selectors match nothing here.
		$this->assertStringContainsString( '.cds--g100 .awt-band', $out );
	}

	/**
	 * A site pinned to dark previews the dark family alone.
	 */
	public function test_a_pinned_dark_site_gets_only_the_dark_family(): void {
		$out = \AWT\Theme\editor_custom_css( self::CSS, 'dark' );

		$this->assertStringContainsString( 'body.editor-styles-wrapper { --cds-background: #11161f; }', $out );
		$this->assertStringContainsString( 'body.editor-styles-wrapper .awt-band', $out );
		$this->assertStringNotContainsString( '@media (prefers-color-scheme: dark)', $out );
	}

	/**
	 * A site that follows the visitor carries both, dark behind the media query.
	 */
	public function test_a_site_that_follows_the_visitor_carries_both(): void {
		$out = \AWT\Theme\editor_custom_css( self::CSS, 'default' );

		// Light first, unconditionally.
		$this->assertStringContainsString( 'body.editor-styles-wrapper { --cds-background: #fcfdff; }', $out );

		// Then the dark family, behind the author's own system setting, and
		// after it in source order so it wins on a dark desktop.
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark){', $out );
		$dark = substr( $out, (int) strpos( $out, '@media (prefers-color-scheme: dark){' ) );
		$this->assertStringContainsString( 'body.editor-styles-wrapper { --cds-background: #11161f; }', $dark );
	}

	/**
	 * Empty Custom CSS produces nothing to inject.
	 */
	public function test_nothing_to_re_root_stays_nothing(): void {
		$this->assertSame( '', \AWT\Theme\editor_custom_css( '', 'default' ) );
	}
}
