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

	/**
	 * A theme that pairs with another plugin through `awt_blocks_plugin` names
	 * that plugin, switches that one on, and with no public download offers
	 * the upload screen alone.
	 *
	 * @covers \AWT\Theme\BlocksRequired\plugin
	 */
	public function test_a_filtered_plugin_is_the_one_named_and_switched_on(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		add_filter(
			'awt_blocks_plugin',
			static fn() => array(
				'file'         => 'other-blocks/other-blocks.php',
				'name'         => 'Other Blocks',
				'download_url' => '',
			)
		);

		$off = BlocksRequired\notice_html( true, false );
		$this->assertStringContainsString( 'Turn on the Other Blocks plugin', $off );
		$this->assertStringContainsString( 'Turn on Other Blocks', $off );
		$this->assertStringContainsString( 'plugin=other-blocks%2Fother-blocks.php', $off );
		$this->assertStringNotContainsString( 'AWT Blocks', $off );

		$missing = BlocksRequired\notice_html( false, false );
		$this->assertStringContainsString( 'Install the Other Blocks plugin', $missing );
		$this->assertStringContainsString( 'plugin-install.php?tab=upload', $missing );
		$this->assertStringNotContainsString( BlocksRequired\RELEASES_URL, $missing );
		$this->assertStringNotContainsString( 'Download', $missing );
	}

	/**
	 * A filter that returns something unusable leaves AWT Blocks in place,
	 * field by field.
	 *
	 * @covers \AWT\Theme\BlocksRequired\plugin
	 */
	public function test_an_unusable_filter_falls_back_to_awt_blocks(): void {
		add_filter( 'awt_blocks_plugin', '__return_false' );
		$this->assertSame( 'AWT Blocks', BlocksRequired\plugin()['name'] );
		remove_all_filters( 'awt_blocks_plugin' );

		add_filter(
			'awt_blocks_plugin',
			static fn() => array(
				'file' => 42,
				'name' => '',
			)
		);
		$this->assertSame(
			array(
				'file'         => BlocksRequired\PLUGIN_FILE,
				'name'         => 'AWT Blocks',
				'download_url' => BlocksRequired\RELEASES_URL,
				'constant'     => BlocksRequired\BLOCKS_LOADED,
			),
			BlocksRequired\plugin()
		);
	}

	/**
	 * Whether the paired plugin is running is read from the constant the
	 * filter names, not from AWT Blocks' own.
	 *
	 * @covers \AWT\Theme\BlocksRequired\is_active
	 */
	public function test_running_is_read_from_the_filtered_constant(): void {
		add_filter( 'awt_blocks_plugin', static fn() => array( 'constant' => 'ABSPATH' ) );
		$this->assertTrue( BlocksRequired\is_active() );
		remove_all_filters( 'awt_blocks_plugin' );

		add_filter( 'awt_blocks_plugin', static fn() => array( 'constant' => 'AWT_TEST_NEVER_DEFINED' ) );
		$this->assertFalse( BlocksRequired\is_active() );
	}

	/**
	 * With AWT Blocks running in place of the paired plugin the site works,
	 * so it is a warning, not "not working", and it names both plugins.
	 */
	public function test_awt_blocks_running_instead_is_a_warning(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		add_filter(
			'awt_blocks_plugin',
			static fn() => array(
				'file'         => 'other-blocks/other-blocks.php',
				'name'         => 'Other Blocks',
				'download_url' => '',
			)
		);

		$off = BlocksRequired\notice_html( true, false, true );
		$this->assertStringContainsString( 'notice-warning', $off );
		$this->assertStringContainsString( 'Some AWT features are missing.', $off );
		$this->assertStringContainsString( 'This theme is made for the Other Blocks plugin, but AWT Blocks is running instead. Turn on Other Blocks to get them.', $off );
		$this->assertStringContainsString( 'plugin=other-blocks%2Fother-blocks.php', $off );
		$this->assertStringNotContainsString( 'not working', $off );

		$missing = BlocksRequired\notice_html( false, false, true );
		$this->assertStringContainsString( 'notice-warning', $missing );
		$this->assertStringContainsString( 'Install Other Blocks to get them.', $missing );
		$this->assertStringContainsString( 'plugin-install.php?tab=upload', $missing );
		$this->assertStringNotContainsString( 'action=activate', $missing );
	}
}
