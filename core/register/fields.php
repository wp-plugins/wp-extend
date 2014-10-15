<?php
/**
 * Register Custom Metabox Fields
 *
 * This class generates the formatting for individual custom fields based on context.
 * These fields are used for options pages, custom post type metaboxes, and taxonomy metaboxes.
 *
 * @package wpx
 * 
*/

class wpx_register_field {

	protected static $instance;

	public static function init() {
		is_null( self::$instance ) AND self::$instance = new self;
		return self::$instance;
	}

	function __construct( $meta_key, $args = '') {

		global $post;
		global $wpx_transient_array;

		// some defaults
		$defaults = array(
			'label' => '',
			'description' => '',
			'field' => 'text',
			'required' => 'false'
		);

		// default wp args extraction/merge
		$settings = wp_parse_args( $args, $defaults );
		extract( $settings, EXTR_SKIP );
		
		// if this is a post...
		if ($post) $post_meta = get_post_meta($post->ID, $meta_key, true);

		// class vars
		$this->meta = isset($post_meta) ? $post_meta : false;
		$this->object = $post;
		$this->meta_key_raw = $meta_key;
		$this->meta_key = $meta_key;
		$this->label = isset($settings['label']) ? $settings['label'] : false;
		$this->description = isset($settings['description']) ? $settings['description'] : false;
		$this->type = isset($settings['field']) ? $settings['field'] : false;
		$this->array_key = isset($settings['array_key']) ? $settings['array_key'] : false;
		$this->required = isset($settings['required']) ? $settings['required'] : false;
		$this->value = isset($settings['value']) ? $settings['value'] : false;
		$this->settings = $settings;

		// if we're dealing with a taxonomy
		$request_tag_id = isset($_REQUEST['tag_ID']) ? $_REQUEST['tag_ID'] : false;
		$request_taxonomy = isset($_REQUEST['taxonomy']) ? $_REQUEST['taxonomy'] : false;
		$tag = get_term_by( 'ID', $request_tag_id, $request_taxonomy);

		if ($tag) {
			$t_id = $tag->term_id;
			$this->term_id = $t_id;
			$this->object = $tag;
			$term_meta = get_option( "taxonomy_term_$t_id" );
			$term_field_value = isset($term_meta[$meta_key]) ? $term_meta[$meta_key] : false;
			$this->meta = $term_field_value;
			$this->meta_key = 'term_meta['.$meta_key.']'; // reset the ID
		}

		// get the screen we're on
		$screen = get_current_screen();

		// if this is an options page
		if (stristr($screen->base, 'wpx_options_'.$this->array_key) == true) {
			// we need to retrieve the value as an array from the global options
			$options_group = get_option($this->array_key);
			$this->meta = isset($options_group[$this->meta_key]) ? $options_group[$this->meta_key] : false;
			// options settings are saved as an array
			$this->meta_key = $this->array_key.'['.$this->meta_key.']';
		}

		// set the state
		if ($tag) {
			$this->state = 'taxonomy';
		} elseif (stristr($screen->base, 'wpx_options_'.$this->array_key) == true) {
			$this->state = 'options-page';
		} else {
			$this->state = 'post-type';
		}

		// first try to render the correct input type
		if ( method_exists($this,$settings['field']) ) {
			call_user_func(array($this,$settings['field']));
		
		// then try to call the method from outside the class
		} else if (function_exists($settings['field'])) {

			// let's pass some data to that method			
			$args = array(
				'id' => $this->meta_key_raw,
				'meta_key' => $this->meta_key,
				'label' => $this->label,
				'description' => $this->description,
				'field_type' => $this->type,
				'value' => $this->meta,
				'array_key' => $this->array_key,
				'post_object' => $post,
				'term_object' => $term,
				'markup' => $this->markup($this->type),
				'state' => $this->state
			);

			// then call the method
			call_user_func($settings['field'], $args);
		} else {

			// default to text() if no method is defined
			call_user_func(array($this,'text'));
		}
		
	}

	/**
	 * Field Markup Switch
	 *
	 * Provides the correct markup for rendering meta box fields.
	 *
	 * @since 1.0
	*/
	public function markup($defined_type) {

		// preamble
		if ($this->required == true) { $required = ' form-required '; } else { $required = false; }		
		$screen = get_current_screen();
		$action = isset($_GET['action']) ? $_GET['action'] : false;
		
		// for the edit screen of taxonomies (they use tables)
		if ($screen->base == 'edit-tags' && $action == 'edit') {
			$markup = array(
				'before_container' => '<tr class="'.$this->state.' meta-'.$this->meta_key.' wpx-fields '.$defined_type.''.$required.' form-field edit-tags">',
				'before_label' => '<th scope="row" valign="top">',
				'after_label' => '</th>',
				'before_input' => '<td>',
				'after_input' => '',
				'after_container' => '</tr>',
				'before_description' => '<p class="description">',
				'after_description' => '</p></td>'
			);

		// for the post type metabox screen
		} else if ($screen->base == 'edit-tags') {
			$markup = array(
				'before_container' => '<div class="'.$this->state.' meta-'.$this->meta_key.' wpx-fields '.$defined_type.''.$required.' add-tags form-field '.$defined_type.'">',
				'before_label' => '',
				'after_label' => '',
				'before_input' => '',
				'after_input' => '',
				'after_container' => '</div>',
				'before_description' => '<p>',
				'after_description' => '</p>'
			);

		// for the post type metabox screen or options pages
		} else {		
			$markup = array(
				'before_container' => '<div class="'.$this->state.' meta-'.$this->meta_key.' wpx-fields '.$defined_type.''.$required.' form-field '.$defined_type.' textfield"><div class="wpx-input-bubble">',
				'before_label' => '<div class="column-left">',
				'after_label' => '</div>',
				'before_input' => '<div class="column-right">',
				'after_input' => '</div>',
				'after_container' => '</div></div>',
				'before_description' => '<p class="description"><em>',
				'after_description' => '</em></p>'
			);
		}
		return $markup;
	}

