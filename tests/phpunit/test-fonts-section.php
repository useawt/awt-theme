<?php
/**
 * The Fonts hooks on the Typography sub-tab.
 *
 * Free AWT prints a sentence that points at Styles. Code hooked to
 * `awt_settings_fonts_section` (AWT Premium's font picker) prints at the end
 * of the Typography form instead, and gets `awt_settings_save_fonts` when it
 * is saved, like the Colors sub-tab.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

/**
 * The Typography sub-tab's Fonts hooks.
 *
 * @covers \AWT\Theme\AdminPage\render_tab_typography
 * @covers \AWT\Theme\AdminPage\save_tab_typography
 */
class Test_Fonts_Section extends WP_UnitTestCase {

	/**
	 * Remove the stand-in picker and the request.
	 */
	public function tear_down(): void {
		remove_all_actions( 'awt_settings_fonts_section' );
		remove_all_actions( 'awt_settings_save_fonts' );
		unset( $_POST['awt_section'], $_POST['typography'] );
		parent::tear_down();
	}

	/**
	 * The Typography sub-tab as it prints.
	 *
	 * @param string $context 'settings' or 'wizard'.
	 */
	private function render( string $context = 'settings' ): string {
		ob_start();
		\AWT\Theme\AdminPage\render_tab_typography( $context );
		return (string) ob_get_clean();
	}

	/** Free AWT alone: the sentence that points at Styles. */
	public function test_free_shows_the_sentence(): void {
		$html = $this->render();
		$this->assertStringContainsString( 'To use other fonts, go to <a href=', $html );
		$this->assertStringContainsString( 'site-editor.php?path=/wp_global_styles&#038;section=/typography', $html );
	}

	/** Hooked code prints last, in place of the sentence, and is told where. */
	public function test_hooked_code_prints_at_the_end(): void {
		add_action(
			'awt_settings_fonts_section',
			static function ( string $context ): void {
				echo '<input name="awt-test-font" data-context="' . esc_attr( $context ) . '" />';
			}
		);
		$html = $this->render();

		$this->assertStringNotContainsString( 'To use other fonts', $html );
		$this->assertStringContainsString( 'data-context="settings"', $html );
		$this->assertStringEndsWith( '<input name="awt-test-font" data-context="settings" />', trim( $html ) );
		$this->assertStringContainsString( 'data-context="wizard"', $this->render( 'wizard' ) );
	}

	/** Saving the sub-tab tells the hooked code, and from where. */
	public function test_saving_fires_the_save_action(): void {
		$seen = array();
		add_action(
			'awt_settings_save_fonts',
			static function ( string $context ) use ( &$seen ): void {
				$seen[] = $context;
			}
		);
		$_POST['awt_section'] = 'typography';
		\AWT\Theme\AdminPage\save_tab_appearance();
		\AWT\Theme\AdminPage\save_tab_typography( 'wizard' );

		$this->assertSame( array( 'settings', 'wizard' ), $seen );
	}
}
