<?php
/**
 * Title: AWT — Form
 * Slug: awt/carbon-forms
 * Design system: carbon
 * Description: Carbon form-layout pattern. Stacked labelled inputs + checkbox + submit. Foundation for any form scenario.
 * Categories: awt-section
 * Keywords: form, input, signup, register
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"09","maxWidth":"narrow"} -->
<!-- wp:heading {"level":2} -->
<h2 class="wp-block-heading">Create an account</h2>
<!-- /wp:heading -->

<!-- wp:awt/form -->
<!-- wp:awt/text-input {"label":"Full name","type":"text","required":true,"helperText":"Your real name as you want it displayed."} /-->

<!-- wp:awt/text-input {"label":"Email address","type":"email","required":true,"helperText":"We will only contact you about your account."} /-->

<!-- wp:awt/password-input {"label":"Password","required":true,"helperText":"At least 12 characters."} /-->

<!-- wp:awt/checkbox {"label":"I agree to the terms of service.","required":true} /-->

<!-- wp:awt/button {"text":"Create account","kind":"primary","size":"lg","type":"submit"} /-->
<!-- /wp:awt/form -->
<!-- /wp:awt/section -->
