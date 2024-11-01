<?php 
defined( 'WCMB_DIR_PATH' ) or die( 'No script kiddies please!' );

class WCMB_ADMIN{
	
	/**
	 * The ID of this plugin.
	 *
	 * @since    2.1.2
	 * @access   private
	 * @var      string    $textDomain   The text domain of this plugin.
	 */
	private $textDomain;
	
	/**
	 * The version of this plugin.
	 *
	 * @since    2.1.2
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;	
	
	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    2.1.2
	 * @var      string    $better_search_replace       The name of this plugin.
	 * @var      string    $version    The version of this plugin.
	 */
	public function __construct( $textDomain, $version ) {
		$this->textDomain = $textDomain;
		$this->version = $version;
		
		add_action( 'admin_enqueue_scripts', array( $this, 'wcmb_enqueue_scripts' ) );
		add_action( 'admin_menu', array( $this, 'wcmb_moneybird_api_setting_menu_page' ) );
		
		// save moneybird Data
		add_action('wp_ajax_get_wcmb_clientid_secretid_data', array( $this,'wcmb_get_clientid_secretid_data_callBack'));
		add_action('wp_ajax_nopriv_get_wcmb_clientid_secretid_data', array( $this,'wcmb_get_clientid_secretid_data_callBack'));
		// reset moneybird Data
		add_action('wp_ajax_wcmb_reset_moneybird_api_data',  array( $this,'wcmb_reset_moneybird_api_data_callback'));
		add_action('wp_ajax_nopriv_wcmb_reset_moneybird_api_data',  array( $this,'wcmb_reset_moneybird_api_data_callback'));

		//  callback2 URL for  access tocken  
		add_action( 'admin_init', array( $this,'wcmb_save_data_moneybird_data' ));

		// custom column add invoice -> view to invoice
		add_filter( 'manage_edit-shop_order_columns', array( $this,'wcmb_moneybird_new_order_column' ));

		// new order custome column content
		add_action( 'manage_shop_order_posts_custom_column', array( $this,'wcmb_moneybird_new_order_column_content'), 2 );
	}
	/*
	*	Backend Sidebar Menu Callback functions
	*/
	public function wcmb_moneybird_api_setting_menu_page() {
	    add_menu_page( __( 'Moneybird API Setting', 'wcmb' ), __( 'Moneybird API', 'wcmb' ), 'manage_options','moneybird-settings-page',array($this,'wcmb_moneybird_api_settings_callback'), 'dashicons-admin-generic', 80); 
	    add_submenu_page( 'moneybird-settings-page', __( 'Moneybird API Setting', 'wcmb' ), __('General Setting' , 'wcmb'),'manage_options', 'moneybird-general-settings-page',array( $this,'wcmb_moneybird_api_general_callback'));
	    add_submenu_page( 'moneybird-settings-page', __( 'Moneybird API Setting', 'wcmb' ), __('Support' , 'wcmb'),'manage_options', 'moneybird-support-page',array( $this,'wcmb_moneybird_support_callback'));
	}
	/*
	*	Moneybird API Menu Callback function
	*/
	public function wcmb_moneybird_api_settings_callback(){
    	require_once WCMB_DIR_PATH . 'templates/wcmb-admin.php';
	}
	/*
	*	General Setting Menu Callback function
	*/
	public function wcmb_moneybird_api_general_callback(){
    	require_once WCMB_DIR_PATH . 'templates/wcmb-general.php';
	}
	/*
	*	Support Menu Callback function
	*/
	public function wcmb_moneybird_support_callback(){
		$lang=get_bloginfo("language");
		
		if($lang == 'de-DE'){
    		require_once WCMB_DIR_PATH . 'templates/wcmb-support-dutch.php';
		}else{
    		require_once WCMB_DIR_PATH . 'templates/wcmb-support.php';
		}
	}
	
