<?php
/**
 * Breadcrumb auto-emit — theme-level rendering of a breadcrumb landmark above
 * the main content area per phase-1 spec §5 "Navigation → Breadcrumbs". It is
 * emitted as a sibling before <main>, never inside it, so the skip link lands
 * on the page content instead of on the trail.
 *
 * Settings (all filterable; AWT Settings admin page will expose UI later):
 *   - awt_breadcrumb_auto_emit_enabled (default true)
 *   - awt_breadcrumb_auto_emit_mobile  (default true)
 *   - awt_breadcrumb_home_text         (default "Home")
 *   - awt_breadcrumb_404_text          (default "Page not found")
 *
 * Suppress behavior: when an awt/breadcrumb block is present in the post
 * content, the auto-emit suppresses itself to avoid duplicate <nav> landmarks.
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\Breadcrumb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post meta key for the per-post "hide breadcrumb" override. Registered with
 * show_in_rest so the editor's Document → Breadcrumb panel can bind to it.
 */
const META_HIDE = 'awt_theme_hide_breadcrumb';

/**
 * Resolution helper: AWT Settings → filter → spec default.
 *
 * The AWT Settings admin page is the authoritative source — values set
 * there flow through to the auto-emit without further code wiring. The
 * `apply_filters` step preserves the original developer-extension hook
 * for power users / Premium add-ons that override site-owner settings
 * for specific request conditions (e.g., hide breadcrumbs on a custom
 * post type).
 *
 * @param string $path        Dot-separated AWT Settings path to read.
 * @param string $filter_name Filter applied to the resolved value.
 * @return bool The resolved boolean setting.
 */
function setting_bool( string $path, string $filter_name ): bool {
	$value = null;
	if ( function_exists( '\\AWT\\Theme\\Settings\\get' ) ) {
		$value = \AWT\Theme\Settings\get( $path );
	}
	if ( $value === null ) {
		$value = true; // Spec default for every bool in this surface.
	}
	return (bool) apply_filters( $filter_name, (bool) $value );
}

/**
 * Text counterpart of setting_bool(): AWT Settings → fallback → filter.
 *
 * Reads a text setting from AWT Settings, substitutes the given fallback
 * when the stored value is empty, then passes the result through the
 * developer-extension filter.
 *
 * @param string $path        Dot-separated AWT Settings path to read.
 * @param string $fallback    Value used when the stored setting is empty.
 * @param string $filter_name Filter applied to the resolved value.
 * @return string The resolved text setting.
 */
function setting_text( string $path, string $fallback, string $filter_name ): string {
	$value = '';
	if ( function_exists( '\\AWT\\Theme\\Settings\\get' ) ) {
		$value = (string) \AWT\Theme\Settings\get( $path );
	}
	if ( $value === '' ) {
		$value = $fallback;
	}
	return (string) apply_filters( $filter_name, $value );
}

/**
 * Whether the auto-emit is enabled for this request.
 */
function is_enabled(): bool {
	if ( is_admin() || is_feed() || is_embed() ) {
		return false;
	}
	// Hide on the site root by default — breadcrumbs there only contain [Home].
	if ( is_front_page() ) {
		return false;
	}
	// Per-post override: an author can hide the breadcrumb on a specific
	// page/post via the editor's Document → Breadcrumb panel (the
	// `awt_theme_hide_breadcrumb` meta). This wins over the site-wide setting.
	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof \WP_Post && get_post_meta( $post->ID, META_HIDE, true ) ) {
			return false;
		}
	}
	return setting_bool( 'navigation.breadcrumbAutoEmit.enabled', 'awt_breadcrumb_auto_emit_enabled' );
}

/**
 * Whether the auto-emit shows on mobile (independent of desktop toggle).
 */
function show_on_mobile(): bool {
	return setting_bool( 'navigation.breadcrumbAutoEmit.mobile', 'awt_breadcrumb_auto_emit_mobile' );
}

