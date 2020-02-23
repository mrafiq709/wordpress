<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    My_Basic_Plugin
 * @subpackage My_Basic_Plugin/includes
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
 * @package    My_Basic_Plugin
 * @subpackage My_Basic_Plugin/includes
 * @author     Your Name <email@example.com>
 */
class My_Basic_Plugin
{

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      My_Basic_Plugin_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $my_basic_plugin    The string used to uniquely identify this plugin.
	 */
	protected $my_basic_plugin;

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
	public function __construct()
	{
		if (defined('MY_BASIC_PLUGIN_VERSION')) {
			$this->version = MY_BASIC_PLUGIN_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->my_basic_plugin = 'my_basic_plugin';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();

		// Call all filters
		//$this->call_filters();

		// Call all actions
		$this->call_actions();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - My_Basic_Plugin_Loader. Orchestrates the hooks of the plugin.
	 * - My_Basic_Plugin_i18n. Defines internationalization functionality.
	 * - My_Basic_Plugin_Admin. Defines all hooks for the admin area.
	 * - My_Basic_Plugin_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-my-basic-plugin-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-my-basic-plugin-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-my-basic-plugin-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-my-basic-plugin-public.php';

		$this->loader = new My_Basic_Plugin_Loader();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the My_Basic_Plugin_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new My_Basic_Plugin_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{

		$plugin_admin = new My_Basic_Plugin_Admin($this->get_my_basic_plugin(), $this->get_version());

		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
		$this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{

		$plugin_public = new My_Basic_Plugin_Public($this->get_my_basic_plugin(), $this->get_version());

		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
		$this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_my_basic_plugin()
	{
		return $this->my_basic_plugin;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    My_Basic_Plugin_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}

	// invocation of filter functions
	public function call_filters()
	{
		add_filter('the_content', array($this, 'add_some_text_to_content'));
	}

	// invocation of filter functions
	public function call_actions()
	{
		//add_action('init', array($this, 'add_some_text'));

		// Action for 5 post per page
		add_action('pre_get_posts', array($this, 'my_plugin_post_per_page'));

		add_action('get_sidebar', array($this, 'get_all_tag'));

		add_action('pre_get_posts', array($this, 'print_all_tag_post'));

		//add_action('wp_meta', array($this, 'your_function'));
	}

	// Testing Filter is working or not
	public function add_some_text_to_content($content)
	{
		$text = 'I am added to the content';
		return $content . $text;
	}

	// Tesing Action is working or not
	public function add_some_text()
	{
		// If you didn't see the output, then inspect element. Now you must see.
		var_dump('ok');
	}

	/**
	 * Set 5 Post per page
	 */
	public function my_plugin_post_per_page($query)
	{
		if ($query->is_home() && $query->is_main_query()) {
			$query->set('posts_per_page', 5);
		}
	}

	public function get_all_tag()
	{
		$tags = get_tags(array(
			'hide_empty' => false
		));
		//var_dump($tags);
		//echo "<!-- DEBUG\n" . print_r($tags, true) . "\n-->";
		echo '<ul>';
		foreach ($tags as $tag) {
			echo '<a href="http://localhost/wordpress/index.php/tag/' . $tag->name . '/" onclick="clickme();"><li>' . $tag->name . '</li></a>';
		}
		echo '</ul>';

		echo '<script>
				function clickme(){
					alert("ok");
				} 
			</script>';
	}

	public function print_all_tag_post($query)
	{

		if ($query->is_tag('favourite')) {
			$query->set('posts_per_page', 5);
			return;
		}

	}
}
