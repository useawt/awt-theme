<?php
/**
 * AWT theme bootstrap (Stage 1).
 *
 * Responsibilities:
 *   - Carbon stylesheet enqueue (foundation; per-block CSS comes from awt-blocks).
 *   - Visitor color-scheme infrastructure: pre-paint inline script, scope class
 *     application via body_class + admin_body_class, exposure of theme.json
 *     ui-shell parameters to JS.
 *   - Theme support flags.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const AWT_THEME_VERSION = '2026.01.0-stage1';

require_once __DIR__ . '/inc/settings.php';
// §A Design system layer — interface + registry + Carbon. Loaded before the
// contrast / header-preset / style-variation files, which are thin shims
// delegating to Registry::get_active().
require_once __DIR__ . '/inc/design-system/interface.php';
require_once __DIR__ . '/inc/design-system/carbon.php';
require_once __DIR__ . '/inc/design-system/registry.php';
require_once __DIR__ . '/inc/contrast.php';
require_once __DIR__ . '/inc/header-presets.php';
require_once __DIR__ . '/inc/style-variations.php';
require_once __DIR__ . '/inc/admin-settings-page.php';
require_once __DIR__ . '/inc/breadcrumb-auto-emit.php';
require_once __DIR__ . '/inc/welcome-wizard.php';
// §A: block-inserter filter + design-system-aware pattern registration.
require_once __DIR__ . '/inc/inserter-filter.php';
require_once __DIR__ . '/inc/pattern-registry.php';
// §4: per-page document-language override (meta + language_attributes filter).
require_once __DIR__ . '/inc/page-language.php';
// What's new — release-notes panel + menu indicator (Stage 1 spec §6).
require_once __DIR__ . '/inc/whats-new.php';

// Register Carbon early so the design system is resolvable by everything
// that runs on `init` (block + pattern registration, the inserter filter).
add_action( 'after_setup_theme', '\\AWT\\Theme\\DesignSystem\\bootstrap', 5 );

/**
 * Resolve the active ui-shell settings block from theme.json
 * (settings.custom.ui-shell), including any style-variation overrides.
 *
 * The block holds exactly two keys, and both have a reader:
 *
 *   - `colorScheme` — read here, by color_scheme_settings().
 *   - `themeScope`  — the declared light/dark variant pairing. Style variations
 *     restate it; theme_scopes() carries the same default, and explains below
 *     why it cannot read this key back for a user-applied variation.
 *
 * Nothing else belongs in it. `header`, `sideNav`, `content` and `footer` blocks
 * lived here until 2026-07-30 and were read by nothing at all, which made
 * theme.json advertise knobs (a configurable header height and position, a
 * side-nav width, a content max-width) that turning did nothing to. The header
 * is Carbon's UI Shell header — `position: fixed`, 3rem tall — and that is a
 * design-system invariant, not a site setting. Add a key here only alongside
 * the code that reads it.
 *
 * Cached per request — repeated calls return the same array reference.
 *
 * @return array
 */
function ui_shell_settings(): array {
	static $cache = null;
	if ( $cache !== null ) {
		return $cache;
	}
	$data   = function_exists( 'wp_get_global_settings' ) ? wp_get_global_settings() : array();
	$custom = $data['custom'] ?? array();
	$cache  = is_array( $custom['ui-shell'] ?? null ) ? $custom['ui-shell'] : array();
	return $cache;
}

/**
 * Pull the active light + dark Carbon variant slugs.
 *
 * The applied style variation is stored as a `wp_global_styles` post under
 * `settings.custom.ui-shell.themeScope`. BUT WordPress strips `settings.custom.*`
 * from *user-origin* global styles during sanitization
 * (`WP_Theme_JSON_Resolver::get_user_data()` returns an empty `custom.ui-shell`),
 * so `wp_get_global_settings()` only ever reports the theme.json *default*
 * (`white` / `g100`) regardless of which variation was applied. Reading the scope
 * from there meant every non-default variation (g10 light, g90 dark) silently had
 * no effect on the front-end `cds--{scope}` body class.
 *
 * Source of truth instead: the applied variation slug in `awt_settings`
 * (`welcome.choices.styleVariation`), which both apply-paths (welcome wizard +
 * Appearance/Carbon tab) write and which is NOT subject to global-styles
 * sanitization. The slug encodes both scopes as `{light}-plus-{dark}`. Falls back
 * to the theme.json default when no variation has been applied (fresh install).
 *
 * @return array{light: string, dark: string}
 */
