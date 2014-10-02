<?php
/**
 * The template for displaying the footer.
 *
 * @package WordPress
 * @subpackage MyTheme
 * @since MyTheme 1.0
 */
?>

	<!-- build:js /wp-content/themes/mytheme/assets/js/app.min.js -->
	<script src="<?php bloginfo('template_url'); ?>/js/libraries/jquery.js"></script>
	<script src="<?php bloginfo('template_url'); ?>/js/scripts/common.js"></script>
	<script src="<?php bloginfo('template_url'); ?>/js/scripts/init.js"></script>
	<!-- endbuild -->

	<script>var SITE_ROOT = '<?php bloginfo('url'); ?>';</script>
	<script>var SITE_ASSETS = '<?php bloginfo('template_url'); ?>';</script>

<?php wp_footer(); ?>

</body>
</html>