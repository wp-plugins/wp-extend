<?php
/**
 * Register Custom Options Pages
 *
 * This class will render a custom options page and all metaboxes attached to them in the Dashboard.
 *
 * @package wpx
 * 
*/

class wpx_register_options {

	protected static $instance;

	public static function init() {
		is_null( self::$instance ) AND self::$instance = new self;
		return self::$instance;
	}

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

		$options_args_title = isset($options_args['title']) ? $options_args['title'] : false;
		$options_args_screen_icon = isset($options_args['screen_icon ']) ? $options_args['screen_icon '] : false;
		$options_args_menu_ancestor = isset($options_args['menu_ancestor']) ? $options_args['menu_ancestor'] : false;
		$options_args_menu_parent = isset($options_args['menu_parent']) ? $options_args['menu_parent'] : false;
		$options_args_menu_label = isset($options_args['menu_label']) ? $options_args['menu_label'] : false;
		$options_args_menu_position = isset($options_args['menu_position']) ? $options_args['menu_position'] : false;
		$options_args_capability = isset($options_args['capability']) ? $options_args['capability'] : false;
		$options_args_validation = isset($options_args['validation']) ? $options_args['validation'] : false;
		$options_args_register_metaboxes = isset($options_args['register_metaboxes']) ? $options_args['register_metaboxes'] : false;
		$options_args_icon_url = isset($options_args['icon_url']) ? $options_args['icon_url'] : false;

		// define the vars
		$this->id = 'wpx_options_' .sanitize_key($id);
		$this->key = sanitize_key($id);
		$this->title = $options_args_title;
		$this->screen_icon = $options_args_screen_icon;
		$this->menu_ancestor = $options_args_menu_ancestor;
		$this->menu_parent = $options_args_menu_parent;
		$this->menu_label = $options_args_menu_label;
		$this->menu_position = $options_args_menu_position;
		$this->capability = $options_args_capability;
		$this->validation = $options_args_validation;
		$this->fields = $options_args_register_metaboxes;
		$this->icon_url = $options_args_icon_url;

		// create the menu items
		add_action('admin_menu', array($this, 'add_menu_item'), 11);
		
		// render the form
		if ($this->fields) {
			add_action('admin_init', array($this, 'register_options_page'));
		}

		// run the validation filters
		if ($options_args_validation) {
			foreach($this->validation as $validation_routine) {
				add_filter( 'wpx_validation_'.$this->key.'_'.$validation_routine[0], trim($validation_routine[1]) );
			}
		}

	}

	/**
	 * Register Validation Routines
	 *
	 * Allows for back end validation routines of individual fields. To make use of this, when registering
	 * the options page, you would enter a list of all the validation routines you'd like to run (one for)
	 * each field that needs to be checked). These are entered into the validation field as "nameOfField, functionName".
	 * You need to create a function in your functions file that verifies the output for each field entered here.
	 *
	 * Here is an example: (numberField, exampleValidation)
	 *
	 * In your functions file, to verify the input:
	 * 
	 * function exampleValidation($input) {
	 *		if ($input == 'triggerString') add_settings_error( 'exampleErrorSlug', 'exampleErrorID', 'This is where the error text goes.', 'error' );
	 *		return $input;
	 * }
	 *
	 * Then you need to display a notice:
	 * 
	 * function exampleValidationNotice() {
     *      settings_errors( 'exampleErrorSlug' );
	 * }
	 * add_action( 'admin_notices', 'exampleValidationNotice' );
	 * 
	 * @since 1.0
	*/
	public function register_validation_routines( $input ) {
		foreach( $input as $key => $value ) {
			$input[$key] = apply_filters( 'wpx_validation' . $key, $value );
		}
		return $input;
	}

	/**
	 * Register Options Page
	 *
	 * Saves the options page in the Dashboard.
	 *
	 * @since 1.0
	*/
	public function register_options_page() {

		register_setting($this->id, $this->key, array($this, 'register_validation_routines'));
		$metaboxes = $this->fields;

		// resort the groups
		foreach ($metaboxes as $i => $metabox) {
			$order = isset($metabox[0]['order']) ? $metabox[0]['order'] : false;
			if (!$order) { $order = 0; }
			$resorted[$i] = $metabox;
		}

		// then we sort this "ordering" array by the order field
		// (I have suppressed the notice here because I don't know how to fix it :(! Plz help.)
		@asort($resorted, SORT_NATURAL);

		foreach ($resorted as $i=>$field_section) {

			$field_section_name = 'wpx_opts_'.$i;
			$field_section_key = sanitize_key($field_section_name);
			add_settings_section( $field_section_key, $i, null, $this->id );

			foreach($field_section as $x=>$field) {

				// skip the first metabox, as it is just settings
				// (but check first if it contains an ID)
				$field_id = isset($field['id']) ? $field['id'] : false;
				if ($x == 0 && !$field_id) {
					continue;
				}

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

	/**
	 * Render Options Fields
	 *
	 * Renders each field on the options page.
	 *
	 * @since 1.0
	*/
	public function render_options_fields($args) {
		$args['array_key'] = $this->key;
		$input = new wpx_register_field($args['id'], $args);
	}
	
	/**
	 * Add Menu Item
	 *
	 * Adds menu items to the Dashboard for the options page.
	 *
	 * @since 1.0
	*/
	public function add_menu_item() {

		// if this is a top-level item
		if (!$this->menu_ancestor) {
			if ($this->menu_parent) {
				// if a menu parent was specified, register it as a submenu of that
				add_submenu_page( $this->menu_parent, $this->title, $this->menu_label, $this->capability, $this->id, array($this, 'render_options_page') );
			} else {
				// otherwise, register this as a top-level, and then add itself as the first submenu
				add_menu_page($this->title, $this->menu_label, $this->capability, $this->id,  array($this, 'render_options_page'), $this->icon_url, $this->menu_position);
				add_submenu_page( $this->id, $this->title, $this->menu_label, $this->capability, $this->id );
			}
		// if this is a child of an options page
		} else {
			// get the parent options page
			$parent = get_site_transient('wpx_options_parent_'.$this->id);
			$wpx_transient_array[] = 'wpx_options_parent_'.$this->id;
			if (!$parent) {
				$parent = get_post($this->menu_ancestor);
				set_site_transient('wpx_options_parent_'.$this->id, $parent, YEAR_IN_SECONDS);
			}
			$parent_id = 'wpx_options_'.$parent->post_name;
			add_submenu_page( $parent_id, $this->title, $this->menu_label, $this->capability, 'admin.php?page='.$this->id, array($this, 'render_options_page') );
		}
	}
	
	/**
	 * Render Options Page
	 *
	 * Render the contents of the options page.
	 *
	 * @since 1.0
	*/
	public function render_options_page() {
		?>
		<div class="wrap wpx-bounds">
			<h2><?php echo $this->title; ?></h2>
			<?php do_action($this->id); ?>
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

?>