function theme_scopes(): array {
	$light = 'white';
	$dark  = 'g100';

	$slug = function_exists( '\\AWT\\Theme\\Settings\\get' )
		? (string) ( \AWT\Theme\Settings\get( 'welcome.choices.styleVariation' ) ?? '' )
		: '';

	if ( $slug !== '' && strpos( $slug, '-plus-' ) !== false ) {
		[ $maybe_light, $maybe_dark ] = explode( '-plus-', $slug, 2 );
		if ( in_array( $maybe_light, array( 'white', 'g10' ), true ) ) {
			$light = $maybe_light;
		}
		if ( in_array( $maybe_dark, array( 'g90', 'g100' ), true ) ) {
			$dark = $maybe_dark;
		}
	}

	return array(
		'light' => $light,
		'dark'  => $dark,
	);
}

/**
 * Default scheme + honor-system-preference + allow-visitor-override flags.
 *
 * @return array{default: string, honorSystemPreference: bool, allowVisitorOverride: bool}
 */
function color_scheme_settings(): array {
	$settings = ui_shell_settings();
	$cs       = $settings['colorScheme'] ?? array();
	$default  = ( $cs['default'] ?? 'light' ) === 'dark' ? 'dark' : 'light';
	$honor    = ! isset( $cs['honorSystemPreference'] ) || ! empty( $cs['honorSystemPreference'] );
	$allow    = ! isset( $cs['allowVisitorOverride'] ) || ! empty( $cs['allowVisitorOverride'] );

	// AWT Settings → Carbon → Site appearance overrides the theme.json default.
	// 'light' / 'dark' PIN the site's active scheme: the pin must win over the
	// visitor's system preference AND any stored visitor-toggle cookie, so we
	// force BOTH honorSystemPreference and allowVisitorOverride off. (Leaving
	// allowVisitorOverride on let a stale `awt_color_scheme` cookie flip the
	// page back in the pre-paint script — so "Always dark" appeared to do
	// nothing for anyone who'd ever used the toggle.) With override off, the
	// color-scheme toggle block also self-removes, which is correct: a manual
	// toggle is meaningless when the admin has forced the scheme. 'default'
	// leaves theme.json behavior intact.
	$site = function_exists( '\\AWT\\Theme\\Settings\\get' )
		? (string) ( \AWT\Theme\Settings\get( 'site.colorScheme' ) ?? 'default' )
		: 'default';
	if ( $site === 'light' || $site === 'dark' ) {
		$default = $site;
		$honor   = false;
		$allow   = false;
	}

	return array(
		'default'               => $default,
		'honorSystemPreference' => $honor,
		'allowVisitorOverride'  => $allow,
	);
}

/**
 * Used by awt/color-scheme-toggle's render.php to decide whether to self-remove.
 */
function color_scheme_allow_visitor_override(): bool {
	return (bool) color_scheme_settings()['allowVisitorOverride'];
}

/**
 * Resolve the active scheme (light/dark) for the current request from the
 * cookie when the visitor has set one. Returns the site default otherwise.
 * Used only as the server-side best guess; the pre-paint script reconciles on
 * the client before first paint.
 *
 * @return string 'light' | 'dark'
 */
function active_scheme_server_guess(): string {
	$settings      = color_scheme_settings();
	$cookie_scheme = isset( $_COOKIE['awt_color_scheme'] )
		? sanitize_key( wp_unslash( $_COOKIE['awt_color_scheme'] ) )
		: '';
	if ( $settings['allowVisitorOverride']
		&& in_array( $cookie_scheme, array( 'light', 'dark' ), true )
	) {
		return $cookie_scheme;
	}
	return $settings['default'];
}

