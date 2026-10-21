<?php
/**
 * Post lists have no "Read more" link.
 *
 * Every post in the archive and blog templates ended with a "Read more" link
 * that did not name its post, so a screen reader's links list showed a column
 * of identical links (outside accessibility review, 2026-10-21). The post
 * title above it already links to the same post, so the templates no longer
 * add one.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

/**
 * Checks the post excerpt in the theme's post-list templates.
 */
class Test_Post_List_Links extends WP_UnitTestCase {

	/**
	 * Every block in a template, nested ones included.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return array[]
	 */
	private function flatten( array $blocks ): array {
		$all = array();
		foreach ( $blocks as $block ) {
			$all[] = $block;
			$all   = array_merge( $all, $this->flatten( $block['innerBlocks'] ) );
		}
		return $all;
	}

	/**
	 * The post-list templates.
	 *
	 * @return array[]
	 */
	public function templates(): array {
		return array(
			'archive' => array( 'archive' ),
			'home'    => array( 'home' ),
			'index'   => array( 'index' ),
			'search'  => array( 'search' ),
		);
	}

	/**
	 * Each post's excerpt adds no more link; its title is the link.
	 *
	 * @dataProvider templates
	 *
	 * @param string $slug Template slug.
	 */
	public function test_excerpt_has_no_more_link( string $slug ) {
		$markup = (string) file_get_contents( get_template_directory() . '/templates/' . $slug . '.html' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a theme file.
		$blocks = $this->flatten( parse_blocks( $markup ) );

		$excerpts = array_filter( $blocks, static fn( $b ) => 'core/post-excerpt' === $b['blockName'] );
		$titles   = array_filter( $blocks, static fn( $b ) => 'core/post-title' === $b['blockName'] );
		$this->assertNotEmpty( $excerpts );
		foreach ( $excerpts as $excerpt ) {
			$this->assertArrayNotHasKey( 'moreText', $excerpt['attrs'] );
		}
		foreach ( $titles as $title ) {
			$this->assertTrue( ! empty( $title['attrs']['isLink'] ), 'The post title links to the post.' );
		}
	}
}
