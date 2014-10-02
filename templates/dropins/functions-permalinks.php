<?php
/**
* Functions: Permalinks
*
* These functions help with rewrite rules and permalinks.
*
* @package WordPress
* @subpackage WPx 
* @since 1.0
*/

/*
|--------------------------------------------------------------------------
/**
 * Filter Custom Post Type Permalinks
 *
 *
 * @since 1.0
 * @param array $atts
 * @param string $content
 *
 */
function wpx_taxonomy_permalinks($link, $post) {
	
	// only filter on products
	if ($post->post_type != 'products')
		return $link;

	// replace a taxonomy token (genres, for example) with genres term
	if ($cats = get_the_terms($post->ID, 'genres'))
		$link = str_replace('%genres%', array_pop($cats)->slug, $link);
		return $link;
}
add_filter('post_type_link', 'wpx_taxonomy_permalinks', 10, 2);

/*
|--------------------------------------------------------------------------
/**
 * Extend Pagination Rewrite Rules for Custom Post Types
 *
 * Corrects pagination rewrite rules for custom post types if the URL has been
 * extended to match /post-type/taxonomy/post-name/ for the given post type.
 *
 * @since 1.0
 *
 */
function wpx_taxonomy_pagination( $wp_rewrite ) {

	// unset the normal pagination for books
	unset($wp_rewrite->rules['books/([^/]+)/page/?([0-9]{1,})/?$']);
	
	// add in the taxonomy
	$wp_rewrite->rules = array(
		'books/?$' => $wp_rewrite->index . '?post_type=books',
		'books/page/?([0-9]{1,})/?$' => $wp_rewrite->index . '?post_type=books&paged=' . $wp_rewrite->preg_index( 1 ),
		'books/([^/]+)/page/?([0-9]{1,})/?$' => $wp_rewrite->index . '?genres=' . $wp_rewrite->preg_index( 1 ) . '&paged=' . $wp_rewrite->preg_index( 2 ),
	) + $wp_rewrite->rules;
}

add_action( 'generate_rewrite_rules', 'wpx_taxonomy_pagination' );

/*
|--------------------------------------------------------------------------
/**
 * Change the Author Base
 *
 * In some cases we may want the Author base to be something other than "author."
 * Use the function below to rewrite the author base. 
 *
 * @since 1.0
 *
 */
function wpx_author_base() {
	global $wp_rewrite;
	$author_base = "subscriber";
	$wp_rewrite->init();
	$wp_rewrite->author_base = $author_base;
}
add_action('init', 'wpx_author_base');

/*
|--------------------------------------------------------------------------
/**
 * Insert New Rewrite Rules
 *
 * Here is some sample code to extend the rewrite rules for a particular page
 * to include alphabetical paths (/bands/a/ through /bands/z/ for example).
 * What this does is transform the query string into a pretty permalink, and allow
 * you to then interpret the transformed path as a global variable $alpha in the page-bands.php template
 * (in this example). 
 *
 * @since 1.0
 *
 */
function wpx_rewrite_rules($rules) {
	$newrules = array();
	$newrules['(bands)/(.*)$'] = 'index.php?pagename=$matches[1]&alpha=$matches[2]';
	return $newrules + $rules;
}

function wpx_rewrite_queries($vars) {
	array_push($vars, 'alpha');
	return $vars;
}

add_filter('rewrite_rules_array','wpx_rewrite_rules');
add_filter('query_vars','wpx_rewrite_queries');