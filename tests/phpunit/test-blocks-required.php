<?php
/**
 * The notice that says the AWT Blocks plugin is missing or turned off.
 *
 * The theme and the plugin are one product in two files. Without the plugin
 * every AWT block in every page stops rendering, while the theme still
 * activates and the site still loads — so the failure is silent, and this
 * notice is the only thing that says what is wrong.
 *
 * The states are what the tests are about. "Installed but off" and "not
 * installed at all" need different things done to them, so they must not
 * share a message, and neither may be shown to someone whose account cannot
 * do that thing.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\BlocksRequired;

/**
 * The wording each state is given, and who is given it.
 *
 * @covers \AWT\Theme\BlocksRequired\notice_html
 */
class Test_Blocks_Required extends WP_UnitTestCase {

	/**
	 * Nothing is said while the plugin is doing its job.
	 */
	public function test_an_active_plugin_says_nothing(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->assertSame( '', BlocksRequired\notice_html( true, true ) );
	}

	/**
	 * A plugin that is there but switched off is offered a way to switch it on.
	 */
	public function test_an_installed_plugin_is_offered_activation(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$out = BlocksRequired\notice_html( true, false );

		$this->assertStringContainsString( 'notice-error', $out );
		$this->assertStringContainsString( 'Turn on the AWT Blocks plugin', $out );
		$this->assertStringContainsString( 'Turn on AWT Blocks', $out );
		// The link really activates, and carries the nonce WordPress checks.
		$this->assertStringContainsString( 'action=activate', $out );
		$this->assertStringContainsString( '_wpnonce=', $out );
		// It must not tell someone to download what they already have.
		$this->assertStringNotContainsString( 'Download AWT Blocks', $out );
	}

	/**
	 * A plugin that is not there is offered the file and the upload screen.
	 */
	public function test_a_missing_plugin_is_offered_the_download(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$out = BlocksRequired\notice_html( false, false );

		$this->assertStringContainsString( 'notice-error', $out );
		$this->assertStringContainsString( 'Install the AWT Blocks plugin', $out );
		$this->assertStringContainsString( BlocksRequired\RELEASES_URL, $out );
		$this->assertStringContainsString( 'plugin-install.php?tab=upload', $out );
		// Nothing to turn on, so nothing may offer to.
		$this->assertStringNotContainsString( 'Turn on AWT Blocks', $out );
	}

	/**
	 * An editor cannot install or activate anything, so is told nothing.
	 */
	public function test_a_user_who_cannot_act_is_not_told(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'editor' ) ) );

		$this->assertSame( '', BlocksRequired\notice_html( true, false ) );
		$this->assertSame( '', BlocksRequired\notice_html( false, false ) );
	}
}