add_action(
	'after_setup_theme',
	static function (): void {
		add_theme_support( 'wp-block-styles' );
		add_theme_support( 'editor-styles' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
		add_theme_support( 'title-tag' );

		// Suppress WordPress core's auto-injected block-template skip-link.
		// WP 6.4+ adds a JS-generated `<a class="skip-link" id="wp-skip-link">`
		// at wp_footer for every block theme. AWT ships its own canonical
		// skip-link block (`awt/skip-link`) in default header.html and in
		// every header preset, so WP's injection produces a duplicate —
		// two skip-to-content announcements for screen-reader users.
		//
		// WP's function tries to be smart by checking the template content
		// for `<a class="skip-link screen-reader-text"` and skipping if
		// it's already there. Our skip-link uses Carbon's `cds--skip-to-content`
		// class instead, so WP's check doesn't recognize it. Cleanest path
		// is to remove the action entirely.
		//
		// Both function names handled: current is wp_enqueue_block_template_skip_link
		// (WP 6.4+); the_block_template_skip_link is the older name we may
		// see in older installs.
		remove_action( 'wp_footer', 'wp_enqueue_block_template_skip_link' );
		remove_action( 'wp_footer', 'the_block_template_skip_link' );

		// Load the same Carbon foundation + AWT theme overrides into the
		// block-editor canvas as the front-end uses. Without this, each block's
		// edit.js preview drifts from the rendered output (Carbon classes apply
		// no styles in the editor, so blocks fall back to whatever inline styles
		// the edit.js authored). With this, the editor iframe and front-end
		// share a single CSS source of truth — buttons/radios/tags/accordions
		// look the same in both contexts.
		add_editor_style(
			array(
				'assets/css/foundation.min.css',
				'assets/css/theme.css',
				// Mirrors Carbon's `.cds--white` variable declarations onto
				// `body.editor-styles-wrapper` so Carbon variables resolve in
				// the editor iframe without depending on a runtime body-class
				// injection (which is racy with the iframe's async mount).
				'assets/css/editor-scope.css',
			)
		);
	}
);

/**
 * Register AWT pattern categories so theme-provided patterns surface under a
 * dedicated heading in the editor's pattern inserter.
 */
add_action(
	'init',
	static function (): void {
		if ( ! function_exists( 'register_block_pattern_category' ) ) {
			return;
		}
		register_block_pattern_category(
			'awt-section',
			array( 'label' => __( 'AWT — Sections', 'awt' ) )
		);
	}
);

/**
 * Dev-only pattern cache invalidation.
 *
 * `WP_Theme::get_block_patterns()` caches the patterns/ directory scan results
 * across requests (see WP core wp-includes/block-patterns.php). When a new
 * pattern file is added to /patterns/, the cache survives and WP keeps serving
 * the old list — new patterns never appear in the inserter until the cache is
 * manually invalidated.
 *
 * In production this is the right behavior (theme distribution is stable).
 * In development it's a constant friction point. We invalidate the cache on
 * every page load when WP_DEBUG is on, which mirrors the modify-and-reload
 * workflow expected during theme development.
 *
 * Cost: one extra filesystem stat per request (the cache rebuild). Negligible.
 */
add_action(
	'init',
	static function (): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}
		$theme = wp_get_theme();
		if ( $theme && method_exists( $theme, 'delete_pattern_cache' ) ) {
			$theme->delete_pattern_cache();
		}
	}
);

/**
 * Apply the active Carbon scope class to <body> based on the server-side guess.
 * The pre-paint script may swap it client-side before first paint.
 *
 * Also carries the link-underline classes (AWT Settings → Links), which the
 * D6 rules in `theme.css` key off region by region, and the button focus class
 * (AWT Settings → Focus), which D2 keys off.
 */
add_filter(
	'body_class',
	static function ( array $classes ): array {
		$scopes    = theme_scopes();
		$active    = active_scheme_server_guess();
		$variant   = $active === 'dark' ? $scopes['dark'] : $scopes['light'];
		$classes[] = 'cds--' . $variant;
		if ( function_exists( '\\AWT\\Theme\\Settings\\link_underline_body_classes' ) ) {
			$classes = array_merge( $classes, \AWT\Theme\Settings\link_underline_body_classes() );
		}
		if ( function_exists( '\\AWT\\Theme\\Settings\\button_focus_body_classes' ) ) {
			$classes = array_merge( $classes, \AWT\Theme\Settings\button_focus_body_classes() );
		}
		return $classes;
	}
);

/**
 * Block-editor iframe <body> needs the scope class too.
 */
add_filter(
	'admin_body_class',
	static function ( string $classes ): string {
		$scopes = theme_scopes();
		return trim( $classes . ' cds--' . $scopes['light'] );
	}
);

