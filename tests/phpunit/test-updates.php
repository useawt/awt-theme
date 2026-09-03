<?php
/**
 * The update check.
 *
 * What matters here is not that a version number arrives, but that a wrong
 * answer cannot. Two failures would each be worse than not checking at all:
 * announcing an update that does not exist, and telling useawt.com which site
 * is asking. Both are covered below.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\Updates;

/**
 * The update check: what it reads, what it refuses, and what it sends.
 *
 * @covers \AWT\Theme\Updates
 */
class Test_Updates extends WP_UnitTestCase {

	/**
	 * The check deliberately does nothing on a front-end page load, so a test
	 * that wants to watch it has to be one of the requests that do check.
	 * Cron is the honest one to pretend to be: it is the request that runs
	 * background updates, and `wp_doing_cron()` is filterable, where
	 * `is_admin()` is a constant the test suite fixes at boot.
	 */
	public function set_up(): void {
		parent::set_up();
		add_filter( 'wp_doing_cron', '__return_true' );
	}

	/**
	 * Leave no cached answer or filter behind for the next test.
	 */
	public function tear_down(): void {
		remove_all_filters( 'wp_doing_cron' );
		delete_site_transient( Updates\CACHE_KEY );
		remove_all_filters( 'awt_theme_update_package' );
		remove_all_filters( 'awt_update_check_enabled' );
		parent::tear_down();
	}

	/**
	 * A well-formed manifest is accepted.
	 */
	public function test_parse_accepts_a_good_manifest(): void {
		$parsed = Updates\parse(
			$this->response(
				array(
					'schemaVersion' => 1,
					'version'       => '2099.01.0',
				)
			)
		);

		$this->assertIsArray( $parsed );
		$this->assertSame( '2099.01.0', $parsed['version'] );
	}

	/**
	 * Everything that is not a manifest is refused.
	 *
	 * The captive-portal case is the one worth naming: a hotel or corporate
	 * network that answers every request with its own login page, 200 and all.
	 * Parsing loosely there would let a login page announce a version.
	 *
	 * @dataProvider bad_responses
	 *
	 * @param mixed  $response A response that must not be believed.
	 * @param string $why      What it is.
	 */
	public function test_parse_refuses_anything_else( $response, string $why ): void {
		$this->assertNull( Updates\parse( $response ), $why );
	}

	/**
	 * Responses that are not a manifest.
	 *
	 * @return array<string, array{0: mixed, 1: string}>
	 */
	public function bad_responses(): array {
		return array(
			'transport failure' => array( new WP_Error( 'http_request_failed', 'down' ), 'a network error' ),
			'404'               => array(
				$this->response(
					array(
						'schemaVersion' => 1,
						'version'       => '2099.01.0',
					),
					404
				),
				'a 404',
			),
			'not json'          => array( $this->raw_response( '<html>Sign in to continue</html>' ), 'a captive portal login page' ),
			'empty body'        => array( $this->raw_response( '' ), 'an empty body' ),
			'wrong schema'      => array(
				$this->response(
					array(
						'schemaVersion' => 2,
						'version'       => '2099.01.0',
					)
				),
				'a schema this reader does not know',
			),
			'no version'        => array( $this->response( array( 'schemaVersion' => 1 ) ), 'no version at all' ),
			'version is a word' => array(
				$this->response(
					array(
						'schemaVersion' => 1,
						'version'       => 'latest',
					)
				),
				'a version that is not a version',
			),
			'version truncated' => array(
				$this->response(
					array(
						'schemaVersion' => 1,
						'version'       => '2099.01',
					)
				),
				'a half-written version',
			),
		);
	}

	/**
	 * A newer version reaches WordPress's update list.
	 */
	public function test_a_newer_version_is_offered(): void {
		$this->cache( '2099.01.0' );

		$result = Updates\offer_update( $this->transient() );

		$this->assertArrayHasKey( 'awt', $result->response );
		$this->assertSame( '2099.01.0', $result->response['awt']['new_version'] );
		$this->assertArrayNotHasKey( 'awt', $result->no_update );
	}

	/**
	 * The current version is reported as current, not as an update.
	 */
	public function test_the_installed_version_is_not_offered(): void {
		$this->cache( \AWT\Theme\AWT_THEME_VERSION );

		$result = Updates\offer_update( $this->transient() );

		$this->assertArrayNotHasKey( 'awt', $result->response );
		$this->assertArrayHasKey( 'awt', $result->no_update );
	}

	/**
	 * An older published version never downgrades a site.
	 */
	public function test_an_older_version_is_not_offered(): void {
		$this->cache( '2000.01.0' );

		$result = Updates\offer_update( $this->transient() );

		$this->assertArrayNotHasKey( 'awt', $result->response );
	}

	/**
	 * The free tier carries no package.
	 *
	 * This is the whole free/Premium boundary in one field: an empty package is
	 * what makes WordPress say "Automatic update is unavailable" rather than
	 * offering a button. If a change ever fills it in by default, every free
	 * site silently gains one-click updates.
	 */
	public function test_free_offers_no_package(): void {
		$this->cache( '2099.01.0' );

		$result = Updates\offer_update( $this->transient() );

		$this->assertSame( '', $result->response['awt']['package'] );
	}