/**
 * Configurable label sources.
 */
function home_text(): string {
	return setting_text(
		'navigation.homeItemText',
		_x( 'Home', 'breadcrumb', 'awt' ),
		'awt_breadcrumb_home_text'
	);
}

/**
 * Label for the 404 breadcrumb item: AWT Settings value or the i18n'd default.
 */
function not_found_text(): string {
	return setting_text(
		'navigation.pageNotFoundItemText',
		_x( 'Page not found', 'breadcrumb', 'awt' ),
		'awt_breadcrumb_404_text'
	);
}

/**
 * Build the trail for the current request as an ordered list of
 * { text, href, current } items. The last item carries current=true.
 *
 * @return array<int, array{text: string, href: string, current: bool}>
 */
function build_trail(): array {
	static $cached = null;
	if ( $cached !== null ) {
		return $cached;
	}

	$home = array(
		'text'    => home_text(),
		'href'    => home_url( '/' ),
		'current' => false,
	);

	$trail = array( $home );

	if ( is_404() ) {
		$trail[] = array(
			'text'    => not_found_text(),
			'href'    => '',
			'current' => true,
		);
		$cached  = $trail;
		return $trail;
	}

	if ( is_search() ) {
		$trail[] = array(
			// translators: %s — the visitor's search query.
			'text'    => sprintf( _x( 'Search: "%s"', 'breadcrumb', 'awt' ), get_search_query() ),
			'href'    => '',
			'current' => true,
		);
		$cached  = $trail;
		return $trail;
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof \WP_Post ) {
			// Post type that lives under an archive (e.g., default 'post' under blog).
			$post_type = get_post_type_object( $post->post_type );

			if ( $post->post_type === 'post' ) {
				$blog_page_id = (int) get_option( 'page_for_posts' );
				if ( $blog_page_id ) {
					$trail[] = array(
						'text'    => get_the_title( $blog_page_id ),
						'href'    => get_permalink( $blog_page_id ),
						'current' => false,
					);
				}
			} elseif ( $post_type && $post_type->has_archive ) {
				$trail[] = array(
					'text'    => (string) $post_type->labels->name,
					'href'    => (string) get_post_type_archive_link( $post->post_type ),
					'current' => false,
				);
			}

			// Hierarchical ancestor chain (pages, hierarchical CPTs).
			if ( is_post_type_hierarchical( $post->post_type ) && $post->post_parent ) {
				$ancestors = array_reverse( get_post_ancestors( $post ) );
				foreach ( $ancestors as $ancestor_id ) {
					$trail[] = array(
						'text'    => get_the_title( $ancestor_id ),
						'href'    => (string) get_permalink( $ancestor_id ),
						'current' => false,
					);
				}
			}

			$trail[] = array(
				'text'    => get_the_title( $post ),
				'href'    => '',
				'current' => true,
			);
		}
		$cached = $trail;
		return $trail;
	}

	if ( is_category() || is_tag() || is_tax() ) {
		$term = get_queried_object();
		if ( $term instanceof \WP_Term ) {
			$ancestors = array_reverse( get_ancestors( $term->term_id, $term->taxonomy ) );
			foreach ( $ancestors as $ancestor_id ) {
				$ancestor = get_term( $ancestor_id, $term->taxonomy );
				if ( $ancestor instanceof \WP_Term ) {
					$trail[] = array(
						'text'    => $ancestor->name,
						'href'    => (string) get_term_link( $ancestor ),
						'current' => false,
					);
				}
			}
			$trail[] = array(
				'text'    => $term->name,
				'href'    => '',
				'current' => true,
			);
		}
		$cached = $trail;
		return $trail;
	}

	if ( is_post_type_archive() ) {
		$trail[] = array(
			'text'    => (string) post_type_archive_title( '', false ),
			'href'    => '',
			'current' => true,
		);
		$cached  = $trail;
		return $trail;
	}

	if ( is_author() ) {
		$author  = get_queried_object();
		$trail[] = array(
			'text'    => $author && isset( $author->display_name ) ? (string) $author->display_name : (string) get_the_archive_title(),
			'href'    => '',
			'current' => true,
		);
		$cached  = $trail;
		return $trail;
	}

	if ( is_year() || is_month() || is_day() ) {
		if ( is_year() || is_month() || is_day() ) {
			$year            = get_the_date( 'Y' );
			$is_current_year = is_year();
			$trail[]         = array(
				'text'    => $year,
				'href'    => $is_current_year ? '' : get_year_link( (int) $year ),
				'current' => $is_current_year,
			);
		}
		if ( is_month() || is_day() ) {
			// get_the_date(), not wp_date(): wp_date() with no timestamp formats
			// *now*, so a March archive read in August was labelled "August".
			// The year above and the day below are already loop-based; this is
			// the same source, which is the queried month.
			$month_label = get_the_date( _x( 'F', 'breadcrumb month format', 'awt' ) );
			$trail[]     = array(
				'text'    => $month_label,
				'href'    => is_month() ? '' : get_month_link( (int) get_the_date( 'Y' ), (int) get_the_date( 'n' ) ),
				'current' => is_month(),
			);
		}
		if ( is_day() ) {
			$trail[] = array(
				'text'    => get_the_date( _x( 'j', 'breadcrumb day format', 'awt' ) ),
				'href'    => '',
				'current' => true,
			);
		}
		$cached = $trail;
		return $trail;
	}

	// Fallback: just home.
	$cached = $trail;
	return $trail;
}

