<?php
/**
 * Title: AWT — Header preset: Application
 * Slug: awt/header-preset-application
 * Design system: carbon
 * Description: Brand + global actions cluster (search, notifications, user menu) with optional side-nav toggle. For SaaS dashboards and admin tools.
 * Categories: awt-section, header
 * Keywords: header, preset, app, dashboard, saas
 * Block Types: core/template-part/header
 * Inserter: yes
 *
 * Mirrors §1 "Header style presets → 3. Application". The leading
 * `awt/header-action` button toggles the side-nav panel; remove it if
 * the site doesn't use a side nav. This preset's own description calls the
 * side nav optional, so — unlike Documentation — it does not reference the
 * `sidebar` template part; an author who wants one adds it themselves.
 */
?>
<!-- wp:awt/skip-link /-->
<!-- wp:awt/header-brand {"kind":"logo-with-text"} /-->
<!-- wp:awt/header-nav -->
<!-- wp:awt/header-nav-item {"text":"Dashboard","href":"/"} /-->
<!-- wp:awt/header-menu {"text":"Reports"} -->
<!-- wp:awt/header-nav-item {"text":"Overview","href":"/reports"} /-->
<!-- wp:awt/header-nav-item {"text":"Exports","href":"/reports/exports"} /-->
<!-- wp:awt/header-nav-item {"text":"Scheduled","href":"/reports/scheduled"} /-->
<!-- /wp:awt/header-menu -->
<!-- /wp:awt/header-nav -->
<!-- wp:awt/header-global -->
<!-- wp:awt/header-action {"iconName":"search","label":"Search","href":"/?s="} /-->
<!-- wp:awt/header-action {"iconName":"notification","label":"Notifications","href":"/"} /-->
<!-- wp:awt/color-scheme-toggle {"kind":"icon-only"} /-->
<!-- wp:awt/header-action {"iconName":"user--avatar","label":"Account","href":"/wp-login.php"} /-->
<!-- /wp:awt/header-global -->
