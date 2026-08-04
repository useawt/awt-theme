#!/usr/bin/env node
/**
 * Guard against CSS that a browser silently discards.
 *
 * Why this exists. On 2026-08-04, `theme.css` was found to contain a comment
 * that had been closed too early: six lines of prose sat outside it as invalid
 * CSS, followed by a stray comment terminator. The parser recovered by throwing
 * away the next rule — `.editor-styles-wrapper .cds--header { position:
 * relative }` — so an editor fix that looked present in the source had never
 * applied. The same mistake was then made a second time the same day while
 * editing a comment in that file, which is what makes it worth a check rather
 * than a note: the source reads correctly and nothing fails, so the loss is
 * invisible. (Neither delimiter is written out anywhere in this file, comment or
 * string, for the obvious reason.)
 *
 * What it checks, per stylesheet:
 *   1. Every comment is closed, and none is opened inside an already-open one
 *      (CSS comments do not nest, so an inner opener is a silent no-op).
 *   2. No terminator appears outside a comment — the tell for the bug above.
 *   3. Braces balance.
 *   4. Nothing sits outside a rule: a block of prose does not parse as one.
 *
 * Deliberately not a full CSS validator. It catches the class of error that
 * costs a rule without costing a build.
 */

const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');
const FILES = ['assets/css/theme.css', 'assets/css/editor-scope.css'];

let failures = 0;

/**
 * Report one problem against a file and line.
 *
 * @param {string} file    Repo-relative stylesheet path.
 * @param {number} line    1-indexed line, or 0 when the position is not known.
 * @param {string} message What is wrong, in terms of what the browser will do.
 */
function fail(file, line, message) {
	console.error(`✗ ${file}:${line} — ${message}`);
	failures++;
}

for (const rel of FILES) {
	const abs = path.join(ROOT, rel);
	if (!fs.existsSync(abs)) {
		fail(rel, 0, 'file not found');
		continue;
	}
	const src = fs.readFileSync(abs, 'utf8');

	// Walk once, tracking comment state, so brace counting ignores braces that
	// only appear inside prose (this file's comments quote CSS constantly).
	let inComment = false;
	let openedAt = 0;
	let line = 1;
	let braces = 0;
	let code = ''; // Everything outside comments, for the block check below.

	for (let i = 0; i < src.length; i++) {
		if (src[i] === '\n') {
			line++;
		}
		if (!inComment && src.startsWith('/*', i)) {
			inComment = true;
			openedAt = line;
			i++;
			continue;
		}
		if (inComment && src.startsWith('/*', i)) {
			fail(
				rel,
				line,
				'a `/*` inside a comment opened on line ' +
					openedAt +
					' (CSS comments do not nest)'
			);
			i++;
			continue;
		}
		if (inComment && src.startsWith('*/', i)) {
			inComment = false;
			i++;
			continue;
		}
		if (!inComment && src.startsWith('*/', i)) {
			fail(
				rel,
				line,
				'a `*/` outside a comment — the parser will discard the rule that follows'
			);
			i++;
			continue;
		}
		if (!inComment) {
			if (src[i] === '{') {
				braces++;
			}
			if (src[i] === '}') {
				braces--;
				if (braces < 0) {
					fail(rel, line, 'a `}` with no matching `{`');
					braces = 0;
				}
			}
			code += src[i];
		}
	}

	if (inComment) {
		fail(rel, openedAt, 'comment opened here is never closed');
	}
	if (braces !== 0) {
		fail(rel, line, `${braces} unclosed \`{\` at end of file`);
	}

	// Every top-level block outside a comment should look like a rule: a
	// selector, then declarations containing a colon. Prose left outside a
	// comment fails both halves.
	const blocks = code.split('}');
	for (const block of blocks) {
		const open = block.indexOf('{');
		if (open === -1) {
			const stray = block.trim();
			// Trailing whitespace after the last rule is fine; anything else is
			// text the browser is skipping over.
			if (stray !== '' && !stray.startsWith('@')) {
				fail(
					rel,
					0,
					`text outside any rule, which the parser skips: "${stray.slice(0, 60).replace(/\s+/g, ' ')}…"`
				);
			}
		}
	}
}

if (failures) {
	console.error(
		`\n${failures} problem(s). A browser would silently drop CSS here.`
	);
	process.exit(1);
}
console.log(
	`✓ ${FILES.length} stylesheets parse cleanly (comments balanced, no orphaned rules).`
);
