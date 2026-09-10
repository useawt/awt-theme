# Changelog

<!-- Authoring format (parsed by scripts/release.js at release time — see the
     Stage 1 spec, "Changelog communication"):

     ## <version> — <YYYY-MM-DD>
     ### [Severity]        one of: [Security] [A11y] [Breaking] [New] [Improvement]
     - One entry per bullet.

     markdownlint enforces the structure in CI. Newest release first.
     The Unreleased section accumulates entries between releases. -->

## 2026.09.12 — 2026-09-10

### [Improvement]

- No changes in the theme itself. It carries the same version number as AWT
  Blocks, which has changes in this release.

## 2026.09.11 — 2026-09-10

### [Improvement]

- What's new now lists the theme's release notes as well as the plugin's. It
  showed only the plugin's, so a release that changed the theme looked empty.

## 2026.09.10 — 2026-09-10

### [A11y]

- On screens under 672px a header action shows its icon only. Two labelled
  actions did not fit beside the logo and the menu button, and the last one —
  usually the light/dark toggle — was pushed off the edge of the screen.

## 2026.09.9 — 2026-09-10

### [A11y]

- A header action set to "icon with label" is now as wide as its label. The
  label ran outside the button and over the one next to it.

## 2026.09.8 — 2026-09-09

### [New]

- Styling for the Testimonial block's new source link.

## 2026.09.7 — 2026-09-09

### [Improvement]

- No changes in the theme itself. It carries the same version number as AWT
  Blocks, which has changes in this release.

## 2026.09.6 — 2026-09-09

### [Improvement]

- Breadcrumbs sit the same distance below the header whether or not you are
  signed in. With the admin bar showing they sat lower than the rest of the
  page.

## 2026.09.5 — 2026-09-09

### [Improvement]

- No changes in the theme itself. It carries the same version number as AWT
  Blocks, which has changes in this release.

## 2026.09.4 — 2026-09-09

### [New]

- An AWT menu in the toolbar, on the front end and in wp-admin: a dot that is
  green when your site is up to date and red when a new version is out, and
  shortcuts to the header, the footer and AWT settings.

### [Improvement]

- The size you pick for a Modal opener or a Menu button now changes the
  button. Both stayed at the largest size whatever you chose.

## 2026.09.3 — 2026-09-08

### [New]

- New setting in AWT Settings → Appearance → Header: contain the header to your
  content width, so the logo, menu and icons line up with the page below
  instead of sitting at the edges of a wide screen.

### [Improvement]

- A three- or four-column feature grid now shows two columns on medium screens
  instead of holding every column. Four columns on a 700px window left 195px
  tiles, with headings broken over three lines.
- The menu no longer slides in and out while you scroll on a phone or resize
  the window.

## 2026.09.2 — 2026-09-08

### [A11y]

- The header now collapses to the menu button when the navigation does not
  fit, instead of at a fixed screen width. A long menu used to overflow — items
  wrapping and the last button cut off by the edge of the screen — while a short
  one was hidden behind the menu button with room to spare.

### [Improvement]

- The text size setting in AWT Settings → Typography now changes the site.
  Compact, Default and Comfortable were being saved and shown as chosen, but
  nothing on the page ever changed.
- A Section set wider than the content width now gets that width, on the page
  and in the editor. Picking "Wide" on a section in a page's content changed
  nothing, because the page layout was holding it to the content width.

## 2026.09.1 — 2026-09-08

### [Improvement]

- Blog listings and single posts now show the post's featured image, with the
  image beside the text rather than above it.
- A post's categories appear under its date.
- Post text is held to a comfortable reading width instead of running the full
  page.
- The footer no longer leaves a thin strip of page colour underneath it.
- Form fields, checkboxes, radios and buttons that no block rendered — a
  password form, a custom HTML block, a plugin's form — now match the rest of
  the site instead of showing plain browser widgets.
- Saving AWT Settings without changing anything no longer reports an error.

### [A11y]

- Text marked bold with `<b>` now looks bold. It was rendering at the same
  weight as the text around it, so the emphasis was lost.
- A field the browser fills in for you keeps the site's colours. It used to
  turn pale yellow, which was unreadable in dark mode.

## 2026.09.0 — 2026-09-03

### [Improvement]

- Help text across the AWT Settings screens and the welcome wizard is shorter.

### [New]

- WordPress now tells you when a new version of AWT is out, on Dashboard,
  Updates. You still install it yourself.
- A switch in AWT Settings, Tools turns the update check off.

## 2026.08.0 — 2026-08-25

The first public release of AWT.

### [New]

- **First release.** AWT is a WordPress block theme built on IBM's Carbon
  Design System, designed to be accessible before you change anything.
- Eight page templates, plus header, footer and side navigation parts you edit
  in the Site Editor.
- Style variations in matched light and dark pairs, and a light/dark toggle for
  your visitors. The page arrives in the right mode with no flash of the wrong
  one.
- Block patterns for the pages most sites need: heroes, feature grids,
  statistics, FAQs, forms and documentation pages.
- An AWT Settings screen under Appearance, with a short setup wizard for your
  logo, colours and header style.
- Automatic breadcrumbs, with a switch to hide them on any page.
- Licensed GPLv3 or later. That matters only if you redistribute AWT or build
  on its code — using it on your site is unaffected.

### [A11y]

- AWT departs from stock Carbon in a few places on purpose. Each one has a
  switch if you prefer Carbon's original.
- Links are underlined. Carbon's link blue is too close to body text for colour
  alone to mark a link — 2.14 to 1 in dark mode, where the bar is 3 to 1.
  **Settings → Carbon → Links** has a switch for each region of the page.
- Every focus indicator is at least 2 pixels thick and stands out against
  whatever is behind it. Carbon draws 1 pixel.
- A focused button gets one 2-pixel outline instead of Carbon's three
  overlapping rings, so an auditor can measure it. **Settings → Carbon → Focus**
  gives Carbon's look back.
- Form fields have a border on all four sides instead of a single line under
  the text, so a field's shape does not depend on its fill. Each field block has
  a **Carbon default** switch.
- Form labels, hints and error messages are the same size as the text you type
  into the field. Carbon sets all three a step smaller. **Settings → Carbon →
  Typography** turns this off.
- The side navigation works on a phone. Below 1056px it steps aside and its
  links move into the header menu, so nothing becomes unreachable.
- "Skip to main content" lands on the content. The breadcrumb trail sits above
  the main area rather than inside it, so the link skips it too.
- Each page has one header landmark and one footer landmark, so screen reader
  users do not hear each one twice.
- Coloured tags follow the page into dark mode, and footer links are big enough
  to tap.
