# Changelog

<!-- Authoring format (parsed by scripts/release.js at release time — see the
     Stage 1 spec, "Changelog communication"):

     ## <version> — <YYYY-MM-DD>
     ### [Severity]        one of: [Security] [A11y] [Breaking] [New] [Improvement]
     - One entry per bullet.

     markdownlint enforces the structure in CI. Newest release first.
     The Unreleased section accumulates entries between releases. -->

## Unreleased

### [Breaking]

- Links are underlined. Carbon leaves a link without an underline and brings it
  back only when you point at it, which asks colour to do the whole job of
  saying "this is a link". Colour may only do that job on its own when the link
  is clearly different from the text around it, and Carbon's blue is not: 3.62
  to 1 against body text in light mode, and 2.14 to 1 in dark mode, where the
  bar is 3 to 1. Any link on a dark band inside a light page measures the same
  2.14 to 1, and a brand colour a shade off Carbon's blue takes the light
  figure under the bar too. **AWT Settings → Carbon → Links** has a switch for
  each place a link appears — main content, header, side navigation,
  breadcrumbs, footer — with one switch above them that turns the whole thing
  off and gives you Carbon's link style back. All of them start on. Buttons,
  pagination numbers, tags, cards and the header's icon controls are never
  underlined, and plain links you type into your text are left exactly as your
  browser draws them. (Breaking because every AWT site's links change
  appearance on upgrade. If your own CSS sets link decoration, check it against
  the `--awt-underline` rules in `assets/css/theme.css`.)

- A style rule that positions the header in the block editor works again. It had
  been switched off without anyone noticing: a comment above it was closed one
  line too early, which left the lines after it as invalid CSS, and browsers
  respond to that by throwing away the next rule. The source looked correct, so
  nothing pointed at it. `npm run check:css` now fails if any stylesheet has that
  shape, and it runs on every change.

- Form fields are drawn with a border on all four sides instead of a single line
  under the text, so a field's shape no longer depends on its fill being
  slightly different from the page. Applies to Text input, Text area, Password
  input, Select and Dropdown; each of those blocks has a **Carbon default**
  setting that puts an individual field back to the one-line look. The border
  reuses the color the line already used, so it keeps 3 to 1 contrast in light
  and dark. If your own CSS restyles these fields' borders, check it against the
  `.awt-field--framed` rules in `assets/css/theme.css`.

- Read-only fields are drawn with the same border color as editable ones, in
  place of the much paler color they used to have. A read-only field has no
  shaded fill, so its border is the only thing showing you where the field is,
  and that border was far too faint to see: 1.32 to 1 against the page in light
  mode. It is now 3.32 to 1, the contrast a control needs. Read-only fields are
  still easy to tell apart, because what marks them is having no fill rather
  than having a paler edge: an editable field is a filled box, a read-only one
  is an outline, and a disabled one is a fill with no edge at all. A field with
  **Carbon default** on is unaffected.

- theme.json no longer lists header, side navigation, content and footer
  options under `settings.custom.ui-shell`. Nothing read them, so changing
  them changed nothing, and leaving them there implied a configurable header
  height and position that does not exist. The two live keys, `colorScheme`
  and `themeScope`, stay. (Breaking because the matching
  `--wp--custom--ui-shell--*` CSS variables are no longer generated; custom
  CSS that referenced them should use its own values.)

### [A11y]

- Every focus indicator in the theme is now at least 2 pixels thick, and at
  least 2 pixels of it are visible against what is behind it. The Carbon Design
  System draws its focus rings 1 pixel thick, so this affected almost every
  control on a page: links, buttons, form fields, the tag dismiss button and the
  expandable tile's summary. Thicker is the visible part of the change. Three
  cases were worse than thin, and those are the reason for it:

  - The close button on a notification drew a blue ring on a dark grey
    notification, at 1.8 to 1. One of our own style rules was overriding
    Carbon's correct colour choice for that surface. The rule is gone.
  - The tag dismiss button drew a white ring on a light blue tag in dark mode,
    at 1.2 to 1. It now uses the tag's own text colour, which the tag palette
    already pairs with each fill.
  - The selected button in a Content switcher drew a white ring on a light
    surface in dark mode, at 1.1 to 1. Its two-tone ring is now wide enough on
    both tones for one of them to show.

  A plain link typed into a paragraph used to fall back to whatever ring the
  browser draws. It now gets the theme's, so focus looks the same everywhere.
  Form fields draw their ring just outside the field instead of just inside:
  inside, it competed for the same pixels as the field border and, on a field
  with an error, as the red outline. There is no setting that returns Carbon's
  1 pixel look. If your own CSS restyles focus indicators, check it against the
  "Focus appearance" section at the end of `assets/css/theme.css`.

