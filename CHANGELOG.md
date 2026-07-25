# Changelog

<!-- Authoring format (parsed by scripts/release.js at release time — see the
     Stage 1 spec, "Changelog communication"):

     ## <version> — <YYYY-MM-DD>
     ### [Severity]        one of: [Security] [A11y] [Breaking] [New] [Improvement]
     - One entry per bullet.

     markdownlint enforces the structure in CI. Newest release first.
     The Unreleased section accumulates entries between releases. -->

## Unreleased

### [A11y]

- Paragraphs placed directly in a Section now keep a readable line length:
  they are capped at the reading measure (48rem, about 90 characters),
  matching how the Carbon Design System site sets its own body text. Very
  long lines are a barrier for low-vision and dyslexic readers (WCAG 1.4.8
  recommends 80 characters or less). Centered and right-aligned paragraphs
  keep their alignment. Text inside columns, tiles, and other blocks is not
  affected — it is already sized by its container. To let one paragraph span
  the full section, place it in a Group; to line a whole section up with the
  text column, use the Section's new "Reading (48rem)" max width.

### [Improvement]

- Blocks set to "Align center" inside a Section now actually center, both on
  the page and in the editor. WordPress only centers such blocks inside its
  own layout containers, so a centered, resized image in a Section used to
  stick to the left edge.
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
