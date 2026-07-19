<?php
/**
 * Title: AWT — Page: Home (Hero + Feature grid + FAQ)
 * Slug: awt/page-home
 * Design system: carbon
 * Description: Full home-page starter: hero with CTAs, three-column feature grid, and FAQ accordion. Pair it with the "Page without title" template so the hero is the page's main heading.
 * Categories: awt-section
 * Keywords: home, landing, hero, marketing, starter
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"10","maxWidth":"content"} -->
<!-- wp:awt/hero {"version":2} -->
<!-- wp:paragraph {"className":"awt-hero__eyebrow"} -->
<p class="awt-hero__eyebrow">Accessible by default</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"className":"awt-hero__heading"} -->
<h1 class="wp-block-heading awt-hero__heading">Build accessible WordPress sites</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"awt-hero__description"} -->
<p class="awt-hero__description">Carbon Design System components, paired light/dark variants, and a real accessibility commitment, all in a free, GPL-licensed theme.</p>
<!-- /wp:paragraph -->

<!-- wp:awt/inline-set -->
<!-- wp:awt/button {"text":"Get started","kind":"primary","size":"lg"} /-->
<!-- wp:awt/button {"text":"Read the docs","kind":"tertiary","size":"lg"} /-->
<!-- /wp:awt/inline-set -->
<!-- /wp:awt/hero -->
<!-- /wp:awt/section -->

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