- Coloured tags now change with the page. A red or blue tag used to keep its
  light pill on a dark page, because its colours were written into the theme by
  hand with no dark version. They come from the Carbon Design System's own tag
  colours now, which are defined for light and dark, so a dark page gets a
  strong fill with light text on it. Two things follow from that:

  - The text inside a tag is a slightly lighter shade in light mode than it was.
    The old shades were an older version of Carbon's palette. Every colour still
    passes WCAG AA comfortably, from 5.8 to 1 at the lowest.
  - Tags follow your own colours. If your site overrides Carbon's tag colours,
    tags now use them instead of ignoring them.

  The dismiss button on a tag set to the **high-contrast** colour was still
  drawing a 1 pixel ring when a keyboard user reached it, which was missed when
  every other focus ring was raised to 2 pixels. It is 2 pixels now, like the
  rest.

- Code snippets that scroll sideways now show the theme's focus ring when a
  keyboard user tabs to them, instead of the browser's own default outline. This
  covers both the single-line and multi-line kinds, each of which scrolls a
  different part of the snippet.

- A data table that is wider than the space it has now shows a focus ring when
  a keyboard user tabs to it. The table scrolls sideways in that case, and the
  blocks plugin now makes the scrolling area reachable by keyboard so the
  columns past the right edge can be read without a mouse. Reaching it is only
  useful if you can see where you are, so it uses the same focus ring as every
  other focusable part of the theme, in light and dark, plus a dotted variant
  when the reader has asked for more contrast.

- The "Segmented" color scheme toggle (Light / Auto / Dark) shows which option
  is in use. The one in use is filled, bold and underlined, so the state does
  not depend on color alone, and it reads correctly in both light and dark.

- "Skip to main content" now reaches the content. Automatic breadcrumbs used
  to sit inside the main content area, so the skip link dropped you at the top
  of the trail and you had to tab through every crumb before reaching the page
  itself — the one thing the link exists to avoid. The trail now sits just
  above the main content instead of inside it, which is also where repeated
  navigation belongs. Screen readers announce the trail as "Breadcrumbs"
  rather than "Breadcrumb".

  Your pages look the same afterwards, with one intended exception: on the
  "page without title" template the trail used to sit flush against the
  header, and now it has the same breathing room it has everywhere else.

- The Documentation article pattern no longer inserts a placeholder breadcrumb
  ("Home / Docs / Current page") into your page. Automatic breadcrumbs cover
  that page, with the real trail rather than placeholder text — and they sit in
  the right place. Pages you already built keep the breadcrumb they have.

- Password field: the show/hide-password button is now as tall as the field
  it sits in. It had shrunk to the height of its eye icon — 40 by 16 pixels,
  under the 24 by 24 minimum a target needs — which made it easy to miss with
  a finger or an imprecise mouse, and gave it a focus outline that hugged the
  icon instead of the button. The icon stays exactly where it was.

- Every page now has one header landmark and one footer landmark instead of
  two of each, nested inside one another. Screen reader users navigating by
  landmark heard the site header twice in a row, and the footer twice, with
  nothing to tell the pair apart. Nothing about how the page looks changes.

- A side navigation section with no title now shows its links. Untitled
  sections hid them completely: the links were in the page but drawn at no
  height, and each one still took keyboard focus, so someone tabbing through
  landed on links nobody could see. Titled sections were never affected, which
  is why this went unnoticed — showing the links had been tied to the section
  having a title, two things that have nothing to do with each other.

