#!/usr/bin/env node
/**
 * Build the WordPress.org distribution zip for the AWT theme.
 *
 *   npm run build:zip        →  awt.zip
 *
 * Why this script exists at all: until 2026-08-07 the theme had no packaging
 * step, so there was no defined answer to "what do we upload?" — and the
 * checkout it would have been cut from carries 563 MB of node_modules plus
 * vendor/, src/, scripts/ and half a dozen tool config files.
 *
 * Two decisions worth keeping:
 *
 * 1. INCLUDE list, not an exclude list. The sibling plugin repo learned this
 *    the expensive way: `.distignore` was written as the exclude list and
 *    `wp-scripts plugin-zip` never read it, so the shipped zip was missing
 *    four required files and fataled on activation. An exclude list fails
 *    open — a dev file added next month ships unless someone remembers to
 *    list it. An include list fails closed: anything new is left out until
 *    someone adds it here deliberately.
 *
 * 2. The zip's root folder is `awt`, not `awt-theme`. WordPress.org takes the
 *    theme slug from the folder name inside the zip, and the slug has to match
 *    the Text Domain in style.css. The repo directory is named awt-theme, so
 *    zipping the folder as-is would submit a theme whose slug and text domain
 *    disagree and whose translations therefore never load.
 *
 * The script verifies its own output by reading the finished zip back — the
 * checks below are the point of it, not a formality.
 */

'use strict';

const { execFileSync } = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

const ROOT = path.resolve(__dirname, '..');

/** The WP.org theme slug. Must equal the Text Domain in style.css. */
const SLUG = 'awt';

/** Everything that ships, and nothing else. Paths are relative to the theme root. */
const INCLUDE = [
	'assets',
	'inc',
	'languages',
	'parts',
	'patterns',
	'styles',
	'templates',
	'functions.php',
	'style.css',
	'theme.json',
	'readme.txt',
	'screenshot.png',
	// Three licences, because the zip redistributes three licensed works: our
	// own GPLv3, Carbon's Apache-2.0 (the compiled CSS), and IBM Plex's
	// OFL-1.1 (the woff2 files). Apache 2.0 §4(a) and OFL 1.1 both require
	// their text to travel with what they cover — naming them in readme.txt
	// is attribution, not the licence.
	'license.txt',
	'LICENSE-Apache-2.0.txt',
	'LICENSE-OFL-1.1.txt',
	// The Sass the minified stylesheet is compiled from. The Theme Directory
	// asks for the original of anything minified that ships.
	'src/foundation.scss',
];

/**
 * Files the theme cannot run without. Checked inside the finished zip, so a
 * broken build is caught here rather than by a reviewer installing it.
 * foundation.min.css is on the list because it is the always-loaded Carbon
 * subset — the theme renders unstyled without it, and it is a build artifact,
 * which is exactly the kind of file an include list can silently miss.
 */
const REQUIRED = [
	'style.css',
	'theme.json',
	'functions.php',
	'readme.txt',
	'screenshot.png',
	'assets/css/foundation.min.css',
	'assets/css/theme.css',
	// The source the minified stylesheet is built from. The Theme Directory
	// asks for the original of anything minified, and it is 2 KB.
	'src/foundation.scss',
];

/**
 * The one exception to FORBIDDEN below, listed on its own so the exclusion of
 * `src/` and `.scss` stays otherwise absolute.
 */
const ALLOWED_SOURCES = ['src/', 'src/foundation.scss'];

/**
 * Nothing matching these may appear in the zip. Redundant with the include
 * list by design: if someone adds a directory to INCLUDE without thinking,
 * this is the second net. Each entry is a reason, not a guess — node_modules
 * and vendor are the two that would blow the size limit, the dotfiles and
 * config files are what WP.org's own checkers flag.
 */
