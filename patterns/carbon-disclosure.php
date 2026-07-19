<?php
/**
 * Title: AWT — Disclosure (Carbon pattern)
 * Slug: awt/carbon-disclosure
 * Design system: carbon
 * Description: Collapsible sections built on Carbon's accordion. Use for FAQ-style content where space is scarce.
 * Categories: awt-section
 * Keywords: disclosure, accordion, collapsible, expand
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/accordion {"singleOpen":false} -->
<!-- wp:awt/accordion-item {"title":"Section one"} -->
<!-- wp:paragraph -->
<p>The disclosure pattern hides supporting detail behind a click. Use it for content the reader may not need on first scan, like FAQ answers or expanded specifications.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/accordion-item -->

<!-- wp:awt/accordion-item {"title":"Section two"} -->
<!-- wp:paragraph -->
<p>Each disclosure item carries its own keyboard target. Tab navigates between rows; Enter and Space toggle the active row.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/accordion-item -->

<!-- wp:awt/accordion-item {"title":"Section three"} -->
<!-- wp:paragraph -->
<p>Set the parent accordion's singleOpen to true to make this behave like a radio group: only one row open at a time.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/accordion-item -->
<!-- /wp:awt/accordion -->
