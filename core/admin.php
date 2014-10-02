<?php
/**
 * WPX Settings Page
 *
 * Sets up the WPX settings page.
 *
 * @package wpx
 * 
*/

class wpx_admin {

	protected static $instance;

	public static function init() {
		is_null( self::$instance ) AND self::$instance = new self;
		return self::$instance;
	}

	function __construct() {

		// add the styles for wpx
		add_action( 'admin_head', array($this,'add_dashboard_styles') );

		// create a menu for wpx
		add_action( 'admin_menu', array($this, 'add_dashboard_menu'));
		add_action( 'parent_file', array($this, 'set_menu_classes'));

		// make it possible to style the login screen
		if (wpx::get_option_meta('wpx_admin_options', 'styles_login')) {
			add_action('login_head',  array('wpx','extend_login_styles'));
		}

		// make it possible to style the dashboard
		if (wpx::get_option_meta('wpx_admin_options', 'styles_dashboard')) {
			add_action( 'admin_head', array('wpx','extend_dashboard_styles') );
		}

		// use HTML5 markup
		if (wpx::get_option_meta('wpx_admin_options', 'html5')) {
			add_theme_support( 'html5', array( 'comment-list', 'comment-form', 'search-form', 'gallery', 'caption' ) );
		}

		// turn off recent comments styles
		if (wpx::get_option_meta('wpx_admin_options', 'recent_comments_styles')) {
			add_action( 'widgets_init',  array('wpx', 'disable_recent_comments_styles'));
		}

		// display the excerpt
		if (wpx::get_option_meta('wpx_admin_options', 'enable_excerpt_metabox')) {
			add_filter( 'default_hidden_meta_boxes', array('wpx', 'enable_excerpt_metabox'), 20, 1 );
		}

		// link the logo to the theme's homepage
		if (wpx::get_option_meta('wpx_admin_options', 'logo_homepage')) {
			add_filter( 'login_headerurl', array('wpx','change_login_logo_url') );
		}

		// extend the right now widget
		if (wpx::get_option_meta('wpx_admin_options', 'right_now_widget_extended')) {
			add_action( 'dashboard_glance_items' , array('wpx','extend_right_now_widget') );
		}

	}

	/**
	 * Uninstall Section Disclaimer
	 *
	 * @since 1.0
	*/
	public function uninstall_section_info() {
        echo '
			<p>If you want to remove all data that WPX deploys, enter "uninstall" below and Save Settings. This will disable WPX Extend and you will then need to delete it normally.</p>
			<p><strong>Please understand that this means you will be deleting all custom post types, meta fields, taxonomies, options pages, and group terms you have created with the plugin.</p>
			<p><em>This will also delete any meta data saved by the plugin to your installation.</em></strong></p>
        ';
    }

	/**
	 * Uninstall Field
	 *
	 * @since 1.0
	*/
	public function uninstall_field() {
        printf(
            '<input type="text" class="regular-text" id="wpx_uninstall" name="wpx_admin_options[wpx_uninstall]" value="%s" /><p class="description">It\'s strongly advised you create an <a href="http://local.wpx.com/wp-admin/export.php">XML export</a> of the WPX data before uninstalling.</p>',
            isset( $this->wpx_admin_options['wpx_uninstall'] ) ? esc_attr( $this->wpx_admin_options['wpx_uninstall']) : ''
        );	
	}

	/**
	 * Add Custom Data to Right Now Widget
	 *
	 * @since 1.0
	*/
	public function right_now_widget_extended() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><span>Extend Right Now Widget</span></legend>
			<label for="right_now_widget_extended">
			<input name="wpx_admin_options[right_now_widget_extended]" type="checkbox" value="1" <?php checked( '1' ==  wpx::get_option_meta('wpx_admin_options', 'right_now_widget_extended'), true); ?>>
			Add custom post types and taxonomies to the Right Now widget?</label>
			<p class="description">(If you check this option, WordPress will add all custom post types and taxonomies to the Dashboard's Right Now widget.)</p>
		</fieldset>
		<?php
	}

