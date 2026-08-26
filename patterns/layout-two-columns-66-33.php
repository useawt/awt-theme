<?php
/**
 * Title: AWT — Layout: two columns (66/33)
 * Slug: awt/layout-two-columns-66-33
 * Design system: carbon
 * Description: Two columns at 2/3 + 1/3 width. Main content + sidebar.
 * Categories: awt-theme-section
 * Keywords: layout, two columns, 66/33, main + sidebar, asymmetric
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"08","maxWidth":"content"} -->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"66.66%"} -->
<div class="wp-block-column" style="flex-basis:66.66%"><!-- wp:paragraph --><p>Main content column.</p><!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"33.33%"} -->
<div class="wp-block-column" style="flex-basis:33.33%"><!-- wp:paragraph --><p>Sidebar / aside column.</p><!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:awt/section -->
