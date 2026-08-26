<?php
/**
 * Title: AWT — Header preset: Marketing
 * Slug: awt/header-preset-marketing
 * Design system: carbon
 * Description: Your logo, a row of menu links, and one or two icon buttons. No side navigation. Good for landing pages and product sites.
 * Categories: awt-theme-section, header
 * Keywords: header, preset, marketing, landing, brand
 * Block Types: core/template-part/header
 * Inserter: yes
 *
 * NOTE: the Description above is duplicated in inc/design-system/carbon.php
 * (get_header_presets()), which renders the same sentence in the AWT Settings
 * header-preset picker. WordPress parses this line statically from the file
 * comment, so it cannot read that PHP. Change both places together.
 *
 * Composition mirrors the §1 "Header style presets → 1. Marketing"
 * pseudocode exactly. Switching preset only replaces the header template
 * part's content — the header bar itself is Carbon's fixed 3rem UI Shell
 * header on every preset, and no preset changes that.
 */
?>
<!-- wp:awt/skip-link /-->
<!-- wp:awt/header-brand {"kind":"text-with-prefix"} /-->
<!-- wp:awt/header-nav -->
<!-- wp:awt/header-menu {"text":"Product"} -->
<!-- wp:awt/header-nav-item {"text":"Features","href":"#"} /-->
<!-- wp:awt/header-nav-item {"text":"Integrations","href":"#"} /-->
<!-- wp:awt/header-nav-item {"text":"Updates","href":"#"} /-->
<!-- /wp:awt/header-menu -->
<!-- wp:awt/header-nav-item {"text":"Pricing","href":"#"} /-->
<!-- wp:awt/header-nav-item {"text":"Customers","href":"#"} /-->
<!-- /wp:awt/header-nav -->
<!-- wp:awt/header-global -->
<!-- wp:awt/button {"text":"Get started","kind":"primary","size":"md","className":"awt-hide-on-mobile"} /-->
<!-- wp:awt/color-scheme-toggle {"kind":"icon-only"} /-->
<!-- /wp:awt/header-global -->