	/**
	 * Render Theme Support for HTML5
	 *
	 * @since 1.0
	*/
	public function render_theme_support_html5() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><span>HTML5 Output</span></legend>
			<label for="styles_login">
			<input name="wpx_admin_options[html5]" type="checkbox" value="1" <?php checked( '1' ==  wpx::get_option_meta('wpx_admin_options', 'html5'), true); ?>>
			Make WordPress output HTML5 markup wherever possible?</label>
			<p class="description">(If you check this option, WordPress will generate HTML5 markup for the following core structures: the comment list, the comment form, the search form galleries, and captions.)</p>
		</fieldset>
		<?php
	}

	/**
	 * Link Styles for Login Logo
	 *
	 * @since 1.0
	*/
	public function link_styles_logo() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><span>Link Login Logo to Theme Homepage</span></legend>
			<label for="logo_homepage">
			<input name="wpx_admin_options[logo_homepage]" type="checkbox" value="1" <?php checked( '1' ==  wpx::get_option_meta('wpx_admin_options', 'logo_homepage'), true); ?>>
			Link the Login logo to the theme's homepage?</label>
			<p class="description">(If you check this option, WordPress link the logo on the Login screen to your theme's homepage.)</p>
		</fieldset>
		<?php
	}

	/**
	 * Render Styles for the Login
	 *
	 * @since 1.0
	*/
	public function render_styles_login() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><span>Login Styling Settings</span></legend>
			<label for="styles_login">
			<input name="wpx_admin_options[styles_login]" type="checkbox" value="1" <?php checked( '1' ==  wpx::get_option_meta('wpx_admin_options', 'styles_login'), true); ?>>
			Apply login.css styles to the login screen?</label>
			<p class="description">(If you check this option, you can create a login.css file in your theme's templates directory. The styles in this file will get applied to the WordPress login screens.)</p>
		</fieldset>
		<?php
	}

	/**
	 * Render Styles for the Dashboard
	 *
	 * @since 1.0
	*/
	public function render_styles_dashboard() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><span>Dashboard Styling Settings</span></legend>
			<label for="styles_dashboard">
			<input name="wpx_admin_options[styles_dashboard]" type="checkbox" value="1" <?php checked( '1' ==  wpx::get_option_meta('wpx_admin_options', 'styles_dashboard'), true); ?>>
			Apply dashboard.css styles to the Dashboard?</label>
			<p class="description">(If you check this option, you can create a dashboard.css file in your theme's templates directory. The styles in this file will get applied to the WordPress Dashboard.)</p>
		</fieldset>
		<?php
	}

	/**
	 * Remove Recent Comments Inline Styles
	 *
	 * @since 1.0
	*/
	public function recent_comments_styles_off() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><span>Disable Recent Comments Styles</span></legend>
			<label for="recent_comments_styles">
			<input name="wpx_admin_options[recent_comments_styles]" type="checkbox" value="1" <?php checked( '1' ==  wpx::get_option_meta('wpx_admin_options', 'recent_comments_styles'), true); ?>>
			Disable recent comments styles?</label>
			<p class="description">(If you check this option, WordPress will not output the recent comments widget inline styles.)</p>
		</fieldset>
		<?php
	}

	/**
	 * Re-Activate the Excerpt Box
	 *
	 * @since 1.0
	*/
	public function excerpt_metabox_on() {
		?>
		<fieldset>
			<legend class="screen-reader-text"><span>Enable Excerpt Metabox</span></legend>
			<label for="excerpt_metabox_on">
			<input name="wpx_admin_options[excerpt_metabox_on]" type="checkbox" value="1" <?php checked( '1' ==  wpx::get_option_meta('wpx_admin_options', 'excerpt_metabox_on'), true); ?>>
			Do not hide the Excerpt metabox by default?</label>
			<p class="description">(If you check this option, WordPress will not hide the Excerpt metabox by default.)</p>
		</fieldset>
		<?php
	}

	/**
	 * Add WPX Styles to the Dasboard
	 *
	 * @since 1.0
	*/
	public function add_dashboard_styles(){
		if (is_admin()) {
			// these are the styles for wpx
			wp_register_style('wpx.dashboard', plugins_url('../assets/styles/dashboard.css',  __FILE__), false, null, 'screen');
			wp_enqueue_style('wpx.dashboard');
		}
	}

	/**
	 * Add WPX Menu Tab to Dashboard
	 *
	 * @since 1.0
	*/
	public function add_dashboard_menu(){

		// main menu tab
		add_menu_page( 'WordPress Extend', 'WP Extend', 'manage_options', 'wpx', array($this,'wpx_admin_page'), 'dashicons-wordpress-alt');
		
		// settings page
		add_options_page('Settings', 'Settings', 'manage_options', 'wpx_admin_page', array( $this, 'wpx_admin_page' ));
		add_action( 'admin_init', array( $this, 'wpx_admin_page_init' ) );

		// all the cpts
		add_submenu_page( 'wpx', 'Settings', 'Settings', 'manage_options', 'wpx', array($this,'wpx_admin_page'));
		add_submenu_page( 'wpx', 'Meta Fields', 'Meta Fields', 'manage_options', 'edit.php?post_type=wpx_fields');
		add_submenu_page( 'wpx', 'Post Types', 'Post Types', 'manage_options', 'edit.php?post_type=wpx_types');
		add_submenu_page( 'wpx', 'Taxonomies', 'Taxonomies', 'manage_options', 'edit.php?post_type=wpx_taxonomy');
		add_submenu_page( 'wpx', 'Options', 'Options', 'manage_options', 'edit.php?post_type=wpx_options');
		add_submenu_page( 'wpx', 'Groups', 'Groups', 'manage_options', 'edit-tags.php?taxonomy=wpx_groups');
	}

	/**
	 * Control Highlight for WPX Menu Items
	 *
	 * @since 1.0
	*/
	public function set_menu_classes($parent_file) {

		global $current_screen;
		global $submenu_file;

		if ($current_screen->taxonomy == 'wpx_groups') $parent_file = 'wpx';
		if ($current_screen->action == 'add' && $current_screen->post_type == 'wpx_fields') {
			$parent_file = 'wpx';
			$submenu_file = 'edit.php?post_type=wpx_fields';
		}
		if ($current_screen->action == 'add' && $current_screen->post_type == 'wpx_taxonomy') {
			$parent_file = 'wpx';
			$submenu_file = 'edit.php?post_type=wpx_taxonomy';

		}
		if ($current_screen->action == 'add' && $current_screen->post_type == 'wpx_types') {
			$parent_file = 'wpx';
			$submenu_file = 'edit.php?post_type=wpx_types';
		}
		if ($current_screen->action == 'add' && $current_screen->post_type == 'wpx_options') {
			$parent_file = 'wpx';
			$submenu_file = 'edit.php?post_type=wpx_options';
		}

		$action_state = isset($_GET['action']) ? $_GET['action'] : false;
		if ($action_state == 'edit' && $current_screen->post_type == 'wpx_fields') $parent_file = 'wpx';
		if ($action_state  == 'edit' && $current_screen->post_type == 'wpx_taxonomy') $parent_file = 'wpx';
		if ($action_state  == 'edit' && $current_screen->post_type == 'wpx_types') $parent_file = 'wpx';
		if ($action_state  == 'edit' && $current_screen->post_type == 'wpx_options') $parent_file = 'wpx';

		return $parent_file;
	}

	/**
	 * Register Settings for WPX Settings
	 *
	 * @since 1.0
	*/
	public function wpx_admin_page_init() {

		register_setting(
            'wpx_admin_settings', // Option group
            'wpx_admin_options' // Option name
        );        

		// styles section
		add_settings_section( 
			'styles', 
			'Styling Hooks', 
			null, 
			'wpx_admin_page' 
		);

		add_settings_field( 
			'styles_login', 
			'Style the Login Screen', 
			array($this,'render_styles_login'), 
			'wpx_admin_page', 
			'styles'
		);
		
		add_settings_field( 
			'styles_dashboard', 
			'Style the Dashboard', 
			array($this,'render_styles_dashboard'), 
			'wpx_admin_page', 
			'styles'
		);

		// theme support
		add_settings_section( 
			'theme_support', 
			'Theme Support', 
			null, 
			'wpx_admin_page' 
		);

		add_settings_field( 
			'html5', 
			'HTML5 Support?', 
			array($this,'render_theme_support_html5'), 
			'wpx_admin_page', 
			'theme_support'
		);

		// miscellaneous settings
		add_settings_section( 
			'misc', 
			'Miscellaneous Settings', 
			null, 
			'wpx_admin_page' 
		);

		add_settings_field( 
			'recent_comments_styles', 
			'Disable Recent Comments Styles?', 
			array($this,'recent_comments_styles_off'), 
			'wpx_admin_page', 
			'misc'
		);

		add_settings_field( 
			'excerpt_metabox_on', 
			'Enable the excerpt metabox?', 
			array($this,'excerpt_metabox_on'), 
			'wpx_admin_page', 
			'misc'
		);

		add_settings_field( 
			'logo_homepage', 
			'Link Login logo to homepage?', 
			array($this,'link_styles_logo'), 
			'wpx_admin_page', 
			'misc'
		);
		
		add_settings_field( 
			'right_now_widget_extended', 
			'Extend the Right Now widget?', 
			array($this,'right_now_widget_extended'), 
			'wpx_admin_page', 
			'misc'
		);

        // wpx uninstall
        add_settings_section(
            'wpx_uninstall_section',
            'Uninstall',
            array( $this, 'uninstall_section_info' ),
            'wpx_admin_page'
        );  

        add_settings_field(
            'wpx_uninstall', 
            '<label class="wpx_uninstall">Type "uninstall" to confirm:</label>',
            array( $this, 'uninstall_field' ),
            'wpx_admin_page',
            'wpx_uninstall_section'
        );   

	}

	/**
	 * WPX Settings Page
	 *
	 * @since 1.0
	*/
	public function wpx_admin_page() { 
		
		$this->wpx_admin_options = get_option( 'wpx_admin_options' );
		
		?>
		<div class="wrap">
			
			<h2 class="wpx-header">WordPress Extend</h2>
			
			<form method="post" action="options.php">

			<?php settings_fields( 'wpx_admin_settings' ); ?>

			<p>WP Extend (WPX) is a framework that makes it easier to use WordPress as a CMS. It tries to bridge the gap between WordPress' native ability to make custom post types, taxonomies, options pages, and metaboxes and the Dashboard, by providing a GUI interface for developers to work with outside of templates.</p>

			<h3>Documentation</h3>
			<p>You can find documentation for the plugin here: <a href="https://github.com/alkah3st/wpx/wiki">https://github.com/alkah3st/wpx/wiki</a>

            <?php do_settings_sections( 'wpx_admin_page' ); ?>
			<?php submit_button('Save Settings'); ?>

            </form>

		</div>
	<?php
	}

}