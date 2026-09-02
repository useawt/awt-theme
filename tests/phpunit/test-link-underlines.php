<?php
/**
 * D6: links are underlined, with a switch per region.
 *
 * Two functions express one decision — body classes for the front end, CSS for
 * the block-editor canvas — and they have to agree. A header or footer block is
 * edited in the Site Editor, where the canvas is the only preview an author
 * gets, so a disagreement means an author underlines a region and sees nothing,
 * or the reverse.
 *
 * The two are written independently off two different maps, which is exactly
 * the shape that drifts.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\Settings;

/**
 * D6 underline resolvers, front end and canvas.
 *
 * @covers \AWT\Theme\Settings\link_underline_body_classes
 * @covers \AWT\Theme\Settings\link_underline_editor_css
 */
class Test_Link_Underlines extends WP_UnitTestCase {

	/**
	 * Leave no settings row behind for the next test.
	 */
	public function tear_down(): void {
		delete_option( Settings\OPTION_KEY );
		Settings\flush_cache();
		parent::tear_down();
	}

	/**
	 * Write a `links.underline` sub-document straight to the option.
	 *
	 * @param array<string, bool> $underline The `links.underline` sub-document.
	 */
	private function configure( array $underline ): void {
		update_option(
			Settings\OPTION_KEY,
			wp_json_encode( array( 'links' => array( 'underline' => $underline ) ) )
		);
		Settings\flush_cache();
	}

	/** The master switch off means no class and no CSS, whatever the regions say. */
	public function test_the_master_switch_off_silences_every_region(): void {
		$this->configure(
			array(
				'all'    => false,
				'main'   => true,
				'header' => true,
				'footer' => true,
			)
		);

		$this->assertSame( array(), Settings\link_underline_body_classes() );
		$this->assertSame( '', Settings\link_underline_editor_css() );
	}

	/** On, with regions chosen, emits a class per region and nothing else. */
	public function test_only_the_chosen_regions_are_emitted(): void {
		$this->configure(
			array(
				'all'         => true,
				'main'        => true,
				'header'      => false,
				'sideNav'     => false,
				'breadcrumbs' => false,
				'footer'      => true,
			)
		);

		$classes = Settings\link_underline_body_classes();

		$this->assertContains( 'awt-underline-main', $classes );
		$this->assertContains( 'awt-underline-footer', $classes );
		$this->assertNotContains( 'awt-underline-header', $classes );
		$this->assertCount( 2, $classes );
	}

	/**
	 * The canvas CSS has to cover the same regions as the body classes. This is
	 * the assertion that catches drift between the two maps: same settings,
	 * same number of regions expressed.
	 */
	public function test_the_canvas_covers_the_same_regions_as_the_front_end(): void {
		$regions = array_keys( Settings\LINK_UNDERLINE_CLASSES );

		foreach ( $regions as $region ) {
			// Every region has to be named explicitly: defaults are merged
			// underneath a partial payload, and they are all `true`, so writing
			// one region on would leave the other four on as well.
			$config = array( 'all' => true );
			foreach ( $regions as $each ) {
				$config[ $each ] = ( $each === $region );
			}
			$this->configure( $config );

			$classes = Settings\link_underline_body_classes();
			$css     = Settings\link_underline_editor_css();

			$this->assertCount( 1, $classes, "front end, region {$region}" );
			$this->assertSame(
				1,
				substr_count( $css, '--awt-underline: underline;' ),
				"canvas, region {$region}"
			);
		}
	}

	/** Every rule is rooted on the canvas body, the one selector always present there. */
	public function test_every_canvas_rule_is_rooted_on_the_canvas_body(): void {
		$this->configure(
			array(
				'all'         => true,
				'main'        => true,
				'header'      => true,
				'sideNav'     => true,
				'breadcrumbs' => true,
				'footer'      => true,
			)
		);

		foreach ( explode( "\n", Settings\link_underline_editor_css() ) as $rule ) {
			$this->assertStringStartsWith( 'body.editor-styles-wrapper', $rule );
		}
	}

	/** All five regions on produces five rules, not four and not six. */
	public function test_all_regions_on_produces_one_rule_each(): void {
		$this->configure(
			array(
				'all'         => true,
				'main'        => true,
				'header'      => true,
				'sideNav'     => true,
				'breadcrumbs' => true,
				'footer'      => true,
			)
		);

		$this->assertCount( 5, Settings\link_underline_body_classes() );
		$this->assertCount( 5, explode( "\n", Settings\link_underline_editor_css() ) );
	}
}
