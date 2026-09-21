<?php
/**
 * One-time moves that a site needs when it updates AWT, rather than installs it.
 *
 * The Theme Directory asks for a prefix of at least four letters on everything a
 * theme puts in the public namespace. AWT's was `awt_` — three — so the option,
 * the two post meta keys and the two user meta keys were renamed to `awt_theme_`
 * in 2026.08.0. A site that already had settings would otherwise open to a theme
 * that looks freshly installed, with its per-page language choices gone.
 *
 * Nothing here adds a database option: the marker that says "done" is the
 * schema version inside the settings payload, which already exists.
 *
 * **The rename alone is not enough, and `legacy_option()` /
 * `legacy_post_meta()` below are why.** `run()` is on `admin_init`, so
 * between an update landing and the next wp-admin page load a site serves
 * *defaults* to its visitors rather than the settings its owner saved. For a
 * hand re-upload that window is effectively zero, because the upload happens
 * in wp-admin and the very next screen closes it. For an automatic update it
 * is the normal case: nobody is logged in, by definition, so the wrong
 * skip-link text and the wrong header could be served for hours. The front
 * end therefore reads through those two helpers, which answer from the old
 * name when the new one holds nothing.
 *
 * @package AWT
 */

namespace AWT\Theme\Upgrade;

use AWT\Theme\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Old name => new name, for keys that live in wp_options. */
const LEGACY_OPTIONS = array( 'awt_settings' => Settings\OPTION_KEY );

/** Old name => new name, for keys that live in wp_postmeta. */
const LEGACY_POST_META = array(
	'awt_page_lang'       => 'awt_theme_page_lang',
	'awt_hide_breadcrumb' => 'awt_theme_hide_breadcrumb',
);

/** Old name => new name, for keys that live in wp_usermeta. */
const LEGACY_USER_META = array(
	'awt_whats_new_ack'  => 'awt_theme_whats_new_ack',
	'awt_whats_new_seen' => 'awt_theme_whats_new_seen',
);

/**
 * The value a site may still hold under an option's old name.
 *
 * Costs nothing on a site that has been renamed, because the caller only
 * reaches this when the current name returned nothing — which, one admin page
 * load after an update, is never again.
 *
 * @param string $current The current option name.
 * @return mixed The legacy value, or false when there is no legacy row.
 */
function legacy_option( string $current ) {
	$old = array_search( $current, LEGACY_OPTIONS, true );
	if ( false === $old ) {
		return false;
	}
	return get_option( $old, false );
}

/**
 * The value a site may still hold under a post meta key's old name.
 *
 * Free in practice: WordPress primes the whole meta cache for the queried
 * post in one query, so reading a second key off it adds none.
 *
 * @param int    $post_id Post to read.
 * @param string $current The current meta key.
 * @return mixed The legacy value, or an empty string when there is none.
 */
function legacy_post_meta( int $post_id, string $current ) {
	$old = array_search( $current, LEGACY_POST_META, true );
	if ( false === $old ) {
		return '';
	}
	return get_post_meta( $post_id, $old, true );
}

// admin_init, not init: the rename reads and writes, and there is no reason to
// make every visitor pay for it. The front end does not wait for it — it reads
// through the two helpers above.
add_action( 'admin_init', __NAMESPACE__ . '\\run' );

/**
 * Move anything still stored under the old names.
 */
function run(): void {
	move_option();
	move_meta();
}

/**
 * Carry the settings across to the new option name, once.
 */
function move_option(): void {
	foreach ( LEGACY_OPTIONS as $old => $new ) {
		if ( $old === $new ) {
			continue;
		}
		// Only when the new name holds nothing: a site that has already saved
		// settings under the new name must never be overwritten by a stale row.
		if ( false !== get_option( $new, false ) ) {
			continue;
		}
		$legacy = get_option( $old, false );
		if ( false === $legacy ) {
			continue;
		}
		update_option( $new, $legacy );
		delete_option( $old );
		Settings\flush_cache();
	}
}

/**
 * Rename the meta keys in place.
 *
 * Renaming a meta key has no API of its own, so this is one indexed UPDATE per
 * key — the same statement `WP_CLI\Utils` and core's own upgrade routines use
 * for the job. Guarded by a SELECT that stops after the first row, so the
 * normal case (nothing to move) costs one indexed lookup.
 */
function move_meta(): void {
	global $wpdb;

	// Renaming a meta key has no API of its own, so these are direct queries by
	// necessity: one indexed lookup per key, and an UPDATE only when that finds
	// something. Caching does not apply to a one-time rename.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	foreach ( array(
		$wpdb->postmeta => LEGACY_POST_META,
		$wpdb->usermeta => LEGACY_USER_META,
	) as $table => $map ) {
		foreach ( $map as $old => $new ) {
			// Select meta_key, not the id column: wp_postmeta calls its primary
			// key meta_id and wp_usermeta calls its umeta_id, and asking for
			// the wrong one returns null with an error nobody reads — which
			// looks exactly like "there was nothing to move".
			$exists = $wpdb->get_var( $wpdb->prepare( "SELECT meta_key FROM `$table` WHERE meta_key = %s LIMIT 1", $old ) );
			if ( ! $exists ) {
				continue;
			}
			$wpdb->update( $table, array( 'meta_key' => $new ), array( 'meta_key' => $old ) );
		}
	}
	// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}
