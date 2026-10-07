<?php
/**
 * AWT Settings → Carbon → Colors, with and without code that edits colors.
 *
 * Free AWT shows a chooser that points at Custom CSS. Code hooked to
 * `awt_settings_colors_section` (AWT Premium's brand colors) gets a form in
 * its place, and `awt_settings_save_colors` when it is saved, like the
 * Header widgets row.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

/**
 * The Colors sub-tab.
 *
 * @covers \AWT\Theme\AdminPage\render_tab_appearance
 * @covers \AWT\Theme\AdminPage\save_tab_appearance
 */
class Test_Colors_Section extends WP_UnitTestCase {

	/**
	 * Remove the stand-in editor and the request.
	 */
	public function tear_down(): void {
		remove_all_actions( 'awt_settings_colors_section' );
		remove_all_actions( 'awt_settings_save_colors' );
		unset( $_GET['section'], $_POST['awt_section'] );
		parent::tear_down();
	}

	/**
	 * The Colors sub-tab as the page prints it.
	 */
	private function render(): string {
		$_GET['section'] = 'colors';
		ob_start();
		\AWT\Theme\AdminPage\render_tab_appearance();
		return (string) ob_get_clean();
	}

	/** Free AWT alone: the chooser, and no form. */
	public function test_free_shows_the_chooser(): void {
		$html = $this->render();

		$this->assertStringContainsString( 'name="awt_color_method"', $html );
		$this->assertStringNotContainsString( 'name="awt_section" value="colors"', $html );
	}

	/** Hooked code prints its fields inside a form that saves the sub-tab. */
	public function test_hooked_code_gets_a_form(): void {
		add_action(
			'awt_settings_colors_section',
			static function (): void {
				echo '<input name="awt-test-color" />';
			}
		);
		$html = $this->render();

		$this->assertStringNotContainsString( 'name="awt_color_method"', $html );
		$this->assertStringContainsString( 'name="awt_section" value="colors"', $html );
		$this->assertStringContainsString( 'name="_awt_nonce"', $html );
		$this->assertMatchesRegularExpression( '/<form[^>]*>.*<input name="awt-test-color" \/>.*<\/form>/s', $html );
	}

	/** Saving the sub-tab tells the hooked code. */
	public function test_saving_fires_the_save_action(): void {
		$saved = 0;
		add_action(
			'awt_settings_save_colors',
			static function () use ( &$saved ): void {
				++$saved;
			}
		);
		$_POST['awt_section'] = 'colors';
		\AWT\Theme\AdminPage\save_tab_appearance();

		$this->assertSame( 1, $saved );
	}
}
