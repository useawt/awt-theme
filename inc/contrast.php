<?php
/**
 * WCAG contrast computation.
 *
 * Pure functions that take hex strings and return ratios / pass-fail
 * verdicts. Used by the AWT Settings → Colors tab to audit the active
 * theme palette against Carbon's surface tokens.
 *
 * Spec reference: WCAG 2.2, Section 1.4.3 (Contrast Minimum) — 4.5:1 for
 * normal text, 3:1 for large text (≥ 18pt or 14pt-bold) and non-text
 * (UI components / graphical objects).
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\Contrast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Parse a hex color string into an [r, g, b] array of integers 0–255.
 * Accepts `#fff`, `#ffffff`, with or without the leading hash.
 * Returns `null` for unparseable input.
 *
 * @param string $hex Hex color string, e.g. `#fff` or `ffffff`.
 * @return array{0: int, 1: int, 2: int}|null RGB triplet, or null if unparseable.
 */
function hex_to_rgb( string $hex ): ?array {
	$hex = ltrim( trim( $hex ), '#' );
	if ( strlen( $hex ) === 3 ) {
		// Shorthand: #abc → #aabbcc.
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
		return null;
	}
	return array(
		(int) hexdec( substr( $hex, 0, 2 ) ),
		(int) hexdec( substr( $hex, 2, 2 ) ),
		(int) hexdec( substr( $hex, 4, 2 ) ),
	);
}

/**
 * Compute the WCAG relative luminance of an [r, g, b] triplet.
 * Formula: https://www.w3.org/WAI/GL/wiki/Relative_luminance
 *
 * @param array $rgb RGB triplet of integers 0–255.
 * @return float Relative luminance between 0.0 and 1.0.
 */
function relative_luminance( array $rgb ): float {
	$linear = array_map(
		static function ( int $channel ): float {
			$srgb = $channel / 255.0;
			return $srgb <= 0.03928
				? $srgb / 12.92
				: pow( ( $srgb + 0.055 ) / 1.055, 2.4 );
		},
		$rgb
	);
	return 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];
}

/**
 * WCAG contrast ratio between two hex colors. Returns 1.0 (no contrast)
 * for unparseable inputs.
 *
 * @param string $hex_a First hex color.
 * @param string $hex_b Second hex color.
 * @return float Contrast ratio between 1.0 and 21.0.
 */
function ratio( string $hex_a, string $hex_b ): float {
	$rgb_a = hex_to_rgb( $hex_a );
	$rgb_b = hex_to_rgb( $hex_b );
	if ( $rgb_a === null || $rgb_b === null ) {
		return 1.0;
	}
	$la      = relative_luminance( $rgb_a );
	$lb      = relative_luminance( $rgb_b );
	$lighter = max( $la, $lb );
	$darker  = min( $la, $lb );
	return ( $lighter + 0.05 ) / ( $darker + 0.05 );
}

/**
 * Verdict against WCAG AA for normal text (4.5:1).
 *   - pass   : ratio >= 4.5
 *   - large  : ratio >= 3.0 but < 4.5 — passes for large text and non-text only
 *   - fail   : ratio < 3.0
 *
 * @param float $ratio Contrast ratio to classify.
 * @return string One of 'pass', 'large', or 'fail'.
 */
function verdict( float $ratio ): string {
	if ( $ratio >= 4.5 ) {
		return 'pass';
	}
	if ( $ratio >= 3.0 ) {
		return 'large';
	}
	return 'fail';
}

/**
 * Carbon's known surface + text tokens, resolved per scope. Hardcoded
 * because Carbon's compiled CSS declares them as class-scoped variables
 * — they don't resolve from PHP context. These values match Carbon's
 * v11 themes (white / g10 / g90 / g100) as of the bundled Carbon foundation CSS.
 *
 * Kept for compatibility; `carbon_resolved_palette()` below carries the
 * full per-scope palette used by the role-aware audit.
 */
