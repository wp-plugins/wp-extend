<?php
/**
 * Register Custom Post Types
 *
 * This class will register a post type and any custom metaboxes associated with it.
 *
 * @package wpx
 * 
*/

class wpx_register_type {

	protected static $instance;

	public static function init() {
		is_null( self::$instance ) AND self::$instance = new self;
		return self::$instance;
	}

	function __construct( $id, $settings = '') {

		$this->id = $id;
		$this->settings = $settings;
		$this->label_singular = isset($settings['label_singular']) ? $settings['label_singular'] : false;
		$this->description = isset($settings['label_description']) ? $settings['label_description'] : false;
		$this->label_plural = isset($settings['label_plural']) ? $settings['label_plural'] : false;
		$this->metaboxes = isset($settings['register_metaboxes']) ? $settings['register_metaboxes'] : false;
		$this->taxonomies = isset($settings['register_taxonomies']) ? $settings['register_taxonomies'] : false;

		if ($this->id) {

			// if this is a default post type (Posts or Pages)
			if ($this->id == 'page' || $this->id == 'post') {

				// then we have to attach the metaboxes manually (only on edit screen)
				add_action( 'add_meta_boxes_'.$this->id, array($this,'add_post_type_meta') );

			} else {

				// let's specify some defaults
				$defaults = array(
					'label' => $this->id
				);

				// standard WP default arguments merge
				$post_type_args = wp_parse_args( $this->settings, $defaults );
				extract( $post_type_args, EXTR_SKIP );

				// if a singular and plural name were specified, fill out all the labels
				if ($this->label_singular && $this->label_plural) {
					$post_type_args['labels'] = array(
						'name' => $this->label_plural,
						'singular_name' => $this->label_singular,
						'menu_name' => $this->label_plural,
						'all_items' => $this->label_plural,
						'add_new' => 'Add New',
						'add_new_item' => 'Add New '.$this->label_singular,
						'edit_item' => 'Edit '.$this->label_singular,
						'new_item' => 'New '.$this->label_singular,
						'view_item' => 'View '.$this->label_singular,
						'search_items' => 'Search '.$this->label_plural,
						'not_found' => 'No '.$this->label_plural.' found',
						'not_found_in_trash' => 'No '.$this->label_plural.' found in Trash',
						'parent_item_colon' => 'Parent '.$this->label_singular
					);

				}

				if ($this->description) $post_type_args['description'] = $this->description;

				// we want to pass all the regular arguments to register_post_type
				// but register_meta_box_cb must come from this class
				if ($this->metaboxes) { $post_type_args['register_meta_box_cb'] = array($this, 'add_post_type_meta'); }

				// fix rewrites
				$rewrite = isset($post_type_args['rewrite']) ? $post_type_args['rewrite'] : false;

				if ($rewrite) $post_type_args['rewrite'] = $rewrite;

				if ($rewrite || isset($post_type_args['rewrite_slug']) || isset($post_type_args['rewrite_pages']) || isset($post_type_args['rewrite_feeds']) || isset($post_type_args['rewrite_with_front']) || isset($post_type_args['rewrite_ep_mask'])) {

					$post_type_args['rewrite'] = array();				
					
					if (isset($post_type_args['rewrite_slug']) && $post_type_args['rewrite_slug'] !== NULL) $post_type_args['rewrite']['slug'] = $post_type_args['rewrite_slug'];
					if ($post_type_args['rewrite_slug'] == false && $post_type_args['rewrite_slug'] !== NULL) $post_type_args['rewrite']['slug'] = false;

					if (isset($post_type_args['rewrite_pages']) && $post_type_args['rewrite_pages'] !== NULL) $post_type_args['rewrite']['pages'] = $post_type_args['rewrite_pages'];
					if ($post_type_args['rewrite_pages'] == false && $post_type_args['rewrite_pages'] !== NULL) $post_type_args['rewrite']['pages'] = false;

					if (isset($post_type_args['rewrite_feeds']) && $post_type_args['rewrite_feeds'] !== NULL) $post_type_args['rewrite']['feeds'] = $post_type_args['rewrite_feeds'];
					if ($post_type_args['rewrite_feeds'] == false && $post_type_args['rewrite_feeds'] !== NULL) $post_type_args['rewrite']['feeds'] = false;

					if (isset($post_type_args['rewrite_with_front']) && $post_type_args['rewrite_with_front'] !== NULL) $post_type_args['rewrite']['with_front'] = $post_type_args['rewrite_with_front'];
					if ($post_type_args['rewrite_with_front'] == false && $post_type_args['rewrite_with_front'] !== NULL) $post_type_args['rewrite']['with_front'] = false;

					if (isset($post_type_args['rewrite_ep_mask']) && $post_type_args['rewrite_ep_mask'] !== NULL) $post_type_args['rewrite']['ep_mask'] = $post_type_args['rewrite_ep_mask'];
					if ($post_type_args['rewrite_ep_mask'] == false && $post_type_args['rewrite_ep_mask'] !== NULL) $post_type_args['rewrite']['ep_mask'] = false;

				}

				// deal with capabilities
				$read_post = isset($post_type_args['capabilities_read_post']) ? $post_type_args['capabilities_read_post'] : false;
				$edit_post = isset($post_type_args['capabilities_edit_post']) ? $post_type_args['capabilities_edit_post'] : false;
				$delete_post = isset($post_type_args['capabilities_delete_post']) ? $post_type_args['capabilities_delete_post'] : false;
				$edit_posts = isset($post_type_args['capabilities_edit_posts']) ? $post_type_args['capabilities_edit_posts'] : false;
				$edit_others_posts = isset($post_type_args['capabilities_edit_others_posts']) ? $post_type_args['capabilities_edit_others_posts'] : false;
				$publish_posts = isset($post_type_args['capabilities_publish_posts']) ? $post_type_args['capabilities_publish_posts'] : false;
				$read_private_posts = isset($post_type_args['capabilities_read_private_posts']) ? $post_type_args['capabilities_read_private_posts'] : false;

				if ($read_post || $edit_post || $delete_post || $edit_posts || $edit_others_posts || $publish_posts || $read_private_posts) {
					$post_type_args['capabilities'] = array(); 
					if ($read_post) {
						$post_type_args['capabilities']['read_post'] = $read_post;
					}
					if ($edit_post) {
						$post_type_args['capabilities']['edit_post'] = $edit_post;
					}
					if ($delete_post) {
						$post_type_args['capabilities']['delete_post'] = $delete_post;
					}
					if ($edit_posts) {
						$post_type_args['capabilities']['edit_posts'] = $edit_posts;
					}
					if ($edit_others_posts) {
						$post_type_args['capabilities']['edit_others_posts'] = $edit_others_posts;
					}
					if ($publish_posts) {
						$post_type_args['capabilities']['publish_posts'] = $publish_posts;
					}
					if ($read_private_posts) {
						$post_type_args['capabilities']['read_private_posts'] = $read_private_posts;
					}
				}

				$filter_post_type_args = $post_type_args;

				// acceptable parameters for register post type
				$register_post_type = array(
					'label',
					'labels',
					'exclude_from_search',
					'description',
					'can_export',
					'register_meta_box_cb',
					'permalink_epmask',
					'map_meta_cap',
					'capability_type',
					'capabilities',
					'query_var',
					'publicly_queryable',
					'rewrite',
					'exclude_from_search',
					'public',
					'menu_icon',
					'menu_position',
					'show_in_admin_bar',
					'show_in_menu',
					'show_in_nav_menus',
					'show_ui',
					'has_archive',
					'taxonomies',
					'hierarchical',
					'supports'
				);

				// remove keys that are not in register_post_type
				foreach ($filter_post_type_args as $i=>$filter_post_type_args) {
					if (!in_array($i, $register_post_type)) {
						unset($post_type_args[$i]);
					}
				}

				// now we can register the post type
				register_post_type(
					$this->id,
					$post_type_args
				);
			}
		}

		// save metabox data
		add_action( 'save_post', array($this,'save_post_type_meta'), 1, 2 );

		// register all taxonomies
		if ($this->id && $this->taxonomies) {
			foreach($this->taxonomies as $taxonomy_args) {
				// use wpx to register each taxonomy, with its metaboxes (the fields)
				$taxonomy = new wpx_register_taxonomy(
					$taxonomy_args[0], // slug/id for the post type
					$taxonomy_args[1]['object_type'],
					$taxonomy_args[1]
				);
			}
		}

	}

