<?php
/**
 * Registry — holds the design-system catalog and resolves the active one.
 *
 * §A "Design system abstraction". AWT registers Carbon at bootstrap; every
 * consumer (settings UI, wizard, block render.php files) resolves it through
 * Registry::get_active() rather than instantiating Carbon directly, so there
 * is exactly one resolution path to reason about and to reset in tests.
 *
 * The active selection is read from awt_theme_settings.designSystem.slug, defaulting
 * to 'carbon'. An unknown or unavailable slug falls back to Carbon so the
 * site never renders without a design system.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\DesignSystem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Registry {

	/**
	 * The catalog of registered systems, keyed by slug.
	 *
	 * @var array<string, DesignSystemInterface>
	 */
	private static array $systems = array();

	/**
	 * Register a design system. Replaces any existing entry with the same
	 * slug.
	 *
	 * Contract enforcement: for AVAILABLE systems, classes_for( $slug, [] )
	 * must return a non-empty string for every slug in supported_components().
	 * A system that fails is rejected (logged, not registered) so a buggy
	 * implementation can't silently ship blocks with no classes.
	 *
	 * @param DesignSystemInterface $system The system to register.
	 */
	public static function register( DesignSystemInterface $system ): void {
		if ( $system->is_available() ) {
			foreach ( $system->supported_components() as $slug ) {
				$classes = $system->classes_for( $slug, array() );
				if ( $classes === '' ) {
					error_log( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- developer-facing contract violation; fires only on misregistered design systems.
						sprintf(
							'[AWT] Design system "%s" rejected: classes_for("%s", []) returned empty. Every supported component must resolve to a non-empty root class.',
							$system->slug(),
							$slug
						)
					);
					return; // Reject the whole registration.
				}
			}
		}
		self::$systems[ $system->slug() ] = $system;
	}

	/** All registered systems, keyed by slug. */
	public static function all(): array {
		return self::$systems;
	}

	/** Only the selectable (available) systems. */
	public static function available(): array {
		return array_filter(
			self::$systems,
			static fn ( DesignSystemInterface $s ): bool => $s->is_available()
		);
	}

	/**
	 * Look up a registered system by slug.
	 *
	 * @param string $slug Design system slug, e.g. 'carbon'.
	 * @return DesignSystemInterface|null The system, or null when no system with that slug is registered.
	 */
	public static function get_by_slug( string $slug ): ?DesignSystemInterface {
		return self::$systems[ $slug ] ?? null;
	}

	/**
	 * The active design system. Reads awt_theme_settings.designSystem.slug; falls
	 * back to Carbon when the stored slug is missing, unknown, or not
	 * available.
	 */
	public static function get_active(): DesignSystemInterface {
		$slug = '';
		if ( function_exists( '\\AWT\\Theme\\Settings\\get' ) ) {
			$slug = (string) ( \AWT\Theme\Settings\get( 'designSystem.slug' ) ?? '' );
		}

		$system = $slug !== '' ? self::get_by_slug( $slug ) : null;
		if ( $system instanceof DesignSystemInterface && $system->is_available() ) {
			return $system;
		}

		// Fallback chain: Carbon, then any available system.
		$carbon = self::get_by_slug( 'carbon' );
		if ( $carbon instanceof DesignSystemInterface ) {
			return $carbon;
		}
		$available = self::available();
		if ( ! empty( $available ) ) {
			return reset( $available );
		}

		// Last resort: register Carbon on demand so callers always get a system.
		$carbon                           = new Carbon();
		self::$systems[ $carbon->slug() ] = $carbon;
		return $carbon;
	}

	/** Test/reset hook — clears the catalog. */
	public static function reset(): void {
		self::$systems = array();
	}
}

/*
------------------------------------------------------------------------
 * Module-level ergonomic helpers. render.php files call these via
 * function_exists( '\\AWT\\Theme\\DesignSystem\\get_active' ) so the blocks
 * plugin degrades gracefully when paired with a non-AWT theme.
 * ----------------------------------------------------------------------
 */

/** Shorthand for Registry::get_active(). */
function get_active(): DesignSystemInterface {
	return Registry::get_active();
}

/**
 * Shorthand for Registry::get_active()->classes_for().
 *
 * @param string $component Conceptual component slug, e.g. 'button'.
 * @param array  $variants  Modifier map; 'element' selects the sub-element (default 'root'), other keys carry attribute-driven modifiers.
 * @return string Space-separated CSS class string, or '' when unresolvable.
 */
function classes_for( string $component, array $variants = array() ): string {
	return Registry::get_active()->classes_for( $component, $variants );
}

/**
 * Register Carbon. Hooked early (after_setup_theme) so the design system is
 * resolvable by everything that runs on `init` (block + pattern registration,
 * the inserter filter, etc.).
 */
function bootstrap(): void {
	Registry::register( new Carbon() );
}
