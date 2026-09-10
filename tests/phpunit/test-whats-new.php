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
}
