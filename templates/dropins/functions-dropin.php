<?php
/**
* Functions: Dropin
*
* These are functions you can drop in on an as-needed basis.
*
* @package    WordPress
* @subpackage Sandbox 
* @since 1.0
*/

/*
|--------------------------------------------------------------------------
/**
 * Remove Inline Gallery CSS
 *
 * This filter will stop WP from generating inline styles when galleries are
 * inserted in the Visual Editor. 
 *
 * @since 1.0
 *
 */
add_filter( 'use_default_gallery_style', '__return_false' );

/*
|--------------------------------------------------------------------------
/**
 * Add New Shortcode
 *
 * Sample code to add new shortcodes for use in the Visual Editor.
 * In this example, a blockquote with a citation is rendered using the following
 * shortcode: [blockquote alignment="right" source="Name of Citation"] 
 *
 * @since 1.0
 * @param array $atts
 * @param string $content
 *
 */
function wpx_my_shortcode( $atts, $content = null ) {
	extract( shortcode_atts( array( 'alignment' => 'right', 'source' => '', 'quote' => '' ), $atts ) );
	return '<blockquote class="'.$alignment.'"><p>'.$quote.'</p><p><cite>'.$source.'</cite></p></blockquote>';
}

add_shortcode( 'blockquote', 'wpx_my_shortcode' );

/*
|--------------------------------------------------------------------------
/**
 * Add Custom Post Types to the Feeds
 *
 * Here we can specify what post types should be included in RSS feeds. 
 *
 * @since 1.0
 *
 */
function wpx_custom_feeds($qv) {
	if (isset($qv['feed']) && !isset($qv['post_type']))
		$qv['post_type'] = array('post','books');
	return $qv;
}

add_filter('request', 'wpx_custom_feeds');

/*
|--------------------------------------------------------------------------
/**
 * Extend Search to Include Custom Post Types
 *
 * We can set the array below to include whatever post types we like.
 * These will then be searched by WP's built in search feature. 
 *
 * @since 1.0
 *
 */
function wpx_custom_search($query) {
	if ($query->is_search) {
		$query->set('post_type', array('post'));
	}
	return $query;
}

add_filter('pre_get_posts','wpx_custom_search');

/*
|--------------------------------------------------------------------------
/**
 * Extend Body Class
 *
 * Add new classes to the body class based on various conditions. 
 *
 * @since 1.0
 */
function wpx_body_class($classes) {
	if ($some_condition) {
		$classes[] = 'new-body-class';
	}
	return $classes;
}

add_filter('body_class','wpx_body_class');

/*
|--------------------------------------------------------------------------
/**
 * Globally Redefine Excerpt Length
 *
 * Add new classes to the body class based on various conditions. 
 *
 * @since 1.0
 */
function wpx_redefine_excerpt_length($length) {
	// change by number of words
	return 20; 
}

add_filter('excerpt_length', 'wpx_redefine_excerpt_length');

/*
|--------------------------------------------------------------------------
/**
 * Extend Excerpts with Ellipsis
 *
 * Changes the [...] ellipsis after get_excerpt() and the_excerpt() output and
 * allows for the inclusion of a link to the post. 
 *
 * @since 1.0
 */

function wpx_excerpt_ellipsis() {
	return '&nbsp;&hellip; <a href="'. get_permalink() . '">' . 'Read More'. '</a>';
}

// alters automatically generated excerpt
function wpx_auto_excerpt( $more ) {
	return ' &hellip;' . wpx_excerpt_ellipsis();
}
add_filter( 'excerpt_more', 'wpx_auto_excerpt' );

// alters the get_the_excerpt() output
function wpx_get_excerpt( $output ) {
	if ( has_excerpt() && ! is_attachment() ) {
		$output .= wpx_excerpt_ellipsis();
	}
	return $output;
}
add_filter( 'get_the_excerpt', 'wpx_get_excerpt' );

/*
|--------------------------------------------------------------------------
/**
 * Rename Post Formats
 *
 * If you would like to change the nomenclature of post formats, you may do so here.
 *
 * @since 1.0
 *
 */
function wpx_rename_post_formats($translation, $text, $context, $domain) {
	$names = array(
		'Audio'  => 'Podcast',
		'Standard' => 'Blog Post',
		'Aside' => 'Article'
	);
	if ($context == 'Post format') {
		$translation = str_replace(array_keys($names), array_values($names), $text);
	}
	return $translation;
}
add_filter('gettext_with_context', 'wpx_rename_post_formats', 10, 4);

/*
|--------------------------------------------------------------------------
/**
 * Extend Custom Walker
 *
 * Let's add the post_name value to LI elements in any dynamic menu.
 *
 * @since 1.0
 *
 */
class wpx_custom_nav_walker extends Walker_Nav_Menu {
	// add main/sub classes to li's and links
	function start_el( &$output, $item, $depth, $args ) {
		global $wp_query;

		$indent = ( $depth > 0 ? str_repeat( "\t", $depth ) : '' ); // code indent
		// depth dependent classes
		$depth_classes = array(
			( $depth == 0 ? 'main-menu-item' : 'sub-menu-item' ),
			( $depth >=2 ? 'sub-sub-menu-item' : '' ),
			( $depth % 2 ? 'menu-item-odd' : 'menu-item-even' ),
			'menu-item-depth-' . $depth
		);
		$depth_class_names = esc_attr( implode( ' ', $depth_classes ) );

		// passed classes
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$class_names = esc_attr( implode( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) ) );

		// build html
		$output .= $indent . '<li id="nav-menu-item-'. $item->ID . '" class="'. $item->post_name .' ' . $depth_class_names . ' ' . $class_names . '">';

		// link attributes
		$attributes  = ! empty( $item->attr_title ) ? ' title="'  . esc_attr( $item->attr_title ) .'"' : '';
		$attributes .= ! empty( $item->target )     ? ' target="' . esc_attr( $item->target     ) .'"' : '';
		$attributes .= ! empty( $item->xfn )        ? ' rel="'    . esc_attr( $item->xfn        ) .'"' : '';
		$attributes .= ! empty( $item->url )        ? ' href="'   . esc_attr( $item->url        ) .'"' : '';
		$attributes .= ' class="menu-link ' . ( $depth > 0 ? 'sub-menu-link' : 'main-menu-link' ) . '"';

		$item_output = sprintf( '%1$s<a%2$s>%3$s%4$s%5$s</a>%6$s',
			$args->before,
			$attributes,
			$args->link_before,
			apply_filters( 'the_title', $item->title, $item->ID ),
			$args->link_after,
			$args->after
		);

		// build html
		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
}

/*
|--------------------------------------------------------------------------
/**
 * Custom User Fields
 *
 * Allows the saving of custom user meta fields.
 * Can be used in conjunction with Theme My Login plugin.
 *
 * @since 1.0
 *
 */
add_action( 'personal_options_update', 'wpx_custom_user_fields' );
add_action( 'edit_user_profile_update', 'wpx_custom_user_fields' );

function wpx_custom_user_fields( $user_id ) {
	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;
	update_usermeta( $user_id, 'facebook', $_POST['facebook'] );
	update_usermeta( $user_id, 'twitter', $_POST['twitter'] );
	update_usermeta( $user_id, 'title', $_POST['title'] );
	update_usermeta( $user_id, 'favorites', $_POST['favorites'] );
}

?>