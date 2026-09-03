/**
 * Release publication (Stage 1 spec, "Every released version gets a tag and
 * a GitHub Release"). Run it after `release:prepare` and its commit:
 *
 *   npm run release:publish 2026.09.0
 *
 * and the script:
 *   1. Checks the version against package.json, CHANGELOG.md and
 *      RELEASE_NOTES.md, and refuses if they disagree.
 *   2. Checks the working tree is clean, the branch is main, and no
 *      v<version> tag exists yet.
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
 * single *.zip sits in the repo root.
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

	// --- the repository has to be in a publishable state --------------------
	if (sh('git status --porcelain') !== '') {
		fail('Working tree is not clean. Commit the release first.');
	}
	const branch = sh('git rev-parse --abbrev-ref HEAD');
	if (branch !== 'main') {
		fail(`On branch ${branch}. Releases are cut from main.`);
	}
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
