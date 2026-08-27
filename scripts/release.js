/**
 * Release preparation (Stage 1 spec, "Changelog communication → Release
 * script"). Pre-tag manual: the coordinator runs
 *
 *   npm run release:prepare 2026.08.0
 *
 * and the script:
 *   1. Reads CHANGELOG.md and locates the requested release section
 *      (an "## Unreleased" section may be promoted with --promote).
 *   2. Refreshes the readme.txt Changelog section (plain text, severity
 *      tags inline) between the CHANGELOG_START/END markers, and injects
 *      the accessibility statement from the sibling awt-theme clone
 *      between the ACCESSIBILITY_START/END markers (plugin repo only).
 *   3. Writes build/changelog.json (schemaVersion 1, last 10 releases) —
 *      the What's new panel reads this bundled file.
 *   4. Writes RELEASE_NOTES.md (the GitHub Release body).
 *   5. Stages the outputs in git and prints the coordinator checklist.
 *
 * One copy of this script lives in each repo (kept in sync manually —
 * see the spec). Repo differences are feature-detected: no readme.txt →
 * step 2 is skipped; no sibling awt-theme → accessibility injection is
 * skipped with a warning.
 *
 * Usage:
 *   node scripts/release.js <version> [--promote] [--dry-run]
 *     --promote   rename the "## Unreleased" section to this version,
 *                 stamped with today's date, before generating outputs
 *     --dry-run   print what would happen; write and stage nothing
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const ROOT = path.resolve(__dirname, '..');
const SEVERITIES = ['Security', 'A11y', 'Breaking', 'New', 'Improvement'];
const VERSION_RE = /^\d{4}\.\d{2}\.\d+(-[a-z0-9.]+)?$/;

function fail(msg) {
	console.error(`✖ ${msg}`);
	process.exit(1);
}

/**
 * Parse CHANGELOG.md into an ordered list of releases.
 *
 * @param {string} md CHANGELOG.md content.
 * @return {Array<{version: string, date: string, entries: Array}>} Releases, newest first.
 */
