<?php
/**
* Setup
*
* This is a basic template for organizing your initialization of the theme.
* The prefix "wpx" is used just for uniqueness; you could change that to anything.
*
* @package WordPress
* @subpackage Your Theme 
* @since 1.0
*/

/*
|--------------------------------------------------------------------------
 * WPx Setup
|--------------------------------------------------------------------------
*/
function wpx_setup() {

	// set a content width if you haven't in the Dashboard
	// http://codex.wordpress.org/Content_Width
	if ( ! isset( $content_width ) ) $content_width = 960;

	/*
	 * Makes theme available for translation.
	 *
	 * Translations can be added to the /includes/languages/ directory.
	 * If you're building a theme based on Twenty Thirteen, use a find and
	 * replace to change 'twentythirteen' to the name of your theme in all
	 * template files.
	 */
	load_theme_textdomain( 'desassossego', get_template_directory() . '/includes/languages' );

	/*
	|--------------------------------------------------------------------------
	 * Theme Support
	|--------------------------------------------------------------------------
	*/

	// enable featured thumbnail panel for post types
	add_theme_support('post-thumbnails', array( 'page', 'post') );

	// enable feed links
	add_theme_support( 'automatic-feed-links' );
	
	// enable all possible post formats
	add_theme_support( 'post-formats', array('aside','gallery','audio','video','quote','image','chat') );

	// enable excerpt meta box for Pages
	// (this only applies to default post types)
	add_post_type_support('page', 'excerpt');

	/*
	|--------------------------------------------------------------------------
	 * Misc
	|--------------------------------------------------------------------------
	*/

	// hide version #
	remove_action('wp_head', 'wp_generator');

	// disable the Page columns automatically added by WP SEO by Yoast
	add_filter( 'wpseo_use_page_analysis', '__return_false' );

	/*
	|--------------------------------------------------------------------------
	 * Register Custom Menus
	|--------------------------------------------------------------------------
	*/
	register_nav_menus( array(
		'primary' => 'Primary Navigation',
		'utility' => 'Utility Navigation'
	) );
}

add_action( 'after_setup_theme', 'wpx_setup' );

/*
|--------------------------------------------------------------------------
/**
 * Setup Javascript
 *
 * If you are not going to use a JS framework like requireJS under Grunt to set dependencies to 
 * combine and minify your JS, you should allow a plugin like BWP Minify or W3 Total Cache to do this for you.
 * In this case, you'll need to register all your scripts, then enqueue them all in the footer.
 * By doing this through WP, the plugins listed above can combine the scripts and minify them
 * in the order determined by the dependencies you set.
 
 * Note that this method assumes that your all javascript can run at the same time and does not
 * depend on being conditionally loaded. Furthermore, this method follows the philosophy that
 * we should load all our scripts at the outset, so we can cache them for use during the browser
 * session across the entire site. 
 *
 * @since 1.0
 *
 */
function wpx_setup_js() {
	
	// only if we're not in the admin
	if (!is_admin()) {
		
		/*
		|--------------------------------------------------------------------------
		 * Register Scripts
		|--------------------------------------------------------------------------
		*/
		wp_deregister_script('jquery');
		wp_register_script('jquery', get_bloginfo('template_url').'/assets/js/libraries/jquery.js', false, null, true);
		
		/*
		|--------------------------------------------------------------------------
		 * Enqueue Scripts
		|--------------------------------------------------------------------------
		*/
		wp_enqueue_script('jquery');
	}

}

add_action( 'wp_enqueue_scripts', 'wpx_setup_js' );

/*
|--------------------------------------------------------------------------
 * Enqueue Dashboard  Scripts
|--------------------------------------------------------------------------
*/
function wpx_setup_dashboard_js() {

	wp_register_script( 'wpx.dashboard', get_bloginfo('template_url'). '/assets/js/scripts/dashboard.js', array('jquery'), null, true);
	wp_enqueue_script( 'wpx.dashboard' );

}

add_action( 'admin_enqueue_scripts', 'wpx_setup_dashboard_js' );

/*
|--------------------------------------------------------------------------
/**
 * Setup Styles
 *
 * If you are not going to use a front end framework to combine and minify your stylesheets
 * then you should let a plugin like BWP Minify or W3 Total Cache do this for you.
 * In this case, you'll need to register all your styles, then enqueue them all in the header.
 * By doing this through WP, the plugins listed above can combine the styles and minify them
 * in the order determined by the dependencies you set. 
 *
 * This method is compatible with preprocessors like SASS.
 *
 * @since 1.0
 *
 */
function wpx_setup_styles() {
	
	// only if we're not in the admin
	if (!is_admin()) {
		
		/*
		|--------------------------------------------------------------------------
		 * Register Styles
		|--------------------------------------------------------------------------
		*/
		wp_register_style('wpx.screen', get_bloginfo('template_url').'/assets/styles/screen.css', false, null, 'screen');
		
		/*
		|--------------------------------------------------------------------------
		 * Enqueue Scripts
		|--------------------------------------------------------------------------
		*/

		wp_enqueue_style('wpx.screen');
	}

}

add_action( 'wp_print_styles', 'wpx_setup_styles' );

/*
|--------------------------------------------------------------------------
/**
 * Setup Loop
 *
 * The "proper" alternative to query_posts(); use conditionals to alter the main loop
 * on any given template instead of using query_posts().
 *
 * Add a minimum you need !is_admin() and $query->is_main_query() as conditions.
 * To add a parameter, do $query->set('name_of_parameter', $value);
 * Don't forget $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
 * and $query->set('paged', $paged) for pagination.
 *
 * @since 1.0
 *
 */
function wpx_setup_loop( $query ) {}
add_action( 'pre_get_posts', 'wpx_setup_loop' );

/*
|--------------------------------------------------------------------------
 * Custom Functions
|--------------------------------------------------------------------------
*/