	/**
	 * Textfields
	 *
	 * Defines textfield. These fields are not intended to contain HTML markup. 
	 *
	 * @since 1.0
	*/
	public function text() {

		$value = $this->value;
		$markup = $this->markup('text');
		?>

		<?php echo $markup['before_container']; ?>
		<?php echo $markup['before_label']; ?>

		<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>

		<?php echo $markup['after_label']; ?>
		<?php echo $markup['before_input']; ?>

		<input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>" value="<?php if ($this->meta == false || $this->meta == 'false') { echo $value; } else { echo htmlspecialchars($this->meta); } ?>" />
		<?php echo $markup['before_description']; ?><?php echo htmlspecialchars($this->description); ?><?php echo $markup['after_description']; ?>

		<?php echo $markup['after_input']; ?>
		<?php echo $markup['after_container']; ?>
		<?php
	}

	/**
	 * Checkboxes
	 *
	 * Defines checkboxes. We use the hidden field trick to force the checkbox to save its state.
	 *
	 * @since 1.0
	*/
	public function checkbox() {

		$markup = $this->markup('checkbox');
		?>

		<?php echo $markup['before_container']; ?>
		<?php echo $markup['before_label']; ?>

		<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>

		<?php echo $markup['after_label']; ?>
		<?php echo $markup['before_input']; ?>

		<input type="hidden" name="<?php echo $this->meta_key; ?>" value="0" />
		<input <?php checked( '1' ==  $this->meta, true); ?> type="checkbox" class="checkbox input" name="<?php echo $this->meta_key; ?>" value="1" />
		<?php echo $markup['before_description']; ?><?php echo htmlspecialchars($this->description); ?><?php echo $markup['after_description']; ?>

		<?php echo $markup['after_input']; ?>
		<?php echo $markup['after_container']; ?>
		<?php
	}
	
	/**
	 * Textarea
	 *
	 * Defines raw textareas. The value of these fields are not intended to contain HTML markup. 
	 *
	 * @since 1.0
	*/
	public function textarea() {

		$markup = $this->markup('textarea');
		?>

		<?php echo $markup['before_container']; ?>
		<?php echo $markup['before_label']; ?>

		<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>

		<?php echo $markup['after_label']; ?>
		<?php echo $markup['before_input']; ?>

		<textarea class="input textarea" rows="5" cols="50" name="<?php echo $this->meta_key; ?>"><?php echo htmlspecialchars($this->meta); ?></textarea>
		<?php echo $markup['before_description']; ?><?php echo htmlspecialchars($this->description); ?><?php echo $markup['after_description']; ?>

		<?php echo $markup['after_input']; ?>
		<?php echo $markup['after_container']; ?>
		<?php
	}
	
	/**
	 * File
	 *
	 * This field is a textfield with the class "wpx-media" assigned to it.
	 * Any textfield with this class will automatically render a button to open the
	 * Media Library so that the path to the uploaded file can be saved into the field. 
	 *
	 * @since 1.0
	*/
	public function file() {

		global $post;
		$markup = $this->markup('file');
		?>

		<?php echo $markup['before_container']; ?>
		<?php echo $markup['before_label']; ?>
					
		<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
		<?php echo $markup['after_label']; ?>
		<?php echo $markup['before_input']; ?>
					
		<input data-source="<?php echo $post->ID; ?>" class="wpx-media input file" type="text" name="<?php echo $this->meta_key; ?>" value="<?php echo htmlspecialchars($this->meta); ?>" />
					
		<?php if ($this->meta) { ?>
		<p class="wpx-field-controls">
			<a href="#" class="wpx-clear-image-field wpx-delete-image-<?php echo $this->meta_key; ?> button button-small">Clear</a>
			<a href="#" data-id="<?php echo htmlspecialchars(wpx::get_attachment_id_by_url($this->meta)); ?>" class="wpx-delete-image-field wpx-delete-image-<?php echo $this->meta_key; ?> button button-small">Delete</a>
		</p>
		<?php } ?>
					
		<?php echo $markup['before_description']; ?><?php echo htmlspecialchars($this->description); ?><?php echo $markup['after_description']; ?>
		<?php echo $markup['after_input']; ?>
		<?php echo $markup['after_container']; ?>
		<?php
	}

	/**
	 * Image
	 *
	 * This field is the same as the file field, except that it shows a preview of the image
	 * after you upload it. Also provides the option to "clear" the field and the image
	 * though the image will remain in the Media Library (this is intentional) or delete the image
	 * in which case the image will be deleted when the user saves the post.
	 *
	 * @since 1.0
	*/
	public function image() {

		global $post;
		$markup = $this->markup('image');
		?>

		<?php echo $markup['before_container']; ?>
		<?php echo $markup['before_label']; ?>
					
		<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
				
		<?php echo $markup['after_label']; ?>
		<?php echo $markup['before_input']; ?>
				
		<?php if ($this->meta) { ?>
			<div class="wpx-preview-image"><a href="<?php echo $this->meta; ?>" target="_blank"><img src="<?php echo $this->meta; ?>" alt=""></a></div>
		<?php } ?>
				
		<?php if ($this->meta) { ?><div class="wpx-has-image"><?php } ?>
					
		<input data-source="<?php echo $post->ID; ?>" class="wpx-media input file" type="text" name="<?php echo $this->meta_key; ?>" value="<?php echo htmlspecialchars($this->meta); ?>" />
		
		<?php if ($this->meta) { ?>
		<p class="wpx-field-controls">
			<a href="#" data-id="<?php echo htmlspecialchars(wpx::get_attachment_id_by_url($this->meta)); ?>" class="wpx-delete-image-field wpx-delete-image-<?php echo $this->meta_key; ?> button button-small">Delete</a>
			<a href="#" class="wpx-clear-image-field wpx-delete-image-<?php echo $this->meta_key; ?> button button-small">Clear</a>
		</p>
		<?php } ?>

		<?php echo $markup['before_description']; ?><?php echo htmlspecialchars($this->description); ?><?php echo $markup['after_description']; ?>

		<?php if ($this->meta) { ?></div><?php } ?>

		<?php echo $markup['after_input']; ?>
		<?php echo $markup['after_container']; ?>
		<?php
	}

