=== AWT ===
Contributors: useawt
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.1
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

An accessibility-first block theme built on IBM's open-source Carbon Design System. Made to pair with the AWT Blocks plugin.

== Description ==

AWT is a block theme where accessibility is the starting point, not a checkbox. It's built on IBM's open-source Carbon Design System and reviewed against WCAG 2.2 AA — in both light and dark mode.

* **Four style variations**, each pairing a Carbon light theme with a dark one (White + Gray 90, White + Gray 100, Gray 10 + Gray 90, Gray 10 + Gray 100).
* **A visitor color scheme switch**: visitors choose light, dark, or "follow my system setting", and the choice is applied before the page paints — no flash, and it works with page-caching plugins out of the box.
* **42 ready-made patterns**: page layouts (home, about, pricing, docs, contact, FAQ, legal), sections (hero, feature grid, stats, testimonials, pricing table, team grid, logo cloud, newsletter signup), column layouts, and Carbon component compositions.
* **AWT Settings**: one admin page for your site's identity, design system, navigation, and tools — written in plain language.
* **Fast**: the always-loaded stylesheet is about 15 KB compressed; each block's styles load only on pages that use the block.

Install it together with the AWT Blocks plugin, which adds 58 matching accessible blocks and an accessibility checker inside the editor. The theme works on its own, but the patterns and the design system are built around those blocks.

== Frequently Asked Questions ==

= Does the color scheme switch need a cookie banner? =

No. The visitor's choice is stored in a cookie that only holds a UI preference the visitor set themselves. Under EU rules that is a strictly necessary cookie — no consent banner is required.

= Does it work with caching plugins? =

Yes. One cached copy of a page serves both light and dark visitors correctly; a small script applies the visitor's choice before the page paints. Don't configure your cache to vary by cookie — it isn't needed.

= Where do the blocks come from? =

From the AWT Blocks plugin. Install both for the full experience; the theme's patterns are built from those blocks.

== Copyright ==

AWT WordPress Theme, (C) 2026 AWT.
AWT is distributed under the terms of the GNU GPL v3 or later.
The full text is in license.txt.

AWT is GPLv3-or-later rather than GPLv2-or-later because it bundles Carbon
Design System styles, which are Apache-2.0. Apache 2.0 is compatible with
GPLv3 but not with GPLv2.

== Resources ==

This theme bundles the following third-party resources:

Carbon Design System styles, compiled from @carbon/styles
Copyright IBM Corp. 2016, 2026
License: Apache License 2.0, https://www.apache.org/licenses/LICENSE-2.0
Full text: LICENSE-Apache-2.0.txt
Source: https://github.com/carbon-design-system/carbon

IBM Plex Sans, IBM Plex Serif, IBM Plex Mono fonts
Copyright IBM Corp. 2017, 2026
License: SIL Open Font License 1.1, https://opensource.org/licenses/OFL-1.1
Full text: LICENSE-OFL-1.1.txt
Source: https://github.com/IBM/plex

Placeholder images (assets/images/logo-*.svg, assets/images/avatar-*.svg)
Created for this theme, (C) 2026 AWT. The company names shown are fictional.
License: GPLv3 or later, https://www.gnu.org/licenses/gpl-3.0.html

== Accessibility statement ==

<!-- ACCESSIBILITY_START -->
# Accessibility statement

## Our commitment

AWT is a WordPress theme and blocks plugin committed to **WCAG 2.2 AA**
conformance for the components, patterns, and templates it ships.
Accessibility is the product's reason to exist, not a feature of it: every
block is built on the Carbon Design System's accessibility groundwork,
reviewed against WCAG 2.2 AA, and shipped with an in-editor accessibility
linter that helps authors keep their own content accessible.

## Scope

This statement covers what AWT ships, at its default state:

- The AWT theme: all bundled page templates, template parts, style
  variations, and block patterns.
- The AWT blocks plugin: every block, in the editor and on the published
  page, and the AWT Settings screens.

It does not cover:

- Content written by site owners and authors (the in-editor accessibility
  linter helps here, but authors stay responsible for their content).
