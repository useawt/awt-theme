<?php
/**
 * AWT Welcome wizard — first-run onboarding.
 *
 * Six-step admin-page wizard at Settings → AWT → Welcome:
 *
 *   0. Welcome (overview + a11y statement + AWT Premium promotion)
 *   1. Style & appearance (style variation + site light/dark)
 *   2. Identity (logo for light / dark mode + alt text — the Identity tab)
 *   3. Header (header preset + which icon buttons show)
 *   4. Typography (text size scale + live preview)
 *   5. Done (recap + onward links)
 *
 * On first theme activation, a one-shot admin notice points users here.
 * The wizard's state lives in `awt_theme_settings.welcome.{currentStep,
 * completed,choices}` so authors can leave and resume. Steps 2 and 4
 * reuse the matching AWT Settings tab renderers + savers so there is one
 * source of truth; steps 1 and 3 render inline (they merge two tabs each).
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\Wizard;

use AWT\Theme\AdminPage;
use AWT\Theme\HeaderPresets;
use AWT\Theme\Settings;
use AWT\Theme\StyleVariations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const NONCE_KEY   = 'awt_wizard_step';
const LAST_STEP   = 5;
const PREMIUM_URL = 'https://useawt.com/premium';

add_action( 'after_switch_theme', __NAMESPACE__ . '\\on_activation' );
add_action( 'admin_notices', __NAMESPACE__ . '\\maybe_show_welcome_notice' );
add_action( 'admin_init', __NAMESPACE__ . '\\handle_step_submission' );
add_action( 'admin_init', __NAMESPACE__ . '\\handle_rerun_request' );
add_action( 'admin_init', __NAMESPACE__ . '\\handle_notice_dismissal' );

/**
 * Handle ?awt_wizard_rerun=1 — resets the wizard's completed flag + step
 * counter so the "Re-run welcome wizard" button in Tools restarts the
 * flow from step 0 without losing the user's previous choices.
 */
