<?php
/**
 * Title: AWT — Pricing table
 * Slug: awt/pricing-table
 * Design system: carbon
 * Description: 3-tier pricing tile row + feature-comparison matrix below. Modelled on IBM's Edition options and features pattern.
 * Categories: awt-theme-section
 * Keywords: pricing, plans, tiers, comparison, editions
 * Block Types: core/post-content
 * Inserter: yes
 */

// Build the comparison-table block markup with boolean cells per column.
$comparison_headers = array(
	array(
		'key'      => 'feature',
		'text'     => 'Feature',
		'cellType' => 'text',
	),
	array(
		'key'      => 'essentials',
		'text'     => 'Essentials',
		'cellType' => 'boolean',
	),
	array(
		'key'      => 'standard',
		'text'     => 'Standard',
		'cellType' => 'boolean',
	),
	array(
		'key'      => 'premium',
		'text'     => 'Premium',
		'cellType' => 'boolean',
	),
);

$comparison_rows = array(
	array(
		'feature'    => 'Core feature one',
		'essentials' => true,
		'standard'   => true,
		'premium'    => true,
	),
	array(
		'feature'    => 'Core feature two',
		'essentials' => true,
		'standard'   => true,
		'premium'    => true,
	),
	array(
		'feature'    => 'Advanced capability',
		'essentials' => false,
		'standard'   => true,
		'premium'    => true,
	),
	array(
		'feature'    => 'Higher usage quota',
		'essentials' => false,
		'standard'   => true,
		'premium'    => true,
	),
	array(
		'feature'    => 'Dedicated customer success manager',
		'essentials' => false,
		'standard'   => false,
		'premium'    => true,
	),
	array(
		'feature'    => '24/7 priority support',
		'essentials' => false,
		'standard'   => false,
		'premium'    => true,
	),
);

$dt_attrs = wp_json_encode(
	array(
		'headers'              => $comparison_headers,
		'rows'                 => $comparison_rows,
		'size'                 => 'md',
		'zebra'                => false,
		'useStaticWidth'       => false,
		'stickyHeader'         => false,
		'sortable'             => false,
		'defaultSortKey'       => '',
		'defaultSortDirection' => 'asc',
		'caption'              => 'Edition options and features',
	)
);
?>
<!-- wp:awt/section {"paddingBlock":"09","maxWidth":"content","ariaLabel":"Pricing"} -->
<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:awt/pricing-tile {"tierName":"Essentials","price":"$49","pricePeriod":"/month","description":"Get started with the core feature set. Ideal for small teams and side projects.","ctaText":"Choose Essentials","ctaKind":"tertiary"} /--></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:awt/pricing-tile {"tierName":"Standard","price":"$149","pricePeriod":"/month","description":"Advanced capabilities and higher quotas. Built for growing organisations.","ctaText":"Choose Standard","ctaKind":"primary","featured":true,"badge":"Most popular"} /--></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:awt/pricing-tile {"tierName":"Premium","price":"Custom","description":"Dedicated success manager and 24/7 priority support. For enterprise needs.","ctaText":"Contact sales","ctaKind":"tertiary"} /--></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:awt/data-table <?php echo $dt_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON for block-comment attrs; values are static, author-controlled. ?> /-->
<!-- /wp:awt/section -->