/**
 * Retitle the default page template "Pages" → "Page" so the editor's Template
 * picker reads as a set of single-page choices: "Page" (default, shows the
 * title) and "Page without title".
 */
add_filter(
	'default_template_types',
	static function ( array $types ): array {
		if ( isset( $types['page'] ) ) {
			$types['page']['title'] = _x( 'Page', 'template name', 'awt' );
		}
		return $types;
	}
);

/**
 * "Header color" setting (AWT Settings → Carbon → Header color). When pinned to
 * light or dark, force the active variation's corresponding Carbon scope class
 * onto the `.cds--header` wrapper. That locally redefines --cds-* for the header
 * subtree, so the header keeps its chosen appearance regardless of the page
 * scope (the color-scheme toggle only swaps the <body> class, never the header).
 *
 * Any scope class ALREADY on the header is stripped first. Adding without
 * stripping is not enough: the four scope classes carry equal specificity, so
 * the winner is whichever Carbon defines last in the stylesheet (g100), not
 * whichever we inject. A header that had picked up `cds--g100` by hand in the
 * Site Editor therefore stayed dark under every one of the three choices, with
 * no hint why (2026-08-06, found on the marketing site). Pinning is the
 * authoritative answer to "what color is the header" — it replaces, not adds.
 *
 * 'default' is still a no-op: it means "we don't manage the header's scope", so
 * a scope class set deliberately in the Site Editor survives. The setting's
 * help text says so.
 */
add_filter(
	'render_block',
	static function ( string $html, array $block ): string {
		$class = (string) ( $block['attrs']['className'] ?? '' );
		// Only the header wrapper (class token `cds--header`), not its
		// `cds--header__*` sub-elements or any other block.
		if ( ! in_array( 'cds--header', preg_split( '/\s+/', trim( $class ) ), true ) ) {
			return $html;
		}

		$scheme = '';
		if ( function_exists( '\\AWT\\Theme\\Settings\\get' ) ) {
			$scheme = (string) ( \AWT\Theme\Settings\get( 'header.colorScheme' ) ?? 'default' );
		}
		if ( $scheme !== 'light' && $scheme !== 'dark' ) {
			return $html;
		}

		$scopes      = theme_scopes();
		$scope_class = 'cds--' . ( $scheme === 'dark' ? $scopes['dark'] : $scopes['light'] );

		// Rewrite the first <header …> tag's class list: drop every Carbon scope
		// class already there, then add ours. Token-wise, so `cds--g10` never
		// half-matches `cds--g100`.
		return (string) preg_replace_callback(
			'/(<header\b[^>]*\bclass=")([^"]*)(")/',
			static function ( array $m ) use ( $scope_class ): string {
				$tokens = preg_split( '/\s+/', trim( $m[2] ), -1, PREG_SPLIT_NO_EMPTY );
				$tokens = array_values(
					array_filter(
						is_array( $tokens ) ? $tokens : array(),
						static fn( string $t ): bool => ! in_array(
							$t,
							array( 'cds--white', 'cds--g10', 'cds--g90', 'cds--g100' ),
							true
						)
					)
				);
				array_unshift( $tokens, $scope_class );
				return $m[1] . implode( ' ', $tokens ) . $m[3];
			},
			$html,
			1
		);
	},
	10,
	2
);

/**
 * Stylesheet enqueue for front-end + block-editor iframe.
 *
 * Stage 1 still ships the pre-built Carbon CSS in full. CSS tree-shaking
 * (§6 phase-1 spec) is tracked work; until then both contexts get the full
 * bundle so editor and front-end visuals match.
 */
// NOTE: Carbon + theme CSS is intentionally NOT loaded via `enqueue_block_assets`.
// That hook injects styles into the block editor's MAIN document (chrome), not
// just the canvas iframe, so Carbon's `body`/reset rules (line-height, etc.)
// would override WordPress's own admin/editor UI — a regression users see as
// "everything in wp-admin got a tighter line-height." The two contexts we
// actually want are covered without touching the editor chrome:
// • Front end      → the `wp_enqueue_scripts` enqueue below.
// • Editor CANVAS  → `add_editor_style()` (see after_setup_theme above), the
// WP-canonical mechanism that scopes styles to the iframe.
// Never reintroduce an `enqueue_block_assets` / `admin_enqueue_scripts` load of
// the full Carbon/theme bundle — keep our CSS off WordPress-supplied UI.