- The side navigation works on phones and small tablets. It used to open as a
  full-height panel over your content with no way to close it, and its links
  stayed focusable while off-screen, so keyboard users could tab into things
  they could not see. Below 1056px the panel now steps aside — there is no room
  for it — and its links move into the header menu, under the site's main menu
  items and separated from them by a rule, so nothing becomes unreachable. On a
  documentation site those links are the documentation. The rule uses the design
  system's border token, so it is correct in both light and dark mode, and the
  links keep their own name for screen readers ("Side navigation") instead of
  being folded into the main menu's.

- Your site no longer flashes the wrong theme before it settles. If your
  device is set to dark mode and you have not used the light/dark toggle
  yet, the page used to paint light first and switch to dark a moment
  later. On a slow connection that flash was clearly visible. Dark now
  applies before anything is drawn.

- A dismissed Notification block now fully disappears: an explicit
  `[hidden]` rule makes sure the notification's flex layout can't keep it
  visible after its close button (wired in awt-blocks) hides it.

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

- The "Skip to main content" link now looks the way the design system draws
  it. When you tabbed to it, it showed up as a squat box wearing two focus
  rings at once, about half the height it should be: an older theme rule and
  the design system's own rule each won part of the styling. It is now a
  single panel the full height of the header, with one focus border, in both
  light and dark mode.

### [Improvement]

- Breadcrumbs on a month or day archive now name the month you are looking at.
  They named the current month instead, so an archive of posts from March,
  visited in August, said "August" — the breadcrumb was reading the clock
  rather than the archive. The year and the day beside it were always correct.
  Archives from the current month looked right either way, which is why this
  went unnoticed.

- The Documentation header preset now gives you the side navigation its
  description promises. Picking it adds a side navigation down the left of
  every page on wide screens; edit its links in the Site Editor under
  Patterns → Template Parts → Side navigation. Before, the preset described a
  side navigation and none ever appeared, because nothing on the site
  referenced that template part.

- Where a side navigation is present, the page content and the footer both sit
  clear of it. The footer used to run the full width of the window, so the text
  at its left edge was hidden behind the side navigation. The side navigation
  also starts below the header now instead of on top of it, so it no longer
  covers your logo or site title.

- Section titles in the side navigation now look like titles: bold, sized to
  match the links, and lined up with them. They used to sit flush against the
  left edge in plain body text.

- AWT Settings → Appearance → Header now tells you what the header's size and
  position are — a bar 48 pixels tall, fixed to the top of the screen — and
  that they come from the Carbon Design System layout, so there is no setting
  to change them. Before, an author looking for a height or position control
  found nothing and no explanation.

- A logo you upload now shows up on its own. Adding a logo during setup, or
  on AWT Settings → Identity, saved it but left your header showing only the
  site title, because showing a logo needed a second setting on another tab.
  Brand mode now starts on "Automatic", which shows the logo and prefix you
  have set. Pick any other option on AWT Settings → Appearance → Header to
  always show the same thing.

- Footer section headings now get the theme's default heading gap: 16px
  (spacing-05) between the heading and its links. Before, the heading sat
  flush on the first link. Their weight is also pinned (regular, 400) so
  the Site Editor shows the same heading the published footer renders.

- AWT Settings → Navigation has a new "Edit the footer" button under the
  existing "Edit the header" one. It opens the footer in the Site Editor,
  where the footer's links and text are edited.

- The side navigation can now be edited. It is a normal block inside the
  header: it shows up in List View, you can click it, and it has its own
  settings. Until now the Documentation preset pulled it in from a separate
  "Side navigation" template part, and WordPress does not let you edit one
  template part from inside another — so the side nav appeared in the header
  but could not be selected or changed at all. Its links live in the header
  template part now, and the separate part is gone.

- AWT Settings → Navigation also has an "Edit the side navigation" button
  beside the header and footer ones. It opens the header, where the side nav
  is, so you do not have to know that is where to look.

- The side navigation now previews in the Site Editor the way it renders. When
  you edit the header, it appears below the header bar at its real width
  instead of squeezed into a small box at the right-hand end of the bar.

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
