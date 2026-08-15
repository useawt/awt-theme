=== AWT ===
Contributors: useawt
Requires at least: 6.6
Tested up to: 6.8
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
(Injected from ACCESSIBILITY.md by `npm run release:prepare`.)
<!-- ACCESSIBILITY_END -->

== Changelog ==

<!-- CHANGELOG_START -->
(Injected from CHANGELOG.md by `npm run release:prepare`.)
<!-- CHANGELOG_END -->
