/**
 * Release publication (Stage 1 spec, "Every released version gets a tag and
 * a GitHub Release"). Run it after `release:prepare` and its commit:
 *
 *   npm run release:publish 2026.09.0
 *
 * and the script:
 *   1. Checks the version against package.json, CHANGELOG.md,
 *      RELEASE_NOTES.md and every file that carries the number — the
 *      theme/plugin header, the AWT_*_VERSION constants, readme.txt's
 *      stable tag — and refuses if any of them disagree.
 *   2. Checks the working tree is clean, the branch is main, no
 *      v<version> tag exists yet, and that the only commit this branch
 *      has beyond origin is the release commit itself.
 *   3. Checks the built zip exists and is newer than the commit being
 *      tagged — a zip built before the last commit is not what shipped.
 *   4. Tags v<version>, pushes the branch and the tag, and creates the
 *      GitHub Release with RELEASE_NOTES.md as the body and the zip
 *      attached.
 *
 * This exists because the same three steps were printed as a checklist for
 * 2026.08.0 and skipped, which left no record of which commit the
 * submitted artifact came from. A step a tool performs is a step that
 * happens.
 *
 * One copy of this script lives in each repo (kept in sync manually — see
 * the spec). Repo differences are feature-detected: the zip is whichever
 * single *.zip sits in the repo root, and the version sightings are the
 * ones this repo happens to have.
 *
 * Usage:
 *   node scripts/release-publish.js <version> [--dry-run]
 *     --dry-run   print every command; run none of them
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const ROOT = path.resolve(__dirname, '..');
const VERSION_RE = /^\d{4}\.\d{2}\.\d+(-[a-z0-9.]+)?$/;

function fail(msg) {
	console.error(`✖ ${msg}`);
	process.exit(1);
}

/**
 * Run a command and return its trimmed stdout.
 *
 * @param {string} cmd Command line.
 * @return {string} Trimmed stdout.
 */
function sh(cmd) {
	return execSync(cmd, { cwd: ROOT, encoding: 'utf8' }).trim();
}

/**
 * Run a command for its effect, echoing it first.
 *
 * @param {string}  cmd    Command line.
 * @param {boolean} dryRun Print only.
 */
function run(cmd, dryRun) {
	console.log(`  $ ${cmd}`);
	if (!dryRun) {
		execSync(cmd, { cwd: ROOT, stdio: 'inherit' });
	}
}

/**
 * The single built zip in the repo root.
 *
 * @return {string} File name.
 */
function findZip() {
	const zips = fs.readdirSync(ROOT).filter((f) => f.endsWith('.zip'));
	if (zips.length === 0) {
		fail('No built zip in the repo root. Run the build first.');
	}
	if (zips.length > 1) {
		fail(`More than one zip in the repo root: ${zips.join(', ')}.`);
	}
	return zips[0];
}

/**
 * Every file in this repo that carries the version number, and what it says.
 *
 * The number lives in more places than package.json — a theme's style.css
 * header and AWT_THEME_VERSION, a plugin's header and AWT_BLOCKS_VERSION,
 * readme.txt's stable tag — and all but the stable tag are bumped by hand
 * before the release commit. Missing one ships a version that disagrees with
 * itself: readme.txt's stable tag sat eight releases behind the plugin header
 * before anyone noticed. Which of these files a repo has differs, so they are
 * found rather than listed.
 *
 * @return {Array<{file: string, what: string, says: string|null}>} Sightings.
 */
function versionSightings() {
	const sightings = [];
	const read = (file) => {
		const full = path.join(ROOT, file);
		return fs.existsSync(full) ? fs.readFileSync(full, 'utf8') : null;
	};
	const first = (src, re) => {
		const m = src.match(re);
		return m ? m[1] : null;
	};

	const style = read('style.css');
	if (style && /^Theme Name:/m.test(style)) {
		sightings.push({
			file: 'style.css',
			what: 'theme header',
			says: first(style, /^Version:[ \t]*(\S+)[ \t]*$/m),
		});
	}

	const readme = read('readme.txt');
	const stable = readme && first(readme, /^Stable tag:[ \t]*(\S+)[ \t]*$/m);
	if (stable) {
		sightings.push({
			file: 'readme.txt',
			what: 'stable tag',
			says: stable,
		});
	}

	for (const file of fs.readdirSync(ROOT)) {
		if (!file.endsWith('.php')) {
			continue;
		}
		const src = read(file);
		if (/^[ \t]*\*[ \t]*Plugin Name:/m.test(src)) {
			sightings.push({
				file,
				what: 'plugin header',
				says: first(src, /^[ \t]*\*[ \t]*Version:[ \t]*(\S+)[ \t]*$/m),
			});
		}
		const constant = src.match(/^const (AWT_[A-Z_]*VERSION) = '([^']*)';/m);
		if (constant) {
			sightings.push({
				file,
				what: constant[1],
				says: constant[2],
			});
		}
	}

	return sightings;
}

/**
 * Refuse a release carrying commits it does not know about.
 *
 * This checkout is shared. Another session can commit to it, and a commit
 * sitting under the release commit goes into the zip and onto every site that
 * updates — while the release notes, which are written from CHANGELOG.md
 * alone, say nothing about it. On 2026-09-20 that happened: a header
 * focus-ring change from a parallel session shipped inside 2026.09.28, and
 * what caught it was the commit-identity hook, which is not what that hook is
 * for. Nothing about releasing had an opinion.
 *
 * The rule is the narrowest one that would have caught it: everything except
 * the release commit itself is already on the remote, and the remote holds
 * nothing this build has not got. Both directions matter — commits ahead ride
 * along unannounced, commits behind mean the zip was built without them.
 *
 * It is a prompt, not a veto: push the commits (or drop them) and run again.
 */
