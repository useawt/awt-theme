<?php
/**
 * Title: AWT — Logo cloud
 * Slug: awt/logo-cloud
 * Design system: carbon
 * Description: Row of customer or partner logos with an optional heading. Used to build social proof on marketing pages.
 * Categories: awt-theme-section
 * Keywords: logos, customers, partners, social proof, brands
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"08","maxWidth":"content","ariaLabel":"Trusted by"} -->
<!-- wp:heading {"level":3,"textAlign":"center","style":{"typography":{"fontSize":"var(--cds-body-02-font-size)"}}} -->
<h3 class="wp-block-heading has-text-align-center" style="font-size:var(--cds-body-02-font-size)">Trusted by teams at</h3>
<!-- /wp:heading -->

<!-- wp:columns {"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"medium"} -->
<figure class="wp-block-image size-medium"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-arcadia.svg' ) ); ?>" alt="Arcadia logo (placeholder)"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"medium"} -->
<figure class="wp-block-image size-medium"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-cobalt.svg' ) ); ?>" alt="Cobalt logo (placeholder)"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"medium"} -->
<figure class="wp-block-image size-medium"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-fernwood.svg' ) ); ?>" alt="Fernwood logo (placeholder)"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"medium"} -->
<figure class="wp-block-image size-medium"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-northbeam.svg' ) ); ?>" alt="Northbeam logo (placeholder)"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"medium"} -->
<figure class="wp-block-image size-medium"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/logo-octave.svg' ) ); ?>" alt="Octave logo (placeholder)"/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
<!-- /wp:awt/section -->
