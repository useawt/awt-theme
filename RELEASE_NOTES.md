## 2026.08.0 — 2026-08-25

### [New]

- **First release.** AWT is a WordPress block theme built on IBM's Carbon Design System, designed to be accessible before you change anything.
- Eight page templates, plus header, footer and side navigation parts you edit in the Site Editor.
- Style variations in matched light and dark pairs, and a light/dark toggle for your visitors. The page arrives in the right mode with no flash of the wrong one.
- Block patterns for the pages most sites need: heroes, feature grids, statistics, FAQs, forms and documentation pages.
- An AWT Settings screen under Appearance, with a short setup wizard for your logo, colours and header style.
- Automatic breadcrumbs, with a switch to hide them on any page.
- Licensed GPLv3 or later. That matters only if you redistribute AWT or build on its code — using it on your site is unaffected.

### [A11y]

- AWT departs from stock Carbon in a few places on purpose. Each one has a switch if you prefer Carbon's original.
- Links are underlined. Carbon's link blue is too close to body text for colour alone to mark a link — 2.14 to 1 in dark mode, where the bar is 3 to 1. **Settings → Carbon → Links** has a switch for each region of the page.
- Every focus indicator is at least 2 pixels thick and stands out against whatever is behind it. Carbon draws 1 pixel.
- A focused button gets one 2-pixel outline instead of Carbon's three overlapping rings, so an auditor can measure it. **Settings → Carbon → Focus** gives Carbon's look back.
- Form fields have a border on all four sides instead of a single line under the text, so a field's shape does not depend on its fill. Each field block has a **Carbon default** switch.
- Form labels, hints and error messages are the same size as the text you type into the field. Carbon sets all three a step smaller. **Settings → Carbon → Typography** turns this off.
- The side navigation works on a phone. Below 1056px it steps aside and its links move into the header menu, so nothing becomes unreachable.
- "Skip to main content" lands on the content. The breadcrumb trail sits above the main area rather than inside it, so the link skips it too.
- Each page has one header landmark and one footer landmark, so screen reader users do not hear each one twice.
- Coloured tags follow the page into dark mode, and footer links are big enough to tap.
