<?php
/**
 * The side padding the breadcrumb region is given.
 *
 * The trail sits above `<main>` and has to line up with the first thing below
 * it. On the default page template that is the page title, which takes
 * `<main>`'s own side padding — so the region copies it. On `page-no-title`
 * there is no title, `<main>` is deliberately `padding: 0` on all four sides,
 * and the gutter comes from the site's root padding one level in.
 *
 * Copying the zero faithfully put the trail against the edge of the screen
 * while the content kept its inset, at every width below the content size.
 * Above it the `margin-inline: auto` that centres the trail supplied an inset
 * of its own and hid the gap — so the bug was invisible at the widths people
 * test at (awt-theme#1, 2026-09-20).
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\Breadcrumb;

/**
 * What `wrap_region()` emits for each shape of `<main>` padding.
 *
 * @covers \AWT\Theme\Breadcrumb\wrap_region
 * @covers \AWT\Theme\Breadcrumb\is_blank_padding
 */
class Test_Breadcrumb_Region extends WP_UnitTestCase {

	/** A stand-in for the rendered trail. */
	private const TRAIL = '<nav class="cds--breadcrumb"></nav>';

	/**
	 * A template that supplies its own gutter is copied, as before.
	 */
	public function test_a_padded_main_is_copied(): void {
		$out = Breadcrumb\wrap_region(
			self::TRAIL,
			array(
				'left'  => 'var:preset|spacing|06',
				'right' => 'var:preset|spacing|06',
			)
		);

		$this->assertStringContainsString( 'padding-left:var(--wp--preset--spacing--06)', $out );
		$this->assertStringContainsString( 'padding-right:var(--wp--preset--spacing--06)', $out );
		$this->assertStringNotContainsString( 'root--padding', $out );
	}

	/**
	 * A zeroed `<main>` is not a page without a gutter: the gutter is the
	 * site's root padding, and that is what the trail has to match.
	 */
	public function test_a_zeroed_main_falls_back_to_the_root_padding(): void {
		$out = Breadcrumb\wrap_region(
			self::TRAIL,
			array(
				'top'    => '0',
				'right'  => '0',
				'bottom' => '0',
				'left'   => '0',
			)
		);

		$this->assertStringContainsString( 'padding-left:var(--wp--style--root--padding-left)', $out );
		$this->assertStringContainsString( 'padding-right:var(--wp--style--root--padding-right)', $out );
	}

	/**
	 * Same for a template that says nothing about padding at all.
	 */
	public function test_no_padding_at_all_falls_back_too(): void {
		$out = Breadcrumb\wrap_region( self::TRAIL, array() );

		$this->assertStringContainsString( 'padding-left:var(--wp--style--root--padding-left)', $out );
		$this->assertStringContainsString( 'padding-right:var(--wp--style--root--padding-right)', $out );
	}

	/**
	 * One side each way, because a template may set only one.
	 */
	public function test_each_side_is_decided_on_its_own(): void {
		$out = Breadcrumb\wrap_region(
			self::TRAIL,
			array(
				'left'  => '0px',
				'right' => '2rem',
			)
		);

		$this->assertStringContainsString( 'padding-left:var(--wp--style--root--padding-left)', $out );
		$this->assertStringContainsString( 'padding-right:2rem', $out );
	}

	/**
	 * Zero is zero in any unit — and a real measurement is not zero just
	 * because it starts with one.
	 *
	 * @dataProvider blank_values
	 * @param string $value A padding value.
	 * @param bool   $blank Whether it leaves the element against the edge.
	 */
	public function test_zero_is_recognised_in_any_unit( string $value, bool $blank ): void {
		$this->assertSame( $blank, Breadcrumb\is_blank_padding( $value ) );
	}

	/**
	 * Values a template might carry, and whether each is a gutter.
	 *
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public function blank_values(): array {
		return array(
			'empty'        => array( '', true ),
			'bare zero'    => array( '0', true ),
			'zero px'      => array( '0px', true ),
			'zero rem'     => array( '0rem', true ),
			'zero percent' => array( '0%', true ),
			'zero decimal' => array( '0.00rem', true ),
			'spaced zero'  => array( ' 0 ', true ),
			'half a rem'   => array( '0.5rem', false ),
			'one px'       => array( '1px', false ),
			'a preset'     => array( 'var:preset|spacing|06', false ),
			'a variable'   => array( 'var(--wp--style--root--padding-left)', false ),
		);
	}
}
