<?php
/**
 * Register Custom Taxonomies
 *
 * This class will register taxonomies and all metaboxes attached to them.
 *
 * @package wpx
 * 
*/

class wpx_register_taxonomy {

	protected static $instance;

	public static function init() {
		is_null( self::$instance ) AND self::$instance = new self;
		return self::$instance;
	}

	function __construct( $id, $object_type = array(), $args = '' ) {
		
		$this->object_type = $object_type;
		$this->id = $id;
		$this->settings = $args;

		// standard WP default arguments merge
		$taxonomy_arguments = $this->settings;

		// get the singular and plural labels
		$taxonomy_label_singular = isset($taxonomy_arguments['label_singular']) ? $taxonomy_arguments['label_singular'] : false;
		$taxonomy_label_plural = isset($taxonomy_arguments['label_plural']) ? $taxonomy_arguments['label_plural'] : false;

		// if a singular or plural name was specified, pass this into the arguments
		if ($taxonomy_label_singular && $taxonomy_label_plural) {
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

		// catch built-in taxonomies for tags and categories
		if ($this->id == 'post_tag' || $this->id == 'category') {

			if ($this->id == 'category') {

				// hook into saving/deleting category terms (when editing a term)
				add_action('category_edit_form_fields', array($this,'add_taxonomy_meta'), 10, 2);
				add_action('category_edit_form_fields', array($this,'clear_transients'), 10, 2);

				// for saving tax meta / clearing transients
				add_action('edited_category', array($this,'save_taxonomy_meta'), 10, 2 );
				add_action('edited_category', array($this,'manage_image_field_delete'), 10, 2 );
				add_action('edited_category', array($this,'clear_transients'), 10, 2 );

				// delete tax meta / clear transients
				add_action( 'delete_category', array($this, 'delete_taxonomy_meta'), 10, 2);
				add_action( 'delete_category', array($this, 'clear_transients'), 10, 2);
			}

			if ($this->id == 'post_tag') {

				// hook into saving/deleting category terms (when editing a term)
				add_action('edit_tag_form_fields', array($this,'add_taxonomy_meta'), 10, 2);
				add_action('edit_tag_form_fields', array($this,'clear_transients'), 10, 2);

				// for saving tax meta / clearing transients
				add_action('edited_post_tag', array($this,'save_taxonomy_meta'), 10, 2 );
				add_action('edited_post_tag', array($this,'manage_image_field_delete'), 10, 2 );
				add_action('edited_post_tag', array($this,'clear_transients'), 10, 2 );

				// delete tax meta / clear transients
				add_action( 'delete_post_tag', array($this, 'delete_taxonomy_meta'), 10, 2);
				add_action( 'delete_post_tag', array($this, 'clear_transients'), 10, 2);
			}

		} else if ($this->id && $this->object_type) {

			$filter_taxonomy_args = $taxonomy_arguments;

			// acceptable parameters for register_taxonomy
			$register_taxonomy = array(
				'label',
				'labels',
				'public',
				'show_ui',
				'show_in_nav_menus',
				'show_tagcloud',
				'meta_box_cb',
				'show_admin_column',
				'hierarchical',
				'update_count_callback',
				'query_var',
				'rewrite',
				'capabilities',
				'sort'
			);

			// remove keys that are not in register_taxonomy
			foreach ($filter_taxonomy_args as $i=>$filter_taxonomy_args) {
				if (!in_array($i, $register_taxonomy)) {
					unset($taxonomy_arguments[$i]);
				}
			}

			// register the taxonomy to this post type
			register_taxonomy(
				$this->id, // the slug / id of the taxonomy
				$this->object_type, // the post types we're attaching to
				$taxonomy_arguments // any arguments
			);

			$taxonomy_arguments = isset($taxonomy_arguments['register_metaboxes']) ? $taxonomy_arguments['register_metaboxes'] : false;
			
			// if we have metaboxes for this taxonomy
			if ($taxonomy_arguments) {

				// hook into saving/deleting for custom field data (when editing a term)
				add_action( $this->id.'_edit_form_fields', array($this,'add_taxonomy_meta'), 10, 2 );
				add_action( $this->id.'_edit_form_fields', array($this, 'clear_transients'), 10, 2 );

				// for saving tax meta / clearing transients
				add_action( 'edited_'.$this->id, array($this,'save_taxonomy_meta'), 10, 2 );
				add_action( 'edited_'.$this->id, array($this, 'manage_image_field_delete'), 10, 2 );
				add_action( 'edited_'.$this->id, array($this, 'clear_transients'), 10, 2 );

				// delete tax meta / clear transients
				add_action( 'delete_term', array($this, 'delete_taxonomy_meta'), 10, 2);
				add_action( 'delete_term', array($this, 'clear_transients'), 10, 2);
			}
		}

	}

	/**
	 * Clear Transients
	 * @since 1.0
	*/
	public function clear_transients() {
		wpx::clear_transients();
	}

	/**
	 * Manage Image Field Delete
	 * @since 1.0
	*/
	public function manage_image_field_delete() {
		wpx::manage_image_field_delete();
	}

	/**
	 * Add Taxonomy Meta
	 *
	 * Here we add custom fields to the new taxonomy.
	 *
	 * @since 1.0
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
			$input = new wpx_register_field($tax_field['id'], $tax_field);
		}
	}

	/**
	 * Save/Delete Custom Field Data
	 *
	 * These functions save custom field data for a given taxonomy.
	 * These functions are invoked when the taxonomy is declared.
	 *
	 * @since 1.0
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
			// save the option array  
			update_option( "taxonomy_term_$t_id", $term_meta );  
		}
	}

	/**
	 * Delete Taxonomy Meta
	 *
	 * These functions delete custom field data for a given taxonomy.
	 * These functions are invoked when the taxonomy is declared.
	 *
	 * @since 1.0
	*/
	public function delete_taxonomy_meta( $term_id ) {  
		$t_id = $term_id;
		$term_meta = get_option( "taxonomy_term_$t_id" );  
		// delete the option array
		delete_option( "taxonomy_term_$t_id");
	}

}

?>