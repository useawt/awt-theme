<?php
/**
 * Title: AWT — Contact form
 * Slug: awt/contact-form
 * Design system: carbon
 * Description: Single-column contact form with name + email + message + submit. Replace the form's action endpoint with your handler.
 * Categories: awt-theme-section
 * Keywords: contact, form, message, inquiry
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"09","maxWidth":"narrow","ariaLabel":"Contact"} -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Get in touch</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Send us a message. We respond within one business day.</p>
<!-- /wp:paragraph -->

<!-- wp:awt/form -->
<!-- wp:awt/text-input {"label":"Your name","type":"text","required":true} /-->

<!-- wp:awt/text-input {"label":"Email address","type":"email","required":true} /-->

<!-- wp:awt/text-area {"label":"Message","rows":6,"required":true} /-->

<!-- wp:awt/button {"text":"Send message","kind":"primary","size":"lg","type":"submit"} /-->
<!-- /wp:awt/form -->
<!-- /wp:awt/section -->