/**
 * Render the auto-emit breadcrumb HTML.
 *
 * Suppresses when post content contains an awt/breadcrumb block.
 *
 * When the awt-blocks plugin is active, the trail renders through the real
 * awt/breadcrumb block (via do_blocks()), so WordPress enqueues the block's
 * Carbon stylesheet on pages where only the auto-emit appears — the same
 * per-block loading a hand-inserted breadcrumb block gets. Without the
 * plugin, a markup-only fallback keeps the landmark present and accessible.
 *
 * @return string
 */
function render(): string {
	if ( ! is_enabled() ) {
		return '';
	}

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof \WP_Post && has_block( 'awt/breadcrumb', $post ) ) {
			return '';
		}
	}

	$trail = build_trail();
	if ( count( $trail ) < 2 ) {
		// Only [Home] — no need to render a trail of one.
		return '';
	}

	$classes = 'awt-breadcrumb-auto' . ( show_on_mobile() ? '' : ' awt-breadcrumb-auto--hide-mobile' );

	$registry = \WP_Block_Type_Registry::get_instance();
	if ( $registry->is_registered( 'awt/breadcrumb' ) && $registry->is_registered( 'awt/breadcrumb-item' ) ) {
		return render_via_block( $trail, $classes );
	}

	return render_fallback( $trail, $classes );
}

/**
 * Render the trail through the awt/breadcrumb block so markup, styling, and
 * stylesheet loading all come from the block — one source of truth.
 *
 * @param array<int, array{text: string, href: string, current: bool}> $trail   Trail items.
 * @param string                                                       $classes Extra classes for the <nav>.
 * @return string
 */
function render_via_block( array $trail, string $classes ): string {
	$item_blocks = array();
	foreach ( $trail as $item ) {
		$attrs = array( 'text' => (string) $item['text'] );
		if ( ! empty( $item['current'] ) ) {
			$attrs['isCurrentPage'] = true;
		} elseif ( $item['href'] !== '' ) {
			$attrs['href'] = (string) $item['href'];
		}
		$item_blocks[] = array(
			'blockName'    => 'awt/breadcrumb-item',
			'attrs'        => $attrs,
			'innerBlocks'  => array(),
			'innerHTML'    => '',
			'innerContent' => array(),
		);
	}

	return do_blocks(
		serialize_block(
			array(
				'blockName'    => 'awt/breadcrumb',
				'attrs'        => array(
					'ariaLabel'       => __( 'Breadcrumbs', 'awt' ),
					'className'       => $classes,
					// The auto-emit trail always ends in the current page, so
					// no separator after the last item (Carbon guidance).
					'noTrailingSlash' => true,
				),
				'innerBlocks'  => $item_blocks,
				'innerHTML'    => '',
				'innerContent' => array_fill( 0, count( $item_blocks ), null ),
			)
		)
	);
}

