<?php
use WHMCS\Database\Capsule;
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}
function zoho_mail_MetaData()
{    
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
                 
    $config = array (
                      'Client Id'  => array('Type'=> 'text', 'size' => '50', 'Description' => 'Created in the developer console'),
                      'Client Secret' => array('Type' => 'text', 'size' => '50', 'Description' => 'Created in the developer console'),
                      'Region' => array('Type' => 'dropdown', 'Options' => 'com,eu', 'Description' => 'Region of domain'),
                      'Redirect Url' => array('Type' => 'text', 'Default' => 'https://<mydomain>/whmcs/admin/clientsservices.php?', 'Description' => '<mydomain> refers to your domain in the url. <a href=#> Refer </a> to configure redirect url')
                  );
                  
           return $config;
}
function zoho_mail_CreateAccount(array $params)
{
    $urlChildPanel;
    try {
        $curl = curl_init();
        $arrClient = $params['clientsdetails'];
        $urlAT = 'https://accounts.zoho.'.$params['configoption3'].'/oauth/v2/token?refresh_token='.$params['configoption5'].'&grant_type=refresh_token&client_id='.$params['configoption1'].'&client_secret='.$params['configoption2'].'&redirect_uri='.$params['configoption4'].'&scope=VirtualOffice.partner.organization.CREATE,VirtualOffice.partner.organization.READ';
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
        if ( $getInfo == '200') {
               $access_tok = $accessJson->access_token;
               $bodyArr = array (
                "firstName" => $arrClient['firstname'],
                "lastName" => $arrClient['lastname'],
                "emailId" => $arrClient['email'],
                "domainName" => $params['domain']
               );
               $bodyJson = json_encode($bodyArr);
               $curlOrg = curl_init();
               $urlOrg = 'https://mail.zoho.'.$params['configoption3'].'/api/organization';
               curl_setopt_array($curlOrg, array(
                          CURLOPT_URL => $urlOrg,
                          CURLOPT_RETURNTRANSFER => true,
                          CURLOPT_ENCODING => "",
                          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                          CURLOPT_CUSTOMREQUEST => "POST",
                          CURLOPT_POSTFIELDS => $bodyJson,
                          CURLOPT_HTTPHEADER => array(
                                    "authorization: Zoho-oauthtoken ".$accessJson->access_token,
                                     "content-type: application/json"
                               ),
                        ));
                        $responseOrg = curl_exec($curlOrg);
                        $respOrgJson = json_decode($responseOrg);
                        $getInfo = curl_getinfo($curlOrg,CURLINFO_HTTP_CODE);
                        curl_close($curlOrg);
                        $urlPanel = 'https://mail.zoho.'.$params['configoption3'].'/api/organization/'.$respOrgJson->data->zoid.'?fields=encryptedZoid';
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
                                "authorization: Zoho-oauthtoken ".$accessJson->access_token
                               ),
                             ));
                        $responsePanel = curl_exec($curlPanel);
                        $respJsonPanel = json_decode($responsePanel);
                        $getPanelInfo = curl_getinfo($curlPanel, CURLINFO_HTTP_CODE);
                        curl_close($curlPanel);
                        if ($getPanelInfo == '200') {
                          $encryptedZoid = $respJsonPanel->data->encryptedZoid;
                          $urlChildPanel = 'https://mail.zoho.'.$params['configoption3'].'/cpanel/index.do?zoid='.$encryptedZoid.'&dname='.$params['domain'];
                        }
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
                                                           ':url' => $urlChildPanel
                                                      ]
                                                 );

                                                 $pdo->commit();
                                                 } catch (\Exception $e) {
                                                                 return "Uh oh! {$e->getMessage()}".$urlChildPanel;
                                                                 $pdo->rollBack();
                                                  }

                              return array ('success' => $respOrgJson->data->superAdmin);
                        } 
                        else 
                        {
                        return 'Failed    -->Description: '.$respOrgJson->status->description.' --->More Information:'.$respOrgJson->data->moreInfo;
                    }

        }
        return 'failed'.$response;
    } catch (Exception $e) {
        logModuleCall(
            'zoho_mail',
            __FUNCTION__,
            $params,
            $e->getMessage(),
            $e->getTraceAsString()
        );
        return $e->getMessage();
    }
    return 'success';
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
            'zoho_mail',
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
function zoho_mail_AdminServicesTabFields(array $params)
{


    try{
        if(isset($_GET['code'])) {
            $url = 'https://accounts.zoho.'.$params['configoption3'].'/oauth/v2/token?code='.$_GET['code'].'&client_id='.$params['configoption1'].'&client_secret='.$params['configoption2'].'&redirect_uri='.$params['configoption4'].'&scope=VirtualOffice.partner.organization.CREATE,VirtualOffice.partner.organization.READ&state=17121995&grant_type=authorization_code';
            $curl = curl_init();
            curl_setopt_array($curl, array(
                      CURLOPT_URL => $url,
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_ENCODING => "",
                      CURLOPT_MAXREDIRS => 10,
                      CURLOPT_TIMEOUT => 30,
                      CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                      CURLOPT_CUSTOMREQUEST => "POST",
                   ));

            $response = curl_exec($curl);
            $jsonDecode = json_decode($response);
            $err = curl_error($curl);
            curl_close($curl);
            try {
                  $updatedUserCount = Capsule::table('tblproducts')
                            ->where('servertype','zoho_mail')
                            ->update(
                                  [
                                   'configoption5' => $jsonDecode->refresh_token,
                                   ]
                               );

                   echo "Fixed {$updatedUserCount} misspelled last names.";
                   
                        Capsule::schema()->create(
                                        'zoho_mail',
                                   function ($table) {
                                         $table->string('domain');
                                         $table->string('zoid');
                                         $table->string('superAdmin');
                                         $table->string('isverified');
                                         $table->string('url');
                                       }
                                );
                       } 
                   
                   catch (Exception $e) {
                             logModuleCall(
                                 'provisioningmodule',
                                 __FUNCTION__,
                                $params,
                                $e->getMessage(),
                                $e->getTraceAsString()
                              );
                               
                            }
            $params['configoption3'] = $jsonDecode->refresh_token;
            ?>   <head> <meta http-equiv="refresh" content="0; url= <?php echo $params['configoption4']; ?>"/> </head>  <?php
                return array(
                    

                );

            }

        $url = 'https://accounts.zoho.'.$params["configoption3"].'/oauth/v2/auth?response_type=code&client_id='.$params["configoption1"].'&scope=VirtualOffice.partner.organization.CREATE,VirtualOffice.partner.organization.READ&redirect_uri='.$params["configoption4"].'&state=17121995&prompt=consent&access_type=offline';
        $cli = Capsule::table('zoho_mail')->where('domain',$params['domain'])->first();
        $response = array();
        $authenticateStatus;
        if ( !$params['configoption5'] == '') {
           $authenticateStatus = '<h2 style="color:green;">Authenticated</h2>';
        } else {
          $authenticateStatus = '<a href="'.$url.'" type="submit"> Click here </a> (Call only once for authenticating)';
        }
        return array(
            'Authenticate' =>
             $authenticateStatus,
             'Domain of Client Order' => $cli->domain,
             'URL of Org' => '<a href="'.$cli->url.'">Click here</a> to get org details',
             'Super Admin' => $cli->superAdmin,
             'ZOID' => $cli->zoid,
             'IS DOMAIN VERIFIED' => $cli->isverified,
             'Link to Manage customers' => '<a href="https://mailadmin.zoho.com/cpanel/index.do#managecustomers" target="blank">Click here</a>(After creation to manage your customers)'


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
