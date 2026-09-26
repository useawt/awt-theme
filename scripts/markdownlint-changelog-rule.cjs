/**
 * Custom markdownlint rule: CHANGELOG.md follows the parseable authoring
 * format the release script consumes (Stage 1 spec, "CHANGELOG.md
 * authoring format"):
 *
 *   ## Unreleased            or  ## <YYYY.MM.PATCH> — <YYYY-MM-DD>
 *   ### [Severity]           one of the five documented tags
 *
 * Applied only to CHANGELOG.md (see .markdownlint-cli2.jsonc overrides).
 */

const RELEASE_RE =
	/^(Unreleased|\d{4}\.\d{2}\.\d+(-[a-z0-9.]+)?( — \d{4}-\d{2}-\d{2}| \(\d{4}-\d{2}-\d{2}\)))$/;
const SEVERITY_RE = /^\[(Security|A11y|Breaking|New|Improvement)\]$/;

module.exports = {
	names: ['AWT001', 'awt-changelog-structure'],
	description:
		'CHANGELOG release/severity headings follow the release-script format',
	tags: ['awt'],
	parser: 'markdownit',
	function: (params, onError) => {
		if (!/CHANGELOG\.md$/.test(params.name)) {
			return;
		}
		const tokens = params.parsers.markdownit.tokens;
		for (let i = 0; i < tokens.length; i++) {
			const t = tokens[i];
			if (t.type !== 'heading_open') {
				continue;
			}
			const text = tokens[i + 1].content.trim();
			if (t.tag === 'h2' && !RELEASE_RE.test(text)) {
				onError({
					lineNumber: t.lineNumber,
					detail: `"## ${text}" — expected "## Unreleased" or "## <YYYY.MM.PATCH> (<YYYY-MM-DD>)".`,
				});
			}
			if (t.tag === 'h3' && !SEVERITY_RE.test(text)) {
				onError({
					lineNumber: t.lineNumber,
					detail: `"### ${text}" — expected one of [Security] [A11y] [Breaking] [New] [Improvement].`,
				});
			}
		}
	},
};