/**
 * Markup-only fallback for when awt-blocks is inactive. Carbon's breadcrumb
 * stylesheet ships with the plugin, so only theme.css layout rules apply here;
 * the landmark structure and aria-current semantics stay intact regardless.
 *
 * @param array<int, array{text: string, href: string, current: bool}> $trail   Trail items.
 * @param string                                                       $classes Extra classes for the <nav>.
 * @return string
 */
function render_fallback( array $trail, string $classes ): string {
	$items = '';
	foreach ( $trail as $item ) {
		$is_current = ! empty( $item['current'] );
		if ( $is_current || $item['href'] === '' ) {
			$items .= sprintf(
				'<li class="cds--breadcrumb-item"><span%1$s>%2$s</span></li>',
				$is_current ? ' aria-current="page"' : '',
				esc_html( $item['text'] )
			);
		} else {
			$items .= sprintf(
				'<li class="cds--breadcrumb-item"><a class="cds--link" href="%1$s">%2$s</a></li>',
				esc_url( (string) $item['href'] ),
				esc_html( $item['text'] )
			);
		}
	}

	return sprintf(
		'<nav class="cds--breadcrumb cds--breadcrumb--no-trailing-slash %1$s" aria-label="%2$s"><ol class="cds--breadcrumb__list">%3$s</ol></nav>',
		esc_attr( $classes ),
		esc_attr__( 'Breadcrumbs', 'awt' ),
		$items
	);
}

/**
 * Wrap the breadcrumb in the region that sits above <main>.
 *
 * Only the side padding is copied from <main>, so the trail keeps lining up
 * with the content below it on whatever a site owner sets in the Site Editor.
 * The vertical spacing is NOT copied: <main>'s top gap does not come from that
 * inline value at all — theme.css overrides it with `!important` to clear the
 * fixed header — so the region takes its own top spacing from the same
 * theme.css rules instead. See "Breadcrumb region" there.
 *
 * The <div> is deliberately unlabelled. The breadcrumb's accessible name lives
 * on the <nav aria-label="Breadcrumbs"> inside it, which is the element that
 * carries the navigation role; a second labelled landmark around it would
 * announce one breadcrumb as two.
 *
 * @param string               $breadcrumb Rendered breadcrumb markup.
 * @param array<string, mixed> $padding    <main>'s padding attribute values.
 * @return string
 */
function wrap_region( string $breadcrumb, array $padding ): string {
	$sides = array();
	foreach ( array( 'right', 'left' ) as $side ) {
		if ( isset( $padding[ $side ] ) && $padding[ $side ] !== '' ) {
			$sides[ $side ] = $padding[ $side ];
		}
	}

	$style = '';
	if ( $sides && function_exists( 'wp_style_engine_get_styles' ) ) {
		// The Style Engine turns `var:preset|spacing|06` into the CSS custom
		// property WordPress prints for that preset — the same conversion core
		// performs for the block's own inline style.
		$styles = wp_style_engine_get_styles( array( 'spacing' => array( 'padding' => $sides ) ) );
		$style  = isset( $styles['css'] ) ? (string) $styles['css'] : '';
	}

	return sprintf(
		'<div class="awt-breadcrumb-region"%1$s>%2$s</div>',
		$style !== '' ? ' style="' . esc_attr( $style ) . '"' : '',
		$breadcrumb // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- block markup, escaped on render by the breadcrumb block.
	);
}

