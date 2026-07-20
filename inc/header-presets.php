<?php
/**
 * Header preset content + apply mechanism.
 *
 * Two responsibilities:
 *
 *   1. preset_content( $slug ) — canonical block markup for each of the four
 *      header presets. Kept here (not read from the pattern files) so the
 *      auto-apply path doesn't depend on stripping a pattern docblock.
 *      Patterns under /patterns/header-preset-* are for the Site Editor's
 *      inserter UI; this PHP-side copy is for programmatic application.
 *
 *   2. apply_header_preset( $slug ) — writes the chosen preset's markup
 *      into the active theme's `header` wp_template_part post. WordPress
 *      then serves that DB-stored override instead of the theme file.
 *      Idempotent — repeated calls with the same slug are safe.
 *
 * Per §1 "Header style presets → Preset mechanics": "Picking a preset in
 * onboarding replaces the default header.html template part's contents".
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\HeaderPresets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return the canonical block markup for a preset slug.
 *
 * The four presets mirror the §1 spec verbatim. Each composition is a single
 * `core/group` with `tagName: header`, `className: cds--header`, and flex
 * layout — matching what `parts/header.html` produces, so the visual frame
 * is consistent regardless of which preset is active.
 *
 * `awt/header-brand` blocks INTENTIONALLY carry no `kind` / `prefix`
 * attributes here — they're meant to inherit from AWT Settings → Identity,
 * which is the same precedence chain that the default header.html relies
 * on after we stripped its hardcoded attributes.
 *
 * @param string $slug Preset slug from the active design system's header presets.
 * @return string|null Block markup for the preset, or null if the slug is unknown.
 */
function preset_content( string $slug ): ?string {
	$presets = \AWT\Theme\DesignSystem\Registry::get_active()->get_header_presets();
	$content = $presets[ $slug ]['content'] ?? '';
	return $content !== '' ? $content : null;
}

/**
 * Preset metadata (labels + descriptions) — used by the picker UI in both
 * the welcome wizard and the AWT Settings → Appearance tab.
 *
 * §A: sourced from the active design system's header presets.
 */
function preset_metadata(): array {
	$presets = \AWT\Theme\DesignSystem\Registry::get_active()->get_header_presets();
	$out     = array();
	foreach ( $presets as $slug => $preset ) {
		$out[ $slug ] = array(
			'label'       => $preset['label'] ?? $slug,
			'description' => $preset['description'] ?? '',
		);
	}
	return $out;
}

/**
 * Vertical list of four preset cards with SVG schematic previews. Used by
 * the welcome wizard step 2 and the AWT Settings → Appearance tab.
 * Emits the cards-list markup only — caller wraps in its own `<form>`
 * and adds submit/back/skip buttons.
 *
 * Relies on the `.awt-preset-card*` CSS classes — caller is responsible
 * for printing those styles. (For the wizard they're inline in
 * render_step_2; for the Appearance tab they're emitted alongside.)
 *
 * @param string $selected_slug Currently-selected preset slug, or '' for none.
 * @param string $name_attr     The name attribute for the radio group (default 'headerPreset').
 */
function picker_ui( string $selected_slug, string $name_attr = 'headerPreset' ): void {
	$presets = preset_metadata();
	?>
	<fieldset>
		<legend class="screen-reader-text"><?php esc_html_e( 'Header preset', 'awt' ); ?></legend>
		<div>
			<?php
			foreach ( $presets as $slug => $preset ) :
				$is_selected = $selected_slug === $slug;
				$card_class  = 'awt-preset-card' . ( $is_selected ? ' awt-preset-card--selected' : '' );
				?>
				<label class="<?php echo esc_attr( $card_class ); ?>">
					<div class="awt-preset-card__text">
						<input type="radio" name="<?php echo esc_attr( $name_attr ); ?>" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $is_selected ); ?> style="margin-inline-end: 0.5em;" />
						<strong><?php echo esc_html( $preset['label'] ); ?></strong>
						<p style="margin: 0.5em 0 0; color: #646970; font-size: 13px;"><?php echo esc_html( $preset['description'] ); ?></p>
					</div>
					<div class="awt-preset-card__svg" aria-hidden="true"><?php echo schematic_svg( $slug ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- hand-built SVG authored in code, no dynamic input. ?></div>
				</label>
			<?php endforeach; ?>
		</div>
	</fieldset>
	<?php
}

/**
 * The picker's preset-card CSS. Inlined here so both callers (wizard +
 * Appearance tab) include identical styles without duplication.
 */
