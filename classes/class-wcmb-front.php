<?php 
defined( 'WCMB_DIR_PATH' ) or die( 'No script kiddies please!' );

class WCMB_FRONT{
	
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
	public function __construct() {
		$this->version 	= WCMB_VERSION;
		add_action( 'wp_enqueue_scripts', array( $this, 'wcmb_enqueue_scripts' ) );

		add_action('woocommerce_order_details_after_order_table', array( $this,'wcmb_generate_invoice_from_new_order'), 10, 1 );
		/*woocommerce_order_details_before_order_table*/
		
		// Your additional action button
		add_filter( 'woocommerce_my_account_my_orders_actions', array( $this,'wcmb_add_my_account_my_orders_custom_action'), 10, 2 );

		// Jquery script
		add_action( 'woocommerce_after_account_orders', array( $this,'action_after_account_orders_js'));

	}
	public function wcmb_generate_invoice_from_new_order( $order_id  ) {
		
	    if ( ! $order_id ) return;
	    // access tocken get
	    $access_token = get_option('wcmb_moneybird_access_token');

	    if ( ! $access_token ) return;
	        
	    if( ! get_post_meta( $order_id, 'wcmb_moneybird_invoice_generated', true ) ) {
	        // Get an instance of the WC_Order object
	        $headers = array(
				'Content-Type'  => 'application/json',
				'Authorization' => 'Bearer ' . $access_token,
	        );
	        // administration_id Get
			$administrationsUrl   = "https://moneybird.com/api/v2/administrations.json";
			$getAdministraterData = wp_remote_get( $administrationsUrl, array('headers' => $headers,));
			$administrater        = json_decode(wp_remote_retrieve_body($getAdministraterData));
	        
	        $addministrater_id = $administrater[0]->id;

	        // User Data Get
			$order           = wc_get_order( $order_id );
			$userEmail       = $order->get_billing_email();
			$userFirstName   = $order->get_billing_first_name();
			$userLastName    = $order->get_billing_last_name();
			$userCompanyName = $order->get_billing_company();
			$userAddress     = $order->get_billing_address_1();
	        //$email = $order->get_billing_email();

	        
	        // email verify
	        $verifyEmailUrl = "https://moneybird.com/api/v2/".$addministrater_id."/contacts.json?query=".$userEmail."";
	        $verifyEmailData = wp_remote_get( $verifyEmailUrl, array('headers' => $headers,));
	        $verifyEmail = json_decode(wp_remote_retrieve_body($verifyEmailData));

	        if(!empty($verifyEmail)){
	            $contactId = $verifyEmail[0]->id;
	        } else {
	            $contactData = json_encode([
	                'contact'=>[
	                    'company_name'        => $userCompanyName,
	                    'firstname'           => $userFirstName, 
	                    'lastname'            => $userLastName,
	                    'address1'            => $userAddress,
	                    'email'               => $userEmail
	                ],
	            ]);
	            
				$createcontactUrl  = "https://moneybird.com/api/v2/".$addministrater_id."/contacts.json";
				$createContactData = wp_remote_post( $createcontactUrl, array(
	                'method'      => 'POST',
	                'timeout'     => 120,
	                'redirection' => 5,
	                'httpversion' => '1.1',
	                'blocking'    => true,
	                'headers'     => $headers,
	                'body'        => $contactData,
	                'cookies'     => array()
	                )
	            );
	            if ( is_wp_error( $createContactData ) ) {
	                $error_message = $createContactData->get_error_message();
	                echo "Something went wrong: $error_message";
	            } else {
	                $contactDetail = json_decode(wp_remote_retrieve_body($createContactData));
	                if ( is_wp_error( $contactDetail ) ) {
	                	$error_message = $contactDetail->get_error_message();
	                	echo "Something went wrong: $error_message";
	                }else{
	                	$contactId = $contactDetail->id;
	                }
	            }
	        }
	        // create array of user post data in invoice
	        $postData = array();
	        $postData['sales_invoice']['contact_id'] = $contactId;
	        if(get_option('wcmb_moneybird_document_style_id')){
	            $postData['sales_invoice']['document_style_id'] = get_option('wcmb_moneybird_document_style_id');
	        }
	        if(get_option('wcmb_moneybird_workflow_id')){
	            $postData['sales_invoice']['workflow_id'] = get_option('wcmb_moneybird_workflow_id');
	        }
	        // Loop through order items
	        $oProducts = array();
	        $i = 0;
	        foreach ( $order->get_items() as $item_id => $item ) {
				$product                      = $item->get_product();
				$oProducts[$i]['description'] = $product->get_name();
				$oProducts[$i]['price']       = $product->get_price();
				$oProducts[$i]['amount']      = $item->get_quantity();
	            $i++;
	        }
	        $postData['sales_invoice']['details_attributes'] = $oProducts;  
	        $postDataJson = json_encode($postData);
	        
	        // sales invoice create
			$createInvoiceUrl  = "https://moneybird.com/api/v2/".$addministrater_id."/sales_invoices";
			$createInvoiceData = wp_remote_post( $createInvoiceUrl, array(
	            'method'      => 'POST',
	            'timeout'     => 120,
	            'redirection' => 5,
	            'httpversion' => '1.1',
	            'blocking'    => true,
	            'headers'     => $headers,
	            'body'        => $postDataJson,
	            'cookies'     => array()
	            )
	        );
	        
	        if ( is_wp_error( $createInvoiceData ) ) {
	            $error_message = $createInvoiceData->get_error_message();
	            echo "Something went wrong:" . $error_message;
	        } else {
	        	// ensure we don't have any previous output
				if( headers_sent() ){
					$invoiceDataBaseURL = get_post_meta( $order->get_id(), 'wcmb_moneybird_invoice_url', true );
					if( $invoiceDataBaseURL ) {
						echo '<div class="ypDownloadInvoiceButtonSection"><h2>'.__( 'Download Invoice', 'wcmb' ).'</h2><a download href="'.$invoiceDataBaseURL.'" class="downloadInvoiceButton">'.__( 'Download Invoice', 'wcmb' ).'</a></div>';
					}
				} else {
					header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
					header('Pragma: public');
					header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
					header('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT');

		            $invoiceData = json_decode(wp_remote_retrieve_body($createInvoiceData));
		           
		            if ( is_wp_error( $invoiceData ) ) {
	                	$error_message = $invoiceData->get_error_message();
	                	echo "Something went wrong: $error_message";
	                }else {
			            if($invoiceData){
			                $invoice_id = $invoiceData->id;

			                $dInvoiceUrl = "https://moneybird.com/api/v2/".$addministrater_id."/sales_invoices/".$invoice_id."/download_pdf.json?";
			               
					        $getdinvoiceData = wp_remote_get( $dInvoiceUrl, array('headers' => $headers,));
					       
					        
					        $upload_dir   = wp_upload_dir();
					       	
					       	$invoiceDir = $upload_dir['basedir'].'/moneybird-invoice';
					        if ( ! file_exists( $invoiceDir ) ) {
						        wp_mkdir_p( $invoiceDir );
						    }

					       	$path = $upload_dir['basedir'].'/moneybird-invoice/'.$invoice_id.'.pdf';
					       	$invoiceDownloadUrl = $upload_dir['baseurl'].'/moneybird-invoice/'.$invoice_id.'.pdf';
							$content = wp_remote_retrieve_body($getdinvoiceData);
							// save PDF buffer
							file_put_contents($path, $content);
							
			                echo '<div class="ypDownloadInvoiceButtonSection"><h2>'.__( 'Download Invoice', 'wcmb' ).'</h2><a download href="'.$invoiceDownloadUrl.'" class="downloadInvoiceButton">'.__( 'Download Invoice', 'wcmb' ).'</a></div>';
			            }
	                }
			        $createdI_note = __("Created Invoice in Moneybird.", 'wcmb');
					$errorI_note = __('something wrong with moneybird API' , 'wcmb');
			        if( $invoiceDownloadUrl ) {
						$order->add_order_note( $createdI_note );
			        } else {
						$order->add_order_note( $errorI_note );
			        } 
			        // Flag the action as done (to avoid repetitions on reload for example)
			       	$order->update_meta_data( 'wcmb_moneybird_invoice_generated', true );
			       	$order->update_meta_data( 'wcmb_moneybird_invoice_url', $invoiceDownloadUrl );
				}

	        }
	       	$order->save();
	    } else {
	        $invoice_url = get_post_meta( $order_id, 'wcmb_moneybird_invoice_url', true );
	        echo '<div class="ypDownloadInvoiceButtonSection">
	        		<h2>'.__( 'Download Invoice', 'wcmb' ).'</h2> 
	        		<a download  href="'.$invoice_url.'" class="downloadInvoiceButton">'.__( 'Download Invoice', 'wcmb' ).'</a>
	        	</div>';
	    }
	}
	public function wcmb_add_my_account_my_orders_custom_action( $actions, $order  ) {
		// Add the note
		$order_id = $order->get_id();
	    $action_slug = 'moneybird-invoice';
	    $invoice_url = get_post_meta( $order_id, 'wcmb_moneybird_invoice_url', true );
	    
	    if($invoice_url){
	    	$actions[$action_slug] = array(
		        'url'  => $invoice_url,
		        'name' => 'Download Invoice',
		        'class' => 'custome-button'
		    );
	    }
	    return $actions;	
	    
	}
	public function action_after_account_orders_js() {
	    $action_slug = 'moneybird-invoice';
	    ?>
	    <script>
		    jQuery(function($){
		        $('a.<?php echo $action_slug; ?>').each( function(){
		             $(this).attr('target','_blank');
		        })
		    });
	    </script>
	    <?php
	}
	/**
	 * Register any CSS and JS used by the plugin.
	 * @since    2.1.2
	 * @access 	 public
	 * @param    string $hook Used for determining which page(s) to load our scripts.
	 */
	public function wcmb_enqueue_scripts( $hook ) {
		wp_enqueue_style( 'wcmb-moneybird-custom', WCMB_ASSETS_URL . 'css/wcmb-moneybird-layout.css' , array(), $this->version, 'all' );
	}
}
