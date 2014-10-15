<?php
/**
 * Register WPX Cpts
 *
 * This class registers all the internal WPX CPTs.
 *
 * @package wpx
 * 
*/

class wpx_cpts {

	protected static $instance;

	public static function init() {
		is_null( self::$instance ) AND self::$instance = new self;
		return self::$instance;
	}

	function __construct() {

		// register internal wpx cpts
		add_action( 'init', array($this,'register_wpx_fields') );
		add_action( 'init', array($this,'register_wpx_post_types') );
		add_action( 'init', array($this,'register_wpx_options') );
		add_action( 'init', array($this,'register_wpx_taxonomies') );

		// attach custom columns for admin for internal cpts
		add_filter('manage_edit-wpx_taxonomy_columns', array($this,'extend_taxonomies_columns'));
		add_action('manage_wpx_taxonomy_posts_custom_column', array($this,'extend_taxonomies_post_list'), 10, 2);
		add_filter('manage_edit-wpx_options_columns', array($this,'extend_options_columns'));
		add_action('manage_wpx_options_posts_custom_column', array($this,'extend_options_post_list'), 10, 2);
		add_filter('manage_edit-wpx_types_columns', array($this,'extend_cpts_columns'));
		add_action('manage_wpx_types_posts_custom_column', array($this,'extend_cpts_post_list'), 10, 2);
		add_filter('manage_edit-wpx_fields_columns', array($this,'extend_fields_columns'));
		add_action('manage_wpx_fields_posts_custom_column', array($this,'extend_fields_post_list'), 10, 2);

	}

	/**
	 * Register WPX_Fields
	 *
	 * @since 1.0
	*/
	public static function register_wpx_fields() {

		// define metaboxes 
		$metaboxes = array( 
			'Configuration' => array(
				array('order'=>1000),
				array( 'id'=>'_wpx_fields_type', 'label'=>'Type', 'description'=>'After selecting your field type and saving, you may be presented with additional configuration options specific to the field type you selected.', 'field'=>'wpx_select_types', 'required'=>true),
				array( 'id'=>'_wpx_fields_label', 'label'=>'Label', 'description'=>'This is the label that appears above the metabox.', 'field'=>'text', 'required'=>true),
				array( 'id'=>'_wpx_fields_description', 'label'=>'Description', 'description'=>'Additional information describing this field and/or instructions on how to enter the content.', 'field'=>'textarea', 'required'=>false),
				//@todo array( 'id'=>'_wpx_fields_required', 'label'=>'Required', 'description'=>'Check this box if the field is required.', 'field'=>'checkbox', 'required'=>false)
			),
			'Relationship Options' => array(
				array('order'=>1),
				array( 'id'=>'_wpx_fields_post_multiple', 'label'=>'Multiple Selection?', 'description'=>'Check this box if the user should be able to choose more than 1 post from the relationship.', 'field'=>'checkbox', 'required'=>false),
				array( 'id'=>'_wpx_fields_post_objects', 'label'=>'Data Source', 'description'=>'Choose the post types the user may choose from.', 'field'=>'wpx_select_object_type', 'required'=>false)
			),
			'User Options' => array(
				array('order'=>1),
				array( 'id'=>'_wpx_fields_user_multiple', 'label'=>'Multiple Selection?', 'description'=>'Check this box if the user should be able to choose more than 1 user from the relationship.', 'field'=>'checkbox', 'required'=>false),
				array( 'id'=>'_wpx_fields_user_roles', 'label'=>'Data Source', 'description'=>'Choose the user roles the user may choose from.', 'field'=>'wpx_select_user_roles', 'required'=>false)
			),
			'Taxonomy Options' => array(
				array('order'=>1),
				array( 'id'=>'_wpx_fields_term_multiple', 'label'=>'Multiple Selection?', 'description'=>'Check this box if the user should be able to choose more than 1 term from the relationship.', 'field'=>'checkbox', 'required'=>false),
				array( 'id'=>'_wpx_fields_term_objects', 'label'=>'Data Source', 'description'=>'Choose the taxonomies the user may choose from.', 'field'=>'wpx_select_taxonomies', 'required'=>false)
			),
			'Gallery Options' => array(
				array('order'=>1),
				array( 'id'=>'_wpx_fields_gallery_cpt', 'label'=>'Data Source', 'description'=>'Choose which post type will contain your galleries for this field.', 'field'=>'wpx_select_object_type', 'required'=>false)
			)
		);

		// define groups taxonomy
		$groups = array(
			'wpx_groups', 
			array(
				'label_singular' => 'Group',
				'label_plural' => 'Groups',
				'show_in_nav_menus'=>false,
				'object_type' => array('wpx_fields'),
				'register_metaboxes' => array(
					array( 'id'=>'_wpx_groups_collapsed', 'label'=>'Collapse Tab?', 'description'=>'Should the metabox be collapsed by default?', 'field'=>'checkbox', 'required'=>false),
					array( 'id'=>'_wpx_groups_order', 'label'=>'Order', 'description'=>'Enter a number to define the order in which this Group should appear in a post type. Use increments of 5 to account for future Groups, and remember that higher numbers mean closer to the top of the page.', 'field'=>'number', 'required'=>false)
				)
			),
		);

		$fields = new wpx_register_type(
			'wpx_fields', 
			array(
				'label_singular' => 'Meta Field',
				'label_plural' => 'Meta Fields',
				'hierarchical' => true,
				'supports' => array('title','page-attributes'),
				'register_taxonomies' => array($groups),
				'register_metaboxes' => $metaboxes,
				'delete_with_user'=> false,
				'show_ui'=> true,
				'public'=>true,
				'publicly_queryable'=>false,
				'show_in_nav_menus'=>false,
				'show_in_menu'=> false,
				'show_in_admin_bar'=>false,
				'capabilities' => array(
					'publish_posts' => 'manage_options',
					'edit_posts' => 'manage_options',
					'edit_others_posts' => 'manage_options',
					'delete_posts' => 'manage_options',
					'delete_others_posts' => 'manage_options',
					'read_private_posts' => 'manage_options',
					'edit_post' => 'manage_options',
					'delete_post' => 'manage_options',
					'read_post' => 'manage_options',
				)
			)
		);

	}

