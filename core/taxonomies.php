<?php
/**
* Core: Custom Taxonomies
*
* This class will register taxonomies and all metaboxes attached to them.
*
* @package WordPress
* @subpackage wpx 
* @since 1.0
*/

if(!class_exists('wpx_register_taxonomy')) {

	class wpx_register_taxonomy {

		function __construct( $id, $object_type = array(), $args = '' ) {
			
			$this->object_type = $object_type;
			$this->id = $id;
			$this->settings = $args;
			
			// only if we have a tax id and object types specified
			if ($this->id && $this->object_type) {

				// let's specify some common defaults that I prefer
				$taxonomy_defaults = array(
					'name' => $this->id, 
					'hierarchical' => true, 
					'query_var' => $this->id,
					'public'=>true,
					'show_ui'=>true,
					'show_in_nav_menus'=>true,
					'show_tagcloud'=>true,
					'show_admin_column'=>true,
					'rewrite'=>array('slug'=>$this->id,'with_front'=>true, 'hierarchical'=>false),
					'sort'=>true
				);

				// standard WP default arguments merge
				$taxonomy_arguments = wp_parse_args( $this->settings, $taxonomy_defaults );
				extract( $taxonomy_arguments, EXTR_SKIP );

				// if a singular or plural name was specified, pass this into the arguments
				if ($taxonomy_arguments['label_singular'] && $taxonomy_arguments['label_plural']) {
					$taxonomy_arguments['labels'] = array(
						'name' =>$taxonomy_arguments['label_singular'],
						'singular_name' => $taxonomy_arguments['label_singular'],
						'menu_name' => $taxonomy_arguments['label_plural'],
						'all_items' => 'All '.$taxonomy_arguments['label_plural'],
						'edit_item' => 'Edit '.$taxonomy_arguments['label_singular'],
						'update_item' => 'Update '.$taxonomy_arguments['label_singular'],
						'add_new_item' => 'Add New '.$taxonomy_arguments['label_singular'],
						'new_item_name' => 'New '.$taxonomy_arguments['label_singular'],
						'search_items' => 'Search '.$taxonomy_arguments['label_plural'],
						'popular_items' => 'Popular '.$taxonomy_arguments['label_plural'],
						'parent_item' => 'Parent '.$taxonomy_arguments['label_singular'],
						'parent_item_colon' => 'Parent '.$taxonomy_arguments['label_singular'],
						'separate_items_with_commas' => 'Separate '.$taxonomy_arguments['label_plural'].' with commas.',
						'add_or_remove_items' => 'Add or remove '.$taxonomy_arguments['label_plural'],
						'choose_from_most_used' => 'Choose from the most used '.$taxonomy_arguments['label_plural'].'.',
						'not_found' => 'No '.$taxonomy_arguments['label_plural'].' found.'
					);
				}

				// register the taxonomy to this post type
				register_taxonomy(
					$this->id, // the slug / id of the taxonomy
					$this->object_type, // the post types we're attaching to
					$taxonomy_arguments // any arguments
				);

				// if we have metaboxes for this taxonomy
				if ($taxonomy_arguments['register_metaboxes']) {
					// hook into saving/deleting for custom field data
					add_action( $this->id.'_edit_form_fields', array($this,'add_taxonomy_meta'), 10, 2 );
					// I've decided it's not a good idea to have all these fields on initial creation of a term.
					//add_action( $this->id.'_add_form_fields', array($this,'add_taxonomy_meta'), 10, 2 );
					add_action( 'edited_'.$this->id, array($this,'save_taxonomy_meta'), 10, 2 );
					add_action( 'edited_'.$this->id, wpx::manage_image_field_delete(), 10, 2 );
					// I've decided it's not a good idea to have all these fields on initial creation of a term.
					//add_action( 'created_'.$this->id, array($this,'save_taxonomy_meta'), 10, 2 );
					add_action( 'delete_term', array($this, 'delete_taxonomy_meta'), 10, 2);
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
			if ($this->id == $tag->taxonomy) {
				$tax_meta = $this->settings['register_metaboxes'];
			} elseif ($tag == $this->id) {
				$tax_meta = $this->settings['register_metaboxes'];
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

		/*
		|--------------------------------------------------------------------------
		/**
		 * Delete Taxonomy Meta
		 *
		 * These functions save and delete custom field data for a given taxonomy.
		 * These functions are invoked when the taxonomy is declared.
		 *
		 * @since 1.0
		 *
		 */
		public function delete_taxonomy_meta( $term_id ) {  
			$t_id = $term_id;
			$term_meta = get_option( "taxonomy_term_$t_id" );  
			// delete the option array
			delete_option( "taxonomy_term_$t_id", $term_meta );
		}

	}

}

?>