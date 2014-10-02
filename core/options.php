<?php
/**
* Core: Options Pages
*
* This class will render a custom options page in the Dashboard.
*
* @package    WordPress
* @subpackage wpx 
* @since 1.0
*/

if(!class_exists('wpx_options_page')) {

	class wpx_options_page {

		function __construct( $id, $args = '') {

			// let's specify some defaults
			$defaults = array(
				'title' => $id,
				'menu_label' => $id,
				'screen_icon' => 'options-general',
				'capability' => 'delete_others_pages'
			);

			// standard WP default arguments merge
			$options_args = wp_parse_args( $args, $defaults );
			extract( $options_args, EXTR_SKIP );

			// define the vars
			$this->id = 'wpx_options_' .sanitize_key($id);
			$this->key = sanitize_key($id);
			$this->title = $options_args['title'];
			$this->screen_icon = $options_args['screen_icon'];
			$this->menu_ancestor = $options_args['menu_ancestor'];
			$this->menu_parent = $options_args['menu_parent'];
			$this->menu_label = $options_args['menu_label'];
			$this->menu_position = $options_args['menu_position'];
			$this->capability = $options_args['capability'];
			$this->validation = $options_args['validation'];
			$this->fields = $options_args['register_metaboxes'];
			$this->icon_url = $options_args['icon_url'];

			// create the menu items
			add_action('admin_menu', array($this, 'add_menu_item'));
			
			// render the form
			if ($this->fields) {
				add_action('admin_init', array($this, 'register_options_page'));
			}

			/* run the validation filters
			TK
			if ($validation) {
				foreach($validation as $validation_routine) {
					add_filter( 'wpx_validation_'.$validation_routine[0], $validation_routine[1] );
				}
			}*/
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * Register Options Page
		 *
		 * Saves the options page in the Dashboard.
		 *
		 * @since 1.0
		 *
		 */
		function register_options_page() {

			// run the validation function just once, since we check each field as needed in the validation function
			//TK register_setting($this->id, $this->key, array($this, 'register_validation_routines'));
			register_setting($this->id, $this->key);

			$metaboxes = $this->fields;
			foreach ($metaboxes as $i => $metabox) {
				$order = $metabox[0]['order'];
				if (!$order) { $order = 0; }
				$resorted[$i]  = $order; 
			}
			// then we sort this "ordering" array by the order field
			array_multisort($resorted, SORT_DESC, $metaboxes);

			foreach ($this->fields as $i=>$field_section) {

				$field_section_name = 'wpx_opts_'.$i;
				$field_section_key = sanitize_key($field_section_name);

				// add each section
				add_settings_section( $field_section_key, $i, null, $this->id );
				foreach($field_section as $x=>$field) {

					// skip the first metabox, as it is just settings
					// (but check first if it contains an ID)
					if ($x == 0 && !$field['id']) {
						continue;
					}

					// add all fields in the array
					add_settings_field( 
						$field['id'], 
						$field['label'], 
						array($this,'render_options_fields'), 
						$this->id,
						$field_section_key, 
						$field
					);
				}
			}
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * Render Options Fields
		 *
		 * Renders each field on the options page.
		 *
		 * @since 1.0
		 *
		 */
		function render_options_fields($args) {
			// add the array key into the args
			$args['array_key'] = $this->key;
			$input = new wpx_field_factory($args['id'], $args);
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * Register Validation Routines
		 *
		 * Allows for back end validation routines of individual fields.
		 *
		 * @since 1.0
		 *
		 *
		 TK
		function register_validation_routines( $input ) {
			foreach( $input as $key => $value ) {
				$input[$key] = apply_filters( 'wpx_validation_' . $key, $value );
			}
			return $input;
		}
		*.
		
		/*
		|--------------------------------------------------------------------------
		/**
		 * Add Menu Item
		 *
		 * Adds menu items to the Dashboard for the options page.
		 *
		 * @since 1.0
		 *
		 */
		function add_menu_item() {
			if (!$this->menu_ancestor) {
				if ($this->menu_parent) {
					add_submenu_page( $this->menu_parent, $this->title, $this->menu_label, $this->capability, $this->id, array($this, 'render_options_page') );
				} else {
					add_menu_page($this->title, $this->menu_label, $this->capability, $this->id,  array($this, 'render_options_page'), $this->icon_url, $this->menu_position);
					add_submenu_page( $this->id, $this->title, $this->menu_label, $this->capability, $this->id, array($this, 'render_options_page') );
				}
			} else {
				// get parent post
				$parent = get_post($this->menu_ancestor);
				$parent_id = 'wpx_options_'.$parent->post_name;
				add_submenu_page( $parent_id, $this->title, $this->menu_label, $this->capability, $this->id, array($this, 'render_options_page') );
			}
		}
		
		/*
		|--------------------------------------------------------------------------
		/**
		 * Render Options Page
		 *
		 * Render the contents of the options page.
		 *
		 * @since 1.0
		 *
		 */
		function render_options_page() {
			?>
			<div class="wrap wpx-bounds">
				<?php screen_icon($this->screen_icon); ?>
				<h2><?php echo $this->title; ?></h2>
				<?php do_action($this->id, $output); ?>
				<?php if ($this->fields) { ?>
				<form method="post" class="validate" action="options.php">
					<?php settings_fields($this->id); ?>
					<?php do_settings_sections($this->id); ?>
					<?php submit_button(); ?>
				</form>
				<?php } ?>
			</div>
		<?php
		}

	}
}

?>