
<div class="wrap">
    <h1><?php _e('MoneyBird API Settings', 'wcmb' ); ?></h1>
    <?php
        if(isset($_GET['error_description'])){
        ?>
            <div class="error">
                <p><strong><?php echo $_GET['error_description'];?></strong></p></div>
            </div>
        <?php
        }
        // disable column variable
        $clientIdDisable = "";
        if(get_option('wcmb_moneybird_client_id')){
            $clientIdDisable = 'disabled = "disabled"';
        }
        $secretIdDisable = "";
        if(get_option('wcmb_moneybird_secret_id')){
            $secretIdDisable = 'disabled = "disabled"';
        }
    ?>

    <form method="post" id="wcmb_mbform">
        <table class="form-table">
            <tbody>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('Client ID', 'wcmb' ); ?></label>
                    </th>
                    <td>
                        <input name="wcmb_clientid" id="wcmb_clientid" class="wcmb_clientIdWidth"  type="text"  placeholder="<?php _e('Enter the moneybird client ID', 'wcmb' ); ?>" value="<?php echo get_option('wcmb_moneybird_client_id');?>" <?php echo $clientIdDisable;?> required>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">
                        <label><?php _e('Client Secret ID', 'wcmb' ); ?></label>
                    </th>
                    <td>
                        <input name="wcmb_clientSecret" id="wcmb_clientSecret" class="wcmb_clientSecretWidth" type="text"  placeholder="<?php _e('Enter the moneybird client secret ID', 'wcmb' ); ?>" value="<?php echo get_option('wcmb_moneybird_secret_id');?>" <?php echo $secretIdDisable;?> required>
                    </td>
                </tr>
                <?php 
                    if(get_option('wcmb_moneybird_access_token')){
                ?>
                <tr valign="top" class="access_tocken_row">
                    <th scope="row">
                        <label><?php _e('Access Token Key', 'wcmb' ); ?></label>
                    </th>
                    <td>
                        <input name="wcmb_token" id="wcmb_token" class="wcmb_reset wcmb_resetWidth" type="text" value="<?php echo get_option( 'wcmb_moneybird_access_token' ); ?>" disabled="disabled">
                        <p class="description" id=""><?php _e('<b>How to Create API token : </b> You can be done by logging in to your Moneybird account and visit the page  ', 'wcmb' ); ?> <a href="https://moneybird.com/user/applications/new" target="_blank"><?php _e('https://moneybird.com/user/applications/new ', 'wcmb' ); ?></a>.</p>
                        <p class="description" id=""><?php _e('<b>Note: </b> Go to Currency options and Please select your Euro (€) Currency. ', 'wcmb' ); echo  admin_url('admin.php')?></p>
                    </td>
                </tr>
                <?php $wcmb_moneybird_selected_administration = get_option( 'wcmb_moneybird_selected_administration' ); ?>
                <?php if($wcmb_moneybird_selected_administration){ ?>
                <tr valign="top" class="access_tocken_row">
                    <th scope="row">
                        <label><?php _e('Selected Administrator', 'wcmb' ); ?></label>
                    </th>
                    <td>
                        <p class="description" id="wcmb_administrator_id">
                            <b><?php _e('Administrator Name: ', 'wcmb' ); ?></b><?php echo $wcmb_moneybird_selected_administration[0]->name; ?>
                        </p>
                        <p class="description" id="wcmb_administrator_id">
                             <b><?php _e('Administrator Id: ', 'wcmb' ); ?></b><?php echo $wcmb_moneybird_selected_administration[0]->id; ?>
                        </p>
                        <p class="description" id="wcmb_administrator_language"><b><?php _e('Administrator Language: ', 'wcmb' ); ?></b><?php echo $wcmb_moneybird_selected_administration[0]->language; ?></p>
                        <p class="description" id="wcmb_administrator_currency"><b><?php _e('Administrator Currency: ', 'wcmb' ); ?></b><?php echo $wcmb_moneybird_selected_administration[0]->currency; ?></p>
                        <p class="description" id="wcmb_administrator_country"><b><?php _e('Administrator Country: ', 'wcmb' ); ?></b><?php echo $wcmb_moneybird_selected_administration[0]->country; ?></p>
                        <p class="description" id="wcmb_administrator_time_zone"><b><?php _e('Administrator Time-Zone: ', 'wcmb' ); ?></b><?php echo $wcmb_moneybird_selected_administration[0]->time_zone; ?></p>
                    </td>
                </tr>
                <?php } ?>
                <?php
                    }
                ?>
                <tr valign="top">
                    <th scope="row">
                    </th>
                    <td>
                        <?php
                            echo '<p class="submit">';
                            if( get_option('wcmb_moneybird_client_id' ) ){

                                $client_id = get_option('wcmb_moneybird_client_id');
                                $callback = admin_url('admin.php');
                                $scopes = array("sales_invoices", "documents");
                                $getAuthorizeUrl = $this->wcmb_authorize_url_create($client_id, $callback, $scopes);
                                echo '<button id="wcmb_reset" class="button button-primary">'.__( 'Reset', 'wcmb' ).'</button>';

                            } else {
                                $create_nonce = wp_create_nonce( "wcmb-moneybird-data" );
                                echo '<input type="hidden" name="wcmb_post_security" id="wcmb_post_security" value="'.$create_nonce.'">';
                                echo '<input type="submit" name="submit" id="wcmb_access_tocken" class="button button-primary" value="'.__('Get Access Tocken', 'wcmb' ).'">';
                                $lang = get_bloginfo("language");
        
                                if($lang == 'de-DE'){
                                    $suppryURL = admin_url('admin.php?page=moneybird-support-dutch');
                                }else{
                                    $suppryURL = admin_url('admin.php?page=moneybird-support-page');
                                }
                                ?>
                                <?php
                            }
                            echo '</p>';
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
</div>
