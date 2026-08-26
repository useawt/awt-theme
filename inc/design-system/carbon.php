<?php
/**
 * Carbon — the IBM Carbon Design System implementation of DesignSystemInterface.
 *
 * §A "Design system abstraction". Carbon owns all the design-system data
 * that used to live hardcoded in inc/style-variations.php,
 * inc/header-presets.php, and inc/contrast.php — those files now delegate
 * here via Registry::get_active().
 *
 * The component layer (supported_components() + classes_for()) keeps CSS
 * class knowledge in ONE place: every block's render.php asks classes_for()
 * for its classes instead of hardcoding `cds--*` strings, so Carbon's class
 * vocabulary can be audited, tested, and updated centrally rather than
 * being scattered across 40+ render files.
 *
 * Design-system-NEUTRAL AWT-native blocks (hero, stat, testimonial,
 * feature-grid, inline-set, icon, section, faq-item, pricing-tile,
 * color-scheme-toggle) carry no `awt_component` slug and are NOT listed in
 * supported_components() — they render their own `awt-*` markup regardless
 * of the active system and are always available in the inserter.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\DesignSystem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Carbon implements DesignSystemInterface {
	/*
	====================================================================
	 * 1. Identity
	 * ==================================================================
	 */

	/** Stable machine slug: 'carbon'. Stored in awt_theme_settings.designSystem.slug. */
	public function slug(): string {
		return 'carbon'; }

	/** Human-readable name, used as the per-system AWT-Settings tab label. */
	public function name(): string {
		return __( 'Carbon', 'awt' ); }

	/** One-line description for the Design system settings tab. */
	public function description(): string {
		return __( "IBM's open-source design system for products and digital experiences.", 'awt' );
	}

	/** Whether this system is selectable. Always true. */
	public function is_available(): bool {
		return true; }

	/*
	====================================================================
	 * 2. Visual tokens
	 * ==================================================================
	 */

	/** Light-scope (white) resolved palette, slug => hex. */
	public function get_palette(): array {
		$resolved = $this->get_resolved_palette();
		return $resolved['white'] ?? array();
	}

	/** IBM Plex font stacks plus the productive type-scale base size. */
	public function get_typography(): array {
		return array(
			'font_family_sans' => "'IBM Plex Sans', 'Helvetica Neue', Arial, sans-serif",
			'font_family_mono' => "'IBM Plex Mono', 'Menlo', monospace",
			// Productive type scale base; the global multiplier (Compact /
			// Default / Comfortable) is applied on top in settings.
			'base_size_rem'    => 1.0,
		);
	}

	/** Carbon's 13-step spacing scale, token slug => rem value. */
	public function get_spacing(): array {
		// Carbon's 13-step spacing scale (rem).
		return array(
			'spacing-01' => '0.125rem',
			'spacing-02' => '0.25rem',
			'spacing-03' => '0.5rem',
			'spacing-04' => '0.75rem',
			'spacing-05' => '1rem',
			'spacing-06' => '1.5rem',
			'spacing-07' => '2rem',
			'spacing-08' => '2.5rem',
			'spacing-09' => '3rem',
			'spacing-10' => '4rem',
			'spacing-11' => '5rem',
			'spacing-12' => '6rem',
			'spacing-13' => '10rem',
		);
	}

	/*
	====================================================================
	 * 3. Style variations  (moved from inc/style-variations.php)
	 * ==================================================================
	 */

	/**
	 * The four light/dark pairings offered in AWT Settings (white/g10 light scopes crossed with g90/g100 dark scopes).
	 *
	 * @return array Variation slug => array with 'label', 'description', 'light_color', and 'dark_color'.
	 */
	public function get_style_variations(): array {
		return array(
			'white-plus-g100' => array(
				'label'       => __( 'White + g100', 'awt' ),
				'description' => __( 'Pure white light theme paired with deepest dark. Maximum contrast.', 'awt' ),
				'light_color' => '#ffffff',
				'dark_color'  => '#161616',
			),
			'white-plus-g90'  => array(
				'label'       => __( 'White + g90', 'awt' ),
				'description' => __( 'Pure white light paired with a slightly softer dark. Easier on the eyes for long reading.', 'awt' ),
				'light_color' => '#ffffff',
				'dark_color'  => '#262626',
			),
			'g10-plus-g100'   => array(
				'label'       => __( 'g10 + g100', 'awt' ),
				'description' => __( 'Soft gray light theme paired with deepest dark. Reduces eye strain on bright displays.', 'awt' ),
				'light_color' => '#f4f4f4',
				'dark_color'  => '#161616',
			),
			'g10-plus-g90'    => array(
				'label'       => __( 'g10 + g90', 'awt' ),
				'description' => __( 'Soft gray light + softer dark. The most neutral, least-fatiguing combination.', 'awt' ),
				'light_color' => '#f4f4f4',
				'dark_color'  => '#262626',
			),
		);
	}

	/*
	====================================================================
	 * 4. Header presets  (moved from inc/header-presets.php)
	 * ==================================================================
	 */

	/**
	 * The four header presets: Marketing, Documentation, Application, and Public sector.
	 *
	 * @return array Preset slug => array with 'label', 'description', 'content' (block markup), and 'svg' (preview).
	 */
	public function get_header_presets(): array {
		$meta = array(
			// Descriptions are read by someone choosing a layout, so they say
			// what appears in the bar and who the layout suits — not which
			// components it is built from. Two naming rules, both learned the
			// hard way: use the wording the Header tab already uses ("icon
			// buttons"), and name things the author has to find on screen
			// rather than describing them. "A menu down the left" is the side
			// navigation, and an author told to look for that will not find
			// "Side nav" in the Site Editor.
			//
			// These four strings are duplicated in patterns/header-preset-*.php,
			// which WordPress reads statically from the file comment and so
			// cannot call this method. Change both places together.
			'marketing'     => array(
				'label'       => __( 'Marketing', 'awt' ),
				'description' => __( 'Your logo, a row of menu links, and one or two icon buttons. No side navigation. Good for landing pages and product sites.', 'awt' ),
			),
			'documentation' => array(
				'label'       => __( 'Documentation', 'awt' ),
				'description' => __( 'Your logo, section links, search, and a side navigation menu down the left on wide screens. Good for knowledge bases and reference sites.', 'awt' ),
			),
			'application'   => array(
				'label'       => __( 'Application', 'awt' ),
				'description' => __( 'Your logo and the icon buttons (search, notifications, user menu), with an optional side navigation menu. Good for dashboards and admin tools.', 'awt' ),
			),
			'public-sector' => array(
				'label'       => __( 'Public sector', 'awt' ),
				'description' => __( 'A large organization name, menu links, and a language switcher. Good for government and public-service sites.', 'awt' ),
			),
		);

		$out = array();
		foreach ( $meta as $slug => $info ) {
			$out[ $slug ] = array(
				'label'       => $info['label'],
				'description' => $info['description'],
				'content'     => $this->preset_content( $slug ),
				'svg'         => $this->preset_svg( $slug ),
			);
		}
		return $out;
	}

	/**
	 * Canonical block markup for a preset slug (wrapper + body).
	 *
	 * Documentation is the one preset whose description promises a side nav, so
	 * its body ends with an `awt/side-nav` and its sections. That puts
	 * `.cds--side-nav` inside `header.cds--header` — where Carbon's own UI Shell
	 * puts it (verified against the React reference: `.cds--side-nav` is a direct
	 * child of `header.cds--header`, after `.cds--header__global`). The nav is
	 * `position: fixed`, so sitting inside the header's flex row costs it
	 * nothing; theme.css offsets `.cds--content` to clear its 16rem footprint.
	 *
	 * The blocks are written out here rather than pulled in with a
	 * `wp:template-part` reference to a `sidebar` part, which is what this did
	 * first. Nesting a template part inside another makes the nav **uneditable
	 * where the author sees it**: WordPress gives a nested part a block overlay so
	 * it selects as a single unit, and its inner blocks never appear in the outer
	 * part's List View or block inspector. So the author got a side nav they could
	 * see in the header canvas and could not select, click or configure. Inline,
	 * it is an ordinary block — in List View, selectable, with its own settings —
	 * and the header part is where they are already editing header content.
	 *
	 * @param string $slug Header preset slug.
	 * @return string Block markup; '' for unknown slugs (callers treat '' as "no such preset").
	 */
	private function preset_content( string $slug ): string {
		$wrapper_open  = '<!-- wp:group {"tagName":"header","className":"cds--header","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"space-between"},"style":{"spacing":{"padding":{"top":"0","right":"0","bottom":"0","left":"0"}}}} -->' . "\n";
		$wrapper_open .= '<header class="wp-block-group cds--header" style="padding-top:0;padding-right:0;padding-bottom:0;padding-left:0">';
		$wrapper_close = '</header>' . "\n" . '<!-- /wp:group -->';

		$body = match ( $slug ) {
			'marketing' => '<!-- wp:awt/skip-link /-->

<!-- wp:awt/header-brand /-->

<!-- wp:awt/header-nav {"ariaLabel":"Primary"} -->
<!-- wp:awt/header-menu {"text":"Product"} -->
<!-- wp:awt/header-nav-item {"text":"Features","href":"#"} /-->
<!-- wp:awt/header-nav-item {"text":"Integrations","href":"#"} /-->
<!-- wp:awt/header-nav-item {"text":"Updates","href":"#"} /-->
<!-- /wp:awt/header-menu -->
<!-- wp:awt/header-nav-item {"text":"Pricing","href":"#"} /-->
<!-- wp:awt/header-nav-item {"text":"Customers","href":"#"} /-->
<!-- /wp:awt/header-nav -->

<!-- wp:awt/header-global -->
<!-- wp:awt/button {"text":"Get started","kind":"primary","size":"md","className":"awt-hide-on-mobile"} /-->
<!-- wp:awt/color-scheme-toggle {"kind":"icon-only"} /-->
<!-- /wp:awt/header-global -->',

			'documentation' => '<!-- wp:awt/skip-link /-->

<!-- wp:awt/header-brand /-->

<!-- wp:awt/header-nav {"ariaLabel":"Primary"} -->
<!-- wp:awt/header-nav-item {"text":"Guides","href":"/guides"} /-->
<!-- wp:awt/header-menu {"text":"Reference"} -->
<!-- wp:awt/header-nav-item {"text":"API","href":"/reference/api"} /-->
<!-- wp:awt/header-nav-item {"text":"CLI","href":"/reference/cli"} /-->
<!-- wp:awt/header-nav-item {"text":"SDKs","href":"/reference/sdks"} /-->
<!-- /wp:awt/header-menu -->
<!-- wp:awt/header-nav-item {"text":"Changelog","href":"/changelog"} /-->
<!-- /wp:awt/header-nav -->

<!-- wp:awt/header-global -->
<!-- wp:awt/header-action {"iconName":"search","label":"Search docs","href":"/?s="} /-->
<!-- wp:awt/color-scheme-toggle {"kind":"icon-only"} /-->
<!-- wp:awt/header-action {"iconName":"logo--github","label":"View on GitHub","href":"#"} /-->
<!-- /wp:awt/header-global -->

<!-- wp:awt/side-nav -->
<!-- wp:awt/side-nav-section {"title":"Get started"} -->
<!-- wp:awt/side-nav-link {"text":"Overview","href":"/overview"} /-->
<!-- wp:awt/side-nav-link {"text":"Quick start","href":"/quick-start"} /-->
<!-- /wp:awt/side-nav-section -->

<!-- wp:awt/side-nav-section {"title":"Reference"} -->
<!-- wp:awt/side-nav-link {"text":"Blocks","href":"/reference/blocks","matchMode":"prefix"} /-->
<!-- wp:awt/side-nav-link {"text":"Patterns","href":"/reference/patterns","matchMode":"prefix"} /-->
<!-- /wp:awt/side-nav-section -->
<!-- /wp:awt/side-nav -->',

			'application' => '<!-- wp:awt/skip-link /-->

<!-- wp:awt/header-brand /-->

<!-- wp:awt/header-nav {"ariaLabel":"Primary"} -->
<!-- wp:awt/header-nav-item {"text":"Dashboard","href":"/"} /-->
<!-- wp:awt/header-menu {"text":"Reports"} -->
<!-- wp:awt/header-nav-item {"text":"Overview","href":"/reports"} /-->
<!-- wp:awt/header-nav-item {"text":"Exports","href":"/reports/exports"} /-->
<!-- wp:awt/header-nav-item {"text":"Scheduled","href":"/reports/scheduled"} /-->
<!-- /wp:awt/header-menu -->
<!-- /wp:awt/header-nav -->

<!-- wp:awt/header-global -->
<!-- wp:awt/header-action {"iconName":"search","label":"Search","href":"/?s="} /-->
<!-- wp:awt/header-action {"iconName":"notification","label":"Notifications","href":"/"} /-->
<!-- wp:awt/color-scheme-toggle {"kind":"icon-only"} /-->
<!-- wp:awt/header-action {"iconName":"user--avatar","label":"Account","href":"/wp-login.php"} /-->
<!-- /wp:awt/header-global -->',

			'public-sector' => '<!-- wp:awt/skip-link /-->

<!-- wp:awt/header-brand /-->

<!-- wp:awt/header-nav {"ariaLabel":"Primary"} -->
<!-- wp:awt/header-menu {"text":"Services"} -->
<!-- wp:awt/header-nav-item {"text":"Apply online","href":"#"} /-->
<!-- wp:awt/header-nav-item {"text":"Make a payment","href":"#"} /-->
<!-- wp:awt/header-nav-item {"text":"Report an issue","href":"#"} /-->
<!-- /wp:awt/header-menu -->
<!-- wp:awt/header-nav-item {"text":"About","href":"#"} /-->
<!-- wp:awt/header-nav-item {"text":"News","href":"#"} /-->
<!-- wp:awt/header-nav-item {"text":"Contact","href":"#"} /-->
<!-- /wp:awt/header-nav -->

<!-- wp:awt/header-global -->
<!-- wp:awt/header-action {"iconName":"search","label":"Search","href":"/?s="} /-->
<!-- wp:awt/header-action {"iconName":"language","label":"Change language","panelId":"language-panel"} /-->
<!-- wp:awt/color-scheme-toggle {"kind":"icon-only"} /-->
<!-- /wp:awt/header-global -->',

			default => null,
		};

		if ( $body === null ) {
			return '';
		}
		return $wrapper_open . $body . $wrapper_close;
	}

	/**
	 * Schematic preview SVG for a header preset.
	 *
	 * Hand-built sketches of each preset's layout. Carbon-token palette is
	 * hardcoded so previews render consistently regardless of the user's
	 * active style variation.
	 *
	 * @param string $slug Header preset slug.
	 * @return string SVG markup; '' for unknown slugs.
	 */
	private function preset_svg( string $slug ): string {
		$header_bg     = '#ffffff';
		$header_border = '#e0e0e0';
		$text          = '#161616';
		$text_muted    = '#525252';
		$accent        = '#0f62fe';
		$layer         = '#f4f4f4';

		switch ( $slug ) {
			case 'marketing':
				return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 112" role="img" aria-label="Marketing preset preview">
	<rect x="0" y="0" width="320" height="40" fill="{$header_bg}" stroke="{$header_border}" />
	<text x="12" y="25" fill="{$text}" font-family="sans-serif" font-size="10" font-weight="600">AWT&nbsp;Brand</text>
	<text x="110" y="25" fill="{$text_muted}" font-family="sans-serif" font-size="9">Product</text>
	<text x="152" y="25" fill="{$text_muted}" font-family="sans-serif" font-size="9">Pricing</text>
	<text x="192" y="25" fill="{$text_muted}" font-family="sans-serif" font-size="9">Customers</text>
	<rect x="250" y="11" width="60" height="18" fill="{$accent}" rx="0" />
	<text x="258" y="24" fill="#ffffff" font-family="sans-serif" font-size="8">Get started</text>
	<rect x="0" y="50" width="320" height="62" fill="{$layer}" />
	<text x="12" y="80" fill="{$text_muted}" font-family="sans-serif" font-size="9">Landing / hero content area</text>
</svg>
SVG;

			case 'documentation':
				return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 112" role="img" aria-label="Documentation preset preview">
	<rect x="0" y="0" width="320" height="40" fill="{$header_bg}" stroke="{$header_border}" />
	<text x="12" y="25" fill="{$text}" font-family="sans-serif" font-size="10" font-weight="600">AWT&nbsp;Brand</text>
	<text x="110" y="25" fill="{$text_muted}" font-family="sans-serif" font-size="9">Guides</text>
	<text x="150" y="25" fill="{$text_muted}" font-family="sans-serif" font-size="9">Reference</text>
	<text x="200" y="25" fill="{$text_muted}" font-family="sans-serif" font-size="9">Changelog</text>
	<circle cx="278" cy="20" r="5" fill="none" stroke="{$text_muted}" />
	<circle cx="298" cy="20" r="5" fill="none" stroke="{$text_muted}" />
	<rect x="0" y="40" width="72" height="72" fill="{$layer}" stroke="{$header_border}" />
	<rect x="8" y="48"  width="56" height="4" fill="{$text_muted}" />
	<rect x="8" y="58"  width="48" height="4" fill="{$text_muted}" />
	<rect x="8" y="68"  width="52" height="4" fill="{$text_muted}" />
	<rect x="8" y="78"  width="44" height="4" fill="{$text_muted}" />
	<rect x="8" y="88"  width="50" height="4" fill="{$text_muted}" />
	<text x="84" y="78" fill="{$text_muted}" font-family="sans-serif" font-size="9">Article body</text>
</svg>
SVG;

			case 'application':
				return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 112" role="img" aria-label="Application preset preview">
	<rect x="0" y="0" width="320" height="40" fill="{$header_bg}" stroke="{$header_border}" />
	<rect x="8" y="12" width="16" height="16" fill="none" stroke="{$text}" />
	<line x1="11" y1="17" x2="21" y2="17" stroke="{$text}" />
	<line x1="11" y1="20" x2="21" y2="20" stroke="{$text}" />
	<line x1="11" y1="23" x2="21" y2="23" stroke="{$text}" />
	<text x="36" y="25" fill="{$text}" font-family="sans-serif" font-size="10" font-weight="600">AWT</text>
	<text x="72" y="25" fill="{$text_muted}" font-family="sans-serif" font-size="9">Dashboard</text>
	<text x="126" y="25" fill="{$text_muted}" font-family="sans-serif" font-size="9">Reports</text>
	<circle cx="240" cy="20" r="5" fill="none" stroke="{$text_muted}" />
	<circle cx="262" cy="20" r="5" fill="none" stroke="{$text_muted}" />
	<circle cx="284" cy="20" r="5" fill="none" stroke="{$text_muted}" />
	<circle cx="306" cy="20" r="6" fill="{$layer}" stroke="{$text_muted}" />
	<rect x="0" y="40" width="44" height="72" fill="{$layer}" stroke="{$header_border}" />
	<rect x="8" y="48" width="28" height="4" fill="{$text_muted}" />
	<rect x="8" y="58" width="28" height="4" fill="{$text_muted}" />
	<rect x="8" y="68" width="28" height="4" fill="{$text_muted}" />
	<text x="56" y="76" fill="{$text_muted}" font-family="sans-serif" font-size="9">Workspace</text>
</svg>
SVG;

			case 'public-sector':
				return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 320 112" role="img" aria-label="Public sector preset preview">
	<rect x="0" y="0" width="320" height="62" fill="{$header_bg}" stroke="{$header_border}" />
	<text x="12" y="18" fill="{$text_muted}" font-family="sans-serif" font-size="8">Ministry of …</text>
	<text x="12" y="34" fill="{$text}" font-family="sans-serif" font-size="11" font-weight="600">Public Sector Site</text>
	<text x="12" y="54" fill="{$text_muted}" font-family="sans-serif" font-size="9">Services</text>
	<text x="58" y="54" fill="{$text_muted}" font-family="sans-serif" font-size="9">About</text>
	<text x="92" y="54" fill="{$text_muted}" font-family="sans-serif" font-size="9">News</text>
	<text x="124" y="54" fill="{$text_muted}" font-family="sans-serif" font-size="9">Contact</text>
	<circle cx="284" cy="22" r="5" fill="none" stroke="{$text_muted}" />
	<text x="296" y="26" fill="{$text_muted}" font-family="sans-serif" font-size="9">EN</text>
	<rect x="0" y="72" width="320" height="40" fill="{$layer}" />
	<text x="12" y="92" fill="{$text_muted}" font-family="sans-serif" font-size="9">Content area</text>
</svg>
SVG;
		}
		return '';
	}

	/**
	 * Standard-icon catalogue for the per-system Header settings toggles.
	 *
	 * @return array Icon key => array with 'block' (detection regex), 'markup', and 'label'.
	 */
	public function get_header_icons(): array {
		return array(
			'search'        => array(
				// Free Search is a plain icon button that links to WordPress's
				// built-in search (`/?s=`). The regex also matches a legacy
				// awt/header-search block, so an older header still detects +
				// toggles off correctly. The inline search FIELD is an AWT
				// Premium widget — implemented in the awt-premium repo
				// (premium-staging/header-search), not in this free plugin.
				'block'  => '/<!-- wp:awt\/header-search( \{[^}]*\})? \/-->|<!-- wp:awt\/header-action \{[^}]*"iconName":"search"[^}]*\} \/-->/',
				'markup' => '<!-- wp:awt/header-action {"iconName":"search","label":"Search","href":"/?s="} /-->',
				'label'  => __( 'Search', 'awt' ),
			),
			'notifications' => array(
				// Free Notifications is a plain icon button (the notifications
				// PANEL is an AWT Premium widget). Default link is the site root;
				// the author can repoint it in the Site Editor.
				'block'  => '/<!-- wp:awt\/header-action \{[^}]*"iconName":"notification"[^}]*\} \/-->/',
				'markup' => '<!-- wp:awt/header-action {"iconName":"notification","label":"Notifications","href":"/"} /-->',
				'label'  => __( 'Notifications', 'awt' ),
			),
			'user'          => array(
				// Free User menu is a plain icon button (the user MENU is an AWT
				// Premium widget). Default link is the WordPress login/account
				// page; the author can repoint it in the Site Editor.
				'block'  => '/<!-- wp:awt\/header-action \{[^}]*"iconName":"user--avatar"[^}]*\} \/-->/',
				'markup' => '<!-- wp:awt/header-action {"iconName":"user--avatar","label":"Account","href":"/wp-login.php"} /-->',
				'label'  => __( 'User menu', 'awt' ),
			),
			'color-toggle'  => array(
				'block'  => '/<!-- wp:awt\/color-scheme-toggle( \{[^}]*\})? \/-->/',
				'markup' => '<!-- wp:awt/color-scheme-toggle {"kind":"icon-only"} /-->',
				'label'  => __( 'Color-scheme toggle', 'awt' ),
			),
		);
	}

	/*
	====================================================================
	 * 5. Accessibility audit  (moved from inc/contrast.php)
	 * ==================================================================
	 */

	/**
	 * Per-scope resolved Carbon palette used by the contrast audit.
	 *
	 * @return array Scope ('white', 'g10', 'g90', 'g100') => array of token slug => hex.
	 */
	public function get_resolved_palette(): array {
		return array(
			'white' => array(
				'background'            => '#ffffff',
				'layer-01'              => '#f4f4f4',
				'layer-02'              => '#ffffff',
				'layer-03'              => '#f4f4f4',
				'field-01'              => '#f4f4f4',
				'field-02'              => '#ffffff',
				'field-03'              => '#f4f4f4',
				'text-primary'          => '#161616',
				'text-secondary'        => '#525252',
				'text-placeholder'      => '#a8a8a8',
				'text-helper'           => '#6f6f6f',
				'text-error'            => '#da1e28',
				'text-inverse'          => '#ffffff',
				'text-on-color'         => '#ffffff',
				'link-primary'          => '#0f62fe',
				'link-secondary'        => '#0043ce',
				'link-visited'          => '#8a3ffc',
				'border-subtle'         => '#e0e0e0',
				'border-strong'         => '#8d8d8d',
				'border-inverse'        => '#161616',
				'border-interactive'    => '#0f62fe',
				'focus'                 => '#0f62fe',
				'interactive'           => '#0f62fe',
				'support-error'         => '#da1e28',
				'support-success'       => '#24a148',
				'support-warning'       => '#f1c21b',
				'support-info'          => '#0043ce',
				'button-primary'        => '#0f62fe',
				'button-secondary'      => '#393939',
				'button-tertiary'       => '#0f62fe',
				'button-danger-primary' => '#da1e28',
			),
			'g10'   => array(
				'background'            => '#f4f4f4',
				'layer-01'              => '#ffffff',
				'layer-02'              => '#f4f4f4',
				'layer-03'              => '#ffffff',
				'field-01'              => '#ffffff',
				'field-02'              => '#f4f4f4',
				'field-03'              => '#ffffff',
				'text-primary'          => '#161616',
				'text-secondary'        => '#525252',
				'text-placeholder'      => '#a8a8a8',
				'text-helper'           => '#6f6f6f',
				'text-error'            => '#da1e28',
				'text-inverse'          => '#ffffff',
				'text-on-color'         => '#ffffff',
				'link-primary'          => '#0f62fe',
				'link-secondary'        => '#0043ce',
				'link-visited'          => '#8a3ffc',
				'border-subtle'         => '#c6c6c6',
				'border-strong'         => '#8d8d8d',
				'border-inverse'        => '#161616',
				'border-interactive'    => '#0f62fe',
				'focus'                 => '#0f62fe',
				'interactive'           => '#0f62fe',
				'support-error'         => '#da1e28',
				'support-success'       => '#24a148',
				'support-warning'       => '#f1c21b',
				'support-info'          => '#0043ce',
				'button-primary'        => '#0f62fe',
				'button-secondary'      => '#393939',
				'button-tertiary'       => '#0f62fe',
				'button-danger-primary' => '#da1e28',
			),
			'g90'   => array(
				'background'            => '#262626',
				'layer-01'              => '#393939',
				'layer-02'              => '#525252',
				'layer-03'              => '#6f6f6f',
				'field-01'              => '#393939',
				'field-02'              => '#525252',
				'field-03'              => '#6f6f6f',
				'text-primary'          => '#f4f4f4',
				'text-secondary'        => '#c6c6c6',
				'text-placeholder'      => '#6f6f6f',
				'text-helper'           => '#a8a8a8',
				'text-error'            => '#ffb3b8',
				'text-inverse'          => '#161616',
				'text-on-color'         => '#ffffff',
				'link-primary'          => '#78a9ff',
				'link-secondary'        => '#a6c8ff',
				'link-visited'          => '#be95ff',
				'border-subtle'         => '#525252',
				'border-strong'         => '#a8a8a8',
				'border-inverse'        => '#f4f4f4',
				'border-interactive'    => '#4589ff',
				'focus'                 => '#ffffff',
				'interactive'           => '#4589ff',
				'support-error'         => '#ff8389',
				'support-success'       => '#42be65',
				'support-warning'       => '#f1c21b',
				'support-info'          => '#4589ff',
				'button-primary'        => '#0f62fe',
				'button-secondary'      => '#6f6f6f',
				'button-tertiary'       => '#ffffff',
				'button-danger-primary' => '#da1e28',
			),
			'g100'  => array(
				'background'            => '#161616',
				'layer-01'              => '#262626',
				'layer-02'              => '#393939',
				'layer-03'              => '#525252',
				'field-01'              => '#262626',
				'field-02'              => '#393939',
				'field-03'              => '#525252',
				'text-primary'          => '#f4f4f4',
				'text-secondary'        => '#c6c6c6',
				'text-placeholder'      => '#6f6f6f',
				'text-helper'           => '#a8a8a8',
				'text-error'            => '#ff8389',
				'text-inverse'          => '#161616',
				'text-on-color'         => '#ffffff',
				'link-primary'          => '#78a9ff',
				'link-secondary'        => '#a6c8ff',
				'link-visited'          => '#be95ff',
				'border-subtle'         => '#393939',
				'border-strong'         => '#6f6f6f',
				'border-inverse'        => '#f4f4f4',
				'border-interactive'    => '#4589ff',
				'focus'                 => '#ffffff',
				'interactive'           => '#4589ff',
				'support-error'         => '#fa4d56',
				'support-success'       => '#42be65',
				'support-warning'       => '#f1c21b',
				'support-info'          => '#4589ff',
				'button-primary'        => '#0f62fe',
				'button-secondary'      => '#6f6f6f',
				'button-tertiary'       => '#ffffff',
				'button-danger-primary' => '#da1e28',
			),
		);
	}

	/**
	 * Role taxonomy for the contrast audit: which token pairings are checked, at which threshold.
	 *
	 * @return array Token slug => array with 'role', 'pairings' (each with 'against', 'threshold', 'label', optional 'notes'), and optional 'notes'.
	 */
	public function get_role_map(): array {
		return array(
			// ----- TEXT (4.5:1 minimum) -----
			'text-primary'          => array(
				'role'     => __( 'Text — primary body', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'text',
						'label'     => __( 'on background', 'awt' ),
					),
					array(
						'against'   => 'layer-01',
						'threshold' => 'text',
						'label'     => __( 'on layer-01', 'awt' ),
					),
					array(
						'against'   => 'field-01',
						'threshold' => 'text',
						'label'     => __( 'in field input', 'awt' ),
					),
				),
			),
			'text-secondary'        => array(
				'role'     => __( 'Text — secondary', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'text',
						'label'     => __( 'on background', 'awt' ),
					),
					array(
						'against'   => 'layer-01',
						'threshold' => 'text',
						'label'     => __( 'on layer-01', 'awt' ),
					),
				),
			),
			'text-helper'           => array(
				'role'     => __( 'Text — helper / caption', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'text',
						'label'     => __( 'on background', 'awt' ),
					),
					array(
						'against'   => 'layer-01',
						'threshold' => 'text',
						'label'     => __( 'on layer-01', 'awt' ),
					),
				),
			),
			'text-placeholder'      => array(
				'role'     => __( 'Text — placeholder', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'field-01',
						'threshold' => 'text',
						'label'     => __( 'in field input', 'awt' ),
						'notes'     => __( 'Carbon Design System default — we suggest updating text-placeholder to a higher-contrast value (e.g., #767676 reaches 4.54:1 against field-01).', 'awt' ),
					),
					array(
						'against'   => 'field-02',
						'threshold' => 'text',
						'label'     => __( 'in field input (alt)', 'awt' ),
						'notes'     => __( 'Carbon Design System default — same value as the field-01 pairing above; we suggest updating to meet the 4.5:1 body-text threshold.', 'awt' ),
					),
				),
			),
			'text-error'            => array(
				'role'     => __( 'Text — error message', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'text',
						'label'     => __( 'on background', 'awt' ),
					),
					array(
						'against'   => 'layer-01',
						'threshold' => 'text',
						'label'     => __( 'on layer-01', 'awt' ),
					),
				),
			),
			'text-on-color'         => array(
				'role'     => __( 'Text — on accent surfaces', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'button-primary',
						'threshold' => 'text',
						'label'     => __( 'on Button primary', 'awt' ),
					),
					array(
						'against'   => 'button-secondary',
						'threshold' => 'text',
						'label'     => __( 'on Button secondary', 'awt' ),
					),
					array(
						'against'   => 'button-danger-primary',
						'threshold' => 'text',
						'label'     => __( 'on Button danger', 'awt' ),
					),
					array(
						'against'   => 'support-error',
						'threshold' => 'text',
						'label'     => __( 'on Support error', 'awt' ),
						'notes'     => __( 'Carbon Design System default — in dark scope (g90/g100), Support error brightens (#fa4d56) and white text drops to ~3.35:1, below the 4.5:1 body threshold. We suggest reserving banner body text for ≥18pt sizes, or pairing the banner with an icon shape that carries the meaning.', 'awt' ),
					),
					array(
						'against'   => 'support-info',
						'threshold' => 'text',
						'label'     => __( 'on Support info', 'awt' ),
						'notes'     => __( 'Carbon Design System default — same tradeoff as Support error in dark scope. White text on dark-mode #4589ff falls below the 4.5:1 body threshold.', 'awt' ),
					),
				),
			),
			'text-inverse'          => array(
				'role'     => __( 'Text — inverse (light on dark)', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'border-inverse',
						'threshold' => 'text',
						'label'     => __( 'on inverse surface', 'awt' ),
					),
				),
				'notes'    => __( 'Used on inverted UI like tooltips. Inverse surfaces are usually the opposite-scope background token.', 'awt' ),
			),

			// ----- LINKS (4.5:1) -----
			'link-primary'          => array(
				'role'     => __( 'Link — primary', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'text',
						'label'     => __( 'on background', 'awt' ),
					),
					array(
						'against'   => 'layer-01',
						'threshold' => 'text',
						'label'     => __( 'on layer-01', 'awt' ),
					),
				),
			),
			'link-secondary'        => array(
				'role'     => __( 'Link — hover', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'text',
						'label'     => __( 'on background', 'awt' ),
					),
					array(
						'against'   => 'layer-01',
						'threshold' => 'text',
						'label'     => __( 'on layer-01', 'awt' ),
					),
				),
			),
			'link-visited'          => array(
				'role'     => __( 'Link — visited', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'text',
						'label'     => __( 'on background', 'awt' ),
					),
					array(
						'against'   => 'layer-01',
						'threshold' => 'text',
						'label'     => __( 'on layer-01', 'awt' ),
					),
				),
			),

			// ----- BUTTON SURFACES (UI 3:1 — edge of button vs page surface) -----
			'button-primary'        => array(
				'role'     => __( 'Button — primary surface', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'ui',
						'label'     => __( 'button edge on background', 'awt' ),
					),
				),
				'notes'    => __( 'Button-primary IS a surface; the text on top is checked via "Text — on accent surfaces" above.', 'awt' ),
			),
			'button-secondary'      => array(
				'role'     => __( 'Button — secondary surface', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'ui',
						'label'     => __( 'button edge on background', 'awt' ),
					),
				),
			),
			'button-tertiary'       => array(
				'role'     => __( 'Button — tertiary (outline)', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'text',
						'label'     => __( 'outline/text on background', 'awt' ),
					),
				),
				'notes'    => __( 'Tertiary buttons are outline-only — the same token serves as border AND text. Needs full text contrast.', 'awt' ),
			),
			'button-danger-primary' => array(
				'role'     => __( 'Button — danger surface', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'ui',
						'label'     => __( 'button edge on background', 'awt' ),
					),
				),
			),

			// ----- STATUS / SUPPORT (UI 3:1 for status icons; text 4.5:1 if pure text) -----
			'support-error'         => array(
				'role'     => __( 'Status — error icon / accent', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'ui',
						'label'     => __( 'icon on background', 'awt' ),
					),
					array(
						'against'   => 'layer-01',
						'threshold' => 'ui',
						'label'     => __( 'icon on layer-01', 'awt' ),
					),
				),
			),
			'support-success'       => array(
				'role'     => __( 'Status — success icon / accent', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'ui',
						'label'     => __( 'icon on background', 'awt' ),
					),
					array(
						'against'   => 'layer-01',
						'threshold' => 'ui',
						'label'     => __( 'icon on layer-01', 'awt' ),
					),
				),
			),
			'support-warning'       => array(
				'role'     => __( 'Status — warning icon / accent', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'ui',
						'label'     => __( 'icon on background', 'awt' ),
						'notes'     => __( 'Carbon Design System default — warning yellow #f1c21b fails the 3:1 UI threshold on light surfaces. We suggest always pairing it with an icon SHAPE so meaning isn\'t conveyed by color alone, or substituting a higher-contrast amber if you use it for non-text UI.', 'awt' ),
					),
					array(
						'against'   => 'layer-01',
						'threshold' => 'ui',
						'label'     => __( 'icon on layer-01', 'awt' ),
						'notes'     => __( 'Same — Carbon\'s default warning yellow fails on every light surface; pair with icon shape or substitute.', 'awt' ),
					),
				),
				'notes'    => __( 'Warning yellow is famously low-contrast — Carbon\'s guidance is to always pair it with an icon shape, never use it for text.', 'awt' ),
			),
			'support-info'          => array(
				'role'     => __( 'Status — info icon / accent', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'ui',
						'label'     => __( 'icon on background', 'awt' ),
					),
					array(
						'against'   => 'layer-01',
						'threshold' => 'ui',
						'label'     => __( 'icon on layer-01', 'awt' ),
					),
				),
			),

			// ----- BORDERS / FOCUS (UI 3:1) -----
			'border-strong'         => array(
				'role'     => __( 'Border — strong (field borders, dividers)', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'ui',
						'label'     => __( 'on background', 'awt' ),
					),
					array(
						'against'   => 'field-01',
						'threshold' => 'ui',
						'label'     => __( 'around field input', 'awt' ),
					),
				),
			),
			'border-interactive'    => array(
				'role'     => __( 'Border — interactive (focused field)', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'ui',
						'label'     => __( 'on background', 'awt' ),
					),
				),
			),
			'focus'                 => array(
				'role'     => __( 'Focus ring', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'ui',
						'label'     => __( 'on background', 'awt' ),
					),
					array(
						'against'   => 'button-primary',
						'threshold' => 'ui',
						'label'     => __( 'on primary button', 'awt' ),
						'notes'     => __( 'Carbon Design System default — focus and button-primary share the same blue (#0f62fe). Carbon\'s CSS mitigates with an inner-shadow + focus-inset (white) double-ring technique on Button. If you change either token, port that technique so the focus ring stays visible on the primary button.', 'awt' ),
					),
				),
			),
			'interactive'           => array(
				'role'     => __( 'Interactive — accent for active states', 'awt' ),
				'pairings' => array(
					array(
						'against'   => 'background',
						'threshold' => 'ui',
						'label'     => __( 'on background', 'awt' ),
					),
					array(
						'against'   => 'layer-01',
						'threshold' => 'ui',
						'label'     => __( 'on layer-01', 'awt' ),
					),
				),
			),
		);
	}

	/** Structural/surface tokens listed in the audit without ratio checks. */
	public function get_surface_tokens(): array {
		return array( 'background', 'layer-01', 'layer-02', 'layer-03', 'field-01', 'field-02', 'field-03', 'border-subtle' );
	}

	/** Tokens exempt from the contrast-ratio audit entirely. */
	public function get_exempt_tokens(): array {
		return array( 'text-disabled', 'button-disabled', 'focus-inset', 'focus-inverse', 'border-inverse', 'link-inverse', 'button-danger-secondary' );
	}

	/*
	====================================================================
	 * 6. Component registry + CSS class layer
	 *
	 * The 34 gated Carbon component slugs. Each maps (in classes_for) to the
	 * `cds--*` class strings the blocks currently hardcode. AWT-native blocks
	 * (hero / stat / testimonial / feature-grid / inline-set / icon / section
	 * / faq-item / pricing-tile / color-scheme-toggle) carry no awt_component
	 * and are intentionally absent — they're design-system-neutral.
	 * ==================================================================
	 */

	/**
	 * The 34 gated Carbon component slugs that classes_for() can resolve.
	 *
	 * @return string[] Component slugs.
	 */
	public function supported_components(): array {
		return array(
			'accordion',
			'breadcrumb',
			'button',
			'checkbox',
			'code-snippet',
			'content-switcher',
			'data-table',
			'dropdown',
			'footer',
			'form',
			'header-action',
			'header-brand',
			'header-global',
			'header-nav',
			'header-search',
			'link',
			'list',
			'menu-button',
			'modal',
			'notification',
			'pagination',
			'password-input',
			'radio',
			'select',
			'side-nav',
			'skip-link',
			'tabs',
			'tag',
			'text-area',
			'text-input',
			'tile',
			'toggle',
			'toggletip',
			'tooltip',
		);
	}

	/**
	 * Resolve a (component, variants) pair to a Carbon `cds--*` class string.
	 *
	 * @param string $component Conceptual component slug, e.g. 'button'.
	 * @param array  $variants  Variant modifiers; the 'element' key selects the sub-element being styled (default 'root').
	 * @return string Space-separated class string; '' for unsupported components or unclassed elements.
	 */
	public function classes_for( string $component, array $variants = array() ): string {
		if ( ! in_array( $component, $this->supported_components(), true ) ) {
			return ''; // Orphaned-block fallback: emit DOM + ARIA, no design-system classes.
		}
		$method = 'classes_' . str_replace( '-', '_', $component );
		if ( method_exists( $this, $method ) ) {
			$element = (string) ( $variants['element'] ?? 'root' );
			return trim( (string) $this->$method( $element, $variants ) );
		}
		return '';
	}

	/**
	 * Per-component one-line blurbs.
	 *
	 * @return array Component slug => human-readable description.
	 */
	public function component_descriptions(): array {
		return array(
			'accordion'        => __( 'Vertically stacked, expandable sections.', 'awt' ),
			'breadcrumb'       => __( 'Navigation trail showing page hierarchy.', 'awt' ),
			'button'           => __( 'Primary, secondary, tertiary, ghost, and danger actions.', 'awt' ),
			'checkbox'         => __( 'Multi-select form control.', 'awt' ),
			'code-snippet'     => __( 'Inline, single-line, and multi-line code blocks with copy.', 'awt' ),
			'content-switcher' => __( 'Toggle between related views.', 'awt' ),
			'data-table'       => __( 'Sortable tabular data.', 'awt' ),
			'dropdown'         => __( 'Single-select listbox.', 'awt' ),
			'footer'           => __( 'UI-shell footer sections and links.', 'awt' ),
			'form'             => __( 'Accessible form wrapper with title and description.', 'awt' ),
			'header-action'    => __( 'UI-shell header icon action.', 'awt' ),
			'header-brand'     => __( 'UI-shell brand / logo link.', 'awt' ),
			'header-global'    => __( 'UI-shell global action cluster.', 'awt' ),
			'header-nav'       => __( 'UI-shell primary navigation.', 'awt' ),
			'header-search'    => __( 'UI-shell expanding search.', 'awt' ),
			'link'             => __( 'Standalone and inline hyperlinks.', 'awt' ),
			'list'             => __( 'Ordered and unordered lists.', 'awt' ),
			'menu-button'      => __( 'Button that opens an action menu.', 'awt' ),
			'modal'            => __( 'Dialog overlay.', 'awt' ),
			'notification'     => __( 'Inline and toast status messages.', 'awt' ),
			'pagination'       => __( 'Page navigation.', 'awt' ),
			'password-input'   => __( 'Password field with visibility toggle.', 'awt' ),
			'radio'            => __( 'Single-select radio group.', 'awt' ),
			'select'           => __( 'Native select control.', 'awt' ),
			'side-nav'         => __( 'UI-shell side navigation.', 'awt' ),
			'skip-link'        => __( 'Skip-to-content bypass link.', 'awt' ),
			'tabs'             => __( 'Tabbed content panels.', 'awt' ),
			'tag'              => __( 'Categorical labels.', 'awt' ),
			'text-area'        => __( 'Multi-line text field.', 'awt' ),
			'text-input'       => __( 'Single-line text field.', 'awt' ),
			'tile'             => __( 'Content container; clickable / selectable / expandable.', 'awt' ),
			'toggle'           => __( 'On/off switch.', 'awt' ),
			'toggletip'        => __( 'Click-triggered contextual popover.', 'awt' ),
			'tooltip'          => __( 'Hover/focus contextual hint.', 'awt' ),
		);
	}

	/*
	--------------------------------------------------------------------
	 * classes_for resolvers — one private method per component slug.
	 *
	 * Stage 1 / Task A status: each method returns the correct ROOT class
	 * (with root-level variant modifiers where applicable) so the registry
	 * contract holds and the inserter filter has accurate data. Per-element
	 * cases (element => 'prefix', 'icon', etc.) are filled in during the
	 * block render.php migration (Task C), verified in-browser per block.
	 * ------------------------------------------------------------------
	 */

	/**
	 * Carbon CSS classes for the Accordion component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_accordion( string $el, array $v ): string {
		switch ( $el ) {
			case 'item':
				$c = 'cds--accordion__item';
				if ( ! empty( $v['defaultExpanded'] ) ) {
					$c .= ' cds--accordion__item--active'; }
				if ( ! empty( $v['disabled'] ) ) {
					$c .= ' cds--accordion__item--disabled'; }
				return $c;
			case 'heading':
				return 'cds--accordion__heading';
			case 'title':
				return 'cds--accordion__title';
			case 'content':
				return 'cds--accordion__content';
			case 'arrow':
				return 'cds--accordion__arrow';
			default:
				$align = $v['align'] ?? '';
				$size  = $v['size'] ?? '';
				$c     = 'cds--accordion';
				if ( $align ) {
					$c .= ' cds--accordion--' . $align; }
				if ( $size ) {
					$c .= ' cds--accordion--' . $size; }
				return $c;
		}
	}

	/**
	 * Carbon CSS classes for the Breadcrumb component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_breadcrumb( string $el, array $v ): string {
		switch ( $el ) {
			case 'list':
				return 'cds--breadcrumb__list';
			case 'item':
				return 'cds--breadcrumb-item';
			case 'link':
				return 'cds--link';
			default:
				$c = 'cds--breadcrumb';
				if ( ! empty( $v['noTrailingSlash'] ) ) {
					$c .= ' cds--breadcrumb--no-trailing-slash'; }
				return $c;
		}
	}

	/**
	 * Carbon CSS classes for the Button component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_button( string $el, array $v ): string {
		switch ( $el ) {
			case 'icon':
				return 'cds--btn__icon';
			default:
				$kind      = $v['kind'] ?? 'primary';
				$size      = $v['size'] ?? 'lg';
				$modifiers = array( $kind, $size );
				if ( ! empty( $v['expressive'] ) ) {
					$modifiers[] = 'expressive'; }
				if ( ! empty( $v['icon_only'] ) ) {
					$modifiers[] = 'icon-only'; }
				$parts = array_merge( array( 'cds--btn' ), array_map( static fn( $m ) => 'cds--btn--' . $m, $modifiers ) );
				if ( in_array( $size, array( 'xs', 'sm', 'md', 'lg', 'xl', '2xl' ), true ) ) {
					$parts[] = 'cds--layout--size-' . $size;
				}
				return implode( ' ', $parts );
		}
	}

	/**
	 * Carbon CSS classes for the Checkbox component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_checkbox( string $el, array $v ): string {
		switch ( $el ) {
			case 'input':
				return 'cds--checkbox';
			case 'label':
				return 'cds--checkbox-label';
			case 'label-text':
				return 'cds--checkbox-label-text';
			case 'helper-text':
				return 'cds--form__helper-text';
			case 'requirement':
				return 'cds--form-requirement';
			default:
				return 'cds--form-item cds--checkbox-wrapper';
		}
	}

	/**
	 * Carbon CSS classes for the Code snippet component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_code_snippet( string $el, array $v ): string {
		switch ( $el ) {
			case 'copy-button':
				return 'cds--snippet-button cds--copy-btn';
			case 'copy-button-inline':
				return 'cds--snippet-button cds--copy-btn cds--snippet-button--inline';
			case 'container':
				return 'cds--snippet-container';
			default:
				$variant = $v['variant'] ?? 'single';
				return 'cds--snippet cds--snippet--' . $variant;
		}
	}

	/**
	 * Carbon CSS classes for the Content switcher component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_content_switcher( string $el, array $v ): string {
		switch ( $el ) {
			case 'btn':
				return 'cds--content-switcher-btn';
			case 'btn-selected':
				return 'cds--content-switcher-btn cds--content-switcher--selected';
			case 'label':
				return 'cds--content-switcher__label';
			default:
				// Layout-size utility is appended by the caller (it is not a
				// component class), matching the pre-§A render output.
				$size = $v['size'] ?? '';
				$c    = 'cds--content-switcher';
				if ( $size ) {
					$c .= ' cds--content-switcher--' . $size; }
				return $c;
		}
	}

	/**
	 * Carbon CSS classes for the Data table component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_data_table( string $el, array $v ): string {
		switch ( $el ) {
			case 'table':
				$size = $v['size'] ?? 'md';
				$c    = 'cds--data-table cds--data-table--' . $size;
				if ( ! empty( $v['zebra'] ) ) {
					$c .= ' cds--data-table--zebra'; }
				if ( ! empty( $v['useStaticWidth'] ) ) {
					$c .= ' cds--data-table--static'; }
				// NOTE: the sticky-header modifier intentionally goes on the
				// CONTAINER only (see the default branch below), not the table.
				// Carbon's `.cds--data-table--sticky-header` table rules reflow
				// the table into flex scaffolding (tr{display:flex}, etc.) that
				// only works with Carbon's React component; with our plain
				// <table> it breaks column layout and the header never pins.
				// Our theme.css makes the container the scroll box and pins the
				// <thead> there instead.
				if ( ! empty( $v['sortable'] ) ) {
					$c .= ' cds--data-table--sort'; }
				return $c;
			case 'sort-btn':
				return 'cds--table-sort';
			case 'sort-flex':
				return 'cds--table-sort__flex';
			case 'header-label':
				return 'cds--table-header-label';
			case 'visually-hidden':
				return 'cds--visually-hidden';
			default:
				$c = 'cds--data-table-container';
				if ( ! empty( $v['stickyHeader'] ) ) {
					$c .= ' cds--data-table-container--sticky-header'; }
				return $c;
		}
	}

	/**
	 * Carbon CSS classes for the Dropdown (list box) component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_dropdown( string $el, array $v ): string {
		switch ( $el ) {
			case 'inner': // The inner .cds--dropdown .cds--list-box element.
				$size = $v['size'] ?? 'md';
				$c    = 'cds--dropdown cds--list-box cds--list-box--' . $size;
				if ( ! empty( $v['invalid'] ) ) {
					$c .= ' cds--dropdown--invalid'; }
				if ( ! empty( $v['disabled'] ) ) {
					$c .= ' cds--dropdown--disabled'; }
				if ( in_array( $size, array( 'sm', 'md', 'lg' ), true ) ) {
					$c .= ' cds--layout--size-' . $size; }
				return $c;
			case 'label':
				return 'cds--label';
			case 'trigger':
				return 'cds--list-box__field';
			case 'trigger-label':
				return 'cds--list-box__label';
			case 'menu-icon':
				return 'cds--list-box__menu-icon';
			case 'menu':
				return 'cds--list-box__menu';
			case 'menu-item':
				return 'cds--list-box__menu-item';
			case 'menu-item-option':
				return 'cds--list-box__menu-item__option';
			case 'error':
				return 'cds--form-requirement';
			case 'helper':
				return 'cds--form__helper-text';
			default:
				return 'cds--dropdown__wrapper'; // Outer wrapper + inserter probe.
		}
	}

	/**
	 * Carbon CSS classes for the UI-shell footer section.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_footer( string $el, array $v ): string {
		switch ( $el ) {
			case 'link':
				return 'cds--footer__link';
			case 'anchor':
				return 'cds--link';
			case 'heading':
				return 'cds--footer__heading';
			case 'links':
				return 'cds--footer__links';
			default:
				return 'cds--footer__section';
		}
	}

	/**
	 * Carbon CSS classes for the Form wrapper component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_form( string $el, array $v ): string {
		switch ( $el ) {
			case 'header':
				return 'cds--form__header';
			case 'title':
				return 'cds--form__title';
			case 'description':
				return 'cds--form__description';
			default:
				return 'cds--form';
		}
	}

	/**
	 * Carbon CSS classes for the UI-shell header action button.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_header_action( string $el, array $v ): string {
		switch ( $el ) {
			case 'icon':
				return 'cds--header__action-icon';
			case 'label':
				return 'cds--header__action-label';
			default:
				return 'cds--header__action';
		}
	}

	/**
	 * Carbon CSS classes for the UI-shell header brand / logo link.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_header_brand( string $el, array $v ): string {
		switch ( $el ) {
			case 'prefix':
				return 'cds--header__name--prefix';
			case 'name-text':
				return 'cds--header__name--text';
			case 'logo':
				return 'cds--header__logo';
			case 'logo-light':
				return 'cds--header__logo cds--header__logo--light';
			case 'logo-dark':
				return 'cds--header__logo cds--header__logo--dark';
			default:
				return 'cds--header__name';
		}
	}

	/**
	 * Carbon CSS classes for the UI-shell header global action cluster.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_header_global( string $el, array $v ): string {
		return 'cds--header__global';
	}

	/**
	 * Carbon CSS classes for the UI-shell header navigation.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_header_nav( string $el, array $v ): string {
		switch ( $el ) {
			case 'menu-bar':
				return 'cds--header__menu-bar';
			case 'menu-item':
				return 'cds--header__menu-item';
			// Multi-level menu (awt/header-menu) — Carbon HeaderMenu grammar.
			case 'submenu':
				return 'cds--header__submenu';
			case 'menu-title':
				return 'cds--header__menu-item cds--header__menu-title';
			case 'menu':
				return 'cds--header__menu';
			case 'menu-arrow':
				return 'cds--header__menu-arrow';
			// Responsive hamburger. The `__menu-toggle__hidden` class is what
			// Carbon's bundled CSS uses to hide the trigger at >=66rem.
			case 'trigger':
				return 'cds--header__action cds--header__menu-trigger cds--header__menu-toggle cds--header__menu-toggle__hidden';
			default:
				return 'cds--header__nav';
		}
	}

	/**
	 * Carbon CSS classes for the UI-shell header search.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_header_search( string $el, array $v ): string {
		switch ( $el ) {
			case 'action':
				return 'cds--header__action';
			case 'icon':
				return 'cds--header__action-icon';
			default:
				return 'cds--header__search';
		}
	}

	/**
	 * Carbon CSS classes for the Link component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_link( string $el, array $v ): string {
		switch ( $el ) {
			case 'icon':
				return 'cds--link__icon';
			default:
				$size      = $v['size'] ?? 'md';
				$modifiers = array( $size );
				if ( ! empty( $v['inline'] ) ) {
					$modifiers[] = 'inline'; }
				if ( ! empty( $v['visited'] ) ) {
					$modifiers[] = 'visited'; }
				if ( ! empty( $v['disabled'] ) ) {
					$modifiers[] = 'disabled'; }
				$parts = array_merge( array( 'cds--link' ), array_map( static fn( $m ) => 'cds--link--' . $m, $modifiers ) );
				return implode( ' ', $parts );
		}
	}

	/**
	 * Carbon CSS classes for the List component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_list( string $el, array $v ): string {
		switch ( $el ) {
			case 'item':
				return 'cds--list__item';
			default:
				$type = $v['type'] ?? 'unordered';
				if ( $type === 'ordered-native' ) {
					$c = 'cds--list--ordered--native';
				} elseif ( $type === 'ordered' ) {
					$c = 'cds--list--ordered';
				} else {
					$c = 'cds--list--unordered';
				}
				if ( ! empty( $v['isExpressive'] ) ) {
					$c .= ' cds--list--expressive'; }
				if ( ! empty( $v['nested'] ) ) {
					$c .= ' cds--list--nested'; }
				return $c;
		}
	}

	/**
	 * Carbon CSS classes for the Menu button component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_menu_button( string $el, array $v ): string {
		switch ( $el ) {
			case 'trigger':
				$kind = $v['kind'] ?? 'primary';
				$size = $v['size'] ?? 'lg';
				return 'cds--btn cds--btn--' . $kind . ' cds--btn--' . $size . ' cds--menu-button__trigger';
			case 'menu':
				$alignment = $v['menuAlignment'] ?? 'bottom';
				return 'cds--menu cds--menu--' . $alignment;
			case 'menu-item':
				return 'cds--menu-item';
			case 'menu-item-button':
				return 'cds--menu-item__button';
			default:
				return 'cds--menu-button';
		}
	}

	/**
	 * Carbon CSS classes for the Modal component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_modal( string $el, array $v ): string {
		$size   = $v['size'] ?? 'md';
		$danger = ! empty( $v['danger'] );
		switch ( $el ) {
			case 'container':
				return 'cds--modal-container cds--modal-container--' . $size;
			case 'header':
				return 'cds--modal-header';
			case 'header-label':
				return 'cds--modal-header__label';
			case 'header-heading':
				return 'cds--modal-header__heading';
			case 'close-button':
				return 'cds--modal-close-button cds--modal-close';
			case 'content':
				return 'cds--modal-content';
			case 'footer':
				return 'cds--modal-footer';
			case 'cancel-button':
				return 'cds--btn cds--btn--secondary cds--modal-cancel-button';
			case 'primary-button':
				return 'cds--btn cds--btn--' . ( $danger ? 'danger' : 'primary' ) . ' cds--modal-primary-button';
			case 'opener':
				$kind = $v['kind'] ?? 'primary';
				$sz   = $v['size'] ?? 'md';
				return 'cds--btn cds--btn--' . $kind . ' cds--btn--' . $sz;
			default:
				$c = 'cds--modal cds--modal--' . $size;
				if ( $danger ) {
					$c .= ' cds--modal--danger'; }
				return $c;
		}
	}

	/**
	 * Carbon CSS classes for the Notification component (inline and toast).
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_notification( string $el, array $v ): string {
		$variant = ( $v['variant'] ?? 'inline' ) === 'toast' ? 'toast' : 'inline';
		$base    = 'cds--' . $variant . '-notification';
		$kind    = $v['kind'] ?? '';
		$c       = $base;
		if ( $kind ) {
			$c .= ' ' . $base . '--' . $kind; }
		if ( ! empty( $v['lowContrast'] ) ) {
			$c .= ' ' . $base . '--low-contrast'; }
		return $c;
	}

	/**
	 * Carbon CSS classes for the Pagination component (nav variant).
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_pagination( string $el, array $v ): string {
		switch ( $el ) {
			case 'list':
				return 'cds--pagination-nav__list';
			case 'list-item':
				return 'cds--pagination-nav__list-item';
			case 'page':
				$c = 'cds--pagination-nav__page';
				if ( ! empty( $v['disabled'] ) ) {
					$c .= ' cds--pagination-nav__page--disabled'; }
				if ( ! empty( $v['current'] ) ) {
					$c .= ' cds--pagination-nav__page--current'; }
				if ( ! empty( $v['ellipsis'] ) ) {
					$c .= ' cds--pagination-nav__page--ellipsis'; }
				return $c;
			case 'visually-hidden':
				return 'cds--visually-hidden';
			default:
				return 'cds--pagination-nav';
		}
	}

	/**
	 * Carbon CSS classes for the Password input component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_password_input( string $el, array $v ): string {
		switch ( $el ) {
			case 'field-wrapper':
				$c = 'cds--text-input__field-wrapper';
				if ( ! empty( $v['invalid'] ) ) {
					$c .= ' cds--text-input__field-wrapper--invalid'; }
				if ( ! empty( $v['warn'] ) ) {
					$c .= ' cds--text-input__field-wrapper--warning'; }
				return $c;
			case 'input':
				$size = (string) ( $v['size'] ?? 'md' );
				$c    = 'cds--text-input';
				if ( $size !== '' ) {
					$c .= ' cds--text-input--' . $size; }
				if ( ! empty( $v['invalid'] ) ) {
					$c .= ' cds--text-input--invalid'; }
				if ( ! empty( $v['warn'] ) ) {
					$c .= ' cds--text-input--warning'; }
				$c .= ' cds--password-input';
				if ( in_array( $size, array( 'sm', 'md', 'lg' ), true ) ) {
					$c .= ' cds--layout--size-' . $size; }
				return $c;
			case 'label':
				$c = 'cds--label';
				if ( ! empty( $v['hideLabel'] ) ) {
					$c .= ' cds--visually-hidden'; }
				return $c;
			default:
				return 'cds--form-item cds--text-input-wrapper cds--password-input-wrapper';
		}
	}

	/**
	 * Carbon CSS classes for the Radio button group component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_radio( string $el, array $v ): string {
		switch ( $el ) {
			case 'group-root':
				$orientation    = $v['orientation'] ?? 'horizontal';
				$label_position = $v['label_position'] ?? 'right';
				$c              = 'cds--radio-button-group';
				$c             .= ' cds--radio-button-group--' . $orientation;
				$c             .= ' cds--radio-button-group--label-' . $label_position;
				if ( ! empty( $v['invalid'] ) ) {
					$c .= ' cds--radio-button-group--invalid'; }
				return $c;
			case 'group-legend':
				return 'cds--label';
			case 'group-helper':
				return 'cds--form__helper-text';
			case 'group-error':
				return 'cds--form-requirement';
			case 'button-wrapper':
				return 'cds--radio-button-wrapper';
			case 'button-input':
				return 'cds--radio-button';
			case 'button-label':
				return 'cds--radio-button__label';
			case 'button-appearance':
				return 'cds--radio-button__appearance';
			case 'button-label-text':
				return 'cds--radio-button__label-text';
			default:
				return 'cds--radio-button-group';
		}
	}

	/**
	 * Carbon CSS classes for the Select component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_select( string $el, array $v ): string {
		switch ( $el ) {
			case 'input':
				$size = (string) ( $v['size'] ?? 'md' );
				$c    = 'cds--select-input cds--select-input--' . $size;
				if ( ! empty( $v['invalid'] ) ) {
					$c .= ' cds--select-input--invalid'; }
				if ( in_array( $size, array( 'sm', 'md', 'lg' ), true ) ) {
					$c .= ' cds--layout--size-' . $size; }
				return $c;
			case 'label':
				$c = 'cds--label';
				if ( ! empty( $v['hideLabel'] ) ) {
					$c .= ' cds--visually-hidden'; }
				return $c;
			default:
				$c = 'cds--form-item cds--select';
				if ( ! empty( $v['invalid'] ) ) {
					$c .= ' cds--select--invalid'; }
				return $c;
		}
	}

	/**
	 * Carbon CSS classes for the UI-shell side navigation.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_side_nav( string $el, array $v ): string {
		switch ( $el ) {
			case 'navigation':
				return 'cds--side-nav__navigation';
			case 'items':
				return 'cds--side-nav__items';
			case 'divider':
				return 'cds--side-nav__divider';
			case 'item':
				return 'cds--side-nav__item';
			case 'link':
				$c = 'cds--side-nav__link';
				if ( ! empty( $v['isCurrent'] ) ) {
					$c .= ' cds--side-nav__link--current'; }
				return $c;
			case 'icon':
				return 'cds--side-nav__icon';
			case 'link-text':
				return 'cds--side-nav__link-text';
			case 'section':
				// No `--expanded` variant: nothing defines that class, in Carbon
				// or here, so it changed nothing. A section is a static group.
				return 'cds--side-nav__section';
			case 'heading':
				return 'cds--side-nav__heading';
			case 'menu':
				return 'cds--side-nav__menu';
			default:
				$mode = $v['mode'] ?? '';
				$c    = 'cds--side-nav';
				if ( $mode ) {
					$c .= ' cds--side-nav--' . $mode; }
				// `persistent` is AWT's name for the one mode a side nav has; the
				// class that carries Carbon's actual "docked under the UI Shell
				// header" layout is `--ux`. It supplies `inset-block-start: 3rem`
				// (so the nav starts below the 3rem header instead of covering the
				// brand) and `inline-size: 0` below Carbon's lg breakpoint. Reusing
				// Carbon's own class rather than restating those values keeps us on
				// whatever Carbon does next.
				//
				// No `--expanded`: Carbon adds it as the runtime open state of a
				// collapsible nav, and ours is not collapsible — `--ux` already
				// gives the docked panel its 16rem. Emitting it also broke the
				// narrow screen, since `--expanded`'s unconditional 16rem beat
				// `--ux`'s media-query 0.
				if ( $mode === 'persistent' ) {
					$c .= ' cds--side-nav--ux'; }
				return $c;
		}
	}

	/**
	 * Carbon CSS classes for the Skip-to-content link.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_skip_link( string $el, array $v ): string {
		return 'cds--skip-to-content';
	}

	/**
	 * Carbon CSS classes for the Tabs component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_tabs( string $el, array $v ): string {
		switch ( $el ) {
			case 'tab-list':
				return 'cds--tab--list';
			case 'overflow-btn-prev':
				return 'cds--tab--overflow-nav-button cds--tab--overflow-nav-button--previous cds--tab--overflow-nav-button--hidden';
			case 'overflow-btn-next':
				return 'cds--tab--overflow-nav-button cds--tab--overflow-nav-button--next cds--tab--overflow-nav-button--hidden';
			case 'nav-item':
				$c = 'cds--tabs__nav-item';
				if ( ! empty( $v['disabled'] ) ) {
					$c .= ' cds--tabs__nav-item--disabled'; }
				return $c;
			case 'nav-link':
				return 'cds--tabs__nav-link';
			case 'nav-item-label':
				return 'cds--tabs__nav-item-label';
			case 'tab-content':
				return 'cds--tab-content';
			default:
				$orientation = $v['orientation'] ?? 'horizontal';
				return 'cds--tabs cds--tabs--' . $orientation;
		}
	}

	/**
	 * Carbon CSS classes for the Tag component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_tag( string $el, array $v ): string {
		switch ( $el ) {
			case 'label':
				return 'cds--tag__label';
			case 'close-icon':
				return 'cds--tag__close-icon';
			default:
				$type = $v['type'] ?? 'gray';
				$size = $v['size'] ?? 'md';
				$c    = 'cds--tag cds--tag--' . $type . ' cds--tag--' . $size;
				if ( ! empty( $v['filter'] ) ) {
					$c .= ' cds--tag--filter'; }
				return $c;
		}
	}

	/**
	 * Carbon CSS classes for the Text area component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_text_area( string $el, array $v ): string {
		switch ( $el ) {
			case 'inner-wrapper':
				$c = 'cds--text-area__wrapper';
				if ( ! empty( $v['invalid'] ) ) {
					$c .= ' cds--text-area__wrapper--invalid'; }
				if ( ! empty( $v['warn'] ) ) {
					$c .= ' cds--text-area__wrapper--warn'; }
				return $c;
			case 'textarea':
				$c = 'cds--text-area';
				if ( ! empty( $v['invalid'] ) ) {
					$c .= ' cds--text-area--invalid'; }
				if ( ! empty( $v['warn'] ) ) {
					$c .= ' cds--text-area--warn'; }
				return $c;
			case 'label':
				$c = 'cds--label';
				if ( ! empty( $v['hideLabel'] ) ) {
					$c .= ' cds--visually-hidden'; }
				return $c;
			default:
				$c = 'cds--form-item cds--text-area-wrapper';
				if ( ! empty( $v['readonly'] ) ) {
					$c .= ' cds--text-area-wrapper--readonly'; }
				return $c;
		}
	}

	/**
	 * Carbon CSS classes for the Text input component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_text_input( string $el, array $v ): string {
		switch ( $el ) {
			case 'field-wrapper':
				$c = 'cds--text-input__field-wrapper';
				if ( ! empty( $v['invalid'] ) ) {
					$c .= ' cds--text-input__field-wrapper--invalid'; }
				if ( ! empty( $v['warn'] ) ) {
					$c .= ' cds--text-input__field-wrapper--warning'; }
				return $c;
			case 'input':
				$size = (string) ( $v['size'] ?? 'md' );
				$c    = 'cds--text-input';
				if ( $size !== '' ) {
					$c .= ' cds--text-input--' . $size; }
				if ( ! empty( $v['invalid'] ) ) {
					$c .= ' cds--text-input--invalid'; }
				if ( ! empty( $v['warn'] ) ) {
					$c .= ' cds--text-input--warning'; }
				if ( in_array( $size, array( 'sm', 'md', 'lg' ), true ) ) {
					$c .= ' cds--layout--size-' . $size; }
				return $c;
			case 'label':
				$c = 'cds--label';
				if ( ! empty( $v['inline'] ) ) {
					$c .= ' cds--label--inline'; }
				if ( ! empty( $v['hideLabel'] ) ) {
					$c .= ' cds--visually-hidden'; }
				return $c;
			default:
				$c = 'cds--form-item cds--text-input-wrapper';
				if ( ! empty( $v['inline'] ) ) {
					$c .= ' cds--text-input-wrapper--inline'; }
				if ( ! empty( $v['readonly'] ) ) {
					$c .= ' cds--text-input-wrapper--readonly'; }
				if ( ! empty( $v['fluid'] ) ) {
					$c .= ' cds--text-input--fluid'; }
				return $c;
		}
	}

	/**
	 * Carbon CSS classes for the Tile component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_tile( string $el, array $v ): string {
		switch ( $el ) {
			case 'summary':
				return 'cds--tile__summary';
			case 'summary-text':
				return 'cds--tile__summary-text';
			case 'chevron':
				return 'cds--tile__chevron';
			case 'content':
				return 'cds--tile__content';
			case 'selectable-content':
				// Single-dash `cds--tile-content`, not the double-underscore
				// `cds--tile__content` above: Carbon uses two different classes
				// and only this one sizes the label to fill the tile.
				return 'cds--tile-content';
			case 'checkmark':
				return 'cds--tile__checkmark';
			case 'checkmark-persistent':
				return 'cds--tile__checkmark cds--tile__checkmark--persistent';
			case 'input':
				return 'cds--tile-input';
			case 'group':
				return 'cds--tile-group';
			case 'group-label':
				return 'cds--label';
			default:
				$variant = $v['variant'] ?? 'default';
				$c       = 'cds--tile';
				switch ( $variant ) {
					case 'clickable':
						$c .= ' cds--tile--clickable';
						break;
					case 'selectable':
						$c .= ' cds--tile--selectable';
						if ( ! empty( $v['radio'] ) ) {
							$c .= ' cds--tile--radio';
						}
						break;
					case 'expandable':
						$c .= ' cds--tile--expandable';
						break;
				}
				return $c;
		}
	}

	/**
	 * Carbon CSS classes for the Toggle component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_toggle( string $el, array $v ): string {
		switch ( $el ) {
			case 'button':
				return 'cds--toggle__button';
			case 'label':
				return 'cds--toggle__label';
			case 'label-text':
				return ! empty( $v['hide_label'] )
					? 'cds--toggle__label-text cds--visually-hidden'
					: 'cds--toggle__label-text';
			case 'appearance':
				$size = $v['size'] ?? 'md';
				return 'cds--toggle__appearance' . ( $size === 'sm' ? ' cds--toggle__appearance--sm' : '' );
			case 'switch':
				return ! empty( $v['toggled'] )
					? 'cds--toggle__switch cds--toggle__switch--checked'
					: 'cds--toggle__switch';
			case 'text':
				return 'cds--toggle__text';
			default:
				return ! empty( $v['disabled'] ) ? 'cds--toggle cds--toggle--disabled' : 'cds--toggle';
		}
	}

	/**
	 * Carbon CSS classes for the Toggletip component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_toggletip( string $el, array $v ): string {
		switch ( $el ) {
			case 'label':
				return 'cds--toggletip-label';
			case 'button':
				return 'cds--toggletip-button';
			case 'popover':
				return 'cds--popover';
			case 'popover-content':
				return 'cds--popover-content';
			case 'content':
				return 'cds--toggletip-content';
			case 'caret':
				return 'cds--popover-caret';
			default:
				$align = $v['align'] ?? 'bottom';
				return 'cds--popover-container cds--popover--caret cds--popover--high-contrast cds--popover--' . $align . ' cds--toggletip';
		}
	}

	/**
	 * Carbon CSS classes for the Tooltip component.
	 *
	 * @param string $el Sub-element being styled ('root' when not specified).
	 * @param array  $v  Variant modifiers from the block's attributes.
	 * @return string Space-separated `cds--*` class string.
	 */
	private function classes_tooltip( string $el, array $v ): string {
		switch ( $el ) {
			case 'trigger':
				return 'cds--tooltip__trigger';
			case 'content':
				return 'cds--tooltip__content';
			default:
				$align = $v['align'] ?? '';
				$c     = 'cds--tooltip';
				if ( $align ) {
					$c .= ' cds--tooltip--' . $align; }
				return $c;
		}
	}

	/*
	====================================================================
	 * 7. Settings UI
	 * ==================================================================
	 */

	/**
	 * Carbon's AWT-Settings tab body. Fleshed out in Task F (the settings
	 * restructure) — it will render Style variation · Header preset · Header
	 * settings · Brand mode · Default prefix · Colors audit · Further
	 * customization. Until then the existing Appearance + Colors tabs in
	 * admin-settings-page.php remain the live surface, so this is unused.
	 */
	public function render_settings_tab(): void {
		// Implemented in Task F.
	}
}
