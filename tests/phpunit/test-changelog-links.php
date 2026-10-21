<?php
/**
 * Links in release notes can be clicked.
 *
 * The 2026.10.12 notes linked to the accessibility page, and every place that
 * shows release notes printed the address in angle brackets instead of a link
 * (found by the owner, 2026-10-21). Both link forms now become links; nothing
 * else in the text turns into markup.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

/**
 * Checks how a release note entry becomes HTML.
 */
class Test_Changelog_Links extends WP_UnitTestCase {

	/**
	 * Formats one entry.
	 *
	 * @param string $text Entry text as written in the changelog.
	 * @return string
	 */
	private function format( string $text ): string {
		return \AWT\Theme\WhatsNew\format_entry_text( $text );
	}

	/**
	 * An address in angle brackets becomes a link showing the address.
	 */
	public function test_address_in_angle_brackets() {
		$this->assertSame(
			'Various accessibility improvements. Details: <a href="https://useawt.com/accessibility/">https://useawt.com/accessibility/</a>',
			$this->format( 'Various accessibility improvements. Details: <https://useawt.com/accessibility/>' )
		);
	}

	/**
	 * A Markdown link becomes a link showing its text.
	 */
	public function test_markdown_link() {
		$this->assertSame(
			'Details on the <a href="https://useawt.com/accessibility/">accessibility page</a>.',
			$this->format( 'Details on the [accessibility page](https://useawt.com/accessibility/).' )
		);
	}

	/**
	 * An ampersand in the address is kept, escaped.
	 */
	public function test_address_with_query() {
		$this->assertSame(
			'<a href="https://example.org/?a=1&#038;b=2">https://example.org/?a=1&amp;b=2</a>',
			$this->format( '<https://example.org/?a=1&b=2>' )
		);
	}

	/**
	 * Only http and https become links, and markup in the text stays text.
	 */
	public function test_nothing_else_becomes_markup() {
		$this->assertSame( '[x](javascript:alert(1))', $this->format( '[x](javascript:alert(1))' ) );
		$this->assertSame( '&lt;script&gt;alert(1)&lt;/script&gt;', $this->format( '<script>alert(1)</script>' ) );
		$this->assertSame( '&lt;ftp://example.org/&gt;', $this->format( '<ftp://example.org/>' ) );
	}

	/**
	 * Bold and code spans still work beside a link.
	 */
	public function test_bold_and_code_still_work() {
		$this->assertSame(
			'<strong>New</strong> <code>level</code> setting, see <a href="https://useawt.com/blocks/accordion/">the Accordion page</a>',
			$this->format( '**New** `level` setting, see [the Accordion page](https://useawt.com/blocks/accordion/)' )
		);
	}
}
