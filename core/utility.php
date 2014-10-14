<?php
/**
 * Public Functions
 *
 * These are public static functions that you can call anywhere.
 *
 * @package wpx
 * 
*/

class wpx {

	protected static $instance;

	public static function init() {
		is_null( self::$instance ) AND self::$instance = new self;
		return self::$instance;
	}

	function __construct() {

		// add js necessary for wpx
		add_action( 'admin_enqueue_scripts', array($this,'extend_dashboard_script') );

		// allow galleries and image field types to delete images from the media library
		add_action( 'save_post', array($this, 'manage_image_field_delete') );

		// clear transients when saving posts
		add_action( 'save_post', array($this,'clear_transients') );

		// forces all keys in WPX cpts to use underscores
		add_filter( 'sanitize_title', array($this, 'normalize_keys'));

		// extends the internal field types to modify screen behavior
		add_action( 'wpx_post_type_edit_screen', array($this,'extend_internal_field_types') );

	}

	/**
	 * Add Custom CSS to the Dashboard
	 *
	 * Includes a CSS file into the Dashboard for wpx and for the theme. 
	 * The one for your theme will look for a dashboard.css in the root of your theme.
	 *
	 * @since 1.0
	*/
	public static function extend_dashboard_styles() {
		if (is_admin()) {
			$css = TEMPLATEPATH . '/dashboard.css';
			if (is_file($css)) {
				wp_register_style('wpx.dashboard.custom', get_bloginfo('template_url').'/dashboard.css', false, null, 'screen');
				wp_enqueue_style('wpx.dashboard.custom');
			}
		}
	}

	/**
	 * Add Custom CSS to the Login Form
	 *
	 * Includes a CSS file into the Login screens. It will look for a login.css in the theme directory.
	 *
	 * @since 1.0
	*/
	public static function extend_login_styles() {
		$css = TEMPLATEPATH . '/login.css';
		if(is_file($css)){
			echo '<link rel="stylesheet" type="text/css" href="' . get_bloginfo('template_url') . '/login.css" />'."\n";
		}
	}

	/**
	 * Extend "Right Now" Dashboard Widget 
	 *
	 * Adds custom taxonomies and custom post types to the Right Now widget in the Dashboard. 
	 *
	 * @since 1.0
	*/
	public static function extend_right_now_widget() {
		$args = array(
			'public' => true ,
			'_builtin' => false
		);
		$output = 'object';
		$operator = 'and';
		$post_types = get_post_types( $args , $output , $operator );
		foreach( $post_types as $post_type ) {
			if ($post_type->name == 'wpx_fields' || $post_type->name == 'wpx_options' || $post_type->name == 'wpx_types' || $post_type->name == 'wpx_taxonomy') continue;
			$num_posts = wp_count_posts( $post_type->name );
			$num = number_format_i18n( $num_posts->publish );
			$text = _n( $post_type->labels->singular_name, $post_type->labels->name , intval( $num_posts->publish ) );
			if ( current_user_can( 'edit_posts' ) ) {
				$cpt_name = $post_type->name;
			}
			echo '<li class="'.$cpt_name.'-count"><tr><a href="edit.php?post_type='.$cpt_name.'"><td class="first b b-' . $post_type->name . '"></td>' . $num . ' <td class="t ' . $post_type->name . '">' . $text . '</td></a></tr></li>';
		}
		$taxonomies = get_taxonomies( $args , $output , $operator );
		foreach( $taxonomies as $taxonomy ) {
			if ($taxonomy->name == 'wpx_groups') continue;
			$num_terms  = wp_count_terms( $taxonomy->name );
			$num = number_format_i18n( $num_terms );
			$text = _n( $taxonomy->labels->name, $taxonomy->labels->name , intval( $num_terms ));
			if ( current_user_can( 'manage_categories' ) ) {
				$cpt_tax = $taxonomy->name;
			}
			echo '<li class="post-count"><tr><a href="edit-tags.php?taxonomy='.$cpt_tax.'"><td class="first b b-' . $taxonomy->name . '"></td>' . $num . ' <td class="t ' . $taxonomy->name . '">' . $text . '</td></a></tr></li>';
		}
	}

	/**
	 * Change Login Logo URL
	 *
	 * Makes the login logo link to the site's homepage, and not WordPress.org. 
	 *
	 * @since 1.0
	*/
	public function change_login_logo_url($url) {
		return get_bloginfo('url');
	}