function picker_styles(): string {
	return '
		.awt-preset-card { display: flex; gap: 1.5em; padding: 1em; border: 2px solid #c3c4c7; border-radius: 4px; cursor: pointer; background: #fff; margin-block-end: 0.75em; align-items: center; }
		.awt-preset-card--selected { border-color: #0073aa; background: #f0f6fc; }
		.awt-preset-card__text { flex: 1 1 0; min-inline-size: 0; }
		.awt-preset-card__svg { flex: 0 0 300px; max-inline-size: 40%; }
		.awt-preset-card__svg svg { display: block; inline-size: 100%; block-size: auto; border: 1px solid #dcdcde; border-radius: 2px; background: #ffffff; }
		@media (max-width: 720px) {
			.awt-preset-card { flex-direction: column; align-items: stretch; }
			.awt-preset-card__svg { flex: 0 0 auto; }
		}
	';
}

/**
 * Schematic preview SVG for each header preset. Hand-built (not generated)
 * so each one is a deliberate, accurate sketch of the preset's layout:
 *
 *   - Marketing       : brand left, primary nav center, single primary-button CTA right
 *   - Documentation   : brand left, nav center, action icons right, side-nav sidebar below
 *   - Application     : hamburger leading, brand, nav, action cluster right; side-nav overlay
 *   - Public sector   : tall header with prefix above brand; nav row below; compact actions
 *
 * Carbon-token palette hardcoded so previews render consistently regardless
 * of the user's active style variation — these are documentation, not live
 * skin previews.
 *
 * @param string $slug Preset slug to render the schematic for.
 * @return string SVG markup, or an empty string for an unknown slug.
 */
function schematic_svg( string $slug ): string {
	$presets = \AWT\Theme\DesignSystem\Registry::get_active()->get_header_presets();
	return (string) ( $presets[ $slug ]['svg'] ?? '' );
}

/**
 * Standard-icon catalogue backing the header icon toggles.
 *
 * The Appearance tab → Header settings checkboxes route through these
 * helpers to add or remove individual icons in the active header template
 * without re-applying a whole preset.
 *
 * Each entry is keyed by a stable settings slug ("search", "notifications",
 * "user", "color-toggle") and carries:
 *
 *   - block:   block-comment-grep pattern used to DETECT whether the icon
 *              is currently present. Matches the canonical markup the
 *              presets emit. A regex anchored to the block name + (when
 *              relevant) the iconName attribute.
 *   - markup:  the canonical block markup we insert when adding the icon
 *              back. Idempotent — adding twice is harmless because the
 *              detect-then-skip path in `set_header_icon()` checks first.
 *   - label:   the checkbox label shown in the UI.
 *
 * @return array Icon entries keyed by settings slug.
 */
function standard_icons(): array {
	return \AWT\Theme\DesignSystem\Registry::get_active()->get_header_icons();
}

/**
 * Effective header template-part content: the user-customized DB post if
 * one exists, otherwise the theme's `parts/header.html` file. Returns the
 * full block markup string (including the outer group + header element).
 */
function effective_header_content(): string {
	$theme_slug = get_stylesheet();

	$existing = get_posts(
		array(
			'post_type'     => 'wp_template_part',
			'post_status'   => array( 'publish', 'auto-draft' ),
			'name'          => 'header',
			'numberposts'   => 1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- single-row lookup of the theme's header template part; same query the Site Editor performs.
			'tax_query'     => array(
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => $theme_slug,
				),
			),
			'no_found_rows' => true,
		)
	);

	if ( ! empty( $existing ) ) {
		return (string) $existing[0]->post_content;
	}

	$theme_file = get_stylesheet_directory() . '/parts/header.html';
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local theme-bundled template part, not a remote URL.
	return file_exists( $theme_file ) ? (string) file_get_contents( $theme_file ) : '';
}

/**
 * Whether a standard icon is present in the current header content.
 *
 * @param string $icon_key Settings slug of the icon, e.g. 'search'.
 * @return bool True if the icon's block markup is present in the header.
 */
function header_has_icon( string $icon_key ): bool {
	$icons = standard_icons();
	if ( ! isset( $icons[ $icon_key ] ) ) {
		return false;
	}
	return (bool) preg_match( $icons[ $icon_key ]['block'], effective_header_content() );
}

/**
 * Set a single icon's presence in the active theme's `header`
 * `wp_template_part`. Adding inserts at the end of the
 * `awt/header-global` group (the canonical right-side cluster).
 * Removing strips every match — handles the case where a stale
 * duplicate was left behind by an older preset.
 *
 * Auto-creates the override post on first toggle if none exists yet,
 * seeded with the current effective content so the user's existing
 * structure isn't reset.
 *
 * Returns true on a successful write, false if the icon key is unknown,
 * the header has no `awt/header-global` group to insert into, or the
 * post write fails.
 *
 * @param string $icon_key Settings slug of the icon to toggle.
 * @param bool   $enabled  True to add the icon, false to remove it.
 * @return bool True on success (or no-op), false on failure.
 */
function set_header_icon( string $icon_key, bool $enabled ): bool {
	$icons = standard_icons();
	if ( ! isset( $icons[ $icon_key ] ) ) {
		return false;
	}
	$entry = $icons[ $icon_key ];

	$content = effective_header_content();
	if ( $content === '' ) {
		return false;
	}

	$already_present = (bool) preg_match( $entry['block'], $content );

	if ( $enabled && $already_present ) {
		return true; // No-op; nothing to write.
	}
	if ( ! $enabled && ! $already_present ) {
		return true; // Same.
	}

	if ( $enabled ) {
		// Insert at the end of the awt/header-global block. Anchored via
		// the closing block comment so we don't have to handle the inner
		// content's variable layout.
		$close_tag = '<!-- /wp:awt/header-global -->';
		$insert    = "\n" . $entry['markup'] . "\n";
		if ( strpos( $content, $close_tag ) === false ) {
			return false; // Custom header without an awt/header-global cluster.
		}
		$new_content = str_replace( $close_tag, $insert . $close_tag, $content );
	} else {
		// Remove every instance + trim now-empty surrounding whitespace.
		$new_content = preg_replace( $entry['block'], '', $content );
		// Collapse the multiple blank lines a removal can leave behind.
		$new_content = preg_replace( "/\n{3,}/", "\n\n", (string) $new_content );
	}

	return write_header_content( (string) $new_content );
}

/**
 * Write the given block markup into the active theme's `header`
 * `wp_template_part` post, creating the post if it doesn't exist yet.
 * Same write path `apply_header_preset()` uses — extracted as a helper so
 * the toggle action shares one save mechanism.
 *
 * @param string $content Full block markup to store as the header content.
 * @return bool True on a successful write, false on failure.
 */
function write_header_content( string $content ): bool {
	$theme_slug = get_stylesheet();

	$existing = get_posts(
		array(
			'post_type'     => 'wp_template_part',
			'post_status'   => array( 'publish', 'auto-draft' ),
			'name'          => 'header',
			'numberposts'   => 1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- single-row lookup of the theme's header template part; same query the Site Editor performs.
			'tax_query'     => array(
				array(
					'taxonomy' => 'wp_theme',
					'field'    => 'name',
					'terms'    => $theme_slug,
				),
			),
			'no_found_rows' => true,
		)
	);

	$post_data = array(
		'post_type'    => 'wp_template_part',
		'post_status'  => 'publish',
		'post_name'    => 'header',
		'post_title'   => 'header',
		'post_content' => $content,
		'tax_input'    => array(
			'wp_theme'              => $theme_slug,
			'wp_template_part_area' => 'header',
		),
	);

	if ( ! empty( $existing ) ) {
		$post_data['ID'] = $existing[0]->ID;
		$result          = wp_update_post( $post_data, true );
	} else {
		$result = wp_insert_post( $post_data, true );
	}

	if ( is_wp_error( $result ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[AWT] write_header_content failed: ' . $result->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- debug-gated failure trace.
		}
		return false;
	}

	$post_id = is_array( $result ) ? ( $result['ID'] ?? 0 ) : (int) $result;
	if ( ! $post_id && ! empty( $existing ) ) {
		$post_id = (int) $existing[0]->ID;
	}
	if ( $post_id ) {
		wp_set_object_terms( $post_id, $theme_slug, 'wp_theme' );
		wp_set_object_terms( $post_id, 'header', 'wp_template_part_area' );
	}

	if ( function_exists( 'wp_clean_themes_cache' ) ) {
		wp_clean_themes_cache();
	}
	delete_transient( 'wp_block_template_lookup' );

	return true;
}

/**
 * Apply a preset to the active theme's `header` template part by creating
 * or updating a `wp_template_part` post.
 *
 * WordPress's block-template stack works in three layers:
 *
 *   1. Theme files (`parts/header.html`) — read-only, lowest precedence.
 *   2. `wp_template_part` posts in the database — created when a user edits
 *      a template part in the Site Editor. Override the file when present.
 *   3. Filters (`get_block_templates`, etc.) — runtime overrides.
 *
 * Auto-apply writes at layer 2 — same row the Site Editor would write to —
 * so the user's choice survives Site Editor edits and respects the normal
 * customization flow.
 *
 * Returns true on success, false if the preset slug is unknown or the
 * write fails. Logs failures via `error_log` (no admin notice — wizard
 * flow continues regardless; the recap step shows what was applied).
 *
 * @param string $slug Preset slug to apply.
 * @return bool True on success, false if the slug is unknown or the write fails.
 */
function apply_header_preset( string $slug ): bool {
	$content = preset_content( $slug );
	if ( $content === null ) {
		return false;
	}
	return write_header_content( $content );
}
