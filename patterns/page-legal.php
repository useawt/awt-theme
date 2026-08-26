<?php
/**
 * Title: AWT — Page: Legal page shell
 * Slug: awt/page-legal
 * Design system: carbon
 * Description: Scaffold for ToS / Privacy / Accessibility statement / similar pages. Heading + last-updated date + sectioned long-form prose.
 * Categories: awt-theme-section
 * Keywords: page, legal, terms of service, privacy, accessibility statement
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"08","maxWidth":"narrow"} -->
<!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Page title</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"awt-legal-meta"} -->
<p class="awt-legal-meta"><strong>Last updated:</strong> Month DD, YYYY</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Opening paragraph that frames the page. State the purpose plainly. Avoid legalese where possible.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Section 1</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Body content. Repeat the heading + paragraph pattern as needed. Number sections only if the document mandates it (legal contracts typically do, accessibility statements typically don't).</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Section 2</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>More body content.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Contact</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>How to reach you about this document. Include an email and (where required by law) a postal address.</p>
<!-- /wp:paragraph -->
<!-- /wp:awt/section -->
