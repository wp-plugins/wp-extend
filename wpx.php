<?php
/*
 * Plugin Name: WP Extend
 * Plugin URI: http://www.dquinn.net/wp-extend/
 * Description: A developer-centric framework for creating custom post types, taxonomies, metaboxes, options pages and more.
 * Version: 1.0.6
 * Author: Daniel Quinn
 * Author URI: http://www.dquinn.net
 * License: GPL2
 * GitHub Plugin URI: https://github.com/alkah3st/wpx
 * @package wpx
 * @author Daniel Quinn <daniel@dquinn.net>
 * @license http://www.gnu.org/licenses/gpl-2.0.html GNU Public License GPL-2.0+
 * @link http://dquinn.net/wpx/ WP Extend on DQuinn.net
 * @copyright Copyright (c) 2014, Daniel Quinn
*/

/*
Copyright 2014 Daniel Quinn (email: daniel@dquinn.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// if this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// include the wpx utility class
require_once('core/utility.php');
require_once('core/cpts.php');
require_once('core/admin.php');

// include the types factory classes
require_once('core/register/fields.php');
require_once('core/register/types.php');
require_once('core/register/taxonomies.php');
require_once('core/register/options.php');

// installation, deactivation, uninstallation hooks
register_activation_hook(__FILE__, array('wpx_core', 'activate'));
register_deactivation_hook(__FILE__, array('wpx_core', 'deactivate'));

// register internal wpx cpts
add_action( 'plugins_loaded', array( 'wpx_cpts', 'init' ) );

// instantiate wpx core
add_action( 'plugins_loaded', array( 'wpx_core', 'init' ) );

// setup settings page
add_action( 'plugins_loaded', array( 'wpx_admin', 'init' ) );

/**
 * WPX Core Class
 *
 * Registers WPX CPTs, taxonomies, and options pages.
 *
 * @since 1.0
*/
class wpx_core {

	protected static $instance;

