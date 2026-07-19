<?php
/**
 * AWT Settings — admin page.
 *
 * Settings → AWT sub-menu under WordPress core's Settings. A tabbed page
 * with sections matching the §5 spec:
 *
 *   - Identity (logo, brand mode, prefix)
 *   - Navigation (skip-link text, breadcrumb home/404 text, auto-emit toggles)
 *   - Custom code (head, body-open, body-close — gated behind a11y warning)
 *   - Custom CSS (gated behind a11y warning)
 *   - Tools (re-run welcome wizard, export/import)
 *
 * Why a sub-menu and not a top-level menu: minimizes admin sprawl and
 * matches the spec's §5 placement decision ("sub-menu under WP core's
 * Settings, not a top-level menu"). Familiar pattern from Yoast / WooCommerce.
 *
 * Capability gate: `manage_options` for read + write. Standard WP admin
 * capability for theme settings. Both the menu registration and the form
 * processor check it.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\AdminPage;

use AWT\Theme\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MENU_SLUG = 'awt-settings';
const NONCE_KEY = 'awt_settings_save';

add_action( 'admin_menu', __NAMESPACE__ . '\\register_menu' );
add_action( 'admin_init', __NAMESPACE__ . '\\handle_form_submission' );
add_action( 'admin_init', __NAMESPACE__ . '\\handle_export' );
add_action( 'admin_init', __NAMESPACE__ . '\\handle_import' );
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_assets' );

/**
 * Register the sub-menu page under WP core's Settings menu.
 *
 * `options-general.php` is the parent slug — that's the literal file
 * WP-core's "Settings" menu corresponds to. Using it puts us in the
 * conventional location next to General / Writing / Reading / etc.
 */
function register_menu(): void {
	add_submenu_page(
		'options-general.php',
		__( 'AWT Settings', 'awt' ),
		__( 'AWT', 'awt' ),
		'manage_options',
		MENU_SLUG,
		__NAMESPACE__ . '\\render_page'
	);
}

/**
 * Available tabs. Order matters — first key is the default tab when
 * `?tab=` is missing. Each tab's content lives in its own renderer
 * function (render_tab_*) — keeps this file readable as the surface grows.
 */
function tabs(): array {
	// Identity leads — it's the first thing most authors set (logo, site
	// icon, brand). Being first also makes it the default tab. Typography
	// is no longer a top-level tab: it now lives as a sub-section inside the
	// active design system's tab (Carbon), between Header and Colors.
	$tabs = array(
		'identity'      => array(
			'label'  => __( 'Identity', 'awt' ),
			'render' => __NAMESPACE__ . '\\render_tab_identity',
		),
		'design-system' => array(
			'label'  => __( 'Design system', 'awt' ),
			'render' => __NAMESPACE__ . '\\render_tab_design_system',
		),
		'appearance'    => array(
			'label'  => __( 'Appearance', 'awt' ),
			'render' => __NAMESPACE__ . '\\render_tab_appearance',
		),
		'navigation'    => array(
			'label'  => __( 'Navigation', 'awt' ),
			'render' => __NAMESPACE__ . '\\render_tab_navigation',
		),
		'custom-code'   => array(
			'label'  => __( 'Custom code', 'awt' ),
			'render' => __NAMESPACE__ . '\\render_tab_custom_code',
		),
		'custom-css'    => array(
			'label'  => __( 'Custom CSS', 'awt' ),
			'render' => __NAMESPACE__ . '\\render_tab_custom_css',
		),
		'tools'         => array(
			'label'  => __( 'Tools', 'awt' ),
			'render' => __NAMESPACE__ . '\\render_tab_tools',
		),
	);
	// Filter lets the welcome wizard (and any future module) inject its
	// own tab without admin-settings-page.php needing to know it exists.
	return (array) apply_filters( 'awt_admin_settings_tabs', $tabs );
}

/**
 * Resolve the active tab slug from `$_GET['tab']`, falling back to the
 * first tab in `tabs()` if missing or invalid.
 */
function active_tab(): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab routing; sanitized, no state change.
	$requested = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';
	$tabs      = tabs();
	return isset( $tabs[ $requested ] ) ? $requested : (string) array_key_first( $tabs );
}

/**
 * Build the URL for a given tab. Used by the tab nav.
 *
 * @param string $tab_slug Slug of the tab to link to (a key of tabs()).
 * @return string Admin URL for the tab.
 */
function tab_url( string $tab_slug ): string {
	return add_query_arg(
		array(
			'page' => MENU_SLUG,
			'tab'  => $tab_slug,
		),
		admin_url( 'options-general.php' )
	);
}

/**
 * Main page render. The shell (header, tab nav, save bar) is shared;
 * the active tab's content is delegated to its renderer function.
 */
function render_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'awt' ) );
	}

	$tabs     = tabs();
	$active   = active_tab();
	$renderer = $tabs[ $active ]['render'];
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of the post-save notice flag.
	$saved = isset( $_GET['awt_saved'] ) && $_GET['awt_saved'] === '1';
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of the post-save error message; sanitized.
	$save_error = isset( $_GET['awt_error'] ) ? sanitize_text_field( wp_unslash( $_GET['awt_error'] ) ) : '';

	?>
	<div class="wrap awt-settings-page">
		<h1><?php esc_html_e( 'AWT Settings', 'awt' ); ?></h1>

		<?php if ( $saved ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Settings saved.', 'awt' ); ?></p>
			</div>
		<?php endif; ?>
		<?php if ( $save_error !== '' ) : ?>
			<div class="notice notice-error">
				<p><?php echo esc_html( $save_error ); ?></p>
			</div>
		<?php endif; ?>

		<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'AWT Settings sections', 'awt' ); ?>">
			<?php
			foreach ( $tabs as $slug => $tab ) :
				$class = 'nav-tab' . ( $slug === $active ? ' nav-tab-active' : '' );
				?>
				<a class="<?php echo esc_attr( $class ); ?>"
					href="<?php echo esc_url( tab_url( $slug ) ); ?>"
					<?php echo $slug === $active ? 'aria-current="page"' : ''; ?>>
					<?php echo esc_html( $tab['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<?php
		// Tabs that render their own forms (and don't want an outer save
		// button wrapping everything) opt out of the standard save-form
		// shell. The welcome wizard does this per step; the Appearance
		// tab does it because its two pickers each save independently.
		$self_form_tabs = array( 'welcome', 'design-system', 'appearance', 'tools', 'whats-new' );
		if ( in_array( $active, $self_form_tabs, true ) ) {
			call_user_func( $renderer );
		} else {
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'options-general.php?page=' . MENU_SLUG . '&tab=' . $active ) ); ?>" class="awt-settings-form">
				<?php
				wp_nonce_field( NONCE_KEY, '_awt_nonce' );
				echo '<input type="hidden" name="awt_active_tab" value="' . esc_attr( $active ) . '" />';
				call_user_func( $renderer );
				submit_button( __( 'Save changes', 'awt' ) );
				?>
			</form>
			<?php
		}
		?>
		<script>
		( function () {
			var page = document.querySelector( '.awt-settings-page' );
			if ( ! page ) { return; }
			var unsavedMsg = <?php echo wp_json_encode( __( 'You have unsaved changes on this tab. Leave without saving? Your changes will be lost.', 'awt' ) ); ?>;
			var dirty = false, leaving = false;

			page.querySelectorAll( 'form' ).forEach( function ( form ) {
				form.addEventListener( 'input', function () { dirty = true; } );
				form.addEventListener( 'change', function () { dirty = true; } );
				form.addEventListener( 'submit', function ( e ) {
					// Confirm only when a "destructive" choice (style variation /
					// header preset) actually changed from what is applied now.
					var field = form.getAttribute( 'data-awt-confirm-field' );
					if ( field ) {
						var orig = form.getAttribute( 'data-awt-confirm-original' ) || '';
						var msg  = form.getAttribute( 'data-awt-confirm-message' ) || '';
						var el   = form.querySelector( '[name="' + field + '"]:checked' );
						var cur  = el ? el.value : orig;
						if ( cur !== orig && msg && ! window.confirm( msg ) ) {
							e.preventDefault();
							return;
						}
					}
					leaving = true; // saving navigates away — don't also prompt
				} );
			} );

			// Prompt on tab change (top-level tabs AND the Carbon sub-tabs)
			// whenever there are unsaved edits on the current tab.
			page.querySelectorAll( 'a.nav-tab' ).forEach( function ( link ) {
				link.addEventListener( 'click', function ( e ) {
					if ( link.classList.contains( 'nav-tab-active' ) ) { return; }
					if ( dirty && ! leaving ) {
						if ( window.confirm( unsavedMsg ) ) {
							leaving = true;
						} else {
							e.preventDefault();
						}
					}
				} );
			} );
		} )();
		</script>
	</div>
	<?php
}

/**
 * POST handler. Runs early on `admin_init` so the redirect happens before
 * any HTML is sent. Standard WordPress post-redirect-get pattern.
 */
