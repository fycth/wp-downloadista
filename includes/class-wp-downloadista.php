<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://serhiienko.se
 * @since      1.0.0
 *
 * @package    Wp_Downloadista
 * @subpackage Wp_Downloadista/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Wp_Downloadista
 * @subpackage Wp_Downloadista/includes
 * @author     Andrii Serhiienko <andrii@oslikas.com>
 */
class Wp_Downloadista {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Wp_Downloadista_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'WP_DOWNLOADISTA_VERSION' ) ) {
			$this->version = WP_DOWNLOADISTA_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wp-downloadista';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		$this->set_default_options();

        add_action('init', 'downloadista_shortcodes_init' );
		add_action('init', 'downloadista_rewrite_tag_rule' );
        add_action('wp_enqueue_scripts', 'downloadista_enqueue_scripts' );
        add_action('admin_menu', 'downloadista_add_admin_menu' );
        add_action('admin_init', 'downloadista_settings_init' );
		add_action('wp', 'downloadista_handle_download_request');
		add_filter('manage_media_columns', 'downloadista_add_attachment_id_column' );
		add_action('manage_media_custom_column', 'downloadista_show_attachment_id_column', 10, 2);
	}

    function set_default_options() {
        $default_options = array(
            'button_background_color' => '#4CAF50',
            'button_text_color' => '#ffffff',
            'button_text' => 'Download Now',
            'pane_background_color' => '#faeb10',
            'pane_text_color' => '#000000',
            'pane_border_size' => '1',
            'counter_pre_text' => 'Download will start in ',
            'counter_post_text' => ' seconds',
            'counter_value' => '15',
            'url_text' => 'If download does not start, please, click ',
            'url_name' => 'here',
	        'link_expiration' => '5',
	        'link_expired_message' => 'This download link has expired, please refresh the page.',
            'icon_color' => '#aaaaaa',
        );

        if (!get_option('downloadista_options')) {
            add_option('downloadista_options', $default_options);
        }
    }

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Wp_Downloadista_Loader. Orchestrates the hooks of the plugin.
	 * - Wp_Downloadista_i18n. Defines internationalization functionality.
	 * - Wp_Downloadista_Admin. Defines all hooks for the admin area.
	 * - Wp_Downloadista_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-downloadista-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-wp-downloadista-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-wp-downloadista-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-wp-downloadista-public.php';

		$this->loader = new Wp_Downloadista_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Wp_Downloadista_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Wp_Downloadista_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Wp_Downloadista_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Wp_Downloadista_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Wp_Downloadista_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}


}
