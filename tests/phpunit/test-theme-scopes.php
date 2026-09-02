<?php
/**
 * Theme scopes and the editor canvas.
 *
 * `theme_scopes()` turns a style-variation slug into the light/dark Carbon
 * scope pair the whole theme keys off, and `editor_scope_css()` decides which
 * of that pair the block editor previews. Both are new-ish, both are pure, and
 * the editor one shipped on 2026-09-02 to fix a defect where a site pinned to
 * dark previewed unscoped body copy in the light scheme.
 *
 * The browser gates cannot see this: they read a rendered page, and these
 * functions decide what gets rendered in the first place.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\Settings;

/**
 * Scope resolution and the CSS the editor canvas is given.
 *
 * @covers \AWT\Theme\theme_scopes
 * @covers \AWT\Theme\editor_scope_tokens
 * @covers \AWT\Theme\editor_scope_css
 */
class Test_Theme_Scopes extends WP_UnitTestCase {

	/**
	 * Start every test from a cold settings cache.
	 */
	public function set_up(): void {
		parent::set_up();
		Settings\flush_cache();
	}

	/**
	 * Leave no settings row behind for the next test.
	 */
	public function tear_down(): void {
		delete_option( Settings\OPTION_KEY );
		Settings\flush_cache();
		parent::tear_down();
	}

	/**
	 * Set a variation and colour scheme without going through save(), so the
	 * test exercises the resolver rather than the admin screens.
	 *
	 * @param string $variation Style-variation slug.
	 * @param string $scheme    One of `default`, `light`, `dark`.
	 */
	private function configure( string $variation, string $scheme ): void {
		update_option(
			Settings\OPTION_KEY,
			wp_json_encode(
				array(
					'welcome' => array( 'choices' => array( 'styleVariation' => $variation ) ),
					'site'    => array( 'colorScheme' => $scheme ),
				)
			)
		);
		Settings\flush_cache();
	}

	/* ---------------------------------------------------------- theme_scopes */

	/** With nothing chosen, the pair is Carbon's white + g100. */
	public function test_scopes_default_to_white_and_g100(): void {
		$this->assertSame(
			array(
				'light' => 'white',
				'dark'  => 'g100',
			),
			AWT\Theme\theme_scopes()
		);
	}

	/** Every shipped variation slug resolves to its own pair. */
	public function test_each_shipped_variation_resolves_to_its_pair(): void {
		$cases = array(
			'white-plus-g100' => array( 'white', 'g100' ),
			'white-plus-g90'  => array( 'white', 'g90' ),
			'g10-plus-g100'   => array( 'g10', 'g100' ),
			'g10-plus-g90'    => array( 'g10', 'g90' ),
		);

		foreach ( $cases as $slug => list( $light, $dark ) ) {
			$this->configure( $slug, 'default' );
			$this->assertSame(
				array(
					'light' => $light,
					'dark'  => $dark,
				),
				AWT\Theme\theme_scopes(),
				"variation {$slug}"
			);
		}
	}

	/**
	 * A slug naming a scope that is not one of Carbon's four falls back rather
	 * than emitting `.cds--nonsense`, which would match nothing and leave the
	 * canvas with no tokens at all.
	 */
	public function test_an_unknown_scope_in_the_slug_falls_back(): void {
		$this->configure( 'chartreuse-plus-vantablack', 'default' );

		$this->assertSame(
			array(
				'light' => 'white',
				'dark'  => 'g100',
			),
			AWT\Theme\theme_scopes()
		);
	}

	/** A slug with no `-plus-` separator is not a pairing and is ignored. */
	public function test_a_slug_without_a_pairing_is_ignored(): void {
		$this->configure( 'just-one-thing', 'default' );

		$this->assertSame(
			array(
				'light' => 'white',
				'dark'  => 'g100',
			),
			AWT\Theme\theme_scopes()
		);
	}

	/* --------------------------------------------------- editor_scope_tokens */

	/** Each scope's tokens come out of the compiled Carbon file we already ship. */
	public function test_tokens_are_read_for_every_carbon_scope(): void {
		foreach ( array( 'white', 'g10', 'g90', 'g100' ) as $scope ) {
			$css = AWT\Theme\editor_scope_tokens( $scope );

			$this->assertStringStartsWith( 'body.editor-styles-wrapper{', $css, $scope );
			$this->assertStringContainsString( '--cds-background:', $css, $scope );
		}
	}

	/** White and g100 are opposites; if they ever read the same, the extraction broke. */
	public function test_light_and_dark_tokens_actually_differ(): void {
		$this->assertNotSame(
			AWT\Theme\editor_scope_tokens( 'white' ),
			AWT\Theme\editor_scope_tokens( 'g100' )
		);
	}

	/**
	 * `.cds--g10` must not match inside `.cds--g100`. The extraction guards
	 * both ends of the token for exactly this, and getting it wrong would give
	 * a g10 site g100's colours.
	 */
	public function test_g10_does_not_match_inside_g100(): void {
		$this->assertNotSame(
			AWT\Theme\editor_scope_tokens( 'g10' ),
			AWT\Theme\editor_scope_tokens( 'g100' )
		);
	}

	/** Anything that is not a Carbon scope returns nothing, rather than guessing. */
	public function test_an_unknown_scope_yields_no_tokens(): void {
		$this->assertSame( '', AWT\Theme\editor_scope_tokens( 'not-a-scope' ) );
	}

	/* ------------------------------------------------------ editor_scope_css */

	/** Pinned dark: the canvas gets the dark scope, with no media query. */
	public function test_a_dark_site_gives_the_canvas_the_dark_scope(): void {
		$this->configure( 'white-plus-g100', 'dark' );
		$css = AWT\Theme\editor_scope_css();

		$this->assertSame( AWT\Theme\editor_scope_tokens( 'g100' ), $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}

	/** Pinned light: the light scope, and again no media query. */
	public function test_a_light_site_gives_the_canvas_the_light_scope(): void {
		$this->configure( 'white-plus-g100', 'light' );
		$css = AWT\Theme\editor_scope_css();

		$this->assertSame( AWT\Theme\editor_scope_tokens( 'white' ), $css );
		$this->assertStringNotContainsString( 'prefers-color-scheme', $css );
	}

	/**
	 * Following the visitor's device means the author is a visitor too: light
	 * by default, dark behind `prefers-color-scheme`, so an author on a dark
	 * desktop previews what they would themselves see on the site.
	 */
	public function test_a_default_site_carries_both_scopes(): void {
		$this->configure( 'white-plus-g100', 'default' );
		$css = AWT\Theme\editor_scope_css();

		$this->assertStringStartsWith( AWT\Theme\editor_scope_tokens( 'white' ), $css );
		$this->assertStringContainsString( '@media (prefers-color-scheme: dark){', $css );
		$this->assertStringContainsString( AWT\Theme\editor_scope_tokens( 'g100' ), $css );
	}

	/**
	 * The pair comes from the variation, not from hardcoded white/g100 — the
	 * thing that had to be proved by hand across two variations before this
	 * test existed.
	 */
	public function test_the_canvas_follows_the_chosen_variation(): void {
		$this->configure( 'g10-plus-g90', 'dark' );

		$this->assertSame( AWT\Theme\editor_scope_tokens( 'g90' ), AWT\Theme\editor_scope_css() );
	}
}
