<?php
use WHMCS\Database\Capsule;
use WHMCS\Utility\Environment\WebHelper;
use WHMCS\Config\Setting;
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
function zoho_mail_MetaData()
{    try {
               Capsule::schema()->create(
                                        'zoho_mail',
                                   function ($table) {
                                         $table->string('domain');
                                         $table->string('zoid')->unique();
                                         $table->string('superAdmin');
                                         $table->string('isverified');
                                         $table->string('url');
                                       }
                                );
        } catch (Exception $e) {
    }
    return array(
        'DisplayName' => 'Zoho Mail',
        'APIVersion' => '1.1', 
        'RequiresServer' => true,
        'DefaultNonSSLPort' => '1111',
        'DefaultSSLPort' => '1112',
        'ServiceSingleSignOnLabel' => 'Login to Panel as User',
        'AdminSingleSignOnLabel' => 'Login to Panel as Admin',
    );
}

function zoho_mail_ConfigOptions()
{  
         $patharray = array();
         $patharray = explode('/',$_SERVER['REQUEST_URI']);
         $url = Setting::getValue('SystemURL');
         $patharray[1] = $url;//"http://".$_SERVER['HTTP_HOST'].substr(getcwd(),strlen($_SERVER['DOCUMENT_ROOT'])));
         $config = array (
            'Provide Zoho API credentials'=>array(
                      'Description'=>
                           '<script type="text/javascript">
                           var tabval = window.location.hash;
                           document.getElementById("zm_tab_value").value = tabval.toString();
                           </script>
                           <form action=../modules/servers/zoho_mail/zm_oauthgrant.php method=post>
                           <label>Domain</label><br>
                           <select name="zm_dn" required>
                           <option value=".com">.com</option>
                           <option value=".eu">.eu</option>
                           </select><br><br>
                           <label>Client ID</label><br>
                           <input type="text" size="60" name="zm_ci" required/><br>
                           Generated from <a href="https://accounts.localzoho.com/developerconsole" target=_blank>Zoho Developer Console</a><br><br>
                           <label>Client Secret</label><br>
                           <input type="text" size="60" name="zm_cs" required/><br>
                           Generated from <a href="https://accounts.localzoho.com/developerconsole" target=_blank>Zoho Developer Console</a><br><br>
                           <label>Admin folder name</label><br>
                           <input type="text" size="60" name="zm_ad"/><br>
                           If you have a customized WHMCS admin directory name, please enter it here. You will be redirected here after authentication. Refer here for instructions.<a href="https://www.zoho.com/mail/help/partnerportal/whmcs-integration.html" target=_blank>Refer here</a> for instructions.<br><br>
                           <label>Redirect URL</label><br>
                           <input type="text" size="60" name="zm_ru" value='.$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['SERVER_NAME'].'/modules/servers/zoho_mail/zm_oauthgrant.php required readonly/><br>
                           Redirect URL used to generate Client ID and Client Secret.<br><br>
                           <input type="hidden" id="zm_tab_value" name="zm_tab_value" value=""/>
                           <input type="hidden" name="zm_pi" value='.$_REQUEST['id'].'>
                           <button name="zm_submit" size="15">Authenticate</button>
                           </form>'
                      )
                  );
          try {
            if (Capsule::schema()->hasTable('zoho_mail_auth_table')) 
            {
              $count = 0;
              $list = 0;
              foreach (Capsule::table('zoho_mail_auth_table')->get() as $client) {
                  if (strpos($client->token, 'tab') == false && strlen($client->token) > 1 ){
                    $list = $list + 1;
                    $count = 1;
                  } 
                }
              if ($count > 0 && $list > 0) { 
              $config = array (
              'Status' => array('Description'=>' <label style="color:green;"> Authenticated Successfully </label>')
              );
            }
            
          } 
         } catch(Exception $e) {

          }
        return $config;
}

function zoho_mail_TestConnection(array $params)
{
    try {
        // Call the service's connection test function.

        $success = true;
        $errorMsg = '';
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'provisioningmodule',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        $success = false;
        $errorMsg = $e->getMessage();
    }

    return array(
        'success' => $success,
        'error' => $errorMsg,
    );
}

function zoho_mail_AdminCustomButtonArray()
{
    return array(
        "Create Mail Premium" => "buttonOneFunction",
        "Create Workplace Standard" => "buttonTwoFunction",
        "Create Workplace Professional" => "buttonThreeFunction",
        "Create Mail Free" => "buttonFourFunction"
    );
}

