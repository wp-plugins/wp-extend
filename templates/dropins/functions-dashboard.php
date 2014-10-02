<?php
/**
* Functions: Dashboard
*
* Add or remove parts of the Dashboard. These are some quick functions you can drop
* into your functions file to remove or reconfigure the Dashboard.
*
* @package WordPress
* @subpackage WPx 
* @since 1.0
*/

/*
|--------------------------------------------------------------------------
/**
 * Add Custom Columns to Posts
 *
 * Adds new columns in the Dashboard in the Edit Screen for the default Post type.
 * This is so that we can make things more robust for the client when she is 
 * rifling through the list of posts. 
 *
 * @since 1.0
 */
 
// define the posts edit column
function wpx_extend_posts_columns($post_columns) {
	$new_columns['cb'] = '<input type="checkbox" />';
	$new_columns['title'] = 'Title';
	$new_columns['author'] = __('Author');
	$new_columns['date'] = 'Date';
	$new_columns['id'] = 'ID';
	return $new_columns;
}

add_filter('manage_edit-post_columns', 'wpx_extend_posts_columns');

// add the new column to the post edit screen
function wpx_extendPostsList($column_name, $id) {
	global $post;
	switch ($column_name) {
	case 'id':
		echo $post->ID;
	default:
		break;
	}
}

add_action('manage_posts_custom_column', 'wpx_extendPostsList', 10, 2);


/*
|--------------------------------------------------------------------------
/**
 * Add Custom Columns to Pages
 *
 * Adds new columns in the Dashboard in the Edit Screen for the default Post type.
 * This is so that we can make things more robust for the client when she is 
 * rifling through the list of posts. 
 *
 * @since 1.0
 */
 
// define a page edit column
function wpx_extend_pages_columns($page_columns) {
	$new_columns['cb'] = '<input type="checkbox" />';
	$new_columns['title'] = _x('Title', 'column name');
	$new_columns['author'] = __('Author');
	$new_columns['date'] = _x('Date', 'column name');
	$new_columns['id'] = 'ID';
	return $new_columns;
}

add_filter('manage_pages_columns', 'wpx_extend_pages_columns', 10);

// add the new column to the post edit screen
function wpx_extendPagesList($column_name, $id) {
	global $post;
	switch ($column_name) {
	case 'id':
		echo $post->ID;
	default:
		break;
	}
}

add_action('manage_pages_custom_column', 'wpx_extendPagesList', 10, 2);

/*
|--------------------------------------------------------------------------
/**
 * Add Custom Columns to Custom Post Types
 *
 * Adds new columns in the Dashboard in the Edit Screen for a custom post type.
 * This is so that we can make things more robust for the client when she is 
 * rifling through the list of posts. 
 *
 * @since 1.0
 */

function wpx_extend_books_columns($page_columns) {
	$new_columns['cb'] = '<input type="checkbox" />';
	$new_columns['title'] = _x('Title', 'column name');
	$new_columns['genres'] = __('Genres');
	$new_columns['authors'] = __('Authors');
	$new_columns['author'] = __('Author');
	$new_columns['date'] = _x('Date', 'column name');
	$new_columns['id'] = __('ID');
	return $new_columns;
}

add_filter('manage_edit-books_columns', 'wpx_extend_books_columns');

function wpx_extend_books_post_list($column_name, $id) {
	global $post;
	switch ($column_name) {
	case 'id':
		echo $id;
		break;
	case 'genres':
		$media = get_the_terms( $post->ID, 'genres' );
		if ($media) { 
			$count = 0;
			foreach ($media as $medium) {
				if ($count == count($media)-1) { 
					echo '<a href="edit.php?post_type=books&genres='.$medium->slug.'">'.$medium->name.'</a>'; 
				} else {
					echo '<a href="edit.php?post_type=books&genres='.$medium->slug.'">'.$medium->name.'</a>, '; 
				}
				$count++;
			}
		}
	break;
	case 'authors':
		$media = get_the_terms( $post->ID, 'authors' );
		if ($media) { 
			$count = 0;
			foreach ($media as $medium) {
				if ($count == count($media)-1) { 
					echo '<a href="edit.php?post_type=books&authors='.$medium->slug.'">'.$medium->name.'</a>'; 
				} else {
					echo '<a href="edit.php?post_type=books&authors='.$medium->slug.'">'.$medium->name.'</a>, '; 
				}
				$count++;
			}
		}
	break;
	default:
		break;
	} // end switch
}

add_action('manage_books_posts_custom_column', 'wpx_extend_books_post_list', 10, 2);

/*
|--------------------------------------------------------------------------
/**
 * Disable Dashboard Widgets
 *
 * Removes widgets loaded in the Dashboard. 
 *
 * @since 1.0
 *
 */
function wpx_disable_dashboard_widgets() {
	remove_meta_box('dashboard_right_now', 'dashboard', 'core');
	remove_meta_box('dashboard_recent_comments', 'dashboard', 'core');
	remove_meta_box('dashboard_incoming_links', 'dashboard', 'core');
	remove_meta_box('dashboard_plugins', 'dashboard', 'core');
	remove_meta_box('dashboard_quick_press', 'dashboard', 'core');
	remove_meta_box('dashboard_recent_drafts', 'dashboard', 'core');
	remove_meta_box('dashboard_primary', 'dashboard', 'core');
	remove_meta_box('dashboard_secondary', 'dashboard', 'core');
}

add_action('admin_menu', 'wpx_disable_dashboard_widgets');

