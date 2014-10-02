<?php
/**
* Core: Custom Post Types
*
* This class will register a post type and any custom metaboxes, as well as
* register any taxonomies associated with the post type.
*
* @package WordPress
* @subpackage wpx 
* @since 1.0
*/

if(!class_exists('wpx_register_type')) {

	class wpx_register_type {

		function __construct( $id, $settings = '') {
			
			$this->id = $id;
			$this->settings = $settings;
			$this->label_singular = $settings['label_singular'];

			$this->label_plural = $settings['label_plural'];
			$this->metaboxes = $settings['register_metaboxes'];
			$this->taxonomies = $settings['register_taxonomies'];
			
			if ($this->id) {

				// if this is a default post type, then we have to attach the metaboxes manually
				if ($this->id == 'page' || $this->id == 'post') {

					add_action( 'add_meta_boxes', array($this,'add_post_type_meta') );

				} else {

					// let's specify some common defaults that I prefer
					$defaults = array(
						'label' => $this->id,
						'public' => true,
						'exclude_from_search' => false,
						'publicly_queryable' => true,
						'show_ui' => true,
						'show_in_nav_menus' => true,
						'show_in_menu' => true,
						'show_in_admin_bar' => true,
						'menu_icon' => false,
						'query_var' => true,
						'rewrite' => true,
						'media_type' => 'post',
						'hierarchical' => false,
						'has_archive' => false,
						'rewrite' => array('slug'=>$this->id,'with_front'=>true, 'feeds'=>true),
						'can_export' => true,
						'supports' => false
					);

					// standard WP default arguments merge
					$post_type_args = wp_parse_args( $this->settings, $defaults );
					extract( $post_type_args, EXTR_SKIP );

					// if a singular or plural name was specified, pass this into the arguments
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

					// we want to pass all the regular arguments to register_post_type
					// but register_meta_box_cb must come from this class
					if ($this->metaboxes) { $post_type_args['register_meta_box_cb'] = array($this, 'add_post_type_meta'); }

					//print_r($post_type_args);

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

		/*
		|--------------------------------------------------------------------------
		/**
		 * Define Taxonomy Custom Meta
		 *
		 * Here we add custom fields to the new taxonomy.
		 *
		 * @since 1.0
		 *
		 */
		public function add_taxonomy_meta($tag) {

			foreach($this->taxonomies as $taxonomy) {
				if ($taxonomy[0] == $tag->taxonomy) {
					$tax_meta = $taxonomy[1]['register_metaboxes'];
				} elseif ($tag == $taxonomy[0]) {
					$tax_meta = $taxonomy[1]['register_metaboxes'];
				}
			}
			$t_id = $tag->term_id;
			$term_meta = get_option( "taxonomy_term_$t_id" );
			foreach($tax_meta as $i=>$tax_field) {
				$input = new wpx_field_factory($tax_field['id'], $tax_field);
			}
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * Save/Delete Custom Field Data
		 *
		 * These functions save and delete custom field data for a given taxonomy.
		 * These functions are invoked when the taxonomy is declared.
		 *
		 * @since 1.0
		 *
		 */
		public function save_taxonomy_meta( $term_id ) {
			if ( isset( $_POST['term_meta'] ) ) {  
				$t_id = $term_id;  
				$term_meta = get_option( "taxonomy_term_$t_id" );  
				$cat_keys = array_keys( $_POST['term_meta'] );  
					foreach ( $cat_keys as $key ){  
					if ( isset( $_POST['term_meta'][$key] ) ){  
						$term_meta[$key] = $_POST['term_meta'][$key];  
					}
				}
				//save the option array  
				update_option( "taxonomy_term_$t_id", $term_meta );  
			}
		}
		
		public function delete_taxonomy_meta( $term_id ) {  
			$t_id = $term_id;
			$term_meta = get_option( "taxonomy_term_$t_id" );  
			// delete the option array
			delete_option( "taxonomy_term_$t_id", $term_meta );
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * Add Custom Metaboxes
		 *
		 * This function loops through each metabox in the $metaboxes array of the class
		 * and uses the add_meta_box core WP function to attach the metabox to the given
		 * post type.
		 *
		 * @since 1.0
		 *
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
				foreach ($metaboxes as $i => $metabox) {
					$order = $metabox[0]['order'];
					if (!$order) { $order = 0; }
					$resorted[$i]  = $order; 
				}
				// then we sort this "ordering" array by the order field
				array_multisort($resorted, SORT_DESC, $metaboxes);
				foreach ( $resorted as $i => $meta_box_content ) {
					add_meta_box( $post_type.'_'.str_replace('-','',sanitize_key($i)), $i,  array($this,'render_meta'), $post_type, 'normal', 'high', $metaboxes[$i][0]);
				}
			}
			// at this point, if we need to, we can hide/display based on template concerns
			// (this is a hook you can use)
			do_action('wpx_post_type_edit_screen');
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * Render Custom Metaboxes
		 *
		 * This function renders the given custom metabox using the wpx_meta_html function. 
		 *
		 * @since 1.0
		 *
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
				if ($i == 0 && !$box['id']) {
					// if postbox_classes actually worked for custom post types, this too would work
					// but alas, I don't know why this doesn't work.
					/*
					if ($box['collapsed'] == 1) {
						$metabox_to_filter = 'postbox_classes_'.$obj->post_type.'_'.str_replace('-','',sanitize_title($hold_box['title']));
						add_filter( $metabox_to_filter, array( $this, 'addClosedClassMetabox' ) );
					}
					*/
					// i am instead reduced to this chicanery
					if ($box['collapsed'] == 1) {
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
						$input = new wpx_field_factory($box['id'], $box);
					}
				}
			}
		}
		
		/*
		|--------------------------------------------------------------------------
		/**
		 * Echo Nonces
		 *
		 * Inserts the nonce for above function.
		 *
		 * @since 1.0
		 *
		 */
		public function echoNonce() {
			echo sprintf(
				'<input type="hidden" name="%1$s" id="%1$s" value="%2$s" />',
				'nonce_name',
				wp_create_nonce( plugin_basename(__FILE__) )
			);
		}
		
		/*
		|--------------------------------------------------------------------------
		/**
		 * Save Custom Metaboxes
		 *
		 * This function saves the custom field data for each metabox in the $metaboxes
		 * array and handles the nonces involved in doing so. 
		 *
		 * @since 1.0
		 *
		 */
		public function save_post_type_meta($post_id, $post) {
			// only save for the current post type we're dealing with
			if ($post->post_type == $this->id) {
				if ( ! wp_verify_nonce( $_POST['nonce_name'], plugin_basename(__FILE__) ) ) {
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
						$my_data[$fields['id']] =  $_POST[$fields['id']];
					}
				}
				foreach ($my_data as $key => $value) {
					if ( 'revision' == $post->post_type ) {
						return;
					}
					$value = implode(',', (array)$value);
					if ( get_post_meta($post->ID, $key, FALSE) ) {
					update_post_meta($post->ID, $key, $value);
					} else {
					add_post_meta($post->ID, $key, $value);
				}
				if (!$value) {
					delete_post_meta($post->ID, $key);
				}
				}
			}
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * Close Metabox
		 *
		 * Close some metaboxes by default. This doesn't work (see notes above).
		 *
		 * @since 1.0
		 *
		public function addClosedClassMetabox( $classes ) {

			array_push( $classes, 'closed' );
			return $classes;

		}
		*/

	}

}

?>