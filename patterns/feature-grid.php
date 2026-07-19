<?php
/**
 * Title: AWT — Feature grid (3-up)
 * Slug: awt/feature-grid
 * Design system: carbon
 * Description: Three-column feature grid with icon + heading + description per cell.
 * Categories: awt-section, columns
 * Keywords: features, grid, marketing
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"09","maxWidth":"content","ariaLabel":"Features"} -->
<!-- wp:heading {"level":2,"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center">Why AWT</h2>
<!-- /wp:heading -->

<!-- wp:awt/feature-grid {"columns":3} -->
<!-- wp:awt/tile -->
<!-- wp:awt/icon {"iconName":"checkmark","size":"24","color":"support-success","decorative":false,"label":"Accessible"} /-->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Accessible</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Every block outputs semantically correct HTML and is validated against WCAG 2.2 AA.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/tile -->

<!-- wp:awt/tile -->
<!-- wp:awt/icon {"iconName":"information","size":"24","color":"support-info","decorative":false,"label":"Design system"} /-->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Carbon Design System</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>Proven visual system from IBM, paired light/dark variants out of the box.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/tile -->

<!-- wp:awt/tile -->
<!-- wp:awt/icon {"iconName":"launch","size":"24","color":"link-primary","decorative":false,"label":"Free"} /-->
<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Free, forever</h3>
<!-- /wp:heading -->
<!-- wp:paragraph -->
<p>GPL-2.0-or-later. No telemetry, no callbacks, no lock-in.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/tile -->
<!-- /wp:awt/feature-grid -->
<!-- /wp:awt/section -->