	/**
	 * Enable Excerpt Metabox
	 *
	 * WP by default hides the Excerpt metabox in the Dashboard, this unhides it. 
	 *
	 * @since 1.0
	 *
	*/
	public function enable_excerpt_metabox( $hidden ) {
		foreach ( $hidden as $i => $metabox ) {
			if ( 'postexcerpt' == $metabox ) {
				unset ( $hidden[$i] );
			}
		}
		return $hidden;
	}

	/**
	 * Remove Recent Comments Styles
	 *
	 * WP inserts this inline style: <style type="text/css">.recentcomments a{display:inline !important;padding:0 !important;margin:0 !important;}</style>
	 * directly into the head of the document for the Recent Comments widget. This function removes the inline style. 
	 *
	 * @since 1.0
	 *
	*/
	public static function disable_recent_comments_styles() {
		global $wp_widget_factory;
		remove_action( 'wp_head', array( $wp_widget_factory->widgets['WP_Widget_Recent_Comments'], 'recent_comments_style' ) );
	}

	/**
	 * Get Excerpt by ID
	 *
	 * Sometimes the functions get_excerpt() and the_excerpt() are not helpful, because they both only work in the Loop
	 * and return different markup (get_excerpt() strips HTML, while the_excerpt() returns content
	 * wrapped in p tags). This function will let you generate an excerpt by passing a post object:
	 *
	 * If there is a manually entered post_excerpt, it will return the content of the post_excerpt raw. Any markup
	 * entered into the Excerpt meta box will be returned as well, and you can use apply_filters('the_content', $output);
	 * on the output to render the content as you would the_excerpt().
	 *
	 * If there is no manual excerpt, the function will get the post_content, apply the_content filter, 
	 * escape and filter out all HTML, then truncate the excerpt to a specified length. 
	 *
	 * @since 1.0
	 * @param object $object
	 * @param int $length
	 *
	*/
	public static function get_excerpt_by_id($object, $length = 55) {
		if ($object->post_excerpt) {
			return $object->post_excerpt;
		} else {
			$output = $object->post_content;
			$output = apply_filters('the_content', $output);
			$output = str_replace('\]\]\>', ']]&gt;', $output);
			$output = strip_tags($output);
			$excerpt_length = 55;
			$words = explode(' ', $output, $length + 1);
			if (count($words)> $length) {
				array_pop($words);
				array_push($words, '');
				$output = implode(' ', $words);
			}
			return $output.'...';
		}
	}

	/**
	 * Get Ancestor ID
	 *
	 * Retrieve the ID of the ancestor of the given object.
	 *
	 * @since 1.0
	 * @param object $object The post we're checking ancestors for.
	 * @return string
	 *
	*/
	public static function get_ancestor_id($object = null) {
		if (!$object) {
			global $post;
			$object = $post;
		}
		$ancestor = get_post_ancestors( $object );
		if (empty($ancestor)) {
			$ancestor = array($object->ID);
		}
		$ancestor = end($ancestor);
		return $ancestor;
	}

	/**
	 * Truncate
	 *
	 * A very simple function that limits a given block of text to the specified length.
	 * Does *not* account for HTML.
	 *
	 * @since 1.0
	 * @param string $text
	 * @param string $limit
	 * @param string $break The character to break on (typically a space)
	 * @return string
	 *
	*/
	public static function truncate($text, $limit, $break) {
		$size = strlen($text);
		if ($size > $limit) {
			$text = $text." ";
			$text = substr($text,0,$limit);
			$text = substr($text,0,strrpos($text,' '));
			$text = $text.$break;
		}
		return $text;
	}

	/**
	 * Get Ancestor Meta
	 *
	 * Retrieve custom meta from an ancestor of the given post.
	 *
	 * @since 1.0
	 * @param object $post The post we're getting ancestor meta for.
	 * @param string $meta_key The custom field name.
	 * @param string $default_value The content to display if we find nothing.
	 * @return string
	 *
	*/
	public static function get_ancestor_meta($post=null, $meta_key, $default_value) {
		if (!$post) {
			global $post;
		}
		$ancestor_meta = get_post_meta(wpx::get_ancestor_id($post), $meta_key, true);
		$meta = get_post_meta($post->ID, $meta_key, true);
		// does this page have the meta?
		if ($meta) {
			$value = $meta;
		// okay, does the ancestor have the meta?
		} else if ($ancestor_meta) {
			$value = $ancestor_meta;
			// just use the default
		} else {
			$value = $default_value;
		}
		return $value;
	}