	/**
	 * TinyMCE
	 *
	 * Uses wp_editor() to render a tinyMCE editor on the field.
	 * Still susceptible to the "moving metaboxes" bug. 
	 *
	 * @since 1.0
	*/
	public function tinymce() {

		$markup = $this->markup('tinymce');
		?>

		<?php echo $markup['before_container']; ?>
		<?php echo $markup['before_label']; ?>

		<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>

		<?php echo $markup['after_label']; ?>
		<?php echo $markup['before_input']; ?>

		<?php 
			// need to remove brackets in the case of a taxonomy term since 3.9 (tinymce does not like brackets)
			// when used in the ID of the tinymce instance
			$tinymce_id = str_replace('[', '', $this->meta_key);
			$tinymce_id = str_replace(']', '', $tinymce_id);
		?>
		<div class="input ve"><?php wp_editor( $this->meta, $tinymce_id, array('textarea_name'=>$this->meta_key) ); ?></div>
		<?php echo $markup['before_description']; ?><?php echo htmlspecialchars($this->description); ?><?php echo $markup['after_description']; ?>

		<?php echo $markup['after_input']; ?>
		<?php echo $markup['after_container']; ?>
		<?php
	}

	/**
	 * Users
	 *
	 * This field lets the user pick a user or multiple users configured in the field.
	 *
	 * @since 1.0
	*/
	public function user() {
		
		global $post;
		
		// saved meta
		if (!is_array($this->meta)) {
			$meta_array = explode(',',$this->meta);
		} else {
			$meta_array = $this->meta;
		}
		
		// standard markup stuff
		$markup = $this->markup('user');
		
		echo $markup['before_container'];
		echo $markup['before_label'];
		if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } 
		echo $markup['after_label'];
		echo $markup['before_input'];
		
		// get properties of this field type
		if ($this->state == 'options-page') {
			// if this is an options page
			$field_key = wpx::extract_field_key($this->meta_key_raw, $this->array_key);
		} else if($this->state == 'taxonomy') {
			// if this is a taxonomy
			$field_key = get_page_by_path($this->meta_key_raw, OBJECT, 'wpx_fields');
		} else {
			// otherwise it's a post screen
			$field_key = wpx::extract_field_key($this->meta_key, $post->post_type);
		}

		// get the relevant field
		$multiple = get_post_meta($field_key->ID, '_wpx_fields_user_multiple', true);
		
		// relationship type
		$relationship = get_post_meta($field_key->ID, '_wpx_fields_user_roles', true);
		$relationship_array = explode(',',$relationship);
		sort($relationship_array);
		
