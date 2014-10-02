<?php
/**
* Core: Custom Metabox Fields
*
* This class generates the formatting for individual custom fields based on context.
* These fields are used for options pages, custom post type metaboxes, and taxonomy metaboxes.
*
* @package    WordPress
* @subpackage wpx 
* @since 1.0
*/

if(!class_exists('wpx_field_factory')) {

	class wpx_field_factory {

		function __construct( $meta_key, $args = '') {

			global $post;

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

			// if this is a term, reset the meta & ID
			$tag = get_term_by( 'ID', $_REQUEST['tag_ID'], $_REQUEST['taxonomy']);

			// some default vars
			$this->meta_key_raw = $meta_key; // used in taxonomy field types
			$this->meta_key = $meta_key;
			$this->label = $settings['label'];
			$this->description = $settings['description'];
			$this->type = $settings['field'];
			$this->meta = get_post_meta($post->ID, $meta_key, true);
			$this->array_key = $settings['array_key'];
			$this->required = $settings['required'];
			$this->settings = $settings;

			// if we're dealing with a taxonomy
			if ($tag) {
				$t_id = $tag->term_id;
				$this->term_id = $t_id;
				$term_meta = get_option( "taxonomy_term_$t_id" );
				$term_field_value = $term_meta[$meta_key];
				$this->meta = $term_field_value;
				// reset the ID
				$this->meta_key = 'term_meta['.$meta_key.']';
			}

			// if this is a settings page
			$screen = get_current_screen();
			if (stristr($screen->base, 'wpx_options_'.$this->array_key) == true) {
				// we need to retrieve the value as an array from the global options
				$options_group = get_option($this->array_key);
				$this->meta = $options_group[$this->meta_key];
				// options settings are saved as an array
				$this->meta_key = $this->array_key.'['.$this->meta_key.']';
			}

			// lets us know what "state" this is
			if ($tag) {
				$this->state = 'taxonomy';
			} elseif (stristr($screen->base, 'wpx_options_'.$this->array_key) == true) {
				$this->state = 'options-page';
			} else {
				$this->state = 'post-type';
			}

			// render the correct input type
			if ( method_exists($this,$settings['field']) ) {
				call_user_func(array($this,$settings['field']));
			// try to call the method from outside the class
			} else if (function_exists($settings['field'])) {
				// this allows us to make custom field types
				// let's pass everything you'll need to make some:
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
				call_user_func($settings['field'], $args);
			} else {
				// default to text if no method is defined
				call_user_func(array($this,'text'));
			}
			
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * Markup
		 *
		 * Renders wrapper markup depending on context.
		 *
		 * @since 1.0
		 *
		 */
		public function markup($defined_type) {
			if ($this->required == true) { $required = ' form-required '; }
			$screen = get_current_screen();
			$action = $_GET['action'];
			if ($screen->base == 'edit-tags' && $action == 'edit') {
				// for the edit screen of taxonomies (they use tables)
				$markup = array(
					'before_container' => '<tr class="meta'.$this->meta_key.' wpx-fields '.$defined_type.''.$required.' form-field edit-tags">',
					'before_label' => '<th scope="row" valign="top">',
					'after_label' => '</th>',
					'before_input' => '<td>',
					'after_input' => '',
					'after_container' => '</tr>',
					'before_description' => '<p>',
					'after_description' => '</p></td>'
				);
			} else if ($screen->base == 'edit-tags') {
				// for the post type metabox screen
				$markup = array(
					'before_container' => '<div class="meta'.$this->meta_key.' wpx-fields '.$defined_type.''.$required.' add-tags form-field '.$defined_type.'">',
					'before_label' => '',
					'after_label' => '',
					'before_input' => '',
					'after_input' => '',
					'after_container' => '</div>',
					'before_description' => '<p>',
					'after_description' => '</p>'
				);
			} else {
				// for the  post type metabox screen or options pages
				$markup = array(
					'before_container' => '<div class="meta'.$this->meta_key.' wpx-fields '.$defined_type.''.$required.' form-field '.$defined_type.' textfield"><div class="wpx-input-bubble">',
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

		/*
		|--------------------------------------------------------------------------
		/**
		 * Textfield
		 *
		 * Defines textfield. These fields are not intended to contain HTML markup. 
		 *
		 * @since 1.0
		 *
		 */
		public function text() {
			$markup = $this->markup('text');
			?>
			<?php echo $markup['before_container']; ?>
					<?php echo $markup['before_label']; ?>
						<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
					<?php echo $markup['after_label']; ?>
					<?php echo $markup['before_input']; ?>
						<input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>" value="<?php echo htmlspecialchars($this->meta); ?>" />
						<?php echo $markup['before_description']; ?><?php echo htmlspecialchars($this->description); ?><?php echo $markup['after_description']; ?>
					<?php echo $markup['after_input']; ?>
			<?php echo $markup['after_container']; ?>
			<?php
		}
		
		/*
		|--------------------------------------------------------------------------
		/**
		 * Checkbox
		 *
		 * Defines checkboxes. We use the hidden field trick to force the checkbox to save its state.
		 * For the life of me, I can't get the WP checked() function to work without this technique.
		 * 
		 * @since 1.0
		 *
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
		
		/*
		|--------------------------------------------------------------------------
		/**
		 * Textarea
		 *
		 * Defines raw textareas. The value of these fields are not intended to contain HTML markup. 
		 *
		 * @since 1.0
		 *
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

		/*
		|--------------------------------------------------------------------------
		/**
		 * File Upload
		 *
		 * This field is a textfield with the class "custom-media" assigned to it.
		 * Any textfield with this class will automatically render a button to open the
		 * Media Library so that the path to the uploaded file can be saved into the field. 
		 *
		 * @since 1.0
		 *
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
							<a href="#" data-id="<?php echo htmlspecialchars(wpx::get_attachment_id_by_url($this->meta)); ?>" class="wpx-delete-image-field wpx-delete-image-<?php echo $this->meta_key; ?> button button-small">Delete</a>
							<a href="#" class="wpx-clear-image-field wpx-delete-image-<?php echo $this->meta_key; ?> button button-small">Clear</a>
						</p>
						<?php } ?>
						<?php echo $markup['before_description']; ?><?php echo htmlspecialchars($this->description); ?><?php echo $markup['after_description']; ?>
					<?php echo $markup['after_input']; ?>
			<?php echo $markup['after_container']; ?>
			<?php
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * Image Field
		 *
		 * This field is the same as the file field, except that it shows a preview of the image
		 * after you upload it. Also provides the option to "clear" the field and the image
		 * though the image will remain in the Media Library. This is intentional.
		 *
		 * @since 1.0
		 *
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
					<?php if ($this->meta) { ?>
						<div class="wpx-has-image">
					<?php } ?>
						<input data-source="<?php echo $post->ID; ?>" class="wpx-media input file" type="text" name="<?php echo $this->meta_key; ?>" value="<?php echo htmlspecialchars($this->meta); ?>" />
						<?php if ($this->meta) { ?>
						<p class="wpx-field-controls">
							<a href="#" data-id="<?php echo htmlspecialchars(wpx::get_attachment_id_by_url($this->meta)); ?>" class="wpx-delete-image-field wpx-delete-image-<?php echo $this->meta_key; ?> button button-small">Delete</a>
							<a href="#" class="wpx-clear-image-field wpx-delete-image-<?php echo $this->meta_key; ?> button button-small">Clear</a>
						</p>
						<?php } ?>
						<?php echo $markup['before_description']; ?><?php echo htmlspecialchars($this->description); ?><?php echo $markup['after_description']; ?>
					<?php if ($this->meta) { ?>
					</div>
					<?php } ?>
					<?php echo $markup['after_input']; ?>
			<?php echo $markup['after_container']; ?>
			<?php
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * TinyMCE
		 *
		 * Uses wp_editor() to render a tinyMCE editor on the field.
		 * Still susceptible to the "moving metaboxes" bug. 
		 *
		 * @since 1.0
		 *
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
							// strip non-alpha characters b/c tinymce doesn't accept nonalpha
							$tinymce_id = strtolower(preg_replace( '/[^a-z\s]/i', '', $this->meta_key ));
						?>
						<div class="input ve"><?php wp_editor( $this->meta, $tinymce_id, array('textarea_name'=>$this->meta_key) ); ?></div>
						<?php echo $markup['before_description']; ?><?php echo htmlspecialchars($this->description); ?><?php echo $markup['after_description']; ?>
					<?php echo $markup['after_input']; ?>
			<?php echo $markup['after_container']; ?>
			<?php
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * Users(s)
		 *
		 * This field lets the user pick a user or multiple users configured in the field.
		 *
		 * @since 1.0
		 *
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
			if($this->state == 'taxonomy' || $this->state == 'options-page') {
				// if this is a taxonomy or options page
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
							print_r($option);
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
			<?php } else { ?>
			<select class="input select" name="<?php echo $this->meta_key; ?>">
				<option value="">-- Select --</option>
				<?php foreach($relationship_array as $relationship) { ?>
					<optgroup label="<?php echo $relationship_object->labels->name; ?>">
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

		/*
		|--------------------------------------------------------------------------
		/**
		 * Post(s)
		 *
		 * This field lets the user pick a post or multiple posts from post types configured in the field.
		 *
		 * @since 1.0
		 *
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
			if($this->state == 'taxonomy' || $this->state == 'options-page') {
				// if this is a taxonomy or options page
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
			// if multiple, let's allow them to choose multiple posts
			if ($multiple) {
			?>
			<div class="wpx-dropdown-selector">
				<select class="input select">
					<option value="">-- Select --</option>
					<?php foreach($relationship_array as $relationship) { ?>
					<?php $relationship_object = get_post_type_object( $relationship ); 	?>
					<optgroup label="<?php echo $relationship_object->labels->name; ?>">
					<?php 
						$posts_in_relationship = get_posts(array('posts_per_page'=>-1, 'post_type'=>$relationship));
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
					$saved_posts = get_posts(array('post__in'=>$meta_array, 'post_type'=>$relationship_array));
					foreach($saved_posts as $i=>$option) { 
						if (in_array($option->ID, $meta_array)) { 
					?>
						<li><input type="checkbox" <?php if (is_array($meta_array)) { if (in_array($option->ID, $meta_array)) { echo 'checked="checked"'; } } ?> name="<?php echo $this->meta_key; ?>[]" value="<?php echo $option->ID; ?>"><?php echo get_the_title($option->ID); ?></li>
					<?php } ?>
					<?php } ?>
				</ul>
			</div>
			<?php } else { ?>
			<select class="input select" name="<?php echo $this->meta_key; ?>">
				<option value="">-- Select --</option>
				<?php foreach($relationship_array as $relationship) { ?>
					<?php $relationship_object = get_post_type_object( $relationship ); 	?>
					<optgroup label="<?php echo $relationship_object->labels->name; ?>">
					<?php $posts_in_relationship = get_posts(array('posts_per_page'=>-1, 'orderby'=>'name', 'order'=>'ASC', 'post_type'=>$relationship)); ?>
					<?php foreach($posts_in_relationship as $i=>$option) { ?>
						<option name="<?php echo $this->meta_key; ?>" <?php if (is_array($meta_array)) { if (in_array($option->ID, $meta_array)) { echo 'selected="selected"'; } } ?> value="<?php echo $option->ID; ?>"><?php if (get_the_title($option->ID)) { echo get_the_title($option->ID).' ('.str_replace(home_url(),'',get_permalink($option->ID)).')'; } else { echo $option->ID; } ?></option>
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

		/*
		|--------------------------------------------------------------------------
		/**
		 * Term(s)
		 *
		 * This field lets the user pick a term or multiple terms from taxonomies configured in the field.
		 *
		 * @since 1.0
		 *
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
			if($this->state == 'taxonomy' || $this->state == 'options-page') {
				$field_key = get_page_by_path($this->meta_key_raw, OBJECT, 'wpx_fields');
			} else {
				$field_key = wpx::extract_field_key($this->meta_key, $post->post_type);
			}
			// get the relevant field
			$multiple = get_post_meta($field_key->ID, '_wpx_fields_term_multiple', true);
			// relationship type
			$relationship = get_post_meta($field_key->ID, '_wpx_fields_term_objects', true);
			$relationship_array = explode(',',$relationship);
			// if multiple, let's allow them to choose multiple posts
			if ($multiple) {
			?>
			<div class="wpx-dropdown-selector">
				<select class="input select">
					<option value="">-- Select --</option>
					<?php foreach($relationship_array as $relationship) { ?>
					<?php $relationship_object = get_taxonomy( $relationship ); 	?>
					<optgroup label="<?php echo $relationship_object->labels->name; ?>">
					<?php 
						$terms_in_object = get_terms($relationship);
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
					$saved_terms =  get_terms( $from_taxonomies, array('include'=>$meta_array) );
					foreach($saved_terms as $i=>$option) { 
					?>
						<li><input type="checkbox" <?php if (is_array($meta_array)) { if (in_array($option->term_id, $meta_array)) { echo 'checked="checked"'; } } ?> name="<?php echo $this->meta_key; ?>[]" value="<?php echo $option->term_id; ?>"><?php echo $option->name; ?></li>
					<?php } ?>
				</ul>
			</div>
			<?php } else { ?>
			<select class="input select" name="<?php echo $this->meta_key; ?>">
				<option value="">-- Select --</option>
				<?php foreach($relationship_array as $relationship) { ?>
					<?php $relationship_object = get_taxonomy( $relationship ); 	?>
					<optgroup label="<?php echo $relationship_object->labels->name; ?>">
					<?php $terms_in_object = get_terms($relationship); ?>
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

		/*
		|--------------------------------------------------------------------------
		/**
		 * Gallery
		 *
		 * Displays the image attachments of the post selected (the post type for this is defined in the field)
		 * and allows users to delete images attached to that post.
		 *
		 * @since 1.0
		 *
		 */
		public function gallery() {
			global $post;
			// saved values
			$meta_array = explode(',',$this->meta);
			// standard markup
			$markup = $this->markup('gallery');
			// let's get the post type assigned to this.
			if($this->state == 'taxonomy' || $this->state == 'options-page') {
				$field_key = get_page_by_path($this->meta_key_raw, OBJECT, 'wpx_fields');
			} else {
				$field_key = wpx::extract_field_key($this->meta_key, $post->post_type);
			}
			// get the relevant field
			$gallery_post_type = get_post_meta($field_key->ID, '_wpx_fields_gallery_cpt', true);
			$galleries = get_posts(array('post_type'=>$gallery_post_type, 'posts_per_page'=>-1));
			?>
			<?php echo $markup['before_container']; ?>
					<?php echo $markup['before_label']; ?>
						<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
					<?php echo $markup['after_label']; ?>
					<?php echo $markup['before_input']; ?>

						<?php
							// if this is a gallery type of post, show its attachments
							// rather than provide the option to associate a post

							if ( (get_post_type($post->ID) == $gallery_post_type) && !($this->state == 'taxonomy') && !($this->state == 'options-page') ) {
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
							if ( $gallery_id || (get_post_type($post->ID) == $gallery_post_type && !($this->state == 'taxonomy') && !($this->state == 'options-page')) ) {
								?><div class="wpx-gallery-container"><?php
								if (get_post_type($post->ID) == $gallery_post_type) $gallery_id = $post->ID;
								$images = wpx::get_attachments($gallery_id, 'ASC', 'menu_order ID', true, 'image', 'inherit');
								$count = 1;
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

		/*
		|--------------------------------------------------------------------------
		 * Internal Field Types
		|--------------------------------------------------------------------------
		*/
		// These fields are for use with WPx only and are not selectable
		// as field types in the Dashboard.

 		/*
		|--------------------------------------------------------------------------
		/**
		 * [WPx] Select - User Roles
		 *
		 * Displays a list of user roles.
		 *
		 * @since 1.0
		 *
		 */
		public function wpx_select_user_roles() {
			$markup = $this->markup('wpx_select_user_roles');
			$meta_array = explode(',',$this->meta);
			global $wp_roles;
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
										} else {
								?>
								<option value="<?php echo $i; ?>!wpx!<?php echo $role; ?>!wpx!<?php echo htmlspecialchars($this->meta_key); ?>"><?php echo $role; ?></option>
								<?php
									}
								} ?>
							</select>
						</div>
						<div class="wpx-checkbox-multi">
							<ul>
								<?php
								foreach($roles as $i=>$role) { 
									if (in_array($i, $meta_array)) { 
								?>
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

 		/*
		|--------------------------------------------------------------------------
		/**
		 * [WPx] Select - Fields
		 *
		 * Defines the built-in field types.
		 *
		 * @since 1.0
		 *
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

		/*
		|--------------------------------------------------------------------------
		/**
		 * [WPx] Select - Supports
		 *
		 * A list of core options for default metaboxes in a post type.
		 *
		 * @since 1.0
		 *
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
								<li><input <?php if (is_array($meta_array)) { if (in_array('title', $meta_array)) { echo 'checked="checked"'; } } ?> type="checkbox" name="<?php echo $this->meta_key; ?>[]" value="title">Title</li>
								<li><input <?php if (is_array($meta_array)) { if (in_array('editor', $meta_array)) { echo 'checked="checked"'; } } ?> type="checkbox" name="<?php echo $this->meta_key; ?>[]" value="editor">Editor</li>
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

		/*
		|--------------------------------------------------------------------------
		/**
		 * [WPx] Select - Taxonomies
		 *
		 * A list of taxonomies registered in the system to assign to a cpt.
		 *
		 * @since 1.0
		 *
		 */
		public function wpx_select_taxonomies() {
			$markup = $this->markup('wpx_select_taxonomies');
			$meta_array = explode(',',$this->meta);
			$taxonomies = get_taxonomies( null, 'objects');
			?>
			<?php echo $markup['before_container']; ?>
					<?php echo $markup['before_label']; ?>
						<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
					<?php echo $markup['after_label']; ?>
					<?php echo $markup['before_input']; ?>
						<div class="wpx-checkbox-multi">
							<ul>
								<?php foreach($taxonomies as $i=>$taxonomy) { ?>
									<li><input type="checkbox" <?php if (is_array($meta_array)) { if (in_array($i, $meta_array)) { echo 'checked="checked"'; } } ?> name="<?php echo $this->meta_key; ?>[]" value="<?php echo $i; ?>"><?php echo $taxonomy->labels->name; ?></li>
								<?php } ?>
							</ul>
						</div>
						<?php echo $markup['before_description']; ?><?php echo $this->description; ?><?php echo $markup['after_description']; ?>
					<?php echo $markup['after_input']; ?>
			<?php echo $markup['after_container']; ?>
			<?php
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * [WPx] Select - Metaboxes
		 *
		 * A list of all available fields, to attach to custom post types.
		 *
		 * @since 1.0
		 *
		 */
		public function wpx_select_fields() {
			$markup = $this->markup('wpx_select_fields');
			$meta_array = explode(',',$this->meta);
			$fields = get_posts(array('posts_per_page'=>-1, 'post_type'=>'wpx_fields', 'orderby'=>'title', 'order'=>'asc'));
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
								<option value="<?php echo $field->ID; ?>!wpx!<?php echo get_the_title($field->ID); ?>!wpx!<?php echo htmlspecialchars($this->meta_key); ?>"><?php echo get_the_title($field->ID); ?></option>
								<?php
									}
								} ?>
							</select>
						</div>
						<div class="wpx-checkbox-multi">
							<ul>
								<?php
								foreach($fields as $i=>$field) { 
									if (in_array($field->ID, $meta_array)) { 
								?>
									<li><input type="checkbox" <?php if (is_array($meta_array)) { if (in_array($field->ID, $meta_array)) { echo 'checked="checked"'; } } ?> name="<?php echo $this->meta_key; ?>[]" value="<?php echo $field->ID; ?>"><?php echo get_the_title($field->ID); ?></li>
								<?php } ?>
								<?php } ?>
							</ul>
						</div>
						<?php echo $markup['before_description']; ?><?php echo $this->description; ?><?php echo $markup['after_description']; ?>
					<?php echo $markup['after_input']; ?>
			<?php echo $markup['after_container']; ?>
			<?php
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * [WPx] Select - Object Type
		 *
		 * A list of all available post types, to attach to taxonomies.
		 *
		 * @since 1.0
		 *
		 */
		public function wpx_select_object_type() {
			$markup = $this->markup('wpx_select_object_type');
			$meta_array = explode(',',$this->meta);
			$post_types = get_post_types( null, 'objects');
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
									foreach($post_types as $i=>$post_type) {
										 if (in_array($post_type->name, $meta_array) || $post_type->name == 'wpx_fields' || $post_type->name == 'wpx_types' || $post_type->name == 'wpx_taxonomy') {
											continue;
										 } else {
								?>
								<option value="<?php echo $post_type->name; ?>!wpx!<?php echo $post_type->labels->singular_name; ?>!wpx!<?php echo htmlspecialchars($this->meta_key); ?>"><?php echo $post_type->labels->singular_name; ?></option>
								<?php
									}
								} ?>
							</select>
						</div>
						<div class="wpx-checkbox-multi">
							<ul>
								<?php
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

		/*
		|--------------------------------------------------------------------------
		/**
		 * [WPx] Capabilities
		 *
		 * Allows entering of a string for each of the 14 possible capabilities.
		 *
		 * @since 1.0
		 *
		 */
		public function wpx_capabilities() {
			$markup = $this->markup('wpx_capabilities');
			$meta_array = explode(',',$this->meta);
			?>
			<?php echo $markup['before_container']; ?>
					<?php echo $markup['before_label']; ?>
						<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
					<?php echo $markup['after_label']; ?>
					<?php echo $markup['before_input']; ?>
						<?php echo $markup['before_description']; ?><?php echo $this->description; ?><?php echo $markup['after_description']; ?>
						<div class="wpx-form-row"><span class="label">[edit_post]</span><input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[0]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[read_post]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[1]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[delete_post]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[2]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[edit_posts]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[3]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[edit_others_posts]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[4]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[publish_posts]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[5]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[read_private_posts]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[6]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[read]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[read]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[delete_posts]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[8]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[delete_private_posts]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[9]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[delete_published_posts]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[10]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[delete_others_posts]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[11]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[edit_private_posts]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[12]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[edit_published_posts]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[13]; ?>" /></div>
					<?php echo $markup['after_input']; ?>
			<?php echo $markup['after_container']; ?>
			<?php
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * [WPx] Taxonomy Capabilities
		 *
		 * Allows entering of a string for each of the possible capabilities for taxonomies.
		 *
		 * @since 1.0
		 *
		 */
		public function wpx_taxonomy_capabilities() {
			$markup = $this->markup('wpx_taxonomy_capabilities');
			$meta_array = explode(',',$this->meta);
			?>
			<?php echo $markup['before_container']; ?>
					<?php echo $markup['before_label']; ?>
						<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
					<?php echo $markup['after_label']; ?>
					<?php echo $markup['before_input']; ?>
						<?php echo $markup['before_description']; ?><?php echo $this->description; ?><?php echo $markup['after_description']; ?>
						<div class="wpx-form-row"><span class="label">[manage_terms]</span><input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[0]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[edit_terms]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[1]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[delete_terms]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[2]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[assign_terms]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[3]; ?>" /></div>
					<?php echo $markup['after_input']; ?>
			<?php echo $markup['after_container']; ?>
			<?php
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * [WPx] Taxonomy Rewrite
		 *
		 * The rewrite array for taxonomies.
		 *
		 * @since 1.0
		 *
		 */
		public function wpx_taxonomy_rewrite() {
			$markup = $this->markup('wpx_taxonomy_rewrite');
			$meta_array = explode(',',$this->meta);
			?>
			<?php echo $markup['before_container']; ?>
					<?php echo $markup['before_label']; ?>
						<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
					<?php echo $markup['after_label']; ?>
					<?php echo $markup['before_input']; ?>
						<?php echo $markup['before_description']; ?><?php echo $this->description; ?><?php echo $markup['after_description']; ?>
						<div class="wpx-form-row"><span class="label">[slug]</span><input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[0]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[with_front]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[1]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[hierarchical]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[2]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[ep_mask]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[3]; ?>" /></div>
						<input type="hidden" class="wpx-rewrite-hidden" value="<?php echo $meta_array[4]; ?>" name="<?php echo $this->meta_key; ?>[]">
						<div class="wpx-form-row">
							<span class="label">(disable?)</span>
							<input class="wpx-rewrite-switch" style="width: auto;" <?php checked( '1' ==  $meta_array[4], true); ?> type="checkbox" class="checkbox input" />
						</div>
					<?php echo $markup['after_input']; ?>
			<?php echo $markup['after_container']; ?>
			<?php
		}

		/*
		|--------------------------------------------------------------------------
		/**
		 * [WPx] CPT Rewrite
		 *
		 * The rewrite array for CPTs.
		 *
		 * @since 1.0
		 *
		 */
		public function wpx_cpt_rewrite() {
			$markup = $this->markup('wpx_cpt_rewrite');
			$meta_array = explode(',',$this->meta);
			?>
			<?php echo $markup['before_container']; ?>
					<?php echo $markup['before_label']; ?>
						<?php if (!$this->array_key) { ?><label for="<?php echo $this->meta_key; ?>"><?php echo $this->label; ?></label><?php } ?>
					<?php echo $markup['after_label']; ?>
					<?php echo $markup['before_input']; ?>
						<?php echo $markup['before_description']; ?><?php echo $this->description; ?><?php echo $markup['after_description']; ?>
						<div class="wpx-form-row"><span class="label">[slug]</span><input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[0]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[with_front]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[1]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[feeds]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[2]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[pages]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[3]; ?>" /></div>
						<div class="wpx-form-row"><span class="label">[ep_mask]</span> <input class="input textfield" type="text" name="<?php echo $this->meta_key; ?>[]" value="<?php echo $meta_array[4]; ?>" /></div>
						<input type="hidden" class="wpx-rewrite-hidden" value="<?php echo $meta_array[5]; ?>" name="<?php echo $this->meta_key; ?>[]">
						<div class="wpx-form-row">
							<span class="label">(disable?)</span>
							<input class="wpx-rewrite-switch" style="width: auto;" <?php checked( '1' ==  $meta_array[5], true); ?> type="checkbox" class="checkbox input" />
						</div>
					<?php echo $markup['after_input']; ?>
			<?php echo $markup['after_container']; ?>
			<?php
		}

	}

}
?>