- Third-party plugins installed alongside AWT.
- Custom CSS added through AWT Settings, and any code added by an AWT Premium
  add-on.

## Standard

**WCAG 2.2 Level AA.** The in-editor accessibility linter uses the same
2.2 AA thresholds (for example, contrast checks), so what the editor
enforces and what this statement promises stay aligned.

## Known limitations

We list what we know does not yet meet the standard, honestly:

- **No formal independent audit has been completed yet.** Conformance so far
  rests on the Carbon Design System's accessibility work, our own component
  reviews, automated checks, and an ongoing review by accessibility
  practitioners outside the project. That review is real and it has found real
  bugs, but it is not a published third-party audit. See "Audit status" below.
- Components inherit the current behavior of the Carbon Design System
  (v11). Where Carbon publishes known accessibility issues for a component,
  those apply to the matching AWT block until fixed upstream or worked
  around.

If you find a barrier we have not listed, please tell us — see "Feedback"
below.

## Audit status

**Outside expert review is under way. A formal audit is still to come.**

Since 2026-08-01, accessibility practitioners from outside the project have
been testing AWT with real assistive technology and reporting what they find.
Findings are treated as bugs: fixed, verified, and written up. This is not a
published third-party audit, and it does not replace one. A formal independent
audit is still planned before commercial launch, and its report will appear
here.

**Ten findings received so far. All ten are fixed.** Newest first.

1. **2026-08-07. A select in error was marked by colour alone, and lost even
   that on focus.** Carbon draws an error icon inside a select that has an
   error; ours drew only the red outline. So the field itself said "error" in
   red and nothing else, which colour may not do on its own. Worse, the rule
   Carbon writes for that red outline steps aside for the focus indicator — by
   design, since the two would otherwise occupy the same edge — so tabbing into
   the field removed the last mark of the error at the exact moment you reached
   it. The message under the field was still there and still read out, but the
   field showed nothing. The icon is now drawn, and it stays put whether the
   field is focused or not. The automated checks gained a select in error, three
   style probes and one screen-reader-tree probe: none of the four browser gates
   could see this shape before, because no test page contained one.

2. **2026-08-06. A focused button's outline could not be measured.** Carbon
   marks a focused button by recolouring its border, drawing a ring inside that,
   and then a third ring in the button's own background colour. That is two
   rings of different thickness, assembled from a border and two shadows, with
   no outline anywhere — so there is no single thickness to read. On a primary
   button two of the three layers are the same blue as the button fill, which is
   what made a reading ambiguous: whether the mark cleared the 2-pixel bar
   became something to argue about rather than something to check. A focused
   button now carries one 2-pixel outline just outside its edge, the same mark
   links and form fields already use. In the header bar there is no outside to
   draw in, because a button fills the bar's whole height, so there the same
   outline goes just inside the edge instead, matching the icon buttons beside
   it. (That part was a second pass the same day: the first version drew outside
   everywhere, which in the header spilled onto the page below and left a
   one-pixel gap that read as a border.) **AWT Settings → Carbon → Focus** puts
   Carbon's two-ring look back. Both meet the guideline — this was about which
   one an auditor can measure, not about whether a keyboard user can see it —
   and the automated check now measures both.

3. **2026-08-04. Links were marked out by colour alone.** A link had no
   underline until you pointed at it, so colour was the only thing telling you
   it was a link. Colour may do that job by itself only when the link is clearly
   different from the text around it — three times the contrast or more. Ours
   was not: 3.62 to 1 against body text in light mode, and 2.14 to 1 in dark
   mode. Any link sitting on a dark band inside a light page measured the same
   2.14 to 1, and changing the palette can take the light figure under the line
   too. Links are now underlined. There is a switch for each place a link
   appears (main content, header, side navigation, breadcrumbs, footer) and one
   above them that turns the whole thing off, in **AWT Settings → Carbon →
   Links**; all of them start on. Buttons, pagination numbers, tags, cards and
   the header's icon controls are not underlined, because each is already marked
   out by its own shape. Fixing this also removed a stray underline that had
   been drawn under the header's icon controls.

