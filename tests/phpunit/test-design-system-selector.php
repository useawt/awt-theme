<?php
/**
 * The Design system selector, and the promise it must not break.
 *
 * The tab shows the whole catalogue — Carbon plus one locked placeholder per
 * system reserved for AWT Premium. The locked ones are advertising, not
 * working code: they render nothing, support no components, and must never
 * become the site's active system. A site whose active system supported no
 * components would show an empty block inserter and unstyled blocks, so the
 * clamp below is the load-bearing part, not the markup.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\DesignSystem\Registry;

/**
 * The catalogue, the clamp, and what the tab renders.
 *
 * @covers \AWT\Theme\DesignSystem\Registry
 * @covers \AWT\Theme\AdminPage\render_tab_design_system
 */
class Test_Design_System_Selector extends WP_UnitTestCase {

	/**
	 * Put the setting back, so a test that changes it cannot leak into the next.
	 */
	public function tear_down(): void {
		\AWT\Theme\Settings\set( 'designSystem.slug', 'carbon' );
		parent::tear_down();
	}

	/**
	 * The catalogue carries every system the selector offers.
	 */
	public function test_catalogue_holds_carbon_and_the_locked_placeholders(): void {
		$slugs = array_keys( Registry::all() );
		$this->assertContains( 'carbon', $slugs );
		foreach ( array( 'uswds', 'bootstrap', 'govuk', 'ecl', 'more' ) as $locked ) {
			$this->assertContains( $locked, $slugs, "$locked is missing from the selector catalogue" );
		}
	}

	/**
	 * Only Carbon is selectable. Everything else reports itself locked.
	 */
	public function test_only_carbon_is_available(): void {
		$this->assertSame( array( 'carbon' ), array_keys( Registry::available() ) );
		foreach ( Registry::all() as $slug => $system ) {
			$this->assertSame( 'carbon' === $slug, $system->is_available(), "$slug reports the wrong availability" );
		}
	}

	/**
	 * A locked system never becomes active, however the slug gets in.
	 */
	public function test_a_locked_slug_snaps_back_to_carbon(): void {
		\AWT\Theme\Settings\set( 'designSystem.slug', 'bootstrap' );
		$this->assertSame( 'carbon', \AWT\Theme\Settings\get( 'designSystem.slug' ) );
		$this->assertSame( 'carbon', Registry::get_active()->slug() );
	}

	/**
	 * An unknown slug is no different from a locked one.
	 */
	public function test_an_unknown_slug_snaps_back_to_carbon(): void {
		\AWT\Theme\Settings\set( 'designSystem.slug', 'not-a-design-system' );
		$this->assertSame( 'carbon', \AWT\Theme\Settings\get( 'designSystem.slug' ) );
	}

	/**
	 * A locked placeholder renders nothing and claims nothing, so it could
	 * not style a site even if it somehow became active.
	 */
	public function test_a_locked_placeholder_carries_no_data(): void {
		$locked = Registry::all()['uswds'];
		$this->assertSame( array(), $locked->supported_components() );
		$this->assertSame( '', $locked->classes_for( 'button' ) );
		$this->assertSame( array(), $locked->get_palette() );
		$this->assertSame( array(), $locked->get_style_variations() );
	}

	/**
	 * The upgrade link points at the site we actually run.
	 */
	public function test_locked_tiles_link_to_the_current_domain(): void {
		$url = Registry::all()['uswds']->premium_url();
		$this->assertSame( 'https://useawt.com/premium', $url );
		$this->assertNull( Registry::all()['carbon']->premium_url(), 'Carbon is not a placeholder and needs no upgrade link' );
	}

	/**
	 * The catch-all tile asks for a design system through the contact page.
	 *
	 * It used to print an email address as plain text. A link is one click
	 * rather than a copy-and-paste, and it sits OUTSIDE the tile's <label> —
	 * a label names its control, so a link inside one both lengthens the
	 * radio's accessible name and gives the tile two things to click.
	 */
	public function test_the_catch_all_tile_links_to_the_contact_page(): void {
		$this->assertSame(
			'https://useawt.com/contact/',
			Registry::all()['more']->request_url()
		);
		$this->assertSame(
			'',
			Registry::all()['uswds']->request_url(),
			'only the catch-all tile asks for a system'
		);

		ob_start();
		\AWT\Theme\AdminPage\render_tab_design_system();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'https://useawt.com/contact/', $html );
		$this->assertStringNotContainsString( 'hello@useawt.com', $html );

		// A locked tile is not dimmed. Opacity blended every line on it below
		// 4.5:1, and the only reason the contrast gate stayed quiet was that
		// text inside a disabled control's label is exempt from it.
		$this->assertStringNotContainsString( 'opacity:', $html );

		// The link must not be inside a label. Every <label> the tab renders
		// has to close before the first <a> that follows it.
		$labels = preg_split( '/<label\b/', $html );
		array_shift( $labels );
		foreach ( $labels as $index => $after ) {
			$close = strpos( $after, '</label>' );
			$this->assertNotFalse( $close, "label $index never closes" );
			$this->assertStringNotContainsString(
				'<a ',
				substr( $after, 0, $close ),
				"label $index contains a link"
			);
		}
	}

	/**
	 * The tab offers one radio per system, with the locked ones disabled and
	 * labelled, and Carbon pre-selected.
	 */
	public function test_the_tab_renders_the_catalogue(): void {
		ob_start();
		\AWT\Theme\AdminPage\render_tab_design_system();
		$html = (string) ob_get_clean();

		$this->assertSame(
			count( Registry::all() ),
			substr_count( $html, 'name="designSystem"' ),
			'one radio per registered system'
		);
		$this->assertSame(
			count( Registry::all() ) - 1,
			substr_count( $html, 'disabled aria-disabled="true"' ),
			'every system except Carbon is disabled'
		);
		$this->assertStringContainsString( 'value="carbon"', $html );
		$this->assertStringContainsString( "checked='checked'", $html );
		$this->assertSame( 5, substr_count( $html, 'Coming soon to AWT Premium.' ) );
		$this->assertStringNotContainsString( 'accessiblewordpresstheme.com', $html );
	}
}
