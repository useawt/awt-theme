<?php
/**
 * Page-level language override (§4 Language controls).
 *
 * A page that is entirely in another language can override the document
 * language just for itself: the `awt_theme_page_lang` post meta (set via the editor's
 * "Language" document panel, awt-blocks) replaces `<html lang>` on that singular
 * view via the language_attributes filter. Empty meta = inherit the site
 * language (WordPress core's default behavior).
 *
 * @package AWT\Theme
 */

declare( strict_types = 1 );

namespace AWT\Theme\PageLanguage;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const META_KEY = 'awt_theme_page_lang';

/**
 * Register the override meta on every public post type, exposed to the REST API
 * so the editor's document panel can read/write it.
 */
add_action(
	'init',
	static function (): void {
		$types = get_post_types( array( 'public' => true ), 'names' );
		foreach ( $types as $type ) {
			register_post_meta(
				$type,
				META_KEY,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'sanitize_callback' => static function ( $value ): string {
						// BCP-47-ish: letters, digits, hyphens only (e.g. "fr", "pt-BR").
						return is_string( $value ) ? preg_replace( '/[^A-Za-z0-9-]/', '', $value ) : '';
					},
					'auth_callback'     => static function (): bool {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
);

/**
 * Override the whole document language on a singular view when the page sets it.
 */
add_filter(
	'language_attributes',
	static function ( string $output, string $doctype = '' ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- language_attributes filter passes $doctype; arity must match.
		if ( ! is_singular() ) {
			return $output;
		}
		$id = get_queried_object_id();
		if ( ! $id ) {
			return $output;
		}
		$lang = get_post_meta( $id, META_KEY, true );
		$lang = is_string( $lang ) ? trim( $lang ) : '';
		if ( $lang === '' ) {
			return $output;
		}
		$attr = 'lang="' . esc_attr( $lang ) . '"';
		if ( preg_match( '/\blang="[^"]*"/i', $output ) ) {
			return preg_replace( '/\blang="[^"]*"/i', $attr, $output, 1 );
		}
		return trim( $output . ' ' . $attr );
	},
	10,
	2
);