function zoho_mail_buttonOneFunction(array $params)
{
    try {
      return create_child_organization($params, "mailPremiumTrial", get_access_token($params));
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'zoho_maill',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function zoho_mail_buttonTwoFunction(array $params)
{
    try {
      return create_child_organization($params, "basicTrial", get_access_token($params));
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'zoho_maill',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function zoho_mail_buttonThreeFunction(array $params)
{
    try {
      return create_child_organization($params, "professionalTrial", get_access_token($params));
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'zoho_maill',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}

function zoho_mail_buttonFourFunction(array $params)
{
    try {
      return create_child_organization($params, "free", get_access_token($params));
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'zoho_maill',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );

        return $e->getMessage();
    }

    return 'success';
}




function zoho_mail_AdminServicesTabFields(array $params)
{


    try{

        $cli = Capsule::table('zoho_mail')->where('domain',$params['domain'])->first();
        $response = array();
        $$authenticateStatus = '<h2 style="color:red;">UnAuthenticated</h2>';;
        if (Capsule::schema()->hasTable('zoho_mail_auth_table')) 
            {
              $count = 0;
              $list = 0;
              foreach (Capsule::table('zoho_mail_auth_table')->get() as $client) {
                  $list = $list + 1;
                  if ( $client->token =='test'){
                    $count = 1;
                  } 
                }
              if ($count == 0 && $list > 0) { 
                $authenticateStatus = '<h2 style="color:green;">Authenticated</h2>';
              }
            }
        
        $verificationStatus;
        if (strcmp("true",$cli->isverified) == 0) {
                 $verificationStatus = '<b style=color:green>Verified</b>';
        } else {
                 $verificationStatus = '<b style=color:red>Not Verified</b>';
        }

        return array(
             'Client Domain' => $cli->domain,
             'Client Control Panel' => '<a href="'.$cli->url.'" target=_blank>Click here</a>',
             'Super Administrator' => $cli->superAdmin,
             'ZOID' => $cli->zoid,
             'Domain verification status' => $verificationStatus,
             'URL to Manage Customers' => '<a href="https://mailadmin.localzoho.com/cpanel/index.do#managecustomers" target="blank">Click here</a>'


        );
    } catch (Exception $e) {
        logModuleCall(
            'zoho_mail',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
    }
    return array();
}
function zoho_mail_AdminServicesTabFieldsSave(array $params)
{
    // Fetch form submission variables.
    $originalFieldValue = isset($_REQUEST['zoho_mail_original_uniquefieldname'])
        ? $_REQUEST['zoho_mail_original_uniquefieldname']
        : '';
    $newFieldValue = isset($_REQUEST['zoho_mail_uniquefieldname'])
        ? $_REQUEST['zoho_mail_uniquefieldname']
        : '';
    if ($originalFieldValue != $newFieldValue) {
        try {
        } catch (Exception $e) {
            logModuleCall(
                'zoho_mail',
                __FUNCTION__,
                $params,
                $e->getMessage(),
                $e->getTraceAsString()
            );
        }
    }
}
function zoho_mail_ServiceSingleSignOn(array $params)
{
    try {
        $response = array();
        return array(
            'success' => true,
            'redirectTo' => $response['redirectUrl'],
        );
    } catch (Exception $e) {
        logModuleCall(
            'zoho_mail',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return array(
            'success' => false,
            'errorMsg' => $e->getMessage(),
        );
    }
}
function zoho_mail_AdminSingleSignOn(array $params)
{
    try {
        // Call the service's single sign-on admin token retrieval function,
        // using the values provided by WHMCS in `$params`.
        $response = array();
        return array(
            'success' => true,
            'redirectTo' => $response['redirectUrl'],
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'zoho_mail',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return array(
            'success' => false,
            'errorMsg' => $e->getMessage(),
        );
    }
}
function zoho_mail_ClientArea(array $params)
{
    $serviceAction = 'get_stats';
    $templateFile = 'templates/overview.tpl';
    try {
      $cli = Capsule::table('zoho_mail')->where('domain',$params['domain'])->first();
      $urlToPanel = $cli->url;
        return array(
            'tabOverviewReplacementTemplate' => $templateFile,
            'templateVariables' => array(
             'mailUrl' => 'https://mail.zoho.com',
             'panelUrl' => $urlToPanel
            ),
        );
    } catch (Exception $e) {
        // Record the error in WHMCS's module log.
        logModuleCall(
            'zoho_mail',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        // In an error condition, display an error page.
        return array(
            'tabOverviewReplacementTemplate' => 'error.tpl',
            'templateVariables' => array(
                'usefulErrorHelper' => $e->getMessage(),
            ),
        );
    }
}



function get_access_token(array $params) {

        $curl = curl_init();
        $cli = Capsule::table('zoho_mail_auth_table')->first();
        $urlAT = 'https://accounts.localzoho'.$cli->region.'/oauth/v2/token?refresh_token='.$cli->token.'&grant_type=refresh_token&client_id='.$cli->clientId.'&client_secret='.$cli->clientSecret.'&redirect_uri='.$cli->redirectUrl.'&scope=VirtualOffice.partner.organization.CREATE,VirtualOffice.partner.organization.READ';
        curl_setopt_array($curl, array(
                  CURLOPT_URL => $urlAT,
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => "POST"
                 ));
 
        $response = curl_exec($curl);
        $accessJson = json_decode($response);
        $getInfo = curl_getinfo($curl,CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $accessJson->access_token;

}


function create_child_organization(array $params, $planName, $accessToken) {
              $arrClient = $params['clientsdetails'];
              $cli = Capsule::table('zoho_mail_auth_table')->first();
              $bodyArr = array (
                "firstName" => $arrClient['firstname'],
                "lastName" => $arrClient['lastname'],
                "emailId" => $arrClient['email'],
                "domainName" => $params['domain'],
                "planName" => $planName
               );
               $bodyJson = json_encode($bodyArr);
               $curlOrg = curl_init();
               $urlOrg = 'https://mail.localzoho'.$cli->region.'/api/organization';
               curl_setopt_array($curlOrg, array(
                          CURLOPT_URL => $urlOrg,
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_ENCODING => "",
                          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                          CURLOPT_CUSTOMREQUEST => "POST",
                          CURLOPT_POSTFIELDS => $bodyJson,
                          CURLOPT_HTTPHEADER => array(
                                    "authorization: Zoho-oauthtoken ".$accessToken,
                                    "content-type: application/json"
                               ),
                        ));
               $responseOrg = curl_exec($curlOrg);
               $respOrgJson = json_decode($responseOrg);
               $getInfo = curl_getinfo($curlOrg,CURLINFO_HTTP_CODE);
               curl_close($curlOrg);
               if ( $getInfo == '200') 
                        {
                                        $pdo = Capsule::connection()->getPdo();
                                        $pdo->beginTransaction();

                                         try {
                                            $statement = $pdo->prepare(
                                                   'insert into zoho_mail (domain, zoid, isverified, superAdmin, url) values (:domain, :zoid, :isverified, :superAdmin, :url)'
                                                   );

                                             $statement->execute(
                                                     [
                                                           ':domain' => $respOrgJson->data->domainName,
                                                           ':zoid' => $respOrgJson->data->zoid,
                                                           ':isverified' => ($respOrgJson->data->isVerified)?'true':'false',
                                                           ':superAdmin' => $respOrgJson->data->superAdmin,
                                                           ':url' => get_child_org_url($params, $accessToken, $respOrgJson->data->zoid)
                                                      ]
                                                 );

                                                 $pdo->commit();
                                                 } catch (Exception $e) {
                                                                 return "Uh oh! {$e->getMessage()}".$urlChildPanel;
                                                                 $pdo->rollBack();
                                                  }

                              return array ('success' => 'Mailbox has been created.'.$respOrgJson);
                        } else if ($getInfo == '400') {
                          $updatedUserCount = Capsule::table('tblproducts')
                            ->where('servertype','zoho_mail')
                            ->update(
                                  [
                                   'configoption5' => '',
                                   ]
                               );
                        }
                        else {
                        return 'Failed -->Description: '.$respOrgJson->status->description.' --->More Information:'.$respOrgJson->data->moreInfo.'--------------'.$getInfo.'--------'.$bodyJson;
                    }

}


function get_child_org_url(array $params, $accessToken, $zoid) {
                        $cli = Capsule::table('zoho_mail_auth_table')->first();
                        $urlPanel = 'https://mail.localzoho'.$cli->region.'/api/organization/'.$zoid.'?fields=encryptedZoid';
                        $curlPanel = curl_init();
                            curl_setopt_array($curlPanel, array(
                            CURLOPT_URL => $urlPanel,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_ENCODING => "",
                            CURLOPT_MAXREDIRS => 10,
                            CURLOPT_TIMEOUT => 30,
                            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                            CURLOPT_CUSTOMREQUEST => "GET",
                            CURLOPT_HTTPHEADER => array(
                                "authorization: Zoho-oauthtoken ".$accessToken
                               ),
                             ));
                        $responsePanel = curl_exec($curlPanel);
                        $respJsonPanel = json_decode($responsePanel);
                        $getPanelInfo = curl_getinfo($curlPanel, CURLINFO_HTTP_CODE);
                        curl_close($curlPanel);
                        if ($getPanelInfo == '200') {
                          $encryptedZoid = $respJsonPanel->data->encryptedZoid;
                           return 'https://mail.localzoho'.$cli->region.'/cpanel/index.do?zoid='.$encryptedZoid.'&dname='.$params['domain'];
                        }
                        return null;

}