// Inline per-block styles into the document instead of loading each as a
// render-blocking request. WordPress inlines block styles that carry a
// 'path' (block.json styles do) up to this byte budget; the default 20 KB
// covers only 2-3 AWT blocks. 150 KB raw (~20 KB gz inside the HTML)
// inlines every block on a typical page — Lighthouse showed the request
// chain of separate per-block stylesheets was the main LCP cost on
// HTTP/1.1 hosts.
add_filter(
	'styles_inline_size_limit',
	static fn(): int => 150000
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$carbon_path = get_template_directory() . '/assets/css/foundation.min.css';
		$theme_path  = get_template_directory() . '/assets/css/theme.css';

		wp_enqueue_style( 'awt-carbon', get_template_directory_uri() . '/assets/css/foundation.min.css', array(), (string) filemtime( $carbon_path ) );
		wp_enqueue_style( 'awt-theme', get_template_directory_uri() . '/assets/css/theme.css', array( 'awt-carbon' ), (string) filemtime( $theme_path ) );
	}
);

/**
 * Emit the pre-paint script in <head> as the very first script so it runs
 * before paint. Reads cookie → prefers-color-scheme → site default, applies
 * the matching cds--{variant} scope class to <body>, and sets
 * data-awt-color-scheme on <html>.
 *
 * Per phase-1 spec §1 "Visitor color-scheme behavior → Performance": hand-
 * written, dependency-free, ≤ 1 KB (size enforced in CI in production builds).
 *
 * <body> does not exist yet while this runs in <head>, so the scope class is
 * applied by the companion `wp_body_open` script below — the earliest moment
 * <body> exists, and still before any content has been parsed, so before any
 * contentful paint. The DOMContentLoaded listener stays as a fallback for
 * documents that never fire `wp_body_open` (a custom template, a plugin that
 * builds its own <body>); it is idempotent, so running twice is harmless.
 */
add_action(
	'wp_head',
	static function (): void {
		$scopes = theme_scopes();
		$cs     = color_scheme_settings();
		// JSON_HEX_TAG keeps < / > out of the encoded values so nothing can
		// close the inline script element early.
		$variants = wp_json_encode( $scopes, JSON_HEX_TAG );

		$honour_pref = $cs['honorSystemPreference'] ? 'true' : 'false';
		$allow_user  = $cs['allowVisitorOverride'] ? 'true' : 'false';
		$default     = wp_json_encode( $cs['default'], JSON_HEX_TAG );

		// Pre-paint script: ≤ 1 KB minified. Inline so it executes before
		// the first stylesheet has parsed.
		echo '<script id="awt-color-scheme-prepaint">(function(){var v=' . $variants . ',H=' . $honour_pref . ',A=' . $allow_user . ',D=' . $default . ';'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- inline script: wp_json_encode( ..., JSON_HEX_TAG ) values + literal true/false strings.
		echo 'window.AWT_THEME_SCOPES=v;';
		echo 'var c="";if(A){var m=document.cookie.match(/(?:^|; )awt_color_scheme=(light|dark|auto)/);if(m){c=m[1]}}';
		echo 'var s=D;if(A&&(c==="light"||c==="dark")){s=c}else if(H){try{if(window.matchMedia&&matchMedia("(prefers-color-scheme: dark)").matches){s="dark"}}catch(e){}}';
		echo 'var html=document.documentElement;html.setAttribute("data-awt-color-scheme",s);';
		echo 'var apply=window.AWT_APPLY_SCHEME=function(){var b=document.body;if(!b){return false}["white","g10","g90","g100"].forEach(function(x){b.classList.remove("cds--"+x)});b.classList.add("cds--"+(s==="dark"?v.dark:v.light));return true};';
		echo 'if(!apply()){document.addEventListener("DOMContentLoaded",apply)}})();</script>';
	},
	0
);

