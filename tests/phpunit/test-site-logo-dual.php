<?php
/**
 * The Site Logo block with the light-mode and dark-mode logos from AWT
 * Settings.
 *
 * Only the header brand used those two logos, so a Site Logo elsewhere showed
 * the one WordPress logo in both modes, and a logo drawn for a dark background
 * lost its letters on a light page (2026-09-27).
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

/**
 * The markup the Site Logo block is given.
 *
 * @covers \AWT\Theme\site_logo_dual
 */
class Test_Site_Logo_Dual extends WP_UnitTestCase {

	/** A stand-in for core's Site Logo output. */
	private const HTML = '<div class="wp-block-site-logo"><a href="https://example.com/" class="custom-logo-link" rel="home"><img width="274" height="43" src="https://example.com/wp-logo.png" class="custom-logo" alt="Example logo" srcset="https://example.com/wp-logo.png 768w" sizes="(max-width: 274px) 100vw, 274px" /></a></div>';

	/**
	 * Both logos set: one image per mode, the block's own link and width kept.
	 */
	public function test_both_logos_print_one_image_per_mode(): void {
		$out = \AWT\Theme\site_logo_dual( self::HTML, 'https://example.com/light.png', 'https://example.com/dark.png' );

		$this->assertSame( 2, substr_count( $out, '<img' ) );
		$this->assertStringContainsString( 'src="https://example.com/light.png"', $out );
		$this->assertStringContainsString( 'src="https://example.com/dark.png"', $out );
		$this->assertStringContainsString( 'custom-logo awt-logo--light', $out );
		$this->assertStringContainsString( 'custom-logo awt-logo--dark', $out );
		$this->assertStringNotContainsString( 'wp-logo.png', $out );
		$this->assertStringNotContainsString( 'srcset', $out );
		$this->assertStringNotContainsString( 'height=', $out );
		$this->assertSame( 2, substr_count( $out, 'width="274"' ) );
		$this->assertSame( 2, substr_count( $out, 'alt="Example logo"' ) );
		$this->assertStringContainsString( '<a href="https://example.com/" class="custom-logo-link" rel="home">', $out );
	}

	/**
	 * One logo, the same logo twice, or no image: the block is left alone.
	 */
	public function test_nothing_changes_without_two_different_logos(): void {
		$this->assertSame( self::HTML, \AWT\Theme\site_logo_dual( self::HTML, 'https://example.com/light.png', '' ) );
		$this->assertSame( self::HTML, \AWT\Theme\site_logo_dual( self::HTML, '', 'https://example.com/dark.png' ) );
		$this->assertSame( self::HTML, \AWT\Theme\site_logo_dual( self::HTML, 'https://example.com/a.png', 'https://example.com/a.png' ) );
		$this->assertSame( '<div class="wp-block-site-logo"></div>', \AWT\Theme\site_logo_dual( '<div class="wp-block-site-logo"></div>', 'https://example.com/light.png', 'https://example.com/dark.png' ) );
	}
}
