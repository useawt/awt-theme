<?php
/**
 * AWT Settings — persistence layer.
 *
 * Single `wp_options` row (`awt_settings`) serialized as JSON. Every §5
 * admin-page setting reads/writes here through the `Settings` class —
 * blocks, theme code, and the admin UI all share one source of truth.
 *
 * Why one row instead of many: WordPress's autoload behavior, network
 * fewer queries, and the spec's Export/Import schema (§5) treats AWT
 * settings as one coherent JSON document. Splitting across rows would
 * complicate atomic snapshot/restore.
 *
 * Why JSON instead of PHP serialize: JSON is human-readable in the DB,
 * survives WP database exports without serialized-array-length corruption
 * (a real-world problem when sites are migrated and the table prefix
 * changes), and lines up directly with the export-file schema.
 *
 * Schema versioning: `schemaVersion` keys the stored payload. When the
 * shape changes incompatibly, bump the integer and add a migration in
 * `migrate()`. Forward-compatible additions (new optional keys under
 * existing sections) don't bump the version.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const OPTION_KEY     = 'awt_settings';
const SCHEMA_VERSION = 2;

/**
 * The link-underline switches, in the order the settings page shows them.
 *
 * `all` is first and is a gate: with it off, no region is underlined, but each
 * region keeps its own stored value so turning `all` back on restores the set.
 * The five that follow name the places a link appears, because the reason a
 * link needs an underline differs by place — see `defaults()`.
 */
const LINK_UNDERLINE_KEYS = array( 'all', 'main', 'header', 'sideNav', 'breadcrumbs', 'footer' );

/**
 * Front-end body class per underline region. The CSS in `theme.css` keys off
 * these, so a region added here needs a rule there to mean anything.
 */
const LINK_UNDERLINE_CLASSES = array(
	'main'        => 'awt-underline-main',
	'header'      => 'awt-underline-header',
	'sideNav'     => 'awt-underline-side-nav',
	'breadcrumbs' => 'awt-underline-breadcrumbs',
	'footer'      => 'awt-underline-footer',
);

/**
 * The container each region's links live in, for the block-editor canvas only.
 *
 * The canvas <body> never carries the `awt-underline-*` classes above, so the
 * region rules in `theme.css` cannot match there and every container falls back
 * to its "no underline" reset. These selectors re-root those rules onto the
 * canvas body, which is the one class WordPress always puts there.
 *
 * An empty string means the region IS the canvas body: post content is what the
 * canvas renders, so main needs no container of its own.
 *
 * This duplicates the container list in `theme.css` — CSS cannot read PHP, so
 * one copy per language is the floor. Change one, change the other.
 */
const LINK_UNDERLINE_CANVAS_SELECTORS = array(
	'main'        => '',
	'header'      => '.cds--header',
	'sideNav'     => '.cds--side-nav',
	'breadcrumbs' => '.cds--breadcrumb',
	'footer'      => '.cds--footer',
);

/**
 * Bring a stored payload up to the current schema shape.
 *
 * A pure transform applied on every read — it performs no database write, so a
 * site is correct from the very next page load, and the migrated value persists
 * naturally the next time anything is saved (both `save()` and `sanitize()`
 * stamp the current SCHEMA_VERSION).
 *
 * **v1 → v2 — `identity.brandMode` gains `auto` and defaults to it.** Under v1
 * the sanitizer always wrote a concrete mode, filling in `text-only` even when
 * the site owner had never opened the setting. A stored v1 `text-only`
 * therefore carries no information about intent, so it is rewritten to `auto`.
 * For a site with no logo that renders identically; for a site that had
 * uploaded a logo it is the entire point of the change, because the logo was
 * being saved and then silently not drawn.
 *
 * @param array $stored Decoded payload straight from the option.
 * @return array Payload at the current schema version.
 */
function migrate( array $stored ): array {
	$version = isset( $stored['schemaVersion'] ) ? (int) $stored['schemaVersion'] : 1;

	if ( $version < 2 ) {
		if ( ( $stored['identity']['brandMode'] ?? '' ) === 'text-only' ) {
			$stored['identity']['brandMode'] = 'auto';
		}
		$stored['schemaVersion'] = 2;
	}

	return $stored;
}