/*
|--------------------------------------------------------------------------
/**
 * Disable Dashboard Menus
 *
 * Oftentimes we need to hide some menus from the client in WP.
 * This function will remove menus we don't want them to access. 
 *
 * @since 1.0
 *
 */
function wpx_disable_menu_tabs() {
	global $menu;
	$restricted = array();
	// $restricted = array(__('Posts'), __('Comments'), __('Tools'));
	end($menu);
	while (prev($menu)){
		$value = explode(' ',$menu[key($menu)][0]);
		if(is_array($restricted)) { if(in_array($value[0] != NULL?$value[0]:"" , $restricted)){unset($menu[key($menu)]);} }
	}
}

add_action('admin_menu', 'wpx_disable_menu_tabs');

/*
|--------------------------------------------------------------------------
/**
 * Disable Default Media Library Crops
 *
 * WP by default creates three intermediate sizes whenever an image is uploaded
 * to the Media Library. This stops WP from generating these crops. 
 *
 * @since 1.0
 *
*/
function wpx_disable_default_crops($sizes) {
	// if you want to restrict this to a specific post type
	$current_post_type = get_post_type( $_POST['post_id'] );
	if ($current_post_type == 'NAMEOFTYPE') { 
		// send the resize function nothing
		$sizes = array();
		return $sizes;
	} else {
		// do whatever happens normally
		return $sizes;
	}
}

add_filter('intermediate_image_sizes_advanced', 'wpx_disable_default_crops');

/*
|--------------------------------------------------------------------------
/**
 * Disable Default Meta Boxes
 *
 * If we want to remove default meta boxes that WordPress generates by default, we can list them all here
 * by post type, and by positioning (normal or side). 
 *
 * @since 1.0
 *
 */
function wpx_disable_default_metaboxes() {
	// in this case we're removing the custom fields panels
	// custom field metaboxes are already hidden by WP, but here are examples
	remove_meta_box('postcustom','page','normal');
	remove_meta_box('postcustom','post','normal');
	remove_meta_box('postcustom','books','normal');
	remove_meta_box( 'tagsdiv-post_tag', 'post', 'side' );
	remove_meta_box( 'categorydiv', 'post', 'side' );
}

add_action('admin_init','wpx_disable_default_metaboxes');

/*
|--------------------------------------------------------------------------
/**
 * Disable Default Widgets
 *
 * Oftentimes we do not need all the default widgets WP has to offer.
 * We deregister each of the defaults here. 
 *
 * @since 1.0
 *
 */
function wpx_disable_default_widgets() {
	unregister_widget( 'WP_Widget_Calendar' );
	unregister_widget( 'WP_Widget_Categories' );
	unregister_widget( 'WP_Widget_Tag_Cloud' );
	unregister_widget( 'WP_Widget_Pages' );
	unregister_widget( 'WP_Widget_Search' );
	unregister_widget( 'WP_Widget_Archives' );
	unregister_widget( 'WP_Nav_Menu_Widget' );
	unregister_widget( 'WP_Widget_Meta' );
	unregister_widget( 'WP_Widget_Text' );
	unregister_widget( 'WP_Widget_RSS' );
	unregister_widget( 'WP_Widget_Recent_Comments' );
	unregister_widget( 'WP_Widget_Recent_Posts' );
	unregister_widget( 'Akismet_Widget' );
}

add_action( 'widgets_init', 'wpx_disable_default_widgets' );

/*
|--------------------------------------------------------------------------
/**
 * Remove Editor Screens
 *
 * Due to this stupidity: http://core.trac.wordpress.org/ticket/14365 
 * we have to remove screens by hand from the Editor role
 * since we are forced to give the Editor role manage_options capability
 *
 * @since 1.0
 *
 */
function wpx_remove_dashboard_menus() {
	if(!current_user_can('activate_plugins')) {
		remove_menu_page( 'theme_my_login' );
		remove_menu_page( 'edit.php?post_type=wpx_fields' );
		remove_menu_page( 'edit.php?post_type=wpx_types' );
		remove_menu_page( 'edit.php?post_type=wpx_taxonomy' );
		remove_menu_page( 'edit.php?post_type=wpx_options' );
		remove_menu_page( 'edit.php?post_type=api' );
	}
}

function wpx_remove_dashboard_submenus() {
	if(!current_user_can('activate_plugins')) {
		global $submenu;
		unset($submenu['tools.php']);
		// settings page stuff
		unset($submenu['options-general.php'][10]);
		unset($submenu['options-general.php'][15]);
		unset($submenu['options-general.php'][20]);
		unset($submenu['options-general.php'][25]);
		unset($submenu['options-general.php'][30]);
		unset($submenu['options-general.php'][40]);
		unset($submenu['options-general.php'][41]);
		unset($submenu['options-general.php'][42]);
		unset($submenu['admin.php?page=jetpack'][42]);
	}
}

add_action('admin_init', 'wpx_remove_dashboard_submenus');
add_action( 'admin_menu', 'wpx_remove_dashboard_menus' );

/*
|--------------------------------------------------------------------------
/**
 * Remove Default User Meta
 *
 * Jabber is definitely outdated; you decide if the others are too.
 *
 * @since 1.0
 *
 */
function wpx_remove_default_usermeta( $contactmethods ) {
	unset($contactmethods['aim']);
	unset($contactmethods['jabber']);
	unset($contactmethods['yim']);
	return $contactmethods;
}

add_filter('user_contactmethods','wpx_remove_default_usermeta', 10, 1);