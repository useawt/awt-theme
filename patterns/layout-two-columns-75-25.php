<?php
/**
 * Title: AWT — Layout: two columns (75/25)
 * Slug: awt/layout-two-columns-75-25
 * Design system: carbon
 * Description: Main content (3/4) beside a narrow sidebar (1/4).
 * Categories: awt-theme-section
 * Keywords: layout, two columns, 75/25, main + narrow sidebar
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"08","maxWidth":"content"} -->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"75%"} -->
<div class="wp-block-column" style="flex-basis:75%"><!-- wp:paragraph --><p>Main content column.</p><!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"25%"} -->
<div class="wp-block-column" style="flex-basis:25%"><!-- wp:paragraph --><p>Narrow aside.</p><!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:awt/section -->
