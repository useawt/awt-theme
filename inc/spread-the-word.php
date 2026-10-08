<?php
/**
 * Asking site owners to tell others about AWT.
 *
 * Two quiet places, both on screens an owner opens anyway: the What's new
 * tab, under its heading, and the top of AWT's dashboard box. Never a notice
 * of its own, never on the front end, nothing to dismiss and nothing fetched:
 * two plain links, one to the GitHub repository and one to the page on
 * useawt.com that holds the share text.
 *
 * On What's new it comes after the notes that ask for action (an edited
 * template part, the "Needs your attention" box), so a request for help never
 * pushes a security note down the screen.
 *
 * The copy says AWT is free and open source, which is only true of the free
 * build. A build that is not the free one turns it off with the
 * `awt_spread_the_word` filter.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\SpreadTheWord;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The repository the stars go to: one, so they don't split across two. */
const GITHUB_URL = 'https://github.com/useawt/awt-theme';

/** The page with the share text and the other ways to help. */
const PAGE_URL = 'https://useawt.com/spread-the-word/';

/**
 * Whether to show the ask.
 *
 * @return bool True unless the `awt_spread_the_word` filter says otherwise.
 */
function enabled(): bool {
	/**
	 * Filters whether AWT's admin screens ask the site owner to tell others
	 * about AWT.
	 *
	 * @param bool $show True to show the ask on What's new and the dashboard.
	 */
	return (bool) apply_filters( 'awt_spread_the_word', true );
}

/** The card on the What's new tab. */
function render_card(): void {
	if ( ! enabled() ) {
		return;
	}
	?>
	<div class="awt-share-ask">
		<svg class="awt-share-ask__icon" width="24" height="24" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20.5s-7.5-4.6-7.5-10.2A4.3 4.3 0 0 1 12 7.6a4.3 4.3 0 0 1 7.5 2.7c0 5.6-7.5 10.2-7.5 10.2z" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"/></svg>
		<div>
			<h3><?php esc_html_e( 'Help more WordPress sites become accessible', 'awt' ); ?></h3>
			<p><?php esc_html_e( 'AWT is free and open source. If it helps you, tell others about it. A post, a review or a word to a colleague helps the next person find it.', 'awt' ); ?></p>
			<p class="awt-share-ask__links">
				<a class="button" href="<?php echo esc_url( GITHUB_URL ); ?>"><?php esc_html_e( 'Star AWT on GitHub', 'awt' ); ?></a>
				<a href="<?php echo esc_url( PAGE_URL ); ?>"><?php esc_html_e( 'More ways to help', 'awt' ); ?></a>
			</p>
		</div>
	</div>
	<?php
}

/** The line at the top of AWT's dashboard box. */
function render_line(): void {
	if ( ! enabled() ) {
		return;
	}
	printf(
		'<p class="awt-dash__ask">%s</p>',
		wp_kses(
			sprintf(
				/* translators: 1: link to the useawt.com page on sharing AWT. 2: link to AWT's GitHub repository. Keep the <a> tags around the words that are links. */
				__( 'Like AWT? Help more sites become accessible: <a href="%1$s">tell others about it</a> or <a href="%2$s">star it on GitHub</a>.', 'awt' ),
				esc_url( PAGE_URL ),
				esc_url( GITHUB_URL )
			),
			array( 'a' => array( 'href' => true ) )
		)
	);
}
