# Contributing to AWT Theme

Thank you for helping. AWT is an accessibility-first WordPress theme, so every
change has to keep the theme at least as accessible as it was before.

## Report a problem

- **Something broken:** open a
  [bug report](https://github.com/useawt/awt-theme/issues/new?template=bug.yml).
- **Something hard or impossible to use** with a keyboard, screen reader,
  zoom or other assistive technology: open an
  [accessibility problem](https://github.com/useawt/awt-theme/issues/new?template=accessibility.yml).
- **A security problem:** do not open an issue. Email
  [hello@useawt.com](mailto:hello@useawt.com) instead.
- **A problem with a block:** report it in
  [AWT Blocks](https://github.com/useawt/awt-blocks/issues). The blocks live there.

## Before you change code

For anything bigger than a small fix, open an issue first and describe what
you want to change. That saves you work on something we would not merge.

The theme and [AWT Blocks](https://github.com/useawt/awt-blocks) are released
together under the same version number. If your change needs both, open a pull
request in each and link them to each other.

## Set up

The development site lives in the blocks repo. Clone both repos side by side
and follow the
[AWT Blocks setup](https://github.com/useawt/awt-blocks#development-setup).
Then install this repo's tools:

```bash
cd awt-theme
npm install
composer install
```

## Edit `src/`, then build

Everything a browser downloads is built from `src/` into `assets/`, with the
comments removed. So:

- Edit files in `src/` and write your explanations there, as long as they need
  to be.
- Never edit `assets/` by hand. Run `npm run build:assets` and commit what it
  produces. CI fails if `assets/` does not match a fresh build.
- CSS or JavaScript printed from PHP is not built. Put its explanation in a
  PHP comment next to it, not inside the output.

## What every change must keep

- **Accessibility.** Every control has an accessible name, works with the
  keyboard, and shows a visible focus indicator. Colour is never the only way
  something is shown. Text meets WCAG AA contrast (4.5:1, or 3:1 for large
  text) and controls meet 3:1. Targets are at least 24 by 24 CSS pixels.
  Motion respects `prefers-reduced-motion`.
- **Light and dark mode.** Use the existing colour tokens instead of fixed
  colour values, and check your change in both modes.
- **Plain language.** Anything a site owner or visitor reads is short and
  clear, with no unexplained jargon. Keep real terms such as `aria-label` or
  alt text, and explain them briefly if needed.
- **Nothing from AWT Premium.** Code for the paid add-on does not belong here.
  A check runs on every commit and in CI.

## Run the checks

```bash
npm run lint:js
npm run lint:php
npm run lint:md
npm run check:assets
npm run test:php     # needs the development site running
```

A pull request can only be merged when CI passes.

## Changelog

If a site owner would notice your change, add one line to `CHANGELOG.md` under
`## Unreleased` at the top (add that heading if it is not there). Put it under
one of these tags:

| Tag | Use it for |
| --- | --- |
| `[Security]` | A security fix |
| `[A11y]` | A change to what people using assistive technology get, including changes only screen readers notice |
| `[Breaking]` | The site renders differently than before, or a block or block feature is discontinued or deprecated |
| `[New]` | A new feature, pattern or setting |
| `[Improvement]` | Any other fix or improvement a site owner would notice |

Say what changed in one or two plain sentences. The maintainer checks every
entry and tag before a release.

## Commit messages

One short line that says what changed, for example `Fix focus ring on the
search button`. No reasons, no plans, no build steps. The history is public.

## Licence

AWT Theme is licensed under GPL-3.0-or-later. By contributing, you agree that
your contribution is licensed the same way.