4. **2026-08-03. Focus rings were too thin, and three of them could not be
   seen at all.** The ring that marks the control you have reached with the Tab
   key was 1 pixel thick, where the guideline asks for 2. Three were worse than
   thin: each was drawn in a colour so close to the surface behind it that there
   was nothing to see. The close button on a notification measured 1.8 to 1, the
   dismiss button on a tag in dark mode 1.2 to 1, and the selected button in a
   Content switcher in dark mode 1.1 to 1. Every focus ring is now at least 2
   pixels thick, with at least 2 of those pixels checked against what sits
   behind them. One of the three faint rings was our own style rule overriding a
   Carbon rule that was already correct. A plain link typed into a paragraph now
   gets the theme's ring instead of the browser's. No setting puts Carbon's
   1-pixel ring back. Buttons later gained a choice, but it is between two marks
   that both clear the bar, not between having one and not — see finding 1.

5. **2026-08-03. Form fields were hard to see as fields.** A field was marked
   out by a shaded fill with a single line under the text, so what showed you
   where the field was, and how big it was, came down to that one line: the fill
   differs from the page by only 1.10 to 1 in light mode and 1.20 to 1 in dark,
   and in high-contrast (forced-colors) mode it is replaced entirely. Text
   input, Text area, Password input, Select and Dropdown now draw a border on all
   four sides, in the same color the line already used, which keeps the 3 to 1
   contrast that user-interface components need in both light and dark. Any
   single field can be put back to the one-line look with its **Carbon default**
   setting.

6. **2026-08-03. The Select block told screen readers it had one more option
   than it really has.** A select offering four choices was announced as
   "3 of 5". Its placeholder sat in the list assistive technology reads while
   being left out of the list drawn on screen, so every count was one too high.
   The placeholder is now an ordinary first option, present in both lists and
   greyed out. It still cannot be chosen. Screen readers that do not announce
   list position, including VoiceOver, were never affected.

7. **2026-08-02. The light and dark mode switch did not say what it had
   done.** The button was named "Light mode / Dark mode", which tells you
   neither what pressing it does nor which mode you are in, and the first press
   announced nothing at all. The button is now named after the mode it turns
   on, reports whether that mode is on, and announces the change. The segmented
   version now also marks which of Light, Auto and Dark is in use, and a
   returning visitor is no longer told the wrong mode while the page loads.

8. **2026-08-02. "Skip to main content" landed before the breadcrumb trail.**
   Automatic breadcrumbs were rendered inside the main region, so anyone who
   used the skip link still had every breadcrumb to move through before
   reaching the page content, which is what the skip link exists to avoid. The
   trail now sits immediately before the main region. Nothing changed visually.

9. **2026-08-01. The "Skip to main content" link was hard to see when
   focused.** An old style rule of ours was overriding part of the Carbon
   styling, so the focused link drew at roughly half its intended height and
   carried two different focus indicators at once. The override is gone. The
   focused link is now a full-height panel with its text at 11:1 contrast.

10. **2026-08-01. Links asked the reader to "see" a thing.** Reported on our own
   website: link text used *see* where an action word says the same thing
   without assuming sight. About 205 links and labels were reworded. This one
   was on our website rather than in the theme or plugin, so it falls outside
   the scope above, but it came from the same review and belongs in the same
   list.

Findings 1 to 8 are fixed in the theme and plugin, and each is described in
plain language in `CHANGELOG.md`.

## Feedback

Found an accessibility problem in AWT? Email
**[hello@useawt.com](mailto:hello@useawt.com)**.
Reports about real barriers are treated as bugs, not feature requests.

## Dates

- Statement prepared: 2026-07-17
- Last reviewed: 2026-08-07
<!-- ACCESSIBILITY_END -->

== Changelog ==