/**
 * Default shape — every key the rest of the codebase may read MUST have a
 * default here, so `get()` never returns `null` for a known path.
 *
 * Keep these in sync with §5 of `awt-stage-1-spec.md`. Anything added here
 * must also be documented in the spec's export schema example.
 */
function defaults(): array {
	return array(
		'schemaVersion' => SCHEMA_VERSION,
		// §A: the active design system. Default 'carbon'. Sanitized against
		// Registry::available() at save.
		'designSystem'  => array(
			'slug' => 'carbon',
		),
		// Header bar light/dark appearance. 'default' = the header inherits the
		// page color scheme (follows the visitor's system preference + the
		// color-scheme toggle). 'light' / 'dark' pin the header to one Carbon
		// scope regardless of the rest of the page (AWT Settings → Carbon →
		// Header color; applied via the render_block filter in functions.php).
		'header'        => array(
			'colorScheme' => 'default', // One of: default | light | dark.
		),
		// Whole-site light/dark appearance. 'default' = honor the visitor's
		// system preference (and toggle). 'light' / 'dark' pin the site's
		// active color scheme regardless of system preference. Overrides
		// theme.json's ui-shell.colorScheme default via color_scheme_settings().
		'site'          => array(
			'colorScheme' => 'default', // One of: default | light | dark.
		),
		'welcome'       => array(
			'completed'   => false,
			'currentStep' => 0,
			// Wizard sub-state: tracks the user's choice per step so they
			// can leave and resume. Each step writes its result here.
			'choices'     => array(),
		),
		'identity'      => array(
			// Light-mode logo URL. Acts as the universal logo when only one
			// is configured; rendered under both light and dark scopes in
			// that case. When both light + dark URLs are set, this one shows
			// in light scope and `logoUrlDark` shows in dark scope.
			'logoUrl'     => '',
			// Optional dark-mode logo URL. Set when the same artwork doesn't
			// read on both surfaces — usually because the light-mode logo
			// uses dark colors, so a light/white variant is needed for dark
			// backgrounds. Empty → fall back to the light-mode logo.
			'logoUrlDark' => '',
			'logoAlt'     => '',
			// One of: auto | text-only | logo-with-text | logo-only |
			// text-with-prefix | logo-with-text-and-prefix.
			//
			// 'auto' is the default and means "show what I have set": the logo
			// appears once a logo URL is saved, and the prefix appears once a
			// prefix is typed. Without it, a logo uploaded during the welcome
			// wizard was stored but never drawn, because the mode that draws it
			// lives on a different settings tab. Resolved at render time in
			// `awt/header-brand` (render.php + edit.js), never stored resolved,
			// so the site keeps following the logo/prefix fields as they change.
			'brandMode'   => 'auto',
			'prefix'      => '',
		),
		'navigation'    => array(
			'skipLinkText'         => '', // Empty = use the i18n'd default in the block.
			'homeItemText'         => '',
			'pageNotFoundItemText' => '',
			'breadcrumbAutoEmit'   => array(
				'enabled'  => true,
				'mobile'   => true,
				'position' => 'above-content',
			),
		),
		// Link underlines. AWT underlines links, so a link is never marked out
		// by colour alone — Carbon leaves `text-decoration: none` and brings
		// the underline back only on hover, which asks colour to do the whole
		// job. Whether that is allowed depends on the palette: WCAG 1.4.1 lets
		// colour carry a link only when the link contrasts at least 3:1 with
		// the text around it, and Carbon's blue against Carbon's body text
		// measures 3.62:1 in light but 2.14:1 in dark. So the dark scope fails
		// on Carbon's own palette, and a darker or lighter brand blue can put
		// the light scope under too. See "Differences from Carbon" (D6).
		//
		// One switch per place a link appears, because the reason for the rule
		// is different in each: a link inside a paragraph is surrounded by
		// text it must be told apart from, while a header nav item stands
		// alone and is found by its position. `all` is a gate, not a bulk
		// action — turning it off suppresses every underline while leaving the
		// individual choices intact, so turning it back on restores them.
		//
		// Excluded by design, with no switch: pagination numbers, links
		// rendered as buttons, linked tags, clickable tiles, and links that
		// are a whole heading. Each of those carries its own fill, box or type
		// size, so colour is not the only thing marking it.
		'links'         => array(
			'underline' => array(
				'all'         => true,
				'main'        => true,
				'header'      => true,
				'sideNav'     => true,
				'breadcrumbs' => true,
				'footer'      => true,
			),
		),
		'typography'    => array(
			// Global font-size multiplier per §5 "Site Editor surfaces →
			// Typography (sizes only)". One of: 0.875 (Compact), 1.0
			// (Default), 1.125 (Comfortable). Applied at the html element
			// as `font-size: calc(100% * X)` — every rem-based Carbon
			// token scales proportionally, including line-height ratios.
			'sizeScale' => 1.0,
		),
		'customCode'    => array(
			'head'            => '',
			'afterBodyOpen'   => '',
			'beforeBodyClose' => '',
		),
		'customCss'     => '',
	);
}

