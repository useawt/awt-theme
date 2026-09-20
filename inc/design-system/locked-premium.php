<?php
/**
 * LockedPremiumSystem — catalog placeholder for a design system reserved
 * for AWT Premium.
 *
 * §A "Selector UI". AWT Free registers one of these per Premium system
 * (USWDS / Bootstrap 5 / GOV.UK Frontend / ECL) so the Design system selector
 * can show the whole catalogue with lock badges. When AWT Premium activates
 * and registers a REAL implementation with the same slug,
 * Registry::register() replaces the placeholder atomically.
 *
 * A locked system:
 *   - reports is_available() === false (the selector renders it disabled + a
 *     "Premium" badge, and the save handler refuses to make it active)
 *   - supplies a premium_url() for the upgrade CTA
 *   - returns empty data for every visual/component method — it never
 *     actually renders a site (the registry never makes a locked system
 *     active; awt_theme_settings.designSystem.slug sanitizes to 'carbon' if a
 *     locked slug somehow slips through)
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\DesignSystem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LockedPremiumSystem implements DesignSystemInterface {

	/**
	 * Stable machine slug, e.g. 'bootstrap'.
	 *
	 * @var string
	 */
	private string $slug;

	/**
	 * Human-readable name shown on the locked selector tile.
	 *
	 * @var string
	 */
	private string $name;

	/**
	 * One-line description shown on the locked selector tile.
	 *
	 * @var string
	 */
	private string $description;

	/**
	 * Upgrade-CTA URL, or null when the tile carries its own contact prompt.
	 *
	 * @var string|null
	 */
	private ?string $premium_url;

	/**
	 * Optional component-delta blurb shown on the locked tile, e.g. "33 of Carbon's 52 components + 7 unique".
	 *
	 * @var string
	 */
	private string $component_delta;

	/**
	 * Where someone asks for a design system that is not on the list.
	 *
	 * Only the catch-all tile has one. It is separate from premium_url()
	 * because it is not an upgrade: nothing is being sold, the tile is asking
	 * to be told what to build.
	 *
	 * @var string
	 */
	private string $request_url;

	/**
	 * Build a locked placeholder for one Premium design system.
	 *
	 * @param string      $slug            Stable machine slug, e.g. 'bootstrap'.
	 * @param string      $name            Human-readable name for the selector tile.
	 * @param string      $description     One-line description for the selector tile.
	 * @param string|null $premium_url     Upgrade-CTA URL, or null when the tile carries its own contact prompt.
	 * @param string      $component_delta Optional component-delta blurb for the locked tile.
	 * @param string      $request_url     Where to ask for a design system that is not listed.
	 */
	public function __construct(
		string $slug,
		string $name,
		string $description,
		?string $premium_url = null,
		string $component_delta = '',
		string $request_url = ''
	) {
		$this->slug            = $slug;
		$this->name            = $name;
		$this->description     = $description;
		$this->premium_url     = $premium_url;
		$this->component_delta = $component_delta;
		$this->request_url     = $request_url;
	}

	/* --- Identity -------------------------------------------------------- */

	/**
	 * Stable machine slug, e.g. 'bootstrap'.
	 *
	 * @return string The slug passed at construction.
	 */
	public function slug(): string {
		return $this->slug; }

	/**
	 * Human-readable name shown on the locked selector tile.
	 *
	 * @return string The name passed at construction.
	 */
	public function name(): string {
		return $this->name; }

	/**
	 * One-line description shown on the locked selector tile.
	 *
	 * @return string The description passed at construction.
	 */
	public function description(): string {
		return $this->description; }

	/**
	 * Whether the system can be selected. Always false for a locked placeholder.
	 *
	 * @return bool Always false.
	 */
	public function is_available(): bool {
		return false; }

	/**
	 * Upgrade-CTA URL for the locked tile.
	 *
	 * @return string|null The URL passed at construction, or null when the tile carries its own contact prompt.
	 */
	public function premium_url(): ?string {
		return $this->premium_url; }

	/**
	 * Component-delta copy for the locked tile.
	 *
	 * @return string The blurb passed at construction, or '' when none was given.
	 */
	public function component_delta(): string {
		return $this->component_delta; }

	/**
	 * Where someone asks for a design system that is not on the list.
	 *
	 * @return string The URL passed at construction, or '' when the tile has none.
	 */
	public function request_url(): string {
		return $this->request_url; }

	/* --- Everything else: empty (a locked system never renders) ---------- */

	/**
	 * Palette tokens. Empty — a locked system never renders a site.
	 *
	 * @return array Always empty.
	 */
	public function get_palette(): array {
		return array(); }

	/**
	 * Typography data. Empty — a locked system never renders a site.
	 *
	 * @return array Always empty.
	 */
	public function get_typography(): array {
		return array(); }

	/**
	 * Spacing data. Empty — a locked system never renders a site.
	 *
	 * @return array Always empty.
	 */
	public function get_spacing(): array {
		return array(); }

	/**
	 * Style variations. Empty — a locked system never renders a site.
	 *
	 * @return array Always empty.
	 */
	public function get_style_variations(): array {
		return array(); }

	/**
	 * Header presets. Empty — a locked system never renders a site.
	 *
	 * @return array Always empty.
	 */
	public function get_header_presets(): array {
		return array(); }

	/**
	 * Header-icon catalogue. Empty — a locked system never renders a site.
	 *
	 * @return array Always empty.
	 */
	public function get_header_icons(): array {
		return array(); }

	/**
	 * Role taxonomy for the contrast audit. Empty — a locked system is never audited.
	 *
	 * @return array Always empty.
	 */
	public function get_role_map(): array {
		return array(); }

	/**
	 * Per-scope resolved palette. Empty — a locked system is never audited.
	 *
	 * @return array Always empty.
	 */
	public function get_resolved_palette(): array {
		return array(); }

	/**
	 * Structural/surface tokens. Empty — a locked system is never audited.
	 *
	 * @return array Always empty.
	 */
	public function get_surface_tokens(): array {
		return array(); }

	/**
	 * Tokens exempt from the ratio audit. Empty — a locked system is never audited.
	 *
	 * @return array Always empty.
	 */
	public function get_exempt_tokens(): array {
		return array(); }

	/**
	 * Supported component slugs. Empty — no supported components, so the
	 * inserter would hide everything if this were ever active.
	 *
	 * @return string[] Always empty.
	 */
	public function supported_components(): array {
		return array(); }

	/**
	 * Resolve a (component, variants) pair to a CSS class string. Always ''
	 * because a locked system supports no components.
	 *
	 * @param string $component Conceptual component slug, e.g. 'button'.
	 * @param array  $variants  Modifier map (unused here).
	 * @return string Always ''.
	 */
	public function classes_for( string $component, array $variants = array() ): string {
		return ''; }

	/**
	 * Per-component blurbs. Empty — a locked system lists no components.
	 *
	 * @return array Always empty.
	 */
	public function component_descriptions(): array {
		return array(); }

	/** The selector renders the upgrade message; a locked tab is never shown as active. */
	public function render_settings_tab(): void {
		echo '<p>' . esc_html(
			sprintf(
				/* translators: %s: design system name */
				__( '%s is coming soon to AWT Premium.', 'awt' ),
				$this->name
			)
		) . '</p>';
		if ( $this->premium_url ) {
			printf(
				'<p><a class="button button-primary" href="%s" target="_blank" rel="noopener noreferrer">%s</a></p>',
				esc_url( $this->premium_url ),
				esc_html__( 'Learn more about AWT Premium', 'awt' )
			);
		}
	}
}
