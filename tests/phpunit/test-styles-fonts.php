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
	 * The Code block's font becomes the font for all code. Its own default,
	 * IBM Plex Mono, prints nothing.
	 */
	public function test_code_block_font_sets_the_mono_variable(): void {
		$this->save_styles( array( 'styles' => array( 'blocks' => array( 'core/code' => array( 'typography' => array( 'fontFamily' => 'var:preset|font-family|plex-serif' ) ) ) ) ) );
		$this->assertStringContainsString( "--awt-font-mono:'IBM Plex Serif', Georgia", \AWT\Theme\styles_font_css() );

		$this->save_styles( array( 'styles' => array( 'blocks' => array( 'core/code' => array( 'typography' => array( 'fontFamily' => 'var:preset|font-family|plex-mono' ) ) ) ) ) );
		$this->assertSame( '', \AWT\Theme\styles_font_css() );
	}

	/**
	 * The preloads the page head gets.
	 */
	private function preloads(): string {
		ob_start();
		\AWT\Theme\styles_font_preloads();
		return (string) ob_get_clean();
	}

	/**
	 * A site that kept IBM Plex preloads nothing.
	 */
	public function test_no_choice_preloads_nothing(): void {
		$this->assertSame( '', $this->preloads() );
	}

	/**
	 * An added font for text and headings: its regular face is preloaded,
	 * once, and not its italic or bold.
	 */
	public function test_chosen_font_regular_face_is_preloaded_once(): void {
		$this->save_styles(
			array(
				'settings' => array(
					'typography' => array(
						'fontFamilies' => array(
							'custom' => array(
								array(
									'slug'       => 'inter',
									'name'       => 'Inter',
									'fontFamily' => 'Inter, sans-serif',
									'fontFace'   => array(
										array(
											'fontFamily' => 'Inter',
											'fontStyle'  => 'italic',
											'fontWeight' => '400',
											'src'        => 'https://example.org/fonts/inter-italic.woff2',
										),
										array(
											'fontFamily' => 'Inter',
											'fontStyle'  => 'normal',
											'fontWeight' => '700',
											'src'        => 'https://example.org/fonts/inter-700.woff2',
										),
										array(
											'fontFamily' => 'Inter',
											'fontStyle'  => 'normal',
											'fontWeight' => '400',
											'src'        => array( 'https://example.org/fonts/inter-400.ttf', 'https://example.org/fonts/inter-400.woff2' ),
										),
									),
								),
							),
						),
					),
				),
				'styles'   => array(
					'typography' => array( 'fontFamily' => 'var:preset|font-family|inter' ),
					'elements'   => array(
						'heading' => array( 'typography' => array( 'fontFamily' => 'var:preset|font-family|inter' ) ),
					),
				),
			)
		);

		$html = $this->preloads();
		$this->assertSame( 1, substr_count( $html, '<link rel="preload"' ) );
		$this->assertStringContainsString( 'href="https://example.org/fonts/inter-400.woff2" as="font" type="font/woff2" crossorigin', $html );
	}

	/**
	 * Headings set to IBM Plex Serif under another text font: the theme's own
	 * face is preloaded by its address.
	 */
	public function test_theme_font_for_headings_is_preloaded(): void {
		$this->save_styles( array( 'styles' => array( 'elements' => array( 'heading' => array( 'typography' => array( 'fontFamily' => 'var:preset|font-family|plex-serif' ) ) ) ) ) );

		$this->assertStringContainsString( '/assets/fonts/IBMPlexSerif-Light.woff2"', $this->preloads() );
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
