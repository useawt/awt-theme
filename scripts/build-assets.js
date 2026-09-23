#!/usr/bin/env node
/**
 * Compile every stylesheet and script the theme serves, from `src/` into
 * `assets/`.
 *
 *   npm run build:assets
 *
 * Why this exists. Until 2026-09-23 `theme.css` was authored and served as the
 * same file: 245 KB, of which 166 KB — 68% — was the commentary that explains
 * the Carbon overrides. Every visitor downloaded all of it, and every visitor
 * who opened View Source read it. Prose does not compress the way CSS does, so
 * the comments cost 65 KB of the 76 KB that stylesheet transferred.
 *
 * The split is the fix: `src/` is written for whoever maintains the theme and
 * keeps every word of the reasoning, `assets/` is written for the browser and
 * carries none of it. `scripts/check-assets-clean.js` holds that line, and CI
 * rebuilds and diffs so the committed artifacts cannot drift from their source.
 *
 * Source maps are deliberately off. A map is a second public file that points
 * at the first, which would put the comments back on the site by another route.
 */

'use strict';

const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');

const ROOT = path.resolve(__dirname, '..');

/**
 * What is built, and from what. `from` is authored; `to` is served.
 *
 * foundation.scss is Sass proper (it pulls Carbon's partials out of
 * node_modules, hence the load path). The other three are plain CSS and JS —
 * they go through the same compilers only to be stripped and compressed.
 */
const CSS = [
	['src/foundation.scss', 'assets/css/foundation.min.css'],
	['src/theme.css', 'assets/css/theme.min.css'],
	['src/editor-scope.css', 'assets/css/editor-scope.min.css'],
];

const JS = [['src/breadcrumb-editor.js', 'assets/js/breadcrumb-editor.min.js']];

/**
 * Run a local binary, letting its own diagnostics through to the terminal.
 *
 * @param {string}   bin  Executable name inside node_modules/.bin.
 * @param {string[]} args Arguments to pass it.
 */
function run(bin, args) {
	execFileSync(path.join(ROOT, 'node_modules', '.bin', bin), args, {
		cwd: ROOT,
		stdio: 'inherit',
	});
}

for (const [from, to] of CSS) {
	fs.mkdirSync(path.join(ROOT, path.dirname(to)), { recursive: true });
	run('sass', [
		'--style=compressed',
		'--no-source-map',
		'--load-path=node_modules',
		from,
		to,
	]);
}

for (const [from, to] of JS) {
	fs.mkdirSync(path.join(ROOT, path.dirname(to)), { recursive: true });
	run('terser', [
		from,
		'--compress',
		'--mangle',
		'--comments',
		'false',
		'--output',
		to,
	]);
}

for (const [from, to] of [...CSS, ...JS]) {
	const before = fs.statSync(path.join(ROOT, from)).size;
	const after = fs.statSync(path.join(ROOT, to)).size;
	process.stdout.write(
		`✓ ${to.padEnd(38)} ${String(before).padStart(7)} → ${String(after).padStart(7)} B\n`
	);
}