	/**
	 * Add Custom Metaboxes
	 *
	 * This function loops through each metabox in the $metaboxes array of the class
	 * and uses the add_meta_box core WP function to attach the metabox to the given
	 * post type.
	 *
	 * @since 1.0
	*/
	public function add_post_type_meta() {
		
		global $post;
		$metaboxes = $this->metaboxes;
		$post_type = get_post_type($post->ID);

		if ($this->id == $post_type) {
			// as far as I understand, there isn't an implicit way to set the order
			// in which metaboxes are appended, as calling them in a specific order doesn't
			// matter, so we are appending an order to the beginning of their IDs
			// which in turn forces them to appear in an explicit order
			if ($metaboxes) {
				foreach ($metaboxes as $i => $metabox) {
					$metabox_order = isset($metabox[0]['order']) ? $metabox[0]['order'] : false;
					$order = $metabox_order;
					if (!$order) { $order = 0; }
					$resorted[$i]  = $order; 
				}
				// then we sort this "ordering" array by the order field
				array_multisort($resorted, SORT_DESC, $metaboxes);
				foreach ( $resorted as $i => $meta_box_content ) {
					add_meta_box( $post_type.'_'.str_replace('-','',sanitize_key($i)), $i,  array($this,'render_meta'), $post_type, 'normal', 'high', $metaboxes[$i][0]);
				}
			}
		}
		// at this point, if we need to, we can hide/display groups of metaboxes
		// (this is a hook you can use)
		do_action('wpx_post_type_edit_screen');
	}