/**
 * Apply the resolved Carbon scope class to <body> the instant <body> opens.
 *
 * The server renders a best-guess scope class (`active_scheme_server_guess()`),
 * which is exact whenever the visitor has an `awt_color_scheme` cookie. It
 * cannot be exact for a first-time visitor with no cookie, because
 * `prefers-color-scheme` is a client-only fact — so a visitor whose device is
 * set to dark was served the light scope and only got the dark one at
 * DOMContentLoaded, which on a throttled connection lands well after first
 * paint. That is the flash-of-wrong-theme this closes: reconciling here means
 * the correct scope is in place before any page content has been parsed, let
 * alone painted.
 *
 * Priority 0 so it runs ahead of the Custom code → after body open injection.
 */
add_action(
	'wp_body_open',
	static function (): void {
		echo '<script id="awt-color-scheme-scope">window.AWT_APPLY_SCHEME&&window.AWT_APPLY_SCHEME();</script>';
	},
	0
);

/**
 * Front-end emission of AWT Settings → Typography size multiplier.
 *
 * Applies `font-size: calc(100% * X)` at the html root so every rem-based
 * Carbon token scales proportionally. Compact = 0.875×, Default = 1.0×,
 * Comfortable = 1.125×. Inline because it's tiny (one CSS declaration)
 * and needs to be in the cascade before Carbon's stylesheet to avoid a
 * flash of unscaled type.
 *
 * Priority 1 so it lands very early in `<head>` — even before the
 * color-scheme pre-paint script (which only sets classes, not styles).
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! function_exists( '\\AWT\\Theme\\Settings\\get' ) ) {
			return;
		}
		$scale = (float) \AWT\Theme\Settings\get( 'typography.sizeScale' );
		if ( $scale === 1.0 || $scale <= 0 ) {
			return; // Default — no override needed.
		}
		echo '<style id="awt-type-scale">html{font-size:calc(100% * ' . esc_html( (string) $scale ) . ')}</style>';
	},
	1
);

/**
 * Front-end emission of AWT Settings → Custom CSS field.
 *
 * Priority 999 so it lands at the very end of `<head>`, after every
 * other stylesheet and inline style — ensures site owner overrides win
 * the cascade fight against Carbon + theme.css without authors having
 * to write higher-specificity selectors.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! function_exists( '\\AWT\\Theme\\Settings\\get' ) ) {
			return;
		}
		$css = (string) \AWT\Theme\Settings\get( 'customCss' );
		if ( trim( $css ) === '' ) {
			return;
		}
		// Raw output — the spec's §5 warning block puts the responsibility
		// for safe CSS on the site owner. wp_strip_all_tags would strip
		// `<` characters that are valid in CSS selectors (attribute
		// selectors, child combinators), so we don't sanitize here.
		echo '<style id="awt-custom-css">' . $css . '</style>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- deliberately raw: site-owner Custom CSS feature (capability-gated save path); see spec §5.
	},
	999
);

/**
 * Front-end emission of AWT Settings → Custom code → "Before </head>".
 *
 * Priority 99 per spec — fires after other plugins on the same hook so
 * AWT-injected code doesn't break plugins that expect a settled head.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! function_exists( '\\AWT\\Theme\\Settings\\get' ) ) {
			return;
		}
		$code = (string) \AWT\Theme\Settings\get( 'customCode.head' );
		if ( trim( $code ) === '' ) {
			return;
		}
		echo "\n<!-- AWT custom code: head -->\n" . $code . "\n<!-- /AWT custom code: head -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- deliberately raw: site-owner Custom code feature (capability-gated save path); see spec §5.
	},
	99
);

add_action(
	'wp_body_open',
	static function (): void {
		if ( ! function_exists( '\\AWT\\Theme\\Settings\\get' ) ) {
			return;
		}
		$code = (string) \AWT\Theme\Settings\get( 'customCode.afterBodyOpen' );
		if ( trim( $code ) === '' ) {
			return;
		}
		echo "\n<!-- AWT custom code: after body open -->\n" . $code . "\n<!-- /AWT custom code: after body open -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- deliberately raw: site-owner Custom code feature (capability-gated save path); see spec §5.
	},
	99
);

add_action(
	'wp_footer',
	static function (): void {
		if ( ! function_exists( '\\AWT\\Theme\\Settings\\get' ) ) {
			return;
		}
		$code = (string) \AWT\Theme\Settings\get( 'customCode.beforeBodyClose' );
		if ( trim( $code ) === '' ) {
			return;
		}
		echo "\n<!-- AWT custom code: before body close -->\n" . $code . "\n<!-- /AWT custom code: before body close -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- deliberately raw: site-owner Custom code feature (capability-gated save path); see spec §5.
	},
	99
);

/**
 * Expose ui-shell config + active variants to the editor iframe so blocks
 * (notably awt/color-scheme-toggle) can render preview state accurately.
 */
