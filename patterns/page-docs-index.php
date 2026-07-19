<?php
/**
 * Title: AWT — Page: Documentation index
 * Slug: awt/page-docs-index
 * Design system: carbon
 * Description: Starter docs landing page. Heading + intro + 3-column card grid linking to top-level docs sections.
 * Categories: awt-section
 * Keywords: page, docs, documentation, knowledge base
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"09","maxWidth":"content"} -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Documentation</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Pick a topic to get started. Each section is grouped by what you're trying to do: explore by task, not by component.</p>
<!-- /wp:paragraph -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:awt/tile {"clickable":true,"href":"/docs/getting-started"} -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Getting started</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Install, activate, pick a style variation, configure your first pages.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/tile --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:awt/tile {"clickable":true,"href":"/docs/blocks"} -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Block reference</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Every block, its attributes, and authoring tips. Searchable index.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/tile --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:awt/tile {"clickable":true,"href":"/docs/patterns"} -->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Patterns</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Pre-composed page sections. Drop-in starters for hero, pricing, contact.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/tile --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:awt/section -->