	/**
	 * Is Page a Child of Another Page
	 *
	 * Recursively looks at ancestors to determine if the given page is a child
	 * of a given page. If no ID is passed, it will check the current post in the Loop. 
	 *
	 * @since 1.0
	 * @param int $target_id
	 * @param int $post_id
	 *
	*/
	public static function is_child_of($target_id, $post_id = null){
		global $post;
		
		if ($post_id == null) {
			$post_id = $post->ID;
		}
		$current = get_page($post_id);
		if ($current->post_parent != 0) {
			if ($current->post_parent != $target_id) {
				return is_child_of($target_id, $current->post_parent);
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	/**
	 * Get Options Meta
	 *
	 * Like get_post_meta(), this function returns the custom meta attached to an Options page.
	 *
	 * @since 1.0
	 * @param string $options_page_id
	 * @param string $meta_key
	 * 
	*/
	public static function get_option_meta($options_page_id, $meta_key) {
		$meta_array =  get_option( $options_page_id );
		if (is_array($meta_array)) {
			$meta = isset($meta_array[$meta_key]) ? $meta_array[$meta_key] : false;
			return $meta;
		} else {
			return false;
		}
	}

	/**
	 * Gravatar Exists
	 *
	 * Utility function to check if a gravatar exists for a given email or id
	 * @param int|string|object $id_or_email A user ID,  email address, or comment object
	 * @return bool if the gravatar exists or not
	*/
	public static function validate_gravatar($id_or_email) {
		$email = '';
		if ( is_numeric($id_or_email) ) {
			$id = (int) $id_or_email;
			$user = get_userdata($id);
			if ( $user )
				$email = $user->user_email;
		} elseif ( is_object($id_or_email) ) {
			// No avatar for pingbacks or trackbacks
			$allowed_comment_types = apply_filters( 'get_avatar_comment_types', array( 'comment' ) );
			if ( ! empty( $id_or_email->comment_type ) && ! in_array( $id_or_email->comment_type, (array) $allowed_comment_types ) )
				return false;
	 
			if ( !empty($id_or_email->user_id) ) {
				$id = (int) $id_or_email->user_id;
				$user = get_userdata($id);
				if ( $user)
					$email = $user->user_email;
			} elseif ( !empty($id_or_email->comment_author_email) ) {
				$email = $id_or_email->comment_author_email;
			}
		} else {
			$email = $id_or_email;
		}
	 
		$hashkey = md5(strtolower(trim($email)));
		$uri = 'http://www.gravatar.com/avatar/' . $hashkey . '?d=404';
	 
		$data = wp_cache_get($hashkey);
		if (false === $data) {
			$response = wp_remote_head($uri);
			if( is_wp_error($response) ) {
				$data = 'not200';
			} else {
				$data = $response['response']['code'];
			}
		    wp_cache_set($hashkey, $data, $group = '', $expire = 60*5);
	 
		}		
		if ($data == '200'){
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Resize Image
	 *
	 * Wraps the WP Image Editor.
	 * All crops generated by this function go into the /wpx/ folder.
	 * Useful for passing crops to responsive load methods.
	 * 
	 * @since 1.0
	 * @param string $path
	 * @param int $max_width
	 * @param int $max_height
	 * @param bool $crop
	 * @param array $args
	*/
	public static function resize( $path, $max_width = null, $max_height = null, $crop = true, $args = '') {

		// determine base path for uploads
		$upload_dir = wp_upload_dir();

		// let's specify some common defaults
		$defaults = array(
			'quality' => 100,
			'suffix' => null,
			'uploads_url' => $upload_dir['baseurl'],
			'uploads_directory' => $upload_dir['basedir'],
			'crops_directory' => '/wpx/',
			'filetype' => null
		);

		// standard WP default arguments merge
		$parameters = wp_parse_args( $args, $defaults );
		extract( $parameters, EXTR_SKIP );

		// get the path
		$system_root = $_SERVER[ 'DOCUMENT_ROOT' ];
		$file_path = parse_url($path);
		$image = wp_get_image_editor($system_root.$file_path['path']);

		// parameters
		$parameters_quality = isset($parameters['quality']) ? $parameters['quality'] : false;
		$parameters_rotate = isset($parameters['rotate']) ? $parameters['rotate'] : false;
		$parameters_crop = isset($parameters['crop']) ? $parameters['crop'] : false;
		$parameters_suffix = isset($parameters['suffix']) ? $parameters['suffix'] : false;
		$parameters_flip = isset($parameters['flip']) ? $parameters['flip'] : false;
		$parameters_uploads_directory = isset($parameters['uploads_directory']) ? $parameters['uploads_directory'] : false;
		$parameters_crops_directory = isset($parameters['crops_directory']) ? $parameters['crops_directory'] : false;
		$parameters_filetype = isset($parameters['filetype']) ? $parameters['filetype'] : false;
		$parameters_uploads_url = isset($parameters['uploads_url']) ? $parameters['uploads_url'] : false;

		// only do stuff on success
		if ( ! is_wp_error( $image ) ) {
			// resize the image
			if ($max_width || $max_height) { $image->resize( $max_width, $max_height, $crop ); }
			// set the quality
			if ($parameters_quality) $image->set_quality( $parameters_quality );
			// rotate the image
			if ($parameters_rotate) $image->rotate( $parameters_rotate );
			// flip the image
			if ($parameters_flip) $image->flip( true, false );
			// crop the image
			if ($parameters_crop) $image->crop( $parameters_crop );
			// generate the filename
			$filename = $image->generate_filename($parameters_suffix, $parameters_uploads_directory.$parameters_crops_directory, $parameters_filetype );
			// save the image
			$image_meta = $image->save($filename);
			// insert the URL path to the crop
			$image_meta['url'] = $parameters_uploads_url.$parameters_crops_directory.$image_meta['file'];
			// return the data
			return $image_meta;
		}
	}

	/**
	 * Get Taxonomy Term Meta
	 *
	 * Like get_post_meta(), this function returns the custom meta attached to a taxonomy.
	 *
	 * @since 1.0
	 * @param int $term_id
	 * @param string $meta_key
	 *
	*/
	public static function get_taxonomy_meta($term_id, $meta_key) {
		$string = 'taxonomy_term_'.$term_id;
		$meta_array =  get_option( $string );
		return $meta_array[$meta_key];
	}

	/**
	 * Get Attachments
	 *
	 * A function to retrieve the attachments of a post. 
	 *
	 * @since 1.0
	 * @param int $post_id The ID of the post you want to get attachments of.
	 * @param string $order Reverse or ascending order, etc
	 * @param string $orderby What field to sort on
	 * @param bool $exclude_featured_thumbnail Should we exclude the featured thumbnail?
	 * @param string $mime Restrict by mime type
	 * @param string $post_status Don't change this
	 * @return object
	 *
	*/
	public static function get_attachments($post_id, $order = 'ASC', $orderby = 'menu_order ID', $exclude_thumbnail = true, $mime = '', $post_status = 'inherit') {
		if ($exclude_thumbnail == true) {
			$attachments = get_children( array('post_parent' => $post_id, 'post_status' => $post_status, 'post_type'=> 'attachment', 'post_mime_type' => $mime, 'order' => $order, 'orderby' => $orderby, 'exclude' => get_post_thumbnail_id($post_id) ));
		} else {
			$attachments = get_children( array('post_parent' => $post_id, 'post_status' => $post_status, 'post_type'=> 'attachment', 'post_mime_type' => $mime, 'order' => $order, 'orderby' => $orderby ) );
		}
		return $attachments;
	}

	/**
	 * Get Attachment ID by URL
	 *
	 * Gets an attachments by its URL.
	 *
	 * @since 1.0
	 * @param string $url
	 *
	*/
	public static function get_attachment_id_by_url($url) {
		if ($url) {
			global $wpdb;
			$query = "SELECT ID FROM {$wpdb->posts} WHERE guid='$url'";
			$id = $wpdb->get_var($query);
			return $id;
		} else {
			return false;
		}
	}

	/**
	 * Delete All WPX Transients
	 *
	 * Throughout WPX we store transients for performance reasons, tracked in global $wpx_transients_array.
	 * This is better than deleting all transients, and is performed whenever a post is saved or created.
	 *
	 * @since 1.0
	 * @param int $post_id
	 *
	*/
	public static function clear_transients( $post_id=null ) {

		global $post;

		$post_type = get_post_type($post);

		// get the transients array
		global $wpx_transient_array;

		// if this is just a revision, don't clear the transient
		if ( wp_is_post_revision( $post_id ) )
			return;

		// delete all wpx transients
		if ($wpx_transient_array) {
			foreach($wpx_transient_array as $transient) {
				delete_site_transient($transient);
			}
		}

		if ($post_type == 'wpx_types') {
			// because it's possible to activate has_archive
			flush_rewrite_rules();
		}

	}

	/**
	 * Normalize Keys
	 *
	 * Forces hyphens to become underscores when dealing with "core" WPx content types
	 * and prevents invalid characters from making it thru from the Title field.
	 *
	 * @since 1.0
	 * @param string $title
	 *
	*/
	public function normalize_keys($title) {
		global $post;
		$post_type = get_post_type($post);
		// only for internal cpts
		if ($post_type == 'wpx_fields' || $post_type == 'wpx_options' || $post_type == 'wpx_types' || $post_type == 'wpx_taxonomy') {
			// only underscores allowed
			$title = sanitize_key( $title );
			return str_replace('-', '_', $title);
		} else {
			return $title;
		}
	}

	/**
	 * Extract Field Key
	 *
	 * Gets the unique ID of a field when on an edit post screen.
	 *
	 * @since 1.0
	 * @param string $key
	 * @param string $post_type
	*/
	public static function extract_field_key($key, $post_type) {
		$search = '_'.$post_type.'_';
		$field_key = str_replace($search, '', $key);
		$field_key = get_page_by_path($field_key, OBJECT, 'wpx_fields');
		return $field_key;

	}

	/**
	 * Extend Internal Field Types
	 *
	 * Allows us to show extra field options when specific field types are chosen.
	 *
	 * @since 1.0
	 */
	public function extend_internal_field_types() {

		global $post;

		$post_type = get_post_type($post->ID);

		// remove wp seo for the wpx types
		remove_meta_box( 'wpseo_meta', 'wpx_fields', 'normal' );
		remove_meta_box( 'wpseo_meta', 'wpx_options', 'normal' );
		remove_meta_box( 'wpseo_meta', 'wpx_taxonomy', 'normal' );
		remove_meta_box( 'wpseo_meta', 'wpx_types', 'normal' );

		// only do this for core WPx fields
		if ($post_type == 'wpx_fields') {

			// inquire about the field type
			$field_type = get_post_meta($post->ID, '_wpx_fields_type', true);

			if ($field_type !== 'post') {
				remove_meta_box('wpx_fields_relationshipoptions','wpx_fields','normal');
			}

			if ($field_type !== 'user') {
				remove_meta_box('wpx_fields_useroptions','wpx_fields','normal');
			}

			if ($field_type !== 'term') {
				remove_meta_box('wpx_fields_taxonomyoptions','wpx_fields','normal');
			}

			if ($field_type !== 'gallery') {
				remove_meta_box('wpx_fields_galleryoptions','wpx_fields','normal');
			}

			// if there is no field type, it means the field hasn't been saved yet
			// so hide all the additional panels
			if (!$field_type) {
				remove_meta_box('wpx_fields_galleryoptions','wpx_fields','normal');
				remove_meta_box('wpx_fields_relationshipoptions','wpx_fields','normal');
				remove_meta_box('wpx_fields_taxonomyoptions','wpx_fields','normal');
			}

		}

	}

	/**
	 * Adds JS for wpx
	 *
	 * Enqueue to the Dashboard the JS we need for WPx.
	 *
	 * @since 1.0
	 */
	public function extend_dashboard_script() {

		global $post;

		// get the screen
		$screen = get_current_screen();

		if ($screen->base == 'post') {
			// need to pass the post id, or else no attachments
			$args = array('post'=>$post->ID);
			wp_enqueue_media($args);
		} else {
			// options pages, taxonomies are unattached
			wp_enqueue_media();
		}

		wp_register_script( 'wpx.common', plugins_url('/assets/js/scripts/common.js', dirname(__FILE__)), array('jquery'), null, true);
		wp_register_script( 'wpx.fields', plugins_url('/assets/js/scripts/fields.js', dirname(__FILE__)), array('jquery'), null, true);
		wp_enqueue_script( 'wpx.common' );
		wp_enqueue_script( 'wpx.fields' );

	}

	/**
	 * Manage Image Field Delete
	 *
	 * Used in conjunction with the image and gallery field types.
	 *
	 * @since 1.0
	 */
	public static function manage_image_field_delete() {

		// skip autosaves
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

		// delete all images in this array
		if (isset($_POST['wpx_prep_gallery_delete_image'])) {
			wp_delete_attachment($_POST['wpx_prep_gallery_delete_image']);
		}
		// delete a specific set of images
		if (isset($_POST['wpx_prep_gallery_delete_set'])) {
			$set = explode(',',$_POST['wpx_prep_gallery_delete_set']);
			$set = array_filter($set);
			foreach ($set as $image) {
				wp_delete_attachment($image);
			}
		}
	}

}

// call this function
wpx::init();