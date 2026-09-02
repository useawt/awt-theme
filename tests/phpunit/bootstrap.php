<?php
/**
 * PHPUnit bootstrap: loads the WordPress test library provided by wp-env and
 * makes AWT the active theme, so `functions.php` and everything in `inc/` load
 * the way WordPress loads them rather than being require'd by hand.
 *
 * That matters: several of the functions under test read settings, and the
 * settings layer is only wired up once the theme has booted.
 *
 * The blocks plugin is deliberately NOT installed here. This suite covers the
 * theme's own pure functions — settings, scopes, the upgrade migration, the
 * contrast maths, the D6 resolvers — none of which read the plugin, and keeping
 * the environment to one repository keeps CI to one checkout.
 *
 * Anything that genuinely needs both halves belongs elsewhere and already has a
 * home: the browser gates in `awt-blocks` run against a site with both
 * installed, and the two coexistence tests from 2026-09-01 cover each half
 * alone. If a theme function ever starts reading the plugin (the What's new
 * panel reads its changelog), test that one there rather than pulling the
 * plugin in here.
 *
 * Run via `npm run test:php`.
 *
 * @package AWT\Theme
 */

$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = '/wordpress-phpunit';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	echo "Could not find the WordPress test library at {$_tests_dir}. Run this suite via `npm run test:php` (wp-env).\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CLI bootstrap message.
	exit( 1 );
}

// Core's bootstrap reads these before it loads the theme.
$GLOBALS['wp_tests_options'] = array(
	'template'   => 'awt',
	'stylesheet' => 'awt',
);

require_once $_tests_dir . '/includes/functions.php';
require $_tests_dir . '/includes/bootstrap.php';
