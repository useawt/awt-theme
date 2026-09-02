<?php
/**
 * The contrast maths.
 *
 * This is the arithmetic behind every accessibility number the product shows an
 * author: the Colors screen's pass/fail badges, the palette audit, and the
 * ratios quoted in the spec and on `useawt.com`. It is small, pure, and
 * entirely unverified until now — which is an odd gap for the one calculation
 * a buyer would be entitled to check.
 *
 * The reference values below are WCAG's own, not this implementation's output:
 * black on white is exactly 21, a colour against itself is exactly 1, and the
 * threshold cases sit on 4.5 and 3.0 by definition.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\Contrast;

/**
 * WCAG contrast arithmetic.
 *
 * @covers \AWT\Theme\Contrast
 */
class Test_Contrast extends WP_UnitTestCase {

	/* ---------------------------------------------------------- hex parsing */

	/** A plain six-digit hex parses to its three channels. */
	public function test_a_six_digit_hex_parses(): void {
		$this->assertSame( array( 255, 128, 0 ), Contrast\hex_to_rgb( '#ff8000' ) );
	}

	/** Shorthand expands the way CSS does: #abc is #aabbcc, not #0abc. */
	public function test_shorthand_expands_by_doubling_each_digit(): void {
		$this->assertSame( array( 170, 187, 204 ), Contrast\hex_to_rgb( '#abc' ) );
	}

	/** The leading hash and any surrounding space are tolerated. */
	public function test_the_hash_and_surrounding_space_are_optional(): void {
		$this->assertSame( array( 255, 255, 255 ), Contrast\hex_to_rgb( '  ffffff ' ) );
	}

	/**
	 * Anything unparseable returns null rather than a guess. A wrong colour
	 * here would produce a confident, wrong ratio on a screen that exists to
	 * be trusted.
	 */
	public function test_unparseable_input_returns_null(): void {
		foreach ( array( '#12345', '#gggggg', 'rebeccapurple', '', '#' ) as $bad ) {
			$this->assertNull( Contrast\hex_to_rgb( $bad ), $bad );
		}
	}

	/* ---------------------------------------------------------------- ratios */

	/** WCAG's upper bound. Black on white is exactly 21:1. */
	public function test_black_on_white_is_twenty_one(): void {
		$this->assertEqualsWithDelta( 21.0, Contrast\ratio( '#000000', '#ffffff' ), 0.001 );
	}

	/** And its lower bound: a colour against itself is exactly 1:1. */
	public function test_a_colour_against_itself_is_one(): void {
		$this->assertEqualsWithDelta( 1.0, Contrast\ratio( '#767676', '#767676' ), 0.001 );
	}

	/** Order must not matter — the formula sorts lighter from darker itself. */
	public function test_the_ratio_is_symmetric(): void {
		$this->assertEqualsWithDelta(
			Contrast\ratio( '#0f62fe', '#ffffff' ),
			Contrast\ratio( '#ffffff', '#0f62fe' ),
			0.0001
		);
	}

	/**
	 * `#767676` on white is the canonical 4.5:1 boundary colour — the darkest
	 * grey that still passes AA for normal text.
	 */
	public function test_the_canonical_aa_boundary_colour_lands_on_four_point_five(): void {
		$this->assertEqualsWithDelta( 4.54, Contrast\ratio( '#767676', '#ffffff' ), 0.01 );
	}

	/**
	 * Carbon's own promise, and the reason D5 could reuse the token rather
	 * than invent one: `border-strong` clears 3:1 against the field fill.
	 */
	public function test_carbons_strong_border_clears_three_to_one_on_its_own_fill(): void {
		$this->assertGreaterThanOrEqual( 3.0, Contrast\ratio( '#8d8d8d', '#f4f4f4' ) );
	}

	/**
	 * Unparseable input yields 1.0 — the worst possible ratio. Failing safe
	 * matters here: a bad hex must never be reported as passing.
	 */
	public function test_a_bad_colour_fails_safe_at_one(): void {
		$this->assertSame( 1.0, Contrast\ratio( 'not-a-colour', '#ffffff' ) );
	}

	/* -------------------------------------------------------------- verdicts */

	/** Verdicts split at WCAG's own thresholds, inclusive at each boundary. */
	public function test_verdicts_split_at_the_wcag_thresholds(): void {
		$this->assertSame( 'pass', Contrast\verdict( 21.0 ) );
		$this->assertSame( 'pass', Contrast\verdict( 4.5 ), 'exactly 4.5 passes' );
		$this->assertSame( 'large', Contrast\verdict( 4.49 ) );
		$this->assertSame( 'large', Contrast\verdict( 3.0 ), 'exactly 3.0 is large-text only' );
		$this->assertSame( 'fail', Contrast\verdict( 2.99 ) );
		$this->assertSame( 'fail', Contrast\verdict( 1.0 ) );
	}

	/**
	 * The helper-text regression of 2026-08-02 in one assertion: 4.49 is not a
	 * pass. It sat on the marketing site reported as fine because the value was
	 * rounded before it was judged.
	 */
	public function test_four_point_four_nine_is_not_a_pass(): void {
		$this->assertNotSame( 'pass', Contrast\verdict( 4.49 ) );
	}
}
