<?php
/**
 * Title: AWT — Header preset: Documentation
 * Slug: awt/header-preset-documentation
 * Design system: carbon
 * Description: Your logo, section links, search, and a side navigation menu down the left on wide screens. Good for knowledge bases and reference sites.
 * Categories: awt-section, header
 * Keywords: header, preset, docs, knowledge base, reference
 * Block Types: core/template-part/header
 * Inserter: yes
 *
 * NOTE: the Description above is duplicated in inc/design-system/carbon.php
 * (get_header_presets()), which renders the same sentence in the AWT Settings
 * header-preset picker. WordPress parses this line statically from the file
 * comment, so it cannot read that PHP. Change both places together.
 *
 * Mirrors §1 "Header style presets → 2. Documentation". The side nav's blocks
 * are written out here rather than pulled in from a `sidebar` template part:
 * a part nested inside another part gets a block overlay, so the nav would be
 * visible in the header canvas but impossible to select or configure. Carbon's
 * UI Shell puts `.cds--side-nav` inside `header.cds--header`, which is where
 * this lands it.
 */
?>
<!-- wp:awt/skip-link /-->
<!-- wp:awt/header-brand {"kind":"text-with-prefix"} /-->
<!-- wp:awt/header-nav -->
<!-- wp:awt/header-nav-item {"text":"Guides","href":"/guides"} /-->
<!-- wp:awt/header-menu {"text":"Reference"} -->
<!-- wp:awt/header-nav-item {"text":"API","href":"/reference/api"} /-->
<!-- wp:awt/header-nav-item {"text":"CLI","href":"/reference/cli"} /-->
<!-- wp:awt/header-nav-item {"text":"SDKs","href":"/reference/sdks"} /-->
<!-- /wp:awt/header-menu -->
<!-- wp:awt/header-nav-item {"text":"Changelog","href":"/changelog"} /-->
<!-- /wp:awt/header-nav -->
<!-- wp:awt/header-global -->
<!-- wp:awt/header-action {"iconName":"search","label":"Search docs","href":"/?s="} /-->
<!-- wp:awt/color-scheme-toggle {"kind":"icon-only"} /-->
<!-- wp:awt/header-action {"iconName":"logo--github","label":"View on GitHub","href":"#"} /-->
<!-- /wp:awt/header-global -->
<!-- wp:awt/side-nav -->
<!-- wp:awt/side-nav-section {"title":"Get started"} -->
<!-- wp:awt/side-nav-link {"text":"Overview","href":"/overview"} /-->
<!-- wp:awt/side-nav-link {"text":"Quick start","href":"/quick-start"} /-->
<!-- /wp:awt/side-nav-section -->
<!-- wp:awt/side-nav-section {"title":"Reference"} -->
<!-- wp:awt/side-nav-link {"text":"Blocks","href":"/reference/blocks","matchMode":"prefix"} /-->
<!-- wp:awt/side-nav-link {"text":"Patterns","href":"/reference/patterns","matchMode":"prefix"} /-->
<!-- /wp:awt/side-nav-section -->
<!-- /wp:awt/side-nav -->
