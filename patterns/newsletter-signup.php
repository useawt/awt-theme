<?php
/**
 * Title: AWT — Newsletter signup
 * Slug: awt/newsletter-signup
 * Design system: carbon
 * Description: Heading + supporting text + email input + submit. Subscribe section for marketing pages.
 * Categories: awt-section
 * Keywords: newsletter, email, signup, subscribe
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"09","maxWidth":"narrow"} -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Stay in the loop</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>One email per release. New blocks, accessibility-linter changes, and what's coming next. No marketing fluff.</p>
<!-- /wp:paragraph -->

<!-- wp:awt/form -->
<!-- wp:awt/text-input {"label":"Email address","type":"email","placeholder":"you@example.com","required":true} /-->

<!-- wp:awt/button {"text":"Subscribe","kind":"primary","size":"lg","type":"submit"} /-->
<!-- /wp:awt/form -->
<!-- /wp:awt/section -->
