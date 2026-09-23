#!/usr/bin/env node
/**
 * Gate: nothing the browser downloads carries a comment.
 *
 *   npm run check:assets
 *
 * Why this exists. `theme.css` was authored and served as one file, so 166 KB
 * of notes about Carbon's cascade went to every visitor and sat in View Source
 * on every AWT site. The fix was to split authoring from serving — `src/` is
 * written for people, `assets/` is written for the browser — and a split only
 * stays split if something checks. One stylesheet dropped into `assets/` by
 * hand, or one build flag that starts preserving banners, and it is back.
 *
 * So this reads every stylesheet and script under `assets/` and fails on any
 * comment at all, including the `sourceMappingURL` kind, which is a comment
 * that fetches the commented original.
 *
 * What it does not do is parse CSS or JavaScript. A `/*` inside a string is
 * reported, and that is the right trade: a false positive costs a minute, and
 * the alternative is a parser that is wrong in the other direction.
 */

'use strict';

const fs = require('node:fs');
const path = require('node:path');

const ROOT = path.resolve(__dirname, '..');
const DIR = path.join(ROOT, 'assets');
const EXTENSIONS = ['.css', '.js', '.mjs'];

/**
 * Every file under a directory, depth first.
 *
 * @param {string} dir Absolute path to walk.
 * @return {string[]} Absolute file paths.
 */
function walk(dir) {
	const out = [];
	for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
		const full = path.join(dir, entry.name);
		if (entry.isDirectory()) {
			out.push(...walk(full));
		} else {
			out.push(full);
		}
	}
	return out;
}

/**
 * Where a comment starts in this text, or -1.
 *
 * Block openers count anywhere. Line comments count only at the start of a
 * line, because `//` is also every URL's authority separator and a minifier's
 * output is one long line — a leading `//` is the tell of a file that was
 * never compressed.
 *
 * @param {string} text File contents.
 * @return {number} 1-indexed line number, or -1 when the file is clean.
 */
function firstComment(text) {
	const block = text.indexOf('/*');
	if (block !== -1) {
		return text.slice(0, block).split('\n').length;
	}
	const lines = text.split('\n');
	const line = lines.findIndex((l) => /^\s*\/\//.test(l));
	return line === -1 ? -1 : line + 1;
}

if (!fs.existsSync(DIR)) {
	console.error('✗ assets/ does not exist — run npm run build:assets first.');
	process.exit(1);
}

const files = walk(DIR).filter((f) => EXTENSIONS.includes(path.extname(f)));
const offenders = [];

for (const file of files) {
	const line = firstComment(fs.readFileSync(file, 'utf8'));
	if (line !== -1) {
		offenders.push(`${path.relative(ROOT, file)}:${line}`);
	}
}

if (offenders.length) {
	console.error(
		`✗ ${offenders.length} served file(s) carry a comment. Author them in src/ ` +
			`and build with npm run build:assets:\n  ` +
			offenders.join('\n  ')
	);
	process.exit(1);
}

console.log(
	`✓ ${files.length} served stylesheet(s)/script(s) carry no comments.`
);
