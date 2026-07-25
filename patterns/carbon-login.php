<?php
/**
 * Title: AWT — Login
 * Slug: awt/carbon-login
 * Design system: carbon
 * Description: Centered login form with username + password + submit + recovery link.
 * Categories: awt-section
 * Keywords: login, signin, authentication, account
 * Block Types: core/post-content
 * Inserter: yes
 */
?>
<!-- wp:awt/section {"paddingBlock":"10","maxWidth":"narrow"} -->
<!-- wp:heading {"level":1,"textAlign":"center"} -->
<h1 class="wp-block-heading has-text-align-center">Sign in</h1>
<!-- /wp:heading -->

<!-- wp:awt/form -->
<!-- wp:awt/text-input {"label":"Username or email","type":"text","required":true} /-->

<!-- wp:awt/password-input {"label":"Password","required":true} /-->

<!-- wp:awt/button {"text":"Sign in","kind":"primary","size":"lg","type":"submit"} /-->
<!-- /wp:awt/form -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><a href="#">Forgot password?</a></p>
<!-- /wp:paragraph -->
<!-- /wp:awt/section -->
