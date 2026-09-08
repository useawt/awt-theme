## 2026.09.1 — 2026-09-08

### [Improvement]

- Blog listings and single posts now show the post's featured image, with the image beside the text rather than above it.
- A post's categories appear under its date.
- Post text is held to a comfortable reading width instead of running the full page.
- The footer no longer leaves a thin strip of page colour underneath it.
- Form fields, checkboxes, radios and buttons that no block rendered — a password form, a custom HTML block, a plugin's form — now match the rest of the site instead of showing plain browser widgets.
- Saving AWT Settings without changing anything no longer reports an error.

### [A11y]

- Text marked bold with `<b>` now looks bold. It was rendering at the same weight as the text around it, so the emphasis was lost.
- A field the browser fills in for you keeps the site's colours. It used to turn pale yellow, which was unreadable in dark mode.
