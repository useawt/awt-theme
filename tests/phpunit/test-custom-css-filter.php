<?php
/**
 * The `awt_custom_css` filter: CSS a plugin adds to the site's Custom CSS.
 *
 * Whatever the filter adds is printed with the owner's Custom CSS at the end
 * of the head, and re-rooted into the editor canvas the same way. A plugin
 * that recolors the design system (AWT Premium's brand colors) relies on
 * both, and on the owner's CSS still coming last.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

/**
 * The filter reaches the page and the canvas.
 *
 * @covers \AWT\Theme\custom_css
 */
class Test_Custom_Css_Filter extends WP_UnitTestCase {

	/** What the stand-in plugin adds: a token override in the light family. */
	private const ADDED = '.cds--white,.cds--g10{--cds-focus:#123456}';

	/**
	 * Add the stand-in plugin's CSS before the owner's.
	 */
	public function set_up(): void {
		parent::set_up();
		add_filter( 'awt_custom_css', array( $this, 'prepend' ) );
	}

	/**
	 * Remove the stand-in and the owner's CSS.
	 */
	public function tear_down(): void {
		remove_filter( 'awt_custom_css', array( $this, 'prepend' ) );
		\AWT\Theme\Settings\set( 'customCss', '' );
		\AWT\Theme\Settings\set( 'site.colorScheme', 'default' );
		parent::tear_down();
	}

	/**
	 * The stand-in plugin.
	 *
	 * @param string $css The owner's Custom CSS.
	 */
	public function prepend( string $css ): string {
		return self::ADDED . "\n" . $css;
	}

	/** The filter runs on the owner's CSS and can put its own first. */
	public function test_added_css_comes_before_the_owners(): void {
		\AWT\Theme\Settings\set( 'customCss', '.awt-owner{color:red}' );

		$this->assertSame( self::ADDED . "\n.awt-owner{color:red}", \AWT\Theme\custom_css() );
	}

	/** The page prints it even when the owner wrote no Custom CSS. */
	public function test_the_page_prints_it_without_any_custom_css(): void {
		ob_start();
		do_action( 'wp_head' );
		$head = (string) ob_get_clean();

		$this->assertStringContainsString( '<style id="awt-custom-css">' . self::ADDED, $head );
	}

	/** The canvas gets it re-rooted, as it does the owner's CSS. */
	public function test_the_canvas_gets_it_rerooted(): void {
		\AWT\Theme\Settings\set( 'site.colorScheme', 'light' );
		$styles = apply_filters( 'block_editor_settings_all', array( 'styles' => array() ) )['styles'];
		$css    = implode( "\n", array_column( $styles, 'css' ) );

		$this->assertStringContainsString( 'body.editor-styles-wrapper,body.editor-styles-wrapper{--cds-focus:#123456}', $css );
	}
}