function handle_rerun_request(): void {
	if ( empty( $_GET['awt_wizard_rerun'] ) || $_GET['awt_wizard_rerun'] !== '1' ) {
		return;
	}
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'awt_wizard_rerun' ) ) {
		return;
	}
	Settings\set( 'welcome.completed', false );
	Settings\set( 'welcome.currentStep', 0 );
	wp_safe_redirect(
		add_query_arg(
			array(
				'page' => AdminPage\MENU_SLUG,
				'tab'  => 'welcome',
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}

/**
 * Set the transient that drives the first-run admin notice.
 * Runs on `after_switch_theme` so it fires exactly once per activation.
 */
function on_activation(): void {
	$completed = (bool) Settings\get( 'welcome.completed' );
	if ( $completed ) {
		return; // Returning visitors don't see the notice again.
	}
	set_transient( 'awt_theme_welcome_notice', '1', 30 * DAY_IN_SECONDS );
}

/**
 * One-shot admin notice that points users to the wizard.
 */
function maybe_show_welcome_notice(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	if ( (bool) Settings\get( 'welcome.completed' ) ) {
		return;
	}
	if ( ! get_transient( 'awt_theme_welcome_notice' ) ) {
		return;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $screen && $screen->id === AdminPage\PAGE_HOOK && active_tab_is_welcome() ) {
		return;
	}
	$wizard_url  = add_query_arg(
		array(
			'page' => AdminPage\MENU_SLUG,
			'tab'  => 'welcome',
		),
		admin_url( 'themes.php' )
	);
	$dismiss_url = wp_nonce_url(
		add_query_arg( 'awt_dismiss_welcome', '1' ),
		'awt_dismiss_welcome'
	);
	?>
	<div class="notice notice-info is-dismissible">
		<p>
			<strong><?php esc_html_e( 'Welcome to AWT', 'awt' ); ?></strong>
			— <?php esc_html_e( 'Finish setting up your site in a few quick steps.', 'awt' ); ?>
			<a href="<?php echo esc_url( $wizard_url ); ?>" class="button button-primary" style="margin-inline-start: 1em;">
				<?php esc_html_e( 'Start setup', 'awt' ); ?>
			</a>
			<a href="<?php echo esc_url( $dismiss_url ); ?>" style="margin-inline-start: 1em;">
				<?php esc_html_e( 'Dismiss', 'awt' ); ?>
			</a>
		</p>
	</div>
	<?php
}

/**
 * Stop showing the welcome notice, for good.
 *
 * The core dismiss button only hides a notice for the page you are looking at,
 * so a notice that is only `is-dismissible` comes back on the next screen and
 * reads as broken. This handles the link beside it, which removes the flag the
 * notice is drawn from.
 */
function handle_notice_dismissal(): void {
	if ( empty( $_GET['awt_dismiss_welcome'] ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'awt_dismiss_welcome' ) ) {
		return;
	}
	delete_transient( 'awt_theme_welcome_notice' );
	wp_safe_redirect( remove_query_arg( array( 'awt_dismiss_welcome', '_wpnonce' ) ) );
	exit;
}

/**
 * Whether the AWT Settings page's active tab is the Welcome (wizard) tab.
 *
 * @return bool True when `?tab=welcome` is being viewed.
 */
function active_tab_is_welcome(): bool {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only tab check; no state change.
	return isset( $_GET['tab'] ) && $_GET['tab'] === 'welcome';
}

add_filter( 'awt_admin_settings_tabs', __NAMESPACE__ . '\\inject_welcome_tab' );
/**
 * Add a "welcome" tab to the AWT Settings page. Placed first for new
 * installs (so they land on it), appended for completed installs (re-run).
 *
 * @param array $tabs Registered AWT Settings tabs.
 * @return array Tabs with the welcome tab injected.
 */
function inject_welcome_tab( array $tabs ): array {
	$completed   = (bool) Settings\get( 'welcome.completed' );
	$welcome_tab = array(
		'label'  => __( 'Welcome', 'awt' ),
		'render' => __NAMESPACE__ . '\\render_wizard_page',
	);
	if ( ! $completed ) {
		return array( 'welcome' => $welcome_tab ) + $tabs;
	}
	$tabs['welcome'] = $welcome_tab;
	return $tabs;
}

/**
 * Render the wizard. Dispatches to a per-step renderer based on
 * `awt_theme_settings.welcome.currentStep`. Steps numbered 0–5.
 */
function render_wizard_page(): void {
	$current = (int) Settings\get( 'welcome.currentStep' );
	$current = max( 0, min( LAST_STEP, $current ) );
	?>
	<div class="awt-wizard" style="padding-block-start: 1.5em;">
		<ol class="awt-wizard-steps" style="display: flex; flex-wrap: wrap; gap: 1em; padding: 0; margin: 0 0 2em; list-style: none;" aria-label="<?php esc_attr_e( 'Wizard progress', 'awt' ); ?>">
			<?php
			$labels = array(
				__( 'Welcome', 'awt' ),
				__( 'Style & appearance', 'awt' ),
				__( 'Identity', 'awt' ),
				__( 'Header', 'awt' ),
				__( 'Typography', 'awt' ),
				__( 'Done', 'awt' ),
			);
			foreach ( $labels as $i => $label ) {
				$state  = $i < $current ? 'done' : ( $i === $current ? 'current' : 'upcoming' );
				$prefix = $state === 'done' ? '✓ ' : ( $state === 'current' ? '▸ ' : '○ ' );
				$weight = $state === 'current' ? 'font-weight: 600;' : '';
				printf(
					'<li style="%s" %s>%s%d. %s</li>',
					esc_attr( $weight ),
					$state === 'current' ? 'aria-current="step"' : '',
					esc_html( $prefix ),
					(int) $i + 1,
					esc_html( $label )
				);
			}
			?>
		</ol>
		<?php
		$renderer = __NAMESPACE__ . '\\render_step_' . $current;
		if ( function_exists( $renderer ) ) {
			call_user_func( $renderer );
		}
		?>
	</div>
	<style>.awt-wizard h2 { margin-block-start: 0; }</style>
	<?php
}

/*
-------------------------------------------------------------------------
 * Shared form scaffolding — each field step posts its own form so its
 * inputs submit together with the Back / Skip / Next buttons.
 * -----------------------------------------------------------------------
 */

/**
 * Open a wizard step's form: nonce, action, and step-number hidden fields.
 *
 * @param int $step Zero-based step number the form belongs to.
 */
function step_form_open( int $step ): void {
	?>
	<form method="post" action="" style="margin-block-start: 1.5em;">
		<?php wp_nonce_field( NONCE_KEY, '_awt_wizard_nonce' ); ?>
		<input type="hidden" name="awt_wizard_action" value="advance" />
		<input type="hidden" name="awt_wizard_step" value="<?php echo esc_attr( (string) $step ); ?>" />
	<?php
}

/**
 * Render a step's Back / Skip / Next buttons and close the form opened by
 * step_form_open().
 *
 * @param int  $step      Zero-based step number (Back is hidden on step 0).
 * @param bool $show_skip Whether to offer the "Skip this step" button.
 */
function step_nav_buttons( int $step, bool $show_skip = true ): void {
	?>
		<p style="margin-block-start: 2em;">
			<?php if ( $step > 0 ) : ?>
				<button type="submit" name="awt_wizard_direction" value="back" class="button button-secondary"><?php esc_html_e( '← Back', 'awt' ); ?></button>
			<?php endif; ?>
			<?php if ( $show_skip ) : ?>
				<button type="submit" name="awt_wizard_direction" value="skip" class="button button-secondary"><?php esc_html_e( 'Skip this step', 'awt' ); ?></button>
			<?php endif; ?>
			<button type="submit" name="awt_wizard_direction" value="next" class="button button-primary"><?php esc_html_e( 'Next →', 'awt' ); ?></button>
		</p>
	</form>
	<?php
}

/*
-------------------------------------------------------------------------
 * Step renderers.
 * -----------------------------------------------------------------------
 */

/** Step 0 — Welcome + AWT Premium promotion. */
function render_step_0(): void {
	?>
	<h2><?php esc_html_e( 'Welcome to AWT', 'awt' ); ?></h2>
	<p><?php esc_html_e( 'Set up how your site looks. You can skip any step and change everything later in AWT Settings.', 'awt' ); ?></p>
	<p><?php esc_html_e( 'AWT is built on IBM\'s Carbon Design System. Every block meets WCAG 2.2 AA, works with a keyboard and a screen reader, and has matching light and dark themes.', 'awt' ); ?></p>

	<div class="notice notice-info inline" style="margin: 1.5em 0; padding: 1em 1.25em; max-inline-size: 50em;">
		<p style="margin-block-start: 0;"><strong><?php esc_html_e( 'Want more? AWT Premium adds:', 'awt' ); ?></strong></p>
		<ul style="list-style: disc; padding-inline-start: 1.5em; margin-block: 0.5em;">
			<li><?php esc_html_e( 'More section layouts and page patterns', 'awt' ); ?></li>
			<li><?php esc_html_e( 'A contrast-safe color editor that matches your brand without breaking readability', 'awt' ); ?></li>
			<li><?php esc_html_e( 'Styled WooCommerce shop, product, cart, and checkout pages', 'awt' ); ?></li>
			<li><?php esc_html_e( 'Professionally translated European languages, and email support', 'awt' ); ?></li>
		</ul>
		<p style="margin-block-end: 0;">
			<a class="button button-secondary" href="<?php echo esc_url( PREMIUM_URL ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Learn more about AWT Premium →', 'awt' ); ?>
			</a>
		</p>
	</div>

	<?php
	step_form_open( 0 );
	step_nav_buttons( 0, false );
}

/** Step 1 — Style variation + site light/dark appearance. */
function render_step_1(): void {
	$variation          = (string) ( Settings\get( 'welcome.choices.styleVariation' ) ?? '' );
	$site_scheme        = (string) ( Settings\get( 'site.colorScheme' ) ?? 'default' );
	$appearance_options = array(
		'default' => __( 'Default (follows the visitor\'s device setting)', 'awt' ),
		'light'   => __( 'Always light', 'awt' ),
		'dark'    => __( 'Always dark', 'awt' ),
	);
	?>
	<h2><?php esc_html_e( 'Style & appearance', 'awt' ); ?></h2>
	<?php
	step_form_open( 1 );
	?>
		<h3><?php esc_html_e( 'Style variation', 'awt' ); ?></h3>
		<p class="awt-field-help"><?php esc_html_e( 'Each variation pairs a light theme with a dark theme.', 'awt' ); ?></p>
		<div style="margin-block: 1em 2em;">
			<?php StyleVariations\picker_ui( $variation ); ?>
		</div>

		<h3><?php esc_html_e( 'Site appearance', 'awt' ); ?></h3>
		<p class="awt-field-help"><?php esc_html_e( '"Default" follows each visitor\'s device setting, and the light/dark toggle if you show one.', 'awt' ); ?></p>
		<fieldset style="margin-block: 1em;">
			<legend class="screen-reader-text"><?php esc_html_e( 'Site appearance', 'awt' ); ?></legend>
			<?php foreach ( $appearance_options as $value => $label ) : ?>
				<label style="display:block; margin-block-end:0.5em;">
					<input type="radio" name="siteColorScheme" value="<?php echo esc_attr( $value ); ?>" <?php checked( $site_scheme, $value ); ?> />
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<p class="awt-field-help"><?php esc_html_e( 'Applying a style variation replaces any style changes you have made under Appearance → Editor → Styles.', 'awt' ); ?></p>
	<?php
	step_nav_buttons( 1 );
}

/** Step 2 — Identity (reuses the Identity tab: light/dark logo + alt). */
function render_step_2(): void {
	?>
	<h2><?php esc_html_e( 'Add your logo', 'awt' ); ?></h2>
	<?php
	step_form_open( 2 );
	AdminPage\render_tab_identity();
	step_nav_buttons( 2 );
}

/** Step 3 — Header preset + which header icon buttons show. */
function render_step_3(): void {
	$preset = (string) ( Settings\get( 'welcome.choices.headerPreset' ) ?? '' );
	$icons  = HeaderPresets\standard_icons();
	?>
	<h2><?php esc_html_e( 'Header', 'awt' ); ?></h2>
	<style><?php echo HeaderPresets\picker_styles(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static admin CSS authored in code, no dynamic input. ?></style>
	<?php
	step_form_open( 3 );
	?>
		<h3><?php esc_html_e( 'Header preset', 'awt' ); ?></h3>
		<p class="awt-field-help"><?php esc_html_e( 'Pick a ready-made header layout. You can rearrange the individual blocks later.', 'awt' ); ?></p>
		<div style="margin-block: 1em 2em;">
			<?php HeaderPresets\picker_ui( $preset ); ?>
		</div>

		<h3><?php esc_html_e( 'Header icon buttons', 'awt' ); ?></h3>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><?php esc_html_e( 'Header icon buttons', 'awt' ); ?></th>
				<td>
					<fieldset>
						<legend class="screen-reader-text"><?php esc_html_e( 'Header icon buttons', 'awt' ); ?></legend>
						<?php
						foreach ( $icons as $key => $entry ) :
							$present  = HeaderPresets\header_has_icon( $key );
							$input_id = 'awt-w-icon-' . esc_attr( $key );
							?>
							<label for="<?php echo esc_attr( $input_id ); ?>" style="display:flex; align-items:center; gap:0.5em; margin-block-end:0.75em;">
								<input type="checkbox" id="<?php echo esc_attr( $input_id ); ?>" name="headerIcons[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( $present ); ?> />
								<?php echo esc_html( $entry['label'] ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
		</table>
		<p class="awt-field-help"><?php esc_html_e( 'Changing the header preset replaces your current header layout.', 'awt' ); ?></p>
	<?php
	step_nav_buttons( 3 );
}

/** Step 4 — Typography (reuses the Typography tab). */
function render_step_4(): void {
	?>
	<h2><?php esc_html_e( 'Text size', 'awt' ); ?></h2>
	<?php
	step_form_open( 4 );
	AdminPage\render_tab_typography();
	step_nav_buttons( 4 );
}

/** Step 5 — Done (recap + onward links). */
function render_step_5(): void {
	$choices       = (array) ( Settings\get( 'welcome.choices' ) ?? array() );
	$style         = (string) ( $choices['styleVariation'] ?? '' );
	$header_preset = (string) ( $choices['headerPreset'] ?? '' );
	$logo_url      = (string) Settings\get( 'identity.logoUrl' );
	$site_scheme   = (string) ( Settings\get( 'site.colorScheme' ) ?? 'default' );
	$completed     = (bool) Settings\get( 'welcome.completed' );
	?>
	<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display of the setup-complete notice flag. ?>
	<?php if ( isset( $_GET['awt_done'] ) || $completed ) : ?>
		<div class="notice notice-success inline" style="margin: 0 0 1.5em; padding: 0.75em 1.25em;">
			<p style="margin: 0;"><?php esc_html_e( 'Setup complete — your choices are saved.', 'awt' ); ?></p>
		</div>
	<?php endif; ?>
	<h2><?php esc_html_e( "You're all set", 'awt' ); ?></h2>
	<p><?php esc_html_e( 'Here\'s a recap. You can change any of this later in AWT Settings.', 'awt' ); ?></p>

	<table class="form-table" role="presentation">
		<tr>
			<th scope="row"><?php esc_html_e( 'Style variation', 'awt' ); ?></th>
			<td><?php echo $style ? '<code>' . esc_html( $style ) . '</code>' : '<em>' . esc_html__( 'Theme default', 'awt' ) . '</em>'; ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Site appearance', 'awt' ); ?></th>
			<td><?php echo esc_html( ucfirst( $site_scheme ) ); ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Header preset', 'awt' ); ?></th>
			<td><?php echo $header_preset ? '<code>' . esc_html( $header_preset ) . '</code>' : '<em>' . esc_html__( 'Default (Marketing)', 'awt' ) . '</em>'; ?></td>
		</tr>
		<tr>
			<th scope="row"><?php esc_html_e( 'Logo', 'awt' ); ?></th>
			<td><?php echo $logo_url ? esc_html__( 'Configured', 'awt' ) : '<em>' . esc_html__( 'Site title only', 'awt' ) . '</em>'; ?></td>
		</tr>
	</table>

	<form method="post" action="">
		<?php wp_nonce_field( NONCE_KEY, '_awt_wizard_nonce' ); ?>
		<input type="hidden" name="awt_wizard_action" value="complete" />
		<p>
			<button type="submit" name="awt_wizard_direction" value="back" class="button button-secondary"><?php esc_html_e( '← Back', 'awt' ); ?></button>
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Finish setup', 'awt' ); ?></button>
		</p>
	</form>
	<?php
}

/**
 * Handle wizard step submissions (Back / Skip / Next / Finish).
 */
function handle_step_submission(): void {
	if ( empty( $_POST['_awt_wizard_nonce'] ) ) {
		return;
	}
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	$nonce = sanitize_text_field( wp_unslash( $_POST['_awt_wizard_nonce'] ) );
	if ( ! wp_verify_nonce( $nonce, NONCE_KEY ) ) {
		return;
	}

	$action    = sanitize_key( wp_unslash( $_POST['awt_wizard_action'] ?? '' ) );
	$direction = sanitize_key( wp_unslash( $_POST['awt_wizard_direction'] ?? 'next' ) );

	// The Done step's "Finish" completes; its "Back" returns to the last step.
	if ( $action === 'complete' && $direction !== 'back' ) {
		Settings\set( 'welcome.completed', true );
		Settings\set( 'welcome.currentStep', LAST_STEP );
		delete_transient( 'awt_theme_welcome_notice' );
		// Stay on the Done step (now marked complete) rather than leaving for
		// the Site Editor — the recap + onward links live here.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'     => AdminPage\MENU_SLUG,
					'tab'      => 'welcome',
					'awt_done' => '1',
				),
				admin_url( 'themes.php' )
			)
		);
		exit;
	}

	if ( $action === 'complete' && $direction === 'back' ) {
		Settings\set( 'welcome.currentStep', LAST_STEP - 1 );
		redirect_to_wizard();
	}

	if ( $action === 'advance' ) {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified above; cast to int.
		$step = max( 0, (int) ( $_POST['awt_wizard_step'] ?? 0 ) );

		// Per-step saves happen only when moving forward (not on Back/Skip).
		if ( $direction === 'next' ) {
			save_step( $step );
		}

		$next_step = $step;
		if ( $direction === 'back' && $step > 0 ) {
			$next_step = $step - 1;
		} elseif ( in_array( $direction, array( 'next', 'skip' ), true ) && $step < LAST_STEP ) {
			$next_step = $step + 1;
		}
		Settings\set( 'welcome.currentStep', $next_step );
		redirect_to_wizard();
	}
}

/**
 * Persist the inputs for a given step. Reuses the matching AWT Settings tab
 * savers where a step maps 1:1 to a tab, so there's one persistence path.
 *
 * Only ever called from handle_step_submission() after its nonce check, so
 * the per-read phpcs ignores below are safe.
 *
 * @param int $step Zero-based step number whose inputs should be saved.
 */
function save_step( int $step ): void {
	switch ( $step ) {
		case 1: // Style variation + site appearance.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handle_step_submission().
			if ( isset( $_POST['styleVariation'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handle_step_submission().
				$variation = sanitize_key( wp_unslash( $_POST['styleVariation'] ) );
				if ( $variation !== '' ) {
					Settings\set( 'welcome.choices.styleVariation', $variation );
					StyleVariations\apply_style_variation( $variation );
				}
			}
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handle_step_submission().
			$scheme = sanitize_key( wp_unslash( $_POST['siteColorScheme'] ?? 'default' ) );
			Settings\set( 'site.colorScheme', in_array( $scheme, array( 'default', 'light', 'dark' ), true ) ? $scheme : 'default' );
			break;

		case 2: // Identity (logos + alt).
			AdminPage\save_tab_identity();
			break;

		case 3: // Header preset + icon buttons.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified in handle_step_submission().
			$preset         = isset( $_POST['headerPreset'] ) ? sanitize_key( wp_unslash( $_POST['headerPreset'] ) ) : '';
			$preset_changed = false;
			if ( $preset !== '' ) {
				$current        = (string) ( Settings\get( 'welcome.choices.headerPreset' ) ?? '' );
				$preset_changed = ( $preset !== $current );
				Settings\set( 'welcome.choices.headerPreset', $preset );
				if ( $preset_changed ) {
					HeaderPresets\apply_header_preset( $preset );
				}
			}
			// Apply icon toggles only when the preset didn't just rewrite the
			// header. All standard icons are plain free buttons.
			if ( ! $preset_changed ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce verified in handle_step_submission(); values only tested for truthiness against the fixed standard_icons() key list.
				$submitted = (array) ( $_POST['headerIcons'] ?? array() );
				foreach ( array_keys( HeaderPresets\standard_icons() ) as $key ) {
					HeaderPresets\set_header_icon( $key, ! empty( $submitted[ $key ] ) );
				}
			}
			break;

		case 4: // Typography.
			AdminPage\save_tab_typography();
			break;
	}
}

/**
 * Redirect back to the wizard tab (post-redirect-get) and exit.
 */
function redirect_to_wizard(): void {
	wp_safe_redirect(
		add_query_arg(
			array(
				'page' => AdminPage\MENU_SLUG,
				'tab'  => 'welcome',
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}
