## 2026.09.33 — 2026-09-23

### [Improvement]

- AWT's stylesheets and scripts are now compressed, so every page loads less code.
- AWT no longer loads WordPress's emoji script on the front end.
- The page head no longer carries the WordPress version number or the pointer to `xmlrpc.php`.
- Pasting a link to one of your pages into another WordPress site no longer turns it into a preview card. Add the `awt_oembed_discovery` filter to put it back.
- A site with comments switched off no longer advertises a comments feed.
