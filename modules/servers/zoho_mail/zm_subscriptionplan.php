<?php

use WHMCS\Config;
use WHMCS\Product;
use WHMCS\Database\Capsule;
use WHMCS\Config\Setting;

require '../../../init.php';


$userid = $_REQUEST['userID'];
$case = $_REQUEST['case'];

if($case == "getOrgdetails")
{
    $conn = Capsule::connection()->getPdo();
    if(Capsule::schema()->hasTable('tblclients')){
        $sql = "SELECT email FROM tblclients WHERE id={$userid}";
        $result = $conn->query($sql);
        $clientdetails = $result->fetch();
    }
    $email = $clientdetails['email'];
    
    $row = Capsule::table('zoho_mail')->where('superAdmin',$email)->first();
    $domain = $row->domain;
    $accessToken = get_access_token();
    $status = getOrgDetails($accessToken,$domain);
}else if($case == "getSubscription details")
{
    $conn = Capsule::connection()->getPdo();
    if(Capsule::schema()->hasTable('tblclients')){
        $sql = "SELECT email FROM tblclients WHERE id={$userid}";
        $result = $conn->query($sql);
        $clientdetails = $result->fetch();
    }
    $email = $clientdetails['email'];
    $accessToken = get_access_token();
    
    $status = getSubscriptionPlan($accessToken,$email);
}
echo $status;
 
function getSubscriptionPlan($accessToken,$email) {
    $cli1 = Capsule::table('zoho_mail')->where('domain',$params['domain'])->first();
    $arrClient = $params['clientsdetails'];
    $cli = Capsule::table('zoho_mail_auth_table')->first();
    $bodyArr = array (
        "serviceid" => 1501,
        "email" => $email
    );
    $bodyJson = json_encode($bodyArr);
    $curlOrg = curl_init();
    $urlOrg = 'https://store.zoho'.$cli->region.'/restapi/partner/v1/json/subscription?JSONString=%7B%22serviceid%22%3A1501%2C%22email%22%3A%22'.$email.'%22%7D';
    curl_setopt_array($curlOrg, array(
        CURLOPT_URL => $urlOrg,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "authorization: Zoho-oauthtoken ".$accessToken
        ),
    ));
    $responseOrg = curl_exec($curlOrg);
    $respOrgJson = json_decode($responseOrg,false);
    $arr = json_decode($responseOrg,true);
    $getInfo = curl_getinfo($curlOrg,CURLINFO_HTTP_CODE);
    curl_close($curlOrg);
    if ( $getInfo == '200')
    {
        if($respOrgJson->result == "success" && !$arr["licensedetails"]["paiduser"]){
            
            $cli = Capsule::table('zoho_mail')->where('superAdmin',$email)->first();
            $cli1 = Capsule::table('zoho_mail_auth_table')->first();
                         $curlOrg1 = curl_init();
                         $urlOrg1 = 'https://mail.zoho'.$cli1->region.'/api/organization/'.(string)$cli->zoid;
                         
                         curl_setopt_array($curlOrg1, array(
                         CURLOPT_URL => $urlOrg1,
                         CURLOPT_RETURNTRANSFER => true,
                         CURLOPT_ENCODING => "",
                         CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                         CURLOPT_CUSTOMREQUEST => "GET",
                         CURLOPT_HTTPHEADER => array(
                             "authorization: Zoho-oauthtoken ".$accessToken
                             )
                         ));
                         
                         $responseOrg1 = curl_exec($curlOrg1);
                         $respOrgJson1 = json_decode($responseOrg1);
                         $arr = json_decode($responseOrg1,true);
                         $getInfo = curl_getinfo($curlOrg1,CURLINFO_HTTP_CODE);
                         curl_close($curlOrg1);
                         if ( $getInfo == '200')
                             {
                                 return $arr["data"]["basePlan"];
                         }
                         return $responseOrg1;
        }
        else if($respOrgJson->result == "success"){
            return $responseOrg;
        }
    }
    return "Not paid user";
}


function getOrgDetails($accessToken,$domain)
{
    $curlOrg = curl_init();
    $urlOrg = 'https://mail.zoho.com/api/organization?mode=getCustomerOrgDetails&searchKey='.$domain;
    
    curl_setopt_array($curlOrg, array(
        CURLOPT_URL => $urlOrg,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "authorization: Zoho-oauthtoken " . $accessToken
        )
    ));
    $responseOrg = curl_exec($curlOrg);
    $respOrgJson = json_decode($responseOrg);
    $arr = json_decode($responseOrg,true);
    $getInfo = curl_getinfo($curlOrg,CURLINFO_HTTP_CODE);
    curl_close($curlOrg);
    if ( $getInfo == '200')
    {
        if($respOrgJson->status->description == "success")
        {
            echo $responseOrg;
        }
    }
    else
        echo $responseOrg;
}

function get_access_token() {
    $curl = curl_init();
    $cli = Capsule::table('zoho_mail_auth_table')->first();
    $urlAT = 'https://accounts.zoho'.$cli->region.'/oauth/v2/token?refresh_token='.$cli->token.'&grant_type=refresh_token&client_id='.$cli->clientId.'&client_secret='.$cli->clientSecret.'&redirect_uri='.$cli->redirectUrl.'&scope=VirtualOffice.partner.organization.CREATE,VirtualOffice.partner.organization.UPDATE,VirtualOffice.partner.organization.READ,ZohoPayments.partnersubscription.all,ZohoPayments.fullaccess.READ,ZohoPayments.leads.READ';
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
?>