function assertNothingRidesAlong() {
	try {
		sh('git rev-parse --abbrev-ref --symbolic-full-name @{u}');
	} catch {
		fail(
			'This branch has no upstream, so there is no way to tell which ' +
				'commits this release is carrying. Set one and run again.'
		);
	}
	try {
		sh('git fetch --quiet origin');
	} catch {
		fail(
			'Could not reach origin to check what this release is carrying. ' +
				'A release that cannot be checked is not one to publish.'
		);
	}

	const behind = Number(sh('git rev-list --count HEAD..@{u}'));
	if (behind > 0) {
		fail(
			`origin has ${behind} commit(s) this build does not, so the zip ` +
				'was built without them. Pull, rebuild, and run again.'
		);
	}

	// Newest first, so the release commit is the first line — that one is
	// meant to be here. Anything under it is not.
	// Quoted: the separator is a pipe and these commands run through a shell.
	const ahead = sh("git log --format='%h|%an|%s' @{u}..HEAD")
		.split('\n')
		.filter(Boolean);
	const ridingAlong = ahead.slice(1);
	if (ridingAlong.length) {
		const listed = ridingAlong
			.map((line) => {
				const [hash, author, ...rest] = line.split('|');
				return `    ${hash}  ${author}  ${rest.join('|')}`;
			})
			.join('\n');
		fail(
			`${ridingAlong.length} commit(s) here are not on origin and are ` +
				`not the release commit:\n${listed}\n  Their code is in the ` +
				'zip, and the release notes come from CHANGELOG.md alone — so ' +
				'either push them and give them changelog entries, or take ' +
				'them off this branch, then run again.'
		);
	}
}

function main() {
	const args = process.argv.slice(2);
	const version = args.find((a) => !a.startsWith('--'));
	const dryRun = args.includes('--dry-run');

	if (!version || !VERSION_RE.test(version)) {
		fail('Usage: npm run release:publish <YYYY.MM.PATCH> [-- --dry-run]');
	}

	// --- the version has to mean the same thing everywhere ------------------
	const pkg = JSON.parse(
		fs.readFileSync(path.join(ROOT, 'package.json'), 'utf8')
	);
	if (pkg.version !== version) {
		fail(`package.json says ${pkg.version}, you asked for ${version}.`);
	}

	const changelog = fs.readFileSync(path.join(ROOT, 'CHANGELOG.md'), 'utf8');
	const newest = (changelog.match(/^## (?!Unreleased$)(.+)$/m) || [])[1];
	if (!newest || !newest.startsWith(version)) {
		fail(
			`CHANGELOG.md's newest release is "${
				newest || 'none'
			}", not ${version}. Did release:prepare run?`
		);
	}

	const notesPath = path.join(ROOT, 'RELEASE_NOTES.md');
	if (!fs.existsSync(notesPath)) {
		fail('No RELEASE_NOTES.md. Run release:prepare first.');
	}
	const firstLine = fs.readFileSync(notesPath, 'utf8').split('\n')[0].trim();
	if (!firstLine.startsWith(`## ${version}`)) {
		fail(`RELEASE_NOTES.md opens with "${firstLine}", not ## ${version}.`);
	}

	const disagreements = versionSightings().filter((s) => s.says !== version);
	if (disagreements.length) {
		fail(
			`You asked for ${version}, and these do not say it:\n` +
				disagreements
					.map(
						(s) =>
							`    ${s.file} (${s.what}) says ${
								s.says || 'nothing'
							}`
					)
					.join('\n') +
				'\n  Bump them and commit before publishing.'
		);
	}

	// --- the repository has to be in a publishable state --------------------
	if (sh('git status --porcelain') !== '') {
		fail('Working tree is not clean. Commit the release first.');
	}
	const branch = sh('git rev-parse --abbrev-ref HEAD');
	if (branch !== 'main') {
		fail(`On branch ${branch}. Releases are cut from main.`);
	}
	assertNothingRidesAlong();
	// --- the zip has to be the one built from this commit -------------------
	const zip = findZip();
	const zipTime = Math.floor(
		fs.statSync(path.join(ROOT, zip)).mtimeMs / 1000
	);
	const commitTime = Number(sh('git log -1 --format=%ct'));
	if (zipTime < commitTime) {
		fail(
			`${zip} is older than the commit being tagged. Rebuild it, or ` +
				'the release will carry an artifact nobody shipped.'
		);
	}

	const tag = `v${version}`;
	if (sh(`git tag --list ${tag}`) !== '') {
		fail(`${tag} already exists locally.`);
	}
	if (sh(`git ls-remote --tags origin ${tag}`) !== '') {
		fail(`${tag} already exists on origin.`);
	}

	try {
		sh('gh auth status');
	} catch {
		fail('gh is not installed or not logged in.');
	}

	// --- publish ------------------------------------------------------------
	const head = sh('git log -1 --format=%h');
	console.log(
		`\nPublishing ${tag} from ${head} with ${zip}${
			dryRun ? ' (dry run — nothing will run)' : ''
		}:`
	);
	run(
		`git tag -a ${tag} -m ${JSON.stringify(`${pkg.name} ${version}`)}`,
		dryRun
	);
	run('git push', dryRun);
	run(`git push origin ${tag}`, dryRun);
	run(
		`gh release create ${tag} ${zip} --title ${JSON.stringify(
			version
		)} --notes-file RELEASE_NOTES.md`,
		dryRun
	);

	console.log(
		`\n${tag} published. Repeat in the sibling repos — releases are ` +
			'lockstep (same version, same day).\n' +
			'Then publish the update manifest, or no installed site hears about ' +
			'this release:\n  marketing/publish-updates.sh'
	);
}

main();