	/**
	 * Register WPX_Types
	 *
	 * @since 1.0
	*/
	public static function register_wpx_post_types() {

		$metaboxes = array(
			'Name' => array(
				array('order'=>40),
				array( 'id'=>'_wpx_cpt_label_plural', 'label'=>'Plural Label', 'description'=>'What is the post type called in the plural case? If you enter both a Plural and Singular Label, the rest of the Label fields will be filled out for you.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_label_singular', 'label'=>'Singular Label', 'description'=>'What is the post type called in the singular case? If you enter both a Plural and Singular Label, the rest of the Label fields will be filled out for you.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_label_description', 'label'=>'Description', 'description'=>'A short descriptive summary of what the post type is. This field does not appear visually in the Dashboard.', 'field'=>'text', 'required'=>false)
			),
			'Metaboxes' => array(
				array( 'collapsed' => true, 'order'=>35),
				array( 'id'=>'_wpx_cpt_metaboxes', 'label'=>'Fields', 'description'=>'Select the fields that you would like to assign to this post type. Each field will appear in a metabox that is defined by the Group to which you have assigned the field. To call a field, use get_post_meta($post->ID, \'_cpt_nameOfField\'); where "cpt" is the name of this custom post type and "nameOfField" is the slug of the WPX meta field.', 'field'=>'wpx_select_fields', 'required'=>false),
				array( 'id'=>'_wpx_cpt_supports', 'label'=>'Supports', 'description'=>'An alias for calling add_post_type_support() directly. Title and Editor are active by default; uncheck these to hide them.', 'field'=>'wpx_select_supports', 'required'=>false)
			),
			'Labels' => array(
				array( 'collapsed' => true, 'order'=>30),
				array( 'id'=>'_wpx_cpt_name', 'label'=>'Name', 'description'=>'General name for the post type, usually plural.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_singular_name', 'label'=>'Singular Name', 'description'=>'Name for one object of this post type. Defaults to value of name.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_menu', 'label'=>'Menu Name', 'description'=>'The menu name text. This string is the name to give menu items.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_all', 'label'=>'All Items', 'description'=>'The all items text used in the menu.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_add_new', 'label'=>'Add New', 'description'=>'The add new text. The default is Add New for both hierarchical and non-hierarchical types.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_add_new_item', 'label'=>'Add New Item', 'description'=>'The add new item text. Default is Add New Post/Add New Page.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_edit_item', 'label'=>'Edit Item', 'description'=>'The edit item text. Default is Edit Post/Edit Page.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_new_item', 'label'=>'New Item', 'description'=>'The new item text. Default is New Post/New Page.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_view_item', 'label'=>'View Item', 'description'=>'The view item text. Default is View Post/View Page.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_search_items', 'label'=>'Search Items', 'description'=>'The search items text. Default is Search Posts/Search Pages.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_not_found', 'label'=>'Not Found', 'description'=>'The not found text. Default is No posts found/No pages found.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_not_found_in_trash', 'label'=>'Not Found in Trash', 'description'=>'The not found in trash text. Default is No posts found in Trash/No pages found in Trash.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_parent_item', 'label'=>'Parent Item', 'description'=>'the parent text. This string isn\'t used on non-hierarchical types. In hierarchical ones the default is Parent Page.', 'field'=>'text', 'required'=>false)
			),
			'Relationships' => array(
				array( 'collapsed' => true, 'order'=>25),
				array( 'id'=>'_wpx_cpt_hierarchical', 'label'=>'Hierarchical', 'description'=>'Whether the post type is hierarchical. Allows parent to be specified. The "supports" parameter should contain "page-attributes" to show the parent select box on the editor page.', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_cpt_taxonomies', 'label'=>'Taxonomies', 'description'=>'Attaches registered taxonomies to this post type.', 'field'=>'wpx_select_taxonomies', 'required'=>false),
				array( 'id'=>'_wpx_cpt_has_archive', 'label'=>'Has Archive', 'description'=>'Enables post type archives. Will use $post_type as archive slug by default.', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_cpt_delete_with_user', 'label'=>'Delete with user?', 'description'=>'Whether to delete posts of this type when deleting a user. If "true," posts of this type belonging to the user will be moved to trash when then user is deleted. If "false," posts of this type belonging to the user will *not* be trashed or deleted. If not set (the default), posts are trashed if post_type_supports(\'author\'). Otherwise posts are not trashed or deleted. (Defaults to null.)', 'field'=>'wpx_states', 'required'=>false)
			),
			'UI Settings' => array(
				array( 'collapsed' => true, 'order'=>20),
				array( 'id'=>'_wpx_cpt_show_ui', 'label'=>'Show UI', 'description'=>'Whether to generate a default UI for managing this post type in the admin.', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_cpt_show_in_nav_menus', 'label'=>'Show in Nav Menus', 'description'=>'Whether post_type is available for selection in navigation menus.', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_cpt_show_in_menu', 'label'=>'Show in Menu', 'description'=>'Where to show the post type in the admin menu. Remember that show_ui must be true. If an existing top level page such as \'tools.php\' or \'edit.php?post_type=page\', the post type will be placed as a sub menu of that. When using \'some string\' to show as a submenu of a menu page created by a plugin, this item will become the first submenu item, and replace the location of the top level link. If this isn\'t desired, the plugin that creates the menu page needs to set the add_action priority for admin_menu to 9 or lower.', 'field'=>'wpx_text', 'required'=>false, 'value'=>"true"),
				array( 'id'=>'_wpx_cpt_show_in_admin_bar', 'label'=>'Show in Admin Bar', 'description'=>'Whether to make this post type available in the WordPress admin bar.', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_cpt_menu_position', 'label'=>'Menu Position', 'description'=>'The position in the menu order the post type should appear. You can use decimals here for more distance. The parameter "show_in_menu" must be true.', 'field'=>'number', 'required'=>false),
				array( 'id'=>'_wpx_cpt_menu_icon', 'label'=>'Menu Icon', 'description'=>'The url to the icon to be used for this menu. Examples: \'dashicons-video-alt\' or the full path to an icon. (For this field you cannot enter PHP using WPX.)', 'field'=>'text', 'required'=>false)
			),
			'Query Settings' => array(
				array( 'collapsed' => true, 'order'=>15),
				array( 'id'=>'_wpx_cpt_public', 'label'=>'Public', 'description'=>'Controls how the type is visible to authors (show_in_nav_menus, show_ui) and readers (exclude_from_search, publicly_queryable). Default: false. True implies exclude_from_search, publicly_queryable, show_in_nav_menus, and show_ui. The built-in types attachment, page, and post are similar to this. False implies exclude_from_search: false, publicly_queryable: false, show_in_nav_menus: false, and show_ui: false. The built-in types nav_menu_item and revision are similar to this. Best used if you\'ll provide your own editing and viewing interfaces (or none at all). If no value is specified for exclude_from_search, publicly_queryable, show_in_nav_menus, or show_ui, they inherit their values from public.', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_cpt_exclude_from_search', 'label'=>'Exclude from Search', 'description'=>'Whether to exclude posts with this post type from front end search results.', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_cpt_publicly_queryable', 'label'=>'Publicly Queryable', 'description'=>' Whether queries can be performed on the front end as part of parse_request().', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_cpt_query_var', 'label'=>'Query Var', 'description'=>'Defaults to "true," and will use the post slug. If you enter “false” it will disable query_var key use. A post type cannot be loaded at /?{query_var}={single_post_slug}. True if you enter a string.', 'field'=>'wpx_text', 'required'=>false)
			),
			'Capabilities' => array(
				array( 'collapsed' => true, 'order'=>10),
				array( 'id'=>'_wpx_cpt_capability_type', 'label'=>'Capability Type', 'description'=>'The string to use to build the read, edit, and delete capabilities. May be passed as an array to allow for alternative plurals when using this argument as a base to construct the capabilities, e.g. array(\'story\', \'stories\'). To pass an array, enter each array key separated by a comma. By default the capability_type is used as a base to construct capabilities. It seems that "map_meta_cap" needs to be set to true, to make this work.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_capabilities', 'label'=>'Capabilities', 'description'=>'An array of the capabilities for this post type.', 'field'=>'wpx_capabilities', 'required'=>false),
				array( 'id'=>'_wpx_cpt_map_meta_cap', 'label'=>'Meta Capability Mapping', 'description'=>'Whether to use the internal default meta capability handling.', 'field'=>'wpx_states', 'required'=>false)
			),
			'Permalink Settings' => array(
				array( 'collapsed' => true, 'order'=>00),
				array( 'id'=>'_wpx_cpt_permalink_epmask', 'label'=>'Permalink EP Mask', 'description'=>'The default rewrite endpoint bitmasks. For more info see Trac Ticket 12605 and this Make WordPress Plugins summary of endpoints. In 3.4, this argument is effectively replaced by the "ep_mask" argument under rewrite.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_cpt_rewrite', 'label'=>'Rewrite', 'description'=>'Triggers the handling of rewrites for this post type. To prevent rewrites, set to false. Defaults to "true" and uses $post_type as slug.', 'field'=>'wpx_cpt_rewrite', 'required'=>false),
				array( 'id'=>'_wpx_cpt_can_export', 'label'=>'Can Export', 'description'=>'Can this post_type be exported?', 'field'=>'wpx_states', 'required'=>false)
			)
		);

		$fields = new wpx_register_type(
			'wpx_types',
			array(
				'description' => 'Each post in this post type represents a custom post type.',
				'label_singular' => 'Post Type',
				'label_plural' => 'Post Types',
				'supports' => array('title', 'permalink'),
				'exclude_from_search'=> true,
				'show_ui'=> true,
				'show_in_menu' => false,
				'delete_with_user'=> false,
				'show_in_admin_bar'=>false,
				'publicly_queryable'=>false,
				'show_in_nav_menus'=>false,
				'public'=>true,
				'register_metaboxes' => $metaboxes,
				'capabilities' => array(
					'publish_posts' => 'manage_options',
					'edit_posts' => 'manage_options',
					'edit_others_posts' => 'manage_options',
					'delete_posts' => 'manage_options',
					'delete_others_posts' => 'manage_options',
					'read_private_posts' => 'manage_options',
					'edit_post' => 'manage_options',
					'delete_post' => 'manage_options',
					'read_post' => 'manage_options',
				)
			)
		);

	}

	/**
	 * Register WPX_Options
	 *
	 * @since 1.0
	*/
	public function register_wpx_options() {

		$metaboxes = array( 
			'Basics' => array(
				array( 'collapsed' => false, 'order'=>40),
				array( 'id'=>'_wpx_options_menu_label', 'label'=>'Menu Label', 'description'=>'Enter the label to display on the menu tab.', 'field'=>'text', 'required'=>true),
				array( 'id'=>'_wpx_options_menu_parent', 'label'=>'Menu Parent', 'description'=>'This is an existing top level menu that this option page will appear underneath. This applies only to pages that exist in the Dashboard already. For example: edit.php?post_type=books for a custom post type. Other options include: index.php (Dashboard); edit.php (Posts); upload.php (Media); link-manager.php (Links); edit.php?post_type=page (Page); edit-comments.php (Comments); themes.php (Themes); plugins.php (Plugins); users.php (Users); tools.php (Tools); Settings (options-general.php). To make this option page\'s menu item appear underneath a top-level custom options page, just assign this option page\'s post as the child of the top-level options page.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_options_register_metaboxes', 'label'=>'Metaboxes', 'description'=>'Choose meta fields to assign to this options page.', 'field'=>'wpx_select_fields', 'required'=>true),
			),
			'UI Settings' => array(
				array( 'collapsed' => true, 'order'=>30),
				array( 'id'=>'_wpx_options_icon_url', 'label'=>'Menu Icon', 'description'=>'You may: 1) enter the full path to an icon here. Icons should be 20 x 20 pixels or smaller; 2) (WP 3.8+) If "dashicons-...", a Dashicon is shown from the collection at http://melchoyce.github.io/dashicons/. For example, the default "gear" symbol could be explicitly specified with "dashicons-admin-generic"; 3) (WP 3.8+) If "data:image/svg+xml;base64...", the specified SVG data image is used as a CSS background; 4) If "none" (previously "div"), the icon is replaced with an empty div you can style with CSS; 5) If nothing is entered (default), the "gear" Dashicon is shown (and menu-icon-generic is added to the CSS classes of the link).', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_options_menu_position', 'label'=>'Menu Position', 'description'=>' The position in the menu order this menu should appear. By default, if this parameter is omitted, the menu will appear at the bottom of the menu structure. The higher the number, the lower its position in the menu. WARNING: if two menu items use the same position attribute, one of the items may be overwritten so that only one item displays! Risk of conflict can be reduced by using decimal instead of integer values, e.g. 63.3 instead of 63 (Note: Use quotes in code, IE 63.3).', 'field'=>'number', 'required'=>false)
			),
			'Advanced Configuration' => array(
				array( 'collapsed' => true, 'order'=>20),
				array( 'id'=>'_wpx_options_capability', 'label'=>'Capability', 'description'=>'Enter the capability that the options page requires in order to be viewed/edited. See: http://codex.wordpress.org/Roles_and_Capabilities.', 'field'=>'text', 'required'=>true),
				array( 'id'=>'_wpx_options_validation', 'label'=>'Validation Routines', 'description'=>'Optionally, enter the validation routines you would like to run on meta fields attached to this page. Enter these as string pairs, one per line, like so: metafieldID, nameOfFunction. The first string before the comma is the ID of the meta field; the second string is the name of the validation function you will create in your functions.php file. See the documentation for more information about proper error handling.', 'field'=>'textarea', 'required'=>false)
			)
		);

		$options = new wpx_register_type(
			'wpx_options',
			array(
				'description' => 'Each post in this post type represents an options page.',
				'label_singular' => 'Options Page',
				'label_plural' => 'Options Pages',
				'hierarchical'=> true,
				'exclude_from_search'=> true,
				'supports' => array('title','page-attributes'),
				'show_ui' => true,
				'show_in_menu' => false,
				'delete_with_user' => false,
				'public'=>true,
				'publicly_queryable'=>false,
				'show_in_admin_bar'=> false,
				'show_in_nav_menus'=> false,
				'register_metaboxes' => $metaboxes,
				'capabilities' => array(
					'publish_posts' => 'manage_options',
					'edit_posts' => 'manage_options',
					'edit_others_posts' => 'manage_options',
					'delete_posts' => 'manage_options',
					'delete_others_posts' => 'manage_options',
					'read_private_posts' => 'manage_options',
					'edit_post' => 'manage_options',
					'delete_post' => 'manage_options',
					'read_post' => 'manage_options',
				)
			)
		);

	}

	/**
	 * Register WPX_Taxonomy
	 *
	 * @since 1.0
	*/
	public static function register_wpx_taxonomies() {

		$metaboxes = array( 
			'Name' => array(
				array('order'=>40),
				array( 'id'=>'_wpx_taxonomy_label_plural', 'label'=>'Plural Name', 'description'=>'What is the taxonomy called in the singular case? If you enter both a Plural and Singular Label, the rest of the Label fields will be filled out for you.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_label_singular', 'label'=>'Singular Name', 'description'=>'What is the taxonomy called in the plural case? If you enter both a Plural and Singular Label, the rest of the Label fields will be filled out for you.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_description', 'label'=>'Description', 'description'=>' A short descriptive summary of what the taxonomy is.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_object_type', 'label'=>'Object Type', 'description'=>'Choose object type(s) to associate this taxonomy with.', 'field'=>'wpx_select_object_type', 'required'=>true)
			),
			'Metaboxes' => array(
				array( 'collapsed' => true, 'order'=>35),
				array( 'id'=>'_wpx_taxonomy_register_metaboxes', 'label'=>'Fields', 'description'=>'Select the fields that you would like to assign to this taxonomy.', 'field'=>'wpx_select_fields', 'required'=>false)
			),
			'Labels' => array(
				array( 'collapsed' => true, 'order'=>30),
				array( 'id'=>'_wpx_taxonomy_name', 'label'=>'Name', 'description'=>'A plural descriptive name for the taxonomy marked for translation.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_singular_name', 'label'=>'Singular Name', 'description'=>'Name for one object of this taxonomy.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_menu_name', 'label'=>'Menu Name', 'description'=>'The menu name text. This string is the name to give menu items. Defaults to value of name.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_all_items', 'label'=>'All Items', 'description'=>'The all items text.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_edit_item', 'label'=>'Edit Item', 'description'=>'The edit item text.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_view_item', 'label'=>'View Item', 'description'=>'The view item text.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_update_item', 'label'=>'Update Item', 'description'=>'The update item text.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_add_new_item', 'label'=>'Add New Item', 'description'=>'The add new item text.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_new_item_name', 'label'=>'New Item Name', 'description'=>'The new item name text.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_parent_item', 'label'=>'Parent Item', 'description'=>'The parent item text. This string is not used on non-hierarchical taxonomies such as post tags.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_parent_item_colon', 'label'=>'Parent Item Colon', 'description'=>'The same as parent_item, but with colon : at the end.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_search_items', 'label'=>'Search Items', 'description'=>'The search items text.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_popular_items', 'label'=>'Popular Items', 'description'=>'The popular items text.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_separate_items_with_commas', 'label'=>'Separate Items with Commas', 'description'=>'The separate item with commas text used in the taxonomy meta box. This string isn\'t used on hierarchical taxonomies.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_add_or_remove_items', 'label'=>'Add or Remove Items', 'description'=>'The add or remove items text and used in the meta box when JavaScript is disabled. This string isn\'t used on hierarchical taxonomies.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_choose_from_most_used', 'label'=>'Choose from Most Used', 'description'=>'The choose from most used text used in the taxonomy meta box. This string isn\'t used on hierarchical taxonomies.', 'field'=>'text', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_not_found', 'label'=>'Not Found', 'description'=>'The text displayed via clicking \'Choose from the most used tags\' in the taxonomy meta box when no tags are available. This string isn\'t used on hierarchical taxonomies.', 'field'=>'text', 'required'=>false)
			),
			'UI Settings' => array(
				array( 'collapsed' => true, 'order'=>20),
				array( 'id'=>'_wpx_taxonomy_hierarchical', 'label'=>'Hierarchical', 'description'=>'Is this taxonomy hierarchical (can it have descendant) like categories or not hierarchical like tags?', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_show_ui', 'label'=>'Show UI', 'description'=>'Whether to generate a default UI for managing this taxonomy.', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_show_in_nav_menus', 'label'=>'Show in Nav Menus', 'description'=>'True makes this taxonomy available for selection in navigation menus.', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_show_tagcloud', 'label'=>'Show Tag Cloud', 'description'=>'Whether to allow the Tag Cloud widget to use this taxonomy.', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_show_admin_column', 'label'=>'Show in Admin Column', 'description'=>'Whether to allow automatic creation of taxonomy columns on associated post-types. (Available since 3.5).', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_public', 'label'=>'Public', 'description'=>'If the taxonomy should be publicly queryable.', 'field'=>'wpx_states', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_update_count_callback', 'label'=>'Update Count Callback Hook', 'description'=>'A function name that will be called when the count of an associated $object_type, such as post, is updated. Works much like a hook.', 'field'=>'text', 'required'=>false)
			),
			'Capabilities' => array(
				array( 'collapsed' => true, 'order'=>10),
				array( 'id'=>'_wpx_taxonomy_capabilities', 'label'=>'Capabilities', 'description'=>'An array of the capabilities for this taxonomy.', 'field'=>'wpx_taxonomy_capabilities', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_sort', 'label'=>'Sort', 'description'=>'Whether this taxonomy should remember the order in which terms are added to objects.', 'field'=>'checkbox', 'required'=>false)
			),
			'Permalink Settings' => array(
				array( 'collapsed' => true, 'order'=>0),
				array( 'id'=>'_wpx_taxonomy_rewrite', 'label'=>'Rewrite', 'description'=>'Set to false to prevent automatic URL rewriting a.k.a. "pretty permalinks". Pass an $args array to override default URL settings for permalinks.', 'field'=>'wpx_taxonomy_rewrite', 'required'=>false),
				array( 'id'=>'_wpx_taxonomy_query_var', 'label'=>'Query Var', 'description'=>'False to disable the query_var, set as string to use custom query_var instead of default which is $taxonomy, the taxonomy\'s "name." Note that leaving this blank will set the default as determined by WordPress.', 'field'=>'wpx_text', 'required'=>false)
			)
		);

		$taxonomies = new wpx_register_type(
			'wpx_taxonomy', 
			array(
				'label_singular' => 'Taxonomy',
				'label_plural' => 'Taxonomies',
				'supports' => array('title','page-attributes'),
				'show_ui'=> true,
				'show_in_menu'=> false,
				'publicly_queryable'=>false,
				'public'=>true,
				'show_in_nav_menus'=> false,
				'delete_with_user'=>false,
				'register_metaboxes' => $metaboxes,
				'capabilities' => array(
					'publish_posts' => 'manage_options',
					'edit_posts' => 'manage_options',
					'edit_others_posts' => 'manage_options',
					'delete_posts' => 'manage_options',
					'delete_others_posts' => 'manage_options',
					'read_private_posts' => 'manage_options',
					'edit_post' => 'manage_options',
					'delete_post' => 'manage_options',
					'read_post' => 'manage_options',
				)
			)
		);

	}

	/**
	 * Custom Columns for WPX_Fields
	 *
	 * @since 1.0
	*/
	public function extend_fields_columns($page_columns) {
		$new_columns['cb'] = '<input type="checkbox" />';
		$new_columns['title'] = 'Title';
		$new_columns['post_types'] = 'Post Types';
		$new_columns['group'] = 'Group';
		$new_columns['order'] = 'Order';
		$new_columns['id'] = 'Meta Key';
		return $new_columns;
	}

	/**
	 * Custom Columns for WPX_Types
	 *
	 * @since 1.0
	*/
	public function extend_cpts_columns($page_columns) {
		$new_columns['cb'] = '<input type="checkbox" />';
		$new_columns['title'] = 'Title';
		$new_columns['fields'] = 'Fields';
		$new_columns['id'] = 'ID';
		return $new_columns;
	}

	/**
	 * Custom Columns for WPX_Options
	 *
	 * @since 1.0
	*/
	public function extend_options_columns($page_columns) {
		$new_columns['cb'] = '<input type="checkbox" />';
		$new_columns['title'] = 'Title';
		$new_columns['fields'] = 'Fields';
		$new_columns['id'] = 'ID';
		return $new_columns;
	}

	/**
	 * Custom Columns for WPX_Taxonomy
	 *
	 * @since 1.0
	*/
	public function extend_taxonomies_columns($page_columns) {
		$new_columns['cb'] = '<input type="checkbox" />';
		$new_columns['title'] = 'Title';
		$new_columns['fields'] = 'Fields';
		$new_columns['id'] = 'ID';
		return $new_columns;
	}

	/**
	 * Switch Statement for WPX_Fields Custom Column
	 *
	 * @since 1.0
	*/
	public function extend_fields_post_list($column_name, $id) {
		
		global $post;

		switch ($column_name) {

		case 'id':
			echo $post->post_name;
			break;

		case 'order':
			echo $post->menu_order;
			break;

		case 'post_types':
			
			$post_types = get_posts(array('post_type'=>'wpx_types', 'posts_per_page'=>-1,'meta_key'=>'_wpx_cpt_metaboxes'));
			$found_types = array();

			if ($post_types) {
				foreach($post_types as $i=>$type) {
					$metaboxes = get_post_meta($type->ID, '_wpx_cpt_metaboxes', true);
					$metaboxes_array = explode(',', $metaboxes);
					if (is_array($metaboxes_array)) {
						if (in_array($post->ID, $metaboxes_array)) {
							$found_types[] = $type;
						}
					}
					
				}
			}
			if ($found_types) {
				$comma = ', ';
				$count = count($found_types)-1;
				foreach($found_types as $i=>$type) {
					if ($i == $count) $comma = '';
					echo '<a href="'.get_bloginfo('url').'/wp-admin/post.php?post='.$type->ID.'&action=edit">'.get_the_title($type->ID).'</a>'.$comma;
				}
			}
			break;

		case 'group':
			$groups = get_the_terms( $post->ID, 'wpx_groups' );
			if ($groups) { 
				$count = 0;
				foreach ($groups as $group) {
					echo '<a href="edit.php?post_type=wpx_fields&groups='.$group->slug.'">'.$group->name.'</a>'; 
				}
			}
		break;

		default:
			break;
		}
	}

	/**
	 * Switch Statement for WPX_Types Custom Column
	 *
	 * @since 1.0
	*/
	public function extend_cpts_post_list($column_name, $id) {
		global $post;
		switch ($column_name) {
		case 'id':
			echo $post->post_name;
			break;
		case 'order':
			echo $post->menu_order;
			break;
		case 'fields':
			$fields_meta = get_post_meta($post->ID, '_wpx_cpt_metaboxes', true);
			$fields_meta = explode(',',$fields_meta);
			if (is_array($fields_meta)) {
				$fields = get_posts(array('post_type'=>'wpx_fields', 'posts_per_page'=>-1,'post__in'=>$fields_meta));
				$comma = ', ';
				$count = count($fields)-1;
				foreach($fields as $i=>$field) {
					if ($i == $count) $comma = '';
					echo '<a href="'.get_bloginfo('url').'/wp-admin/post.php?post='.$field->ID.'&action=edit">'.get_the_title($field->ID).'</a>'.$comma;
				}

			}
			break;
		default:
			break;
		}
	}

	/**
	 * Switch Statement for WPX_Options Custom Column
	 *
	 * @since 1.0
	*/
	public function extend_options_post_list($column_name, $id) {
		
		global $post;

		switch ($column_name) {

		case 'id':
			echo $post->post_name;
			break;

		case 'fields':
			$fields_meta = get_post_meta($post->ID, '_wpx_options_register_metaboxes', true);
			$fields_meta = explode(',',$fields_meta);
			if (is_array($fields_meta)) {
				$fields = get_posts(array('post_type'=>'wpx_fields', 'posts_per_page'=>-1,'post__in'=>$fields_meta));
				$comma = ', ';
				$count = count($fields)-1;
				foreach($fields as $i=>$field) {
					if ($i == $count) $comma = '';
					echo '<a href="'.get_bloginfo('url').'/wp-admin/post.php?post='.$field->ID.'&action=edit">'.get_the_title($field->ID).'</a>'.$comma;
				}

			}
			break;

		default:
			break;
		}
	}

	/**
	 * Switch Statement for WPX_Taxonomy Custom Column
	 *
	 * @since 1.0
	*/
	public function extend_taxonomies_post_list($column_name, $id) {
		
		global $post;

		switch ($column_name) {

		case 'id':
			echo $post->post_name;
			break;

		case 'fields':
			$fields_meta = get_post_meta($post->ID, '_wpx_taxonomy_register_metaboxes', true);
			$fields_meta = explode(',',$fields_meta);
			if (is_array($fields_meta)) {
				$fields = get_posts(array('post_type'=>'wpx_fields', 'posts_per_page'=>-1,'post__in'=>$fields_meta));
				$comma = ', ';
				$count = count($fields)-1;
				foreach($fields as $i=>$field) {
					if ($i == $count) $comma = '';
					echo '<a href="'.get_bloginfo('url').'/wp-admin/post.php?post='.$field->ID.'&action=edit">'.get_the_title($field->ID).'</a>'.$comma;
				}

			}
			break;

		default:
			break;
		}
	}
}