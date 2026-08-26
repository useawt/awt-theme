<?php
/**
 * DesignSystemInterface — the contract every design system implements.
 *
 * §A "Design system abstraction" (see awt-stage-1-spec.md). All of AWT's
 * design-system data — tokens, style variations, header presets, the contrast
 * audit, and the component CSS-class resolver — lives behind this one
 * contract instead of being hardcoded across the theme's inc/ files. That
 * keeps every consumer (settings UI, wizard, block render.php files) on a
 * single, testable entry point: Registry::get_active().
 *
 * The interface groups its methods into wrapped surfaces:
 *
 *   1. Identity            — slug / name / description / availability
 *   2. Visual tokens       — palette / typography / spacing
 *   3. Style variations    — light/dark pairings
 *   4. Header presets      — composition markup + preview SVG + icon catalogue
 *   5. Accessibility audit — role-map / resolved palette / surface + exempt sets
 *   6. Component layer      — supported_components() + classes_for() resolver
 *   7. Settings UI         — render_settings_tab() (the design system's AWT-Settings tab)
 *
 * Data that used to live hardcoded in inc/style-variations.php,
 * inc/header-presets.php, and inc/contrast.php now lives behind these
 * methods. Those files become thin shims that delegate to the active
 * system via Registry::get_active().
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\DesignSystem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

interface DesignSystemInterface {

	/* --- 1. Identity ----------------------------------------------------- */

	/** Stable machine slug, e.g. 'carbon'. Stored in awt_theme_settings.designSystem.slug. */
	public function slug(): string;

	/** Human-readable name, e.g. 'Carbon'. Used as the per-system AWT-Settings tab label. */
	public function name(): string;

	/** One-line description for the Design system settings tab. */
	public function description(): string;

	/** Whether the system can be made active. Registry::get_active() only ever returns an available system. */
	public function is_available(): bool;

	/* --- 2. Visual tokens ----------------------------------------------- */

	/** Token slug => hex. Carbon ships ~37; the light-scope resolved palette. */
	public function get_palette(): array;

	/** Font families + size-scale data. */
	public function get_typography(): array;

	/** Base spacing-unit data. */
	public function get_spacing(): array;

	/* --- 3. Style variations -------------------------------------------- */

	/**
	 * Light/dark pairings.
	 *   slug => [ 'label', 'description', 'light_color', 'dark_color' ]
	 */
	public function get_style_variations(): array;

	/* --- 4. Header presets ---------------------------------------------- */

	/**
	 * Header presets.
	 *   slug => [ 'label', 'description', 'content' (block markup), 'svg' (preview) ]
	 */
	public function get_header_presets(): array;

	/**
	 * Standard-icon catalogue for the per-system Header settings toggles.
	 *   icon-key => [ 'block' (detect regex), 'markup', 'label' ]
	 * Empty array for systems without a header-icon toggle model.
	 */
	public function get_header_icons(): array;

	/* --- 5. Accessibility audit ----------------------------------------- */

	/** Role taxonomy: token => [ role, pairings[], notes? ]. */
	public function get_role_map(): array;

	/** Per-scope resolved palette: scope => [ token => hex ]. */
	public function get_resolved_palette(): array;

	/** Structural/surface tokens listed without ratio checks. */
	public function get_surface_tokens(): array;

	/** Tokens exempt from the ratio audit entirely. */
	public function get_exempt_tokens(): array;

	/* --- 6. Component registry + CSS class layer ------------------------ */

	/**
	 * The conceptual component slugs this system can render (e.g. 'button',
	 * 'tabs', 'data-table'). Drives the block-inserter filter. classes_for()
	 * MUST return a non-empty root class for every slug listed here —
	 * Registry::register() enforces this at registration time.
	 *
	 * @return string[]
	 */
	public function supported_components(): array;

	/**
	 * Resolve a (component, variants) pair to a CSS class string.
	 *
	 * `$variants['element']` selects which sub-element of the component is
	 * being styled (default 'root'). Other keys carry attribute-driven
	 * modifiers (kind, size, etc.). Returns '' when the component is not in
	 * supported_components() (orphaned-block fallback) or when the requested
	 * element has no class in this system.
	 *
	 * @param string $component Conceptual component slug, e.g. 'button'.
	 * @param array  $variants  Modifier map; 'element' selects the sub-element (default 'root'), other keys carry attribute-driven modifiers.
	 * @return string Space-separated CSS class string, or '' when unresolvable.
	 */
	public function classes_for( string $component, array $variants = array() ): string;

	/**
	 * Per-component one-line blurbs. component-slug => human description.
	 */
	public function component_descriptions(): array;

	/* --- 7. Settings UI ------------------------------------------------- */

	/**
	 * Output the system-specific AWT-Settings tab body (style variations,
	 * header presets, brand mode, contrast audit, etc.). Called by
	 * admin-settings-page.php for the active system's tab. The shared form
	 * wrapper / nonce / save dispatch live in admin-settings-page.php; only
	 * the inner content lives here.
	 */
	public function render_settings_tab(): void;
}
