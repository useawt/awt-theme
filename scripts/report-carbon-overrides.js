/**
 * Report every rule in theme.css whose selector Carbon ALSO declares — in the
 * foundation or in a block's compiled style-index.css — and say whether the
 * two agree on each shared property.
 *
 * Why this exists: theme.css and Carbon's CSS have the same specificity for
 * these selectors, so the cascade decides property by property on source
 * order. A theme rule written before Carbon's own rule shipped therefore does
 * not simply get replaced — the two split the declarations between them, and
 * the result is a component neither side drew. That is what happened to the
 * skip link (2026-08-01): a 24px-tall panel wearing both Carbon's 4px focus
 * border and our 2px outline.
 *
 * This is a REPORT, not a gate. Most collisions listed are legitimate — AWT
 * markup is not Carbon's React markup, so plenty of components genuinely need
 * a different rule. Read it when touching theme.css or after a Carbon bump,
 * and check that every entry still has a reason.
 *
 *   node scripts/report-carbon-overrides.js
 *
 * Two things the output cannot tell you, both learned the hard way:
 *   - Identical declaration text does NOT mean identical computed value.
 *     Deleting theme.css's `.cds--inline-notification--low-contrast { color }`
 *     — byte-identical to Carbon's — dropped that notification to 1.2:1,
 *     because theme.css's own generic notification rule then won on source
 *     order. Measure in the browser before and after, always.
 *   - A rule with no comment is not automatically stale. Check git and the
 *     block's render.php before removing anything.
 */
const fs = require('fs');
const path = require('path');

const THEME = path.join(__dirname, '..');
// The blocks plugin is a sibling checkout; its compiled per-block CSS is where
// most of Carbon's component rules live. Without it this only sees the
// foundation, so say so rather than reporting a misleadingly short list.
const BLOCKS = path.join(THEME, '..', 'awt-blocks', 'build');

function parse(css) {
	const out = [];
	const stack = [];
	let i = 0,
		buf = '';
	css = css.replace(/\/\*[\s\S]*?\*\//g, (m) => m.replace(/[^\n]/g, ' '));
	while (i < css.length) {
		const ch = css[i];
		if (ch === '{') {
			const head = buf.trim();
			buf = '';
			if (head.startsWith('@')) {
				stack.push(head);
				i++;
				continue;
			}
			// style rule: read to matching close
			let depth = 1,
				j = i + 1;
			while (j < css.length && depth) {
				if (css[j] === '{') {
					depth++;
				} else if (css[j] === '}') {
					depth--;
				}
				j++;
			}
			const body = css.slice(i + 1, j - 1);
			const decls = {};
			for (const d of body.split(';')) {
				const k = d.indexOf(':');
				if (k < 0 || d.includes('{')) {
					continue;
				}
				decls[d.slice(0, k).trim()] = d
					.slice(k + 1)
					.trim()
					.replace(/\s+/g, ' ');
			}
			const line = css.slice(0, i).split('\n').length;
			const ctx = stack.join(' && ');
			for (const s of head
				.split(',')
				.map((x) => x.trim().replace(/\s+/g, ' '))) {
				if (s) {
					out.push({ sel: s, ctx, decls, line });
				}
			}
			i = j;
			continue;
		}
		if (ch === '}') {
			stack.pop();
			buf = '';
			i++;
			continue;
		}
		buf += ch;
		i++;
	}
	return out;
}

const themeRules = parse(
	fs.readFileSync(path.join(THEME, 'assets/css/theme.css'), 'utf8')
);
const carbon = new Map(); // `${ctx}|${sel}` -> {decls, sources}
function addCarbon(file, label) {
	for (const r of parse(fs.readFileSync(file, 'utf8'))) {
		const key = `${r.ctx}|${r.sel}`;
		if (!carbon.has(key)) {
			carbon.set(key, { decls: {}, sources: new Set() });
		}
		const e = carbon.get(key);
		e.sources.add(label);
		Object.assign(e.decls, r.decls);
	}
}
addCarbon(path.join(THEME, 'assets/css/foundation.min.css'), 'foundation');
if (fs.existsSync(BLOCKS)) {
	for (const dir of fs.readdirSync(BLOCKS)) {
		const f = path.join(BLOCKS, dir, 'style-index.css');
		if (fs.existsSync(f)) {
			addCarbon(f, dir);
		}
	}
} else {
	console.log(
		`NOTE: ${BLOCKS} not found — comparing against the foundation only.\n` +
			'Build awt-blocks first for the full picture.\n'
	);
}

const norm = (v) =>
	v
		.replace(/\s*,\s*/g, ',')
		.replace(/\b0px\b/g, '0')
		.replace(/(^|\s)\.(\d)/g, '$10.$2')
		.toLowerCase();

const rows = [];
for (const r of themeRules) {
	const c = carbon.get(`${r.ctx}|${r.sel}`);
	if (!c) {
		continue;
	}
	const diff = [],
		same = [];
	for (const [p, v] of Object.entries(r.decls)) {
		if (!(p in c.decls)) {
			continue;
		}
		(norm(v) === norm(c.decls[p]) ? same : diff).push([p, v, c.decls[p]]);
	}
	if (diff.length || same.length) {
		rows.push({ ...r, src: [...c.sources].join(','), diff, same });
	}
}

rows.sort((a, b) => b.diff.length - a.diff.length);
console.log(
	`theme.css rules colliding with a Carbon rule in the SAME media context: ${rows.length}\n`
);
console.log("--- theme changes Carbon's value ---");
for (const r of rows.filter((x) => x.diff.length)) {
	console.log(
		`\ntheme.css:${r.line}  ${r.sel}${r.ctx ? '   @' + r.ctx : ''}   [carbon: ${r.src}]`
	);
	r.diff.forEach(([p, a, b]) =>
		console.log(`   ${p}: ${a}   <- carbon: ${b}`)
	);
	if (r.same.length) {
		console.log(`   (+${r.same.length} properties repeated verbatim)`);
	}
}
const dead = rows.filter((x) => !x.diff.length);
console.log(
	`\n\n--- pure duplication, no behaviour change (${dead.length} rules) ---`
);
dead.forEach((r) =>
	console.log(
		`   theme.css:${r.line}  ${r.sel}  [${r.src}]  ${r.same.length} props`
	)
);