	/**
	 * A licence can fill the package in without touching this code.
	 */
	public function test_a_licence_can_add_the_package(): void {
		$this->cache( '2099.01.0' );
		add_filter( 'awt_theme_update_package', static fn () => 'https://example.com/awt.zip' );

		$result = Updates\offer_update( $this->transient() );

		$this->assertSame( 'https://example.com/awt.zip', $result->response['awt']['package'] );
	}

	/**
	 * Turning the check off stops it, without needing the setting saved.
	 */
	public function test_the_check_can_be_turned_off(): void {
		$this->cache( '2099.01.0' );
		add_filter( 'awt_update_check_enabled', '__return_false' );

		$this->assertNull( Updates\manifest() );
		$this->assertSame( array(), (array) Updates\offer_update( $this->transient() )->response );
	}

	/**
	 * The check is on unless someone turns it off.
	 */
	public function test_the_check_is_on_by_default(): void {
		$this->assertTrue( AWT\Theme\Settings\get( 'updates.check' ) );
		$this->assertTrue( Updates\enabled() );
	}

	/**
	 * An unchecked box is stored as off, and survives the round trip.
	 */
	public function test_turning_it_off_is_stored(): void {
		AWT\Theme\Settings\set( 'updates.check', false );

		$this->assertFalse( AWT\Theme\Settings\get( 'updates.check' ) );
		$this->assertFalse( Updates\enabled() );

		AWT\Theme\Settings\set( 'updates.check', true );
	}

	/**
	 * The request tells useawt.com nothing about this site.
	 *
	 * WordPress's default User-Agent is `WordPress/6.8; https://example.com` —
	 * the site's own address, sent on every check. The header is overridden,
	 * and this test watches the request go out to prove it: no site address
	 * anywhere in the URL, the headers or the body.
	 */
	public function test_the_request_carries_nothing_about_the_site(): void {
		$seen = null;
		add_filter(
			'pre_http_request',
			static function ( $preempt, $args, $url ) use ( &$seen ) {
				$seen = array(
					'args' => $args,
					'url'  => $url,
				);
				return new WP_Error( 'stopped', 'not making a real request' );
			},
			10,
			3
		);

		Updates\manifest();
		remove_all_filters( 'pre_http_request' );

		$this->assertNotNull( $seen, 'the check should have made a request' );

		$sent = wp_json_encode( $seen );
		$home = wp_parse_url( home_url(), PHP_URL_HOST );

		$this->assertStringNotContainsString( (string) $home, $sent, 'the request named this site' );
		$this->assertSame( 'AWT', $seen['args']['user-agent'] );
		$this->assertStringNotContainsString( '?', $seen['url'], 'the request should carry no query string' );
		$this->assertEmpty( $seen['args']['body'] ?? '' );
	}

	/**
	 * A failed check is remembered, so an unreachable endpoint is not paid for
	 * on every admin page load.
	 */
	public function test_a_failure_is_cached(): void {
		add_filter( 'pre_http_request', static fn () => new WP_Error( 'down', 'no' ) );

		$this->assertNull( Updates\manifest() );
		$this->assertSame( 'failed', get_site_transient( Updates\CACHE_KEY ) );

		remove_all_filters( 'pre_http_request' );
	}

	// --- helpers ------------------------------------------------------------

	/**
	 * Put a manifest naming $version straight into the cache.
	 *
	 * @param string $version Version to announce.
	 */
	private function cache( string $version ): void {
		set_site_transient(
			Updates\CACHE_KEY,
			array(
				'schemaVersion' => 1,
				'version'       => $version,
				'requiresWp'    => '6.6',
				'requiresPhp'   => '8.1',
				'testedWp'      => '7.1',
				'theme'         => array(
					'slug'       => 'awt',
					'releaseUrl' => 'https://example.com/theme',
				),
				'plugin'        => array(
					'slug'       => 'awt-blocks',
					'releaseUrl' => 'https://example.com/plugin',
				),
			),
			HOUR_IN_SECONDS
		);
	}

	/** An empty update_themes transient to filter. */
	private function transient(): stdClass {
		$t            = new stdClass();
		$t->response  = array();
		$t->no_update = array();
		return $t;
	}

	/**
	 * A response carrying a JSON body.
	 *
	 * @param array $body JSON body.
	 * @param int   $code HTTP status.
	 * @return array A wp_remote_get()-shaped response.
	 */
	private function response( array $body, int $code = 200 ): array {
		return $this->raw_response( (string) wp_json_encode( $body ), $code );
	}

	/**
	 * A response carrying whatever body it is given.
	 *
	 * @param string $body Raw body.
	 * @param int    $code HTTP status.
	 * @return array A wp_remote_get()-shaped response.
	 */
	private function raw_response( string $body, int $code = 200 ): array {
		return array(
			'headers'  => array(),
			'body'     => $body,
			'response' => array(
				'code'    => $code,
				'message' => '',
			),
			'cookies'  => array(),
			'filename' => null,
		);
	}
}
