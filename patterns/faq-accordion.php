<?php
/**
 * Title: AWT — FAQ accordion
 * Slug: awt/faq-accordion
 * Design system: carbon
 * Description: Heading + collapsible question-and-answer accordion. Emits FAQPage JSON-LD via awt/faq-item for Google rich-result SEO.
 * Categories: awt-section, text
 * Keywords: faq, accordion, questions, schema
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"09","maxWidth":"content","ariaLabel":"Frequently asked questions"} -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Frequently asked questions</h2>
<!-- /wp:heading -->

<!-- wp:awt/accordion {"singleOpen":true} -->
<!-- wp:awt/faq-item {"question":"Is this product really free?","level":"3"} -->
<!-- wp:paragraph -->
<p>Yes. The base product is GPL-2.0-or-later and is published on WordPress.org with no telemetry, no callbacks, and no license keys. Premium adds optional capabilities; the free theme and plugin are the complete product.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/faq-item -->

<!-- wp:awt/faq-item {"question":"What design system does it use?","level":"3"} -->
<!-- wp:paragraph -->
<p>Carbon Design System from IBM. The theme preserves Carbon's class grammar exactly, so anything built against Carbon's CSS works.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/faq-item -->

<!-- wp:awt/faq-item {"question":"Does it support dark mode?","level":"3"} -->
<!-- wp:paragraph -->
<p>Yes. Visitors switch between paired light and dark Carbon variants via the header toggle. Site owners pick the pairing at design time.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/faq-item -->
<!-- /wp:awt/accordion -->
<!-- /wp:awt/section -->