	/**
	 * Register any CSS and JS used by the plugin.
	 * @since    2.1.2
	 * @access 	 public
	 * @param    string $hook Used for determining which page(s) to load our scripts.
	 */
	public function wcmb_enqueue_scripts( $hook ) {
		wp_enqueue_style( 'wcmb-moneybird-custom', WCMB_ASSETS_URL . 'css/wcmb-moneybird-layout.css' , array(), $this->version, 'all' );

	    wp_enqueue_script( "wcmb-moneybird-custom", WCMB_ASSETS_URL . 'js/wcmb-admin-script.js', array( 'jquery' ) );
	    // make the ajaxurl var available to the above script
	    wp_localize_script( 'wcmb-moneybird-custom', 'wcmb_moneybird_ajax_script', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) ); 
		
	}
	/*
	*	Reset moneybird data callback function
	*/
	public function wcmb_reset_moneybird_api_data_callback(){
	    delete_option('wcmb_moneybird_client_id');
	    delete_option('wcmb_moneybird_secret_id');
	    delete_option('wcmb_moneybird_access_token');
	    delete_option('wcmb_moneybird_selected_administration');
	    delete_option('wcmb_moneybird_document_style_id');
	    delete_option('wcmb_moneybird_workflow_id');
	    
	    $redirectresult = array();
	    $redirekUrl = admin_url('admin.php?page=moneybird-settings-page');
	    $redirectresult['suuccess'] = 1;
	    $redirectresult['resetUrl'] = $redirekUrl;
	    $resultJson = json_encode($redirectresult);
	    echo $resultJson;
	    exit();
	}

	/*
	*	Moneybird Authorize URL function
	*/
    Public function wcmb_authorize_url_create($client_id, $callback, $scopes = array()){
	    $pattern = "https://moneybird.com/oauth/authorize?client_id=%s&redirect_uri=%s&scope=%s&response_type=code";
	    return sprintf($pattern, $client_id,urlencode($callback),implode("+", $scopes));
	}

	/*
	*	Moneybird ClientId & secretId store in Database
	*/
	Public function wcmb_get_clientid_secretid_data_callBack(){
	   
	    $wcmb_nonce   = sanitize_text_field($_POST['wcmb_nonce']);
	    
	    if ( ! wp_verify_nonce( $wcmb_nonce, 'wcmb-moneybird-data' ) ) {
	        die( __( 'Security check', 'wcmb' ) ); 
	    } else {

	        $clientId   = sanitize_text_field($_POST['clientId']);
	        $secretId   = sanitize_text_field($_POST['secretId']);

	        update_option('wcmb_moneybird_client_id', $clientId);
	        update_option('wcmb_moneybird_secret_id', $secretId);

	        $result = array();
	        // access url generate.....
	        $client_id = $clientId;
	        $callback = admin_url('admin.php');
	        $scopes = array("sales_invoices", "documents");

	        $getAuthorizeUrl = $this->wcmb_authorize_url_create($client_id, $callback, $scopes);
	        
	        $result['accesssUrl'] = $getAuthorizeUrl;
	        $resultJson = json_encode($result);
	        echo $resultJson;
	        exit();
	    }

	}
	/*
	*	Moneybird Access token get function
	*/
	Public function wcmb_get_access_code($client_id, $callback, $client_secret, $request_code) {
        $AccesstokenUrl = "https://moneybird.com/oauth/token";
        $getAccessrokenData = wp_remote_post( $AccesstokenUrl, array(
            'method'      => 'POST',
            'timeout'     => 120,
            'redirection' => 5,
            'httpversion' => '1.1',
            'blocking'    => true,
            'headers'     => array(),
            'body'        => [
                'client_id'     => $client_id,
                'redirect_uri'  => $callback,
                'client_secret' => $client_secret,
                'code'          => $request_code,
                'grant_type'    => 'authorization_code'
            ],
            'cookies'     => array()
            )
        );
        if ( is_wp_error( $getAccessrokenData ) ) {
            $error_message = urlencode($getAccessrokenData->get_error_message());
            wp_redirect( admin_url('admin.php?page=moneybird-settings-page') .'&error_description='. $error_message );
            die();
        } else {
            $access_request = json_decode(wp_remote_retrieve_body($getAccessrokenData));
            return $access_request;
            die();
        }
    }

	/*
	*	Moneybird callback2 URL for  access tocken
	*/
	Public function wcmb_save_data_moneybird_data() {
		global $wcmb_plugin_callback_url;
	    $wcmb_plugin_callback_url = admin_url('admin.php?page=moneybird-settings-page');

	    // user click deny in moneybird
	    if(isset($_GET['error']) == 'access_denied'){
	        $error_message = urlencode('Something rong. Please reset and try again.');
	        wp_redirect( $wcmb_plugin_callback_url .'&error_description='. $error_message );
	        die();
	    }	
	    if (isset($_GET['code'])) {
	    	
	        $access_code = $_GET['code'];
	        $getClientId = get_option('wcmb_moneybird_client_id');
	        $getSecreteId = get_option('wcmb_moneybird_secret_id');
	        
	        $access_request = $this->wcmb_get_access_code($getClientId, $wcmb_plugin_callback_url, $getSecreteId, $access_code);
	       	
	        if($access_request){
	            if($access_request->access_token){

	                $accessToken = $access_request->access_token;   
	                $headers = array(
	                    'Content-Type' => 'application/json',
	                    'Authorization'=> 'Bearer '.$accessToken
	                );
	                
	                $administrationsUrl = "https://moneybird.com/api/v2/administrations.json";
	                $getAdministraterData = wp_remote_get( $administrationsUrl, array(
	                    'timeout'     => 120,
	                    'httpversion' => '1.1',
	                    'headers'     => $headers,
	                    )
	                );
		               
	                if ( is_wp_error( $getAdministraterData ) ) {
	                    $error_message = urlencode($getAdministraterData->get_error_message());
	                    wp_redirect( $wcmb_plugin_callback_url .'&error_description='. $error_message );
	                    die();
	                } else {
	                    $administrater = json_decode(wp_remote_retrieve_body($getAdministraterData));
	                    
	                    if($administrater->error){
	                        $error_message = urlencode($administrater->error.". Please Reset And try again");
	                        wp_redirect( $wcmb_plugin_callback_url .'&error_description='. $error_message);
	                        die();
	                    }
	                    update_option( 'wcmb_moneybird_selected_administration', $administrater);
	                    // save document style id
	                    $wcmb_moneybird_selected_administration = get_option( 'wcmb_moneybird_selected_administration' );
	                    $administratorId = $wcmb_moneybird_selected_administration[0]->id;
	                    $DocumentstyleIdUrl = "https://moneybird.com/api/v2/".$administratorId."/document_styles.json?";
	                    $getDocumentstyleIdData = wp_remote_get( $DocumentstyleIdUrl, array(
	                        'headers'     => $headers,
	                        )
	                    );
	                    $documentStyleIdData = json_decode(wp_remote_retrieve_body($getDocumentstyleIdData));
	                    $getDocumentStyleId = $documentStyleIdData[0]->id;
	                    update_option('wcmb_moneybird_document_style_id',$getDocumentStyleId);
	                    
	                    // save workflow Id
	                    $workflowIdURL = "https://moneybird.com/api/v2/".$administratorId."/workflows.json?";
	                    $getworkflowIdData = wp_remote_get( $workflowIdURL, array(
	                        'headers'     => $headers,
	                        )
	                    );
	                    $workflowIdData = json_decode(wp_remote_retrieve_body($getworkflowIdData));
	                    $getworkflowId = $workflowIdData[1]->id;
	                    update_option('wcmb_moneybird_workflow_id',$getworkflowId);
	                    update_option( 'wcmb_moneybird_access_token', $access_request->access_token);
	                    wp_redirect( $wcmb_plugin_callback_url );
	                }
	                
	            } else if( $access_request->error === 'invalid_client'){
	                wp_redirect( $wcmb_plugin_callback_url .'&error_description='. urlencode($access_request->error_description) );
	                die();
	            } else {
	                wp_redirect( $wcmb_plugin_callback_url .'&error_description='. urlencode($access_request->error_description) );
	                die();
	            }
	        } else {
	            wp_redirect( $wcmb_plugin_callback_url .'&error_description='.urlencode('some issues') );
	            die();
	        }
	    }
	}
	/*
	* Custom column add invoice -> view to invoice
	*/
	Public function wcmb_moneybird_new_order_column( $columns ) {
	    $columns['wcmb_invoice'] = __( 'Invoice','wcmb' );
	    return $columns;
	}
	/*
	* View Button add in order custome column content
	*/
	Public function wcmb_moneybird_new_order_column_content($column_name ) {
	    global $post;
	    if ( $column_name === 'wcmb_invoice' ) {
	        $invoice_url = get_post_meta( $post->ID, 'wcmb_moneybird_invoice_url', true );
	        if($invoice_url){
	            echo '<a href="'.$invoice_url.'" id="wcmb_invoice_url" class="button button-primary" target="_blank" >'.__('View', 'wcmb' ).'</a>';
	        }
	    }
	}
}
