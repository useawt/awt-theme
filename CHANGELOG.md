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

- Footer links are now easier to tap: each link is at least 32px tall with
  an 8px gap between rows. Before, the links stacked with no spacing at all,
  so on touch screens it was easy to hit the wrong one — below the WCAG
  2.5.8 minimum target size, and flagged by Lighthouse. The links look the
  same, they just breathe.

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

- Footer section headings now get the theme's default heading gap: 16px
  (spacing-05) between the heading and its links. Before, the heading sat
  flush on the first link. Their weight is also pinned (regular, 400) so
  the Site Editor shows the same heading the published footer renders.

- AWT Settings → Navigation has a new "Edit the footer" button under the
  existing "Edit the header" one. It opens the footer in the Site Editor,
  where the footer's links and text are edited.

- The core List block renders as a proper list again: bullets (numbers for
  ordered lists), indentation, and the same line height as body text.
  Carbon's CSS reset removes native list styling because Carbon components
  bring their own; the plain List block isn't a Carbon component, so its
  lists rendered flat — no markers, no indent, cramped lines. Nested lists
  get the standard circle and square markers, and ordered-list settings
  (start value, reversed) keep working. The AWT List block's Carbon look
  (en-dash markers) is unchanged.
- Links typed into content now show the same color in the editor as on the
  published page. The theme's link color now follows Carbon's link token, so
  a link inside a dark section correctly previews in the light blue that the
  published page already used, instead of a dark blue that was hard to read
  on the dark background. Site-wide token overrides (for example a brand
  link color set through AWT Settings → Custom CSS) now reach the editor
  preview too.
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
