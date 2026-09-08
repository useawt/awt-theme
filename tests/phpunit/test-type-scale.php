<?php
/**
 * The typography size scale reaches the page.
 *
 * AWT Settings offered Compact, Default and Comfortable, stored the choice,
 * sanitized it and showed it selected — and nothing rendered it. No rule
 * anywhere read the setting, so all three looked identical on the site. A test
 * of the setting's storage would have passed throughout; what was missing was
 * a test that something is emitted.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\Settings;

/**
 * The size scale: the CSS it produces, and that it is actually enqueued.
 *
 * @covers \AWT\Theme\type_scale_css
 */
class Test_Type_Scale extends WP_UnitTestCase {

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
	 * The default scale needs no rule at all.
	 */
	public function test_default_scale_emits_nothing(): void {
		Settings\set( 'typography.sizeScale', 1.0 );

		$this->assertSame( '', \AWT\Theme\type_scale_css() );
	}

	/**
	 * Each of the other two choices moves the root, which is what carries the
	 * rem-based Carbon sizes with it.
	 */
	public function test_other_scales_move_the_root(): void {
		Settings\set( 'typography.sizeScale', 1.125 );
		$this->assertSame( 'html{font-size:calc(100% * 1.125)}', \AWT\Theme\type_scale_css() );

		Settings\set( 'typography.sizeScale', 0.875 );
		$this->assertSame( 'html{font-size:calc(100% * 0.875)}', \AWT\Theme\type_scale_css() );
	}

	/**
	 * And it is attached to the theme's stylesheet, not merely available.
	 *
	 * This is the assertion the missing feature would have failed: the
	 * function can be right and still reach nobody.
	 */
	public function test_the_scale_is_enqueued_with_the_theme_stylesheet(): void {
		Settings\set( 'typography.sizeScale', 0.875 );

		do_action( 'wp_enqueue_scripts' );

		$after = wp_styles()->get_data( 'awt-theme', 'after' );
		$this->assertIsArray( $after, 'the theme stylesheet should carry inline CSS' );
		$this->assertStringContainsString(
			'font-size:calc(100% * 0.875)',
			implode( '', $after )
		);
	}
}
