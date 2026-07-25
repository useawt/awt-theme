<?php
/**
 * Title: AWT — Team grid
 * Slug: awt/team-grid
 * Design system: carbon
 * Description: 3-column team-member grid with photo + name + role. Replace placeholder images with real photos.
 * Categories: awt-section
 * Keywords: team, people, about, staff
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"09","maxWidth":"content","ariaLabel":"Team"} -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Meet the team</h2>
<!-- /wp:heading -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"sizeSlug":"medium","style":{"border":{"radius":"50%"}}} -->
<figure class="wp-block-image size-medium has-custom-border"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/avatar-1.svg' ) ); ?>" alt="Placeholder portrait of team member 1" style="border-radius:50%"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Name One</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Role and short bio. Replace with real content.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"sizeSlug":"medium","style":{"border":{"radius":"50%"}}} -->
<figure class="wp-block-image size-medium has-custom-border"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/avatar-2.svg' ) ); ?>" alt="Placeholder portrait of team member 2" style="border-radius:50%"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Name Two</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Role and short bio. Replace with real content.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:image {"sizeSlug":"medium","style":{"border":{"radius":"50%"}}} -->
<figure class="wp-block-image size-medium has-custom-border"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/avatar-3.svg' ) ); ?>" alt="Placeholder portrait of team member 3" style="border-radius:50%"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Name Three</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Role and short bio. Replace with real content.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:awt/section -->
