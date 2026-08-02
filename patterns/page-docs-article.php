<?php
/**
 * Title: AWT — Page: Documentation article
 * Slug: awt/page-docs-article
 * Design system: carbon
 * Description: Starter docs article. Heading + intro + body content scaffold.
 * Categories: awt-section
 * Keywords: page, docs, article, documentation
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"08","maxWidth":"narrow"} -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Article title</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"awt-docs-intro"} -->
<p class="awt-docs-intro">One- to two-sentence intro that sets up what this article will cover. Carbon-style lead paragraph.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">First section</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Body content. Use <code>core/heading</code> level 2 for major sections and level 3 for sub-sections. Use <code>awt/notification</code> for inline callouts.</p>
<!-- /wp:paragraph -->

<!-- wp:awt/notification {"kind":"info","title":"Heads-up","subtitle":"Inline notifications work well for prerequisites, version compatibility notes, or tips.","lowContrast":true,"hideCloseButton":true} /-->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Second section</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Continue the article. For code samples, use the awt/code-snippet block.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/section -->
