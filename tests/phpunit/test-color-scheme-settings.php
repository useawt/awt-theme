<?php
/**
 * How the page picks its colour scheme, and the filter on it.
 *
 * `color_scheme_settings()` feeds the pre-paint script, the server's guess at
 * the body's scope class and the colour-scheme toggle. The filter lets an
 * extension pin one request to light or dark without touching the site's
 * saved settings.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

/**
 * The resolver and its filter.
 *
 * @covers \AWT\Theme\color_scheme_settings
 */
class Test_Color_Scheme_Settings extends WP_UnitTestCase {

	/**
	 * Leave the setting and the filter as they were found.
	 */
	public function tear_down(): void {
		remove_all_filters( 'awt_color_scheme_settings' );
		\AWT\Theme\Settings\set( 'site.colorScheme', 'default' );
		parent::tear_down();
	}

	/** A site pinned to dark turns off the desktop and the visitor's choice. */
	public function test_a_pinned_site_pins_the_page(): void {
		\AWT\Theme\Settings\set( 'site.colorScheme', 'dark' );

		$this->assertSame(
			array(
				'default'               => 'dark',
				'honorSystemPreference' => false,
				'allowVisitorOverride'  => false,
			),
			\AWT\Theme\color_scheme_settings()
		);
	}

	/** The filter can pin one request, and the toggle goes with the pin. */
	public function test_the_filter_can_pin_a_request(): void {
		\AWT\Theme\Settings\set( 'site.colorScheme', 'default' );
		add_filter(
			'awt_color_scheme_settings',
			static function (): array {
				return array(
					'default'               => 'dark',
					'honorSystemPreference' => false,
					'allowVisitorOverride'  => false,
				);
			}
		);

		$this->assertSame( 'dark', \AWT\Theme\color_scheme_settings()['default'] );
		$this->assertFalse( \AWT\Theme\color_scheme_allow_visitor_override() );
	}

	/** A filter that returns something unusable changes nothing. */
	public function test_a_broken_filter_is_ignored(): void {
		\AWT\Theme\Settings\set( 'site.colorScheme', 'dark' );
		$before = \AWT\Theme\color_scheme_settings();
		add_filter( 'awt_color_scheme_settings', '__return_null' );

		$this->assertSame( $before, \AWT\Theme\color_scheme_settings() );
	}

	/** Only light or dark come out, whatever the filter says. */
	public function test_an_unknown_scheme_falls_back_to_light(): void {
		add_filter(
			'awt_color_scheme_settings',
			static function ( array $settings ): array {
				$settings['default'] = 'sepia';
				return $settings;
			}
		);

		$this->assertSame( 'light', \AWT\Theme\color_scheme_settings()['default'] );
	}
}
