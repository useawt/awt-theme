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

Install it together with the AWT Blocks plugin, which adds 58 matching accessible blocks and an accessibility checker inside the editor. The two are one product: the header, the footer and all 42 patterns are built from those blocks, so install both. Download them from useawt.com.

== Frequently Asked Questions ==

= Does the color scheme switch need a cookie banner? =

No. The visitor's choice is stored in a cookie that only holds a UI preference the visitor set themselves. Under EU rules that is a strictly necessary cookie — no consent banner is required.

= Does it work with caching plugins? =

Yes. One cached copy of a page serves both light and dark visitors correctly; a small script applies the visitor's choice before the page paints. Don't configure your cache to vary by cookie — it isn't needed.

= Where do the blocks come from? =

From the AWT Blocks plugin, and the theme needs it. The header, the footer and every pattern are built from those blocks, so install both — the theme on its own gives you an empty header and no patterns.

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
* [New] **First release.** AWT is a WordPress block theme built on IBM's Carbon Design System, designed to be accessible before you change anything.
* [New] Eight page templates, plus header, footer and side navigation parts you edit in the Site Editor.
* [New] Style variations in matched light and dark pairs, and a light/dark toggle for your visitors. The page arrives in the right mode with no flash of the wrong one.
* [New] Block patterns for the pages most sites need: heroes, feature grids, statistics, FAQs, forms and documentation pages.
* [New] An AWT Settings screen under Appearance, with a short setup wizard for your logo, colours and header style.
* [New] Automatic breadcrumbs, with a switch to hide them on any page.
* [New] Licensed GPLv3 or later. That matters only if you redistribute AWT or build on its code — using it on your site is unaffected.
* [A11y] AWT departs from stock Carbon in a few places on purpose. Each one has a switch if you prefer Carbon's original.
* [A11y] Links are underlined. Carbon's link blue is too close to body text for colour alone to mark a link — 2.14 to 1 in dark mode, where the bar is 3 to 1. **Settings → Carbon → Links** has a switch for each region of the page.
* [A11y] Every focus indicator is at least 2 pixels thick and stands out against whatever is behind it. Carbon draws 1 pixel.
* [A11y] A focused button gets one 2-pixel outline instead of Carbon's three overlapping rings, so an auditor can measure it. **Settings → Carbon → Focus** gives Carbon's look back.
* [A11y] Form fields have a border on all four sides instead of a single line under the text, so a field's shape does not depend on its fill. Each field block has a **Carbon default** switch.
* [A11y] Form labels, hints and error messages are the same size as the text you type into the field. Carbon sets all three a step smaller. **Settings → Carbon → Typography** turns this off.
* [A11y] The side navigation works on a phone. Below 1056px it steps aside and its links move into the header menu, so nothing becomes unreachable.
* [A11y] "Skip to main content" lands on the content. The breadcrumb trail sits above the main area rather than inside it, so the link skips it too.
* [A11y] Each page has one header landmark and one footer landmark, so screen reader users do not hear each one twice.
* [A11y] Coloured tags follow the page into dark mode, and footer links are big enough to tap.
<!-- CHANGELOG_END -->

== Upgrade Notice ==

= 2026.08.0 =
First release. Nothing to upgrade from yet.