function handle_form_submission(): void {
	if ( empty( $_POST['_awt_nonce'] ) || empty( $_POST['awt_active_tab'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$nonce = sanitize_text_field( wp_unslash( $_POST['_awt_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, NONCE_KEY ) ) {
		return;
	}

	$tab  = sanitize_key( wp_unslash( $_POST['awt_active_tab'] ) );
	$tabs = tabs();
	if ( ! isset( $tabs[ $tab ] ) ) {
		return;
	}

	// Delegate to the active tab's save handler — each tab knows which
	// settings keys it owns. Keeps form-shape and persistence-shape close
	// together (one section's UI only touches its own settings keys).
	$handler = __NAMESPACE__ . '\\save_tab_' . str_replace( '-', '_', $tab );
	$error   = '';
	if ( function_exists( $handler ) ) {
		try {
			call_user_func( $handler );
		} catch ( \Throwable $e ) {
			$error = $e->getMessage();
		}
	}

	$redirect_args = array(
		'page' => MENU_SLUG,
		'tab'  => $tab,
	);
	// Carry the sub-tab section back so the author stays on the same sub-tab
	// after saving (the Carbon tab posts a hidden awt_section).
	$section = isset( $_POST['awt_section'] ) ? sanitize_key( wp_unslash( $_POST['awt_section'] ) ) : '';
	if ( $section !== '' ) {
		$redirect_args['section'] = $section;
	}
	if ( $error === '' ) {
		$redirect_args['awt_saved'] = '1';
	} else {
		$redirect_args['awt_error'] = $error;
	}
	wp_safe_redirect( add_query_arg( $redirect_args, admin_url( 'options-general.php' ) ) );
	exit;
}

/**
 * Enqueue tiny CSS for the settings page. Inlined to avoid an extra
 * HTTP request — the page is small and the styles are stable.
 *
 * @param string $hook_suffix Current admin page hook suffix.
 */
function enqueue_assets( string $hook_suffix ): void {
	if ( $hook_suffix !== 'settings_page_' . MENU_SLUG ) {
		return;
	}
	wp_register_style( 'awt-settings-admin', false, array(), wp_get_theme()->get( 'Version' ) );
	wp_enqueue_style( 'awt-settings-admin' );
	wp_add_inline_style(
		'awt-settings-admin',
		'
		.awt-settings-page { max-width: 1200px; }
		/* Release-notes panel. Severity is never color-only: every entry keeps
		   its visible [Severity] text badge; colors just reinforce it. */
		.awt-settings-page .awt-whats-new-release { margin: 12px 0; padding: 12px 16px; background: #fff; border: 1px solid #c3c4c7; border-radius: 4px; }
		.awt-settings-page .awt-whats-new-release summary { cursor: pointer; font-size: 14px; }
		.awt-settings-page .awt-whats-new-release summary:focus-visible { outline: 2px solid #2271b1; outline-offset: 2px; }
		.awt-settings-page .awt-whats-new-entries { margin: 8px 0 4px 4px; }
		.awt-settings-page .awt-whats-new-entry { margin: 6px 0; }
		.awt-whats-new-badge { font-family: Consolas, Monaco, monospace; font-size: 12px; }
		.awt-settings-page .awt-whats-new-entry--security .awt-whats-new-badge,
		.awt-settings-page .awt-whats-new-entry--breaking .awt-whats-new-badge { color: #b32d2e; }
		.awt-settings-page .awt-whats-new-entry--a11y { background: #fcf9e8; padding: 2px 6px; border-radius: 3px; }
		.awt-settings-page .awt-whats-new-unread { color: #2271b1; font-style: normal; font-weight: 600; }
		.awt-settings-page .awt-whats-new-pinned { margin: 16px 0; padding: 16px; border: 1px solid #b32d2e; border-left-width: 4px; background: #fff; border-radius: 4px; }
		.awt-settings-page .awt-whats-new-pinned h3 { margin-top: 0; }
		.awt-settings-page .awt-whats-new-pinned form { margin-top: 12px; }
		/* Scoped to the whole page (not just .awt-settings-form) so these also
		   apply on self-form tabs like the welcome wizard. */
		.awt-settings-page .form-table th { width: 240px; }
		.awt-settings-page .awt-field-help { color: #646970; font-size: 13px; max-width: 50em; }
		.awt-logo-preview { display: block; block-size: 48px; inline-size: auto; max-inline-size: 280px; margin-block-start: 0.5em; padding: 8px; border: 1px solid #dcdcde; border-radius: 4px; background: #f6f7f7; box-sizing: content-box; }
		.awt-logo-preview--dark { background: #161616; border-color: #393939; }
		.awt-site-icon-preview { inline-size: 48px; block-size: 48px; object-fit: contain; }
		/* Premium upsell badge — a small link to the AWT Premium page. Same pill
		   look everywhere it appears (header-widget rows, the color editor).
		   #50575e on #fff is ~7:1 so the 11px text passes
		   WCAG 1.4.3. Hover/focus deepen the color + add a surface; focus reuses
		   the WP admin focus ring. */
		.awt-settings-page .awt-premium-badge { display: inline-flex; align-items: center; gap: .3em; white-space: nowrap; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; color: #50575e; text-decoration: none; border: 1px solid #c3c4c7; border-radius: 10px; padding: .15em .6em; line-height: 1.6; }
		.awt-settings-page .awt-premium-badge:hover,
		.awt-settings-page .awt-premium-badge:focus-visible { color: #1d2327; border-color: #8c8f94; background: #f6f7f7; text-decoration: underline; }
		.awt-settings-form details { margin: 1em 0; padding: 0.5em 1em; background: #fff8e5; border-left: 4px solid #dba617; }
		.awt-settings-form details > summary { cursor: pointer; font-weight: 600; }
		.awt-settings-form details > summary + * { margin-block-start: 0.5em; }
		.awt-settings-form textarea.code { font-family: Consolas, Monaco, monospace; min-block-size: 8em; inline-size: 100%; max-inline-size: 60em; }
	'
	);

	// Media library — needed by the "Select from Media Library" button on
	// the Identity tab and the wizard's brand-identity step. `wp_enqueue_media`
	// is the standard WP way to get the JS bundle for the media frame.
	wp_enqueue_media();

	// Generic media-picker glue. One frame, opened by any element carrying
	// either `data-awt-media-target` (writes the attachment URL to that input
	// ID — logos) or `data-awt-media-id-target` (writes the attachment ID —
	// the site icon, which WordPress stores by ID). Optional companions:
	// data-awt-media-alt-target   input ID to receive the attachment's alt
	// data-awt-media-preview      <img> ID to show the chosen image
	// data-awt-media-title        media-frame title
	// data-awt-media-type         restrict the library (e.g. 'image')
	// A separate `data-awt-media-remove` button clears its paired field +
	// preview. Used on both the Identity tab and the wizard.
	wp_register_script( 'awt-settings-admin', false, array( 'jquery', 'media-editor' ), wp_get_theme()->get( 'Version' ), true );
	wp_enqueue_script( 'awt-settings-admin' );
	wp_add_inline_script(
		'awt-settings-admin',
		"(function(){
		document.addEventListener('click', function(e){
			// Remove button: clear the paired hidden input + preview, hide self.
			var remove = e.target.closest('[data-awt-media-remove]');
			if (remove) {
				e.preventDefault();
				var rid = document.getElementById(remove.getAttribute('data-awt-media-remove'));
				if (rid) { rid.value = ''; rid.dispatchEvent(new Event('change')); }
				var rp = remove.getAttribute('data-awt-media-remove-preview');
				if (rp) { var rpe = document.getElementById(rp); if (rpe) { rpe.src = ''; rpe.style.display = 'none'; } }
				remove.style.display = 'none';
				return;
			}
			var trigger = e.target.closest('[data-awt-media-target],[data-awt-media-id-target]');
			if (!trigger) return;
			e.preventDefault();
			if (!window.wp || !wp.media) return;
			var targetId  = trigger.getAttribute('data-awt-media-target');
			var idTarget  = trigger.getAttribute('data-awt-media-id-target');
			var altId     = trigger.getAttribute('data-awt-media-alt-target');
			var previewId = trigger.getAttribute('data-awt-media-preview');
			var title     = trigger.getAttribute('data-awt-media-title') || " . wp_json_encode( __( 'Select image', 'awt' ), JSON_HEX_TAG ) . ";
			var type      = trigger.getAttribute('data-awt-media-type');
			var args = { title: title, button: { text: " . wp_json_encode( __( 'Use this', 'awt' ), JSON_HEX_TAG ) . " }, multiple: false };
			if (type) { args.library = { type: type }; }
			var frame = wp.media(args);
			frame.on('select', function(){
				var att = frame.state().get('selection').first().toJSON();
				if (targetId) {
					var urlInput = document.getElementById(targetId);
					if (urlInput) { urlInput.value = att.url; urlInput.dispatchEvent(new Event('change')); }
				}
				if (idTarget) {
					var idInput = document.getElementById(idTarget);
					if (idInput) { idInput.value = att.id; idInput.dispatchEvent(new Event('change')); }
				}
				if (altId) {
					var altInput = document.getElementById(altId);
					// Only auto-fill alt if the field is currently empty —
					// otherwise we'd clobber a customization the user typed.
					if (altInput && !altInput.value && att.alt) { altInput.value = att.alt; }
				}
				if (previewId) {
					var pv = document.getElementById(previewId);
					if (pv) {
						var src = att.url;
						if (att.sizes && att.sizes.thumbnail) { src = att.sizes.thumbnail.url; }
						pv.src = src; pv.style.display = '';
					}
				}
				// Reveal the paired Remove button, if any.
				if (idTarget) {
					var rm = document.querySelector('[data-awt-media-remove=\"' + idTarget + '\"]');
					if (rm) { rm.style.display = ''; }
				}
			});
			frame.open();
		});
	})();"
	);
}

/**
 * The AWT Premium upsell badge — a small pill that links to the AWT Premium
 * page. Use this everywhere a capability is Premium-only on an admin screen so
 * the badge looks and behaves identically (and is always a working link).
 *
 * Renders an <a> (opens in a new tab). The lock glyph is decorative
 * (aria-hidden); the accessible name spells out the destination.
 *
 * @param string|null $label Visible label. Defaults to "Premium".
 * @return string Escaped HTML for the badge link.
 */
function premium_badge( ?string $label = null ): string {
	$label = $label ?? __( 'Premium', 'awt' );
	$url   = 'https://accessiblewordpresstheme.com/premium';
	/* translators: %s: tier name, e.g. "Premium". */
	$aria = sprintf( __( '%s — learn more (opens in a new tab)', 'awt' ), $label );
	return '<a class="awt-premium-badge" href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $aria ) . '">'
		. '<span aria-hidden="true">🔒</span> ' . esc_html( $label )
		. '</a>';
}

/*
-------------------------------------------------------------------------
 * Tab renderers
 * -----------------------------------------------------------------------
 */

/**
 * Design system (§A). An informational page about Carbon — what a design
 * system is and what building on Carbon gives the site. Read-only: it renders
 * outside the save-form shell (listed in $self_form_tabs), so no Save button
 * appears.
 */
function render_tab_design_system(): void {
	?>
	<p class="awt-field-help" style="margin-block: 1em 1.5em; max-inline-size: 50em;">
		<?php esc_html_e( 'A design system sets the look of every AWT block: colors, fonts, spacing, and component style.', 'awt' ); ?>
	</p>
	<p style="max-inline-size:50em;">
		<?php esc_html_e( 'AWT is built on Carbon, IBM\'s open-source design system for products and digital experiences. Every block follows Carbon\'s components, spacing, and type scale, comes with matching light and dark themes, and meets the WCAG 2.2 AA accessibility standard.', 'awt' ); ?>
	</p>
	<p>
		<a href="https://carbondesignsystem.com/" target="_blank" rel="noopener noreferrer">
			<?php esc_html_e( 'Explore the Carbon Design System (opens in a new tab)', 'awt' ); ?>
		</a>
	</p>
	<?php
}

/**
 * Render the Identity tab: default logos (light + dark), logo alt text,
 * and the site icon (favicon) picker backed by WP's native `site_icon`.
 */
function render_tab_identity(): void {
	$logo_url      = Settings\get( 'identity.logoUrl' );
	$logo_url_dark = Settings\get( 'identity.logoUrlDark' );
	$logo_alt      = Settings\get( 'identity.logoAlt' );

	// Site icon (favicon). We reuse WordPress's native `site_icon` option so
	// core outputs all the favicon / apple-touch / web-app icon tags for us,
	// and the value round-trips with Settings → General. Stored as an
	// attachment ID; we resolve a URL just for the preview.
	$site_icon_id  = (int) get_option( 'site_icon' );
	$site_icon_url = $site_icon_id ? wp_get_attachment_image_url( $site_icon_id, 'full' ) : '';

	?>
	<p class="awt-field-help">
		<?php esc_html_e( 'These defaults apply to every Header brand block on your site. A setting on an individual block overrides them.', 'awt' ); ?>
	</p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="awt-identity-logoUrl"><?php esc_html_e( 'Logo for light mode', 'awt' ); ?></label></th>
			<td>
				<div style="display:flex; gap:0.5em; align-items:center; flex-wrap:wrap;">
					<button type="button" class="button button-secondary"
							data-awt-media-target="awt-identity-logoUrl"
							data-awt-media-alt-target="awt-identity-logoAlt">
						<?php esc_html_e( 'Select from Media Library…', 'awt' ); ?>
					</button>
					<input type="url" id="awt-identity-logoUrl" name="identity[logoUrl]" value="<?php echo esc_attr( (string) $logo_url ); ?>" class="regular-text" placeholder="https://example.com/logo.svg" />
				</div>
				<?php if ( $logo_url ) : ?>
					<img class="awt-logo-preview" src="<?php echo esc_url( (string) $logo_url ); ?>" alt="" />
				<?php endif; ?>
				<p class="awt-field-help"><?php esc_html_e( 'Click "Select from Media Library" to upload or choose an image, or paste a URL. SVG, PNG, or WebP work best. This logo shows on light backgrounds, and is also used in dark mode if you don\'t set a separate dark-mode logo.', 'awt' ); ?></p>
				<p class="awt-field-help" style="font-style: italic;">
					<?php
					// Point users to THIS site's plugin-install search page when
					// they have the capability — one click + Install Now lands
					// Safe SVG without leaving the admin. Fall back to the
					// canonical wordpress.org listing for users without the
					// install_plugins cap (Multisite single-site admins,
					// custom-capability setups, etc.).
					$can_install  = current_user_can( 'install_plugins' );
					$safe_svg_url = $can_install
						? add_query_arg(
							array(
								's'    => 'safe svg',
								'tab'  => 'search',
								'type' => 'term',
							),
							admin_url( 'plugin-install.php' )
						)
						: 'https://wordpress.org/plugins/safe-svg/';
					$link_target  = $can_install ? '_self' : '_blank';
					$link_rel     = $can_install ? '' : ' rel="noopener noreferrer"';
					$opens_in_tab = $can_install
						? ''
						: ' <span class="screen-reader-text">' . esc_html__( '(opens in new tab)', 'awt' ) . '</span>';
					printf(
						/* translators: 1: opening <a> tag for Safe SVG plugin link, 2: closing </a> tag (with opens-in-new-tab sr-text when relevant) */
						esc_html__( 'Need to upload an SVG logo? WordPress blocks SVG uploads by default, because SVG files can hide harmful code. To allow them safely, install %1$sSafe SVG%2$s or a similar plugin that cleans SVG files. AWT leaves this to a dedicated plugin so SVG safety stays well-maintained.', 'awt' ),
						'<a href="' . esc_url( $safe_svg_url ) . '" target="' . esc_attr( $link_target ) . '"' . $link_rel . '>', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $link_rel is a static literal chosen above.
						$opens_in_tab . '</a>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above with esc_html__().
					);
					?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="awt-identity-logoUrlDark"><?php esc_html_e( 'Logo for dark mode', 'awt' ); ?></label></th>
			<td>
				<div style="display:flex; gap:0.5em; align-items:center; flex-wrap:wrap;">
					<button type="button" class="button button-secondary"
							data-awt-media-target="awt-identity-logoUrlDark"
							data-awt-media-alt-target="awt-identity-logoAlt">
						<?php esc_html_e( 'Select from Media Library…', 'awt' ); ?>
					</button>
					<input type="url" id="awt-identity-logoUrlDark" name="identity[logoUrlDark]" value="<?php echo esc_attr( (string) $logo_url_dark ); ?>" class="regular-text" placeholder="https://example.com/logo-on-dark.svg" />
				</div>
				<?php if ( $logo_url_dark ) : ?>
					<img class="awt-logo-preview awt-logo-preview--dark" src="<?php echo esc_url( (string) $logo_url_dark ); ?>" alt="" />
				<?php endif; ?>
				<p class="awt-field-help">
					<?php esc_html_e( 'Optional. Upload a light or inverted version of your logo for dark backgrounds. Most logos use dark colors that vanish on dark backgrounds, so add a readable version here. Leave empty to use your light-mode logo everywhere.', 'awt' ); ?>
				</p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="awt-identity-logoAlt"><?php esc_html_e( 'Default logo alt text', 'awt' ); ?></label></th>
			<td>
				<input type="text" id="awt-identity-logoAlt" name="identity[logoAlt]" value="<?php echo esc_attr( (string) $logo_alt ); ?>" class="regular-text" />
				<p class="awt-field-help"><?php esc_html_e( 'Required when you set a logo. Describe what the logo shows — usually your company name. Leave empty if your brand mode is "Site Title only".', 'awt' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="awt-identity-siteIcon-btn"><?php esc_html_e( 'Site icon (favicon)', 'awt' ); ?></label></th>
			<td>
				<div style="display:flex; gap:0.5em; align-items:center; flex-wrap:wrap;">
					<button type="button" id="awt-identity-siteIcon-btn" class="button button-secondary"
							data-awt-media-id-target="awt-identity-siteIcon"
							data-awt-media-preview="awt-identity-siteIcon-preview"
							data-awt-media-type="image"
							data-awt-media-title="<?php esc_attr_e( 'Select site icon', 'awt' ); ?>">
						<?php esc_html_e( 'Select from Media Library…', 'awt' ); ?>
					</button>
					<button type="button" class="button button-link-delete"
							data-awt-media-remove="awt-identity-siteIcon"
							data-awt-media-remove-preview="awt-identity-siteIcon-preview"
							style="<?php echo $site_icon_id ? '' : 'display:none;'; ?>">
						<?php esc_html_e( 'Remove', 'awt' ); ?>
					</button>
				</div>
				<input type="hidden" id="awt-identity-siteIcon" name="siteIcon" value="<?php echo $site_icon_id ? esc_attr( (string) $site_icon_id ) : ''; ?>" />
				<img id="awt-identity-siteIcon-preview" class="awt-logo-preview awt-site-icon-preview" src="<?php echo esc_url( (string) $site_icon_url ); ?>" alt="" style="<?php echo $site_icon_url ? '' : 'display:none;'; ?>" />
				<p class="awt-field-help"><?php esc_html_e( 'Shown in browser tabs, bookmarks, and when someone adds your site to a phone home screen. Use a square image at least 512×512 pixels. This is the same Site Icon as Settings → General — kept here so all your branding lives in one place.', 'awt' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save the Identity tab: logo URLs, logo alt text, and the site icon.
 */
function save_tab_identity(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in handle_form_submission(); unslashed on the next line; values re-sanitized by Settings\sanitize() on save.
	$input = (array) ( $_POST['identity'] ?? array() );
	$input = wp_unslash( $input );
	Settings\set( 'identity.logoUrl', (string) ( $input['logoUrl'] ?? '' ) );
	Settings\set( 'identity.logoUrlDark', (string) ( $input['logoUrlDark'] ?? '' ) );
	Settings\set( 'identity.logoAlt', (string) ( $input['logoAlt'] ?? '' ) );
	// Brand mode + prefix now saved by save_tab_appearance (the Carbon tab).

	// Site icon (favicon) → WordPress's native `site_icon` option, so core
	// emits the icon tags and the value matches Settings → General. Only
	// accept a real image attachment; anything else clears the icon.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handle_form_submission().
	$icon_id = isset( $_POST['siteIcon'] ) ? absint( wp_unslash( $_POST['siteIcon'] ) ) : 0;
	if ( $icon_id > 0 && wp_attachment_is_image( $icon_id ) ) {
		update_option( 'site_icon', $icon_id );
	} else {
		delete_option( 'site_icon' );
	}
}

/**
 * Render the Appearance tab — standalone access to the wizard's style
 * variation + header preset pickers, without forcing a re-run.
 *
 * Two independently-savable forms. Each uses its own nonce (different
 * from the AWT-settings master nonce) and its own POST action key so
 * the handlers can tell them apart and apply only the changed value.
 *
 * Both pickers' rendering + apply logic comes from the shared helpers
 * in inc/style-variations.php and inc/header-presets.php — exactly the
 * same code paths the welcome wizard uses, so behavior is identical.
 */
function render_tab_appearance(): void {
	// §A: this is the design system's own tab (labeled with its name in
	// tabs()); Carbon's settings body is rendered inline below.
	$active_variation = (string) ( \AWT\Theme\Settings\get( 'welcome.choices.styleVariation' ) ?? '' );
	$active_preset    = (string) ( \AWT\Theme\Settings\get( 'welcome.choices.headerPreset' ) ?? '' );
	$page_url         = admin_url( 'options-general.php?page=' . MENU_SLUG . '&tab=appearance' );
	?>
	<p class="awt-field-help" style="margin-block: 1em 1.5em;">
		<?php esc_html_e( 'Settings that are specific to the Carbon design system: light/dark style, header layout, brand options, type size, and the color-contrast check. Other settings — logos and navigation — have their own tabs.', 'awt' ); ?>
	</p>

	<?php
	// --- Carbon sub-tabs -----------------------------------------------------
	// Second-level tab bar (default WordPress nav-tab styling), driven by a
	// ?section= query param — same pattern as the top-level tabs. Three groups:
	// Appearance (style variation + site light/dark), Header (header appearance,
	// preset, buttons, brand, further customization), and Colors (the read-only
	// contrast audit). Each form tab saves with a single button.
	$carbon_sections = array(
		'appearance' => __( 'Appearance', 'awt' ),
		'header'     => __( 'Header', 'awt' ),
		'typography' => __( 'Typography', 'awt' ),
		'colors'     => __( 'Colors', 'awt' ),
	);
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only sub-tab routing; sanitized, no state change.
	$active_section = isset( $_GET['section'] ) ? sanitize_key( wp_unslash( $_GET['section'] ) ) : 'appearance';
	if ( ! isset( $carbon_sections[ $active_section ] ) ) {
		$active_section = 'appearance';
	}
	$appearance_options = array(
		'default' => __( 'Default (follows the visitor\'s device setting)', 'awt' ),
		'light'   => __( 'Always light', 'awt' ),
		'dark'    => __( 'Always dark', 'awt' ),
	);
	?>
	<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Carbon settings sections', 'awt' ); ?>" style="margin-block-end: 1.5em;">
		<?php
		foreach ( $carbon_sections as $sslug => $slabel ) :
			$sclass = 'nav-tab' . ( $sslug === $active_section ? ' nav-tab-active' : '' );
			$surl   = add_query_arg(
				array(
					'page'    => MENU_SLUG,
					'tab'     => 'appearance',
					'section' => $sslug,
				),
				admin_url( 'options-general.php' )
			);
			?>
			<a class="<?php echo esc_attr( $sclass ); ?>"
				href="<?php echo esc_url( $surl ); ?>"
				<?php echo $sslug === $active_section ? 'aria-current="page"' : ''; ?>>
				<?php echo esc_html( $slabel ); ?>
			</a>
		<?php endforeach; ?>
	</nav>

	<?php
	if ( 'appearance' === $active_section ) {
		$site_scheme   = (string) ( \AWT\Theme\Settings\get( 'site.colorScheme' ) ?? 'default' );
		$variation_msg = __( 'Applying a different style variation replaces any color, typography, or layout changes you have made in Appearance → Editor → Styles. Continue?', 'awt' );
		?>
		<form method="post" action="<?php echo esc_url( $page_url ); ?>"
				data-awt-confirm-field="styleVariation"
				data-awt-confirm-original="<?php echo esc_attr( $active_variation ); ?>"
				data-awt-confirm-message="<?php echo esc_attr( $variation_msg ); ?>">
			<?php wp_nonce_field( NONCE_KEY, '_awt_nonce' ); ?>
			<input type="hidden" name="awt_active_tab" value="appearance" />
			<input type="hidden" name="awt_section" value="appearance" />

			<h2 style="margin-block-start: 0;"><?php esc_html_e( 'Style variation', 'awt' ); ?></h2>
			<p class="awt-field-help">
				<?php esc_html_e( 'Each variation pairs a light theme with a dark theme. Applying one replaces any style changes you\'ve made under Appearance → Editor → Styles — the same as choosing a variation there.', 'awt' ); ?>
			</p>
			<div style="margin-block: 1em 2.5em;">
				<?php \AWT\Theme\StyleVariations\picker_ui( $active_variation ); ?>
			</div>

			<h2><?php esc_html_e( 'Site appearance', 'awt' ); ?></h2>
			<p class="awt-field-help">
				<?php esc_html_e( 'Sets your site\'s light or dark appearance. "Default" follows each visitor\'s device setting (and the light/dark toggle, if you show one). "Always light" and "Always dark" lock the appearance for everyone.', 'awt' ); ?>
			</p>
			<fieldset style="margin-block: 1em;">
				<legend class="screen-reader-text"><?php esc_html_e( 'Site appearance', 'awt' ); ?></legend>
				<?php foreach ( $appearance_options as $value => $label ) : ?>
					<label style="display:block; margin-block-end:0.5em;">
						<input type="radio" name="siteColorScheme" value="<?php echo esc_attr( $value ); ?>" <?php checked( $site_scheme, $value ); ?> />
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>

			<?php submit_button( __( 'Save changes', 'awt' ) ); ?>
		</form>
		<?php
	} elseif ( 'header' === $active_section ) {
		$header_scheme = (string) ( \AWT\Theme\Settings\get( 'header.colorScheme' ) ?? 'default' );
		$icons         = \AWT\Theme\HeaderPresets\standard_icons();
		$brand_mode    = (string) \AWT\Theme\Settings\get( 'identity.brandMode' );
		$brand_prefix  = (string) \AWT\Theme\Settings\get( 'identity.prefix' );
		$brand_modes   = array(
			'text-only'                 => __( 'Site Title only', 'awt' ),
			'logo-with-text'            => __( 'Logo + Site Title', 'awt' ),
			'logo-only'                 => __( 'Logo only', 'awt' ),
			'text-with-prefix'          => __( 'Site Title + prefix', 'awt' ),
			'logo-with-text-and-prefix' => __( 'Logo + Site Title + prefix', 'awt' ),
		);
		$preset_msg    = __( 'Applying a different header preset replaces any changes you have made to your header. Continue?', 'awt' );
		?>
		<style><?php echo \AWT\Theme\HeaderPresets\picker_styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static admin CSS authored in code, no dynamic input. ?></style>
		<form method="post" action="<?php echo esc_url( $page_url ); ?>"
				data-awt-confirm-field="headerPreset"
				data-awt-confirm-original="<?php echo esc_attr( $active_preset ); ?>"
				data-awt-confirm-message="<?php echo esc_attr( $preset_msg ); ?>">
			<?php wp_nonce_field( NONCE_KEY, '_awt_nonce' ); ?>
			<input type="hidden" name="awt_active_tab" value="appearance" />
			<input type="hidden" name="awt_section" value="header" />

			<h2 style="margin-block-start: 0;"><?php esc_html_e( 'Header appearance', 'awt' ); ?></h2>
			<p class="awt-field-help">
				<?php esc_html_e( 'Sets the header bar\'s light or dark appearance. "Default" matches the site appearance above. "Always light" and "Always dark" keep the header fixed no matter what the rest of the page does.', 'awt' ); ?>
			</p>
			<fieldset style="margin-block: 1em 2.5em;">
				<legend class="screen-reader-text"><?php esc_html_e( 'Header appearance', 'awt' ); ?></legend>
				<?php foreach ( $appearance_options as $value => $label ) : ?>
					<label style="display:block; margin-block-end:0.5em;">
						<input type="radio" name="headerColorScheme" value="<?php echo esc_attr( $value ); ?>" <?php checked( $header_scheme, $value ); ?> />
						<?php echo esc_html( $label ); ?>
					</label>
				<?php endforeach; ?>
			</fieldset>

			<h2><?php esc_html_e( 'Header preset', 'awt' ); ?></h2>
			<p class="awt-field-help">
				<?php esc_html_e( 'Replaces your current header with one of five ready-made layouts.', 'awt' ); ?>
			</p>
			<div style="margin-block: 1em 2.5em;">
				<?php \AWT\Theme\HeaderPresets\picker_ui( $active_preset ); ?>
			</div>

			<h2><?php esc_html_e( 'Header settings', 'awt' ); ?></h2>
			<p class="awt-field-help">
				<?php esc_html_e( 'Add or remove the icon buttons at the top-right of your header. Each switch changes just that one button and leaves your other header changes alone.', 'awt' ); ?>
			</p>
			<?php
			// AWT Premium turns these icons into rich widgets (a search field in
			// the header, a notifications panel, a user menu). The lock badge
			// below links to the AWT Premium page.
			$premium_badge = premium_badge();

			// The free "Header icon widgets" upsell: the same three icons as
			// rich behaviors. Display-only (locked) — admin-screen gating is a
			// separate surface from the block-inspector PremiumNotice.
			$header_widgets = array(
				'search'        => array(
					'label'       => __( 'Search', 'awt' ),
					'description' => __( 'Replaces search icon with a search field within the header.', 'awt' ),
				),
				'notifications' => array(
					'label'       => __( 'Notifications', 'awt' ),
					'description' => __( 'Opens notifications panel.', 'awt' ),
				),
				'user'          => array(
					'label'       => __( 'User menu', 'awt' ),
					'description' => __( 'Opens user menu.', 'awt' ),
				),
			);
			?>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Header icon buttons', 'awt' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Header icon buttons', 'awt' ); ?></legend>
							<p class="awt-field-help" style="margin-block:0 0.75em; max-inline-size:42em;">
								<?php esc_html_e( 'Plain icon buttons. Search links to your site search; you can point Notifications and User menu wherever you like in the Site Editor.', 'awt' ); ?>
							</p>
							<?php
							foreach ( $icons as $key => $entry ) :
								$present  = \AWT\Theme\HeaderPresets\header_has_icon( $key );
								$input_id = 'awt-header-icon-' . esc_attr( $key );
								?>
								<label for="<?php echo esc_attr( $input_id ); ?>" style="display:flex; align-items:center; gap:0.5em; margin-block-end:0.75em;">
									<input type="checkbox" id="<?php echo esc_attr( $input_id ); ?>" name="headerIcons[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $present ); ?> />
									<?php echo esc_html( $entry['label'] ); ?>
								</label>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Header icon widgets', 'awt' ); ?></th>
					<td>
						<fieldset>
							<legend class="screen-reader-text"><?php esc_html_e( 'Header icon widgets (AWT Premium)', 'awt' ); ?></legend>
							<p class="awt-field-help" style="margin-block:0 0.75em; max-inline-size:42em;">
								<?php esc_html_e( 'AWT Premium upgrades these icons into rich widgets instead of plain buttons.', 'awt' ); ?>
							</p>
							<?php
							foreach ( $header_widgets as $key => $widget ) :
								$input_id = 'awt-header-widget-' . esc_attr( $key );
								?>
								<div style="margin-block-end:0.9em;">
									<label for="<?php echo esc_attr( $input_id ); ?>" style="display:flex; align-items:center; gap:0.5em;">
										<input type="checkbox" id="<?php echo esc_attr( $input_id ); ?>" disabled />
										<?php echo esc_html( $widget['label'] ); ?>
										<?php echo $premium_badge; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_html__ above. ?>
									</label>
									<p class="awt-field-help" style="margin:0.15em 0 0 1.85em; max-inline-size:42em;">
										<?php echo esc_html( $widget['description'] ); ?>
									</p>
								</div>
							<?php endforeach; ?>
						</fieldset>
					</td>
				</tr>
			</table>

			<h2 style="margin-block-start: 2em;"><?php esc_html_e( 'Brand', 'awt' ); ?></h2>
			<p class="awt-field-help"><?php esc_html_e( 'How the Header brand block looks by default. You can show an optional prefix before the site title (for example a parent brand). Set logo images on the Identity tab.', 'awt' ); ?></p>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="awt-brand-mode"><?php esc_html_e( 'Default brand mode', 'awt' ); ?></label></th>
					<td>
						<select id="awt-brand-mode" name="identity[brandMode]">
							<?php foreach ( $brand_modes as $value => $label ) : ?>
								<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $brand_mode, $value ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="awt-brand-prefix"><?php esc_html_e( 'Default prefix', 'awt' ); ?></label></th>
					<td>
						<input type="text" id="awt-brand-prefix" name="identity[prefix]" value="<?php echo esc_attr( $brand_prefix ); ?>" class="regular-text" />
						<p class="awt-field-help"><?php esc_html_e( 'A short parent-brand name shown before the site title — like "IBM" in "IBM Cloud". Only appears when brand mode includes a prefix.', 'awt' ); ?></p>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save changes', 'awt' ) ); ?>
		</form>

		<hr style="margin-block: 2.5em 2em;" />

		<h2><?php esc_html_e( 'Further customization', 'awt' ); ?></h2>
		<p class="awt-field-help">
			<?php esc_html_e( 'AWT Settings covers the common cases. For advanced changes — custom buttons, reordering icons, or restoring the original header — use the WordPress Site Editor.', 'awt' ); ?>
		</p>

		<h3 style="margin-block: 1.5em 0.5em;"><?php esc_html_e( 'Adding or reordering icons', 'awt' ); ?></h3>
		<p class="awt-field-help"><?php esc_html_e( 'To add custom buttons (an X/Twitter icon, a call-to-action button) or reorder icons, edit the header in the Site Editor.', 'awt' ); ?></p>
		<ol class="awt-field-help" style="padding-inline-start: 1.5em; max-inline-size: 50em;">
			<li>
				<?php
				printf(
					/* translators: %s: link to the Site Editor. */
					esc_html__( 'WordPress admin → %s.', 'awt' ),
					'<strong><a href="' . esc_url( admin_url( 'site-editor.php' ) ) . '">' . esc_html__( 'Appearance → Editor (Site Editor)', 'awt' ) . '</a></strong>'
				);
				?>
			</li>
			<li><?php esc_html_e( 'Patterns → Template Parts → Header.', 'awt' ); ?></li>
			<li><?php esc_html_e( 'Click into the header bar. A small toolbar appears with the global actions group at the right end — click into it.', 'awt' ); ?></li>
			<li><?php esc_html_e( 'Use the + button to add a new header action, or click an existing one to edit it.', 'awt' ); ?></li>
		</ol>

		<h3 style="margin-block: 2em 0.5em;"><?php esc_html_e( 'Reverting to the theme\'s shipped header', 'awt' ); ?></h3>
		<p class="awt-field-help">
			<?php esc_html_e( 'Want to discard your changes and go back to the header AWT ships with the theme? WordPress can do this for you — no plugin needed.', 'awt' ); ?>
		</p>
		<ol class="awt-field-help" style="padding-inline-start: 1.5em; max-inline-size: 50em;">
			<li>
				<?php
				printf(
					/* translators: %s: link to the Site Editor. */
					esc_html__( 'WordPress admin → %s.', 'awt' ),
					'<strong><a href="' . esc_url( admin_url( 'site-editor.php' ) ) . '">' . esc_html__( 'Appearance → Editor (Site Editor)', 'awt' ) . '</a></strong>'
				);
				?>
			</li>
			<li><?php esc_html_e( 'Patterns → Template Parts → Header.', 'awt' ); ?></li>
			<li><?php esc_html_e( 'Open the three-dot (⋯) menu on the Header template part (top-right of the canvas, or beside the template part\'s name in the sidebar).', 'awt' ); ?></li>
			<li><?php esc_html_e( 'Click "Clear customizations" (sometimes called "Reset"). WordPress discards your saved changes and restores the original header from the theme.', 'awt' ); ?></li>
		</ol>
		<p class="awt-field-help" style="font-style: italic;">
			<?php esc_html_e( 'The same works for any other template area (footer, sidebar, and so on) — it\'s a WordPress feature, not an AWT one. Handy whenever you want a fresh start.', 'awt' ); ?>
		</p>
		<?php
	} elseif ( 'typography' === $active_section ) {
		// Typography lives inside the Carbon tab now (between Header and
		// Colors). It saves through this tab's merged form, dispatched on
		// awt_section='typography' in save_tab_appearance(). render_tab_typography()
		// emits just the fields + live preview — wrap it in the self-form here.
		?>
		<form method="post" action="<?php echo esc_url( $page_url ); ?>">
			<?php wp_nonce_field( NONCE_KEY, '_awt_nonce' ); ?>
			<input type="hidden" name="awt_active_tab" value="appearance" />
			<input type="hidden" name="awt_section" value="typography" />
			<?php render_tab_typography(); ?>
			<?php submit_button( __( 'Save changes', 'awt' ) ); ?>
		</form>
		<?php
	} else {
		// Colors. In AWT (free) the role-aware contrast audit is hidden and
		// reserved for AWT Premium — render_tab_colors() is kept (unused) below.
		// Free shows a short chooser: recolor via Custom CSS (the supported free
		// path) or the Premium color editor (disabled).
		$premium_url    = 'https://accessiblewordpresstheme.com/premium';
		$custom_css_url = admin_url( 'options-general.php?page=' . MENU_SLUG . '&tab=custom-css' );
		?>
		<p class="awt-field-help" style="max-inline-size: 50em;">
			<?php esc_html_e( 'You can change your colors, but every change must keep WCAG-conformant contrast ratios so your site stays readable.', 'awt' ); ?>
		</p>
		<fieldset style="margin-block: 1.5em;">
			<legend style="font-weight: 600; margin-block-end: 0.75em;"><?php esc_html_e( 'Change colors using', 'awt' ); ?></legend>

			<label style="display:flex; align-items:center; gap:0.5em; margin-block-end:0.75em;">
				<input type="radio" name="awt_color_method" value="custom-css" checked />
				<a href="<?php echo esc_url( $custom_css_url ); ?>"><?php esc_html_e( 'Custom CSS', 'awt' ); ?></a>
			</label>

			<label style="display:flex; align-items:center; gap:0.5em; margin-block-end:0.75em; color:#646970;">
				<input type="radio" name="awt_color_method" value="color-editor" disabled />
				<?php esc_html_e( 'AWT contrast-safe color editor', 'awt' ); ?>
				<?php echo premium_badge(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- premium_badge() returns escaped markup. ?>
			</label>
		</fieldset>

		<p class="awt-field-help">
			<?php
			printf(
				/* translators: %s: link to the AWT Premium website */
				esc_html__( 'Learn more about %s.', 'awt' ),
				'<a href="' . esc_url( $premium_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'AWT Premium', 'awt' ) . '</a>'
			);
			?>
		</p>
		<?php
	}
}

/**
 * Save handler for the Carbon (Appearance) tab. Each sub-tab posts one merged
 * form carrying a hidden `awt_section`; we dispatch on it. This is a self-form
 * tab, so handle_form_submission() has already verified the nonce.
 *
 * @throws \RuntimeException When applying the style variation or header preset fails.
 */
function save_tab_appearance(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handle_form_submission().
	$section = sanitize_key( wp_unslash( $_POST['awt_section'] ?? 'appearance' ) );

	if ( 'appearance' === $section ) {
		// Style variation — apply only when the selection changed, so saving the
		// site light/dark choice alone never re-clobbers Site Editor → Styles edits.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handle_form_submission().
		$slug    = sanitize_key( wp_unslash( $_POST['styleVariation'] ?? '' ) );
		$current = (string) ( \AWT\Theme\Settings\get( 'welcome.choices.styleVariation' ) ?? '' );
		if ( $slug !== '' && $slug !== $current ) {
			\AWT\Theme\Settings\set( 'welcome.choices.styleVariation', $slug );
			if ( ! \AWT\Theme\StyleVariations\apply_style_variation( $slug ) ) {
				throw new \RuntimeException( esc_html__( 'Failed to apply the style variation. Check that the variation file exists in /styles.', 'awt' ) );
			}
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handle_form_submission().
		$scheme = sanitize_key( wp_unslash( $_POST['siteColorScheme'] ?? 'default' ) );
		\AWT\Theme\Settings\set( 'site.colorScheme', in_array( $scheme, array( 'default', 'light', 'dark' ), true ) ? $scheme : 'default' );
		return;
	}

	if ( 'header' === $section ) {
		// Header preset — apply only when changed. Applying rewrites the header,
		// so the per-icon toggles (which reflect the OLD header) are skipped on
		// that save; the new preset's own buttons stand and the toggles refresh
		// on reload. When the preset is unchanged, apply the icon toggles.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handle_form_submission().
		$preset         = sanitize_key( wp_unslash( $_POST['headerPreset'] ?? '' ) );
		$current_preset = (string) ( \AWT\Theme\Settings\get( 'welcome.choices.headerPreset' ) ?? '' );
		if ( $preset !== '' && $preset !== $current_preset ) {
			\AWT\Theme\Settings\set( 'welcome.choices.headerPreset', $preset );
			if ( ! \AWT\Theme\HeaderPresets\apply_header_preset( $preset ) ) {
				throw new \RuntimeException( esc_html__( 'Failed to apply the header preset.', 'awt' ) );
			}
		} else {
			// All standard icons are plain free buttons now (Search / Notifications
			// / User menu / Color-scheme toggle). The rich-widget versions are an
			// AWT Premium upsell shown read-only, never posted here.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in handle_form_submission(); values only tested for truthiness against the fixed standard_icons() key list.
			$submitted = (array) ( $_POST['headerIcons'] ?? array() );
			foreach ( array_keys( \AWT\Theme\HeaderPresets\standard_icons() ) as $key ) {
				\AWT\Theme\HeaderPresets\set_header_icon( $key, ! empty( $submitted[ $key ] ) );
			}
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handle_form_submission().
		$hscheme = sanitize_key( wp_unslash( $_POST['headerColorScheme'] ?? 'default' ) );
		\AWT\Theme\Settings\set( 'header.colorScheme', in_array( $hscheme, array( 'default', 'light', 'dark' ), true ) ? $hscheme : 'default' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in handle_form_submission(); unslashed here, values validated against the brand-mode whitelist and re-sanitized by Settings\sanitize() on save.
		$input       = wp_unslash( (array) ( $_POST['identity'] ?? array() ) );
		$brand_modes = array( 'text-only', 'logo-with-text', 'logo-only', 'text-with-prefix', 'logo-with-text-and-prefix' );
		$mode        = (string) ( $input['brandMode'] ?? 'text-only' );
		\AWT\Theme\Settings\set( 'identity.brandMode', in_array( $mode, $brand_modes, true ) ? $mode : 'text-only' );
		\AWT\Theme\Settings\set( 'identity.prefix', (string) ( $input['prefix'] ?? '' ) );
		return;
	}

	if ( 'typography' === $section ) {
		// Delegate to the shared handler (also used by the welcome wizard).
		save_tab_typography();
		return;
	}

	// 'colors' is read-only — nothing to save.
}

/**
 * Render the Typography sub-tab: the size-scale picker (Compact / Default /
 * Comfortable) with a live H1-through-body preview.
 */
function render_tab_typography(): void {
	$scale   = (float) Settings\get( 'typography.sizeScale' );
	$options = array(
		'0.875' => array(
			'label'       => __( 'Compact (0.875×)', 'awt' ),
			'description' => __( 'Slightly smaller than the browser default. Best when fitting more on screen matters more than easy reading.', 'awt' ),
		),
		'1'     => array(
			'label'       => __( 'Default (1.0×)', 'awt' ),
			'description' => __( 'The standard type scale. Recommended for most sites.', 'awt' ),
		),
		'1.125' => array(
			'label'       => __( 'Comfortable (1.125×)', 'awt' ),
			'description' => __( 'Slightly larger. Recommended for sites with older audiences or low-vision readers.', 'awt' ),
		),
	);
	?>
	<p class="awt-field-help">
		<?php esc_html_e( 'Resize every font on your site by the same amount. Heading-to-body proportions and line spacing stay balanced at any size.', 'awt' ); ?>
	</p>
	<p class="awt-field-help">
		<?php esc_html_e( 'AWT uses IBM Plex Sans for text and IBM Plex Mono for code, chosen for on-screen readability. You can\'t swap fonts yet, because many web fonts are harder to read.', 'awt' ); ?>
	</p>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Size scale', 'awt' ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><?php esc_html_e( 'Size scale', 'awt' ); ?></legend>
					<?php
					foreach ( $options as $value => $opt ) :
						// PHP auto-converts integer-looking string keys ('1') to
						// real ints in foreach. Cast back to string before
						// str_replace, which is strictly typed in PHP 8.
						$value       = (string) $value;
						$float_value = (float) $value;
						$id          = 'awt-type-scale-' . str_replace( '.', '-', $value );
						?>
						<p>
							<label for="<?php echo esc_attr( $id ); ?>">
								<input type="radio" id="<?php echo esc_attr( $id ); ?>" name="typography[sizeScale]" value="<?php echo esc_attr( $value ); ?>" <?php checked( $scale, $float_value ); ?> />
								<strong><?php echo esc_html( $opt['label'] ); ?></strong> — <?php echo esc_html( $opt['description'] ); ?>
							</label>
						</p>
					<?php endforeach; ?>
				</fieldset>
			</td>
		</tr>
	</table>

	<?php
	// Live preview: shows the four most-common type levels (h1 / h2 / h3 /
	// body) at the currently-saved scale. The preview's `font-size` is
	// computed by JS from the active radio so the user sees the multiplier
	// effect immediately, before saving. Carbon's font-size tokens are
	// hardcoded here to match `--cds-heading-{06,05,04}-font-size` +
	// `--cds-body-01-font-size` so the preview is faithful even though the
	// admin page itself doesn't include the Carbon stylesheet.
	?>
	<h2><?php esc_html_e( 'Preview', 'awt' ); ?></h2>
	<p class="awt-field-help"><?php esc_html_e( 'Updates live as you change the size scale. Save changes to apply across the site.', 'awt' ); ?></p>
	<div id="awt-type-preview" style="border: 1px solid #c3c4c7; padding: 1.5em 2em; background: #ffffff; max-inline-size: 60em; font-family: 'IBM Plex Sans', system-ui, sans-serif;">
		<div data-awt-preview-base="2.625" style="font-size: 2.625rem; font-weight: 300; line-height: 1.199; margin-block: 0 0.5em;"><?php esc_html_e( 'Heading 1 — Productive heading 06', 'awt' ); ?></div>
		<div data-awt-preview-base="1.75" style="font-size: 1.75rem; font-weight: 400; line-height: 1.25; margin-block: 1em 0.5em;"><?php esc_html_e( 'Heading 2 — Productive heading 04', 'awt' ); ?></div>
		<div data-awt-preview-base="1.25" style="font-size: 1.25rem; font-weight: 400; line-height: 1.4; margin-block: 1em 0.5em;"><?php esc_html_e( 'Heading 3 — Productive heading 03', 'awt' ); ?></div>
		<p data-awt-preview-base="0.875" style="font-size: 0.875rem; line-height: 1.4286; margin-block: 1em 0;">
			<?php esc_html_e( 'Body text. The quick brown fox jumps over the lazy dog. 1234567890. AWT resizes every font by the same amount, so spacing stays balanced at any size. Compact fits more on screen; Comfortable gives larger, easier-to-read type.', 'awt' ); ?>
		</p>
	</div>

	<script>
	(function(){
		var preview = document.getElementById('awt-type-preview');
		if (!preview) return;
		function applyScale(scale){
			scale = parseFloat(scale) || 1;
			preview.querySelectorAll('[data-awt-preview-base]').forEach(function(el){
				var base = parseFloat(el.getAttribute('data-awt-preview-base')) || 1;
				el.style.fontSize = (base * scale).toFixed(4) + 'rem';
			});
		}
		// Apply the currently-selected (saved) scale on load.
		var current = document.querySelector('input[name="typography[sizeScale]"]:checked');
		if (current) applyScale(current.value);
		// Live update on radio change — instant feedback, no save needed.
		document.querySelectorAll('input[name="typography[sizeScale]"]').forEach(function(r){
			r.addEventListener('change', function(){ applyScale(r.value); });
		});
	})();
	</script>
	<?php
}

/**
 * Render the Colors tab — a role-aware contrast audit.
 *
 * For each foreground token (text / link / button surface / status icon /
 * border / focus), we look up its INTENDED surface pairings from the role
 * map and check WCAG against the threshold appropriate to that role
 * (text = 4.5:1, ui = 3.0:1). Pairings the token wasn't designed for
 * are not checked — Carbon's tokens are role-specific, and treating every
 * color as if it could land on every surface produces false-positive
 * failures that obscure the real ones.
 *
 * Scopes: shows both the active light scope and the active dark scope,
 * so the user sees how each token behaves under either theme variation.
 *
 * Why here, not in the Site Editor picker: a proper picker integration
 * needs a Gutenberg JS plugin (SlotFill). That's tracked as a Stage 1.x
 * extension. This admin-side audit ships now per spec §5 "Color palette".
 */
function render_tab_colors(): void {
	$settings = function_exists( 'wp_get_global_settings' ) ? wp_get_global_settings() : array();
	$palette  = $settings['color']['palette'] ?? array();

	// Flatten — theme.json's palette can be a flat array or an associative
	// array of theme/default/custom palettes depending on WP version.
	$colors = array();
	if ( isset( $palette[0] ) && is_array( $palette[0] ) ) {
		$colors = $palette;
	} elseif ( ! empty( $palette ) ) {
		foreach ( $palette as $source ) {
			if ( is_array( $source ) ) {
				$colors = array_merge( $colors, $source );
			}
		}
	}

	// Index by slug for cross-lookup; preserve name + hex per entry.
	$by_slug = array();
	foreach ( $colors as $c ) {
		if ( ! is_array( $c ) || empty( $c['color'] ) || empty( $c['slug'] ) ) {
			continue;
		}
		$hex = trim( (string) $c['color'] );
		if ( $hex === '' || $hex[0] !== '#' ) {
			continue;
		}
		$by_slug[ (string) $c['slug'] ] = array(
			'name' => (string) ( $c['name'] ?? $c['slug'] ),
			'hex'  => $hex,
		);
	}

	$role_map   = \AWT\Theme\Contrast\role_map();
	$resolved   = \AWT\Theme\Contrast\carbon_resolved_palette();
	$exempt     = \AWT\Theme\Contrast\exempt_tokens();
	$surfaces   = \AWT\Theme\Contrast\surface_tokens();
	$light_slug = (string) ( \AWT\Theme\theme_scopes()['light'] ?? 'white' );
	$dark_slug  = (string) ( \AWT\Theme\theme_scopes()['dark'] ?? 'g100' );

	?>
	<p class="awt-field-help">
		<?php
		printf(
			/* translators: %s: link to Site Editor */
			esc_html__( 'Edit colors in %s. Custom colors you add there appear in this audit alongside Carbon\'s defaults.', 'awt' ),
			'<a href="' . esc_url( admin_url( 'site-editor.php' ) ) . '">' . esc_html__( 'Site Editor → Styles → Colors', 'awt' ) . '</a>'
		);
		?>
	</p>
	<p class="awt-field-help">
		<?php esc_html_e( 'This check tests each color only against the backgrounds it\'s meant to appear on, using the WCAG contrast level its role requires: 4.5:1 for body and link text, 3:1 for interface parts like button edges, icons, focus rings, and borders. Color pairings a color isn\'t meant for aren\'t shown.', 'awt' ); ?>
	</p>

	<style>
		.awt-roles-table { border-collapse: collapse; margin-block: 0.5em 1.5em; inline-size: 100%; max-inline-size: 92em; }
		.awt-roles-table th, .awt-roles-table td { padding: 8px 10px; border: 1px solid #c3c4c7; vertical-align: top; text-align: start; font-size: 13px; }
		.awt-roles-table th { background: #f0f0f1; font-weight: 600; }
		.awt-roles-table td.awt-roles-token-cell { inline-size: 240px; background: #fafafa; }
		.awt-roles-table td.awt-roles-pairing-cell { inline-size: 180px; }
		.awt-roles-table td.awt-roles-numeric { text-align: end; font-variant-numeric: tabular-nums; white-space: nowrap; }
		.awt-roles-table td.awt-roles-pill-cell { text-align: center; }
		.awt-roles-table td.awt-roles-notes-cell { max-inline-size: 28em; font-size: 12px; line-height: 1.45; color: #1d2327; }
		.awt-contrast-swatch { display: inline-block; inline-size: 18px; block-size: 18px; border: 1px solid #c3c4c7; vertical-align: middle; margin-inline-end: 0.5em; border-radius: 2px; }
		.awt-contrast-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; line-height: 1.6; letter-spacing: 0.02em; }
		.awt-contrast-pass    { background: #d4edda; color: #155724; }
		.awt-contrast-fail    { background: #f8d7da; color: #721c24; }
		.awt-contrast-exempt  { background: #e7f1ff; color: #003a8c; }
		.awt-contrast-required{ background: #f0f0f1; color: #1d2327; }
		/* Exempt pills are anchors when an `exempt_url` is set — they open
		 * the relevant WCAG Understanding section in a new tab. The pill
		 * styling is shared with the static Exempt span; we just add
		 * underline-on-hover + a subtle inline gap before the external icon. */
		a.awt-contrast-link { text-decoration: none; display: inline-flex; align-items: center; gap: 0.35em; }
		a.awt-contrast-link:hover, a.awt-contrast-link:focus { text-decoration: underline; }
		a.awt-contrast-link:focus-visible { outline: 2px solid #2271b1; outline-offset: 2px; }
		.awt-external-icon { vertical-align: -2px; }
		.awt-roles-row-notes { color: #646970; font-size: 12px; margin-block-start: 0.4em; }
		details.awt-roles-section { margin-block: 1em; padding: 0.5em 1em; background: #f6f7f7; border-inline-start: 4px solid #c3c4c7; }
		details.awt-roles-section[open] { background: #f0f0f1; }
		details.awt-roles-section > summary { font-weight: 600; cursor: pointer; }
		.awt-roles-summary-stats { font-size: 12px; color: #646970; margin-block: 0.5em 1em; }
	</style>

	<?php
	if ( empty( $by_slug ) ) {
		echo '<p>' . esc_html__( 'No palette colors found.', 'awt' ) . '</p>';
		return;
	}

	// Render one block per scope (light + dark) so the same token's
	// behavior in both themes is visible side-by-side via tabbing.
	foreach ( array(
		$light_slug => __( 'Light scope', 'awt' ),
		$dark_slug  => __( 'Dark scope', 'awt' ),
	) as $scope_slug => $scope_label ) :
		$scope_palette = $resolved[ $scope_slug ] ?? $resolved['white'];

		// Tally pass / fail for this scope's summary. Earlier versions of
		// this audit had an "Exempt" third bucket for placeholder rows
		// citing WCAG 1.4.3 — but that citation didn't hold up (WCAG
		// doesn't exempt placeholders). The audit now reports honestly
		// and the Notes column carries the design-tradeoff context.
		$pass_count = 0;
		$fail_count = 0;
		$rows       = array();
		foreach ( $role_map as $token_slug => $meta ) {
			$fg_hex = $scope_palette[ $token_slug ] ?? null;
			if ( ! $fg_hex ) {
				continue;
			}
			$pair_results = array();
			foreach ( $meta['pairings'] as $pairing ) {
				$bg_hex = $scope_palette[ $pairing['against'] ] ?? null;
				if ( ! $bg_hex ) {
					continue;
				}
				$r              = \AWT\Theme\Contrast\ratio( $fg_hex, $bg_hex );
				$v              = \AWT\Theme\Contrast\role_verdict( $r, $pairing['threshold'] );
				$pair_results[] = array(
					'against'     => $pairing['against'],
					'against_hex' => $bg_hex,
					'label'       => $pairing['label'],
					'threshold'   => $pairing['threshold'],
					'ratio'       => $r,
					'verdict'     => $v,
					'notes'       => $pairing['notes'] ?? '',
				);
				if ( $v === 'pass' ) {
					++$pass_count;
				} else {
					++$fail_count; }
			}
			$rows[] = array(
				'slug'  => $token_slug,
				'meta'  => $meta,
				'hex'   => $fg_hex,
				'pairs' => $pair_results,
			);
		}
		?>

		<h2><?php echo esc_html( $scope_label ); ?> — <code><?php echo esc_html( $scope_slug ); ?></code></h2>
		<p class="awt-roles-summary-stats">
			<?php
			printf(
				/* translators: 1: passing pairings, 2: failing pairings */
				esc_html__( '%1$d pass · %2$d fail.', 'awt' ),
				(int) $pass_count,
				(int) $fail_count
			);
			?>
		</p>

		<table class="awt-roles-table">
			<thead>
				<tr>
					<th scope="col"><?php esc_html_e( 'Token & role', 'awt' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Pairing', 'awt' ); ?></th>
					<th scope="col" style="text-align: end;"><?php esc_html_e( 'Required contrast', 'awt' ); ?></th>
					<th scope="col" style="text-align: end;"><?php esc_html_e( 'Measured contrast', 'awt' ); ?></th>
					<th scope="col" style="text-align: center;"><?php esc_html_e( 'Outcome', 'awt' ); ?></th>
					<th scope="col"><?php esc_html_e( 'Notes', 'awt' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php
				foreach ( $rows as $row ) :
					$slug       = $row['slug'];
					$meta       = $row['meta'];
					$hex        = $row['hex'];
					$pair_count = count( $row['pairs'] );
					if ( $pair_count === 0 ) {
						continue;
					}
					foreach ( $row['pairs'] as $i => $pair ) :
						$th_required   = \AWT\Theme\Contrast\threshold_value( $pair['threshold'] );
						$outcome_class = $pair['verdict'] === 'pass' ? 'awt-contrast-pass' : 'awt-contrast-fail';
						$outcome_label = $pair['verdict'] === 'pass' ? __( 'Pass', 'awt' ) : __( 'Fail', 'awt' );
						?>
						<tr>
							<?php if ( $i === 0 ) : ?>
								<td class="awt-roles-token-cell" rowspan="<?php echo (int) $pair_count; ?>">
									<span class="awt-contrast-swatch" style="background: <?php echo esc_attr( $hex ); ?>;" aria-hidden="true"></span>
									<strong><?php echo esc_html( $by_slug[ $slug ]['name'] ?? $slug ); ?></strong>
									<br /><small><code><?php echo esc_html( $hex ); ?></code> · <?php echo esc_html( $slug ); ?></small>
									<div style="margin-block-start: 0.4em; color: #646970; font-size: 12px;"><?php echo esc_html( $meta['role'] ); ?></div>
									<?php if ( ! empty( $meta['notes'] ) ) : ?>
										<div class="awt-roles-row-notes"><?php echo esc_html( $meta['notes'] ); ?></div>
									<?php endif; ?>
								</td>
							<?php endif; ?>
							<td class="awt-roles-pairing-cell">
								<span class="awt-contrast-swatch" style="background: <?php echo esc_attr( $pair['against_hex'] ); ?>;" aria-hidden="true"></span>
								<?php echo esc_html( $pair['label'] ); ?>
								<br /><small><code><?php echo esc_html( $pair['against'] ); ?></code></small>
							</td>
							<td class="awt-roles-numeric">
								<?php echo esc_html( number_format( $th_required, 1 ) ); ?>:1
							</td>
							<td class="awt-roles-numeric"><?php echo esc_html( number_format( $pair['ratio'], 2 ) ); ?>:1</td>
							<td class="awt-roles-pill-cell">
								<span class="awt-contrast-badge <?php echo esc_attr( $outcome_class ); ?>"><?php echo esc_html( $outcome_label ); ?></span>
							</td>
							<td class="awt-roles-notes-cell">
								<?php if ( ! empty( $pair['notes'] ) ) : ?>
									<?php echo esc_html( $pair['notes'] ); ?>
								<?php else : ?>
									<span style="color: #c3c4c7;">—</span>
								<?php endif; ?>
							</td>
						</tr>
						<?php
					endforeach;
				endforeach;
				?>
			</tbody>
		</table>

		<details class="awt-roles-section">
			<summary><?php esc_html_e( 'Surfaces (no ratio check)', 'awt' ); ?></summary>
			<p class="awt-roles-row-notes" style="margin-block-start: 0.5em;">
				<?php esc_html_e( 'Surface colors are backgrounds — the colors that text and icons sit on top of. They aren\'t foreground colors, so there\'s no contrast ratio to check.', 'awt' ); ?>
			</p>
			<ul style="list-style: none; padding: 0; margin-block-start: 0.5em; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 4px;">
				<?php
				foreach ( $surfaces as $surface_slug ) :
					$surface_hex = $scope_palette[ $surface_slug ] ?? null;
					if ( ! $surface_hex ) {
						continue; }
					?>
					<li>
						<span class="awt-contrast-swatch" style="background: <?php echo esc_attr( $surface_hex ); ?>;" aria-hidden="true"></span>
						<small><strong><?php echo esc_html( $by_slug[ $surface_slug ]['name'] ?? $surface_slug ); ?></strong> — <code><?php echo esc_html( $surface_hex ); ?></code></small>
					</li>
				<?php endforeach; ?>
			</ul>
		</details>

		<details class="awt-roles-section">
			<summary><?php esc_html_e( 'Exempt tokens (intentionally low contrast)', 'awt' ); ?></summary>
			<p class="awt-roles-row-notes" style="margin-block-start: 0.5em;">
				<?php esc_html_e( 'These colors are skipped. WCAG exempts disabled controls from contrast rules, and other colors depend too much on context (like focus insets) for an automatic check to judge fairly.', 'awt' ); ?>
			</p>
			<ul style="list-style: none; padding: 0; margin-block-start: 0.5em; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 4px;">
				<?php
				foreach ( $exempt as $ex_slug ) :
					$ex_hex = $scope_palette[ $ex_slug ] ?? ( $by_slug[ $ex_slug ]['hex'] ?? null );
					if ( ! $ex_hex ) {
						continue; }
					?>
					<li>
						<span class="awt-contrast-swatch" style="background: <?php echo esc_attr( $ex_hex ); ?>;" aria-hidden="true"></span>
						<small><strong><?php echo esc_html( $by_slug[ $ex_slug ]['name'] ?? $ex_slug ); ?></strong> — <code><?php echo esc_html( $ex_hex ); ?></code></small>
					</li>
				<?php endforeach; ?>
			</ul>
		</details>

	<?php endforeach; ?>

	<p class="awt-field-help" style="margin-block-start: 2em;">
		<strong><?php esc_html_e( 'How to read this audit', 'awt' ); ?></strong><br />
		<?php esc_html_e( 'Each row shows a color, the backgrounds it\'s designed for, and the contrast level its role needs. Colors are checked only where they\'re actually used: a text color is tested against the backgrounds it appears on; a button color is tested for its edge contrast, while text-on-button readability is checked separately under "Text — on accent surfaces".', 'awt' ); ?><br /><br />
		<?php esc_html_e( 'If a built-in color fails, it\'s worth investigating. If a color you added yourself fails, reconsider it — or use it only where there\'s no text.', 'awt' ); ?>
	</p>
	<?php
}

/**
 * Save handler for the Colors sub-tab.
 */
function save_tab_colors(): void {
	// Read-only audit tab — nothing to save here. Color editing happens
	// in the Site Editor.
}

/**
 * Save the typography size scale. Typography is no longer a top-level tab —
 * it's a Carbon sub-tab (save_tab_appearance dispatches section 'typography'
 * here) — but the welcome wizard's Typography step also calls this directly,
 * so it stays the single source of truth for the save.
 */
function save_tab_typography(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in handle_form_submission(); unslashed here, cast to float, and clamped to the allowed set by Settings\sanitize() on save.
	$input = wp_unslash( (array) ( $_POST['typography'] ?? array() ) );
	$raw   = (float) ( $input['sizeScale'] ?? 1.0 );
	// sanitize() enforces the allowed set on the round-trip through save().
	Settings\set( 'typography.sizeScale', $raw );
}

/**
 * Render the Navigation tab: skip-link text and the breadcrumb auto-emit
 * settings (on/off, mobile, home + 404 item text).
 */
function render_tab_navigation(): void {
	$skip_link_text = Settings\get( 'navigation.skipLinkText' );
	$home_text      = Settings\get( 'navigation.homeItemText' );
	$not_found_text = Settings\get( 'navigation.pageNotFoundItemText' );
	$breadcrumb     = Settings\get( 'navigation.breadcrumbAutoEmit' );
	?>
	<h2><?php esc_html_e( 'Skip link', 'awt' ); ?></h2>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="awt-nav-skipLinkText"><?php esc_html_e( 'Skip link text', 'awt' ); ?></label></th>
			<td>
				<input type="text" id="awt-nav-skipLinkText" name="navigation[skipLinkText]" value="<?php echo esc_attr( (string) $skip_link_text ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Skip to main content', 'awt' ); ?>" />
				<p class="awt-field-help"><?php esc_html_e( 'Default text for every Skip link on your site. A setting on an individual block wins. Leave empty to use the default, "Skip to main content".', 'awt' ); ?></p>
			</td>
		</tr>
	</table>

	<h2><?php esc_html_e( 'Breadcrumbs', 'awt' ); ?></h2>
	<div style="margin: 0.5em 0 1em; padding: 0.75em 1em; background: #f0f6fc; border-inline-start: 4px solid #0073aa; max-inline-size: 60em;">
		<p style="margin: 0;">
			<strong>♿ <?php esc_html_e( 'Accessibility benefit', 'awt' ); ?></strong>
			— 
			<?php
			printf(
				/* translators: 1: opening <a> tag to WCAG 2.4.5; 2: closing </a> tag */
				esc_html__( 'Automatic breadcrumbs help meet the %1$sWCAG "Multiple Ways" guideline (2.4.5, Level AA)%2$s, which asks for more than one way to find a page. This matters most if your site has no search: without it, breadcrumbs (or a sitemap) become the main alternative, and the guideline needs at least two ways.', 'awt' ),
				'<a href="https://www.w3.org/WAI/WCAG21/Understanding/multiple-ways.html" target="_blank" rel="noopener">',
				'</a>'
			);
			?>
		</p>
	</div>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="awt-nav-bc-enabled"><?php esc_html_e( 'Auto-emit breadcrumbs', 'awt' ); ?></label></th>
			<td>
				<label>
					<input type="checkbox" id="awt-nav-bc-enabled" name="navigation[breadcrumbAutoEmit][enabled]" value="1" <?php checked( ! empty( $breadcrumb['enabled'] ) ); ?> />
					<?php esc_html_e( 'Show a breadcrumb trail above the main content on every page except the front page.', 'awt' ); ?>
				</label>
				<p class="awt-field-help"><?php esc_html_e( 'Turn this off if you build breadcrumbs by hand with the Breadcrumb block. If a page has both, the automatic one hides itself.', 'awt' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="awt-nav-bc-mobile"><?php esc_html_e( 'Show on mobile', 'awt' ); ?></label></th>
			<td>
				<label>
					<input type="checkbox" id="awt-nav-bc-mobile" name="navigation[breadcrumbAutoEmit][mobile]" value="1" <?php checked( ! empty( $breadcrumb['mobile'] ) ); ?> />
					<?php esc_html_e( 'Show breadcrumbs on narrow screens (under 672px). This is separate from the main toggle above.', 'awt' ); ?>
				</label>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="awt-nav-homeItemText"><?php esc_html_e( 'Home item text', 'awt' ); ?></label></th>
			<td>
				<input type="text" id="awt-nav-homeItemText" name="navigation[homeItemText]" value="<?php echo esc_attr( (string) $home_text ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Home', 'awt' ); ?>" />
				<p class="awt-field-help"><?php esc_html_e( 'The label for the first item in every auto-emitted breadcrumb trail.', 'awt' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="awt-nav-notFoundText"><?php esc_html_e( '404 item text', 'awt' ); ?></label></th>
			<td>
				<input type="text" id="awt-nav-notFoundText" name="navigation[pageNotFoundItemText]" value="<?php echo esc_attr( (string) $not_found_text ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Page not found', 'awt' ); ?>" />
				<p class="awt-field-help"><?php esc_html_e( 'The breadcrumb label for the current page when a visitor hits a "page not found" (404) error.', 'awt' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save the Navigation tab: skip-link text and breadcrumb settings.
 */
function save_tab_navigation(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in handle_form_submission(); unslashed on the next line; values re-sanitized by Settings\sanitize() on save.
	$input = (array) ( $_POST['navigation'] ?? array() );
	$input = wp_unslash( $input );
	Settings\set( 'navigation.skipLinkText', (string) ( $input['skipLinkText'] ?? '' ) );
	Settings\set( 'navigation.homeItemText', (string) ( $input['homeItemText'] ?? '' ) );
	Settings\set( 'navigation.pageNotFoundItemText', (string) ( $input['pageNotFoundItemText'] ?? '' ) );
	$breadcrumb = (array) ( $input['breadcrumbAutoEmit'] ?? array() );
	Settings\set( 'navigation.breadcrumbAutoEmit.enabled', ! empty( $breadcrumb['enabled'] ) );
	Settings\set( 'navigation.breadcrumbAutoEmit.mobile', ! empty( $breadcrumb['mobile'] ) );
}

/**
 * Render the Custom code tab: three raw-code fields (before </head>, after
 * <body> opening, before </body>) with an accessibility caution up front.
 */
function render_tab_custom_code(): void {
	$head       = Settings\get( 'customCode.head' );
	$body_open  = Settings\get( 'customCode.afterBodyOpen' );
	$body_close = Settings\get( 'customCode.beforeBodyClose' );
	?>
	<details open>
		<summary><?php esc_html_e( '⚠ Read before using these fields', 'awt' ); ?></summary>
		<p><?php esc_html_e( 'These fields accept arbitrary code that AWT cannot validate. Custom code can:', 'awt' ); ?></p>
		<ul style="list-style: disc; padding-inline-start: 1.5em;">
			<li><?php esc_html_e( 'Break keyboard navigation by stealing or trapping focus', 'awt' ); ?></li>
			<li><?php esc_html_e( 'Break screen-reader announcements by overriding ARIA live regions (the regions screen readers watch for updates)', 'awt' ); ?></li>
			<li><?php esc_html_e( 'Lower color contrast by adding elements in colors outside your palette', 'awt' ); ?></li>
			<li><?php esc_html_e( 'Break reduced-motion support by adding animations that ignore the visitor\'s prefers-reduced-motion setting', 'awt' ); ?></li>
			<li><?php esc_html_e( 'Conflict with your site\'s Content Security Policy, if it has one', 'awt' ); ?></li>
		</ul>
		<p><strong><?php esc_html_e( "AWT's accessibility checker doesn't review code in these fields. You're responsible for the accessibility of anything you paste here.", 'awt' ); ?></strong></p>
	</details>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="awt-cc-head"><?php esc_html_e( 'Before </head>', 'awt' ); ?></label></th>
			<td><textarea id="awt-cc-head" name="customCode[head]" class="code" rows="6"><?php echo esc_textarea( (string) $head ); ?></textarea>
				<p class="awt-field-help"><?php esc_html_e( 'For analytics snippets, site-verification tags, and font or asset preloads. Loads after most other plugins.', 'awt' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="awt-cc-bo"><?php esc_html_e( 'After <body> opening', 'awt' ); ?></label></th>
			<td><textarea id="awt-cc-bo" name="customCode[afterBodyOpen]" class="code" rows="6"><?php echo esc_textarea( (string) $body_open ); ?></textarea>
				<p class="awt-field-help"><?php esc_html_e( 'For third-party widgets that need to load early, such as chat bubbles or A/B-test anti-flicker scripts.', 'awt' ); ?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="awt-cc-bc"><?php esc_html_e( 'Before </body>', 'awt' ); ?></label></th>
			<td><textarea id="awt-cc-bc" name="customCode[beforeBodyClose]" class="code" rows="6"><?php echo esc_textarea( (string) $body_close ); ?></textarea>
				<p class="awt-field-help"><?php esc_html_e( 'For scripts that can load last, such as footer chat widgets and deferred third-party tools.', 'awt' ); ?></p>
			</td>
		</tr>
	</table>
	<?php
}

/**
 * Save the Custom code tab's three injection fields.
 */
function save_tab_custom_code(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in handle_form_submission(); fields accept raw code by design and are wp_unslash()ed per field below.
	$input = (array) ( $_POST['customCode'] ?? array() );
	// Don't unslash + sanitize — these fields accept raw code per spec.
	// WP magic-quotes-style slash escaping IS removed (wp_unslash) so the
	// stored code matches what the user typed, but no other transforms.
	Settings\set( 'customCode.head', wp_unslash( (string) ( $input['head'] ?? '' ) ) );
	Settings\set( 'customCode.afterBodyOpen', wp_unslash( (string) ( $input['afterBodyOpen'] ?? '' ) ) );
	Settings\set( 'customCode.beforeBodyClose', wp_unslash( (string) ( $input['beforeBodyClose'] ?? '' ) ) );
}

/**
 * Render the Custom CSS tab: one raw-CSS field plus an "insert color
 * customization example" helper that appends a demo snippet.
 */
function render_tab_custom_css(): void {
	$css = Settings\get( 'customCss' );

	// Demo snippet appended by the "Insert color customization example" link.
	// Overrides Carbon's color tokens per scope — intentionally random / not
	// contrast-safe (the unguarded free-tier path; Premium adds live checks).
	$color_example = <<<'CSS'
/* DEMO — recolor Carbon by overriding its color tokens with random colors.
   Intentionally garish and NOT contrast-safe — this is the unguarded
   free-tier path (AWT Premium's color editor checks contrast for you).
   Targets the scope classes so these win over Carbon's own token values. */

/* Light scopes (White / g10) */
.cds--white,
.cds--g10 {
  --cds-background:            #fbe7c6;  /* page background      */
  --cds-layer-01:              #f6d365;  /* cards / tiles        */
  --cds-layer-02:              #ffd6e0;  /* nested layer         */
  --cds-layer-03:              #c1f0dc;  /* deepest layer        */
  --cds-field-01:              #f6d365;  /* inputs               */
  --cds-text-primary:          #2d1b69;  /* body text            */
  --cds-text-secondary:        #6a4c93;  /* secondary text       */
  --cds-text-on-color:         #ffffff;  /* text on accent fills */
  --cds-link-primary:          #d7263d;  /* links                */
  --cds-link-primary-hover:    #a51c30;
  --cds-interactive:           #1b9aaa;  /* accent               */
  --cds-button-primary:        #6a0572;  /* primary button       */
  --cds-button-primary-hover:  #4f0356;
  --cds-button-primary-active: #38013d;
  --cds-button-secondary:      #1b9aaa;
  --cds-support-error:         #d7263d;
  --cds-support-success:       #06a77d;
  --cds-border-subtle-00:      #e09f3e;
  --cds-border-subtle-01:      #e09f3e;
  --cds-border-strong-01:      #9e2a2b;
  --cds-focus:                 #ff2e63;  /* focus ring           */
}

/* Dark scopes (g90 / g100) — a different random set so you can see both */
.cds--g90,
.cds--g100 {
  --cds-background:            #1a1423;
  --cds-layer-01:              #372549;
  --cds-layer-02:              #774c60;
  --cds-layer-03:              #b75d69;
  --cds-field-01:              #372549;
  --cds-text-primary:          #ffd6e0;
  --cds-text-secondary:        #eebbc3;
  --cds-text-on-color:         #1a1423;
  --cds-link-primary:          #7fffd4;
  --cds-link-primary-hover:    #5ee0bb;
  --cds-interactive:           #f6d365;
  --cds-button-primary:        #7fffd4;
  --cds-button-primary-hover:  #5ee0bb;
  --cds-button-primary-active: #3fbf9b;
  --cds-button-secondary:      #b75d69;
  --cds-support-error:         #ff6b6b;
  --cds-support-success:       #06a77d;
  --cds-border-subtle-00:      #774c60;
  --cds-border-subtle-01:      #774c60;
  --cds-border-strong-01:      #eebbc3;
  --cds-focus:                 #f6d365;
}
CSS;
	?>
	<details open>
		<summary><?php esc_html_e( '⚠ Read before using this field', 'awt' ); ?></summary>
		<p><?php esc_html_e( 'Custom CSS can:', 'awt' ); ?></p>
		<ul style="list-style: disc; padding-inline-start: 1.5em;">
			<li><strong><?php esc_html_e( 'Hide focus outlines', 'awt' ); ?></strong> — <?php esc_html_e( 'removing them is the most common accessibility mistake on the web', 'awt' ); ?></li>
			<li><?php esc_html_e( 'Lower color contrast below the WCAG AA minimum', 'awt' ); ?></li>
			<li><?php esc_html_e( 'Hide elements that screen readers depend on', 'awt' ); ?></li>
			<li><?php esc_html_e( 'Override the prefers-reduced-motion rules built into AWT', 'awt' ); ?></li>
			<li><?php esc_html_e( "Clash with AWT's built-in styles in unexpected ways", 'awt' ); ?></li>
		</ul>
		<p><strong><?php esc_html_e( "AWT's accessibility checker doesn't review the CSS you add here.", 'awt' ); ?></strong></p>
	</details>
	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><label for="awt-cc-css"><?php esc_html_e( 'Custom CSS', 'awt' ); ?></label></th>
			<td>
				<textarea id="awt-cc-css" name="customCss" class="code" rows="14"><?php echo esc_textarea( (string) $css ); ?></textarea>
				<p style="margin-block-start: 0.5em;">
					<button type="button" class="button button-secondary" id="awt-insert-color-example"><?php esc_html_e( 'Insert color customization example', 'awt' ); ?></button>
				</p>
			</td>
		</tr>
	</table>
	<script>
	( function () {
		var btn = document.getElementById( 'awt-insert-color-example' );
		var ta  = document.getElementById( 'awt-cc-css' );
		if ( ! btn || ! ta ) { return; }
		var example = <?php echo wp_json_encode( $color_example ); ?>;
		btn.addEventListener( 'click', function () {
			ta.value += ( ta.value.trim() ? '\n\n' : '' ) + example;
			ta.dispatchEvent( new Event( 'input', { bubbles: true } ) ); // mark the form dirty
			ta.focus();
			ta.selectionStart = ta.selectionEnd = ta.value.length;
			ta.scrollTop = ta.scrollHeight;
		} );
	} )();
	</script>
	<?php
}

/**
 * Save the Custom CSS tab's stylesheet field.
 */
function save_tab_custom_css(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in handle_form_submission(); the field accepts raw CSS by design and is wp_unslash()ed inline.
	Settings\set( 'customCss', wp_unslash( (string) ( $_POST['customCss'] ?? '' ) ) );
}

/**
 * Render the Tools tab: re-run the welcome wizard, export configuration
 * (nonced GET link), and import configuration (multipart POST form).
 */
function render_tab_tools(): void {
	$rerun_url = wp_nonce_url(
		add_query_arg(
			array(
				'page'             => MENU_SLUG,
				'tab'              => 'tools',
				'awt_wizard_rerun' => '1',
			),
			admin_url( 'options-general.php' )
		),
		'awt_wizard_rerun'
	);
	?>
	<h2><?php esc_html_e( 'Welcome wizard', 'awt' ); ?></h2>
	<p class="awt-field-help">
		<?php esc_html_e( 'Runs the five-step setup wizard again. Your current settings are kept as the starting point.', 'awt' ); ?>
	</p>
	<p>
		<a href="<?php echo esc_url( $rerun_url ); ?>" class="button button-secondary">
			<?php esc_html_e( 'Re-run welcome wizard', 'awt' ); ?>
		</a>
	</p>

	<h2><?php esc_html_e( 'Export configuration', 'awt' ); ?></h2>
	<p class="awt-field-help">
		<?php esc_html_e( 'Download this site\'s AWT settings as a file — handy for copying to a staging site, sharing a starting point, or attaching to a support request.', 'awt' ); ?>
	</p>

	<?php
	$export_url = wp_nonce_url(
		add_query_arg(
			array(
				'page'       => MENU_SLUG,
				'tab'        => 'tools',
				'awt_export' => '1',
			),
			admin_url( 'options-general.php' )
		),
		'awt_export'
	);
	?>
	<p>
		<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary">
			<?php esc_html_e( 'Export configuration', 'awt' ); ?>
		</a>
	</p>

	<h2><?php esc_html_e( 'Import configuration', 'awt' ); ?></h2>
	<p class="awt-field-help">
		<?php esc_html_e( 'Upload a settings file to replace this site\'s current AWT settings.', 'awt' ); ?>
	</p>

	<form method="post"
			action="<?php echo esc_url( admin_url( 'options-general.php?page=' . MENU_SLUG . '&tab=tools' ) ); ?>"
			enctype="multipart/form-data"
			onsubmit="return confirm(<?php echo esc_attr( wp_json_encode( __( 'Importing will replace this site\'s current AWT settings with the uploaded file. Continue?', 'awt' ) ) ); ?>);">
		<?php wp_nonce_field( 'awt_import', 'awt_import_nonce' ); ?>
		<p>
			<label for="awt-import-file" class="screen-reader-text"><?php esc_html_e( 'AWT settings JSON file', 'awt' ); ?></label>
			<input type="file" id="awt-import-file" name="awt_import_file" accept="application/json,.json" required />
		</p>
		<?php submit_button( __( 'Import configuration', 'awt' ), 'secondary', 'submit', false ); ?>
	</form>
	<?php
}

/**
 * Export handler — streams the current AWT settings as a downloadable JSON
 * file. Triggered by the nonced `?awt_export=1` link in the Tools tab and
 * runs on `admin_init` so headers are sent before any HTML.
 *
 * Exports the fully-merged document (`Settings\all()`, defaults + overrides)
 * so the file is a complete, self-describing config snapshot — portable to a
 * fresh install where the importing site has different stored values.
 */
function handle_export(): void {
	if ( empty( $_GET['awt_export'] ) || $_GET['awt_export'] !== '1' ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'awt_export' ) ) {
		return;
	}

	$json = wp_json_encode(
		Settings\all(),
		JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
	);
	if ( $json === false ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => MENU_SLUG,
					'tab'       => 'tools',
					'awt_error' => rawurlencode( __( 'Could not serialize settings for export.', 'awt' ) ),
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	$site     = sanitize_title( get_bloginfo( 'name' ) );
	$site     = $site !== '' ? $site : 'site';
	$filename = 'awt-settings-' . $site . '-' . gmdate( 'Ymd' ) . '.json';

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Length: ' . strlen( $json ) );
	echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw JSON download, not HTML.
	exit;
}

/**
 * Import handler — reads an uploaded JSON file and replaces the site's AWT
 * settings with its contents. Runs on `admin_init` (post-redirect-get).
 *
 * Safety: capability + nonce gate; the upload must be a genuine uploaded
 * file; the payload must decode to an object that looks like an AWT settings
 * document (has `schemaVersion` or at least one known top-level key) so we
 * don't silently swallow arbitrary JSON. `Settings\save()` re-sanitizes the
 * whole document into the canonical shape, defaulting anything missing or
 * invalid — so a partial or older export imports cleanly.
 */
function handle_import(): void {
	if ( empty( $_POST['awt_import_nonce'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$nonce = sanitize_text_field( wp_unslash( $_POST['awt_import_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, 'awt_import' ) ) {
		return;
	}

	$redirect = add_query_arg(
		array(
			'page' => MENU_SLUG,
			'tab'  => 'tools',
		),
		admin_url( 'options-general.php' )
	);
	$fail     = static function ( string $msg ) use ( $redirect ): void {
		wp_safe_redirect( add_query_arg( 'awt_error', rawurlencode( $msg ), $redirect ) );
		exit;
	};

	// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified above; only tmp_name/size are used, gated by is_uploaded_file(), and the payload is re-sanitized by Settings\save().
	$file = $_FILES['awt_import_file'] ?? null;
	if ( ! is_array( $file ) || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
		$fail( __( 'No file was uploaded, or the upload failed.', 'awt' ) );
	}
	if ( ! empty( $file['size'] ) && (int) $file['size'] > 1024 * 1024 ) {
		$fail( __( 'That file is too large to be an AWT settings file.', 'awt' ) );
	}

	$raw = file_get_contents( $file['tmp_name'] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading a PHP upload temp file.
	if ( $raw === false || $raw === '' ) {
		$fail( __( 'The uploaded file was empty or unreadable.', 'awt' ) );
	}

	$decoded = json_decode( $raw, true );
	if ( ! is_array( $decoded ) ) {
		$fail( __( 'The uploaded file is not valid JSON.', 'awt' ) );
	}

	// Sanity: must look like an AWT settings document.
	$known_keys = array_keys( Settings\defaults() );
	if ( ! isset( $decoded['schemaVersion'] ) && count( array_intersect( array_keys( $decoded ), $known_keys ) ) === 0 ) {
		$fail( __( 'That file doesn\'t look like an AWT settings file.', 'awt' ) );
	}

	// NOTE: don't gate success on Settings\save()'s return value. It proxies
	// update_option(), which returns false when the new value equals the
	// stored one — i.e. importing a file identical to the current config is a
	// legitimate no-op, not a failure. We've already rejected the real error
	// cases above (bad nonce, no file, non-JSON, not-an-AWT-doc). Verify the
	// write instead: re-read and confirm the document round-tripped.
	Settings\save( $decoded );
	Settings\flush_cache();
	$stored = Settings\all();
	if ( ! isset( $stored['schemaVersion'] ) ) {
		$fail( __( 'Importing the settings failed. Please check the file and try again.', 'awt' ) );
	}

	wp_safe_redirect( add_query_arg( 'awt_saved', '1', $redirect ) );
	exit;
}

/**
 * Save handler for the Tools tab.
 */
function save_tab_tools(): void {
	// Tools tab renders its own forms (self-form tab). Export is a nonced GET
	// link handled by handle_export(); Import is a multipart POST handled by
	// handle_import(). Nothing for the standard save dispatcher to do.
}