	public static function init() {
		is_null( self::$instance ) AND self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Construct the WPX Core
	 *
	 * @since 1.0
	*/
	function __construct() {

		// wpx admin options
		$this->wpx_admin_options = '';

		// transient array allows us to keep track of transients
		// so that we can delete them all later
		global $wpx_transient_array;
		$transient_array = array();

		// loop thru and register all custom cpts
		add_action( 'init', array($this,'register_custom_cpts') );
		add_action( 'init', array($this,'register_custom_options_pages') );
		add_action( 'init', array($this,'register_custom_taxonomies') );

		// capture an uninstall event
		// (WPX must be running to uninstall itself properly)
		$uninstall_state = get_option('wpx_admin_options');
		$confirm = isset($uninstall_state['wpx_uninstall']) ? $uninstall_state['wpx_uninstall'] : false;
		if ($confirm == 'uninstall') add_action( 'admin_init', array($this,'uninstall') );
	}

	/**
	 * Register Custom Options Pages
	 *
	 * Loop through all the posts from the wpx_options CPT, collect custom field
	 * data from the wpx_fields CPT and register options pages.
	 * 
	 * @since 1.0
	*/
	public function register_custom_options_pages() {

		// get all wpx_options cpts as transients
		global $wpx_transient_array;
		$options = get_site_transient('wpx_options');
		$wpx_transient_array[] = 'wpx_options';

		// in the case of multisite, don't cache options pages
		if (!$options || is_multisite()) {
			$options = get_posts(array('posts_per_page'=>-1, 'post_type'=>'wpx_options', 'orderby'=>'menu_order'));
			set_site_transient('wpx_options', $options, YEAR_IN_SECONDS);
		}

		// loop through the wpx options cpts
		foreach($options as $options_page) {

			// "attributes" are custom fields of the wpx options cpts
			$attributes = get_site_transient('wpx_options_attributes_'.$options_page->ID);
			$wpx_transient_array[] = 'wpx_options_attributes_'.$options_page->ID;

			if (!$attributes) {
				$attributes = get_post_custom($options_page->ID);
				set_site_transient('wpx_options_attributes_'.$options_page->ID, $attributes, YEAR_IN_SECONDS);
			}

			// reset the args
			$args = array();

			// go through each custom field
			foreach($attributes as $i=>$attribute) {

				// kick out custom field defaults
				if($i == '_edit_lock' || $i == '_edit_last') continue;

				// extract the validation routine / field key
				if ($i == '_wpx_options_validation') {

					$attribute = $attribute[0];
					$routines = explode("\n", $attribute);
					foreach($routines as $routine) {
						$routine_sets[] = explode(',', $routine);
					}
					$args['validation'] = $routine_sets;

				} else {

					// take everything out of an array format
					$attribute = $attribute[0];

					// remove the wpx prefix so it matches the args in register post type
					$args[str_replace('_wpx_options_','',$i)] = $attribute;

					// if we have a comma, it needs to become an array
					if (strpos($attribute,',') !== false) {
						$args[str_replace('_wpx_options_','',$i)] = explode(',', $attribute);
					}

					// turn all "false", "true" into proper booleans
					if ($attribute == 'true' || $attribute == 'false') $args[str_replace('_wpx_options_','',$i)] = filter_var($attribute, FILTER_VALIDATE_BOOLEAN);

				}

			}

			// only get metaboxes in the admin
			if (is_admin()) {

				// get all meta fields attached to this options page
				$fields_meta = get_site_transient('wpx_options_register_metaboxes_'.$options_page->ID);
				$wpx_transient_array[] = 'wpx_options_register_metaboxes_'.$options_page->ID;

				if (!$fields_meta) {
					$fields_meta = get_post_meta($options_page->ID, '_wpx_options_register_metaboxes', true);
					set_site_transient('wpx_options_register_metaboxes_'.$options_page->ID, $fields_meta, YEAR_IN_SECONDS);
				}
				
				// turn the IDs into an array
				$fields_ids = explode(',', $fields_meta);

				// get all the fields in the array
				$fields = get_site_transient('wpx_options_fields_'.$options_page->ID);
				$wpx_transient_array[] = 'wpx_options_fields_'.$options_page->ID;

				if (!$fields) {

					$fields = get_posts(array('post_type'=>'wpx_fields', 'post__in'=>$fields_ids, 'orderby'=>'menu_order', 'posts_per_page'=>-1));

					set_site_transient('wpx_options_fields_'.$options_page->ID, $fields, YEAR_IN_SECONDS);

				}

				// reset groups array
				$groups = array();

				// what we're doing here is making it possible to later
				// sort by groups and insert other "settings" for metaboxes in the future
				foreach($fields as $field) {
					
					$group = wp_get_object_terms($field->ID, 'wpx_groups');
					
					if ($group) {

						// groups have order and required fields as custom fields
						$order = wpx::get_taxonomy_meta($group[0]->term_id, '_wpx_groups_order', true);
						$required = wpx::get_taxonomy_meta($group[0]->term_id, '_wpx_groups_collapsed', true);

						// reset the settings array
						$settings = array();

						// we insert "required" as a key in the first array of the groups 
						// metabox array, which we'll retrieve later
						if ($required) {
							$settings['collapsed'] = true;
							$groups[$group[0]->name][0] = $settings;
						}

						// same for order
						if ($order) {
							$settings['order'] = $order;
							$groups[$group[0]->name][0] = $settings;
						} else {
							$settings['order'] = 0;
							$groups[$group[0]->name][0] = $settings;
						}
						$groups[$group[0]->name][] = $field;

					} else {
						$groups['Settings'][] = $field;
					}
				}

				// reset metaboxes array
				$metaboxes = array();

				// build metabox arrays sorted by group
				if (is_array($groups)) {

					$metaboxes = $this->sortMetaboxesByGroup($groups, $metaboxes, $options_page, true);
					
					if ($metaboxes) {
						// add the metaboxes to the args
						$args['register_metaboxes'] = $metaboxes;
					}
				}
			}

			// check if the option page has a parent
			// in which case, pass the parent as the menu_parent
			// and override anything set manually
			$parent_page = wpx::get_ancestor_id($options_page);
			if ($parent_page !== $options_page->ID) {
				$args['menu_ancestor'] = $parent_page;
			} 

			// add the title of the options page
			$title = get_site_transient('wpx_args_title_'.$options_page->ID);
			$wpx_transient_array[] = 'wpx_args_title_'.$options_page->ID;

			if (!$title) {
				$args['title'] = get_the_title($options_page->ID);
				set_site_transient('wpx_args_title_'.$options_page->ID, get_the_title($options_page->ID), YEAR_IN_SECONDS);
			} else {
				$args['title'] = $title;
			}

			// register the options page
			$options_iterate = new wpx_register_options(
				$options_page->post_name,
				$args
			);

		}

	}

	/**
	 * Register Custom Taxonomies
	 *
	 * Loop through all the posts in the wpx_taxonomy CPT and collect custom field data
	 * from the wpx_fields CPT to register custom taxonomies.
	 * 
	 * @since 1.0
	*/
	public static function register_custom_taxonomies() {

		// get all wpx taxonomies as transients
		global $wpx_transient_array;
		$taxonomies = get_site_transient('wpx_taxonomies');
		$wpx_transient_array[] = 'wpx_taxonomies';

		// don't register taxonomies if multisite
		if (!$taxonomies || is_multisite()) {
			$taxonomies = get_posts(array('posts_per_page'=>-1, 'post_type'=>'wpx_taxonomy'));
			set_site_transient('wpx_taxonomies', $taxonomies, YEAR_IN_SECONDS);
		}

		// loop through each taxonomy and register it
		foreach($taxonomies as $taxonomy) {
			
			// get the custom fields of this taxonomy
			$attributes = get_site_transient('wpx_taxonomies_attributes_'.$taxonomy->ID);
			$wpx_transient_array[] = 'wpx_taxonomies_attributes_'.$taxonomy->ID;

			if (!$attributes) {
				$attributes = get_post_custom($taxonomy->ID);
				set_site_transient('wpx_taxonomies_attributes_'.$taxonomy->ID, $attributes, YEAR_IN_SECONDS);			
			}

			// reset the args
			$args = array();

			// go through each custom meta field
			foreach($attributes as $i=>$attribute) {
				
				// first, kick out custom field default custom fields
				if($i == '_edit_lock' || $i == '_edit_last') continue;
				
				// take everything out of an array format
				$attribute = $attribute[0];
				
				// remove the wpx prefix so it matches the args in wpx's register post type array
				$args[str_replace('_wpx_taxonomy_','',$i)] = $attribute;
				
				// if we have a comma, it needs to become an array
				if (strpos($attribute,',') !== false) {
					$args[str_replace('_wpx_taxonomy_','',$i)] = explode(',', $attribute);
				}

				// convert "false" to false and "true" to true
				if ($attribute == 'true' || $attribute == 'false') $args[str_replace('_wpx_taxonomy_','',$i)] = filter_var($attribute, FILTER_VALIDATE_BOOLEAN);

				// unset the rewrite array
				unset($args['rewrite']);

				// we need to handle the strings entered for the "rewrite" array
				if ( $i == '_wpx_taxonomy_rewrite' ) {
					if ($attribute) {
						$rewrite_values = explode(',', $attribute);
						if (isset($rewrite_values[0])) $args['rewrite']['slug'] = $rewrite_values[0];
						if (isset($rewrite_values[1])) $args['rewrite']['with_front'] = $rewrite_values[1];
						if (isset($rewrite_values[2])) $args['rewrite']['hierarchical'] = $rewrite_values[2];
						if (isset($rewrite_values[3])) $args['rewrite']['ep_mask'] = $rewrite_values[3];

						// if disabled is checked, then "rewrite" is false
						if (isset($rewrite_values[4]) == 1) $args['rewrite'] = false;

					}
				}

				// and remove the wpx placeholder
				unset($args['_wpx_taxonomy_rewrite']);

				// unset the capabilities
				$args['capabilities'] = false;

				// we need to handle the strings entered for the capabilities array
				if ( $i == '_wpx_taxonomy_capabilities' ) {
					if ($attribute) {
						$cap_values = explode(',', $attribute);
						if (isset($cap_values[0])) $args['capabilities']['manage_terms'] = $cap_values[0];
						if (isset($cap_values[1])) $args['capabilities']['edit_terms'] = $cap_values[1];
						if (isset($cap_values[2])) $args['capabilities']['delete_terms'] = $cap_values[2];
						if (isset($cap_values[3])) $args['capabilities']['assign_terms'] = $cap_values[3];
					}
				}

				// if there are no capabilities, unset it
				if ($args['capabilities'] == false) unset($args['capabilities']);

				// and remove the wpx placeholder
				unset($args['_wpx_taxonomy_capabilities']);

			}

			// if a singular & plural name was specified, we specify defaults so that
			// we can be lazy and not enter in everything by hand
			$label_singular = isset($args['label_singular']) ? $args['label_singular'] : false;
			$label_plural = isset($args['label_singular']) ? $args['label_singular'] : false;
			if ($label_singular && $label_plural) {
				$args['labels'] = array(
					'name' =>$args['label_singular'],
					'singular_name' => $args['label_singular'],
					'menu_name' => $args['label_plural'],
					'all_items' => 'All '.$args['label_plural'],
					'edit_item' => 'Edit '.$args['label_singular'],
					'update_item' => 'Update '.$args['label_singular'],
					'add_new_item' => 'Add New '.$args['label_singular'],
					'new_item_name' => 'New '.$args['label_singular'],
					'search_items' => 'Search '.$args['label_plural'],
					'popular_items' => 'Popular '.$args['label_plural'],
					'parent_item' => 'Parent '.$args['label_singular'],
					'parent_item_colon' => 'Parent '.$args['label_singular'],
					'separate_items_with_commas' => 'Separate '.$args['label_plural'].' with commas.',
					'add_or_remove_items' => 'Add or remove '.$args['label_plural'],
					'choose_from_most_used' => 'Choose from the most used '.$args['label_plural'].'.',
					'not_found' => 'No '.$args['label_plural'].' found.'
				);
			} else {
				// however if we want to get specific, we leave the plural/singular fields blank
				// and get the manually entered values for each label
				$label_name = isset($args['name']) ? $args['name'] : false;
				$label_singular_name = isset($args['singular_name']) ? $args['name'] : false;
				$label_menu_name = isset($args['menu_name']) ? $args['menu_name'] : false;
				$label_all_items = isset($args['all_items ']) ? $args['all_items '] : false;
				$label_edit_item = isset($args['edit_item']) ? $args['edit_item'] : false;
				$label_update_item = isset($args['update_item']) ? $args['update_item'] : false;
				$label_add_new_item = isset($args['dd_new_item']) ? $args['dd_new_item'] : false;
				$label_new_item_name = isset($args['new_item_name']) ? $args['new_item_name'] : false;
				$label_search_items = isset($args['search_items']) ? $args['search_items'] : false;
				$label_popular_items = isset($args['popular_items']) ? $args['popular_items'] : false;
				$label_parent_item = isset($args['parent_item']) ? $args['parent_item'] : false;
				$label_parent_item_colon = isset($args['parent_item_colon']) ? $args['parent_item_colon'] : false;
				$label_separate_items_with_commas = isset($args['separate_items_with_commas']) ? $args['separate_items_with_commas'] : false;
				$label_add_or_remove_items = isset($args['add_or_remove_items']) ? $args['add_or_remove_items'] : false;
				$label_choose_from_most_used = isset($args['choose_from_most_used']) ? $args['choose_from_most_used'] : false;
				$label_not_found = isset($args['not_found']) ? $args['not_found'] : false;

				if ($label_name) { $args['labels']['name'] = $args['name']; }
				if ($label_singular_name) { $args['labels']['singular_name'] = $args['singular_name']; }
				if ($label_menu_name) { $args['labels']['menu_name'] = $args['menu_name']; }
				if ($label_all_items) { $args['labels']['all_items'] = $args['name']; }
				if ($label_edit_item) { $args['labels']['edit_item'] = $args['edit_item']; }
				if ($label_update_item) { $args['labels']['update_item'] = $args['update_item']; }
				if ($label_add_new_item) { $args['labels']['add_new_item'] = $args['add_new_item']; }
				if ($label_new_item_name) { $args['labels']['new_item_name'] = $args['new_item_name']; }
				if ($label_search_items) { $args['labels']['search_items'] = $args['search_items']; }
				if ($label_popular_items) { $args['labels']['popular_items'] = $args['popular_items']; }
				if ($label_parent_item) { $args['labels']['parent_item'] = $args['parent_item']; }
				if ($label_parent_item_colon) { $args['labels']['parent_item_colon'] = $args['parent_item_colon']; }
				if ($label_separate_items_with_commas) { $args['labels']['separate_items_with_commas'] = $args['separate_items_with_commas']; }
				if ($label_add_or_remove_items) { $args['labels']['add_or_remove_items'] = $args['add_or_remove_items']; }
				if ($label_choose_from_most_used) { $args['labels']['choose_from_most_used'] = $args['choose_from_most_used']; }
				if ($label_not_found) { $args['labels']['not_found'] = $args['not_found']; }
			}

			// then kick out the specific labels, since this will be entered
			// into the labels array
			unset($args['label_singular']);
			unset($args['label_plural']);
			unset($args['name']);
			unset($args['singular_name']);
			unset($args['menu_name']);
			unset($args['all_items']);
			unset($args['edit_item']);
			unset($args['update_item']);
			unset($args['add_new_item']);
			unset($args['new_item_name']);
			unset($args['search_items']);
			unset($args['popular_items']);
			unset($args['parent_item_colon']);
			unset($args['separate_items_with_commas']);
			unset($args['add_or_remove_items']);
			unset($args['choose_from_most_used']);
			unset($args['not_found']);

			// only attach metaboxes in the admin
			if (is_admin()) {

				// get all wpx meta fields that were attached to this cpt
				$fields_meta = get_site_transient('wpx_taxonomy_metaboxes_meta_'.$taxonomy->ID);
				$wpx_transient_array[] = 'wpx_taxonomy_metaboxes_meta_'.$taxonomy->ID;

				if (!$fields_meta) {
					$fields_meta = get_post_meta($taxonomy->ID, '_wpx_taxonomy_register_metaboxes', true);
					set_site_transient('wpx_taxonomy_metaboxes_meta_'.$taxonomy->ID, $fields_meta, YEAR_IN_SECONDS);
				}

				$fields_ids = explode(',', $fields_meta);
				$fields = get_site_transient('wpx_taxonomy_fields_'.$taxonomy->ID);
				$wpx_transient_array[] = 'wpx_taxonomy_fields_'.$taxonomy->ID;

				// now retrieve the wpx fields' custom meta
				if (!$fields) {
					$fields = get_posts(array('post_type'=>'wpx_fields', 'post__in'=>$fields_ids, 'orderby'=>'menu_order', 'posts_per_page'=>-1));
					set_site_transient('wpx_taxonomy_fields_'.$taxonomy->ID, $fields, YEAR_IN_SECONDS);

				}

				// reset the metabox array
				$metaboxes = array();

				// for each meta field, translate its custom meta into the metabox array
				foreach($fields as $field) {

					$required = get_site_transient('wpx_taxonomy_fields_'.$field->ID);
					$wpx_transient_array[] = 'wpx_taxonomy_fields_'.$field->ID;

					// required 
					if (!$required) {
						$required = get_post_meta($field->ID, '_wpx_fields_required', true);
						set_site_transient('wpx_taxonomy_fields_'.$field->ID, $required, YEAR_IN_SECONDS);
					}
					
					// label
					$_wpx_fields_label = get_site_transient('_wpx_fields_label_'.$field->ID);
					$wpx_transient_array[] = '_wpx_fields_label_'.$field->ID;

					if (!$_wpx_fields_label) {
						$_wpx_fields_label = get_post_meta($field->ID, '_wpx_fields_label', true);
						set_site_transient('_wpx_fields_label_'.$field->ID, $_wpx_fields_label, YEAR_IN_SECONDS);
					}

					// description
					$_wpx_fields_description = get_site_transient('_wpx_fields_description'.$field->ID);
					$wpx_transient_array[] = '_wpx_fields_description'.$field->ID;

					if (!$_wpx_fields_description) {
						$_wpx_fields_description = get_post_meta($field->ID, '_wpx_fields_description', true);
						set_site_transient('_wpx_fields_description'.$field->ID, $_wpx_fields_description, YEAR_IN_SECONDS);
					}

					// type
					$_wpx_fields_type = get_site_transient('_wpx_fields_type'.$field->ID);
					$wpx_transient_array[] = '_wpx_fields_type'.$field->ID;

					if (!$_wpx_fields_type) {
						$_wpx_fields_type = get_post_meta($field->ID, '_wpx_fields_type', true);
						set_site_transient('_wpx_fields_type'.$field->ID, $_wpx_fields_type, YEAR_IN_SECONDS);

					}

					$metaboxes[] = array(
						'id'=> $field->post_name,
						'label'=>$_wpx_fields_label,
						'description'=>$_wpx_fields_description,
						'field'=>$_wpx_fields_type,
						'required'=>filter_var($required, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
					);
				}
				
				$args['register_metaboxes'] = $metaboxes;

			}

			$args_object_type = isset($args['object_type']) ? $args['object_type'] : false;

			// use wpx to register each taxonomy, with its metaboxes (the fields)
			$taxonomy = new wpx_register_taxonomy(
				$taxonomy->post_name,
				$args_object_type,
				$args
			);

		}

	}

	/**
	 * Register Custom Post Types
	 *
	 * Loop through the wpx_types CPT and collect custom field data from the
	 * wpx_fields CPT to register each custom post type.
	 * 
	 * @since 1.0
	*/
	public function register_custom_cpts() {

		// get all the wpx custom post types as transients
		global $wpx_transient_array;
		$post_types = get_site_transient('wpx_cpts');
		$wpx_transient_array[] = 'wpx_cpts';

		// in the case of multisite, we can't cache the cpts
		if (!$post_types || is_multisite()) {
			$post_types = get_posts(array('posts_per_page'=>-1, 'post_type'=>'wpx_types'));
			set_site_transient('wpx_cpts', $post_types, YEAR_IN_SECONDS);
		}

		// for each wpx cpt
		foreach($post_types as $post_type) {

			// get all the custom fields attached
			$attributes = get_site_transient('wpx_cpts_attributes_'.$post_type->ID);
			$wpx_transient_array[] = 'wpx_cpts_attributes_'.$post_type->ID;

			if (!$attributes) {
				$attributes = get_post_custom($post_type->ID);
				set_site_transient('wpx_cpts_attributes_'.$post_type->ID, $attributes, YEAR_IN_SECONDS);
			}

			// reset the args
			$args = array();

			// go through each custom field
			foreach($attributes as $i=>$attribute) {
				
				// kick out edit lock and edit last
				if($i == '_edit_lock' || $i == '_edit_last' || $i == '_wp_old_slug') continue;

				// take everything out of an array format
				$attribute = $attribute[0];

				// remove the wpx prefix so it matches the args in register post type
				$args[str_replace('_wpx_cpt_','',$i)] = $attribute;

				// if we have a comma, it needs to become an array
				if (strpos($attribute,',') !== false) {
					$args[str_replace('_wpx_cpt_','',$i)] = explode(',', $attribute);
				}

				// convert "true" to true and "false" to false (only applies to wpx-set values)
				if ($attribute == 'true' || $attribute == 'false') $args[str_replace('_wpx_cpt_','',$i)] = filter_var($attribute, FILTER_VALIDATE_BOOLEAN);

				// make sure supports is an array
				$supports = isset($args['supports']) ? $args['supports'] : false;
				if (!is_array($supports) && !is_bool($supports)) {
					$args['supports']= array($supports);
				}

				// make sure supports is an array
				$taxonomies = isset($args['taxonomies']) ? $args['taxonomies'] : false;
				if (!is_array($taxonomies) && !is_bool($taxonomies)) {
					$args['taxonomies']= array($taxonomies);
				}

				// if a singular & plural name was specified, fill out all the labels
				$label_singular = isset($args['label_singular']) ? $args['label_singular'] : false;
				$label_plural = isset($args['label_singular']) ? $args['label_singular'] : false;

				if ($label_singular && $label_plural) {
					// do nothing, this will be handled internally (in the wpx types class)
				} else {
					// otherwise, reorganize the individual labels under a labels array
					$label_name = isset($args['name']) ? $args['name'] : false;
					$label_singular_name = isset($args['singular_name']) ? $args['singular_name'] : false;
					$label_menu_name = isset($args['menu_name']) ? $args['menu_name'] : false;
					$label_all_items = isset($args['all_items ']) ? $args['all_items '] : false;
					$label_add_new = isset($args['add_new']) ? $args['add_new'] : false;
					$label_add_new_item = isset($args['add_new_item']) ? $args['add_new_item'] : false;
					$label_edit_item = isset($args['edit_item']) ? $args['edit_item'] : false;
					$label_new_item = isset($args['new_item']) ? $args['new_item'] : false;
					$label_view_item = isset($args['view_item']) ? $args['view_item'] : false;
					$label_search_items = isset($args['search_items']) ? $args['search_items'] : false;
					$label_not_found = isset($args['not_found']) ? $args['not_found'] : false;
					$label_not_found_in_trash = isset($args['not_found_in_trash']) ? $args['not_found_in_trash'] : false;
					$label_parent_item_colon = isset($args['parent_item_colon']) ? $args['parent_item_colon'] : false;
					if ($label_name) { $args['labels']['name'] = $args['name']; }
					if ($label_singular_name) { $args['labels']['singular_name'] = $args['singular_name']; }
					if ($label_menu_name) { $args['labels']['menu_name'] = $args['menu_name']; }
					if ($label_all_items) { $args['labels']['all_items'] = $args['name']; }
					if ($label_add_new) { $args['labels']['add_new'] = $args['add_new']; }
					if ($label_add_new_item) { $args['labels']['add_new_item'] = $args['add_new_item']; }
					if ($label_edit_item) { $args['labels']['edit_item'] = $args['edit_item']; }
					if ($label_new_item) { $args['labels']['new_item'] = $args['new_item']; }
					if ($label_view_item) { $args['labels']['view_item'] = $args['view_item']; }
					if ($label_search_items) { $args['labels']['search_items'] = $args['search_items']; }
					if ($label_not_found) { $args['labels']['not_found'] = $args['not_found']; }
					if ($label_not_found_in_trash) { $args['labels']['not_found_in_trash'] = $args['not_found_in_trash']; }
					if ($label_parent_item_colon) { $args['labels']['parent_item_colon'] = $args['parent_item_colon']; }
				}

				// then kick out the labels
				unset($args['name']);
				unset($args['singular_name']);
				unset($args['menu_name']);
				unset($args['all_items']);
				unset($args['add_new']);
				unset($args['add_new_item']);
				unset($args['edit_item']);
				unset($args['new_item']);
				unset($args['view_item']);
				unset($args['search_items']);
				unset($args['not_found']);
				unset($args['not_found_in_trash']);
				unset($args['parent_item_colon']);

				// handle capabilities
				$rename_capabilities = false;
				$args_capabilities = isset($args['capabilities']) ? $args['capabilities'] : false;
				if ( is_array($args_capabilities) ) {
					foreach($args_capabilities as $i=>$capability) {
						if ($i == 0 && $capability) $rename_capabilities['edit_post'] = $capability;
						if ($i == 1 && $capability) $rename_capabilities['read_post'] = $capability;
						if ($i == 2 && $capability) $rename_capabilities['delete_post '] = $capability;
						if ($i == 3 && $capability) $rename_capabilities['edit_posts'] = $capability;
						if ($i == 4 && $capability) $rename_capabilities['edit_others_posts'] = $capability;
						if ($i == 5 && $capability) $rename_capabilities['publish_posts'] = $capability;
						if ($i == 6 && $capability) $rename_capabilities['read_private_posts'] = $capability;
					}
					$args['capabilities'] = $rename_capabilities;
				}

				if (!$args_capabilities) unset($args['capabilities']);

				// cast menu_position as a number (necessary, otherwise it gets ignored by register_post_type())
				$args_menu_position = isset($args['menu_position']) ? $args['menu_position'] : false;
				if ($args_menu_position) {
					$args['menu_position'] = (int)$args['menu_position'];
				}

				// unset the rewrite array
				unset($args['rewrite']);

				// we need to handle the strings entered for the rewrite array
				if ( $i == '_wpx_cpt_rewrite' ) {
					if ($attribute) {
						$rewrite_values = explode(',', $attribute);
						if (isset($rewrite_values[0])) $args['rewrite']['slug'] = $rewrite_values[0];
						if (isset($rewrite_values[1])) $args['rewrite']['with_front'] = $rewrite_values[1];
						if (isset($rewrite_values[2])) $args['rewrite']['feeds'] = $rewrite_values[2];
						if (isset($rewrite_values[3])) $args['rewrite']['pages'] = $rewrite_values[3];
						if (isset($rewrite_values[4])) $args['rewrite']['ep_mask'] = $rewrite_values[4];

						// if disabled is checked, set rewrite to false
						if (isset($rewrite_values[5]) == 1) {
							$args['rewrite'] = false;
						}
					}
				}

				// and remove the wpx placeholder
				unset($args['_wpx_cpt_rewrite']);

			}

			// only register metafields inside the dashboard 
			if (is_admin()) {

				// get all meta fields attached to this cpt by ID
				$fields_meta = get_site_transient('wpx_cpt_metaboxes_'.$post_type->ID);
				$wpx_transient_array[] = 'wpx_cpt_metaboxes_'.$post_type->ID;

				if (!$fields_meta) {
					$fields_meta = get_post_meta($post_type->ID, '_wpx_cpt_metaboxes', true);
					set_site_transient('wpx_cpt_metaboxes_'.$post_type->ID, $fields_meta, YEAR_IN_SECONDS);
				}

				$fields_ids = explode(',', $fields_meta);

				// get all the custom fields or the wpx meta field
				$fields = get_site_transient('wpx_fields_'.$post_type->ID);
				$wpx_transient_array[] = 'wpx_fields_'.$post_type->ID;

				if (!$fields) {
					$fields = get_posts(array('post_type'=>'wpx_fields', 'post__in'=>$fields_ids, 'orderby'=>'menu_order', 'posts_per_page'=>-1, 'order'=>'ASC'));
					set_site_transient('wpx_fields_'.$post_type->ID, $fields, YEAR_IN_SECONDS);
				}

				// reset the groups taxonomy
				$groups = array();

				// what we're doing here is making it possible to later sort by groups 
				// and insert other "settings" for metaboxes
				$groups = $this->getAllGroups($fields);

				// reset the metaboxes
				$metaboxes = array();

				// sort metabox arrays by group
				if (is_array($groups)) {
					$metaboxes = $this->sortMetaboxesByGroup($groups, $metaboxes, $post_type);
					if ($metaboxes) {
						$args['register_metaboxes'] = $metaboxes;
					}
				}
			}

			// make taxonomies an array always
			$args['taxonomies'] = isset($args['taxonomies']) ? $args['taxonomies'] : false;
			if (!empty($args['taxonomies'])) {
				$args['taxonomies'] = $args['taxonomies'];
			} else {
				unset($args['taxonomies']);
			}

			// register each post type, with its metaboxes (the fields)
			$cpt_iterate = new wpx_register_type(
				$post_type->post_name,
				$args
			);

		}

	}

	/**
	 * Sort Metaboxes By Group
	 *
	 * Used in register_cpts and register_options_pages to sort an array of
	 * custom field data collected from the wpx_fields CPT by the wpx_groups
	 * taxonomy.
	 * 
	 * @since 1.0
	*/
	private function sortMetaboxesByGroup($groups, $metaboxes, $post_type=false, $is_options_page=false) {

		global $wpx_transient_array;

		// for each given wpx_group
		foreach($groups as $i=>$group) {

			// break out the meta field 
			foreach($group as $x=>$field) {

				$field_id = isset($field->ID) ? $field->ID : false;

				// the first array is always the settings array
				if ($x == 0 && !$field_id) {

					$metaboxes[$i][0] = $field;

				} else {

					$_wpx_fields_required = get_site_transient('wpx_fields_required_'.$field->ID);
					$wpx_transient_array[] = 'wpx_fields_required_'.$field->ID;

					$_wpx_fields_label = get_site_transient('wpx_fields_label_'.$field->ID);
					$wpx_transient_array[] = 'wpx_fields_label_'.$field->ID;

					$_wpx_fields_description = get_site_transient('wpx_fields_description_'.$field->ID);
					$wpx_transient_array[] = 'wpx_fields_description_'.$field->ID;

					$_wpx_fields_type = get_site_transient('wpx_fields_type_'.$field->ID);
					$wpx_transient_array[] = 'wpx_fields_type_'.$field->ID;

					// required
					if (!$_wpx_fields_required) {
						$required = get_post_meta($field->ID, '_wpx_fields_required', true);
						set_site_transient('wpx_fields_required_'.$field->ID, $_wpx_fields_required, YEAR_IN_SECONDS);

					}

					// label
					if (!$_wpx_fields_label) {
						$_wpx_fields_label = get_post_meta($field->ID, '_wpx_fields_label', true);
						set_site_transient('_wpx_fields_label_'.$field->ID, $_wpx_fields_label, YEAR_IN_SECONDS);
					}

					// description
					if (!$_wpx_fields_description) {
						$_wpx_fields_description = get_post_meta($field->ID, '_wpx_fields_description', true);
						set_site_transient('_wpx_fields_description_'.$field->ID, $_wpx_fields_description, YEAR_IN_SECONDS);
					}

					// type
					if (!$_wpx_fields_type) {
						$_wpx_fields_type = get_post_meta($field->ID, '_wpx_fields_type', true);
						set_site_transient('_wpx_fields_type_'.$field->ID, $_wpx_fields_type, YEAR_IN_SECONDS);
					}

					// options pages do not use a preceding underscore
					if ($is_options_page) {
						$id = $field->post_name;
					} else {
						$id = '_'.$post_type->post_name.'_'.$field->post_name;
					}

					// setup the metabox array
					$metaboxes[$i][] = array(
						'id'=>$id,
						'label'=>$_wpx_fields_label,
						'description'=>$_wpx_fields_description,
						'field'=>$_wpx_fields_type,
						'required'=>filter_var($required, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE)
					);

				}
			}
		}

		return $metaboxes;
	}

	/**
	 * Get All Groups
	 *
	 * Retrieve all the wpx_group terms so they can be used to sort metaboxes.
	 * 
	 * @since 1.0
	*/
	private function getAllGroups($fields) {

		$groups = false;

		global $wpx_transient_array;

		foreach($fields as $field) {

			$group = get_site_transient('wpx_groups_'.$field->ID);
			$wpx_transient_array[] = 'wpx_groups_'.$field->ID;

			if (!$group) {
				$group = wp_get_object_terms($field->ID, 'wpx_groups');
				set_site_transient('wpx_groups_'.$field->ID, $group, YEAR_IN_SECONDS);
			}

			if ($group) {
				
				// order
				$order = get_site_transient('wpx_groups_order_'.$group[0]->term_id);
				$wpx_transient_array[] = 'wpx_groups_order_'.$group[0]->term_id;

				// required
				$required = get_site_transient('wpx_groups_required_'.$group[0]->term_id);
				$wpx_transient_array[] = 'wpx_groups_required_'.$group[0]->term_id;

				if (!$order) {
					$order = wpx::get_taxonomy_meta($group[0]->term_id, '_wpx_groups_order', true);
					set_site_transient('wpx_groups_order_'.$group[0]->term_id, $order, YEAR_IN_SECONDS);
				}

				if (!$required) {
					$required = wpx::get_taxonomy_meta($group[0]->term_id, '_wpx_groups_collapsed', true);
					set_site_transient('wpx_groups_required_'.$group[0]->term_id, $required, YEAR_IN_SECONDS);
				}

				// reset the settings array
				$settings = array();

				// we insert "required" as a key in the first array of the groups 
				// metabox array, which we'll retrieve later
				if ($required) {
					$settings['collapsed'] = true;
					$groups[$group[0]->name][0] = $settings;
				}

				// same for order
				if ($order) {
					$settings['order'] = $order;
					$groups[$group[0]->name][0] = $settings;
				} else {
					$settings['order'] = 0;
					$groups[$group[0]->name][0] = $settings;
				}

				$groups[$group[0]->name][] = $field;

			} else {

				$groups['Settings'][] = $field;

			}
		}

		return $groups;

	}

	/**
	 * Activation
	 *
	 * On activation we flush rewrite rules because of the new CPTs & taxonomies
	 * and then assign some default settings for the plugin that I prefer.
	 * 
	 * @todo Make this work for Multisite.
	 * @since 1.0
	*/
	public static function activate() {

		// for cpts and such
		flush_rewrite_rules();

		// turn on the things I like 
		update_option( 'wpx_admin_options', array(
			'right_now_widget_extended'=>1, 
			'logo_homepage'=>1, 
			'excerpt_metabox_on'=>1, 
			'recent_comments_styles'=>1, 
			'html5'=>1, 
			'activate_sidebars'=>0, 
			'styles_login'=>1, 
			'styles_dashboard'=>1) 
		);

	}

	/**
	 * Deactivation
	 *
	 * On deactivation we flush rewrite rules because of the new CPTs & taxonomies
	 * are gone, and then clear all the transients.
	 * 
	 * @todo Make this work for Multisite.
	 * @since 1.0
	*/
	public static function deactivate() {

		// for cpts and such
		flush_rewrite_rules();

		// clear transients
		wpx::clear_transients();

	}

	/**
	 * Uninstall
	 *
	 * Uninstall has to be done independently of the standard uninstall that
	 * happens in uninstall.php because we need WPX running. This will delete all 
	 * data originally generated by WPX (including the CPTs, taxonomies, and options pages) 
	 * as well as clear transients. Then it deactivates the plugin and sends the user
	 * to the plugin area.
	 * 
	 * @todo Make this work for Multisite.
	 * @since 1.0
	*/
	public function uninstall() {

		// delete admin page settings
		delete_option('wpx_admin_options');

		// loop through each custom option page meta and delete it
		$wpx_option_pages = get_posts(array('post_type'=>'wpx_options', 'post_status'=>'any', 'posts_per_page'=>-1));

		if ($wpx_option_pages) {

			foreach($wpx_option_pages as $option_page) {
				delete_option('wpx_options_'.$option_page->post_name);
			}

		}

		// loop through each custom tax term and delete its meta
		$wpx_taxonomies = get_posts(array('post_type'=>'wpx_taxonomy', 'post_status'=>'any', 'posts_per_page'=>-1));

		if ($wpx_taxonomies) {

			foreach($wpx_taxonomies as $taxonomy) {

				$taxonomy_terms = get_terms($taxonomy->post_name, array('hide_empty'=>false));

				if ($taxonomy_terms) {

					foreach($taxonomy_terms as $term) {
						delete_option( "taxonomy_term_".$term->term_id);
						wp_delete_term( $term->term_id, $taxonomy->post_name );
					}

				}
			}
		}

		// delete group terms
		$group_terms = get_terms( 'wpx_groups', array('hide_empty'=>false) );
		
		foreach($group_terms as $term) {
			wp_delete_term( $term->term_id, 'wpx_groups' );
		}

		// delete posts for all custom cpts 
		$post_types = get_posts(array('post_type'=>array('wpx_types', 'wpx_taxonomy','wpx_options', 'wpx_fields'), 'post_status'=>'any', 'posts_per_page'=>-1));

		if ($post_types) {

			foreach($post_types as $type) {
				 wp_delete_post( $type->ID, true );
			}
		}

		// for cpts and such
		flush_rewrite_rules();

		// clear transients
		wpx::clear_transients();

		// deactivate the plugin
		deactivate_plugins(plugin_basename( __FILE__ ));

		// return to the deactivated plugin state
		wp_redirect(get_bloginfo('url').'/wp-admin/plugins.php?deactivate=true&plugin_status=all&paged=1&s=');

	}

}