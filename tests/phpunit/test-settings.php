<?php
/**
 * The settings layer: schema migration, the merge that gives every read a
 * default, dot-path access, and the sanitizer's deliberate exceptions.
 *
 * These are the theme's most-read functions — every template, every block
 * render and the whole admin surface goes through `all()` — and until
 * 2026-09-02 none of them had a test. The v1 -> v2 migration in particular was
 * only ever verified by hand, on a throwaway install, once.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

use AWT\Theme\Settings;

/**
 * The settings layer: migration, merge, dot-path reads, sanitizing.
 *
 * @covers \AWT\Theme\Settings
 */
class Test_Settings extends WP_UnitTestCase {

	/**
	 * Start every test from a cold settings cache.
	 */
	public function set_up(): void {
		parent::set_up();
		Settings\flush_cache();
	}

	/**
	 * Leave no settings row behind for the next test.
	 */
	public function tear_down(): void {
		delete_option( Settings\OPTION_KEY );
		Settings\flush_cache();
		parent::tear_down();
	}

	/* ---------------------------------------------------------------- migrate */

	/**
	 * Version 1 wrote a concrete brand mode even when the owner never opened the
	 * setting, so a stored `text-only` carries no intent. v2 rewrites it to
	 * `auto`, which is the entire point of the change: a site that HAD
	 * uploaded a logo was saving it and then silently not drawing it.
	 */
	public function test_migrate_rewrites_v1_text_only_brand_mode(): void {
		$out = Settings\migrate(
			array(
				'schemaVersion' => 1,
				'identity'      => array( 'brandMode' => 'text-only' ),
			)
		);

		$this->assertSame( 'auto', $out['identity']['brandMode'] );
		$this->assertSame( 2, $out['schemaVersion'] );
	}

	/**
	 * Only `text-only` is ambiguous. A v1 site that chose any other mode chose
	 * it deliberately, and the migration must not touch it.
	 */
	public function test_migrate_leaves_a_deliberate_v1_brand_mode_alone(): void {
		$out = Settings\migrate(
			array(
				'schemaVersion' => 1,
				'identity'      => array( 'brandMode' => 'logo-only' ),
			)
		);

		$this->assertSame( 'logo-only', $out['identity']['brandMode'] );
		$this->assertSame( 2, $out['schemaVersion'] );
	}

	/**
	 * A payload with no version is v1 by definition — that is what "before we
	 * started stamping a version" looks like on disk.
	 */
	public function test_migrate_treats_a_missing_version_as_v1(): void {
		$out = Settings\migrate( array( 'identity' => array( 'brandMode' => 'text-only' ) ) );

		$this->assertSame( 'auto', $out['identity']['brandMode'] );
		$this->assertSame( 2, $out['schemaVersion'] );
	}

	/**
	 * Already-current payloads pass through untouched. `text-only` at v2 is a
	 * real choice, not a v1 leftover, and rewriting it would silently undo the
	 * owner's decision on every page load.
	 */
	public function test_migrate_does_not_rewrite_a_v2_text_only_choice(): void {
		$out = Settings\migrate(
			array(
				'schemaVersion' => 2,
				'identity'      => array( 'brandMode' => 'text-only' ),
			)
		);

		$this->assertSame( 'text-only', $out['identity']['brandMode'] );
	}

	/** The migration is a pure transform. It must never write to the database. */
	public function test_migrate_writes_nothing(): void {
		Settings\migrate( array( 'schemaVersion' => 1 ) );

		$this->assertFalse( get_option( Settings\OPTION_KEY, false ) );
	}

	/* ------------------------------------------------------------------- read */

	/**
	 * The whole point of `all()`: a partial stored payload still answers every
	 * known path, because defaults are merged underneath it. Without this, any
	 * setting added after a site last saved would read as null.
	 */
	public function test_all_fills_missing_keys_from_defaults(): void {
		update_option( Settings\OPTION_KEY, wp_json_encode( array( 'schemaVersion' => 2 ) ) );
		Settings\flush_cache();

		$all = Settings\all();

		$this->assertSame( 'carbon', $all['designSystem']['slug'] );
		$this->assertArrayHasKey( 'identity', $all );
	}

