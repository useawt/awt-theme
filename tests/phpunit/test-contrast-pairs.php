<?php
/**
 * Carbon's palette and its contrast pairs, as data for a contrast check.
 *
 * The palette is read from the compiled Carbon CSS rather than typed, so it
 * cannot drift from what the page uses; the pairs name only tokens that
 * palette has. AWT Premium's brand color editor checks every pair live.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\DesignSystem\Carbon;

/**
 * Palette and pairs.
 *
 * @covers \AWT\Theme\DesignSystem\Carbon::get_resolved_palette
 * @covers \AWT\Theme\DesignSystem\Carbon::get_role_map
 */
class Test_Contrast_Pairs extends WP_UnitTestCase {

	/** Each of Carbon's four themes, with its own values. */
	public function test_the_palette_has_every_theme_from_the_css(): void {
		$palette = ( new Carbon() )->get_resolved_palette();

		$this->assertSame( array( 'white', 'g10', 'g90', 'g100' ), array_keys( $palette ) );
		$this->assertSame( '#ffffff', $palette['white']['background'] );
		$this->assertSame( '#f4f4f4', $palette['g10']['background'] );
		$this->assertSame( '#161616', $palette['g100']['background'] );
		$this->assertSame( '#0f62fe', $palette['white']['button-primary'] );
		$this->assertSame( '#78a9ff', $palette['g100']['link-primary'] );
		// Far more than the 31 the old typed list had.
		$this->assertGreaterThan( 300, count( $palette['white'] ) );
	}

	/** A token that points at another takes that token's value. */
	public function test_an_alias_takes_the_value_it_points_to(): void {
		$palette = ( new Carbon() )->get_resolved_palette();

		$this->assertSame( $palette['white']['layer-01'], $palette['white']['layer'] );
		$this->assertSame( $palette['g90']['border-strong-01'], $palette['g90']['border-strong'] );
	}

	/** See-through values stay as the CSS writes them. */
	public function test_a_see_through_value_is_kept_as_rgba(): void {
		$palette = ( new Carbon() )->get_resolved_palette();

		$this->assertMatchesRegularExpression( '/^rgba\(141, 141, 141, 0\.\d+\)$/', $palette['white']['background-hover'] );
	}

	/** Every pair names colors each theme has, with a known level. */
	public function test_every_pair_names_tokens_the_palette_has(): void {
		$carbon  = new Carbon();
		$palette = $carbon->get_resolved_palette();
		foreach ( $carbon->get_role_map() as $front => $meta ) {
			$this->assertNotEmpty( $meta['role'], $front );
			foreach ( $meta['pairings'] as $pairing ) {
				$this->assertContains( $pairing['threshold'], array( 'text', 'ui', 'info' ), $front );
				foreach ( $palette as $scope => $tokens ) {
					$this->assertArrayHasKey( $front, $tokens, "$scope: $front" );
					$this->assertArrayHasKey( $pairing['against'], $tokens, "$scope: $front on {$pairing['against']}" );
					if ( isset( $pairing['over'] ) ) {
						$this->assertArrayHasKey( $pairing['over'], $tokens, "$scope: under {$pairing['against']}" );
					}
				}
			}
		}
	}

	/** Text on hover, pressed and selected states is checked too. */
	public function test_states_are_checked(): void {
		$against = array_column( ( new Carbon() )->get_role_map()['text-primary']['pairings'], 'against' );

		foreach ( array( 'layer-hover-01', 'layer-active-01', 'layer-selected-01', 'field-hover-01', 'background-hover' ) as $state ) {
			$this->assertContains( $state, $against );
		}
	}
}
