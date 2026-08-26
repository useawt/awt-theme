<?php
/**
 * Title: AWT — Layout: three columns (25/50/25)
 * Slug: awt/layout-three-columns-25-50-25
 * Design system: carbon
 * Description: Three columns with center-weighted middle. Sidebar + main + sidebar.
 * Categories: awt-theme-section
 * Keywords: layout, three columns, asymmetric, sidebar, center-weighted
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"08","maxWidth":"content"} -->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column {"width":"25%"} -->
<div class="wp-block-column" style="flex-basis:25%"><!-- wp:paragraph --><p>Left aside.</p><!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"50%"} -->
<div class="wp-block-column" style="flex-basis:50%"><!-- wp:paragraph --><p>Center main column.</p><!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"25%"} -->
<div class="wp-block-column" style="flex-basis:25%"><!-- wp:paragraph --><p>Right aside.</p><!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:awt/section -->
