<?php
/**
 * The Header for our theme.
 *
 * @package WordPress
 * @subpackage MyTheme
 * @since MyTheme 1.0
 */
?><!DOCTYPE HTML>
<html <?php language_attributes(); ?>>
<head>

	<title><?php wp_title( '|', true, 'right' ); ?></title>

	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

	<link rel="icon" href="<?php bloginfo('url'); ?>/favicon.png">
	<!--[if IE]><link rel="shortcut icon" href="<?php bloginfo('url'); ?>/favicon.ico"><![endif]-->
	<meta name="msapplication-TileColor" content="#ccff00">
	<meta name="msapplication-TileImage" content="<?php bloginfo('url'); ?>/tileicon.png">

	<link rel="profile" href="http://gmpg.org/xfn/11">
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

	<!-- build:css /wp-content/themes/mytheme/assets/styles/screen.min.css -->
	<link rel="stylesheet" href="<?php bloginfo('template_url'); ?>/assets/styles/screen.css">
	<!-- endbuild -->

	<?php wp_head(); ?>

	<!--[if IE]><link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo('template_url'); ?>/assets/styles/ie.css"><![endif]-->
	<!--[if IE 8]><link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo('template_url'); ?>/assets/styles/ie8.css"><![endif]-->
	<!--[if IE 9]><link rel="stylesheet" type="text/css" media="all" href="<?php bloginfo('template_url'); ?>/assets/styles/ie9.css"><![endif]-->

	<script type="text/javascript">

		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
		(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
		m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','//www.google-analytics.com/analytics.js','ga');
		ga('create', 'UA-XXXXXXXX-X', 'mywebsite.com');
		ga('send', 'pageview');

	</script>

</head>

<body <?php body_class(); ?>>