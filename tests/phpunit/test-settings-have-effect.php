<?php
/**
 * Every setting with a control changes something.
 *
 * The typography size scale was stored, sanitized, and shown as the chosen
 * radio for months while nothing on the page read it. Tests of the settings
 * layer all passed: it stored and returned the value perfectly. The question
 * none of them asked was whether anything downstream *used* it.
 *
 * So this asks that, one setting at a time: flip it, and look at the thing it
 * is supposed to change. It is deliberately shallow — it does not check that
 * the effect is correct, only that there is one — because the failure being
 * guarded against is a control wired to nothing.
 *
 * Not covered here, on purpose: the customCode fields, which the free theme
 * stores and never outputs because that screen is Premium; the reserved
 * breadcrumb `position`, which has one valid value at Stage 1; and the wizard's
 * own progress keys, which are state rather than settings.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\Settings;

/**
 * Each setting is flipped and its observable effect checked.
 */
class Test_Settings_Have_Effect extends WP_UnitTestCase {

	/**
	 * Start from a clean settings row.
	 */
	public function set_up(): void {
		parent::set_up();
		Settings\flush_cache();
	}

	/**
	 * Leave no settings behind.
	 */
	public function tear_down(): void {
		delete_option( Settings\OPTION_KEY );
		Settings\flush_cache();
		parent::tear_down();
	}

	/**
	 * Body classes are how most of the switches reach the page.
	 *
	 * @return array<string,array{0:string,1:string}> Setting path, body class.
	 */
	public static function body_class_settings(): array {
		return array(
			'underline in main'      => array( 'links.underline.main', 'awt-underline-main' ),
			'underline in header'    => array( 'links.underline.header', 'awt-underline-header' ),
			'underline in sidenav'   => array( 'links.underline.sideNav', 'awt-underline-side-nav' ),
			'underline in crumbs'    => array( 'links.underline.breadcrumbs', 'awt-underline-breadcrumbs' ),
			'underline in footer'    => array( 'links.underline.footer', 'awt-underline-footer' ),
			'button focus outline'   => array( 'focus.buttonOutline', 'awt-btn-focus-outline' ),
			'form text at body size' => array( 'typography.formTextBodySize', 'awt-form-text-body' ),
		);
	}

	/**
	 * A switch that is on puts its class on the body, and off takes it away.
	 *
	 * @dataProvider body_class_settings
	 *
	 * @param string $path      Setting path.
	 * @param string $body_class Body class it should add.
	 */
	public function test_switch_reaches_the_body_class( string $path, string $body_class ): void {
		Settings\set( 'links.underline.all', true );

		Settings\set( $path, true );
		$this->assertContains( $body_class, get_body_class(), $path . ' on should add ' . $body_class );

		Settings\set( $path, false );
		$this->assertNotContains( $body_class, get_body_class(), $path . ' off should remove ' . $body_class );
	}

	/**
	 * The master switch gates every region, so it has an effect of its own.
	 */
	public function test_the_underline_master_gates_the_regions(): void {
		Settings\set( 'links.underline.main', true );

		Settings\set( 'links.underline.all', true );
		$this->assertContains( 'awt-underline-main', get_body_class() );

		Settings\set( 'links.underline.all', false );
		$this->assertNotContains( 'awt-underline-main', get_body_class() );
	}

	/**
	 * The size scale is CSS rather than a class — the one that was missing.
	 */
	public function test_the_size_scale_produces_css(): void {
		Settings\set( 'typography.sizeScale', 1.125 );
		$this->assertNotSame( '', \AWT\Theme\type_scale_css() );

		Settings\set( 'typography.sizeScale', 1.0 );
		$this->assertSame( '', \AWT\Theme\type_scale_css() );
	}

	/**
	 * The header width, also CSS rather than a class.
	 */
	public function test_the_header_width_produces_css(): void {
		Settings\set( 'header.containWidth', true );
		$this->assertNotSame( '', \AWT\Theme\header_contain_css() );

		Settings\set( 'header.containWidth', false );
		$this->assertSame( '', \AWT\Theme\header_contain_css() );
	}

	/**
	 * The breadcrumb switches, asked the way the emitter asks them.
	 */
	public function test_the_breadcrumb_switches_reach_the_emitter(): void {
		Settings\set( 'navigation.breadcrumbAutoEmit.enabled', true );
		$this->assertTrue( \AWT\Theme\Breadcrumb\is_enabled() );

		Settings\set( 'navigation.breadcrumbAutoEmit.enabled', false );
		$this->assertFalse( \AWT\Theme\Breadcrumb\is_enabled() );

		Settings\set( 'navigation.breadcrumbAutoEmit.mobile', true );
		$this->assertTrue( \AWT\Theme\Breadcrumb\show_on_mobile() );

		Settings\set( 'navigation.breadcrumbAutoEmit.mobile', false );
		$this->assertFalse( \AWT\Theme\Breadcrumb\show_on_mobile() );
	}

	/**
	 * The breadcrumb's two pieces of wording.
	 */
	public function test_the_breadcrumb_wording_reaches_the_trail(): void {
		Settings\set( 'navigation.homeItemText', 'Start' );
		$this->assertSame( 'Start', \AWT\Theme\Breadcrumb\home_text() );

		Settings\set( 'navigation.pageNotFoundItemText', 'Nothing here' );
		$this->assertSame( 'Nothing here', \AWT\Theme\Breadcrumb\not_found_text() );
	}

	/**
	 * The site's colour scheme decides the scope class on <body>.
	 */
	public function test_the_colour_scheme_reaches_the_body_class(): void {
		Settings\set( 'site.colorScheme', 'light' );
		$light = preg_grep( '/^cds--/', get_body_class() );

		Settings\set( 'site.colorScheme', 'dark' );
		$dark = preg_grep( '/^cds--/', get_body_class() );

		$this->assertNotEquals( $light, $dark, 'the scope class should follow the setting' );
	}
}
