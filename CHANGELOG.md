# Changelog

<!-- Authoring format (parsed by scripts/release.js at release time — see the
     Stage 1 spec, "Changelog communication"):

     ## <version> — <YYYY-MM-DD>
     ### [Severity]        one of: [Security] [A11y] [Breaking] [New] [Improvement]
     - One entry per bullet.

     markdownlint enforces the structure in CI. Newest release first.
     The Unreleased section accumulates entries between releases. -->

## Unreleased

### [Improvement]

- The Page template no longer adds its own 32px padding under the content.
  The space before the footer now comes from one place — the last block's
  Spacing setting — instead of two stacked sources. Pages that end in a
  full-width color band can sit flush against the footer with the section's
  "No gap below" switch; on other pages the gap is the last block's spacing
  (16px by default, adjustable per block).
- Spacing tokens below 16px (spacing-01 to spacing-04) now produce the exact
  gap they promise. WordPress adds a 16px layout margin above every block,
  which used to override the smaller tokens — the gap never went below 16px.
- Pattern placeholder copy rewritten in plainer language: "ship" wording and
  em dashes are gone, and the free-product FAQ answer now matches the
  promise that the free theme and plugin are the complete product.
- Automatic breadcrumbs now render through the Breadcrumb block when the AWT
  Blocks plugin is active, so their Carbon styling (including the "/"
  separators) loads on every page — not just pages that already contain a
  Breadcrumb block. The trail no longer shows a separator after the current
  page.

### [New]

- Initial Stage 1 release of the AWT theme: Carbon Design System foundation
  CSS, eight page templates, header/footer/sidebar template parts, block
  patterns, style variations (light + dark scope pairs), the AWT Settings
  screen with welcome wizard, automatic breadcrumbs, and visitor
  color-scheme support.
