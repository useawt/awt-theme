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
		remove_all_filters( 'awt_update_environment' );
		remove_all_filters( 'awt_deployed_from_source' );
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
	 *
	 * On an admin page load, which is where a person reads it. The unattended
	 * path is a different question and has its own tests below.
	 */
	public function test_a_newer_version_is_offered(): void {
		remove_all_filters( 'wp_doing_cron' );
		set_current_screen( 'themes' );
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
	 * Every site carries the package.
	 *
	 * **This test used to assert the opposite**, and said so: an empty package
	 * was the whole free/Premium boundary, and the test existed to fail if a
	 * change ever filled it in by default. On 2026-09-21 that became the
	 * decision rather than the accident, so the guard is inverted rather than
	 * deleted. An empty package here would now mean WordPress printing
	 * "Automatic update is unavailable" to a site that was promised the
	 * opposite, and a security fix that reaches nobody.
	 */
	public function test_every_site_is_offered_the_package(): void {
		remove_all_filters( 'wp_doing_cron' );
		set_current_screen( 'themes' );
		$this->cache( '2099.01.0' );

		$result = Updates\offer_update( $this->transient() );

		$this->assertSame(
			'https://example.com/awt-2099.01.0.zip',
			$result->response['awt']['package']
		);
	}

	/**
	 * The filter is still the seam, it just no longer decides the tier.
	 */
	public function test_the_package_can_still_be_replaced_by_a_filter(): void {
		remove_all_filters( 'wp_doing_cron' );
		set_current_screen( 'themes' );
		$this->cache( '2099.01.0' );
		add_filter( 'awt_theme_update_package', static fn () => 'https://example.com/other.zip' );

		$result = Updates\offer_update( $this->transient() );

		$this->assertSame( 'https://example.com/other.zip', $result->response['awt']['package'] );
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
	 * A site keeps itself up to date unless someone says otherwise.
	 */
	public function test_the_default_is_to_keep_itself_up_to_date(): void {
		$this->assertSame( 'auto', AWT\Theme\Settings\get( 'updates.mode' ) );
		$this->assertSame( 'auto', Updates\mode() );
		$this->assertTrue( Updates\enabled() );

		add_filter( 'awt_update_environment', static fn () => 'production' );
		$this->assertTrue( Updates\automatic_allowed() );
	}

	/**
	 * A staging copy does not update itself while somebody is working on it.
	 *
	 * Worth an explicit test because the test suite runs on a site that
	 * reports itself as `local` — so the default here is already the
	 * cautious answer, and a regression would look like nothing.
	 */
	public function test_only_production_installs_by_itself(): void {
		foreach ( array( 'local', 'development', 'staging' ) as $env ) {
			add_filter( 'awt_update_environment', static fn () => $env );
			$this->assertFalse( Updates\automatic_allowed(), $env );
			remove_all_filters( 'awt_update_environment' );
		}

		add_filter( 'awt_update_environment', static fn () => 'production' );
		$this->assertTrue( Updates\automatic_allowed() );
	}

	/**
	 * Each mode is stored and survives the round trip.
	 */
	public function test_each_mode_is_stored(): void {
		add_filter( 'awt_update_environment', static fn () => 'production' );
		AWT\Theme\Settings\set( 'updates.mode', 'notify' );
		$this->assertSame( 'notify', Updates\mode() );
		$this->assertTrue( Updates\enabled(), 'notify still asks useawt.com' );
		$this->assertFalse( Updates\automatic_allowed() );

		AWT\Theme\Settings\set( 'updates.mode', 'off' );
		$this->assertSame( 'off', Updates\mode() );
		$this->assertFalse( Updates\enabled() );
		$this->assertFalse( Updates\automatic_allowed() );

		AWT\Theme\Settings\set( 'updates.mode', 'auto' );
	}

	/**
	 * Nonsense in the setting is not a way to stop a site getting fixes.
	 */
	public function test_an_unknown_mode_falls_back_to_auto(): void {
		AWT\Theme\Settings\set( 'updates.mode', 'sometimes' );

		$this->assertSame( 'auto', Updates\mode() );

		AWT\Theme\Settings\set( 'updates.mode', 'auto' );
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

	/* ------------------------------------------- what may install itself */

	/**
	 * A release list, newest first, in the shape the manifest publishes.
	 *
	 * @param array $rows version => [ breaking, autoInstall ].
	 * @return array Release entries.
	 */
	private function releases( array $rows ): array {
		$out = array();
		foreach ( $rows as $version => $flags ) {
			$out[] = array(
				'version'     => (string) $version,
				'breaking'    => ! empty( $flags['breaking'] ),
				'autoInstall' => ! empty( $flags['autoInstall'] ),
				'theme'       => array( 'package' => 'https://example.com/awt-' . $version . '.zip' ),
				'plugin'      => array( 'package' => 'https://example.com/blocks-' . $version . '.zip' ),
			);
		}
		return $out;
	}

	/** With nothing in the way, a site climbs to the newest soaked release. */
	public function test_the_walk_reaches_the_newest_soaked_release(): void {
		$target = Updates\auto_install_target(
			array(
				'releases' => $this->releases(
					array(
						'2099.01.3' => array( 'autoInstall' => true ),
						'2099.01.2' => array( 'autoInstall' => true ),
						'2099.01.1' => array( 'autoInstall' => true ),
					)
				),
			),
			'2099.01.0'
		);

		$this->assertSame( '2099.01.3', $target['version'] );
	}

	/**
	 * A breaking release is a wall: the site stops underneath it.
	 *
	 * And everything above the wall waits, including releases that are
	 * themselves harmless — a version is cumulative, so there is no way to
	 * take 2099.01.3 without also taking the 2099.01.2 in it.
	 */
	public function test_the_walk_stops_under_a_breaking_release(): void {
		$target = Updates\auto_install_target(
			array(
				'releases' => $this->releases(
					array(
						'2099.01.3' => array( 'autoInstall' => true ),
						'2099.01.2' => array( 'breaking' => true ),
						'2099.01.1' => array( 'autoInstall' => true ),
					)
				),
			),
			'2099.01.0'
		);

		$this->assertSame( '2099.01.1', $target['version'] );
	}

	/** A site already above the wall is not held by it. */
	public function test_a_site_past_the_wall_climbs_on(): void {
		$target = Updates\auto_install_target(
			array(
				'releases' => $this->releases(
					array(
						'2099.01.3' => array( 'autoInstall' => true ),
						'2099.01.2' => array( 'breaking' => true ),
						'2099.01.1' => array( 'autoInstall' => true ),
					)
				),
			),
			'2099.01.2'
		);

		$this->assertSame( '2099.01.3', $target['version'] );
	}

	/** A release still soaking is not installed, and nor is anything above it. */
	public function test_the_walk_stops_at_a_release_still_soaking(): void {
		$target = Updates\auto_install_target(
			array(
				'releases' => $this->releases(
					array(
						'2099.01.3' => array( 'autoInstall' => true ),
						'2099.01.2' => array( 'autoInstall' => false ),
						'2099.01.1' => array( 'autoInstall' => true ),
					)
				),
			),
			'2099.01.0'
		);

		$this->assertSame( '2099.01.1', $target['version'] );
	}

	/** A current site has nothing to install. */
	public function test_the_walk_finds_nothing_for_a_current_site(): void {
		$this->assertNull(
			Updates\auto_install_target(
				array( 'releases' => $this->releases( array( '2099.01.1' => array( 'autoInstall' => true ) ) ) ),
				'2099.01.1'
			)
		);
	}

	/**
	 * A manifest with no release list installs nothing.
	 *
	 * The shape that was published before any of this existed. Failing safe
	 * here is what stops an old manifest, or a half-written one, from
	 * installing a version on somebody's site.
	 */
	public function test_a_manifest_without_releases_installs_nothing(): void {
		$this->assertNull( Updates\auto_install_target( array(), '2000.01.0' ) );
		$this->assertNull( Updates\auto_install_target( array( 'releases' => 'nonsense' ), '2000.01.0' ) );
	}

	/* ----------------------------------------- installed under another name */

	/**
	 * A site whose theme folder is not what the zip unpacks to.
	 *
	 * `awt.zip` extracts to `awt/`. A site that installed from a renamed zip
	 * or a clone can have the theme in `awt-theme/` — accessibilitycloud.com
	 * did, found on 2026-09-21 — and WordPress is happy with that until the
	 * day it updates. Then the package lands beside the theme instead of over
	 * it: a second copy, the old one still running, and a notice that never
	 * clears. Worse, every template part and template the owner has edited is
	 * filed against the old folder name and would not follow.
	 */
	public function test_a_mismatched_folder_withholds_the_package(): void {
		remove_all_filters( 'wp_doing_cron' );
		set_current_screen( 'themes' );
		$this->cache( '2099.01.0' );
		$this->assertTrue( Updates\package_folder_matches(), 'the fixture matches by default' );

		// Say the package unpacks somewhere this theme does not live.
		$data                  = get_site_transient( Updates\CACHE_KEY );
		$data['theme']['slug'] = 'somewhere-else';
		set_site_transient( Updates\CACHE_KEY, $data, HOUR_IN_SECONDS );

		$this->assertFalse( Updates\package_folder_matches() );

		$result = Updates\offer_update( $this->transient() );

		$this->assertArrayHasKey( 'awt', $result->response, 'the update is still announced' );
		$this->assertSame( '', $result->response['awt']['package'], 'but not installable' );
	}

	/** And such a site never installs anything by itself. */
	public function test_a_mismatched_folder_never_installs_by_itself(): void {
		add_filter( 'awt_update_environment', static fn () => 'production' );
		$this->cache( '2099.01.0' );
		$data                  = get_site_transient( Updates\CACHE_KEY );
		$data['theme']['slug'] = 'somewhere-else';
		set_site_transient( Updates\CACHE_KEY, $data, HOUR_IN_SECONDS );

		$this->assertFalse( Updates\automatic_allowed() );
	}

	/** A manifest that does not say where it unpacks is not treated as wrong. */
	public function test_a_manifest_without_a_slug_is_given_the_benefit_of_the_doubt(): void {
		$this->assertTrue( Updates\package_folder_matches( array() ) );
		$this->assertTrue( Updates\package_folder_matches( array( 'theme' => array( 'slug' => '' ) ) ) );
	}

	/* -------------------------------------------------- the unattended path */

	/** Unattended, the site is offered exactly what it may install. */
	public function test_cron_is_offered_the_target_and_not_the_newest(): void {
		add_filter( 'awt_update_environment', static fn () => 'production' );
		$this->cache(
			'2099.01.3',
			$this->releases(
				array(
					'2099.01.3' => array( 'autoInstall' => true ),
					'2099.01.2' => array( 'breaking' => true ),
					'2099.01.1' => array( 'autoInstall' => true ),
				)
			)
		);

		$result = Updates\offer_update( $this->transient() );

		$this->assertSame( '2099.01.1', $result->response['awt']['new_version'] );
		$this->assertSame( 'https://example.com/awt-2099.01.1.zip', $result->response['awt']['package'] );
	}

	/**
	 * With a wall immediately ahead, cron is offered nothing at all.
	 *
	 * The load-bearing one. Core installs whatever the entry names without
	 * asking anybody, so "offer the newest and rely on a filter to refuse"
	 * would put a breaking release on every site the day a filter elsewhere
	 * returned true.
	 */
	public function test_cron_is_offered_nothing_when_the_wall_is_next(): void {
		add_filter( 'awt_update_environment', static fn () => 'production' );
		$this->cache(
			'2099.01.2',
			$this->releases( array( '2099.01.2' => array( 'breaking' => true ) ) )
		);

		$result = Updates\offer_update( $this->transient() );

		$this->assertArrayNotHasKey( 'awt', $result->response );
		$this->assertArrayHasKey( 'awt', $result->no_update );
	}

	/** A person still sees the newest version, wall or no wall. */
	public function test_the_admin_still_sees_the_newest_version(): void {
		remove_all_filters( 'wp_doing_cron' );
		set_current_screen( 'themes' );
		$this->cache(
			'2099.01.2',
			$this->releases( array( '2099.01.2' => array( 'breaking' => true ) ) )
		);

		$result = Updates\offer_update( $this->transient() );

		$this->assertSame( '2099.01.2', $result->response['awt']['new_version'] );
	}

	/** Not production means nothing installs itself, whatever the manifest says. */
	public function test_cron_installs_nothing_outside_production(): void {
		add_filter( 'awt_update_environment', static fn () => 'staging' );
		$this->cache(
			'2099.01.1',
			$this->releases( array( '2099.01.1' => array( 'autoInstall' => true ) ) )
		);

		$result = Updates\offer_update( $this->transient() );

		$this->assertArrayNotHasKey( 'awt', $result->response );
	}

	/** A site deployed by something else never installs over that deploy. */
	public function test_a_site_deployed_from_source_is_left_alone(): void {
		add_filter( 'awt_update_environment', static fn () => 'production' );
		$this->assertTrue( Updates\automatic_allowed() );

		add_filter( 'awt_deployed_from_source', '__return_true' );

		$this->assertFalse( Updates\automatic_allowed() );
	}

	/**
	 * WordPress asks whether AWT should update itself, and always gets an
	 * answer — never "no opinion", which would leave the Themes screen
	 * offering a second switch that disagrees with AWT Settings.
	 */
	public function test_wordpress_is_given_a_definite_answer(): void {
		add_filter( 'awt_update_environment', static fn () => 'production' );
		$item = (object) array( 'theme' => 'awt' );

		$this->assertTrue( Updates\should_auto_update( null, $item ) );

		AWT\Theme\Settings\set( 'updates.mode', 'notify' );
		$this->assertFalse( Updates\should_auto_update( null, $item ) );
		AWT\Theme\Settings\set( 'updates.mode', 'auto' );

		$this->assertNull(
			Updates\should_auto_update( null, (object) array( 'theme' => 'twentytwentyfive' ) ),
			'someone else\'s theme is not ours to answer for'
		);
	}

	/**
	 * The documented constant is honoured.
	 *
	 * **Deliberately the last test in this class**, because `define()` cannot
	 * be undone: from here to the end of the process AWT considers itself
	 * deployed from source. Nothing after this file asks. If a test is ever
	 * added below this one and fails for no visible reason, this is why.
	 */
	public function test_zz_the_deploy_constant_is_honoured(): void {
		add_filter( 'awt_update_environment', static fn () => 'production' );
		$this->assertTrue( Updates\automatic_allowed() );

		define( 'AWT_DEPLOYED_FROM_SOURCE', true );

		$this->assertTrue( Updates\deployed_from_source() );
		$this->assertFalse( Updates\automatic_allowed() );
	}

	// --- helpers ------------------------------------------------------------

	/**
	 * Put a manifest naming $version straight into the cache.
	 *
	 * @param string $version  Version to announce.
	 * @param array  $releases Optional release list, newest first. A manifest
	 *                         without one can never install itself, which is
	 *                         the safe default and what most tests here want.
	 */
	private function cache( string $version, array $releases = array() ): void {
		set_site_transient(
			Updates\CACHE_KEY,
			array(
				'schemaVersion' => 1,
				'version'       => $version,
				'requiresWp'    => '6.6',
				'requiresPhp'   => '8.1',
				'testedWp'      => '7.1',
				'theme'         => array(
					'slug'       => Updates\slug(),
					'releaseUrl' => 'https://example.com/theme',
					'package'    => 'https://example.com/awt-' . $version . '.zip',
				),
				'plugin'        => array(
					'slug'       => 'awt-blocks',
					'releaseUrl' => 'https://example.com/plugin',
					'package'    => 'https://example.com/awt-blocks-' . $version . '.zip',
				),
				'releases'      => $releases,
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