const FORBIDDEN = [
	{ re: /(^|\/)node_modules\//, why: 'dev dependencies' },
	{ re: /(^|\/)vendor\//, why: 'composer dev dependencies' },
	{ re: /(^|\/)src\//, why: 'uncompiled sources' },
	{ re: /(^|\/)scripts\//, why: 'build tooling' },
	{ re: /(^|\/)tests?\//, why: 'test suite' },
	{ re: /(^|\/)\.[^/]/, why: 'dotfile' },
	{ re: /\.(scss|map)$/, why: 'source/sourcemap' },
	{ re: /\.dist$/, why: 'tool config' },
	{
		re: /(^|\/)(package|composer)(-lock)?\.(json|lock)$/,
		why: 'dev manifest',
	},
	{ re: /(^|\/)eslint\.config\.js$/, why: 'tool config' },
];

function fail(message) {
	process.stderr.write(`\n✗ ${message}\n\n`);
	process.exit(1);
}

// --- Stage ------------------------------------------------------------------
// Copy the include list into a temp directory named after the slug, so the zip
// carries `awt/…` regardless of what the repo directory is called.

const stageRoot = fs.mkdtempSync(path.join(os.tmpdir(), 'awt-zip-'));
const stage = path.join(stageRoot, SLUG);
fs.mkdirSync(stage);

for (const entry of INCLUDE) {
	const from = path.join(ROOT, entry);
	if (!fs.existsSync(from)) {
		fail(`Include list names "${entry}", which does not exist in ${ROOT}.`);
	}
	fs.cpSync(from, path.join(stage, entry), { recursive: true });
}

// macOS sprinkles these through any directory that has been opened in Finder.
for (const junk of execFileSync('find', [stage, '-name', '.DS_Store'], {
	encoding: 'utf8',
})
	.split('\n')
	.filter(Boolean)) {
	fs.rmSync(junk);
}

// --- Zip --------------------------------------------------------------------

const outfile = path.join(ROOT, `${SLUG}.zip`);
fs.rmSync(outfile, { force: true });

// -r recurse, -q quiet, -X drop the extra macOS attributes that would otherwise
// add an Apple-specific header to every entry.
execFileSync('zip', ['-rqX', outfile, SLUG], { cwd: stageRoot });
fs.rmSync(stageRoot, { recursive: true, force: true });

// --- Verify the artifact, not the intent ------------------------------------

const listed = execFileSync('unzip', ['-Z1', outfile], { encoding: 'utf8' })
	.split('\n')
	.filter(Boolean);

// Strip the `awt/` prefix so the checks below read as theme-relative paths.
const paths = listed.map((p) => p.replace(new RegExp(`^${SLUG}/`), ''));

const missing = REQUIRED.filter((f) => !paths.includes(f));
if (missing.length) {
	fail(`Zip is missing required file(s):\n  ${missing.join('\n  ')}`);
}

const offenders = [];
for (const p of paths) {
	if (ALLOWED_SOURCES.includes(p)) {
		continue;
	}
	const hit = FORBIDDEN.find((rule) => rule.re.test(p));
	if (hit) {
		offenders.push(`${p}  (${hit.why})`);
	}
}
if (offenders.length) {
	fail(
		`Zip contains ${offenders.length} file(s) that must not ship:\n  ` +
			offenders.slice(0, 20).join('\n  ') +
			(offenders.length > 20
				? `\n  …and ${offenders.length - 20} more`
				: '')
	);
}

// Every entry must sit under the slug folder — WP.org rejects a zip whose
// files are at the top level, and it is a silent failure otherwise.
const stray = listed.filter((p) => !p.startsWith(`${SLUG}/`));
if (stray.length) {
	fail(
		`Zip has ${stray.length} entr(ies) outside the ${SLUG}/ folder, e.g. ${stray[0]}`
	);
}

// The version in the zip is what reviewers see; report it so a stale or
// suffixed version (see submission blocker S2) is visible at build time.
const styleCss = fs.readFileSync(path.join(ROOT, 'style.css'), 'utf8');
const version =
	(styleCss.match(/^\s*Version:\s*(.+)$/m) || [])[1]?.trim() ??
	'(unreadable)';
const textDomain =
	(styleCss.match(/^\s*Text Domain:\s*(.+)$/m) || [])[1]?.trim() ?? '';

if (textDomain !== SLUG) {
	fail(
		`Text Domain in style.css is "${textDomain}" but the zip folder is "${SLUG}". ` +
			`WordPress.org derives the slug from the folder, and the two must match.`
	);
}

const bytes = fs.statSync(outfile).size;
// Count from `listed`, not `paths`: stripping the slug prefix turns the `awt/`
// folder entry into an empty string, which reads as a file and inflates the
// number a person uses to sanity-check the package.
const files = listed.filter((p) => !p.endsWith('/')).length;

process.stdout.write(
	`\n✓ ${path.basename(outfile)} — ${files} files, ${(bytes / 1024 / 1024).toFixed(2)} MB\n` +
		`  slug ${SLUG} · version ${version}\n\n`
);