	/**
	 * Render Custom Metaboxes
	 *
	 * This function parses the given custom metabox with html. 
	 *
	 * @since 1.0
	*/
	public function render_meta($obj, $box) {
		
		$hold_box = $box;
		static $nonce_flag = false;
		if ( ! $nonce_flag ) {
			array($this->echoNonce());
			$nonce_flag = true;
		}

		foreach ( $this->metaboxes[$box['title']] as $i=>$box ) {
			
			// always skip the first metabox, as it is settings
			// (but check first if it contains an ID)
			$box_id = isset($box['id']) ? $box['id'] : false;
			$collapsed = isset($box['collapsed']) ? $box['collapsed'] : false;

			if ($i == 0 && !$box_id) {
				/* This should work, but doesn't. Should let us pass a class to each metabox of "closed."
				if ($box['collapsed'] == 1) {
					$metabox_to_filter = 'postbox_classes_'.$obj->post_type.'_'.$obj->post_type.'_'.str_replace('-','',sanitize_title($hold_box['title']));
					add_filter( $metabox_to_filter, array( $this, 'addClosedClassMetabox' ) );
					// test: add_filter('postbox_classes_".obj->post_type."_postexcerpt',array( $this, 'addClosedClassMetabox' )); doesn't append class :( either

				}
				*/
				// instead we'll just hide closed boxes with jQuery
				if ($collapsed == 1) {
					if (!$box['order']) { $order = 0; } else { $order = $box['order']; }
					$collapse_metabox = '<script>
						jQuery(document).ready( function($) {
							$("#'.$obj->post_type.'_'.str_replace("-","",sanitize_key($hold_box["title"])).'").addClass("closed");
						});
					</script>';
					echo $collapse_metabox;
				}
			} else {
				if(is_array($box)){
					$input = new wpx_register_field($box['id'], $box);
				}
			}
		}
	}

	/**
	 * Echo Nonce
	 *
	 * Inserts the nonce for above function.
	 *
	 * @since 1.0
	*/
	public function echoNonce() {
		echo sprintf(
			'<input type="hidden" name="%1$s" id="%1$s" value="%2$s" />',
			'nonce_name',
			wp_create_nonce( plugin_basename(__FILE__) )
		);
	}
	
	/**
	 * Save Custom Metaboxes
	 *
	 * This function saves the custom field data for each metabox in the $metaboxes
	 * array and handles the nonces involved in doing so.
	 *
	 * @since 1.0
	*/
	public function save_post_type_meta($post_id, $post) {
		
		// only save for the current post type we're dealing with
		$nonce_name = isset($_POST['nonce_name']) ? $_POST['nonce_name'] : false;

		if ($post->post_type == $this->id) {
			if ( ! wp_verify_nonce( $nonce_name, plugin_basename(__FILE__) ) ) {
				return $post->ID;
			}
			if ( 'page' == $_POST['post_type'] ) {
				if ( ! current_user_can( 'edit_page', $post->ID ))
					return $post->ID;
			} else {
				if ( ! current_user_can( 'edit_post', $post->ID ))
					return $post->ID;
			}
			foreach ( $this->metaboxes as $box ) {
				foreach ( $box as $fields ) {
					$fields_id = isset($fields['id']) ? $fields['id'] : false;
					$_post_id = isset($_POST[$fields_id]) ? $_POST[$fields_id] : false;
					if ($fields_id) {
						$my_data[$fields_id] = $_post_id;
					}
				}
			}

			foreach ($my_data as $key => $value) {

				if ( 'revision' == $post->post_type ) {
					return;
				}

				if (is_array($value)) {
					// ensure they are keys
					$value = array_filter($value);
					// if the array is empty
					if (empty($value)) {
						// keep it so
						$value = '';
					} else {
						// split it into comma-separated values
						$value = implode(',', (array)$value);		
					}
				}

				// if the postmeta exists
				if ( get_post_meta($post->ID, $key, FALSE) ) {
					// update it
					update_post_meta($post->ID, $key, $value);
				} else {
					// otherwise add it
					add_post_meta($post->ID, $key, $value);
				}

				if (!$value) {
					delete_post_meta($post->ID, $key);
				}
			}

		}
	}

	/*
	 * Close Metabox
	 *
	 * Close some metaboxes by default. This doesn't work (see notes above).
	 *
	 * @since 1.0
	 *
	 * 
	*
	public function addClosedClassMetabox( $classes ) {
		array_push( $classes, 'closed' );
		return $classes;

	}
	*/

}

?>