# Changelog

<!-- Authoring format (parsed by scripts/release.js at release time — see the
     Stage 1 spec, "Changelog communication"):

     ## <version> — <YYYY-MM-DD>
     ### [Severity]        one of: [Security] [A11y] [Breaking] [New] [Improvement]
     - One entry per bullet.

     markdownlint enforces the structure in CI. Newest release first.
     The Unreleased section accumulates entries between releases. -->

## 2026.09.34 — 2026-09-23

### [Improvement]

- Blog posts now use the same content width as other pages.
- Featured images keep their own shape. They are no longer cropped to fit a
  fixed box.
- The pagination under a list of posts is now centred.
- A post's categories now show as tags.

## 2026.09.33 — 2026-09-23

### [Improvement]

- AWT's stylesheets and scripts are now compressed, so every page loads less
  code.
- AWT no longer loads WordPress's emoji script on the front end.
- The page head no longer carries the WordPress version number or the pointer
  to `xmlrpc.php`.
- Pasting a link to one of your pages into another WordPress site no longer
  turns it into a preview card. Add the `awt_oembed_discovery` filter to put it
  back.
- A site with comments switched off no longer advertises a comments feed.

## 2026.09.32 — 2026-09-22

### [Improvement]

- AWT no longer installs updates by itself on a site that is many releases
  behind. Those sites need to be updated manually.
- The AWT menu in the toolbar is now more consistent in states of automatic
  updates.
- Automatic updates now handle sites that installed AWT in a non-standard
  folder structure.
- AWT only installs update packages published on AWT's own GitHub releases.

## 2026.09.31 — 2026-09-21

### [Improvement]

- If AWT is installed in a folder with a different name than the one updates
  install into, it no longer tries to update itself, and tells you how to
  update by hand instead.

## 2026.09.30 — 2026-09-21

### [New]

- AWT now keeps itself up to date. New versions install three days after they
  are released, and the theme and the plugin always move together. A version
  that comes with changes that could affect your site is never installed for
  you — AWT tells you instead.
- Settings, Tools: choose how updates work. Keep AWT up to date
  automatically, be notified about new versions, or disable updates.
- Settings, Tools: A new 'Check for updates' button.
- A box on your dashboard showing which version you are on and what changed
  in the last two releases.
- A line at the top of the admin saying where your site stands: up to date,
  an update waiting for you, the theme and plugin on different versions, or
  updates turned off.
- The AWT menu in the toolbar now says whether an update is coming on its own
  or needs you to install it.

### [Improvement]

- After an update, your site keeps its own skip link text and per-page
  language straight away, instead of showing the defaults until someone next
  visits the admin pages.
- If an automatic update fails, the email explains what happened.
- What's new now shows the parts of your site you have edited, and says that
  AWT's changes to those parts do not reach them. Applies to header and
  footer.
- Accessibility fixes stay pinned in What's new until you dismiss them, the
  same as security fixes.

## 2026.09.29 — 2026-09-20

### [A11y]

- A button in the header shows its focus ring again. In 2026.09.28 the ring
  was drawn in the same colour as the button underneath it, so keyboard users
  could not see where they were.
- The design systems that are not available yet are no longer dimmed, so
  every line on their cards is readable.

### [Improvement]

- Settings → Design system: asking for a design system that is not listed is
  now a link to the contact page instead of an email address to copy out.

## 2026.09.28 — 2026-09-20

### [Improvement]

- Sub-lists are indented by the same amount as the design system they follow.
  They were indented half as far again, and a list marked as a sub-list on its
  own was barely indented at all.
- Header buttons mark focus in the same colour as every other control on the
  site.

## 2026.09.27 — 2026-09-20

### [Improvement]

- No changes in the theme itself. It carries the same version number as AWT
  Blocks, which has changes in this release.

## 2026.09.26 — 2026-09-20

### [New]

- Settings → Design system lists the design systems AWT will support. Carbon
  is the one you can pick today; the rest are marked as coming to AWT Premium.

## 2026.09.25 — 2026-09-20

### [Improvement]

- No changes in the theme itself. It carries the same version number as AWT
  Blocks, which has changes in this release.

## 2026.09.24 — 2026-09-20

### [Improvement]

- The footer credit reads "Built with AWT, an accessible WordPress theme."

## 2026.09.23 — 2026-09-20

### [Improvement]

- The automatic breadcrumb lines up with the page on templates whose main area
  has no side padding of its own, including the theme's own "Page without
  title". The trail sat against the edge of the screen on any window narrower
  than the content width.

## 2026.09.22 — 2026-09-19

### [A11y]

- The editor shows the colour scheme you chose on the site. If you had set the
  site to light while your computer was set to dark, you authored in dark
  against a light site — two settings, and the editor followed the one that was
  not about the site.

## 2026.09.21 — 2026-09-19

### [New]

- A warning when the AWT Blocks plugin is missing or turned off. Without it the
  theme's blocks do not appear on your pages or in the editor, and nothing said
  so. The warning offers a button to turn the plugin on, or the file to
  download when it is not installed.

## 2026.09.20 — 2026-09-19

### [Improvement]

- The header stays under the WordPress admin bar on a phone. Below 600px the
  admin bar scrolls away with the page, and the header was left floating below
  the top of the screen with content showing through the gap. Only logged-in
  viewers ever saw this.

## 2026.09.19 — 2026-09-19

### [A11y]

- Every header control stays on a narrow screen. With a logo in the header, the
  last icon on the right sat off the side of a 320px screen with no way to
  reach it; the logo now scales down instead.
- A button with a long label stays inside the column it is in. It could stick
  out past the edge of a phone screen and take the page's sideways scrolling
  with it.

## 2026.09.18 — 2026-09-19

### [Improvement]

- The open vertical tab joins its panel. It kept a line down its right side, so
  it read as a box beside the panel rather than the open part of it.

## 2026.09.17 — 2026-09-19

### [A11y]

- Vertical tabs become a row of tabs on a narrow screen, and on a wide one the
  tab list takes a quarter of the width instead of a fixed size. They kept a
  fixed sidebar at every screen size, so on a phone the panel ran off the side
  of the screen and the page scrolled sideways.

## 2026.09.16 — 2026-09-18

### [A11y]

- The editor shows your own colour scheme again. On a dark desktop a site that
  follows the visitor's setting showed the page's light backgrounds behind
  dark-mode text, which left the content nearly unreadable while editing.

## 2026.09.15 — 2026-09-18

### [Improvement]

- No changes in the theme itself. It carries the same version number as AWT
  Blocks, which has changes in this release.

## 2026.09.14 — 2026-09-18

### [Improvement]

- No changes in the theme itself. It carries the same version number as AWT
  Blocks, which has changes in this release.

## 2026.09.13 — 2026-09-18

### [Improvement]

- No changes in the theme itself. It carries the same version number as AWT
  Blocks, which has changes in this release.

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