add_filter(
	'block_editor_settings_all',
	static function ( array $settings ): array {
		$scopes     = theme_scopes();
		$existing   = $settings['styles'] ?? array();
		$existing[] = array(
			'css'            => 'body.editor-styles-wrapper { background: var(--cds-background, #fff); color: var(--cds-text-primary, #161616); }',
			'__unstableType' => 'theme',
		);

		// AWT Settings → Custom CSS into the editor canvas. On the front end the
		// Custom CSS is a <style> in the head and its colour overrides are keyed
		// to the scope classes (`.cds--white` … `.cds--g100`). The canvas differs:
		// • WP's editor-style transform re-roots Carbon's own body-level token
		// defaults to `body.editor-styles-wrapper` (0,1,1), out-ranking a
		// plain `.cds--white` (0,1,0) override; and
		// • the canvas body doesn't reliably carry a `cds--*` scope class.
		// So re-target the ACTIVE scope's selectors to `body.editor-styles-wrapper`
		// (the always-present canvas root — the same selector WP uses for the
		// defaults) and append AFTER the reset above so it wins by source order.
		// The inactive scope family is left as-is: those selectors match nothing
		// in the canvas. Injected via `styles` (not a wp_head-style <style>) so it
		// survives the editor's canvas re-renders. Unlike the front end, only the
		// active scheme's colours apply — the editor previews one scheme at a time.
		$site_cs    = function_exists( '\\AWT\\Theme\\Settings\\get' ) ? (string) \AWT\Theme\Settings\get( 'site.colorScheme' ) : 'default';
		$variant    = $site_cs === 'dark' ? $scopes['dark'] : $scopes['light'];
		$custom_css = function_exists( '\\AWT\\Theme\\Settings\\get' ) ? (string) \AWT\Theme\Settings\get( 'customCss' ) : '';
		if ( trim( $custom_css ) !== '' ) {
			$active     = in_array( $variant, array( 'g90', 'g100' ), true )
				? array( '.cds--g90', '.cds--g100' )   // Dark family active.
				: array( '.cds--white', '.cds--g10' ); // Light family active.
			$patterns   = array_map(
				static function ( string $sel ): string {
					return '/' . preg_quote( $sel, '/' ) . '\b/';
				},
				$active
			);
			$editor_css = preg_replace( $patterns, 'body.editor-styles-wrapper', $custom_css );
			if ( is_string( $editor_css ) && trim( $editor_css ) !== '' ) {
				$existing[] = array(
					'css'            => $editor_css,
					'__unstableType' => 'user',
				);
			}
		}

		// Link underlines (D6) in the canvas. The canvas <body> carries
		// `editor-styles-wrapper` and never our `awt-underline-*` classes, so
		// theme.css's region rules cannot match there and every region would
		// fall back to its no-underline reset. This re-roots the decision onto
		// the canvas body — one short declaration per region, because
		// theme.css keeps the underline itself behind an inherited custom
		// property. A header or footer block is edited in the Site Editor,
		// where the canvas is the only preview there is, so all five regions
		// matter here and not just post content.
		if ( function_exists( '\\AWT\\Theme\\Settings\\link_underline_editor_css' ) ) {
			$underline_css = \AWT\Theme\Settings\link_underline_editor_css();
			if ( $underline_css !== '' ) {
				$existing[] = array(
					'css'            => $underline_css,
					'__unstableType' => 'theme',
				);
			}
		}

		$settings['styles']         = $existing;
		$settings['awtThemeScopes'] = $scopes;
		return $settings;
	}
);

/**
 * Add the active Carbon scope class (cds--white / g10 / g90 / g100) to the
 * editor canvas iframe's body so Carbon's class-scoped CSS variables resolve
 * inside the editor. Without this, foundation.min.css loads but every `.cds--*`
 * variable declaration is gated behind a scope class that the editor body
 * doesn't have — so the editor preview shows unstyled Carbon fallbacks
 * (transparent buttons, missing tag colors, etc.) and drifts from the
 * front-end render.
 *
 * Implementation: a MutationObserver watches for the editor-canvas iframe
 * being created (it appears asynchronously after the editor mounts) and
 * adds the scope class to its body. The class derives from the same
 * theme_scopes() helper the front-end body_class filter uses, so editor and
 * front-end stay in sync.
 */
