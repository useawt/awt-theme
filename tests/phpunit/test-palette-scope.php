<?php
/**
 * Palette colors follow the light or dark theme they sit in.
 *
 * Each palette color is pointed at its Carbon token on the scope classes, so a
 * picked color is the dark value on a dark page or in a dark section
 * (awt-workspace #24). A color the site owner set themselves, in the Site
 * Editor or in CSS, keeps that value: the scope classes would otherwise hide
 * it, because they sit closer to the content than `:root`, where WordPress
 * prints the owner's value.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

/**
 * The rule the page and the canvas get.
 *
 * @covers \AWT\Theme\palette_scope_declarations
 * @covers \AWT\Theme\palette_scope_css
 * @covers \AWT\Theme\editor_palette_scope_css
 */
class Test_Palette_Scope extends WP_UnitTestCase {

	/** What the owner picked in the Site Editor for "Text secondary". */
	private const EDITED = '#8a3ffc';

	/**
	 * Remove the stand-ins and clear WordPress's cached theme.json data.
	 */
	public function tear_down(): void {
		remove_filter( 'wp_theme_json_data_user', array( $this, 'edit_text_secondary' ) );
		\AWT\Theme\Settings\set( 'customCss', '' );
		$this->clean_theme_json();
		parent::tear_down();
	}

	/** Forget the merged theme.json, so the next read sees the filters. */
	private function clean_theme_json(): void {
		WP_Theme_JSON_Resolver::clean_cached_data();
		if ( function_exists( 'wp_clean_theme_json_cache' ) ) {
			wp_clean_theme_json_cache();
		}
	}

	/**
	 * A Site Editor edit of one theme palette color, as WordPress stores it.
	 *
	 * @param WP_Theme_JSON_Data $data The user's global styles.
	 */
	public function edit_text_secondary( $data ) {
		$palette = WP_Theme_JSON_Resolver::get_theme_data()->get_settings()['color']['palette']['theme'];
		foreach ( $palette as $i => $entry ) {
			if ( 'text-secondary' === $entry['slug'] ) {
				$palette[ $i ]['color'] = self::EDITED;
			}
		}
		return $data->update_with(
			array(
				'version'  => 3,
				'settings' => array( 'color' => array( 'palette' => array( 'theme' => $palette ) ) ),
			)
		);
	}

	/** Every palette color is pointed at its token, on the scope classes only. */
	public function test_every_palette_color_follows_its_token(): void {
		$css     = \AWT\Theme\palette_scope_css();
		$palette = WP_Theme_JSON_Resolver::get_theme_data()->get_settings()['color']['palette']['theme'];

		$this->assertStringStartsWith( '.cds--white,.cds--g10,.cds--g90,.cds--g100{', $css );
		$this->assertStringNotContainsString( ':root', $css );
		foreach ( $palette as $entry ) {
			$this->assertStringContainsString(
				'--wp--preset--color--' . $entry['slug'] . ':var(--cds-' . $entry['slug'] . ');',
				$css,
				$entry['slug'] . ' should follow its token'
			);
		}
	}

	/**
	 * The canvas body carries no scope class, so its rule names the body too.
	 */
	public function test_the_canvas_rule_includes_its_body(): void {
		$css = \AWT\Theme\editor_palette_scope_css();

		$this->assertStringStartsWith( 'body.editor-styles-wrapper,.cds--white,.cds--g10,.cds--g90,.cds--g100{', $css );
		$this->assertStringContainsString( '--wp--preset--color--text-secondary:var(--cds-text-secondary);', $css );
	}

	/** A color the owner changed in the Site Editor keeps the owner's value. */
	public function test_a_color_edited_in_the_site_editor_is_left_alone(): void {
		add_filter( 'wp_theme_json_data_user', array( $this, 'edit_text_secondary' ) );
		$this->clean_theme_json();

		$css = \AWT\Theme\palette_scope_css();

		$this->assertStringNotContainsString( '--wp--preset--color--text-secondary:', $css );
		$this->assertStringContainsString( '--wp--preset--color--text-primary:var(--cds-text-primary);', $css );
	}

	/** A color set in Custom CSS keeps that value, whatever selector sets it. */
	public function test_a_color_set_in_custom_css_is_left_alone(): void {
		\AWT\Theme\Settings\set( 'customCss', ":root {\n\t--wp--preset--color--layer-01 : #fdf6e3;\n}" );

		$css = \AWT\Theme\palette_scope_css();

		$this->assertStringNotContainsString( '--wp--preset--color--layer-01:', $css );
		$this->assertStringContainsString( '--wp--preset--color--layer-02:var(--cds-layer-02);', $css );
	}

	/** Custom CSS that only reads a palette color changes nothing. */
	public function test_custom_css_that_reads_a_color_does_not_count(): void {
		\AWT\Theme\Settings\set( 'customCss', '.x { color: var(--wp--preset--color--layer-01); }' );

		$this->assertStringContainsString( '--wp--preset--color--layer-01:var(--cds-layer-01);', \AWT\Theme\palette_scope_css() );
	}

	/** The page carries the rule after Carbon's tokens. */
	public function test_the_page_prints_it(): void {
		$this->go_to( home_url( '/' ) );
		do_action( 'wp_enqueue_scripts' );

		$inline = wp_styles()->get_data( 'awt-theme-carbon', 'after' );

		$this->assertIsArray( $inline );
		$this->assertContains( \AWT\Theme\palette_scope_css(), $inline );
	}
}