function carbon_surface_tokens(): array {
	return array(
		'white' => array(
			'background'     => '#ffffff',
			'layer-01'       => '#f4f4f4',
			'layer-02'       => '#ffffff',
			'text-primary'   => '#161616',
			'text-secondary' => '#525252',
			'text-on-color'  => '#ffffff',
		),
		'g10'   => array(
			'background'     => '#f4f4f4',
			'layer-01'       => '#ffffff',
			'layer-02'       => '#f4f4f4',
			'text-primary'   => '#161616',
			'text-secondary' => '#525252',
			'text-on-color'  => '#ffffff',
		),
		'g90'   => array(
			'background'     => '#262626',
			'layer-01'       => '#393939',
			'layer-02'       => '#525252',
			'text-primary'   => '#f4f4f4',
			'text-secondary' => '#c6c6c6',
			'text-on-color'  => '#ffffff',
		),
		'g100'  => array(
			'background'     => '#161616',
			'layer-01'       => '#262626',
			'layer-02'       => '#393939',
			'text-primary'   => '#f4f4f4',
			'text-secondary' => '#c6c6c6',
			'text-on-color'  => '#ffffff',
		),
	);
}

/**
 * The active design system's palette for each named theme: every token, as
 * its compiled CSS sets it (theme.json only carries the light values;
 * Carbon's CSS swaps to dark values at runtime via the cds--g100 / cds--g90
 * scope classes).
 */
function carbon_resolved_palette(): array {
	return \AWT\Theme\DesignSystem\Registry::get_active()->get_resolved_palette();
}

/**
 * Contrast pairs. Each entry declares:
 *
 *   - role:       what the color is (text, a link, a focus ring, an icon)
 *   - pairings:   list of `{ against, threshold, label }` — the colors it
 *                 is drawn on, with the WCAG threshold for that use:
 *
 *                   text  → 4.5:1 (WCAG 1.4.3 minimum for body text)
 *                   ui    → 3.0:1 (WCAG 1.4.11 minimum for UI components)
 *                   info  → measured, no minimum (the notes say why)
 *
 *                 and optionally `over` (the color under a see-through
 *                 one) and `when` (a condition, such as links not being
 *                 underlined)
 *   - notes:      one-line caveat (optional)
 *
 * The 'against' slugs are resolved per scope via carbon_resolved_palette(),
 * so the same pairs hold for the light and the dark themes.
 */
function role_map(): array {
	return \AWT\Theme\DesignSystem\Registry::get_active()->get_role_map();
}

/**
 * Surface / structural tokens — listed in the audit without ratio checks
 * (they're the canvas, not the figure).
 */
function surface_tokens(): array {
	return \AWT\Theme\DesignSystem\Registry::get_active()->get_surface_tokens();
}

/**
 * Tokens exempt from the ratio audit entirely. Disabled / inset tokens are
 * intentionally low-contrast (WCAG explicitly exempts disabled controls)
 * or context-specific in ways a generic audit can't verify.
 */
function exempt_tokens(): array {
	return \AWT\Theme\DesignSystem\Registry::get_active()->get_exempt_tokens();
}

/**
 * Numeric threshold for a given key, so every caller uses the same numbers.
 *
 * @param string $key Threshold key: 'text' for body text, 'info' for a pair
 *                    that is measured with no minimum, anything else for
 *                    UI/large.
 * @return float Required contrast ratio (4.5, 3.0, or 0.0 for 'info').
 */
function threshold_value( string $key ): float {
	if ( $key === 'info' ) {
		return 0.0;
	}
	return $key === 'text' ? 4.5 : 3.0;
}

/**
 * Pass / fail verdict against the role's actual requirement.
 *
 * No "exempt" path — an earlier version of this function gated on a
 * per-pairing `exempt` flag (used to soften placeholder pairings), but
 * the WCAG citation for that soft-pedaling didn't hold up: WCAG 2.x
 * does not exempt placeholders. The audit now calls out genuine
 * failures honestly and uses per-row Notes to explain Carbon design
 * tradeoffs to the site owner.
 *
 * @param float  $ratio         Measured contrast ratio.
 * @param string $threshold_key Threshold key passed to threshold_value().
 * @return string 'pass' or 'fail', or 'info' for a pair with no minimum.
 */
function role_verdict( float $ratio, string $threshold_key ): string {
	if ( $threshold_key === 'info' ) {
		return 'info';
	}
	$required = threshold_value( $threshold_key );
	return $ratio >= $required ? 'pass' : 'fail';
}