		// if multiple, let's allow them to choose multiple posts
		if ($multiple) {
		?>
		<div class="wpx-dropdown-selector">

			<select class="input select">
				<option value="">-- Select --</option>
				<?php foreach($relationship_array as $relationship) { ?>
				<optgroup label="<?php echo ucwords($relationship); ?>">
				<?php 
					$users_in_relationship = get_users( array('role'=>$relationship,'orderby'=>'display_name') );
					foreach($users_in_relationship as $i=>$option) { 
						if (in_array($option->ID, $meta_array)) {
							continue;
						} else {
							?><option value="<?php echo $option->ID; ?>!wpx!<?php echo htmlspecialchars($option->data->display_name); ?>!wpx!<?php echo htmlspecialchars($this->meta_key); ?>"><?php echo $option->data->display_name; ?></option><?php
					}
				} ?>
				</optgroup>
				<?php } ?>
			</select>
		</div>
		<div class="wpx-checkbox-multi">
			<ul>
				<?php
				$saved_users = get_users( array('include'=>$meta_array) );
				foreach($saved_users as $i=>$option) { 
					if (in_array($option->ID, $meta_array)) { 
				?>
					<li><input type="checkbox" <?php if (is_array($meta_array)) { if (in_array($option->ID, $meta_array)) { echo 'checked="checked"'; } } ?> name="<?php echo $this->meta_key; ?>[]" value="<?php echo $option->ID; ?>"><?php echo $option->data->display_name; ?></li>
				<?php } ?>
				<?php } ?>
			</ul>
		</div>
		<?php 
		// otherwise only a single post can be selected
		} else { 
		?>
		<select class="input select" name="<?php echo $this->meta_key; ?>">
			<option value="">-- Select --</option>
			<?php foreach($relationship_array as $relationship) { ?>
				<optgroup label="<?php echo ucwords($relationship); ?>">
				<?php $users_in_relationship = get_users( array('role'=>$relationship,'orderby'=>'display_name') ); ?>
				<?php foreach($users_in_relationship as $i=>$option) {  ?>
					<option name="<?php echo $this->meta_key; ?>" <?php if (is_array($meta_array)) { if (in_array($option->ID, $meta_array)) { echo 'selected="selected"'; } } ?> value="<?php echo $option->ID; ?>"><?php echo $option->data->display_name; ?></option>
				<?php } ?>
				</optgroup>
			<?php } ?>
		</select>

		<?php }
		echo $markup['before_description']; 
		echo htmlspecialchars($this->description); 
		echo $markup['after_description'];
		echo $markup['after_input'];
		echo $markup['after_container'];
	}

	/**
	 * Post Relationship
	 *
	 * This field lets the user pick a post or multiple posts from post types configured in the field.
	 *
	 * @since 1.0
	*/
	public function post() {
	
		global $post;
	
		// saved meta
		if (!is_array($this->meta)) {
			$meta_array = explode(',',$this->meta);
		} else {
			$meta_array = $this->meta;
		}
	
		// standard markup stuff
		$markup = $this->markup('select_post');
		echo $markup['before_container'];
		echo $markup['before_label'];
		if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } 
		echo $markup['after_label'];
		echo $markup['before_input'];
	
		// get properties of this field type
		if ($this->state == 'options-page') {
			// if this is an options page
			$field_key = wpx::extract_field_key($this->meta_key_raw, $this->array_key);
		} elseif($this->state == 'taxonomy') {
			// if this is a taxonomy
			$field_key = get_page_by_path($this->meta_key_raw, OBJECT, 'wpx_fields');
		} else {
			// otherwise it's a post screen
			$field_key = wpx::extract_field_key($this->meta_key, $post->post_type);
		}

		// get the relevant field
		$multiple = get_post_meta($field_key->ID, '_wpx_fields_post_multiple', true);
		
		// relationship type
		$relationship = get_post_meta($field_key->ID, '_wpx_fields_post_objects', true);
		$relationship_array = explode(',',$relationship);
		sort($relationship_array);
		
		// if multiple, let's allow them to choose multiple posts
		if ($multiple) {
		?>
		<div class="wpx-dropdown-selector">
			<select class="input select">
				<option value="">-- Select --</option>
				<?php foreach($relationship_array as $relationship) { ?>
				<?php $relationship_object = get_post_type_object( $relationship ); ?>
				<optgroup label="<?php if ($relationship_object->labels->name == 'Media') { echo 'Attachments'; } else { echo $relationship_object->labels->name; } ?>">
				<?php 
					$posts_in_relationship = get_posts(array('posts_per_page'=>-1, 'post_type'=>$relationship, 'orderby'=>'title', 'order'=>'ASC'));
					foreach($posts_in_relationship as $i=>$option) { 
							if (in_array($option->ID, $meta_array)) {
								continue;
							} else {
								?><option value="<?php echo $option->ID; ?>!wpx!<?php echo get_the_title($option->ID); ?>!wpx!<?php echo htmlspecialchars($this->meta_key); ?>"><?php if (get_the_title($option->ID)) { echo get_the_title($option->ID); } else { echo $option->ID; } ?></option><?php
						}
				} ?>
				</optgroup>
				<?php } ?>
			</select>
		</div>
		<div class="wpx-checkbox-multi">
			<ul>
				<?php
				$saved_posts = get_posts(array('post__in'=>$meta_array, 'post_type'=>$relationship_array, 'orderby'=>'title', 'order'=>'ASC'));
				foreach($saved_posts as $i=>$option) { 
					if (in_array($option->ID, $meta_array)) { 
				?>
					<li><input type="checkbox" <?php if (is_array($meta_array)) { if (in_array($option->ID, $meta_array)) { echo 'checked="checked"'; } } ?> name="<?php echo $this->meta_key; ?>[]" value="<?php echo $option->ID; ?>"><?php echo get_the_title($option->ID); ?></li>
				<?php } ?>
				<?php } ?>
			</ul>
		</div>
		<?php 
		// if only one post can be selected
		} else { 
		?>
		<select class="input select" name="<?php echo $this->meta_key; ?>">
			<option value="">-- Select --</option>
				<?php foreach($relationship_array as $relationship) { ?>
					<?php $relationship_object = get_post_type_object( $relationship ); ?>
					<optgroup label="<?php if ($relationship_object->labels->name == 'Media') { echo 'Attachments'; } else { echo $relationship_object->labels->name; } ?>">
					<?php $posts_in_relationship = get_posts(array('posts_per_page'=>-1, 'orderby'=>'name', 'order'=>'ASC', 'post_type'=>$relationship, 'orderby'=>'title', 'order'=>'ASC')); ?>
					<?php foreach($posts_in_relationship as $i=>$option) { ?>
						<option name="<?php echo $this->meta_key; ?>" <?php if (is_array($meta_array)) { if (in_array($option->ID, $meta_array)) { echo 'selected="selected"'; } } ?> value="<?php echo $option->ID; ?>"><?php if (get_the_title($option->ID)) { echo get_the_title($option->ID).' ('.str_replace(home_url(),'',get_permalink($option->ID)).')'; } else { echo $option->ID; } ?></option>
					<?php } ?>
				<?php } ?>
			</optgroup>
		</select>
		<?php }
		echo $markup['before_description'];
		echo htmlspecialchars($this->description); 
		echo $markup['after_description'];
		echo $markup['after_input'];
		echo $markup['after_container'];
	}

	/**
	 * Term
	 *
	 * This field lets the user pick a term or multiple terms from taxonomies configured in the field.
	 *
	 * @since 1.0
	*/
	public function term() {
		
		global $post;
		
		// saved meta
		if (!is_array($this->meta)) {
			$meta_array = explode(',',$this->meta);
		} else {
			$meta_array = $this->meta;
		}
		
		// standard markup stuff
		$markup = $this->markup('select_term');
		echo $markup['before_container'];
		echo $markup['before_label'];
		if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } 
		echo $markup['after_label'];
		echo $markup['before_input'];
		
		// get properties of this field type
		if ($this->state == 'options-page') {
			$field_key = wpx::extract_field_key($this->meta_key_raw, $this->array_key);
		} else if($this->state == 'taxonomy') {
			$field_key = get_page_by_path($this->meta_key_raw, OBJECT, 'wpx_fields');
		} else {
			$field_key = wpx::extract_field_key($this->meta_key, $post->post_type);
		}

		// get the relevant field
		$multiple = get_post_meta($field_key->ID, '_wpx_fields_term_multiple', true);
		
		// relationship type
		$relationship = get_post_meta($field_key->ID, '_wpx_fields_term_objects', true);
		$relationship_array = explode(',',$relationship);
		sort($relationship_array);
		
		// if multiple, let's allow them to choose multiple terms
		if ($multiple) {
		?>
		<div class="wpx-dropdown-selector">
			<select class="input select">
				<option value="">-- Select --</option>
				<?php foreach($relationship_array as $relationship) { ?>
				<?php $relationship_object = get_taxonomy( $relationship ); 	?>
				<optgroup label="<?php echo $relationship_object->labels->name; ?>">
				<?php 
					$terms_in_object = get_terms($relationship, array('orderby'=>'name', 'order'=>'ASC', 'hide_empty'=>false));
					foreach($terms_in_object as $i=>$option) { 
						if (in_array($option->term_id, $meta_array)) {
							continue;
						} else {
				?>
					<option value="<?php echo $option->term_id; ?>!wpx!<?php echo $option->name; ?>!wpx!<?php echo htmlspecialchars($this->meta_key); ?>"><?php echo $option->name; ?></option>
				<?php
					}
				} ?>
				</optgroup>
				<?php } ?>
			</select>
		</div>
		<div class="wpx-checkbox-multi">
			<ul>
				<?php
				$all_taxonomies = get_taxonomies();
				foreach($all_taxonomies as $i=>$taxonomy) {
					$from_taxonomies[] = $i;
				}
				$saved_terms = get_terms( $from_taxonomies, array('include'=>$meta_array) );
				foreach($saved_terms as $i=>$option) { 
					if (in_array($option->term_id, $meta_array)) { 
				?>
					<li><input type="checkbox" <?php if (is_array($meta_array)) { if (in_array($option->term_id, $meta_array)) { echo 'checked="checked"'; } } ?> name="<?php echo $this->meta_key; ?>[]" value="<?php echo $option->term_id; ?>"><?php echo $option->name; ?></li>
				<?php } ?>
				<?php } ?>
			</ul>
		</div>
		<?php 
		// only one term is possible to select
		} else { 
		?>
		<select class="input select" name="<?php echo $this->meta_key; ?>">
			<option value="">-- Select --</option>
			<?php foreach($relationship_array as $relationship) { ?>
				<?php $relationship_object = get_taxonomy( $relationship ); 	?>
				<optgroup label="<?php echo $relationship_object->labels->name; ?>">
				<?php $terms_in_object = get_terms($relationship, array('orderby'=>'name', 'order'=>'ASC', 'hide_empty'=>false)); ?>
				<?php foreach($terms_in_object as $i=>$option) {  ?>
					<option name="<?php echo $this->meta_key; ?>" <?php if (is_array($meta_array)) { if (in_array($option->term_id, $meta_array)) { echo 'selected="selected"'; } } ?> value="<?php echo $option->term_id; ?>"><?php echo $option->name; ?></option>
				<?php } ?>
				</optgroup>
			<?php } ?>
		</select>
		<?php }
		echo $markup['before_description']; 
		echo htmlspecialchars($this->description);
		echo $markup['after_description'];
		echo $markup['after_input'];
		echo $markup['after_container'];
	}

	/**
	 * Gallery
	 *
	 * Displays the image attachments of the post selected (the post type for this is defined in the field)
	 * and allows users to delete images attached to that post.
	 *
	 * @since 1.0
	*/
	public function gallery() {
		
		global $post;
		
		// saved values
		$meta_array = explode(',',$this->meta);
		
		// standard markup
		$markup = $this->markup('gallery');
		
		// let's get the post type assigned to this.
		if ($this->state == 'options-page') {
			$field_key = wpx::extract_field_key($this->meta_key_raw, $this->array_key);
		} else if($this->state == 'taxonomy') {
			$field_key = get_page_by_path($this->meta_key_raw, OBJECT, 'wpx_fields');
		} else {
			$field_key = wpx::extract_field_key($this->meta_key, $post->post_type);
		}
		
		// get the relevant field
		$gallery_post_type = get_post_meta($field_key->ID, '_wpx_fields_gallery_cpt', true);
		$galleries = get_posts(array('post_type'=>$gallery_post_type, 'posts_per_page'=>-1, 'orderby'=>'title', 'order'=>'ASC'));
		
		?>
		<?php echo $markup['before_container']; ?>
		<?php echo $markup['before_label']; ?>
			<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
		<?php echo $markup['after_label']; ?>
		<?php echo $markup['before_input']; ?>

		<?php
			// if this is a gallery type of post, show its attachments
			// rather than provide the option to associate a post
			if (!$post) {
				$post_id = false;
			} else {
				$post_id = $post->ID;
			}

			if ( (get_post_type($post_id) == $gallery_post_type) ) {
				// don't show the select
			} else {
				// otherwise let the user pick a post to associate
			?>
			<select class="input select" name="<?php echo $this->meta_key; ?>">
				<option value="">-- Select --</option>
				<?php foreach($galleries as $gallery) { ?>
				<option name="<?php echo $this->meta_key; ?>" <?php if (is_array($meta_array)) { if (in_array($gallery->ID, $meta_array)) { echo 'selected="selected"'; } } ?> value="<?php echo $gallery->ID; ?>"><?php if (get_the_title($gallery->ID)) { echo get_the_title($gallery->ID); } else { echo $gallery->ID; } ?></option>
				<?php } ?>
			</select>
		<?php } ?>

		<?php 
			// once the user saves, we know what gallery to retrieve
			if($this->state == 'taxonomy') {
				$gallery_id = wpx::get_taxonomy_meta($this->term_id, $this->meta_key_raw);
			} elseif ($this->state == 'options-page') {
				$gallery_id = wpx::get_option_meta($this->array_key, $this->meta_key_raw);
			} else {
				$gallery_id = get_post_meta($post->ID, $this->meta_key, true);
			}

			// if this is not a post screen, then set $post to false
			if (!$post) {
				$post_id = false; 
			} else {
				$post_id = $post->ID;
			}

			if ( $gallery_id || (get_post_type($post_id) == $gallery_post_type && !($this->state == 'taxonomy') && !($this->state == 'options-page')) ) {
				?><div class="wpx-gallery-container"><?php
				if (get_post_type($post_id) == $gallery_post_type) $gallery_id = $post_id;
				$images = wpx::get_attachments($gallery_id, 'ASC', 'menu_order ID', true, 'image', 'inherit');
				$count = 1;
				if ($images) {
					foreach($images as $image) {
						?>
						<div class="wpx-gallery-image">
							<?php $resized_image = wpx::resize( $image->guid, 400, 400, true ); ?>
							<a data-id="<?php echo $image->ID; ?>" href="#"><img src="<?php echo $resized_image['url']; ?>" alt=""></a>
						</div>
						<?php
						$count++;
						if ($count == 6) echo '<br class="clear">';
						if ($count == 6) $count = 1;
					}
				} else {
					echo '<em>There are no images attached to this post.</em>';
				}
				?></div>
				<p class="wpx-field-controls"><a href="#" data-source="<?php echo $gallery_id; ?>" class=" button button-small wpx-media-gallery">Manage Images</a>
				<a href="<?php bloginfo('url'); ?>/wp-admin/post.php?post=<?php echo $gallery_id; ?>&action=edit" target="_blank" class="button button-small">Edit Gallery</a>
				<a href="#" class="button button-small wpx-media-gallery-delete">Delete Selected</a>
				</p>
				<?php
			} else {
				?>
				<p>No gallery is attached yet. Choose a gallery from the dropdown, or create one.</p>
				<p><a href="<?php bloginfo('url'); ?>/wp-admin/post-new.php?post_type=<?php echo $gallery_post_type; ?>" target="_blank" class="button">Create Gallery</a></p>
				<?php
			}	
		?>

		<?php echo $markup['before_description']; ?><?php echo htmlspecialchars($this->description); ?><?php echo $markup['after_description']; ?>
		<?php echo $markup['after_input']; ?>
		<?php echo $markup['after_container']; ?>
		<?php
	}

	/**
	 * WPX Select User Roles
	 *
	 * Displays a list of user roles. (Only used by WPX.)
	 *
	 * @since 1.0
	*/
	public function wpx_select_user_roles() {

		global $wp_roles;
		$markup = $this->markup('wpx_select_user_roles');
		$meta_array = explode(',',$this->meta);
		$roles = $wp_roles->get_names();
		?>

		<?php echo $markup['before_container']; ?>
		<?php echo $markup['before_label']; ?>
		<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
		<?php echo $markup['after_label']; ?>
		<?php echo $markup['before_input']; ?>

		<div class="wpx-dropdown-selector">
			<select>
				<option value="">-- Select --</option>
				<?php 
				foreach($roles as $i=>$role) { 
					if (in_array($i, $meta_array)) {
						continue;
					} else { ?>
						<option value="<?php echo $i; ?>!wpx!<?php echo $role; ?>!wpx!<?php echo htmlspecialchars($this->meta_key); ?>"><?php echo $role; ?></option>
					<?php } ?>
				<?php } ?>
			</select>
		</div>
		<div class="wpx-checkbox-multi">
			<ul>
				<?php
				foreach($roles as $i=>$role) { 
					if (in_array($i, $meta_array)) { ?>
						<li><input type="checkbox" <?php if (is_array($meta_array)) { if (in_array($i, $meta_array)) { echo 'checked="checked"'; } } ?> name="<?php echo $this->meta_key; ?>[]" value="<?php echo $i; ?>"><?php echo $role; ?></li>
					<?php } ?>
				<?php } ?>
			</ul>
		</div>
			
		<?php echo $markup['before_description']; ?><?php echo $this->description; ?><?php echo $markup['after_description']; ?>
		<?php echo $markup['after_input']; ?>
		<?php echo $markup['after_container']; ?>
		<?php
	}

	/**
	 * WPX Select Types
	 *
	 * Defines the built-in field types. (Only used by WPX.)
	 *
	 * @since 1.0
	*/
	public function wpx_select_types() {

		$markup = $this->markup('wpx_select_types');
		?>

		<?php echo $markup['before_container']; ?>
		<?php echo $markup['before_label']; ?>
		<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
		<?php echo $markup['after_label']; ?>
		<?php echo $markup['before_input']; ?>

		<select class="input select" name="<?php echo $this->meta_key; ?>">
			<?php 
			// output them
			foreach($this->fieldTypeList() as $field_type) {
			?>
				<option <?php if ($this->meta == $field_type[0]) { echo 'selected="selected"'; } ?> value="<?php echo $field_type[0]; ?>"><?php echo $field_type[1]; ?></option>
			<?php } ?>
		</select>
			
		<?php echo $markup['before_description']; ?><?php echo $this->description; ?><?php echo $markup['after_description']; ?>
		<?php echo $markup['after_input']; ?>
		<?php echo $markup['after_container']; ?>
		<?php
	}

	/**
	 * List of Field Types
	 *
	 * Constructs the possible field types, adding custom ones created outside
	 * the plugin. (Used only by WPX.)
	 *
	 * @since 1.0
	*/
	public function fieldTypeList() {

		$field_types[] = array('checkbox','Checkbox');
		$field_types[] = array('file','File');
		$field_types[] = array('gallery','Gallery');
		$field_types[] = array('image','Image');
		$field_types[] = array('post','Post');
		$field_types[] = array('term','Term');
		$field_types[] = array('text','Text');
		$field_types[] = array('textarea','Textarea');
		$field_types[] = array('tinymce','Visual Editor');
		$field_types[] = array('user','User');
		$field_types =  apply_filters( 'wpx_add_field_type', $field_types );
		return $field_types;
	}

	/**
	 * WPX Select Supports
	 *
	 * A list of core options for default metaboxes in a post type. (Used only by WPX.)
	 *
	 * @since 1.0
	*/
	public function wpx_select_supports() {

		$markup = $this->markup('wpx_select_supports');
		$meta_array = explode(',',$this->meta);
		?>

		<?php echo $markup['before_container']; ?>
		<?php echo $markup['before_label']; ?>
		<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
		<?php echo $markup['after_label']; ?>
		<?php echo $markup['before_input']; ?>

		<div class="wpx-checkbox-multi">
			<ul>
				<li><input <?php if (is_array($meta_array)) { if (in_array('title', $meta_array)) { echo 'checked="checked"'; } } if (!$this->meta) { echo 'checked="checked"'; } ?> type="checkbox" name="<?php echo $this->meta_key; ?>[]" value="title">Title</li>
				<li><input <?php if (is_array($meta_array)) { if (in_array('editor', $meta_array)) { echo 'checked="checked"'; } } if (!$this->meta) { echo 'checked="checked"'; } ?> type="checkbox" name="<?php echo $this->meta_key; ?>[]" value="editor">Editor</li>
				<li><input <?php if (is_array($meta_array)) { if (in_array('author', $meta_array)) { echo 'checked="checked"'; } } ?> type="checkbox" name="<?php echo $this->meta_key; ?>[]" value="author">Author</li>
				<li><input <?php if (is_array($meta_array)) { if (in_array('thumbnail', $meta_array)) { echo 'checked="checked"'; } } ?> type="checkbox" name="<?php echo $this->meta_key; ?>[]" value="thumbnail">Thumbnail</li>
				<li><input <?php if (is_array($meta_array)) { if (in_array('excerpt', $meta_array)) { echo 'checked="checked"'; } } ?> type="checkbox" name="<?php echo $this->meta_key; ?>[]" value="excerpt">Excerpt</li>
				<li><input <?php if (is_array($meta_array)) { if (in_array('trackbacks', $meta_array)) { echo 'checked="checked"'; } } ?> type="checkbox" name="<?php echo $this->meta_key; ?>[]" value="trackbacks">Trackbacks</li>
				<li><input <?php if (is_array($meta_array)) { if (in_array('custom-fields', $meta_array)) { echo 'checked="checked"'; } } ?> type="checkbox" name="<?php echo $this->meta_key; ?>[]" value="custom-fields">Custom Fields</li>
				<li><input <?php if (is_array($meta_array)) { if (in_array('comments', $meta_array)) { echo 'checked="checked"'; } } ?> type="checkbox" name="<?php echo $this->meta_key; ?>[]" value="comments">Comments</li>
				<li><input <?php if (is_array($meta_array)) { if (in_array('revisions', $meta_array)) { echo 'checked="checked"'; } } ?> type="checkbox" name="<?php echo $this->meta_key; ?>[]" value="revisions">Revisions</li>
				<li><input <?php if (is_array($meta_array)) { if (in_array('page-attributes', $meta_array)) { echo 'checked="checked"'; } } ?> type="checkbox" name="<?php echo $this->meta_key; ?>[]" value="page-attributes">Page Attributes</li>
				<li><input <?php if (is_array($meta_array)) { if (in_array('post-formats', $meta_array)) { echo 'checked="checked"'; } } ?> type="checkbox" name="<?php echo $this->meta_key; ?>[]" value="post-formats">Post Formats</li>
			</ul>
		</div>

		<?php echo $markup['before_description']; ?><?php echo $this->description; ?><?php echo $markup['after_description']; ?>
		<?php echo $markup['after_input']; ?>
		<?php echo $markup['after_container']; ?>
		<?php
	}


	/**
	 * WPX Select Taxonomies
	 *
	 * A list of taxonomies registered in the system to assign to a cpt. (Used only by WPX.)
	 *
	 * @since 1.0
	*/
	public function wpx_select_taxonomies() {

		$markup = $this->markup('wpx_select_taxonomies');
		$meta_array = explode(',',$this->meta);
		$builtin_taxonomies = get_taxonomies( array('_builtin'=>true), 'objects');
		$custom_taxonomies = get_taxonomies( array('_builtin'=>false), 'objects');
		asort($builtin_taxonomies);
		asort($custom_taxonomies);
		?>

		<?php echo $markup['before_container']; ?>
		<?php echo $markup['before_label']; ?>
		<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
		<?php echo $markup['after_label']; ?>
		<?php echo $markup['before_input']; ?>
		
		<div class="wpx-checkbox-multi">
			<ul>
				<li><strong>Built-in</strong><br><hr></li>
				<?php foreach($builtin_taxonomies as $i=>$taxonomy) { ?>
					<li><input type="checkbox" <?php if (is_array($meta_array)) { if (in_array($taxonomy->name, $meta_array)) { echo 'checked="checked"'; } } ?> name="<?php echo $this->meta_key; ?>[]" value="<?php echo $taxonomy->name; ?>"><?php echo $taxonomy->labels->name; ?> (<?php echo $taxonomy->name; ?>)</li>
				<?php } ?>
				<li><strong>Custom</strong><br><hr></li>
				<?php foreach($custom_taxonomies as $i=>$taxonomy) { ?>
					<?php 
						if ($taxonomy->labels->name == 'Group') {
							continue;
						}
					?>
					<li><input type="checkbox" <?php if (is_array($meta_array)) { if (in_array($taxonomy->name, $meta_array)) { echo 'checked="checked"'; } } ?> name="<?php echo $this->meta_key; ?>[]" value="<?php echo $taxonomy->name; ?>"><?php echo $taxonomy->labels->name; ?> (<?php echo $taxonomy->name; ?>)</li>
				<?php } ?>
			</ul>
		</div>
		
		<?php echo $markup['before_description']; ?><?php echo $this->description; ?><?php echo $markup['after_description']; ?>
		<?php echo $markup['after_input']; ?>
		<?php echo $markup['after_container']; ?>
		<?php
	}

	/**
	 * WPX Select Fields
	 *
	 * A list of all available fields, to attach to custom post types. (Used only by WPX.)
	 *
	 * @since 1.0
	*/
	public function wpx_select_fields() {

		$markup = $this->markup('wpx_select_fields');
		$meta_array = explode(',',$this->meta);
		$fields = get_posts(array('posts_per_page'=>-1, 'post_type'=>'wpx_fields', 'orderby'=>'title', 'order'=>'ASC'));
		?>
		
		<?php echo $markup['before_container']; ?>
		<?php echo $markup['before_label']; ?>
		<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
		<?php echo $markup['after_label']; ?>
		<?php echo $markup['before_input']; ?>
					
		<div class="wpx-dropdown-selector">
			<select>
				<option value="">-- Select --</option>
				<?php 
					foreach($fields as $i=>$field) { 
						 if (in_array($field->ID, $meta_array)) {
							continue;
						 } else {
				?>
				<option value="<?php echo $field->ID; ?>!wpx!<?php echo get_the_title($field->ID); ?>!wpx!<?php echo htmlspecialchars($this->meta_key); ?>"><?php echo get_the_title($field->ID); ?> (<?php echo $field->post_name; ?>)</option>
				<?php
					}
				} ?>
			</select>
		</div>
		<div class="wpx-checkbox-multi">
			<ul>
				<?php
				foreach($fields as $i=>$field) { 
					if (in_array($field->ID, $meta_array)) { ?>
						<li><input type="checkbox" <?php if (is_array($meta_array)) { if (in_array($field->ID, $meta_array)) { echo 'checked="checked"'; } } ?> name="<?php echo $this->meta_key; ?>[]" value="<?php echo $field->ID; ?>"><?php echo get_the_title($field->ID); ?> (<?php echo $field->post_name; ?>)</li>
					<?php } ?>
				<?php } ?>
			</ul>
		</div>
		
		<?php echo $markup['before_description']; ?><?php echo $this->description; ?><?php echo $markup['after_description']; ?>
		<?php echo $markup['after_input']; ?>
		<?php echo $markup['after_container']; ?>
		<?php
	}

	/**
	 * WPX Select Object Type
	 *
	 * A list of all available post types, to attach to taxonomies. (Used only by WPX.)
	 *
	 * @since 1.0
	*/
	public function wpx_select_object_type() {

		$markup = $this->markup('wpx_select_object_type');
		$meta_array = explode(',',$this->meta);
		$builtin_cpts = get_post_types( array('_builtin'=>true), 'objects');
		$custom_cpts = get_post_types( array('_builtin'=>false), 'objects');
		usort($builtin_cpts, array($this, 'wpx_sort_select_object_type'));
		usort($custom_cpts, array($this, 'wpx_sort_select_object_type'));
		?>
		
		<?php echo $markup['before_container']; ?>
		<?php echo $markup['before_label']; ?>
		<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
		<?php echo $markup['after_label']; ?>
		<?php echo $markup['before_input']; ?>

		<div class="wpx-dropdown-selector">
			<select>
				<option value="">-- Select --</option>
				<optgroup label="Built In">
				<?php foreach($builtin_cpts as $i=>$post_type) { ?>
					<option value="<?php echo $post_type->name; ?>!wpx!<?php echo $post_type->labels->singular_name; ?>!wpx!<?php echo htmlspecialchars($this->meta_key); ?>"><?php echo $post_type->labels->singular_name; ?></option>
				<?php } ?>
				</optgroup>
				<optgroup label="Custom">
				<?php foreach($custom_cpts as $i=>$post_type) {
					if (in_array($post_type->name, $meta_array) || $post_type->name == 'wpx_fields' || $post_type->name == 'wpx_types' || $post_type->name == 'wpx_taxonomy' || $post_type->name == 'wpx_options') {
						continue;
					} else { ?>
						<option value="<?php echo $post_type->name; ?>!wpx!<?php echo $post_type->labels->singular_name; ?>!wpx!<?php echo htmlspecialchars($this->meta_key); ?>"><?php echo $post_type->labels->singular_name; ?></option>
					<?php } ?>
				<?php } ?>
				</optgroup>
			</select>
		</div>

		<div class="wpx-checkbox-multi">
			<ul>
				<?php 
				$post_types = array_merge($builtin_cpts, $custom_cpts);
				usort($post_types, array($this, 'wpx_sort_select_object_type'));
				foreach($post_types as $i=>$post_type) { 
					if (in_array($post_type->name, $meta_array)) { 
				?>
					<li><input type="checkbox" <?php if (is_array($meta_array)) { if (in_array($post_type->name, $meta_array)) { echo 'checked="checked"'; } } ?> name="<?php echo $this->meta_key; ?>[]" value="<?php echo $post_type->name; ?>"><?php echo $post_type->labels->singular_name; ?></li>
				<?php } ?>
				<?php } ?>
			</ul>
		</div>

		<?php echo $markup['before_description']; ?><?php echo $this->description; ?><?php echo $markup['after_description']; ?>
		<?php echo $markup['after_input']; ?>
		<?php echo $markup['after_container']; ?>
		<?php
	}

	/**
	 * WPX Sort Select Object Type
	 *
	 * Sorts object array by a key inside the object.
	 *
	 * @since 1.0
	*/
	private function wpx_sort_select_object_type($a, $b) {

 		return strcmp($a->labels->name, $b->labels->name);

 	}

	/**
	 * WPX States
	 *
	 * Handles passing true, false, or empty values. (Only used by WPX.)
	 *
	 * @since 1.0
	*/
	public function wpx_states() {

	    $markup = $this->markup('wpx_states');

	    ?>

	    <?php echo $markup['before_container']; ?>
            <?php echo $markup['before_label']; ?>

            <label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label>

            <?php echo $markup['after_label']; ?>
            <?php echo $markup['before_input']; ?>

                    <select class="input select" name="<?php echo $this->meta_key; ?>">
                    	<option <?php if ($this->meta == '') { echo 'selected="selected"'; } ?> value="">default</option>
                        <option <?php if ($this->meta == "true") { echo 'selected="selected"'; } ?> value="true">true</option>
                        <option <?php if ($this->meta == "false") { echo 'selected="selected"'; } ?> value="false">false</option>
                    </select>

			<?php echo $markup['before_description']; ?><?php echo $this->description; ?> (Please note that "default" is not null, but the default value for the registration of this object, as specified by WordPress. For example, the default value for the "show_ui" parameter in register_post_type() is "false.")<?php echo $markup['after_description']; ?>
            <?php echo $markup['after_input']; ?>

	    <?php echo $markup['after_container']; ?>
	    <?php
	}

	/**
	 * Textfield (for WPX)
	 *
	 * Handles displaying true or false if that value was passed as a default. (Only used by WPX.)
	 *
	 * @since 1.0
	*/
	public function wpx_text() {
		$value = $this->value;
		$markup = $this->markup('text');
		?>
		<?php echo $markup['before_container']; ?>

			<?php echo $markup['before_label']; ?>
				<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
			<?php echo $markup['after_label']; ?>
			
			<?php echo $markup['before_input']; ?>

				<?php // if no value has been set by the user ?>
				<?php if ($this->meta == 'null' || $this->meta == '') { ?>

					<input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>" value="<?php echo $value; ?>" />

				<?php // a value is given ?>
            	<?php } else { ?>

					<input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>" value="<?php echo htmlspecialchars($this->meta); ?>" />

				<?php } ?>

				<?php echo $markup['before_description']; ?><?php echo htmlspecialchars($this->description); ?><?php echo $markup['after_description']; ?>
			<?php echo $markup['after_input']; ?>

		<?php echo $markup['after_container']; ?>
		<?php
	}

}
?>