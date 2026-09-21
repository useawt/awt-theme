<?php
/**
 * AWT's box on the dashboard.
 *
 * It exists because AWT installs its own updates: the dashboard is where
 * somebody finds out their site changed, and for many site owners it is the
 * only screen they look at.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\DashboardWidget;

/**
 * The dashboard box and where it lands.
 *
 * @covers \AWT\Theme\DashboardWidget
 */
class Test_Dashboard_Widget extends WP_UnitTestCase {

	/**
	 * Register the box the way the dashboard does, and hand back the registry.
	 *
	 * @return array The dashboard's meta boxes.
	 */
	private function boxes(): array {
		global $wp_meta_boxes;
		$wp_meta_boxes = array(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- a fresh registry per test; this is the fixture.
		set_current_screen( 'dashboard' );
		DashboardWidget\register();
		return (array) ( $wp_meta_boxes['dashboard'] ?? array() );
	}

	/** Somebody who looks after the theme gets the box. */
	public function test_an_administrator_gets_the_box(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$boxes = $this->boxes();

		$this->assertArrayHasKey( 'awt_dashboard', $boxes['side']['core'] ?? array() );
	}

	/**
	 * And it asks for the right-hand column.
	 *
	 * Registered straight into `side` rather than added with
	 * `wp_add_dashboard_widget()` and moved afterwards, because moving it
	 * means rewriting WordPress's meta box registry by hand.
	 */
	public function test_it_asks_for_the_side_column(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		$boxes = $this->boxes();

		$this->assertArrayNotHasKey( 'awt_dashboard', $boxes['normal']['core'] ?? array() );
		$this->assertArrayHasKey( 'awt_dashboard', $boxes['side']['core'] ?? array() );
	}

	/** Somebody who cannot open AWT Settings is not shown its release notes. */
	public function test_a_subscriber_does_not_get_the_box(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );

		$boxes = $this->boxes();

		$this->assertArrayNotHasKey( 'awt_dashboard', $boxes['side']['core'] ?? array() );
	}

	/** The box says which version is installed, and links to the full notes. */
	public function test_it_names_the_version_and_links_on(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		ob_start();
		DashboardWidget\render();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( \AWT\Theme\AWT_THEME_VERSION, $html );
		$this->assertStringContainsString( 'tab=whats-new', $html );
	}

	/**
	 * No image is ever fetched from useawt.com here.
	 *
	 * A picture in this box would be loaded by the administrator's own
	 * browser, handing `useawt.com` the IP address of every logged-in admin
	 * on every AWT site. The version check gives away nothing by design, and
	 * this must not be what undoes it — including when the box later grows a
	 * feed of posts.
	 */
	public function test_the_box_loads_nothing_from_outside(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );

		ob_start();
		DashboardWidget\render();
		$html = (string) ob_get_clean();

		$this->assertStringNotContainsString( '<img', $html );
		$this->assertDoesNotMatchRegularExpression( '/(src|srcset)\s*=\s*["\']https?:/i', $html );
	}
}