	/**
	 * Settings persist as a JSON string, so `all()` has to decode. A site
	 * whose option was written as an array must still read, which is what the
	 * `is_string` branch is for.
	 */
	public function test_all_reads_both_the_json_and_the_array_form(): void {
		update_option( Settings\OPTION_KEY, wp_json_encode( array( 'site' => array( 'colorScheme' => 'dark' ) ) ) );
		Settings\flush_cache();
		$this->assertSame( 'dark', Settings\get( 'site.colorScheme' ) );

		update_option( Settings\OPTION_KEY, array( 'site' => array( 'colorScheme' => 'light' ) ) );
		Settings\flush_cache();
		$this->assertSame( 'light', Settings\get( 'site.colorScheme' ) );
	}

	/** Garbage in the option must not take the site down; defaults answer instead. */
	public function test_all_survives_an_unreadable_option(): void {
		update_option( Settings\OPTION_KEY, 'not json at all' );
		Settings\flush_cache();

		$this->assertSame( 'carbon', Settings\get( 'designSystem.slug' ) );
	}

	/** An unknown path returns null rather than throwing or warning. */
	public function test_get_returns_null_for_an_unknown_path(): void {
		$this->assertNull( Settings\get( 'nope.not.here' ) );
	}

	/**
	 * Walking into a scalar must stop, not warn. `identity.brandMode.deeper`
	 * is the shape a typo takes.
	 */
	public function test_get_stops_when_a_path_runs_into_a_scalar(): void {
		$this->assertNull( Settings\get( 'designSystem.slug.deeper' ) );
	}

	/* ------------------------------------------------------------ deep_merge */

	/** Nested keys merge rather than the override replacing the whole branch. */
	public function test_deep_merge_recurses_into_nested_arrays(): void {
		$out = Settings\deep_merge(
			array(
				'a' => array(
					'keep'   => 1,
					'change' => 1,
				),
			),
			array( 'a' => array( 'change' => 2 ) )
		);

		$this->assertSame(
			array(
				'keep'   => 1,
				'change' => 2,
			),
			$out['a']
		);
	}

	/** A scalar override replaces an array, so a stored scalar cannot be merged into. */
	public function test_deep_merge_lets_a_scalar_replace_an_array(): void {
		$out = Settings\deep_merge( array( 'a' => array( 'x' => 1 ) ), array( 'a' => 'scalar' ) );

		$this->assertSame( 'scalar', $out['a'] );
	}

	/* -------------------------------------------------------------- sanitize */

	/**
	 * Custom code and Custom CSS are documented as accepting arbitrary code —
	 * the capability check is the gate, not the sanitizer. Stripping them here
	 * would defeat the field, so this pins the exception deliberately.
	 */
	public function test_sanitize_leaves_custom_code_fields_intact(): void {
		$css = 'body::after { content: "<b>not markup</b>"; }';
		$out = Settings\sanitize( array( 'customCss' => $css ) );

		$this->assertSame( $css, $out['customCss'] );
	}

	/** Anything not in the registry falls back to Carbon rather than being stored. */
	public function test_sanitize_rejects_an_unknown_design_system(): void {
		$out = Settings\sanitize( array( 'designSystem' => array( 'slug' => 'not-a-real-system' ) ) );

		$this->assertSame( 'carbon', $out['designSystem']['slug'] );
	}

	/**
	 * Every WRITE stamps the current version, so a saved document is never
	 * stale. The stamp lives in `save()`, not in `sanitize()` — which this test
	 * originally asserted the other way round and got a failure for, and which
	 * `migrate()`'s docblock claimed wrongly until 2026-09-02.
	 */
	public function test_save_stamps_the_current_schema_version(): void {
		Settings\save( array( 'schemaVersion' => 1 ) );
		Settings\flush_cache();

		$this->assertSame( Settings\SCHEMA_VERSION, Settings\get( 'schemaVersion' ) );
	}

	/**
	 * And `sanitize()` on its own preserves the version it is handed. Pinned
	 * deliberately: it is safe only because `save()` is the sole caller and
	 * stamps first, so a future caller that sanitizes without saving would
	 * carry a stale version through. If this assertion ever has to change,
	 * check that caller.
	 */
	public function test_sanitize_alone_preserves_the_version_it_is_given(): void {
		$out = Settings\sanitize( array( 'schemaVersion' => 1 ) );

		$this->assertSame( 1, $out['schemaVersion'] );
	}

	/* ------------------------------------------------------------ round trip */

	/** Writing then reading is the path the admin screens take on every save. */
	public function test_set_then_read_round_trips_through_the_option(): void {
		Settings\set( 'site.colorScheme', 'dark' );
		Settings\flush_cache();

		$this->assertSame( 'dark', Settings\get( 'site.colorScheme' ) );
	}
}
