<?php
/**
 * No comment reaches the browser through inline CSS or JavaScript.
 *
 * Everything a browser downloads is comment-free (CLAUDE.md §5), and
 * `npm run check:assets` enforces that for assets/ and build/. The theme also
 * prints CSS and JavaScript straight from PHP: <style> and <script> blocks in
 * admin screens, and strings handed to wp_add_inline_style() and
 * wp_add_inline_script(). None of that passes through the build, so this test
 * renders each of them and fails on any comment it finds. The explanation
 * belongs in a PHP comment beside the code instead.
 *
 * WordPress appends its own `sourceURL` comment to inline code it prints; that
 * is core's and is not checked here.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

/**
 * Renders every inline style and script the theme prints and looks for comments.
 *
 * @coversNothing
 */
class Test_No_Inline_Comments extends WP_UnitTestCase {

	/**
	 * Settings screen tabs and Appearance sub-tabs to render.
	 *
	 * @var array<int, array<string, string>>
	 */
	private const SCREENS = array(
		array(),
		array( 'tab' => 'design-system' ),
		array( 'tab' => 'identity' ),
		array( 'tab' => 'appearance' ),
		array(
			'tab'     => 'appearance',
			'section' => 'header',
		),
		array(
			'tab'     => 'appearance',
			'section' => 'typography',
		),
		array(
			'tab'     => 'appearance',
			'section' => 'links',
		),
		array(
			'tab'     => 'appearance',
			'section' => 'focus',
		),
		array(
			'tab'     => 'appearance',
			'section' => 'colors',
		),
		array( 'tab' => 'navigation' ),
		array( 'tab' => 'custom-css' ),
		array( 'tab' => 'tools' ),
		array( 'tab' => 'welcome' ),
	);

	/**
	 * Sign in as an administrator, since every screen checked is admin-only.
	 */
	public function set_up(): void {
		parent::set_up();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Put the query string back.
	 */
	public function tear_down(): void {
		unset( $_GET['tab'], $_GET['section'] );
		parent::tear_down();
	}

	/**
	 * Every tab of the Settings screen.
	 */
	public function test_settings_screen_prints_no_comments(): void {
		foreach ( self::SCREENS as $query ) {
			unset( $_GET['tab'], $_GET['section'] );
			foreach ( $query as $key => $value ) {
				$_GET[ $key ] = $value;
			}
			ob_start();
			\AWT\Theme\AdminPage\render_page();
			$html = (string) ob_get_clean();

			$this->assertStringContainsString( 'awt-settings-page', $html, 'the screen rendered' );
			$this->assert_no_comments( $this->inline_blocks( $html ), 'Settings screen ' . http_build_query( $query ) );
		}
	}

	/**
	 * Parts that no tab reaches by default: the colors audit (kept for AWT
	 * Premium), each wizard step, and the dashboard widget's style.
	 */
	public function test_screens_off_the_default_path_print_no_comments(): void {
		$renderers = array(
			'\AWT\Theme\AdminPage\render_tab_colors',
			'\AWT\Theme\Wizard\render_step_0',
			'\AWT\Theme\Wizard\render_step_1',
			'\AWT\Theme\Wizard\render_step_2',
			'\AWT\Theme\Wizard\render_step_3',
			'\AWT\Theme\Wizard\render_step_4',
			'\AWT\Theme\Wizard\render_step_5',
			'\AWT\Theme\DashboardWidget\style',
		);
		foreach ( $renderers as $renderer ) {
			ob_start();
			call_user_func( $renderer );
			$html = (string) ob_get_clean();
			$this->assert_no_comments( $this->inline_blocks( $html ), $renderer );
		}
	}

	/**
	 * Inline code handed to WordPress to print.
	 */
	public function test_enqueued_inline_code_carries_no_comments(): void {
		\AWT\Theme\AdminPage\enqueue_assets( \AWT\Theme\AdminPage\PAGE_HOOK );
		\AWT\Theme\WhatsNew\enqueue_indicator_style();

		$css = array_merge(
			(array) wp_styles()->get_data( 'awt-theme-settings-admin', 'after' ),
			(array) wp_styles()->get_data( 'awt-theme-whats-new-indicator', 'after' ),
			array( \AWT\Theme\AdminBar\styles(), \AWT\Theme\HeaderPresets\picker_styles() )
		);
		$this->assert_no_comments(
			array_map(
				static fn( $code ) => array( 'style', (string) $code ),
				$css
			),
			'inline CSS'
		);

		$js = (array) wp_scripts()->get_data( 'awt-theme-settings-admin', 'after' );

		// The editor canvas scope script rides on core's wp-blocks handle,
		// whose other inline entries are core's.
		do_action( 'enqueue_block_editor_assets' );
		foreach ( (array) wp_scripts()->get_data( 'wp-blocks', 'after' ) as $code ) {
			if ( str_contains( (string) $code, 'editor-canvas' ) ) {
				$js[] = $code;
			}
		}
		$this->assertNotEmpty( $js, 'the inline scripts were found' );
		$this->assert_no_comments(
			array_map(
				static fn( $code ) => array( 'script', (string) $code ),
				$js
			),
			'inline JavaScript'
		);
	}

	/**
	 * The bodies of inline <style> and <script> elements, without src.
	 *
	 * @param string $html Markup.
	 * @return array<int, array{0: string, 1: string}> Tag name and body.
	 */
	private function inline_blocks( string $html ): array {
		preg_match_all( '#<(style|script)\b([^>]*)>(.*?)</\1>#is', $html, $m, PREG_SET_ORDER );
		$blocks = array();
		foreach ( $m as $match ) {
			if ( str_contains( $match[2], 'src=' ) ) {
				continue;
			}
			$blocks[] = array( strtolower( $match[1] ), $match[3] );
		}
		return $blocks;
	}

	/**
	 * Fail on any block comment, and on line comments in JavaScript.
	 *
	 * @param array<int, array{0: string, 1: string}> $blocks Tag name and body.
	 * @param string                                  $where  Where they came from.
	 */
	private function assert_no_comments( array $blocks, string $where ): void {
		foreach ( $blocks as list( $tag, $code ) ) {
			// Blank string literals first, so "//" inside a URL or a message
			// is not mistaken for a comment.
			$bare = (string) preg_replace( '/"(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'|`(?:\\\\.|[^`\\\\])*`/s', '""', $code );
			$bare = (string) preg_replace( '#/\*\#\s*sourceURL=[^*]*\*/|//\#\s*sourceURL=\S*#', '', $bare );

			$this->assertDoesNotMatchRegularExpression( '#/\*#', $bare, "$where: a /* comment in inline $tag" );
			if ( $tag === 'script' ) {
				$this->assertDoesNotMatchRegularExpression( '#(^|[^:\\\\])//#m', $bare, "$where: a // comment in inline script" );
			}
		}
	}
}