/**
 * Internal cache reference. Wrapped in a function returning by-reference
 * so both `all()` and `flush_cache()` see the same storage — PHP doesn't
 * let one function reset another's `static`, so we use a shared closure
 * over a function-local variable.
 */
function &cache_ref(): ?array {
	static $cache = null;
	return $cache;
}

/**
 * Read the entire settings document, merged over defaults so missing keys
 * fall back gracefully. Cached per-request — repeated calls are free.
 */
function all(): array {
	$cache = &cache_ref();
	if ( $cache !== null ) {
		return $cache;
	}
	$raw = get_option( OPTION_KEY, array() );
	if ( is_string( $raw ) ) {
		// Stored as a JSON string (the persisted form). Decode.
		$decoded = json_decode( $raw, true );
		$raw     = is_array( $decoded ) ? $decoded : array();
	} elseif ( ! is_array( $raw ) ) {
		$raw = array();
	}
	$cache = deep_merge( defaults(), migrate( $raw ) );
	return $cache;
}

/**
 * Bust the per-request cache. Called automatically from `save()`; tests
 * and explicit consumers can call this after a direct option_update.
 */
function flush_cache(): void {
	$cache = &cache_ref();
	$cache = null;
}

/**
 * Read a single setting by dot-path.
 *
 * Example paths: `identity.logoUrl`, `navigation.breadcrumbAutoEmit.enabled`.
 *
 * Returns `null` if the path doesn't exist. Callers should pair with the
 * `?? default` operator for safety, though defaults() should always cover
 * known paths.
 *
 * @param string $path Dot-separated setting path.
 * @return mixed The setting value, or null if the path doesn't exist.
 */
function get( string $path ) {
	$value = all();
	foreach ( explode( '.', $path ) as $segment ) {
		if ( ! is_array( $value ) || ! array_key_exists( $segment, $value ) ) {
			return null;
		}
		$value = $value[ $segment ];
	}
	return $value;
}

/**
 * Set a single setting by dot-path. Reads → mutates → writes the full
 * document. Not optimized for high-frequency writes (admin UI saves only).
 *
 * @param string $path  Dot-separated setting path.
 * @param mixed  $value Value to store at that path.
 * @return bool True on a successful save, false otherwise.
 */
function set( string $path, $value ): bool {
	$all      = all();
	$segments = explode( '.', $path );
	$ref      = &$all;
	foreach ( $segments as $i => $segment ) {
		if ( $i === count( $segments ) - 1 ) {
			$ref[ $segment ] = $value;
			break;
		}
		if ( ! isset( $ref[ $segment ] ) || ! is_array( $ref[ $segment ] ) ) {
			$ref[ $segment ] = array();
		}
		$ref = &$ref[ $segment ];
	}
	return save( $all );
}

/**
 * Persist a complete settings array. Sanitizes recursively, then writes
 * as JSON for human-readable storage. Triggers WordPress's standard
 * update_option hooks so caching plugins invalidate cleanly.
 *
 * @param array $settings Complete settings document to persist.
 * @return bool True on a successful write, false otherwise.
 */