add_action(
	'enqueue_block_editor_assets',
	static function (): void {
		$scopes = theme_scopes();
		// Honor the "Site appearance" override (AWT Settings) so the editor
		// canvas matches the front end: forced-dark → the dark scope, else light.
		$site_cs = function_exists( '\\AWT\\Theme\\Settings\\get' ) ? (string) \AWT\Theme\Settings\get( 'site.colorScheme' ) : 'default';
		$variant = $site_cs === 'dark' ? $scopes['dark'] : $scopes['light'];
		$script  = <<<'JS'
(function() {
	var variant = %s;
	function applyToIframe(iframe) {
		try {
			var apply = function() {
				var body = iframe.contentDocument && iframe.contentDocument.body;
				if (!body) return false;
				['white','g10','g90','g100'].forEach(function(s){ body.classList.remove('cds--' + s); });
				body.classList.add('cds--' + variant);
				return true;
			};
			if (!apply()) {
				iframe.addEventListener('load', apply, { once: true });
			}
		} catch (e) { /* cross-origin guard — never our iframe but be safe */ }
	}
	// Initial pass for any iframe already present
	document.querySelectorAll('iframe[name="editor-canvas"]').forEach(applyToIframe);
	// Watch for iframes added later (block editor mounts them async)
	var obs = new MutationObserver(function(records) {
		records.forEach(function(r) {
			r.addedNodes && r.addedNodes.forEach(function(node) {
				if (node.nodeType === 1) {
					if (node.tagName === 'IFRAME' && node.name === 'editor-canvas') {
						applyToIframe(node);
					}
					if (node.querySelectorAll) {
						node.querySelectorAll('iframe[name="editor-canvas"]').forEach(applyToIframe);
					}
				}
			});
		});
	});
	obs.observe(document.body, { childList: true, subtree: true });
})();
JS;
		wp_add_inline_script(
			'wp-blocks',
			sprintf( $script, wp_json_encode( $variant ) ),
			'after'
		);
	}
);

/**
 * Expose the AWT Settings the header blocks resolve at render time to the
 * block editor as `window.awtSettings`, so the editor previews of skip-link /
 * header-brand (and the header appearance scope) match the front end instead
 * of showing defaults/placeholders. These settings live in wp_options and are
 * otherwise applied only server-side in render.php; the editor renders blocks
 * client-side and can't read them without this bridge.
 */
add_action(
	'enqueue_block_editor_assets',
	static function (): void {
		if ( ! function_exists( '\\AWT\\Theme\\Settings\\get' ) ) {
			return;
		}
		$scopes = theme_scopes();
		$data   = array(
			'skipLinkText'      => (string) Settings\get( 'navigation.skipLinkText' ),
			'skipLinkDefault'   => __( 'Skip to main content', 'awt' ),
			// Sent unresolved: 'auto' means "use the logo/prefix that are set",
			// and edit.js resolves it against the block's own logo + prefix
			// attributes the same way render.php does.
			'brandMode'         => (string) ( Settings\get( 'identity.brandMode' ) ? Settings\get( 'identity.brandMode' ) : 'auto' ),
			'prefix'            => (string) Settings\get( 'identity.prefix' ),
			'logoUrl'           => (string) Settings\get( 'identity.logoUrl' ),
			'logoUrlDark'       => (string) Settings\get( 'identity.logoUrlDark' ),
			'logoAlt'           => (string) Settings\get( 'identity.logoAlt' ),
			'siteTitle'         => (string) get_bloginfo( 'name' ),
			'headerColorScheme' => (string) Settings\get( 'header.colorScheme' ),
			'siteColorScheme'   => (string) Settings\get( 'site.colorScheme' ),
			'lightScope'        => 'cds--' . $scopes['light'],
			'darkScope'         => 'cds--' . $scopes['dark'],
		);
		wp_add_inline_script(
			'wp-blocks',
			'window.awtSettings = ' . wp_json_encode( $data ) . ';',
			'before'
		);
	}
);
