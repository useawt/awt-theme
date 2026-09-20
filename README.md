# AWT Theme

An accessibility-first WordPress block theme built on the
[Carbon Design System](https://carbondesignsystem.com/). Used together with
[AWT Blocks](https://github.com/useawt/awt-blocks).

**Status: released.** Download the latest version from
[Releases](https://github.com/useawt/awt-theme/releases); installed sites are offered
updates in WordPress's own Updates screen. AWT is not distributed through the
WordPress.org directory.

## Development setup

The development environment (wp-env) lives in the blocks repo. Clone both
repos side by side and follow the
[AWT Blocks setup](https://github.com/useawt/awt-blocks#development-setup):

```bash
git clone https://github.com/useawt/awt-theme.git
git clone https://github.com/useawt/awt-blocks.git
```

The wp-env site mounts this theme directly — edits to `theme.css`, templates,
and `theme.json` apply on reload, no build step.

## License

GPL-3.0-or-later
