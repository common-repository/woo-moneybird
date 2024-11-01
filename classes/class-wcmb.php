<?php
defined( 'WCMB_DIR_PATH' ) or die( 'No script kiddies please!' );

class WCMB {
	
	/**
	 * The unique identifier of this plugin.plugin
	 *
	 * @since    2.1.2
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;
	
	/**
	 * The current version of the plugin.
	 *
	 * @since    2.1.2
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;
	
	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since    2.1.2
	 */
	function __construct() {
		$this->plugin_name 	= 'wcmb';
		$this->version 		= WCMB_VERSION;
		$this->wcmb_load_dependencies();
		$this->set_locale();
		$this->wcmb_register_admin_hooks();
		$this->register_front_hooks();	

		/* Plugin activation hook for add default settings while activating plugin*/
		register_activation_hook( WCMB_FILE, array($this, 'pluginActivation'));
		
		/* Plugin deactivation hook for do somthing while deactivating plugin. */
		register_deactivation_hook( WCMB_FILE, array($this, 'pluginDeactivation'));

		/* remove Column invoice */
		// add_action( 'removeColumnInvoice', array( $this, 'removeColumnInvoice' ) );
		// add_filter( 'manage_edit-shop_order_columns', array( $this,'removeColumnInvoice' ));
	}
	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    2.1.2
	 * @access   private
	 */
	private function wcmb_load_dependencies() {

		require_once WCMB_DIR_PATH . 'classes/class-wcmb-admin.php';
		require_once WCMB_DIR_PATH . 'classes/class-wcmb-front.php';
		require_once WCMB_DIR_PATH . 'classes/class-wcmb-language.php';

	}

	/**
	 * Plugin activation callback function
	 */
	public function pluginActivation(){
		
	}
	
	/**
	 * Plugin deactivation callback function
	 */
	public function pluginDeactivation(){
		/* Remove all fields Data */
		delete_option('wcmb_moneybird_client_id');
	    delete_option('wcmb_moneybird_secret_id');
	    delete_option('wcmb_moneybird_access_token');
	    delete_option('wcmb_moneybird_selected_administration');
	    delete_option('wcmb_moneybird_document_style_id');
	    delete_option('wcmb_moneybird_workflow_id');

		/*remove invoice column in backend order page*/
		add_filter( 'manage_edit-shop_order_columns', array( $this,'removeColumnInvoice' ));
	}
	/**
	 * Remove Invoice column in backend order page
	 */
	public function removeColumnInvoice( $columns ){
		unset($columns['wcmb_invoice']);
	    return $columns;
	}

	/**
	 * Register all of the hooks related to the dashboard functionality
	 * of the plugin.
	 *
	 * @since    2.1.2
	 * @access   private
	 */
	private function wcmb_register_admin_hooks() {
		$wcmb_admin = new WCMB_ADMIN( $this->plugin_name, $this->version );		
	}

	/**
	 * Register all of the hooks related to the front-end functionality
	 * of the plugin.
	 *
	 * @since    2.1.2
	 * @access   private
	 */
	private function register_front_hooks() {
		$wcmb_front = new WCMB_FRONT();
	}


	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the BSR_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    2.1.2
	 * @access   private
	 */
	private function set_locale() {
		$wcmb_lamguages = new WCMB_LOAD_TEXT_DOMAIN();
		$wcmb_lamguages->set_domain( $this->plugin_name );
	}
}