<!-- CHANGELOG_START -->
= 2026.08.0 — 2026-08-25 =
* [Breaking] **Custom code has moved to AWT Premium.** The screen that put your own markup into every page (before `</head>`, after `<body>`, before `</body>`) is not something a theme may do: WordPress.org asks themes to stay with design and presentation, and names injected script as the example of what belongs in a plugin. Anything you had saved is kept and reappears if you add Premium. **Custom CSS is unaffected** — styling your site is exactly what a theme is for.
* [Breaking] **Settings are stored under new names.** The option, and the keys behind a page's own language and hidden-breadcrumb choices, gained an `awt_theme_` prefix, because the directory requires at least four letters and ours was three. Your settings and per-page choices move themselves the first time you open the admin — nothing to do. Custom code that read `awt_settings` directly should read `awt_theme_settings`.
* [Breaking] **AWT is now GPLv3 or later, instead of GPLv2 or later.** Nothing changes for you as a site owner: you can still use, modify and redistribute it freely, and it runs on WordPress exactly as before. The change matters only if you redistribute AWT yourself or build on its code, in which case your copy must now follow GPLv3 terms. The reason is that AWT bundles Carbon Design System styles, which IBM releases under the Apache 2.0 licence. Apache 2.0 can be combined with GPLv3 but not with GPLv2, so the older label was wrong. The full licence text ships in `license.txt`, alongside the Apache 2.0 and SIL Open Font License texts covering Carbon and the IBM Plex fonts.
* [Breaking] Form labels, hints and error messages are now the same size as the text a visitor types into the field. Carbon sets all three a step smaller, so the three strings someone has to read in order to fill your form in were the smallest text on the page. **AWT Settings → Carbon → Typography** turns this off and gives you Carbon's sizes back. No accessibility rule sets a minimum text size, so both settings pass — bigger text is simply easier to read, and it matters most where reading it is the only way through. One field style is left out on purpose: the one that floats its label inside the box, where the label sits in a slot built for smaller text. (Breaking because forms get taller on upgrade, which can reflow a tight layout. If your own CSS sizes `.cds--label`, `.cds--form__helper-text` or `.cds--form-requirement`, check it against the D8 rules in `assets/css/theme.css`.)
* [Breaking] A focused button now has one clear outline instead of two rings. Carbon marks a focused button by recolouring its border, adding a ring inside that, and then a third ring in the button's own background colour — two rings of different thickness, built out of a border and two shadows, with no outline anywhere. Nothing about it measures as a single thickness, so an auditor checking whether the mark is big enough has to work it out from three overlapping layers, and an external audit of AWT could not state a figure. AWT now draws one 2-pixel outline just outside the button, the same mark links and form fields already use. In the header bar, where a button fills the whole height and there is no room outside it, that outline is drawn just inside instead, in the button's own text colour, matching the icon buttons beside it. **AWT Settings → Carbon → Focus** turns it off and gives you Carbon's two-ring look back. Both meet the guidelines — this is about which one you can measure, not about whether a keyboard user can see it. (Breaking because focused buttons change appearance on upgrade. If your own CSS styles `.cds--btn:focus`, check it against the D2 rules in `assets/css/theme.css`.)
* [Breaking] Links are underlined. Carbon leaves a link without an underline and brings it back only when you point at it, which asks colour to do the whole job of saying "this is a link". Colour may only do that job on its own when the link is clearly different from the text around it, and Carbon's blue is not: 3.62 to 1 against body text in light mode, and 2.14 to 1 in dark mode, where the bar is 3 to 1. Any link on a dark band inside a light page measures the same 2.14 to 1, and a brand colour a shade off Carbon's blue takes the light figure under the bar too. **AWT Settings → Carbon → Links** has a switch for each place a link appears — main content, header, side navigation, breadcrumbs, footer — with one switch above them that turns the whole thing off and gives you Carbon's link style back. All of them start on. Buttons, pagination numbers, tags, cards and the header's icon controls are never underlined, and plain links you type into your text are left exactly as your browser draws them. On a narrow screen the side navigation moves inside the header menu, and the **Side navigation** switch still governs it there, so a setting you chose does not change on a small screen. (Breaking because every AWT site's links change appearance on upgrade. If your own CSS sets link decoration, check it against the `--awt-underline` rules in `assets/css/theme.css`.)
* [Breaking] A style rule that positions the header in the block editor works again. It had been switched off without anyone noticing: a comment above it was closed one line too early, which left the lines after it as invalid CSS, and browsers respond to that by throwing away the next rule. The source looked correct, so nothing pointed at it. `npm run check:css` now fails if any stylesheet has that shape, and it runs on every change.
* [Breaking] Form fields are drawn with a border on all four sides instead of a single line under the text, so a field's shape no longer depends on its fill being slightly different from the page. Applies to Text input, Text area, Password input, Select and Dropdown; each of those blocks has a **Carbon default** setting that puts an individual field back to the one-line look. The border reuses the color the line already used, so it keeps 3 to 1 contrast in light and dark. If your own CSS restyles these fields' borders, check it against the `.awt-field--framed` rules in `assets/css/theme.css`.
* [Breaking] Read-only fields are drawn with the same border color as editable ones, in place of the much paler color they used to have. A read-only field has no shaded fill, so its border is the only thing showing you where the field is, and that border was far too faint to see: 1.32 to 1 against the page in light mode. It is now 3.32 to 1, the contrast a control needs. Read-only fields are still easy to tell apart, because what marks them is having no fill rather than having a paler edge: an editable field is a filled box, a read-only one is an outline, and a disabled one is a fill with no edge at all. A field with **Carbon default** on is unaffected.
* [Breaking] theme.json no longer lists header, side navigation, content and footer options under `settings.custom.ui-shell`. Nothing read them, so changing them changed nothing, and leaving them there implied a configurable header height and position that does not exist. The two live keys, `colorScheme` and `themeScope`, stay. (Breaking because the matching `--wp--custom--ui-shell--*` CSS variables are no longer generated; custom CSS that referenced them should use its own values.)
* [A11y] The menu item for the page you are on keeps its whole focus outline. Carbon marks the current page with a blue bar along the bottom of that menu item, and marks focus with an outline around it. Both were drawn in the same two pixels and the blue bar won, so a keyboard user landing on that item saw a box with no bottom edge — on whichever page they were on, which is to say on every page. The bar now sits just inside the outline, so you get both: a complete outline, and the bar telling you where you are.
* [A11y] A chosen tile in a Tile group shows it without relying on colour. Carbon only reveals the check mark on a tile once its own JavaScript adds a class, and tiles are now plain radio buttons with no such class, so the theme draws the chosen state from the radio itself: the blue edge, the accent bar, and the check mark. The edge clears the 3-to-1 contrast that a state indicator needs in both light and dark mode (4.55 and 4.52 to 1), and the check mark means the state is never carried by colour alone.
* [A11y] The segmented light/dark switch draws a focus ring you can actually see. Its ring was set to 2 pixels like everything else, but it was drawn on top of the control's own border, and the border colour is too close to the focus blue for the outer pixel to register — so only 1 pixel of it counted. The ring now sits just inside the border, where both pixels stand out. This is the same 2-pixel rule as below; the switch had slipped through it because it is drawn without any Carbon styling.
* [A11y] Every focus indicator in the theme is now at least 2 pixels thick, and at least 2 pixels of it are visible against what is behind it. The Carbon Design System draws its focus rings 1 pixel thick, so this affected almost every control on a page: links, buttons, form fields, the tag dismiss button and the expandable tile's summary. Thicker is the visible part of the change. Three cases were worse than thin, and those are the reason for it: - The close button on a notification drew a blue ring on a dark grey notification, at 1.8 to 1. One of our own style rules was overriding Carbon's correct colour choice for that surface. The rule is gone. - The tag dismiss button drew a white ring on a light blue tag in dark mode, at 1.2 to 1. It now uses the tag's own text colour, which the tag palette already pairs with each fill. - The selected button in a Content switcher drew a white ring on a light surface in dark mode, at 1.1 to 1. Its two-tone ring is now wide enough on both tones for one of them to show. A plain link typed into a paragraph used to fall back to whatever ring the browser draws. It now gets the theme's, so focus looks the same everywhere. Form fields draw their ring just outside the field instead of just inside: inside, it competed for the same pixels as the field border and, on a field with an error, as the red outline. There is no setting that returns Carbon's 1 pixel look. If your own CSS restyles focus indicators, check it against the "Focus appearance" section at the end of `assets/css/theme.css`.
* [A11y] Coloured tags now change with the page. A red or blue tag used to keep its light pill on a dark page, because its colours were written into the theme by hand with no dark version. They come from the Carbon Design System's own tag colours now, which are defined for light and dark, so a dark page gets a strong fill with light text on it. Two things follow from that: - The text inside a tag is a slightly lighter shade in light mode than it was. The old shades were an older version of Carbon's palette. Every colour still passes WCAG AA comfortably, from 5.8 to 1 at the lowest. - Tags follow your own colours. If your site overrides Carbon's tag colours, tags now use them instead of ignoring them. The dismiss button on a tag set to the **high-contrast** colour was still drawing a 1 pixel ring when a keyboard user reached it, which was missed when every other focus ring was raised to 2 pixels. It is 2 pixels now, like the rest.
* [A11y] Code snippets that scroll sideways now show the theme's focus ring when a keyboard user tabs to them, instead of the browser's own default outline. This covers both the single-line and multi-line kinds, each of which scrolls a different part of the snippet.
* [A11y] A data table that is wider than the space it has now shows a focus ring when a keyboard user tabs to it. The table scrolls sideways in that case, and the blocks plugin now makes the scrolling area reachable by keyboard so the columns past the right edge can be read without a mouse. Reaching it is only useful if you can see where you are, so it uses the same focus ring as every other focusable part of the theme, in light and dark, plus a dotted variant when the reader has asked for more contrast.
* [A11y] The "Segmented" color scheme toggle (Light / Auto / Dark) shows which option is in use. The one in use is filled, bold and underlined, so the state does not depend on color alone, and it reads correctly in both light and dark.
* [A11y] "Skip to main content" now reaches the content. Automatic breadcrumbs used to sit inside the main content area, so the skip link dropped you at the top of the trail and you had to tab through every crumb before reaching the page itself — the one thing the link exists to avoid. The trail now sits just above the main content instead of inside it, which is also where repeated navigation belongs. Screen readers announce the trail as "Breadcrumbs" rather than "Breadcrumb". Your pages look the same afterwards, with one intended exception: on the "page without title" template the trail used to sit flush against the header, and now it has the same breathing room it has everywhere else.
* [A11y] The Documentation article pattern no longer inserts a placeholder breadcrumb ("Home / Docs / Current page") into your page. Automatic breadcrumbs cover that page, with the real trail rather than placeholder text — and they sit in the right place. Pages you already built keep the breadcrumb they have.
* [A11y] Password field: the show/hide-password button is now as tall as the field it sits in. It had shrunk to the height of its eye icon — 40 by 16 pixels, under the 24 by 24 minimum a target needs — which made it easy to miss with a finger or an imprecise mouse, and gave it a focus outline that hugged the icon instead of the button. The icon stays exactly where it was.
* [A11y] Every page now has one header landmark and one footer landmark instead of two of each, nested inside one another. Screen reader users navigating by landmark heard the site header twice in a row, and the footer twice, with nothing to tell the pair apart. Nothing about how the page looks changes.
* [A11y] A side navigation section with no title now shows its links. Untitled sections hid them completely: the links were in the page but drawn at no height, and each one still took keyboard focus, so someone tabbing through landed on links nobody could see. Titled sections were never affected, which is why this went unnoticed — showing the links had been tied to the section having a title, two things that have nothing to do with each other.
* [A11y] The side navigation works on phones and small tablets. It used to open as a full-height panel over your content with no way to close it, and its links stayed focusable while off-screen, so keyboard users could tab into things they could not see. Below 1056px the panel now steps aside — there is no room for it — and its links move into the header menu, under the site's main menu items and separated from them by a rule, so nothing becomes unreachable. On a documentation site those links are the documentation. The rule uses the design system's border token, so it is correct in both light and dark mode, and the links keep their own name for screen readers ("Side navigation") instead of being folded into the main menu's.
* [A11y] Your site no longer flashes the wrong theme before it settles. If your device is set to dark mode and you have not used the light/dark toggle yet, the page used to paint light first and switch to dark a moment later. On a slow connection that flash was clearly visible. Dark now applies before anything is drawn.
* [A11y] A dismissed Notification block now fully disappears: an explicit `[hidden]` rule makes sure the notification's flex layout can't keep it visible after its close button (wired in awt-blocks) hides it.
* [A11y] Footer links are now easier to tap: each link is at least 32px tall with an 8px gap between rows. Before, the links stacked with no spacing at all, so on touch screens it was easy to hit the wrong one — below the WCAG 2.5.8 minimum target size, and flagged by Lighthouse. The links look the same, they just breathe.
* [A11y] Paragraphs placed directly in a Section now keep a readable line length: they are capped at the reading measure (48rem, about 90 characters), matching how the Carbon Design System site sets its own body text. Very long lines are a barrier for low-vision and dyslexic readers (WCAG 1.4.8 recommends 80 characters or less). Centered and right-aligned paragraphs keep their alignment. Text inside columns, tiles, and other blocks is not affected — it is already sized by its container. To let one paragraph span the full section, place it in a Group; to line a whole section up with the text column, use the Section's new "Reading (48rem)" max width.
* [A11y] The "Skip to main content" link now looks the way the design system draws it. When you tabbed to it, it showed up as a squat box wearing two focus rings at once, about half the height it should be: an older theme rule and the design system's own rule each won part of the styling. It is now a single panel the full height of the header, with one focus border, in both light and dark mode.
* [Improvement] AWT Settings has moved from Settings to **Appearance**, where WordPress keeps everything that changes how a site looks. The page is unchanged; only where you find it. It also now opens for anyone allowed to change the site's appearance, rather than only for full site administrators.
* [Improvement] The welcome notice can be dismissed, and stays dismissed.
* [Improvement] The What's new tab reads as text instead of showing the marks around it. Bold wording and file names arrived with asterisks and backticks still around them, the way they are typed in the release notes.
* [Improvement] Tested on WordPress 7.0. Everything was checked on it before the version was written down: the blocks and patterns on the front end, the editor, the Site Editor, and every AWT Settings screen.
* [Improvement] The footer credit now reads "Built with AWT, an accessible theme for WordPress." If you have edited your own footer, your version is untouched.
* [Improvement] Version numbers now agree with each other, reading `2026.01.0` everywhere instead of `2026.01.0-stage1` in some places.
* [Improvement] **AWT Settings → Carbon → Header → Header appearance** now decides the header bar's colour even if you have already given the header a light or dark look of its own in the Site Editor. Before, your own choice quietly won every time: all three options left the header exactly as it was, with nothing on the screen to say why. "Always light" and "Always dark" now replace whatever is on the header; "Default" still leaves it alone, which is what "Default" means.
* [Improvement] Breadcrumbs on a month or day archive now name the month you are looking at. They named the current month instead, so an archive of posts from March, visited in August, said "August" — the breadcrumb was reading the clock rather than the archive. The year and the day beside it were always correct. Archives from the current month looked right either way, which is why this went unnoticed.
* [Improvement] The Documentation header preset now gives you the side navigation its description promises. Picking it adds a side navigation down the left of every page on wide screens; edit its links in the Site Editor under Patterns → Template Parts → Side navigation. Before, the preset described a side navigation and none ever appeared, because nothing on the site referenced that template part.
* [Improvement] Where a side navigation is present, the page content and the footer both sit clear of it. The footer used to run the full width of the window, so the text at its left edge was hidden behind the side navigation. The side navigation also starts below the header now instead of on top of it, so it no longer covers your logo or site title.
* [Improvement] Section titles in the side navigation now look like titles: bold, sized to match the links, and lined up with them. They used to sit flush against the left edge in plain body text.
* [Improvement] AWT Settings → Appearance → Header now tells you what the header's size and position are — a bar 48 pixels tall, fixed to the top of the screen — and that they come from the Carbon Design System layout, so there is no setting to change them. Before, an author looking for a height or position control found nothing and no explanation.
* [Improvement] A logo you upload now shows up on its own. Adding a logo during setup, or on AWT Settings → Identity, saved it but left your header showing only the site title, because showing a logo needed a second setting on another tab. Brand mode now starts on "Automatic", which shows the logo and prefix you have set. Pick any other option on AWT Settings → Appearance → Header to always show the same thing.
* [Improvement] Footer section headings now get the theme's default heading gap: 16px (spacing-05) between the heading and its links. Before, the heading sat flush on the first link. Their weight is also pinned (regular, 400) so the Site Editor shows the same heading the published footer renders.
* [Improvement] AWT Settings → Navigation has a new "Edit the footer" button under the existing "Edit the header" one. It opens the footer in the Site Editor, where the footer's links and text are edited.
* [Improvement] The side navigation can now be edited. It is a normal block inside the header: it shows up in List View, you can click it, and it has its own settings. Until now the Documentation preset pulled it in from a separate "Side navigation" template part, and WordPress does not let you edit one template part from inside another — so the side nav appeared in the header but could not be selected or changed at all. Its links live in the header template part now, and the separate part is gone.
* [Improvement] AWT Settings → Navigation also has an "Edit the side navigation" button beside the header and footer ones. It opens the header, where the side nav is, so you do not have to know that is where to look.
* [Improvement] The side navigation now previews in the Site Editor the way it renders. When you edit the header, it appears below the header bar at its real width instead of squeezed into a small box at the right-hand end of the bar.
* [Improvement] The core List block renders as a proper list again: bullets (numbers for ordered lists), indentation, and the same line height as body text. Carbon's CSS reset removes native list styling because Carbon components bring their own; the plain List block isn't a Carbon component, so its lists rendered flat — no markers, no indent, cramped lines. Nested lists get the standard circle and square markers, and ordered-list settings (start value, reversed) keep working. The AWT List block's Carbon look (en-dash markers) is unchanged.
* [Improvement] Links typed into content now show the same color in the editor as on the published page. The theme's link color now follows Carbon's link token, so a link inside a dark section correctly previews in the light blue that the published page already used, instead of a dark blue that was hard to read on the dark background. Site-wide token overrides (for example a brand link color set through AWT Settings → Custom CSS) now reach the editor preview too.
* [Improvement] Blocks set to "Align center" inside a Section now actually center, both on the page and in the editor. WordPress only centers such blocks inside its own layout containers, so a centered, resized image in a Section used to stick to the left edge.
* [Improvement] The Page template no longer adds its own 32px padding under the content. The space before the footer now comes from one place — the last block's Spacing setting — instead of two stacked sources. Pages that end in a full-width color band can sit flush against the footer with the section's "No gap below" switch; on other pages the gap is the last block's spacing (16px by default, adjustable per block).
* [Improvement] Spacing tokens below 16px (spacing-01 to spacing-04) now produce the exact gap they promise. WordPress adds a 16px layout margin above every block, which used to override the smaller tokens — the gap never went below 16px.
* [Improvement] Pattern placeholder copy rewritten in plainer language: "ship" wording and em dashes are gone, and the free-product FAQ answer now matches the promise that the free theme and plugin are the complete product.
* [Improvement] Automatic breadcrumbs now render through the Breadcrumb block when the AWT Blocks plugin is active, so their Carbon styling (including the "/" separators) loads on every page — not just pages that already contain a Breadcrumb block. The trail no longer shows a separator after the current page.
* [New] Initial Stage 1 release of the AWT theme: Carbon Design System foundation CSS, eight page templates, header/footer/sidebar template parts, block patterns, style variations (light + dark scope pairs), the AWT Settings screen with welcome wizard, automatic breadcrumbs, and visitor color-scheme support.
<!-- CHANGELOG_END -->

== Upgrade Notice ==

= 2026.08.0 =
First release. Nothing to upgrade from yet.
