<?php
/**
 * Title: AWT — Error messages
 * Slug: awt/carbon-error-messages
 * Design system: carbon
 * Description: Inline + page-level error compositions. Uses awt/notification at error severity with descriptive copy.
 * Categories: awt-theme-section
 * Keywords: error, alert, notification, failure
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"07","maxWidth":"content"} -->
<!-- wp:awt/notification {"kind":"error","title":"Couldn't load resource","subtitle":"The server returned a 502. Try again in a few minutes or contact support if it persists.","lowContrast":true,"hideCloseButton":false} /-->

<!-- wp:awt/notification {"kind":"warning","title":"Some changes weren't saved","subtitle":"Your last edit conflicts with another change. Review the diff before retrying.","lowContrast":true,"hideCloseButton":false} /-->

<!-- wp:awt/notification {"kind":"info","title":"Maintenance scheduled","subtitle":"This service will be unavailable from 02:00 to 03:00 UTC tomorrow.","lowContrast":true,"hideCloseButton":false} /-->
<!-- /wp:awt/section -->