/**
 * Emit the breadcrumb as a sibling immediately *before* the main content area.
 *
 * Targets blocks tagged `<main id="main-content" class="cds--content">` — the
 * convention every Stage 1 page template uses.
 *
 * It sits outside <main> on purpose. A breadcrumb is navigation repeated across
 * pages, which HTML's own guidance keeps out of <main>; and inside <main> it was
 * the first thing "Skip to main content" reached, so keyboard and screen-reader
 * users had to tab through the whole trail before arriving at the content the
 * skip link promised them.
 */
add_filter(
	'render_block',
	static function ( string $block_content, array $block ): string {
		static $emitted = false;

		if ( $emitted || empty( $block['blockName'] ) || $block['blockName'] !== 'core/group' ) {
			return $block_content;
		}
		// Match the main-content group rendered by every Stage 1 template.
		if ( strpos( $block_content, 'id="main-content"' ) === false ) {
			return $block_content;
		}

		$breadcrumb = render();
		if ( $breadcrumb === '' ) {
			return $block_content;
		}
		$emitted = true;

		$padding = array();
		if ( isset( $block['attrs']['style']['spacing']['padding'] ) && is_array( $block['attrs']['style']['spacing']['padding'] ) ) {
			$padding = $block['attrs']['style']['spacing']['padding'];
		}

		return wrap_region( $breadcrumb, $padding ) . $block_content;
	},
	10,
	2
);

/**
 * Register the per-post "hide breadcrumb" meta on every public, REST-exposed
 * post type so the editor can read/write it and the auto-emit can check it.
 */
add_action(
	'init',
	static function (): void {
		$post_types = get_post_types(
			array(
				'public'       => true,
				'show_in_rest' => true,
			),
			'names'
		);
		foreach ( $post_types as $post_type ) {
			register_post_meta(
				$post_type,
				META_HIDE,
				array(
					'type'          => 'boolean',
					'single'        => true,
					'default'       => false,
					'show_in_rest'  => true,
					'auth_callback' => static function (): bool {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
);

/**
 * Enqueue the editor sidebar control (Document → Breadcrumb panel). Hand-written
 * JS — the theme has no build step — so it depends on the wp.* script handles
 * directly. `wp-editor` carries PluginDocumentSettingPanel on WP 6.6+;
 * `wp-edit-post` is listed too so the pre-6.6 fallback path is loadable.
 */
add_action(
	'enqueue_block_editor_assets',
	static function (): void {
		$rel  = '/assets/js/breadcrumb-editor.js';
		$path = get_template_directory() . $rel;
		if ( ! file_exists( $path ) ) {
			return;
		}
		wp_enqueue_script(
			'awt-breadcrumb-editor',
			get_template_directory_uri() . $rel,
			array( 'wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-components', 'wp-element', 'wp-data', 'wp-core-data', 'wp-i18n' ),
			(string) filemtime( $path ),
			true
		);
		// Data the in-panel live preview needs but can't derive client-side:
		// the resolved Home label, whether the auto-emit is on site-wide, and
		// the blog page's title (the parent crumb for single posts).
		$blog_page_id = (int) get_option( 'page_for_posts' );
		wp_add_inline_script(
			'awt-breadcrumb-editor',
			'window.awtBreadcrumbPreview=' . wp_json_encode(
				array(
					'homeText'      => home_text(),
					'globalEnabled' => setting_bool( 'navigation.breadcrumbAutoEmit.enabled', 'awt_breadcrumb_auto_emit_enabled' ),
					'blogTitle'     => $blog_page_id ? (string) get_the_title( $blog_page_id ) : '',
				)
			) . ';',
			'before'
		);
		if ( function_exists( 'wp_set_script_translations' ) ) {
			wp_set_script_translations( 'awt-breadcrumb-editor', 'awt' );
		}
	}
);