function save( array $settings ): bool {
	$settings['schemaVersion'] = SCHEMA_VERSION;
	$sanitized                 = sanitize( $settings );
	$json                      = wp_json_encode( $sanitized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	if ( $json === false ) {
		return false;
	}
	$ok = update_option( OPTION_KEY, $json, false /* don't autoload — admin reads only */ );
	flush_cache();
	return $ok;
}

/**
 * Recursive deep-merge of an arbitrary settings doc into the defaults
 * shape. Scalars in the override replace defaults; arrays merge key-wise.
 * Unknown keys at any depth are preserved (defensive — keeps future
 * additions safe across reads from older code paths).
 *
 * @param array $base     Defaults-shaped array to merge into.
 * @param array $override Stored settings to merge over the base.
 * @return array The merged settings array.
 */
function deep_merge( array $base, array $override ): array {
	foreach ( $override as $key => $value ) {
		if ( is_array( $value ) && isset( $base[ $key ] ) && is_array( $base[ $key ] ) ) {
			$base[ $key ] = deep_merge( $base[ $key ], $value );
		} else {
			$base[ $key ] = $value;
		}
	}
	return $base;
}

/**
 * Sanitize the entire settings document before write. Strings are
 * passed through `sanitize_text_field` for one-liners and `wp_kses_post`
 * for fields known to allow HTML; URLs through `esc_url_raw`. Boolean
 * fields are cast strictly.
 *
 * Custom-code fields are deliberately NOT sanitized — they're documented
 * as accepting arbitrary code (the spec's §5 warning block makes this
 * explicit). The capability check at the API boundary (`manage_options`)
 * is the access gate; sanitizing here would defeat the field's purpose.
 *
 * @param array $settings Raw settings document to sanitize.
 * @return array The sanitized settings document.
 */
function sanitize( array $settings ): array {
	$out = array();

	$out['schemaVersion'] = isset( $settings['schemaVersion'] ) ? (int) $settings['schemaVersion'] : SCHEMA_VERSION;

	// Design system (§A). Must be a slug in Registry::available(); anything
	// else snaps to 'carbon'.
	$ds_slug = isset( $settings['designSystem']['slug'] ) ? (string) $settings['designSystem']['slug'] : 'carbon';
	if ( class_exists( '\\AWT\\Theme\\DesignSystem\\Registry' ) ) {
		$available = array_keys( \AWT\Theme\DesignSystem\Registry::available() );
		if ( ! in_array( $ds_slug, $available, true ) ) {
			$ds_slug = 'carbon';
		}
	} elseif ( $ds_slug === '' ) {
		$ds_slug = 'carbon';
	}
	$out['designSystem'] = array( 'slug' => $ds_slug );

	// Header color scheme — one of default | light | dark.
	$header_scheme = isset( $settings['header']['colorScheme'] ) ? (string) $settings['header']['colorScheme'] : 'default';
	$out['header'] = array(
		'colorScheme' => in_array( $header_scheme, array( 'default', 'light', 'dark' ), true ) ? $header_scheme : 'default',
	);

	// Site color scheme — one of default | light | dark.
	$site_scheme = isset( $settings['site']['colorScheme'] ) ? (string) $settings['site']['colorScheme'] : 'default';
	$out['site'] = array(
		'colorScheme' => in_array( $site_scheme, array( 'default', 'light', 'dark' ), true ) ? $site_scheme : 'default',
	);

	// Welcome wizard state.
	$welcome        = $settings['welcome'] ?? array();
	$out['welcome'] = array(
		'completed'   => ! empty( $welcome['completed'] ),
		'currentStep' => isset( $welcome['currentStep'] ) ? max( 0, (int) $welcome['currentStep'] ) : 0,
		'choices'     => is_array( $welcome['choices'] ?? null ) ? $welcome['choices'] : array(),
	);

	// Identity.
	$identity        = $settings['identity'] ?? array();
	$brand_modes     = array( 'auto', 'text-only', 'logo-with-text', 'logo-only', 'text-with-prefix', 'logo-with-text-and-prefix' );
	$out['identity'] = array(
		'logoUrl'     => isset( $identity['logoUrl'] ) ? esc_url_raw( (string) $identity['logoUrl'] ) : '',
		'logoUrlDark' => isset( $identity['logoUrlDark'] ) ? esc_url_raw( (string) $identity['logoUrlDark'] ) : '',
		'logoAlt'     => isset( $identity['logoAlt'] ) ? sanitize_text_field( (string) $identity['logoAlt'] ) : '',
		'brandMode'   => in_array( $identity['brandMode'] ?? '', $brand_modes, true ) ? $identity['brandMode'] : 'auto',
		'prefix'      => isset( $identity['prefix'] ) ? sanitize_text_field( (string) $identity['prefix'] ) : '',
	);

	// Navigation.
	$nav               = $settings['navigation'] ?? array();
	$breadcrumb        = $nav['breadcrumbAutoEmit'] ?? array();
	$out['navigation'] = array(
		'skipLinkText'         => isset( $nav['skipLinkText'] ) ? sanitize_text_field( (string) $nav['skipLinkText'] ) : '',
		'homeItemText'         => isset( $nav['homeItemText'] ) ? sanitize_text_field( (string) $nav['homeItemText'] ) : '',
		'pageNotFoundItemText' => isset( $nav['pageNotFoundItemText'] ) ? sanitize_text_field( (string) $nav['pageNotFoundItemText'] ) : '',
		'breadcrumbAutoEmit'   => array(
			'enabled'  => ! isset( $breadcrumb['enabled'] ) || ! empty( $breadcrumb['enabled'] ),
			'mobile'   => ! isset( $breadcrumb['mobile'] ) || ! empty( $breadcrumb['mobile'] ),
			'position' => 'above-content', // Only one valid value at Stage 1.
		),
	);

	// Link underlines. Every switch defaults to ON, so an absent key means
	// "underline" — the same convention `breadcrumbAutoEmit` uses above, and
	// the one that makes a site upgrading from an older release land on the
	// accessible default rather than on Carbon's.
	$underline    = $settings['links']['underline'] ?? array();
	$out['links'] = array(
		'underline' => array_combine(
			LINK_UNDERLINE_KEYS,
			array_map(
				static fn( string $key ): bool => ! isset( $underline[ $key ] ) || ! empty( $underline[ $key ] ),
				LINK_UNDERLINE_KEYS
			)
		),
	);

	// Typography. Only three allowed scale values; anything else snaps back to Default.
	$typography        = $settings['typography'] ?? array();
	$raw_scale         = (float) ( $typography['sizeScale'] ?? 1.0 );
	$allowed_scales    = array( 0.875, 1.0, 1.125 );
	$out['typography'] = array(
		'sizeScale' => in_array( $raw_scale, $allowed_scales, true ) ? $raw_scale : 1.0,
	);

	// Custom code (NOT sanitized — see comment above).
	$cc                = $settings['customCode'] ?? array();
	$out['customCode'] = array(
		'head'            => isset( $cc['head'] ) ? (string) $cc['head'] : '',
		'afterBodyOpen'   => isset( $cc['afterBodyOpen'] ) ? (string) $cc['afterBodyOpen'] : '',
		'beforeBodyClose' => isset( $cc['beforeBodyClose'] ) ? (string) $cc['beforeBodyClose'] : '',
	);

	$out['customCss'] = isset( $settings['customCss'] ) ? (string) $settings['customCss'] : '';

	return $out;
}

/**
 * The body classes that switch link underlines on, region by region.
 *
 * Returns an empty array when the `all` gate is off, which is the one-switch
 * way to get Carbon's link style across the whole site. Otherwise it returns a
 * class per region that is on. Nothing is emitted for a region that is off, so
 * that region keeps Carbon's own CSS with no override of ours in the cascade —
 * the same opt-*out* shape the field blocks use for D5, for the same reason:
 * "Carbon style" should be honestly Carbon, and the next Carbon upgrade should
 * not find a half-overridden link behind it.
 *
 * @return string[] Body classes, possibly empty.
 */
function link_underline_body_classes(): array {
	if ( ! get( 'links.underline.all' ) ) {
		return array();
	}
	$classes = array();
	foreach ( LINK_UNDERLINE_CLASSES as $key => $class ) {
		if ( get( 'links.underline.' . $key ) ) {
			$classes[] = $class;
		}
	}
	return $classes;
}

/**
 * The same decision, expressed for the block-editor canvas.
 *
 * A header or footer block is edited in the Site Editor, where the canvas is
 * the only preview an author gets — so it has to show the underline the
 * published page will draw. Returns '' when nothing is underlined, which the
 * caller can skip injecting entirely.
 *
 * @return string CSS, or '' when there is nothing to say.
 */
function link_underline_editor_css(): string {
	if ( ! get( 'links.underline.all' ) ) {
		return '';
	}
	$rules = array();
	foreach ( LINK_UNDERLINE_CANVAS_SELECTORS as $key => $selector ) {
		if ( ! get( 'links.underline.' . $key ) ) {
			continue;
		}
		$rules[] = 'body.editor-styles-wrapper' . ( $selector === '' ? '' : ' ' . $selector )
			. ' { --awt-underline: underline; }';
	}
	return implode( "\n", $rules );
}
