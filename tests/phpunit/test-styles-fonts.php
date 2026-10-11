<?php
/**
 * Fonts chosen in Styles reach the page (awt-theme #3).
 *
 * WordPress printed the choices and the theme's stylesheets overrode every one
 * of them, so a site that picked a font in Styles kept IBM Plex Sans.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

/**
 * The CSS styles_font_css() writes for each kind of choice, and that it is
 * attached to the theme's stylesheet.
 *
 * @covers \AWT\Theme\styles_font_css
 */
class Test_Styles_Fonts extends WP_UnitTestCase {

	/**
	 * Save a Styles choice the way the Site Editor does.
	 *
	 * @param array $data The user theme.json data.
	 */
	private function save_styles( array $data ): void {
		$post = WP_Theme_JSON_Resolver::get_user_data_from_wp_global_styles( wp_get_theme(), true );
		$data = array_merge(
			array(
				'version'                     => 3,
				'isGlobalStylesUserThemeJSON' => true,
			),
			$data
		);
		wp_update_post(
			wp_slash(
				array(
					'ID'           => $post['ID'],
					'post_content' => wp_json_encode( $data ),
				)
			)
		);
		wp_clean_theme_json_cache();
	}

	/**
	 * Save as an administrator, the person who can open Styles. With no user,
	 * WordPress drops the record's link to the theme without a word and then
	 * never finds it, so every choice reads as none.
	 */
	public function set_up(): void {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Leave no Styles choice behind for the next test.
	 */
	public function tear_down(): void {
		$this->save_styles( array() );
		parent::tear_down();
	}

	/**
	 * A site that chose nothing gets no new CSS, not even WordPress's own
	 * `inherit` for buttons.
	 */
	public function test_no_choice_emits_nothing(): void {
		$this->assertSame( '', \AWT\Theme\styles_font_css() );
	}

	/**
	 * Text set to IBM Plex Sans is the default. Writing it out would also make
	 * --awt-font-sans point at a preset that reads --awt-font-sans.
	 */
	public function test_plex_sans_text_emits_nothing(): void {
		$this->save_styles( array( 'styles' => array( 'typography' => array( 'fontFamily' => 'var:preset|font-family|plex-sans' ) ) ) );

		$this->assertSame( '', \AWT\Theme\styles_font_css() );
	}

	/**
	 * Text and headings become the font variables, written as stacks.
	 */
	public function test_text_and_headings_set_the_variables(): void {
		$this->save_styles(
			array(
				'styles' => array(
					'typography' => array( 'fontFamily' => 'var:preset|font-family|plex-serif' ),
					'elements'   => array(
						'heading' => array( 'typography' => array( 'fontFamily' => 'var:preset|font-family|plex-mono' ) ),
					),
				),
			)
		);

		$css = \AWT\Theme\styles_font_css();
		$this->assertStringStartsWith( ':root{', $css );
		$this->assertStringContainsString( "--awt-font-sans:'IBM Plex Serif', Georgia", $css );
		$this->assertStringContainsString( "--awt-font-headings:'IBM Plex Mono', ui-monospace", $css );
		$this->assertStringNotContainsString( 'var(', $css );
	}

	/**
	 * A font added to the site, one heading level and links.
	 */
	public function test_added_font_and_element_choices(): void {
		$this->save_styles(
			array(
				'settings' => array(
					'typography' => array(
						'fontFamilies' => array(
							'custom' => array(
								array(
									'slug'       => 'courier',
									'name'       => 'Courier',
									'fontFamily' => '"Courier New", monospace',
								),
							),
						),
					),
				),
				'styles'   => array(
					'typography' => array( 'fontFamily' => 'var:preset|font-family|courier' ),
					'elements'   => array(
						'h1'   => array( 'typography' => array( 'fontFamily' => 'var:preset|font-family|plex-serif' ) ),
						'link' => array( 'typography' => array( 'fontFamily' => 'var:preset|font-family|courier' ) ),
					),
				),
			)
		);

		$css = \AWT\Theme\styles_font_css();
		$this->assertStringContainsString( ':root{--awt-font-sans:"Courier New", monospace;}', $css );
		$this->assertStringContainsString( "h1{font-family:'IBM Plex Serif', Georgia", $css );
		$this->assertStringContainsString( 'a:where(:not(.wp-element-button)){font-family:"Courier New", monospace}', $css );
	}

	/**
	 * A font that no longer exists, and a value that could end the rule, are
	 * left out.
	 */
	public function test_unknown_and_unsafe_values_are_dropped(): void {
		$this->save_styles(
			array(
				'styles' => array(
					'typography' => array( 'fontFamily' => 'var:preset|font-family|deleted-font' ),
					'elements'   => array(
						'heading' => array( 'typography' => array( 'fontFamily' => 'Arial}</style><script>' ) ),
					),
				),
			)
		);

		$this->assertSame( '', \AWT\Theme\styles_font_css() );
	}

	/**
	 * And it is attached to the theme's stylesheet, after theme.css.
	 */
	public function test_the_fonts_are_enqueued_with_the_theme_stylesheet(): void {
		$this->save_styles( array( 'styles' => array( 'typography' => array( 'fontFamily' => 'var:preset|font-family|plex-serif' ) ) ) );

		do_action( 'wp_enqueue_scripts' );

		$after = wp_styles()->get_data( 'awt-theme', 'after' );
		$this->assertIsArray( $after, 'the theme stylesheet should carry inline CSS' );
		$this->assertStringContainsString( "--awt-font-sans:'IBM Plex Serif'", implode( '', $after ) );
	}
}
