<?php
/**
 * The "Header width" setting reaches the page and the editor canvas.
 *
 * The header spans the screen by default, the way Carbon's UI Shell draws it.
 * With the setting on, the bar still spans the screen and only its contents
 * are pulled in to the site's content width, so the logo lines up with the
 * page below it.
 *
 * The rule needs `!important`: the header template part is a group block whose
 * padding is zero, and a block's spacing lands in the tag's own `style`
 * attribute. Without it the declaration is in the cascade and loses silently,
 * which is how it first read.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\Settings;

/**
 * The header width: the CSS it produces, and that it is actually delivered.
 *
 * @covers \AWT\Theme\header_contain_css
 */
class Test_Header_Width extends WP_UnitTestCase {

	/**
	 * Start from a clean settings row.
	 */
	public function set_up(): void {
		parent::set_up();
		Settings\flush_cache();
	}

	/**
	 * Leave no settings behind for the next test.
	 */
	public function tear_down(): void {
		delete_option( Settings\OPTION_KEY );
		Settings\flush_cache();
		parent::tear_down();
	}

	/**
	 * A full-width header is the default, and it needs no rule at all.
	 */
	public function test_default_emits_nothing(): void {
		$this->assertFalse( Settings\get( 'header.containWidth' ) );
		$this->assertSame( '', \AWT\Theme\header_contain_css() );
	}

	/**
	 * On, the rule pads the header in to the site's content width — and
	 * carries `!important`, without which the block's own inline padding wins.
	 */
	public function test_contained_header_pads_in_to_the_content_width(): void {
		Settings\set( 'header.containWidth', true );

		$css = \AWT\Theme\header_contain_css();
		$this->assertStringContainsString( '.cds--header{padding-inline:max(0px,', $css );
		$this->assertStringContainsString( '--wp--style--global--content-size', $css );
		$this->assertStringContainsString( '!important', $css );
	}

	/**
	 * And it is attached to the theme's stylesheet, not merely available.
	 */
	public function test_the_rule_is_enqueued_with_the_theme_stylesheet(): void {
		Settings\set( 'header.containWidth', true );

		do_action( 'wp_enqueue_scripts' );

		$after = wp_styles()->get_data( 'awt-theme', 'after' );
		$this->assertIsArray( $after, 'the theme stylesheet should carry inline CSS' );
		$this->assertStringContainsString( 'padding-inline:max(0px,', implode( '', $after ) );
	}

	/**
	 * The Site Editor is where the header is edited, so the canvas has to show
	 * the width the page will have.
	 */
	public function test_the_rule_reaches_the_editor_canvas(): void {
		Settings\set( 'header.containWidth', true );

		$styles = apply_filters( 'block_editor_settings_all', array( 'styles' => array() ) )['styles'];
		$css    = implode( "\n", array_column( $styles, 'css' ) );

		$this->assertStringContainsString( 'body.editor-styles-wrapper .cds--header{padding-inline:max(0px,', $css );
	}

	/**
	 * Off again, nothing is emitted in either place.
	 */
	public function test_turning_it_off_removes_the_rule_everywhere(): void {
		Settings\set( 'header.containWidth', true );
		Settings\set( 'header.containWidth', false );

		$this->assertSame( '', \AWT\Theme\header_contain_css() );

		$styles = apply_filters( 'block_editor_settings_all', array( 'styles' => array() ) )['styles'];
		$this->assertStringNotContainsString( 'padding-inline', implode( "\n", array_column( $styles, 'css' ) ) );
	}

	/**
	 * The stored value is a boolean whatever the form posts, and the header
	 * colour setting beside it is left alone.
	 */
	public function test_the_setting_sanitizes_to_a_boolean(): void {
		Settings\save(
			array(
				'header' => array(
					'colorScheme'  => 'dark',
					'containWidth' => '1',
				),
			)
		);

		$this->assertTrue( Settings\get( 'header.containWidth' ) );
		$this->assertSame( 'dark', Settings\get( 'header.colorScheme' ) );
	}
}