function parseChangelog(md) {
	const releases = [];
	let current = null;
	let severity = null;
	for (const line of md.split('\n')) {
		const release = line.match(/^## (.+)$/);
		if (release) {
			const m = release[1].match(
				/^(?<version>[^ ]+)(?: — (?<date>\d{4}-\d{2}-\d{2}))?$/
			);
			current = {
				version: m.groups.version,
				date: m.groups.date || '',
				entries: [],
			};
			releases.push(current);
			severity = null;
			continue;
		}
		const sev = line.match(/^### \[([A-Za-z0-9]+)\]$/);
		if (sev && current) {
			severity = sev[1];
			if (!SEVERITIES.includes(severity)) {
				fail(`Unknown severity [${severity}] in CHANGELOG.md.`);
			}
			continue;
		}
		if (current && severity && /^- /.test(line)) {
			current.entries.push({
				severity,
				summary: line.replace(/^- /, '').trim(),
				details: '',
			});
			continue;
		}
		// Wrapped continuation lines join the entry's summary.
		if (current && severity && /^ {2,}\S/.test(line)) {
			const last = current.entries[current.entries.length - 1];
			if (last) {
				last.summary = `${last.summary} ${line.trim()}`;
			}
		}
	}
	return releases;
}

/**
 * Replace the text between two HTML marker comments.
 *
 * @param {string} haystack File content.
 * @param {string} marker   Marker name (X_START / X_END).
 * @param {string} body     Replacement body.
 * @return {string|null} Updated content, or null when markers are absent.
 */
function replaceBetween(haystack, marker, body) {
	const re = new RegExp(
		`(<!-- ${marker}_START -->)[\\s\\S]*?(<!-- ${marker}_END -->)`
	);
	if (!re.test(haystack)) {
		return null;
	}
	return haystack.replace(re, `$1\n${body}\n$2`);
}

function main() {
	const args = process.argv.slice(2);
	const version = args.find((a) => !a.startsWith('--'));
	const promote = args.includes('--promote');
	const dryRun = args.includes('--dry-run');

	if (!version || !VERSION_RE.test(version)) {
		fail(
			'Usage: npm run release:prepare <YYYY.MM.PATCH> [-- --promote] [-- --dry-run]'
		);
	}

	const changelogPath = path.join(ROOT, 'CHANGELOG.md');
	let changelog = fs.readFileSync(changelogPath, 'utf8');

	if (promote) {
		const today = new Date().toISOString().slice(0, 10);
		if (!/^## Unreleased$/m.test(changelog)) {
			fail('No "## Unreleased" section to promote.');
		}
		changelog = changelog.replace(
			/^## Unreleased$/m,
			`## ${version} — ${today}`
		);
		if (!dryRun) {
			fs.writeFileSync(changelogPath, changelog);
		}
		console.log(`→ Promoted Unreleased → ${version} (${today}).`);
	}

	const releases = parseChangelog(changelog).filter(
		(r) => r.version !== 'Unreleased'
	);
	const release = releases.find((r) => r.version === version);
	if (!release) {
		fail(
			`CHANGELOG.md has no "## ${version}" section (use --promote to promote Unreleased).`
		);
	}
	if (release.entries.length === 0) {
		fail(`Release ${version} has no entries.`);
	}

	const staged = ['CHANGELOG.md'];

	// --- readme.txt (plugin repo; skipped when absent) -------------------
	const readmePath = path.join(ROOT, 'readme.txt');
	if (fs.existsSync(readmePath)) {
		let readme = fs.readFileSync(readmePath, 'utf8');

		const changelogTxt = releases
			.slice(0, 10)
			.map((r) => {
				const lines = r.entries.map(
					(e) =>
						`* [${e.severity}] ${e.summary}${e.details ? ' ' + e.details : ''}`
				);
				return `= ${r.version} — ${r.date} =\n${lines.join('\n')}`;
			})
			.join('\n\n');
		const withChangelog = replaceBetween(readme, 'CHANGELOG', changelogTxt);
		if (!withChangelog) {
			fail('readme.txt is missing the CHANGELOG_START/END markers.');
		}
		readme = withChangelog;

		const accessibilityPath = path.resolve(
			ROOT,
			'..',
			'awt-theme',
			'ACCESSIBILITY.md'
		);
		if (fs.existsSync(accessibilityPath)) {
			const statement = fs
				.readFileSync(accessibilityPath, 'utf8')
				.replace(/<!--[\s\S]*?-->\n*/g, '') // authoring notes stay out of readme
				.trim();
			const withA11y = replaceBetween(readme, 'ACCESSIBILITY', statement);
			if (withA11y) {
				readme = withA11y;
			} else {
				console.warn(
					'⚠ readme.txt has no ACCESSIBILITY_START/END markers — statement not injected.'
				);
			}
		} else {
			console.warn(
				'⚠ ../awt-theme/ACCESSIBILITY.md not found — statement not injected (clone the theme repo next to this one).'
			);
		}

		if (!dryRun) {
			fs.writeFileSync(readmePath, readme);
		}
		staged.push('readme.txt');
		console.log(
			'→ readme.txt refreshed (changelog + accessibility statement).'
		);
	} else {
		console.log('→ no readme.txt in this repo — skipped.');
	}

	// --- build/changelog.json --------------------------------------------
	const json = {
		schemaVersion: 1,
		currentVersion: version,
		releases: releases.slice(0, 10),
	};
	const jsonPath = path.join(ROOT, 'build', 'changelog.json');
	if (!dryRun) {
		fs.mkdirSync(path.dirname(jsonPath), { recursive: true });
		fs.writeFileSync(jsonPath, JSON.stringify(json, null, '\t') + '\n');
	}
	console.log(
		`→ build/changelog.json written (${json.releases.length} release(s)).`
	);

	// --- RELEASE_NOTES.md --------------------------------------------------
	const notes = [`## ${version} — ${release.date}`, ''];
	for (const sev of SEVERITIES) {
		const entries = release.entries.filter((e) => e.severity === sev);
		if (!entries.length) {
			continue;
		}
		notes.push(`### [${sev}]`, '');
		for (const e of entries) {
			notes.push(`- ${e.summary}${e.details ? ' ' + e.details : ''}`);
		}
		notes.push('');
	}
	const notesPath = path.join(ROOT, 'RELEASE_NOTES.md');
	if (!dryRun) {
		fs.writeFileSync(notesPath, notes.join('\n'));
	}
	staged.push('RELEASE_NOTES.md');
	console.log('→ RELEASE_NOTES.md written.');

	// --- stage + checklist ---------------------------------------------------
	if (!dryRun) {
		execSync(`git add ${staged.join(' ')}`, { cwd: ROOT });
	}
	console.log(`
Release ${version} prepared${dryRun ? ' (dry run — nothing written)' : ''}. Checklist:
  1. Review the staged files (git diff --staged).
  2. Commit:  git commit -m "Release ${version}"
  3. Build the zip if it is not current (this repo's build/check script) --
     release:publish refuses a zip older than the commit it is tagging.
  4. Publish: npm run release:publish ${version}
     (tags v${version}, pushes, and creates the GitHub Release with the zip attached)
  5. Repeat in the sibling repos -- releases are lockstep (same version, same day).`);
}

main();
