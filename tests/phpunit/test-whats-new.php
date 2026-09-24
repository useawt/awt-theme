<?php
/**
 * What's new panel — the release notes it shows.
 *
 * AWT is a theme and a plugin released together under one version number, so
 * the notes for a release are both halves' entries. The panel read only the
 * plugin's file, which meant a release carrying nothing but theme changes
 * showed a single entry saying the plugin had not changed — reported by a
 * site owner on 2026-09-10 asking where they were supposed to see what
 * changed.
 *
 * @package AWT\Theme
 */

/**
 * Merging and ordering of the bundled changelogs.
 */
class Test_Whats_New extends WP_UnitTestCase {

	/**
	 * A changelog file as the release script writes it.
	 *
	 * @param array $releases version => entries.
	 * @return array Decoded-file shape.
	 */
	private function file( array $releases ): array {
		$out = array();
		foreach ( $releases as $version => $entries ) {
			$out[] = array(
				'version' => (string) $version,
				'date'    => '2026-09-10',
				'entries' => $entries,
			);
		}
		return array(
			'schemaVersion'  => 1,
			'currentVersion' => (string) array_key_first( $releases ),
			'releases'       => $out,
		);
	}

	/**
	 * One entry, in the shape the panel renders.
	 *
	 * @param string $summary Entry text.
	 * @return array Entry.
	 */
	private function entry( string $summary ): array {
		return array(
			'severity' => 'Improvement',
			'summary'  => $summary,
		);
	}

	/**
	 * Both halves' entries appear under the one version, theme first.
	 */
	public function test_entries_from_both_halves_are_shown_together(): void {
		$merged = \AWT\Theme\WhatsNew\merge_changelogs(
			array(
				$this->file( array( '2026.09.10' => array( $this->entry( 'Theme thing' ) ) ) ),
				$this->file( array( '2026.09.10' => array( $this->entry( 'Plugin thing' ) ) ) ),
			)
		);

		$this->assertCount( 1, $merged['releases'] );
		$summaries = wp_list_pluck( $merged['releases'][0]['entries'], 'summary' );
		$this->assertSame( array( 'Theme thing', 'Plugin thing' ), $summaries );
	}

	/**
	 * A version only one half released is still listed.
	 */
	public function test_a_version_from_one_half_only_is_kept(): void {
		$merged = \AWT\Theme\WhatsNew\merge_changelogs(
			array(
				$this->file( array( '2026.09.10' => array( $this->entry( 'Theme only' ) ) ) ),
				$this->file( array( '2026.09.8' => array( $this->entry( 'Plugin only' ) ) ) ),
			)
		);

		$this->assertSame(
			array( '2026.09.10', '2026.09.8' ),
			wp_list_pluck( $merged['releases'], 'version' )
		);
	}

	/**
	 * Newest first, counting the patch as a number.
	 *
	 * Sorted as strings, "2026.09.9" sorts above "2026.09.10" — which stops
	 * being hypothetical the first month that reaches ten releases, and
	 * September 2026 did.
	 */
	public function test_a_tenth_release_sorts_above_the_ninth(): void {
		$merged = \AWT\Theme\WhatsNew\merge_changelogs(
			array(
				$this->file(
					array(
						'2026.09.9'  => array( $this->entry( 'Ninth' ) ),
						'2026.09.10' => array( $this->entry( 'Tenth' ) ),
						'2026.10.1'  => array( $this->entry( 'Next month' ) ),
					)
				),
			)
		);

		$this->assertSame(
			array( '2026.10.1', '2026.09.10', '2026.09.9' ),
			wp_list_pluck( $merged['releases'], 'version' )
		);
	}

	/**
	 * The current version is the highest either half reports.
	 */
	public function test_current_version_is_the_highest_of_the_two(): void {
		$merged = \AWT\Theme\WhatsNew\merge_changelogs(
			array(
				$this->file( array( '2026.09.8' => array( $this->entry( 'Older' ) ) ) ),
				$this->file( array( '2026.09.10' => array( $this->entry( 'Newer' ) ) ) ),
			)
		);

		$this->assertSame( '2026.09.10', $merged['currentVersion'] );
	}

	/**
	 * With neither half readable the panel has nothing to show.
	 */
	public function test_no_sources_means_no_panel(): void {
		$this->assertNull( \AWT\Theme\WhatsNew\merge_changelogs( array() ) );
		$this->assertNull(
			\AWT\Theme\WhatsNew\read_changelog_file( '/definitely/not/a/file.json' )
		);
	}

	/* ------------------------------------------- pinning, and edited parts */

	/**
	 * An accessibility fix pins like a security one.
	 *
	 * Added 2026-09-21, when AWT started installing its own updates: if a fix
	 * is important enough to put on somebody's site without asking, it is
	 * important enough to stay on screen until they have read what it did.
	 */
	public function test_an_accessibility_release_is_high_severity(): void {
		$release = array( 'entries' => array( array( 'severity' => 'A11y' ) ) );

		$this->assertTrue( \AWT\Theme\WhatsNew\is_high_severity( $release ) );
	}

	/** An ordinary release still is not. */
	public function test_an_ordinary_release_is_not_high_severity(): void {
		$release = array(
			'entries' => array(
				array( 'severity' => 'Improvement' ),
				array( 'severity' => 'New' ),
			),
		);

		$this->assertFalse( \AWT\Theme\WhatsNew\is_high_severity( $release ) );
	}

	/** A site with nothing edited is told nothing. */
	public function test_no_note_when_nothing_has_been_edited(): void {
		ob_start();
		\AWT\Theme\WhatsNew\render_customized_parts_note();

		$this->assertSame( '', trim( (string) ob_get_clean() ) );
	}

	/**
	 * A site that edited its header is told what that costs.
	 *
	 * The edit is kept through every update, which is the point — and by the
	 * same mechanism it stops receiving AWT's changes to that part. Saying so
	 * is the whole of the answer: knowing *which* fix touched a header would
	 * mean tagging every entry with the markup it changed.
	 */
	public function test_an_edited_part_is_named(): void {
		$part = self::factory()->post->create(
			array(
				'post_type'   => 'wp_template_part',
				'post_title'  => 'header',
				'post_name'   => 'header',
				'post_status' => 'publish',
			)
		);
		wp_set_object_terms( $part, get_stylesheet(), 'wp_theme' );

		$this->assertSame( array( 'header' ), \AWT\Theme\WhatsNew\customized_parts() );

		ob_start();
		\AWT\Theme\WhatsNew\render_customized_parts_note();
		$html = (string) ob_get_clean();

		$this->assertStringContainsString( 'header', $html );
		$this->assertStringContainsString( 'affect your edited copies', $html );
	}

	/** Another theme's parts are not this theme's problem. */
	public function test_another_themes_parts_are_ignored(): void {
		$part = self::factory()->post->create(
			array(
				'post_type'   => 'wp_template_part',
				'post_title'  => 'header',
				'post_status' => 'publish',
			)
		);
		wp_set_object_terms( $part, 'twentytwentyfive', 'wp_theme' );

		$this->assertSame( array(), \AWT\Theme\WhatsNew\customized_parts() );
	}
}
